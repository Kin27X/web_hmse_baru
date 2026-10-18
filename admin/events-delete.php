<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
    $stmt->execute([(int)$_POST['id']]);
}

header('Location: events-list.php?msg=' . urlencode('Event berhasil dihapus.'));
exit;
