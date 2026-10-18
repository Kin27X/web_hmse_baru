<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';

$periodeId = (int)($_POST['periode_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('DELETE FROM program_kerja WHERE id = ?');
    $stmt->execute([(int)$_POST['id']]);
}

header('Location: proker-list.php?periode_id=' . $periodeId . '&msg=' . urlencode('Program kerja berhasil dihapus.'));
exit;
