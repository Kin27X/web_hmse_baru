<?php
require __DIR__ . '/config.php';
$pdo = getDbConnection();

$rows = $pdo->query('SELECT * FROM gallery_photos ORDER BY sort_order ASC, id DESC')->fetchAll();
$galleryPhotos = array_map(fn($r) => [
    'photo' => 'images/' . $r['photo'],
    'category' => $r['category'],
], $rows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css?v=<?= assetVersion('css/style.css') ?>">
    <link rel="stylesheet" href="css/gallery.css?v=<?= assetVersion('css/gallery.css') ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');
    </style>
</head>
<body>

    <header id="navbar">
        <nav class="navbar">

            <div class="nav-bg-gray"></div>

            <div class="brand">
                <img class="logo" src="images/logo2.png" alt="logo HMSE">
                <span>HMSE</span>
            </div>

            <ul class="menu" id="menu">
                <li class="menu-header">
                    <div class="brand">
                        <img class="logo" src="images/logo2.png" alt="logo HMSE">
                        <span>HMSE</span>
                    </div>
                    <span class="menu-close" id="menuClose" aria-label="Tutup menu">&times;</span>
                </li>
                <li class="nav-li"><a href="index.php">Home</a></li>
                <li class="nav-li drop">
                    <div class="drop-btn">
                      About
                      <span class="material-icons dropdown-icon">
                        arrow_drop_down
                      </span>
                    </div>

                    <div class="dropdown">
                      <ul class="dropdown-inner">
                        <li><a href="visi_&_misi.php">Visi & Misi</a></li>
                        <li><a href="program-kerja.php">Program Kerja</a></li>
                        <li><a href="event-schedule.php">Event Schedule</a></li>
                        <li><a href="struktur.php">Struktur Organisasi</a></li>
                      </ul>
                    </div>

                </li>
                <li class="nav-li current"><a href="gallery.php">Gallery</a></li>
                <li class="nav-li"><a href="news.php">News</a></li>
                <li class="nav-li mobile-btn">
                    <a class="btn" href="#"><button>Contact</button></a>
                </li>
            </ul>

            <div class="nav-accent">
                <span class="accent-circle"></span>
                <div class="button">
                    <a class="btn" href="#"><button>Contact</button></a>
                </div>
            </div>

            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>

        </nav>

        <div class="menu-overlay" id="menuOverlay"></div>
    </header>

    <!-- ================= PAGE BANNER ================= -->
    <section class="gl-banner">
        <div class="gl-banner-glow"></div>
        <div class="gl-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Dokumentasi</span>
            <h1>Gallery</h1>
            <p>Cuplikan momen dari kegiatan-kegiatan HMSE.</p>
        </div>
    </section>

    <!-- ================= GALLERY ================= -->
    <section class="gl-section">

        <div class="gl-filter reveal reveal-up" id="glFilter">
            <button class="gl-filter-btn active" data-filter="semua">Semua</button>
            <button class="gl-filter-btn" data-filter="olahraga">Olahraga</button>
            <button class="gl-filter-btn" data-filter="belajar">Belajar</button>
            <button class="gl-filter-btn" data-filter="event">Event</button>
        </div>

        <?php if (empty($galleryPhotos)): ?>
            <p style="text-align:center; color:rgba(23,21,51,0.5); font-size:14px;">Belum ada foto di gallery.</p>
        <?php else: ?>
            <div class="gl-grid" id="glGrid"></div>
        <?php endif; ?>

    </section>

    <!-- ================= LIGHTBOX ================= -->
    <div class="gl-lightbox" id="glLightbox">
        <div class="gl-lightbox-backdrop" id="glLightboxBackdrop"></div>
        <button class="gl-lightbox-close" id="glLightboxClose" aria-label="Tutup">&times;</button>
        <button class="gl-lightbox-nav gl-lightbox-prev" id="glLightboxPrev" aria-label="Sebelumnya">
            <i class="material-icons">chevron_left</i>
        </button>
        <div class="gl-lightbox-body">
            <img id="glLightboxImg" src="" alt="">
            <div class="gl-lightbox-caption">
                <span id="glLightboxAlbum"></span>
                <span id="glLightboxCount"></span>
            </div>
        </div>
        <button class="gl-lightbox-nav gl-lightbox-next" id="glLightboxNext" aria-label="Berikutnya">
            <i class="material-icons">chevron_right</i>
        </button>
    </div>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>


    <script>
        const galleryPhotos = <?= json_encode($galleryPhotos, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="js/index.js?v=<?= assetVersion('js/index.js') ?>"></script>
    <script src="js/gallery.js?v=<?= assetVersion('js/gallery.js') ?>"></script>

</body>
</html>