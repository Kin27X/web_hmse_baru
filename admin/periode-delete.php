<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('DELETE FROM periode WHERE id = ?');
    $stmt->execute([(int)$_POST['id']]);
}

header('Location: periode-list.php?msg=' . urlencode('Periode berhasil dihapus.'));
exit;
