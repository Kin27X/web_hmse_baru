<?php
/**
 * header.php — dipanggil di awal tiap halaman admin (setelah auth check).
 * Butuh variabel $pageTitle (string) dan opsional $currentNav (string,
 * salah satu dari: proker, events, struktur, gallery, news) sudah
 * di-set sebelum include file ini.
 */
$currentNav = $currentNav ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> - HMSE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <header class="admin-header">
        <h1>Admin HMSE</h1>
        <nav class="admin-nav">
            <a href="periode-list.php" class="<?= $currentNav === 'proker' ? 'current' : '' ?>">Program Kerja</a>
            <a href="events-list.php" class="<?= $currentNav === 'events' ? 'current' : '' ?>">Event Schedule</a>
            <a href="struktur-list.php" class="<?= $currentNav === 'struktur' ? 'current' : '' ?>">Struktur</a>
            <a href="gallery-list.php" class="<?= $currentNav === 'gallery' ? 'current' : '' ?>">Gallery</a>
            <a href="news-list.php" class="<?= $currentNav === 'news' ? 'current' : '' ?>">News</a>
        </nav>
        <div class="admin-user">
            <span>Halo, <?= e($_SESSION['admin_username'] ?? '') ?></span>
            <a href="logout.php">Keluar</a>
        </div>
    </header>

    <main class="admin-main">