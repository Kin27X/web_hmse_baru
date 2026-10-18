<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/news-helpers.php';

// Dipanggil lewat fetch() dari news-form.php (balasan JSON),
// tapi tetap jalan sebagai form biasa (balasan redirect).
$isAjax  = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$newsId  = (int) ($_POST['news_id'] ?? 0);
$photoId = (int) ($_POST['photo_id'] ?? 0);

$result = ['ok' => false, 'error' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $photoId > 0 && $newsId > 0) {
    if (!newsCsrfValid()) {
        $result['error'] = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        try {
            $pdo = getDbConnection();

            // pastikan foto ini memang milik berita yang dikirim
            $stmt = $pdo->prepare('SELECT filename FROM news_photos WHERE id = ? AND news_id = ?');
            $stmt->execute([$photoId, $newsId]);
            $row = $stmt->fetch();

            if (!$row) {
                $result['error'] = 'Foto tidak ditemukan.';
            } else {
                $pdo->prepare('DELETE FROM news_photos WHERE id = ?')->execute([$photoId]);
                newsDeleteImageFiles($pdo, [$row['filename']]);
                $result = ['ok' => true];
            }
        } catch (Throwable $ex) {
            error_log('news-photo-delete: ' . $ex->getMessage());
            $result['error'] = 'Gagal menghapus foto.';
        }
    }
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

newsFlashSet($result['ok'] ? 'Foto dihapus.' : $result['error'], $result['ok'] ? 'success' : 'error');
header('Location: news-form.php?id=' . $newsId);
exit;