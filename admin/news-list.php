<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/news-helpers.php';

$pdo = getDbConnection();
$newsList = $pdo->query('SELECT n.*, (SELECT COUNT(*) FROM news_photos np WHERE np.news_id = n.id) AS jumlah_foto
                          FROM news n ORDER BY event_datetime DESC')->fetchAll();

$flash = newsFlashPull();

$pageTitle = 'News';
$currentNav = 'news';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-title-row">
    <h2>News</h2>
    <a class="btn" href="news-form.php"><i class="material-icons" style="font-size:16px;">add</i> Berita Baru</a>
</div>

<?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= newsE($flash['message']) ?></div>
<?php endif; ?>

<?php if (empty($newsList)): ?>
    <div class="admin-empty">Belum ada berita.</div>
<?php else: ?>

    <a href="dashboard.php" class="btn btn-back">Kembali</a>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Judul</th>
                <th>Tanggal</th>
                <th>Author</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($newsList as $n): ?>
                <tr>
                    <td><?= newsE($n['title']) ?></td>
                    <td><?= newsE(newsFormatDate($n['event_datetime'])['full']) ?></td>
                    <td><?= newsE($n['author'] ?: '-') ?></td>
                    <td><?= (int) $n['jumlah_foto'] ?> foto</td>
                    <td class="actions">
                        <a class="btn btn-secondary btn-small" href="news-form.php?id=<?= (int) $n['id'] ?>">Edit</a>
                        <form class="confirm-delete-form" method="POST" action="news-delete.php" data-title="<?= newsE($n['title']) ?>">
                            <?= newsCsrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
// Judul dibaca dari atribut data-title (bukan ditanam di dalam string JS inline),
// jadi tanda kutip / karakter aneh di judul tidak bisa merusak skrip.
document.querySelectorAll('.confirm-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var msg = 'Hapus berita "' + (form.dataset.title || '') + '"?\nSemua fotonya juga ikut terhapus.';
        if (!confirm(msg)) e.preventDefault();
    });
});
</script>