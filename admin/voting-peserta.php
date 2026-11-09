<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$state = votingState($pdo);
$linkAvailable = (int)$state['settings']['link_wiped'] === 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $msg = '';

    if (!csrfCheck()) {
        $error = 'Sesi kedaluwarsa, silakan ulangi.';
    } elseif ($action === 'add_bulk') {
        // Tiap baris: NPM lalu nama, dipisah tab (paste dari Excel), koma, titik koma, atau spasi.
        $added = 0; $skipped = 0; $invalid = [];
        $stmt = $pdo->prepare('INSERT IGNORE INTO vote_voters (npm, name) VALUES (?, ?)');
        foreach (preg_split('/\r\n|\r|\n/', $_POST['bulk'] ?? '') as $line) {
            if (trim($line) === '' || preg_match('/^\s*npm\b/i', $line)) continue; // baris kosong / header Excel
            if (!preg_match('/^\s*([0-9][0-9A-Za-z.\-]*)\s*[\t,;]?\s*(.+?)\s*$/u', $line, $m)) {
                $invalid[] = trim($line);
                continue;
            }
            $stmt->execute([normalizeNpm($m[1]), $m[2]]);
            $stmt->rowCount() ? $added++ : $skipped++;
        }
        $msg = "$added peserta ditambahkan.";
        if ($skipped) $msg .= " $skipped dilewati (NPM sudah ada).";
        if ($invalid) $msg .= ' Baris tidak dikenali: ' . implode(' | ', array_slice($invalid, 0, 5)) . (count($invalid) > 5 ? ' ...' : '');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM vote_voters WHERE id = ? AND has_voted = 0');
        $stmt->execute([(int)$_POST['id']]);
        $msg = $stmt->rowCount() ? 'Peserta dihapus.' : 'Peserta yang sudah memilih tidak bisa dihapus. Batalkan suaranya dulu.';
    } elseif ($action === 'cancel_vote') {
        $voterId = (int)$_POST['id'];
        if (!$linkAvailable) {
            $error = 'Voting sudah ditutup, sambungan NPM-suara sudah dihapus. Suara tidak bisa dibatalkan lagi.';
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT npm FROM vote_voters WHERE id = ? AND has_voted = 1');
            $stmt->execute([$voterId]);
            $npm = $stmt->fetchColumn();
            $del = $pdo->prepare('DELETE FROM vote_ballots WHERE voter_id = ?');
            $del->execute([$voterId]);
            if ($npm !== false && $del->rowCount() === 1) {
                $pdo->prepare('UPDATE vote_voters SET has_voted = 0, voted_at = NULL WHERE id = ?')->execute([$voterId]);
                $pdo->commit();
                votingLog($pdo, $voterId, $npm, 'dibatalkan_admin');
                $msg = "Suara NPM $npm dibatalkan. Peserta ini bisa memilih lagi.";
            } else {
                $pdo->rollBack();
                $error = 'Suara untuk peserta ini tidak ditemukan.';
            }
        }
    }

    if (!$error) {
        header('Location: voting-peserta.php?msg=' . urlencode($msg));
        exit;
    }
}

$voters = $pdo->query('SELECT * FROM vote_voters ORDER BY name ASC')->fetchAll();
$votedCount = count(array_filter($voters, fn($v) => (int)$v['has_voted'] === 1));

$pageTitle = 'Peserta Voting';
$currentNav = 'voting';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="voting.php">&larr; Voting Mubes</a></div>
<div class="admin-title-row">
    <h2>Anggota HMSE <span class="badge"><?= $votedCount ?>/<?= count($voters) ?> sudah memilih</span></h2>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] !== ''): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_bulk">
        <label for="bulk">Tambah peserta</label>
        <textarea id="bulk" name="bulk" rows="5" placeholder="2411010001	Budi Santoso&#10;2411010002, Siti Aminah" required></textarea>
        <div class="hint">Satu peserta per baris: NPM lalu nama. Bisa langsung copy 2 kolom (NPM, Nama) dari Excel lalu paste di sini. NPM yang sudah ada dilewati.</div>
        <div class="form-actions"><button type="submit" class="btn">Tambahkan</button></div>
    </form>
</div>

<?php if (!$voters): ?>
    <div class="admin-empty">Belum ada peserta.</div>
<?php else: ?>
    <input type="search" id="voterFilter" class="vote-filter" placeholder="Cari NPM atau nama...">
    <div style="overflow-x:auto;">
    <table class="admin-table" id="voterTable">
        <thead>
            <tr><th>NPM</th><th>Nama</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($voters as $v): $voted = (int)$v['has_voted'] === 1; ?>
            <tr>
                <td><?= e($v['npm']) ?></td>
                <td><?= e($v['name']) ?></td>
                <td>
                    <?php if ($voted): ?>
                        <span class="badge">Sudah &middot; <?= e(date('H:i', strtotime($v['voted_at']))) ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted">Belum</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if ($voted): ?>
                        <?php if ($linkAvailable): ?>
                            <form class="confirm-delete-form" method="POST" onsubmit="return confirm(<?= e(json_encode('Batalkan suara NPM ' . $v['npm'] . '? Suaranya dihapus (isi pilihannya tidak ditampilkan) dan peserta bisa memilih lagi.')) ?>);">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="cancel_vote">
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Batalkan suara</button>
                            </form>
                        <?php else: ?>
                            <span class="vote-muted" style="font-size:12px;">Terkunci (voting ditutup)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <form class="confirm-delete-form" method="POST" onsubmit="return confirm(<?= e(json_encode('Hapus peserta ' . $v['name'] . '?')) ?>);">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-small">Hapus</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <script>
    document.getElementById('voterFilter').addEventListener('input', function(){
      const q = this.value.toLowerCase().trim();
      document.querySelectorAll('#voterTable tbody tr').forEach(tr => {
        tr.hidden = q !== '' && !tr.textContent.toLowerCase().includes(q);
      });
    });
    </script>
<?php endif; ?>

</main>
</body>
</html>
