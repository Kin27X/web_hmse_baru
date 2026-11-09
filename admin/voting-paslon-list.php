<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$candidates = votingCandidates($pdo);

$pageTitle = 'Pasangan Calon';
$currentNav = 'voting';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="voting.php">&larr; Voting Mubes</a></div>
<div class="admin-title-row">
    <h2>Pasangan Calon</h2>
    <a class="btn" href="voting-paslon-form.php"><i class="material-icons" style="font-size:16px;">add</i> Paslon Baru</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash <?= isset($_GET['err']) ? 'flash-error' : 'flash-success' ?>"><?= e($_GET['msg']) ?></div>
<?php endif; ?>

<?php if (count($candidates) === 1): ?>
    <div class="flash" style="background:#eef0f9;">Paslon cuma 1, jadi pilihan <strong>Kotak Kosong</strong> otomatis muncul di surat suara.</div>
<?php endif; ?>

<?php if (!$candidates): ?>
    <div class="admin-empty">Belum ada pasangan calon.</div>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>No</th><th>Foto</th><th>Ketua</th><th>Wakil</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($candidates as $c): ?>
            <tr>
                <td><strong style="font-size:18px; color:#2d1fd6;"><?= sprintf('%02d', $c['number']) ?></strong></td>
                <td>
                    <?php if ($c['photo']): ?>
                        <img src="../images/<?= e($c['photo']) ?>" alt="" style="width:64px; height:48px; object-fit:cover; border-radius:8px;">
                    <?php else: ?><span class="badge badge-muted">-</span><?php endif; ?>
                </td>
                <td><?= e($c['ketua_name']) ?><br><small style="color:rgba(23,21,51,0.5);"><?= e($c['ketua_npm']) ?></small></td>
                <td>
                    <?php if (votingHasWakil($c)): ?>
                        <?= e($c['wakil_name']) ?><br><small style="color:rgba(23,21,51,0.5);"><?= e($c['wakil_npm']) ?></small>
                    <?php else: ?>
                        <span class="badge badge-muted">Tanpa wakil</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a class="btn btn-secondary btn-small" href="voting-paslon-form.php?id=<?= (int)$c['id'] ?>">Edit</a>
                    <form class="confirm-delete-form" method="POST" action="voting-paslon-delete.php" onsubmit="return confirm(<?= e(json_encode('Hapus paslon ' . sprintf('%02d', $c['number']) . '?')) ?>);">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</main>
</body>
</html>
