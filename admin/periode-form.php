<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$periode = ['start_year' => '', 'end_year' => ''];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM periode WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $periode = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrfCheck()) {
    $error = 'Sesi kedaluwarsa, silakan ulangi.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $startYear = trim($_POST['start_year'] ?? '');
    $endYear = trim($_POST['end_year'] ?? '');

    if ($startYear === '' || $endYear === '' || !ctype_digit($startYear) || !ctype_digit($endYear)) {
        $error = 'Tahun mulai dan tahun akhir wajib diisi angka.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE periode SET start_year=?, end_year=? WHERE id=?');
            $stmt->execute([$startYear, $endYear, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO periode (start_year, end_year) VALUES (?,?)');
            $stmt->execute([$startYear, $endYear]);
        }
        header('Location: periode-list.php?msg=' . urlencode('Periode berhasil disimpan.'));
        exit;
    }
}

$pageTitle = $id ? 'Edit Periode' : 'Periode Baru';
$currentNav = 'proker';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="proker-list.php">&larr; Kembali ke Program Kerja</a></div>
<div class="admin-title-row"><h2><?= $id ? 'Edit Periode' : 'Periode Baru' ?></h2></div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST">
        <?= csrfField() ?>
        <div class="field-row">
            <div>
                <label for="start_year">Tahun Mulai</label>
                <input type="number" id="start_year" name="start_year" value="<?= e((string)$periode['start_year']) ?>" required>
            </div>
            <div>
                <label for="end_year">Tahun Akhir</label>
                <input type="number" id="end_year" name="end_year" value="<?= e((string)$periode['end_year']) ?>" required>
            </div>
        </div>
        <p class="hint">Kedua angka ini yang muncul di bulatan awal &amp; akhir garis waktu Program Kerja.</p>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="proker-list.php">Batal</a>
        </div>
    </form>
</div>