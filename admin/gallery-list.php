<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$photos = $pdo->query('SELECT * FROM gallery_photos ORDER BY sort_order ASC, id DESC')->fetchAll();

$categoryLabel = ['olahraga' => 'Olahraga', 'belajar' => 'Belajar', 'event' => 'Event'];

$pageTitle = 'Gallery';
$currentNav = 'gallery';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-title-row">
    <h2>Gallery</h2>
    <a class="btn" href="gallery-form.php"><i class="material-icons" style="font-size:16px;">add</i> Foto Baru</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>

<?php if (empty($photos)): ?>
    <div class="admin-empty">Belum ada foto di gallery.</div>
<?php else: ?>

    <a href="dashboard.php" class="btn btn-back">Kembali</a>
    
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:16px;">
        <?php foreach ($photos as $p): ?>
            <div class="admin-card" style="padding:10px;">
                <div style="aspect-ratio:4/3; border-radius:10px; overflow:hidden; margin-bottom:8px; background:#f0f0f0;">
                    <img src="../images/<?= e($p['photo']) ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <span class="badge"><?= e($categoryLabel[$p['category']] ?? $p['category']) ?></span>
                <div class="actions" style="margin-top:10px;">
                    <a class="btn btn-secondary btn-small" href="gallery-form.php?id=<?= (int)$p['id'] ?>">Edit</a>
                    <form class="confirm-delete-form" method="POST" action="gallery-delete.php" onsubmit="return confirm('Hapus foto ini?');">
                            <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>