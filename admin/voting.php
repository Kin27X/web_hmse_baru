<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $msg = '';

    if (!csrfCheck()) {
        $error = 'Sesi kedaluwarsa, silakan ulangi.';
    } elseif ($action === 'save_settings') {
        $title = trim($_POST['title'] ?? '');
        $start = trim($_POST['start_at'] ?? '');
        $end = trim($_POST['end_at'] ?? '');
        $wa = trim($_POST['panitia_wa'] ?? '');

        if ($title === '' || $start === '' || $end === '') {
            $error = 'Judul, waktu mulai, dan waktu selesai wajib diisi.';
        } elseif (strtotime($end) <= strtotime($start)) {
            $error = 'Waktu selesai harus setelah waktu mulai.';
        } else {
            $pdo->prepare('UPDATE vote_settings SET title = ?, start_at = ?, end_at = ?, panitia_wa = ? WHERE id = 1')
                ->execute([$title, date('Y-m-d H:i:s', strtotime($start)), date('Y-m-d H:i:s', strtotime($end)), $wa ?: null]);
            $msg = 'Pengaturan voting disimpan.';
        }
    } elseif ($action === 'close_now') {
        $pdo->exec('UPDATE vote_settings SET closed_manually = 1 WHERE id = 1');
        $msg = 'Voting ditutup.';
    } elseif ($action === 'reopen') {
        // Sambungan NPM<->suara untuk suara BARU akan disimpan lagi sampai voting ditutup lagi.
        $pdo->exec('UPDATE vote_settings SET closed_manually = 0, link_wiped = 0, results_public = 0 WHERE id = 1');
        $msg = 'Penutupan manual dibatalkan. Pastikan jadwal selesai belum lewat.';
    } elseif ($action === 'publish') {
        if (votingState($pdo)['status'] === 'closed') {
            $pdo->exec('UPDATE vote_settings SET results_public = 1 WHERE id = 1');
            $msg = 'Hasil diumumkan di halaman voting publik.';
        } else {
            $error = 'Hasil cuma bisa diumumkan setelah voting ditutup.';
        }
    } elseif ($action === 'unpublish') {
        $pdo->exec('UPDATE vote_settings SET results_public = 0 WHERE id = 1');
        $msg = 'Hasil disembunyikan lagi dari publik.';
    } elseif ($action === 'reset') {
        if (($_POST['confirm_text'] ?? '') !== 'RESET') {
            $error = 'Ketik RESET (huruf besar) untuk mengonfirmasi.';
        } else {
            votingEnsureSchema($pdo);
            rateLimitTable($pdo);
            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM vote_ballots');
            $pdo->exec('DELETE FROM vote_log');
            $pdo->exec('DELETE FROM vote_devices');
            $pdo->exec("DELETE FROM rate_limits WHERE k LIKE 'vote_%'");
            $pdo->exec('UPDATE vote_voters SET has_voted = 0, voted_at = NULL, device_ok = 0');
            $pdo->exec('UPDATE vote_settings SET closed_manually = 0, link_wiped = 0, results_public = 0 WHERE id = 1');
            $pdo->commit();
            $msg = 'Semua suara & log dihapus. Peserta dan paslon tetap ada.';
        }
    }

    if (!$error) {
        header('Location: voting.php?msg=' . urlencode($msg));
        exit;
    }
}

$state = votingState($pdo);
$settings = $state['settings'];
$status = $state['status'];
$candidates = votingCandidates($pdo);
$options = votingOptions($candidates);
$participation = votingParticipation($pdo);
$tally = $status === 'closed' ? votingTally($pdo, $options) : null;
$logs = $pdo->query('SELECT * FROM vote_log ORDER BY id DESC LIMIT 40')->fetchAll();

$toInput = fn(?string $dt) => $dt ? date('Y-m-d\TH:i', strtotime($dt)) : '';
$eventLabels = [
    'memilih'                => ['Memilih', ''],
    'ditolak_sudah_memilih'  => ['Ditolak: sudah memilih', 'badge-warn'],
    'npm_tidak_terdaftar'    => ['NPM tidak terdaftar', 'badge-muted'],
    'dibatalkan_admin'       => ['Suara dibatalkan admin', 'badge-warn'],
    'ditolak_perangkat'      => ['Ditolak: perangkat sudah dipakai', 'badge-warn'],
    'dibatasi'               => ['Dibatasi: terlalu banyak percobaan', 'badge-warn'],
    'diizinkan_perangkat'    => ['Diizinkan pinjam HP', 'badge-muted'],
    'izin_perangkat_dicabut' => ['Izin pinjam HP dicabut', 'badge-muted'],
];

