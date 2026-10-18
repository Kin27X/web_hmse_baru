<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$events = $pdo->query('SELECT * FROM events ORDER BY event_date ASC')->fetchAll();

$today = date('Y-m-d');
$activeCount = count(array_filter($events, fn($e) => $e['event_date'] >= $today));

$pageTitle = 'Event Schedule';
$currentNav = 'events';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-title-row">
    <h2>Event Schedule</h2>
    <?php if ($activeCount < 5): ?>
        <a class="btn" href="events-form.php"><i class="material-icons" style="font-size:16px;">add</i> Event Baru</a>
    <?php else: ?>
        <span class="badge badge-muted">Sudah 5 event mendatang (maksimal)</span>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="flash flash-error"><?= e($_GET['err']) ?></div>
<?php endif; ?>

<p style="font-size:13px; color:rgba(23,21,51,0.55); margin-bottom:18px;">
    Maksimal 5 event yang tampil di halaman publik (yang tanggalnya belum lewat).
    Event yang sudah lewat tetap ada di daftar ini, tapi otomatis hilang dari halaman publik.
</p>

<?php if (empty($events)): ?>
    <div class="admin-empty">Belum ada event.</div>
<?php else: ?>

    <a href="dashboard.php" class="btn btn-back">Kembali</a>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Judul</th>
                <th>Tanggal</th>
                <th>Tempat</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $ev): $isPast = $ev['event_date'] < $today; ?>
                <tr>
                    <td><?= e($ev['title']) ?></td>
                    <td><?= e($ev['event_date']) ?></td>
                    <td><?= e($ev['place']) ?></td>
                    <td>
                        <?php if ($isPast): ?>
                            <span class="badge badge-muted">Sudah lewat</span>
                        <?php else: ?>
                            <span class="badge">Tampil di web</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a class="btn btn-secondary btn-small" href="events-form.php?id=<?= (int)$ev['id'] ?>">Edit</a>
                        <form class="confirm-delete-form" method="POST" action="events-delete.php" onsubmit="return confirm('Hapus event \'<?= e($ev['title']) ?>\'?');">
                            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>