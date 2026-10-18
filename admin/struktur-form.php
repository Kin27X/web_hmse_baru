<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error = '';

$node = [
    'name' => '', 'label' => '', 'npm' => '', 'semester' => '', 'nid' => '', 'masa_jabatan' => '',
    'alamat' => '', 'instagram' => '', 'whatsapp' => '', 'email' => '', 'photo' => null, 'icon' => '',
    'parent_id' => null, 'node_key' => null,
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM struktur_nodes WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { header('Location: struktur-list.php'); exit; }
    $node = $found;
    $mode = $found['group_type'] ?: 'member'; // leader | branch | division | member
} else {
    $mode = $_GET['type'] ?? 'member';
    if ($mode === 'member') {
        $node['parent_id'] = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;
        if (!$node['parent_id']) { header('Location: struktur-list.php'); exit; }
    }
}

// Kaprodi itu dosen, bukan mahasiswa -- makanya field-nya beda (NID +
// Masa Jabatan), bukan NPM + Semester kayak leader/anggota lainnya.
$isKaprodi = ($node['node_key'] ?? '') === 'kaprodi';

// Dropdown pilihan kelompok induk (cuma relevan utk mode "member")
$parentOptions = $pdo->query("SELECT * FROM struktur_nodes WHERE parent_id IS NULL AND group_type IN ('branch','division') ORDER BY group_type, sort_order")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $npm = trim($_POST['npm'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $masaJabatan = trim($_POST['masa_jabatan'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $icon = trim($_POST['icon'] ?? '');
    $parentId = $mode === 'member' ? (int)($_POST['parent_id'] ?? 0) : null;

    if ($name === '') {
        $error = 'Nama wajib diisi.';
    } elseif ($mode === 'member' && !$parentId) {
        $error = 'Kelompok induk wajib dipilih.';
    } else {

        $photo = handleImageUpload('photo', $node['photo'] ?? null);

        if ($id) {
            $stmt = $pdo->prepare('UPDATE struktur_nodes SET name=?, label=?, npm=?, semester=?, nid=?, masa_jabatan=?, alamat=?, instagram=?, whatsapp=?, email=?, photo=?, icon=?, parent_id=? WHERE id=?');
            $stmt->execute([$name, $label, $npm, $semester, $nid, $masaJabatan, $alamat, $instagram, $whatsapp, $email, $photo, $icon ?: null, $mode === 'member' ? $parentId : null, $id]);
        } else {
            $groupType = in_array($mode, ['branch', 'division'], true) ? $mode : null;
            $stmt = $pdo->prepare('INSERT INTO struktur_nodes (parent_id, group_type, label, name, npm, semester, nid, masa_jabatan, alamat, instagram, whatsapp, email, photo, icon) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $mode === 'member' ? $parentId : null,
                $groupType,
                $label, $name, $npm, $semester, $nid, $masaJabatan, $alamat, $instagram, $whatsapp, $email, $photo, $icon ?: null
            ]);
        }

        header('Location: struktur-list.php?msg=' . urlencode('Data berhasil disimpan.'));
        exit;
    }
}

$titleMap = ['leader' => 'Pimpinan', 'branch' => 'Cabang', 'division' => 'Divisi', 'member' => 'Anggota'];
$pageTitle = ($id ? 'Edit ' : 'Tambah ') . ($titleMap[$mode] ?? '');
$currentNav = 'struktur';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="struktur-list.php">&larr; Kembali</a></div>
<div class="admin-title-row"><h2><?= e($pageTitle) ?></h2></div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST" enctype="multipart/form-data">

        <?php if ($mode === 'member'): ?>
            <label for="parent_id">Kelompok (Cabang/Divisi)</label>
            <select id="parent_id" name="parent_id" required>
                <?php foreach ($parentOptions as $opt): ?>
                    <option value="<?= (int)$opt['id'] ?>" <?= (int)$node['parent_id'] === (int)$opt['id'] ? 'selected' : '' ?>>
                        <?= e($opt['name']) ?> (<?= $opt['group_type'] === 'branch' ? 'Cabang' : 'Divisi' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label for="name">Nama</label>
        <input type="text" id="name" name="name" value="<?= e($node['name']) ?>" required>

        <label for="label">Jabatan</label>
        <input type="text" id="label" name="label" value="<?= e($node['label']) ?>" placeholder="mis. Ketua Umum / Ketua Kominfo / Kominfo 1">

        <?php if ($mode === 'division' || $mode === 'branch'): ?>
            <label for="icon">Ikon (nama Material Icons)</label>
            <input type="text" id="icon" name="icon" value="<?= e($node['icon']) ?>" placeholder="mis. campaign">
            <p class="hint">Lihat daftar nama ikon di <a href="https://fonts.google.com/icons" target="_blank" rel="noopener">fonts.google.com/icons</a>. Dipakai kalau kategori ini tidak punya foto.</p>
        <?php endif; ?>

        <?php if ($mode !== 'branch' && $mode !== 'division'): ?>
            <?php if ($isKaprodi): ?>
                <div class="field-row">
                    <div>
                        <label for="nid">NID</label>
                        <input type="text" id="nid" name="nid" value="<?= e($node['nid']) ?>" placeholder="Nomor Induk Dosen">
                    </div>
                    <div>
                        <label for="masa_jabatan">Masa Jabatan</label>
                        <input type="text" id="masa_jabatan" name="masa_jabatan" value="<?= e($node['masa_jabatan']) ?>" placeholder="mis. 2024 - 2028">
                    </div>
                </div>
            <?php else: ?>
                <div class="field-row">
                    <div>
                        <label for="npm">NPM</label>
                        <input type="text" id="npm" name="npm" value="<?= e($node['npm']) ?>">
                    </div>
                    <div>
                        <label for="semester">Semester</label>
                        <input type="text" id="semester" name="semester" value="<?= e($node['semester']) ?>">
                    </div>
                </div>
            <?php endif; ?>

            <label for="alamat">Alamat</label>
            <input type="text" id="alamat" name="alamat" value="<?= e($node['alamat']) ?>">

            <div class="field-row">
                <div>
                    <label for="whatsapp">WhatsApp</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="<?= e($node['whatsapp']) ?>" placeholder="mis. 081234567890">
                </div>
                <div>
                    <label for="instagram">Instagram</label>
                    <input type="text" id="instagram" name="instagram" value="<?= e($node['instagram']) ?>" placeholder="mis. username (tanpa @)">
                </div>
            </div>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($node['email']) ?>">
            <p class="hint">WhatsApp/Instagram/Email cukup diisi polos -- link-nya dibuat otomatis di kartu detail.</p>
        <?php endif; ?>

        <label for="photo">Foto</label>
        <input type="file" id="photo" name="photo" accept="image/*">
        <?php if (!empty($node['photo'])): ?>
            <div class="current-photo"><img src="../images/<?= e($node['photo']) ?>" alt=""></div>
            <p class="hint">Foto saat ini. Biarkan kosong kalau tidak mau ganti.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="struktur-list.php">Batal</a>
        </div>
    </form>
</div>