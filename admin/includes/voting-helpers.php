<?php
/**
 * voting-helpers.php — logika bersama fitur Voting Mubes, dipakai
 * halaman publik voting.php dan halaman admin voting-*.php.
 *
 * Kerahasiaan suara (lihat juga database/voting.sql):
 *  - vote_voters nyatet SIAPA yang sudah memilih.
 *  - vote_ballots nyatet ISI suara. voter_id cuma terisi selama voting
 *    buka (buat tombol "Batalkan suara"), lalu dikosongkan permanen
 *    begitu voting ditutup -- lihat votingState().
 */

date_default_timezone_set('Asia/Jakarta');

/**
 * Tambahan kolom/tabel pengaman perangkat. Dijalankan otomatis supaya
 * database yang sudah terlanjur di-import versi lama tetap jalan
 * (isinya sama dengan bagian akhir database/voting.sql).
 */
function votingEnsureSchema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try { $pdo->query('SELECT device_id FROM vote_log LIMIT 1'); }
    catch (PDOException $e) { $pdo->exec('ALTER TABLE vote_log ADD COLUMN device_id VARCHAR(40) NULL'); }
    try { $pdo->query('SELECT device_ok FROM vote_voters LIMIT 1'); }
    catch (PDOException $e) { $pdo->exec('ALTER TABLE vote_voters ADD COLUMN device_ok TINYINT(1) NOT NULL DEFAULT 0'); }
    $pdo->exec('CREATE TABLE IF NOT EXISTS vote_devices (
        device_id VARCHAR(40) NOT NULL,
        voter_id INT NOT NULL,
        created_at DATETIME NULL,
        PRIMARY KEY (device_id, voter_id)
    )');
}

function votingSettings(PDO $pdo): array {
    votingEnsureSchema($pdo);
    $row =$pdo->query('SELECT * FROM vote_settings WHERE id = 1')->fetch();
    if (!$row) {
        $pdo->exec('INSERT INTO vote_settings (id) VALUES (1)');
        $row = $pdo->query('SELECT * FROM vote_settings WHERE id = 1')->fetch();
    }
    return $row;
}

/** Status voting: 'draft' (jadwal belum diatur) | 'scheduled' | 'open' | 'closed'. */
function votingStatus(array $s): string {
    if ((int)$s['closed_manually'] === 1) return 'closed';
    if (empty($s['start_at']) || empty($s['end_at'])) return 'draft';
    $now = time();
    if ($now < strtotime($s['start_at'])) return 'scheduled';
    if ($now >= strtotime($s['end_at'])) return 'closed';
    return 'open';
}

/**
 * Ambil pengaturan + status. Kalau voting sudah tutup (manual ATAU karena
 * jadwal habis), sambungan NPM <-> suara dihapus permanen di sini. Tidak
 * butuh cron: dijalankan di halaman voting mana pun yang pertama dibuka
 * setelah voting tutup.
 */
function votingState(PDO $pdo): array {
    $s = votingSettings($pdo);
    $status = votingStatus($s);
    if ($status === 'closed' && (int)$s['link_wiped'] === 0) {
        $pdo->exec('UPDATE vote_ballots SET voter_id = NULL');
        $pdo->exec('UPDATE vote_settings SET link_wiped = 1 WHERE id = 1');
        $s['link_wiped'] = 1;
    }
    return ['settings' => $s, 'status' => $status];
}

function votingStatusLabel(string $status): string {
    return [
        'draft'     => 'Jadwal belum diatur',
        'scheduled' => 'Belum dibuka',
        'open'      => 'Sedang berlangsung',
        'closed'    => 'Ditutup',
    ][$status] ?? $status;
}

function votingCandidates(PDO $pdo): array {
    return $pdo->query('SELECT * FROM vote_candidates ORDER BY number ASC, id ASC')->fetchAll();
}

function votingHasWakil(array $candidate): bool {
    return trim((string)($candidate['wakil_name'] ?? '')) !== '';
}

/**
 * Daftar pilihan di surat suara. Key-nya string: id paslon, atau 'kosong'
 * untuk Kotak Kosong (otomatis muncul kalau paslonnya cuma 1).
 */
function votingOptions(array $candidates): array {
    $options = [];
    foreach ($candidates as $c) {
        $options[] = [
            'key'    => (string)$c['id'],
            'number' => (int)$c['number'],
            // Wakil opsional: tanpa wakil disebut "Calon", dengan wakil "Paslon"
            'label'  => (votingHasWakil($c) ? 'Paslon ' : 'Calon ') . sprintf('%02d', $c['number']),
            'names'  => $c['ketua_name'] . (votingHasWakil($c) ? ' & ' . $c['wakil_name'] : ''),
            'photo'  => $c['photo'],
            'kosong' => false,
        ];
    }
    if (count($candidates) === 1) {
        $options[] = [
            'key'    => 'kosong',
            'number' => null,
            'label'  => 'Kotak Kosong',
            'names'  => 'Tidak setuju dengan calon tunggal',
            'photo'  => null,
            'kosong' => true,
        ];
    }
    return $options;
}

