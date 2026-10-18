<?php
require __DIR__ . '/config.php';
$pdo = getDbConnection();

$periodes = $pdo->query('SELECT * FROM periode ORDER BY start_year DESC')->fetchAll();

$programsByPeriode = [];
foreach ($pdo->query('SELECT * FROM program_kerja ORDER BY sort_order ASC, id ASC') as $row) {
    $programsByPeriode[$row['periode_id']][] = $row;
}

// Periode aktif secara default = yang tahunnya paling baru
$activePeriode = $periodes[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Kerja - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css?v=<?= assetVersion('css/style.css') ?>">
    <link rel="stylesheet" href="css/program-kerja.css?v=<?= assetVersion('css/program-kerja.css') ?>">
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
                <li class="nav-li drop current">
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
    <section class="pk-banner">
        <div class="pk-banner-glow"></div>
        <div class="pk-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Kegiatan Kami</span>
            <h1>Program Kerja</h1>
            <p>Rangkaian program yang kami jalankan untuk mahasiswa/i Software Engineering dan kebermanfaatan masyarakat luas.</p>
        </div>
    </section>

    <!-- ================= PROGRAM KERJA ================= -->
    <section class="proker-section">

        <div class="proker-head reveal reveal-up">
            <div class="periode-select" id="periodeSelect">
                <button class="periode-btn" id="periodeBtn" type="button" aria-haspopup="true" aria-expanded="false">
                    <span id="periodeLabel">Periode <?= $activePeriode ? (int)$activePeriode['start_year'] . '/' . (int)$activePeriode['end_year'] : '-' ?></span>
                    <span class="material-icons">arrow_drop_down</span>
                </button>
                <ul class="periode-list" id="periodeList">
                    <?php foreach ($periodes as $i => $p): $key = $p['start_year'] . '-' . $p['end_year']; ?>
                        <li data-periode="<?= htmlspecialchars($key) ?>" class="<?= $i === 0 ? 'active' : '' ?>">Periode <?= (int)$p['start_year'] ?>/<?= (int)$p['end_year'] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="timeline-wrap">

            <div class="timeline-programs" id="timelinePrograms">

                <?php foreach ($periodes as $i => $p): $key = $p['start_year'] . '-' . $p['end_year'];
                    $programs = $programsByPeriode[$p['id']] ?? []; ?>
                    <div class="proker-group" data-periode-group="<?= htmlspecialchars($key) ?>" <?= $i === 0 ? '' : 'hidden' ?>>
                        <?php if ($programs): ?>
                            <?php foreach ($programs as $prog): ?>
                                <div class="proker-card reveal reveal-up">
                                    <h3><?= htmlspecialchars($prog['title']) ?></h3>
                                    <p><?= nl2br(htmlspecialchars($prog['description'] ?? '')) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="proker-card proker-card-empty reveal reveal-up">
                                <p>Belum ada data program kerja untuk periode ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            </div>

            <div class="timeline-rail" aria-hidden="true">
                <span class="rail-line"></span>
                <span class="rail-dot rail-dot-top"><em><?= $activePeriode ? (int)$activePeriode['start_year'] : '' ?></em></span>
                <span class="rail-dot rail-dot-bottom"><em><?= $activePeriode ? (int)$activePeriode['end_year'] : '' ?></em></span>
            </div>

        </div>

    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script src="js/index.js?v=<?= assetVersion('js/index.js') ?>"></script>
    <script src="js/program-kerja.js?v=<?= assetVersion('js/program-kerja.js') ?>"></script>

</body>
</html>