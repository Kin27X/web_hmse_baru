<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$msg = 'Paslon berhasil dihapus.';
$isError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && csrfCheck()) {
    $pdo = getDbConnection();
    $id = (int)$_POST['id'];

    // Paslon yang sudah punya suara tidak boleh dihapus, supaya suaranya tidak hilang diam-diam.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM vote_ballots WHERE candidate_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        $msg = 'Paslon ini sudah mendapat suara, jadi tidak bisa dihapus. Reset data suara dulu kalau ini uji coba.';
        $isError = true;
    } else {
        $stmt = $pdo->prepare('SELECT photo FROM vote_candidates WHERE id = ?');
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();

        $pdo->prepare('DELETE FROM vote_candidates WHERE id = ?')->execute([$id]);
        // Dihapus SETELAH barisnya hilang, supaya cek "masih dipakai?" tidak menghitung paslon ini.
        deleteUploadedImage($photo ?: null, $pdo);
    }
}

header('Location: voting-paslon-list.php?msg=' . urlencode($msg) . ($isError ? '&err=1' : ''));
exit;