/** Perolehan suara per pilihan: ['<key>' => jumlah]. */
function votingTally(PDO $pdo, array $options): array {
    $tally = [];
    foreach ($options as $o) $tally[$o['key']] = 0;
    foreach ($pdo->query('SELECT candidate_id, COUNT(*) AS total FROM vote_ballots GROUP BY candidate_id') as $row) {
        $key = $row['candidate_id'] === null ? 'kosong' : (string)$row['candidate_id'];
        if (isset($tally[$key])) $tally[$key] = (int)$row['total'];
    }
    return $tally;
}

function votingParticipation(PDO $pdo): array {
    $row = $pdo->query('SELECT COUNT(*) AS total, COALESCE(SUM(has_voted), 0) AS voted FROM vote_voters')->fetch();
    return ['total' => (int)$row['total'], 'voted' => (int)$row['voted']];
}

function normalizeNpm(string $npm): string {
    return preg_replace('/\s+/', '', trim($npm));
}

/**
 * Simpan satu suara. Return null kalau berhasil, 'sudah' kalau NPM ini
 * ternyata sudah memilih, atau pesan error lain.
 * Baris peserta dikunci (FOR UPDATE) + voter_id UNIQUE, jadi klik dobel
 * atau 2 HP yang submit barengan tetap cuma menghasilkan 1 suara.
 */
function castBallot(PDO $pdo, int $voterId, string $choice, string $deviceId): ?string {
    $candidateId = $choice === 'kosong' ? null : (int)$choice;
    $lock = DB_DRIVER === 'mysql' ? ' FOR UPDATE' : '';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM vote_voters WHERE id = ?' . $lock);
        $stmt->execute([$voterId]);
        $voter = $stmt->fetch();

        if (!$voter) {
            $pdo->rollBack();
            return 'Data peserta tidak ditemukan.';
        }
        if ((int)$voter['has_voted'] === 1) {
            $pdo->rollBack();
            votingLog($pdo, $voterId, $voter['npm'], 'ditolak_sudah_memilih');
            return 'sudah';
        }
        // Dicek ulang di sini (bukan cuma saat cek NPM) supaya tidak bisa
        // diakali dengan membuka 2 tab sekaligus.
        if (votingDeviceBlocked($pdo, $deviceId, $voter)) {
            $pdo->rollBack();
            votingLog($pdo, $voterId, $voter['npm'], 'ditolak_perangkat');
            return 'perangkat';
        }

        $pdo->prepare('INSERT INTO vote_ballots (id, candidate_id, voter_id) VALUES (?, ?, ?)')
            ->execute([bin2hex(random_bytes(8)), $candidateId, $voterId]);
        $pdo->prepare('UPDATE vote_voters SET has_voted = 1, voted_at = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s'), $voterId]);
        // Perangkat ini sekarang "terpakai" oleh peserta ini (bukan isi pilihannya).
        $seen = $pdo->prepare('SELECT COUNT(*) FROM vote_devices WHERE device_id = ? AND voter_id = ?');
        $seen->execute([$deviceId, $voterId]);
        if ((int)$seen->fetchColumn() === 0) {
            $pdo->prepare('INSERT INTO vote_devices (device_id, voter_id, created_at) VALUES (?, ?, ?)')
                ->execute([$deviceId, $voterId, date('Y-m-d H:i:s')]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return 'Gagal menyimpan suara. Silakan coba lagi.';
    }

    votingLog($pdo, $voterId, $voter['npm'], 'memilih');
    return null;
}

/** Catat aktivitas + info perangkat buat audit panitia (tanpa isi pilihan). */
function votingLog(PDO $pdo, ?int $voterId, ?string $npm, string $event): void {
    votingEnsureSchema($pdo);
    $stmt = $pdo->prepare('INSERT INTO vote_log (voter_id, npm, event, ip, user_agent, device_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $voterId,
        $npm === null ? null : substr($npm, 0, 30),
        $event,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        votingDeviceId(false),
        date('Y-m-d H:i:s'),
    ]);
}

/* ================= PENGAMAN PERANGKAT =================
 * Tiap browser yang membuka halaman voting dapat ID acak di cookie.
 * Satu ID perangkat cuma boleh dipakai memilih untuk SATU NPM, kecuali
 * panitia mengizinkan NPM tertentu (device_ok = 1), misalnya yang
 * meminjam HP teman. Ini pagar ringan (bisa diakali incognito);
 * pelaku yang mengakalinya masih tercatat di Log aktivitas (IP, ID HP, tipe HP).
 */
