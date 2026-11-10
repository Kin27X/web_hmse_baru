<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && csrfCheck()) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('DELETE FROM struktur_nodes WHERE id = ?');
    $stmt->execute([(int)$_POST['id']]);
}

header('Location: struktur-list.php?msg=' . urlencode('Data berhasil dihapus.'));
exit;
