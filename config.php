<?php
/**
 * config.php — pusat pengaturan koneksi database.
*/

define('DB_DRIVER', 'mysql');

define('DB_HOST', 'localhost');
define('DB_NAME', 'web_hmse');
define('DB_USER', 'root');
define('DB_PASS', '');
// --------------------------------------------------------------------

function getDbConnection(): PDO {

    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_DRIVER === 'sqlite') {
        $path = __DIR__ . '/data/hmse.sqlite';
        $pdo = new PDO('sqlite:' . $path);
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (DB_DRIVER === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }

    return $pdo;
}

/**
 * assetVersion($relativePath) — dipakai di link CSS/JS, mis:
 * <link rel="stylesheet" href="css/program-kerja.css?v=<?= assetVersion('css/program-kerja.css') ?>">
 *
 * Nilai yang ditampilin di webnya diambil dari waktu file itu TERAKHIR DIUBAH (filemtime). Jadi
 * tiap kali file CSS/JS-nya diedit, nomor versinya otomatis berubah,
 * dan browser WAJIB ambil ulang versi barunya, bukan pakai versi lama yang di-cache. Harusnya gitu
 */
function assetVersion(string $relativePath): string {
    $fullPath = __DIR__ . '/' . ltrim($relativePath, '/');
    return file_exists($fullPath) ? (string) filemtime($fullPath) : '1';
}