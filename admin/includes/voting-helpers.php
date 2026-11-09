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

function votingSettings(PDO $pdo): array {
    $row = $pdo->query('SELECT * FROM vote_settings WHERE id = 1')->fetch();
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
function castBallot(PDO $pdo, int $voterId, string $choice): ?string {
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

        $pdo->prepare('INSERT INTO vote_ballots (id, candidate_id, voter_id) VALUES (?, ?, ?)')
            ->execute([bin2hex(random_bytes(8)), $candidateId, $voterId]);
        $pdo->prepare('UPDATE vote_voters SET has_voted = 1, voted_at = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s'), $voterId]);
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
    $stmt = $pdo->prepare('INSERT INTO vote_log (voter_id, npm, event, ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $voterId,
        $npm,
        $event,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        date('Y-m-d H:i:s'),
    ]);
}

/** Token anti-CSRF sederhana untuk form-form voting. */
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
