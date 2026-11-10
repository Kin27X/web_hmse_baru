<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$periodes = $pdo->query('SELECT p.*, (SELECT COUNT(*) FROM program_kerja pk WHERE pk.periode_id = p.id) AS jumlah_program
                          FROM periode p ORDER BY start_year DESC')->fetchAll();

$pageTitle = 'Program Kerja';
$currentNav = 'proker';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-title-row">
    <h2>Periode Program Kerja</h2>
    <a class="btn" href="periode-form.php"><i class="material-icons" style="font-size:16px;">add</i> Periode Baru</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>

<?php if (empty($periodes)): ?>
    <div class="admin-empty">Belum ada periode. Tambah periode baru dulu di atas.</div>
<?php else: ?>

    <a href="dashboard.php" class="btn btn-back">Kembali</a>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Periode</th>
                <th>Jumlah Program</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($periodes as $p): ?>
                <tr>
                    <td><?= e($p['start_year']) ?>/<?= e($p['end_year']) ?></td>
                    <td><?= (int)$p['jumlah_program'] ?> program</td>
                    <td class="actions">
                        <a class="btn btn-secondary btn-small" href="proker-list.php?periode_id=<?= (int)$p['id'] ?>">Kelola Isi</a>
                        <a class="btn btn-secondary btn-small" href="periode-form.php?id=<?= (int)$p['id'] ?>">Edit</a>
                        <form class="confirm-delete-form" method="POST" action="periode-delete.php" onsubmit="return confirm('Hapus periode <?= e($p['start_year']) ?>/<?= e($p['end_year']) ?>? Semua program kerja di dalamnya ikut terhapus.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
<?php endif; ?>