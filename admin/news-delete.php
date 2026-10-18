<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/news-helpers.php';

$id = (int) ($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !newsCsrfValid() || $id <= 0) {
    newsFlashSet('Permintaan tidak valid atau sesi sudah kedaluwarsa. Coba lagi.', 'error');
    header('Location: news-list.php');
    exit;
}

$pdo = getDbConnection();

try {
    // catat nama file foto dulu, sebelum barisnya dihapus
    $stmt = $pdo->prepare('SELECT filename FROM news_photos WHERE news_id = ?');
    $stmt->execute([$id]);
    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();
    // dihapus eksplisit, jadi tidak bergantung pada ON DELETE CASCADE di schema
    $pdo->prepare('DELETE FROM news_photos WHERE news_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);
    $pdo->commit();

    // file fisik dihapus SETELAH database berhasil
    newsDeleteImageFiles($pdo, $files);

    newsFlashSet('Berita berhasil dihapus.');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('news-delete: ' . $ex->getMessage());
    newsFlashSet('Gagal menghapus berita.', 'error');
}

header('Location: news-list.php');
exit;