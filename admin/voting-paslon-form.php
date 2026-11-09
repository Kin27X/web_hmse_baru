<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$paslon = [
    'number' => '', 'photo' => null,
    'ketua_name' => '', 'ketua_npm' => '',
    'wakil_name' => '', 'wakil_npm' => '',
    'visi' => '', 'misi' => '',
];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM vote_candidates WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: voting-paslon-list.php');
        exit;
    }
    $paslon = $found;
} else {
    $paslon['number'] = (int)$pdo->query('SELECT COALESCE(MAX(number), 0) + 1 FROM vote_candidates')->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['ketua_name', 'ketua_npm', 'wakil_name', 'wakil_npm', 'visi', 'misi'];
    foreach ($fields as $f) $paslon[$f] = trim($_POST[$f] ?? '');
    $paslon['number'] = (int)($_POST['number'] ?? 0);

    $dupe = $pdo->prepare('SELECT COUNT(*) FROM vote_candidates WHERE number = ? AND id <> ?');
    $dupe->execute([$paslon['number'], $id ?? 0]);

    if (!csrfCheck()) {
        $error = 'Sesi kedaluwarsa, silakan ulangi.';
    } elseif ($paslon['number'] < 1) {
        $error = 'Nomor urut wajib diisi.';
    } elseif ($dupe->fetchColumn() > 0) {
        $error = 'Nomor urut ' . $paslon['number'] . ' sudah dipakai paslon lain.';
    } elseif ($paslon['ketua_name'] === '') {
        $error = 'Nama ketua wajib diisi.';
    } elseif ($paslon['wakil_name'] === '' && $paslon['wakil_npm'] !== '') {
        $error = 'NPM wakil diisi tapi nama wakil kosong.';
    } else {
        $oldPhoto = $paslon['photo'];
        $paslon['photo'] = handleImageUpload('photo', $oldPhoto);
        $values = [
            $paslon['number'], $paslon['photo'],
            $paslon['ketua_name'], $paslon['ketua_npm'] ?: null,
            $paslon['wakil_name'] ?: null, $paslon['wakil_npm'] ?: null,
            $paslon['visi'], $paslon['misi'],
        ];
        if ($id) {
            $values[] = $id;
            $pdo->prepare('UPDATE vote_candidates SET number=?, photo=?, ketua_name=?, ketua_npm=?, wakil_name=?, wakil_npm=?, visi=?, misi=? WHERE id=?')
                ->execute($values);
        } else {
            $pdo->prepare('INSERT INTO vote_candidates (number, photo, ketua_name, ketua_npm, wakil_name, wakil_npm, visi, misi) VALUES (?,?,?,?,?,?,?,?)')
                ->execute($values);
        }
        // Foto diganti: buang file lama supaya tidak menumpuk di images/
        if ($oldPhoto && $oldPhoto !== $paslon['photo']) {
            deleteUploadedImage($oldPhoto, $pdo);
        }
        header('Location: voting-paslon-list.php?msg=' . urlencode('Paslon berhasil disimpan.'));
        exit;
    }
}

$pageTitle = $id ? 'Edit Paslon' : 'Paslon Baru';
$currentNav = 'voting';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="voting-paslon-list.php">&larr; Kembali</a></div>
<div class="admin-title-row"><h2><?= $id ? 'Edit Paslon' : 'Paslon Baru' ?></h2></div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>

        <label for="number">Nomor urut</label>
        <input type="number" id="number" name="number" min="1" value="<?= e((string)$paslon['number']) ?>" required style="max-width:120px;">

        <label for="photo">Foto pasangan</label>
        <input type="file" id="photo" name="photo" accept="image/*">
        <div class="hint">Satu foto berdua, rasio 4:3 (landscape) paling pas.</div>
        <?php if ($paslon['photo']): ?>
            <div class="current-photo" style="width:160px; height:120px;"><img src="../images/<?= e($paslon['photo']) ?>" alt=""></div>
        <?php endif; ?>

        <div class="field-row">
            <div>
                <label for="ketua_name">Nama ketua</label>
                <input type="text" id="ketua_name" name="ketua_name" value="<?= e($paslon['ketua_name']) ?>" required>
            </div>
            <div>
                <label for="ketua_npm">NPM ketua</label>
                <input type="text" id="ketua_npm" name="ketua_npm" value="<?= e($paslon['ketua_npm']) ?>">
            </div>
            <div>
                <label for="wakil_name">Nama wakil <span style="font-weight:400;">(opsional)</span></label>
                <input type="text" id="wakil_name" name="wakil_name" value="<?= e($paslon['wakil_name']) ?>">
            </div>
            <div>
                <label for="wakil_npm">NPM wakil <span style="font-weight:400;">(opsional)</span></label>
                <input type="text" id="wakil_npm" name="wakil_npm" value="<?= e($paslon['wakil_npm']) ?>">
            </div>
        </div>

        <label for="visi">Visi</label>
        <textarea id="visi" name="visi" rows="3"><?= e($paslon['visi']) ?></textarea>

        <label for="misi">Misi</label>
        <textarea id="misi" name="misi" rows="6"><?= e($paslon['misi']) ?></textarea>
        <div class="hint">Satu poin per baris.</div>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="voting-paslon-list.php">Batal</a>
        </div>
    </form>
</div>

</main>
</body>
</html>
