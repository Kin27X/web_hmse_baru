<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<p style="font-size:14px; color:rgba(23,21,51,0.6); margin-bottom:30px;">Pilih apa yang mau dikelola.</p>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:18px;">

    <a class="admin-card" href="periode-list.php" style="text-decoration:none; color:inherit; display:block;">
        <i class="material-icons" style="font-size:26px; color:#2d1fd6;">work_history</i>
        <h3 style="font-size:15px; margin:10px 0 4px;">Program Kerja</h3>
        <p style="font-size:12.5px; color:rgba(23,21,51,0.55); margin:0;">Kelola periode & isi program kerja.</p>
    </a>

    <a class="admin-card" href="events-list.php" style="text-decoration:none; color:inherit; display:block;">
        <i class="material-icons" style="font-size:26px; color:#2d1fd6;">event</i>
        <h3 style="font-size:15px; margin:10px 0 4px;">Event Schedule</h3>
        <p style="font-size:12.5px; color:rgba(23,21,51,0.55); margin:0;">Kelola maksimal 5 acara mendatang.</p>
    </a>

    <a class="admin-card" href="struktur-list.php" style="text-decoration:none; color:inherit; display:block;">
        <i class="material-icons" style="font-size:26px; color:#2d1fd6;">account_tree</i>
        <h3 style="font-size:15px; margin:10px 0 4px;">Struktur Organisasi</h3>
        <p style="font-size:12.5px; color:rgba(23,21,51,0.55); margin:0;">Kelola pengurus & anggota tiap divisi.</p>
    </a>

    <a class="admin-card" href="gallery-list.php" style="text-decoration:none; color:inherit; display:block;">
        <i class="material-icons" style="font-size:26px; color:#2d1fd6;">photo_library</i>
        <h3 style="font-size:15px; margin:10px 0 4px;">Gallery</h3>
        <p style="font-size:12.5px; color:rgba(23,21,51,0.55); margin:0;">Kelola foto & kategori kegiatan.</p>
    </a>

    <a class="admin-card" href="news-list.php" style="text-decoration:none; color:inherit; display:block;">
        <i class="material-icons" style="font-size:26px; color:#2d1fd6;">newspaper</i>
        <h3 style="font-size:15px; margin:10px 0 4px;">News</h3>
        <p style="font-size:12.5px; color:rgba(23,21,51,0.55); margin:0;">Kelola berita & dokumentasi foto.</p>
    </a>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>