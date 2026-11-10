<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$event = ['title' => '', 'place' => '', 'event_time' => '', 'event_date' => '', 'photo' => null];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $event = $found;
}

// Batasi maksimal 5 event AKTIF (belum lewat tanggal) -- cuma dicek waktu
// menambah event BARU, bukan waktu mengedit yang sudah ada.
if (!$id) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE event_date >= ?');
    $stmt->execute([$today]);
    $activeCount = (int)$stmt->fetchColumn();
    if ($activeCount >= 5) {
        header('Location: events-list.php?err=' . urlencode('Sudah ada 5 event mendatang. Hapus salah satu dulu sebelum menambah yang baru.'));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrfCheck()) {
    $error = 'Sesi kedaluwarsa, silakan ulangi.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');

    if ($title === '' || $eventDate === '') {
        $error = 'Judul dan tanggal wajib diisi.';
    } else {

        $photo = handleImageUpload('photo', $event['photo'] ?? null);

        if ($id) {
            $stmt = $pdo->prepare('UPDATE events SET title=?, place=?, event_time=?, event_date=?, photo=? WHERE id=?');
            $stmt->execute([$title, $place, $eventTime, $eventDate, $photo, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO events (title, place, event_time, event_date, photo) VALUES (?,?,?,?,?)');
            $stmt->execute([$title, $place, $eventTime, $eventDate, $photo]);
        }
        header('Location: events-list.php?msg=' . urlencode('Event berhasil disimpan.'));
        exit;
    }
}

$pageTitle = $id ? 'Edit Event' : 'Event Baru';
$currentNav = 'events';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="events-list.php">&larr; Kembali</a></div>
<div class="admin-title-row"><h2><?= $id ? 'Edit Event' : 'Event Baru' ?></h2></div>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <label for="title">Judul Event</label>
        <input type="text" id="title" name="title" value="<?= e($event['title']) ?>" required>

        <div class="field-row">
            <div>
                <label for="event_date">Tanggal</label>
                <input type="date" id="event_date" name="event_date" value="<?= e($event['event_date']) ?>" required>
            </div>
            <div>
                <label for="event_time">Jam</label>
                <input type="text" id="event_time" name="event_time" value="<?= e($event['event_time']) ?>" placeholder="mis. 08.00 - selesai">
            </div>
        </div>

        <label for="place">Tempat</label>
        <input type="text" id="place" name="place" value="<?= e($event['place']) ?>">

        <label for="photo">Foto</label>
        <input type="file" id="photo" name="photo" accept="image/*">
        <?php if (!empty($event['photo'])): ?>
            <div class="current-photo"><img src="../images/<?= e($event['photo']) ?>" alt=""></div>
            <p class="hint">Foto saat ini. Biarkan kosong kalau tidak mau ganti.</p>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="events-list.php">Batal</a>
        </div>
    </form>
</div>