$pageTitle = 'Voting Mubes';
$currentNav = 'voting';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-title-row">
    <h2>Voting Mubes</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="voting-paslon-list.php"><i class="material-icons" style="font-size:16px;">groups</i> Paslon (<?= count($candidates) ?>)</a>
        <a class="btn btn-secondary" href="voting-peserta.php"><i class="material-icons" style="font-size:16px;">badge</i> Peserta (<?= $participation['total'] ?>)</a>
        <a class="btn" href="voting-qr.php" target="_blank"><i class="material-icons" style="font-size:16px;">qr_code_2</i> Layar QR</a>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] !== ''): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<!-- ===== Status + partisipasi ===== -->
<div class="vote-stats">
    <div class="admin-card">
        <div class="vote-stat-label">Status</div>
        <div class="vote-status vote-status-<?= e($status) ?>" id="liveStatus"><?= e(votingStatusLabel($status)) ?></div>
        <div class="vote-stat-sub">
            <?= e(formatWaktu($settings['start_at'])) ?> - <?= e(formatWaktu($settings['end_at'])) ?>
        </div>
        <div style="margin-top:14px; display:flex; gap:8px; flex-wrap:wrap;">
            <?php if ($status !== 'closed'): ?>
                <form method="POST" onsubmit="return confirm('Tutup voting sekarang? Setelah ditutup, sambungan NPM-suara dihapus permanen dan suara tidak bisa dibatalkan lagi.');">
                    <?= csrfField() ?><input type="hidden" name="action" value="close_now">
                    <button class="btn btn-danger btn-small" type="submit"><i class="material-icons" style="font-size:15px;">lock</i> Tutup sekarang</button>
                </form>
            <?php elseif ((int)$settings['closed_manually'] === 1): ?>
                <form method="POST" onsubmit="return confirm('Buka lagi voting? Hasil yang sudah diumumkan akan disembunyikan.');">
                    <?= csrfField() ?><input type="hidden" name="action" value="reopen">
                    <button class="btn btn-secondary btn-small" type="submit">Buka lagi</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card">
        <div class="vote-stat-label">Sudah memilih</div>
        <div class="vote-big"><span id="liveVoted"><?= $participation['voted'] ?></span><small> / <span id="liveTotal"><?= $participation['total'] ?></span></small></div>
        <div class="vote-progress"><span id="liveBar" style="width:<?= $participation['total'] ? round($participation['voted'] / $participation['total'] * 100) : 0 ?>%"></span></div>
        <div class="vote-stat-sub">Diperbarui otomatis tiap 5 detik.</div>
    </div>
</div>

<!-- ===== Hasil ===== -->
<div class="admin-card">
    <h3 class="vote-card-title">Perolehan suara</h3>
    <?php if ($status !== 'closed'): ?>
        <p class="vote-muted"><i class="material-icons" style="font-size:16px; vertical-align:-3px;">visibility_off</i>
            Disembunyikan sampai voting ditutup.</p>
    <?php else: ?>
        <!-- Default tertutup, biar angka tidak kelihatan tidak sengaja (mis. layar admin ikut tampil di proyektor) -->
        <details class="vote-reveal">
            <summary>
                <span class="when-closed"><i class="material-icons">visibility</i> Lihat jumlah suara</span>
                <span class="when-open"><i class="material-icons">visibility_off</i> Tutup jumlah suara</span>
            </summary>
            <table class="admin-table" style="box-shadow:none; margin:12px 0 4px;">
                <thead><tr><th>Pilihan</th><th>Suara</th></tr></thead>
                <tbody>
                <?php foreach ($options as $o): ?>
                    <tr><td><strong><?= e($o['label']) ?></strong> &middot; <?= e($o['names']) ?></td><td><?= $tally[$o['key']] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </details>
        <div style="height:16px;"></div>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <a class="btn" href="voting-hitung.php" target="_blank"><i class="material-icons" style="font-size:16px;">slideshow</i> Mulai penghitungan (proyektor)</a>
            <?php if ((int)$settings['results_public'] === 1): ?>
                <span class="badge">Sudah diumumkan</span>
                <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="unpublish">
                    <button class="btn btn-secondary btn-small" type="submit">Sembunyikan</button></form>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('Umumkan hasil ke halaman voting publik?');"><?= csrfField() ?><input type="hidden" name="action" value="publish">
                    <button class="btn btn-secondary btn-small" type="submit">Umumkan ke publik</button></form>
            <?php endif; ?>
        </div>
        <p class="vote-muted" style="margin-top:12px;">Tips: tampilkan penghitungan di proyektor dulu. Tombol "Umumkan" juga ada di akhir animasi.</p>
    <?php endif; ?>
