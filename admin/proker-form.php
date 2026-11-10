<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$periodeId = isset($_GET['periode_id']) ? (int)$_GET['periode_id'] : null;
$program = ['title' => '', 'description' => ''];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM program_kerja WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $program = $found;
        $periodeId = (int)$found['periode_id'];
    }
}

if (!$periodeId) {
    header('Location: periode-list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrfCheck()) {
    $error = 'Sesi kedaluwarsa, silakan ulangi.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '') {
        $error = 'Judul wajib diisi.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE program_kerja SET title=?, description=? WHERE id=?');
            $stmt->execute([$title, $description, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO program_kerja (periode_id, title, description) VALUES (?,?,?)');
            $stmt->execute([$periodeId, $title, $description]);
        }
        header('Location: proker-list.php?periode_id=' . $periodeId . '&msg=' . urlencode('Program kerja berhasil disimpan.'));
        exit;
    }
}

$pageTitle = $id ? 'Edit Program Kerja' : 'Program Kerja Baru';
$currentNav = 'proker';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="proker-list.php?periode_id=<?= $periodeId ?>">&larr; Kembali</a></div>
<div class="admin-title-row"><h2><?= $id ? 'Edit Program Kerja' : 'Program Kerja Baru' ?></h2></div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST">
        <?= csrfField() ?>
        <label for="title">Judul</label>
        <input type="text" id="title" name="title" value="<?= e($program['title']) ?>" required>

        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="5"><?= e($program['description']) ?></textarea>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="proker-list.php?periode_id=<?= $periodeId ?>">Batal</a>
        </div>
    </form>
</div>