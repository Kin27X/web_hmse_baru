<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$photo = ['photo' => null, 'category' => 'event'];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $photo = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $category = trim($_POST['category'] ?? '');
    $validCategories = ['olahraga', 'belajar', 'event'];

    if (!in_array($category, $validCategories, true)) {
        $error = 'Kategori tidak valid.';
    } else {

        $filename = handleImageUpload('photo', $photo['photo'] ?? null);

        if (!$filename) {
            $error = 'Foto wajib diupload.';
        } else {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE gallery_photos SET photo=?, category=? WHERE id=?');
                $stmt->execute([$filename, $category, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO gallery_photos (photo, category) VALUES (?,?)');
                $stmt->execute([$filename, $category]);
            }
            header('Location: gallery-list.php?msg=' . urlencode('Foto berhasil disimpan.'));
            exit;
        }
    }
}

$pageTitle = $id ? 'Edit Foto' : 'Foto Baru';
$currentNav = 'gallery';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="gallery-list.php">&larr; Kembali</a></div>
<div class="admin-title-row"><h2><?= $id ? 'Edit Foto' : 'Foto Baru' ?></h2></div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST" enctype="multipart/form-data">

        <label for="category">Kategori</label>
        <select id="category" name="category" required>
            <option value="olahraga" <?= $photo['category'] === 'olahraga' ? 'selected' : '' ?>>Olahraga</option>
            <option value="belajar" <?= $photo['category'] === 'belajar' ? 'selected' : '' ?>>Belajar</option>
            <option value="event" <?= $photo['category'] === 'event' ? 'selected' : '' ?>>Event</option>
        </select>

        <label for="photo">Foto</label>
        <input type="file" id="photo" name="photo" accept="image/*" <?= $id ? '' : 'required' ?>>
        <?php if (!empty($photo['photo'])): ?>
            <div class="current-photo"><img src="../images/<?= e($photo['photo']) ?>" alt=""></div>
            <p class="hint">Foto saat ini. Biarkan kosong kalau tidak mau ganti.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="gallery-list.php">Batal</a>
        </div>
    </form>
</div>