const VOTING_DEVICE_COOKIE = 'hmse_vdev';

/**
 * ID perangkat dari cookie. $create = true: buat & kirim cookie baru kalau
 * belum ada (panggil sebelum ada output HTML). $create = false: cuma baca.
 */
function votingDeviceId(bool $create = true): ?string {
    static $id = null;
    if ($id !== null) return $id;
    $c = $_COOKIE[VOTING_DEVICE_COOKIE] ?? '';
    if (is_string($c) && preg_match('/^[a-f0-9]{32}$/', $c)) {
        return $id = $c;
    }
    if (!$create) return null;
    $id = bin2hex(random_bytes(16));
    setcookie(VOTING_DEVICE_COOKIE, $id, [
        'expires'  => time() + 60 * 60 * 24 * 90,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[VOTING_DEVICE_COOKIE] = $id;
    return $id;
}

/** true kalau perangkat ini sudah dipakai memilih untuk NPM LAIN dan peserta ini belum diizinkan. */
function votingDeviceBlocked(PDO $pdo, string $deviceId, array $voter): bool {
    if ((int)($voter['device_ok'] ?? 0) === 1) return false;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM vote_devices WHERE device_id = ? AND voter_id <> ?');
    $stmt->execute([$deviceId, (int)$voter['id']]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Batalkan suara satu peserta (cuma bisa selama sambungan NPM-suara belum
 * dihapus). Return NPM-nya kalau berhasil, null kalau tidak ada suaranya.
 */
function votingCancelVote(PDO $pdo, int $voterId): ?string {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT npm FROM vote_voters WHERE id = ? AND has_voted = 1');
    $stmt->execute([$voterId]);
    $npm = $stmt->fetchColumn();
    $del = $pdo->prepare('DELETE FROM vote_ballots WHERE voter_id = ?');
    $del->execute([$voterId]);
    if ($npm === false || $del->rowCount() !== 1) {
        $pdo->rollBack();
        return null;
    }
    $pdo->prepare('UPDATE vote_voters SET has_voted = 0, voted_at = NULL WHERE id = ?')->execute([$voterId]);
    // Bebaskan perangkat yang dipakai untuk suara ini, supaya pemiliknya bisa
    // memilih lagi pakai NPM mana pun (mis. tadi salah ketik NPM orang lain).
    $pdo->prepare('DELETE FROM vote_devices WHERE voter_id = ?')->execute([$voterId]);
    $pdo->commit();
    votingLog($pdo, $voterId, $npm, 'dibatalkan_admin');
    return $npm;
}

/** "Budi Santoso Putra" -> "Budi S. P." (nama lengkap tidak ditampilkan ke publik). */
function votingMaskName(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    if (!$parts) return '';
    $out = array_shift($parts);
    foreach ($parts as $p) $out .= ' ' . mb_strtoupper(mb_substr($p, 0, 1)) . '.';
    return $out;
}

/** Ringkas user agent jadi "Android 14 · Redmi Note 12 · Chrome" buat tabel admin. */
function votingShortUa(?string $ua): string {
    $ua = (string)$ua;
    if ($ua === '') return '-';
    $os = '';
    if (preg_match('/Android ([\d.]+);\s*([^;)]+)/', $ua, $m)) $os = 'Android ' . $m[1] . ' · ' . trim(preg_replace('/\s*Build\/.*$/', '', $m[2]));
    elseif (preg_match('/iPhone OS ([\d_]+)/', $ua, $m)) $os = 'iPhone iOS ' . str_replace('_', '.', $m[1]);
    elseif (preg_match('/iPad/', $ua)) $os = 'iPad';
    elseif (preg_match('/Windows NT/', $ua)) $os = 'Windows';
    elseif (preg_match('/Mac OS X/', $ua)) $os = 'Mac';
    elseif (preg_match('/Linux/', $ua)) $os = 'Linux';
    $br = 'Browser lain';
    foreach (['SamsungBrowser' => 'Samsung Internet', 'OPR' => 'Opera', 'Edg' => 'Edge', 'Firefox' => 'Firefox', 'FxiOS' => 'Firefox', 'CriOS' => 'Chrome', 'Chrome' => 'Chrome', 'Safari' => 'Safari'] as $k => $label) {
        if (strpos($ua, $k) !== false) { $br = $label; break; }
    }
    return trim(($os ? $os . ' · ' : '') . $br);
}

/** Format tanggal-jam ala Indonesia, mis. "20 Nov 2026, 08:00". */
function formatWaktu(?string $datetime): string {
    if (!$datetime) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($datetime);
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y, H:i', $t);
}

/** URL absolut halaman voting publik (dipakai buat QR code). */
function votingPublicUrl(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        // di balik ngrok / proxy hosting, koneksi ke PHP-nya http tapi yang dibuka orang https
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $root = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $root . '/voting.php';
}
