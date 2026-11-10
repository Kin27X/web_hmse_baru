<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$periodeId = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : null;

if (!$periodeId) {
    header('Location: proker-list.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM periode WHERE id = ?');
$stmt->execute([$periodeId]);
$periode = $stmt->fetch();

if (!$periode) {
    header('Location: periode-list.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM program_kerja WHERE periode_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$periodeId]);
$programs = $stmt->fetchAll();

$pageTitle = 'Program Kerja ' . $periode['start_year'] . '/' . $periode['end_year'];
$currentNav = 'proker';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="periode-list.php">&larr; Semua Periode</a></div>
<div class="admin-title-row">
    <h2>Program Kerja &mdash; Periode <?= e($periode['start_year']) ?>/<?= e($periode['end_year']) ?></h2>
    <a class="btn" href="proker-form.php?periode_id=<?= $periodeId ?>"><i class="material-icons" style="font-size:16px;">add</i> Program Baru</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>

<?php if (empty($programs)): ?>
    <div class="admin-empty">Belum ada program kerja di periode ini.</div>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Judul</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($programs as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td style="max-width:320px; color:rgba(23,21,51,0.6);"><?php
                        $desc = $p['description'] ?? '';
                        echo e(strlen($desc) > 90 ? substr($desc, 0, 90) . '...' : $desc);
                    ?></td>
                    <td class="actions">
                        <a class="btn btn-secondary btn-small" href="proker-form.php?id=<?= (int)$p['id'] ?>">Edit</a>
                        <form class="confirm-delete-form" method="POST" action="proker-delete.php" onsubmit="return confirm(<?= e(json_encode("Hapus program '" . $p['title'] . "'?")) ?>);">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="periode_id" value="<?= $periodeId ?>">
                            <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>