</div>

<!-- ===== Pengaturan ===== -->
<div class="admin-card">
    <h3 class="vote-card-title">Pengaturan</h3>
    <form class="admin-form" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_settings">

        <label for="title">Judul pemilihan</label>
        <input type="text" id="title" name="title" value="<?= e($settings['title']) ?>" required>

        <div class="field-row">
            <div>
                <label for="start_at">Mulai</label>
                <input type="datetime-local" id="start_at" name="start_at" value="<?= e($toInput($settings['start_at'])) ?>" required>
            </div>
            <div>
                <label for="end_at">Selesai</label>
                <input type="datetime-local" id="end_at" name="end_at" value="<?= e($toInput($settings['end_at'])) ?>" required>
            </div>
        </div>
        <div class="hint">Waktu WIB. Voting otomatis buka &amp; tutup sesuai jadwal ini.</div>

        <label for="panitia_wa">No. WhatsApp panitia</label>
        <input type="text" id="panitia_wa" name="panitia_wa" value="<?= e($settings['panitia_wa']) ?>" placeholder="08xxxxxxxxxx">
        <div class="hint">Dipakai tombol "Hubungi Panitia" kalau ada peserta yang NPM-nya terpakai orang lain.</div>

        <div class="form-actions"><button type="submit" class="btn">Simpan</button></div>
    </form>
</div>

<!-- ===== Log ===== -->
<div class="admin-card">
    <h3 class="vote-card-title">Log aktivitas &amp; perangkat</h3>
    <p class="vote-muted">Untuk audit panitia. Log tidak menyimpan isi pilihan.</p>
    <?php if (!$logs): ?>
        <div class="admin-empty">Belum ada aktivitas.</div>
    <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="admin-table" style="box-shadow:none;">
            <thead><tr><th>Waktu</th><th>NPM</th><th>Kejadian</th><th>IP</th><th>ID HP</th><th>Perangkat</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l):
                [$label, $cls] = $eventLabels[$l['event']] ?? [$l['event'], 'badge-muted']; ?>
                <tr>
                    <td style="white-space:nowrap;"><?= e(date('H:i:s', strtotime($l['created_at']))) ?></td>
                    <td><?= e($l['npm']) ?></td>
                    <td><span class="badge <?= $cls ?>"><?= e($label) ?></span></td>
                    <td><?= e($l['ip']) ?></td>
                    <td><code><?= e(!empty($l['device_id']) ? substr($l['device_id'], 0, 6) : '-') ?></code></td>
                    <td class="vote-ua" title="<?= e($l['user_agent']) ?>"><?= e(votingShortUa($l['user_agent'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<!-- ===== Reset ===== -->
<div class="admin-card vote-danger">
    <h3 class="vote-card-title">Reset data suara</h3>
    <p class="vote-muted">Hapus semua suara dan log (misalnya setelah uji coba). Daftar peserta dan paslon tidak dihapus.</p>
    <form class="admin-form" method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;" onsubmit="return confirm('Yakin hapus SEMUA suara?');">
        <?= csrfField() ?><input type="hidden" name="action" value="reset">
        <input type="text" name="confirm_text" placeholder="Ketik RESET" style="max-width:180px;" autocomplete="off">
        <button class="btn btn-danger" type="submit">Reset suara</button>
    </form>
</div>

<script>
// Partisipasi live
(function(){
  const voted = document.getElementById('liveVoted');
  const total = document.getElementById('liveTotal');
  const bar = document.getElementById('liveBar');
  const statusEl = document.getElementById('liveStatus');
  const initialStatus = <?= json_encode($status) ?>;
  async function tick(){
    try{
      const res = await fetch('voting-api.php', {cache:'no-store'});
      if(!res.ok) return;
      const d = await res.json();
      voted.textContent = d.voted;
      total.textContent = d.total;
      bar.style.width = (d.total ? Math.round(d.voted / d.total * 100) : 0) + '%';
      // Status berubah (mis. jadwal habis) -> muat ulang biar tombol & hasil ikut berubah
      if(d.status !== initialStatus) location.reload();
    }catch(e){}
  }
  setInterval(tick, 5000);
})();
</script>

</main>
</body>
</html>
