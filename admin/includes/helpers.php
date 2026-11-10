<?php
/**
 * helpers.php — fungsi-fungsi kecil yang dipakai bareng di banyak
 * halaman admin (upload gambar, bikin link WA/IG/email otomatis dari
 * input polos, dan beberapa util umum).
 */

const UPLOAD_DIR = __DIR__ . '/../../images/';
const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

/**
 * Proses upload satu file gambar dari <input type="file" name="$fieldName">.
 * - Kalau ada file baru yang valid, disimpan ke folder images/ dengan nama
 *   unik, lalu nama filenya dikembalikan.
 * - Kalau tidak ada file baru (mis. form edit yang fotonya tidak diganti),
 *   $existingFilename dikembalikan apa adanya.
 * - Kalau gagal / ekstensi tidak didukung, return null (kosong).
 */
function handleImageUpload(string $fieldName, ?string $existingFilename = null): ?string {

    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingFilename;
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return $existingFilename;
    }

    $tmpPath = $_FILES[$fieldName]['tmp_name'];
    $originalName = $_FILES[$fieldName]['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
        return $existingFilename;
    }

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0777, true);
    }

    $newFilename = uniqid('img_', true) . '.' . $ext;
    $destination = UPLOAD_DIR . $newFilename;

    if (move_uploaded_file($tmpPath, $destination)) {
        return $newFilename;
    }

    return $existingFilename;
}

/**
 * Proses upload BEBERAPA file sekaligus dari <input type="file" name="$fieldName[]" multiple>.
 * Return array nama file yang berhasil diupload (yang gagal/invalid dilewati saja).
 */
function handleMultipleImageUploads(string $fieldName): array {

    if (!isset($_FILES[$fieldName])) return [];

    $files = $_FILES[$fieldName];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    $uploaded = [];

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0777, true);
    }

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) continue;

        $newFilename = uniqid('img_', true) . '.' . $ext;
        if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_DIR . $newFilename)) {
            $uploaded[] = $newFilename;
        }
    }

    return $uploaded;
}

/** Ubah nomor WhatsApp polos (mis. "081234567890" atau "0812-3456-7890")
 * jadi link wa.me yang valid. Angka yang diawali "0" diubah ke "62"
 * (konvensi nomor Indonesia), sesuai standar wa.me.
 */
function buildWhatsappLink(?string $rawNumber): ?string {
    if (!$rawNumber) return null;
    $digits = preg_replace('/\D/', '', $rawNumber);
    if ($digits === '') return null;
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}

/** Ubah username Instagram polos (boleh pakai "@" atau tidak) jadi link profil. */
function buildInstagramLink(?string $rawUsername): ?string {
    if (!$rawUsername) return null;
    $clean = ltrim(trim($rawUsername), '@');
    if ($clean === '') return null;
    return 'https://instagram.com/' . rawurlencode($clean);
}

/** Ubah alamat email polos jadi link mailto:. */
function buildEmailLink(?string $rawEmail): ?string {
    if (!$rawEmail) return null;
    return 'mailto:' . trim($rawEmail);
}

/** Bikin slug URL-friendly dari judul (dipakai buat News). */
function makeSlug(string $text): string {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

/** Escape singkat biar tidak berulang nulis htmlspecialchars(...) terus. */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Hapus file gambar hasil upload dari folder images/.
 * Aman dipanggil sembarang: cuma file hasil handleImageUpload() (img_xxx.ext)
 * yang bisa terhapus, jadi aset statis seperti logo2.png tidak akan kena.
 * Kalau $pdo diisi, file TIDAK dihapus selama masih dipakai di tabel lain.
 */
function deleteUploadedImage(?string $filename, ?PDO $pdo = null): bool {
    if (!$filename) return false;
    $filename = basename($filename);
    if (!preg_match('/^img_[0-9a-f]+\.[0-9]+\.(jpe?g|png|gif|webp)$/i', $filename)) return false;

    if ($pdo) {
        $refs = [
            'SELECT COUNT(*) FROM vote_candidates WHERE photo = ?',
            'SELECT COUNT(*) FROM gallery_photos WHERE photo = ?',
            'SELECT COUNT(*) FROM events WHERE photo = ?',
            'SELECT COUNT(*) FROM news_photos WHERE filename = ?',
            'SELECT COUNT(*) FROM struktur_nodes WHERE photo = ?',
        ];
        foreach ($refs as $sql) {
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$filename]);
                if ((int)$stmt->fetchColumn() > 0) return false;
            } catch (PDOException $e) {
                // tabel/kolom belum ada di database ini: lewati
            }
        }
    }

    $path = UPLOAD_DIR . $filename;
    return is_file($path) && @unlink($path);
}

/* ================= ANTI-CSRF =================
 * Token acak per session. Taruh csrfField() di dalam tiap <form method="POST">,
 * lalu cek csrfCheck() sebelum memproses POST-nya.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}

function csrfCheck(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf']);
}

/* ================= PEMBATAS PERCOBAAN (rate limit) =================
 * Dipakai buat login admin & cek NPM voting, supaya tidak bisa ditebak
 * pakai script. Disimpan di tabel kecil `rate_limits` (dibuat otomatis).
 * $key contoh: 'login_ip:1.2.3.4'. Waktu disimpan sebagai unix timestamp.
 */
function rateLimitTable(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $pdo->exec('CREATE TABLE IF NOT EXISTS rate_limits (
        k VARCHAR(120) NOT NULL PRIMARY KEY,
        hits INT NOT NULL DEFAULT 0,
        reset_at INT NOT NULL
    )');
    $done = true;
}

/** Sisa detik kunci kalau $key sudah mencapai $max percobaan; 0 kalau masih boleh. */
function rateLimitWait(PDO $pdo, string $key, int $max): int {
    rateLimitTable($pdo);
    $stmt = $pdo->prepare('SELECT hits, reset_at FROM rate_limits WHERE k = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['reset_at'] <= time()) return 0;
    return (int)$row['hits'] >= $max ? (int)$row['reset_at'] - time() : 0;
}

/** Catat satu percobaan untuk $key. Hitungan direset setelah $windowSec detik. */
function rateLimitHit(PDO $pdo, string $key, int $windowSec): void {
    rateLimitTable($pdo);
    $now = time();
    $upd = $pdo->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE k = ? AND reset_at > ?');
    $upd->execute([$key, $now]);
    if ($upd->rowCount() > 0) return;
    // Belum ada / sudah kedaluwarsa: mulai hitungan baru.
    $pdo->prepare('DELETE FROM rate_limits WHERE k = ?')->execute([$key]);
    try {
        $pdo->prepare('INSERT INTO rate_limits (k, hits, reset_at) VALUES (?, 1, ?)')->execute([$key, $now + $windowSec]);
    } catch (PDOException $e) {
        // request lain barusan membuat baris yang sama: cukup tambah hitungannya
        $pdo->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE k = ?')->execute([$key]);
    }
}

function rateLimitClear(PDO $pdo, string $key): void {
    rateLimitTable($pdo);
    $pdo->prepare('DELETE FROM rate_limits WHERE k = ?')->execute([$key]);
}

/** IP pengunjung. Sengaja TIDAK membaca X-Forwarded-For karena header itu bisa dipalsukan. */
function clientIp(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
