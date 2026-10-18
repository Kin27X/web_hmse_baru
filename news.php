<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/admin/includes/news-helpers.php';
$pdo = getDbConnection();

// Satu query saja (thumbnail diambil lewat subquery) -- tidak ada lagi query foto per berita.
$rows = $pdo->query(
    'SELECT n.id, n.slug, n.title, n.event_datetime, n.author, n.body_html,
            (SELECT np.filename FROM news_photos np WHERE np.news_id = n.id ORDER BY np.sort_order ASC, np.id ASC LIMIT 1) AS thumb
       FROM news n
      ORDER BY n.event_datetime DESC'
)->fetchAll();

$newsArticles = [];
foreach ($rows as $n) {
    $d = newsFormatDate($n['event_datetime']);
    $plain = newsPlainText($n['body_html']);

    $newsArticles[] = [
        'id'          => $n['slug'],
        'title'       => $n['title'],
        'dateISO'     => $d['iso'],
        'dateDisplay' => $d['full'],
        'dateDay'     => $d['day'],
        'author'      => $n['author'] ?: null,
        'thumbnail'   => $n['thumb'] ? 'images/' . rawurlencode($n['thumb']) : 'images/logo2.png',
        'excerpt'     => newsExcerpt($plain, 160),
        'search'      => mb_substr($plain, 0, 600, 'UTF-8'), // bahan pencarian (bukan seluruh isi berita)
    ];
}

// HEX_* mencegah "</script>" di dalam data menutup blok <script>; SUBSTITUTE mencegah json_encode gagal total
$newsJson = json_encode(
    $newsArticles,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
) ?: '[]';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css?v=<?= assetVersion('css/style.css') ?>">
    <link rel="stylesheet" href="css/news.css?v=<?= assetVersion('css/news.css') ?>">
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
                <li class="nav-li"><a href="gallery.php">Gallery</a></li>
                <li class="nav-li current"><a href="news.php">News</a></li>
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
    <section class="nw-banner">
        <div class="nw-banner-glow"></div>
        <div class="nw-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Informasi</span>
            <h1>News</h1>
            <p>Kabar &amp; dokumentasi kegiatan HMSE terbaru.</p>
        </div>
    </section>

    <!-- ================= NEWS ================= -->
    <section class="nw-section">

        <div class="nw-controls reveal reveal-up">

            <div class="nw-view-toggle" id="nwViewToggle">
                <button class="active" data-mode="list" aria-label="Tampilan daftar">
                    <i class="material-icons">view_list</i>
                </button>
                <button data-mode="grid" aria-label="Tampilan kotak-kotak">
                    <i class="material-icons">grid_view</i>
                </button>
            </div>

            <div class="nw-search">
                <i class="material-icons">search</i>
                <input type="text" id="nwSearchInput" placeholder="Cari berita...">
            </div>

        </div>

        <div class="nw-list" id="nwList"></div>
        <div class="nw-grid" id="nwGrid" hidden></div>

        <p class="nw-empty" id="nwEmpty" hidden>Tidak ada berita yang cocok dengan pencarianmu.</p>

        <div class="nw-pagination" id="nwPagination">
            <button class="nw-page-btn" id="nwPrevBtn" aria-label="Halaman sebelumnya">
                <i class="material-icons">chevron_left</i>
            </button>
            <span class="nw-page-label" id="nwPageLabel">Halaman 1 / 1</span>
            <button class="nw-page-btn" id="nwNextBtn" aria-label="Halaman berikutnya">
                <i class="material-icons">chevron_right</i>
            </button>
        </div>

    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script>
        const newsArticles = <?= $newsJson ?>;
    </script>
    <script src="js/index.js?v=<?= assetVersion('js/index.js') ?>"></script>
    <script src="js/news-data.js?v=<?= assetVersion('js/news-data.js') ?>"></script>
    <script src="js/news.js?v=<?= assetVersion('js/news.js') ?>"></script>

</body>
</html>