<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/admin/includes/news-helpers.php';
$pdo = getDbConnection();

$slug = (isset($_GET['id']) && is_string($_GET['id'])) ? trim($_GET['id']) : '';
if ($slug === '') {
    header('Location: news.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM news WHERE slug = ?');
$stmt->execute([$slug]);
$news = $stmt->fetch();
$notFound = !$news;

if ($notFound) {
    http_response_code(404); // bukan 200, supaya mesin pencari tidak mengindeks URL ngawur
}

/* ---------- Data artikel (hanya 1 berita, bukan semua berita) ---------- */
$pageTitle = 'Berita tidak ditemukan - HMSE';
$metaDesc = '';
$date = ['iso' => '', 'day' => '', 'full' => ''];
$bodyHtml = '';
$hero = null;      // foto utama di atas artikel
$strip = [];       // deretan thumbnail (hanya untuk berita lama tanpa foto yang disisipkan)
$gallery = [];     // foto yang tidak disisipkan (berita yang sudah memakai penyisipan foto)
$photoFiles = [];  // id => nama file asli (untuk og:image)

if (!$notFound) {
    $pageTitle = $news['title'] . ' - HMSE';
    $date = newsFormatDate($news['event_datetime']);
    $metaDesc = newsExcerpt(newsPlainText($news['body_html']), 160);

    $ps = $pdo->prepare('SELECT id, filename FROM news_photos WHERE news_id = ? ORDER BY sort_order ASC, id ASC');
    $ps->execute([$news['id']]);
    $photos = []; // id => src
    foreach ($ps->fetchAll() as $p) {
        $photoFiles[(int) $p['id']] = $p['filename'];
        $photos[(int) $p['id']] = 'images/' . rawurlencode($p['filename']);
    }

    $used = [];
    $bodyHtml = newsRenderBody((string) $news['body_html'], $photos, $news['title'], $used);

    if (empty($photos)) {
        $hero = 'images/logo2.png';
    } elseif (empty($used)) {
        // berita tanpa foto yang disisipkan: tampilan lama (foto utama + deretan thumbnail)
        $hero = reset($photos);
        if (count($photos) > 1) {
            $strip = array_values($photos);
        }
    } else {
        // foto yang sudah disisipkan di teks tidak diulang di atas;
        // sisanya: yang pertama jadi foto utama, selebihnya jadi galeri di bawah teks
        $rest = array_diff_key($photos, array_flip($used));
        if ($rest) {
            $hero = reset($rest);
            $gallery = array_slice(array_values($rest), 1);
        }
    }
}

/* ---------- More Posts (6 berita lain) ---------- */
$ms = $pdo->prepare(
    'SELECT n.slug, n.title, n.event_datetime,
            (SELECT np.filename FROM news_photos np WHERE np.news_id = n.id ORDER BY np.sort_order ASC, np.id ASC LIMIT 1) AS thumb
       FROM news n
      WHERE n.slug <> ?
      ORDER BY n.event_datetime DESC
      LIMIT 6'
);
$ms->execute([$slug]);
$morePosts = $ms->fetchAll();

/* ---------- Meta Open Graph (preview link WhatsApp/Facebook) ---------- */
$origin = newsOrigin();
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/news_detail.php';
$siteRoot = $origin . rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
$pageUrl = $origin . $scriptName . '?id=' . rawurlencode($slug);
$ogImage = $siteRoot . '/images/' . (!$notFound && $photoFiles ? rawurlencode(reset($photoFiles)) : 'logo2.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= newsE($pageTitle) ?></title>
<?php if ($notFound): ?>
    <meta name="robots" content="noindex">
<?php else: ?>
    <meta name="description" content="<?= newsE($metaDesc) ?>">
    <link rel="canonical" href="<?= newsE($pageUrl) ?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="HMSE">
    <meta property="og:title" content="<?= newsE($news['title']) ?>">
    <meta property="og:description" content="<?= newsE($metaDesc) ?>">
    <meta property="og:url" content="<?= newsE($pageUrl) ?>">
    <meta property="og:image" content="<?= newsE($ogImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css?v=<?= assetVersion('css/style.css') ?>">
    <link rel="stylesheet" href="css/news_detail.css?v=<?= assetVersion('css/news_detail.css') ?>">
    <link rel="stylesheet" href="css/news_body.css?v=<?= assetVersion('css/news_body.css') ?>">
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

    <section class="nd-section">

        <a href="news.php" class="nd-back">
            <i class="material-icons">arrow_back</i>
            Kembali ke News
        </a>

        <div class="nd-layout">

            <article class="nd-article reveal reveal-up" id="ndArticle">
<?php if ($notFound): ?>
                <h1>Berita tidak ditemukan</h1>
                <p>Berita yang kamu cari tidak ada atau sudah dihapus. Coba pilih dari daftar berita lain.</p>
<?php else: ?>
                <h1><?= newsE($news['title']) ?></h1>
                <div class="nd-meta">Date created: <?= newsE($date['full']) ?></div>
<?php if (!empty($news['author'])): ?>
                <div class="nd-meta">Author: <strong><?= newsE($news['author']) ?></strong></div>
<?php endif; ?>
                <button class="nd-share" type="button" id="ndShareBtn" data-title="<?= newsE($news['title']) ?>">Share...</button>
<?php if ($hero): ?>
                <div class="nd-photo">
                    <img src="<?= newsE($hero) ?>" alt="<?= newsE($news['title']) ?>" id="ndHeroPhoto">
                </div>
<?php endif; ?>
<?php if ($strip): ?>
                <div class="nd-photo-strip">
<?php foreach ($strip as $i => $src): ?>
                    <button type="button" class="nd-photo-thumb<?= $i === 0 ? ' active' : '' ?>" data-src="<?= newsE($src) ?>"><img src="<?= newsE($src) ?>" alt="Foto <?= $i + 1 ?>" loading="lazy"></button>
<?php endforeach; ?>
                </div>
<?php endif; ?>
                <div class="nd-body"><?= $bodyHtml ?></div>
<?php if ($gallery): ?>
                <div class="nd-gallery">
<?php foreach ($gallery as $i => $src): ?>
                    <a href="<?= newsE($src) ?>" target="_blank" rel="noopener"><img src="<?= newsE($src) ?>" alt="Foto tambahan <?= $i + 1 ?>" loading="lazy"></a>
<?php endforeach; ?>
                </div>
<?php endif; ?>
<?php endif; ?>
            </article>

            <aside class="nd-sidebar reveal reveal-up">
                <h4>More Posts</h4>
                <div class="nd-more-list" id="ndMoreList">
<?php foreach ($morePosts as $m): ?>
                    <a class="nd-more-item" href="news_detail.php?id=<?= newsE(rawurlencode($m['slug'])) ?>">
                        <div class="nd-more-thumb">
                            <img src="<?= newsE($m['thumb'] ? 'images/' . rawurlencode($m['thumb']) : 'images/logo2.png') ?>" alt="<?= newsE($m['title']) ?>" loading="lazy">
                        </div>
                        <div class="nd-more-body">
                            <span class="nd-more-title"><?= newsE($m['title']) ?></span>
                            <span class="nd-more-date"><?= newsE(newsFormatDate($m['event_datetime'])['day']) ?></span>
                        </div>
                    </a>
<?php endforeach; ?>
                </div>
            </aside>

        </div>

    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script src="js/index.js?v=<?= assetVersion('js/index.js') ?>"></script>
    <script src="js/news-data.js?v=<?= assetVersion('js/news-data.js') ?>"></script>
    <script src="js/news_detail.js?v=<?= assetVersion('js/news_detail.js') ?>"></script>

</body>
</html>