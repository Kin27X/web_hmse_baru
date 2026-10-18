<?php
/**
 * auth.php — panggil file ini di baris paling atas SETIAP halaman
 * admin yang butuh login (dashboard, kelola news, kelola gallery,
 * kelola struktur, dst). Kalau belum login, otomatis dilempar ke
 * halaman login.
 */

// Aman di-include lebih dari sekali; cookie session dibuat lebih ketat.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // otomatis aktif kalau sudah HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}