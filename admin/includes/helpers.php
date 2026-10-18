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