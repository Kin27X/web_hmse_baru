<?php
require __DIR__ . '/config.php';
$pdo = getDbConnection();

$bulanIndo = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$bulanSingkat = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

$today = date('Y-m-d');
$stmt = $pdo->prepare('SELECT * FROM events WHERE event_date >= ? ORDER BY event_date ASC LIMIT 5');
$stmt->execute([$today]);
$rows = $stmt->fetchAll();

$scheduleEvents = array_map(function($ev) use ($bulanIndo, $bulanSingkat) {
    [$y, $m, $d] = explode('-', $ev['event_date']);
    $d = (int)$d; $m = (int)$m; $y = (int)$y;
    return [
        'name' => $ev['title'],
        'dateShort' => $d . ' ' . $bulanSingkat[$m],
        'dateFull' => $d . ' ' . $bulanIndo[$m] . ' ' . $y,
        'place' => $ev['place'] ?? '',
        'time' => $ev['event_time'] ?? '',
        'photo' => $ev['photo'] ? 'images/' . $ev['photo'] : null,
    ];
}, $rows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Schedule - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css?v=<?= assetVersion('css/style.css') ?>">
    <link rel="stylesheet" href="css/event-schedule.css?v=<?= assetVersion('css/event-schedule.css') ?>">
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
    <section class="es-banner">
        <div class="es-banner-glow"></div>
        <div class="es-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Jadwal</span>
            <h1>Event Schedule</h1>
            <p>Rangkaian acara HMSE, dari yang paling dekat sampai yang masih beberapa waktu lagi.</p>
        </div>
    </section>

    <!-- ================= EVENT SCHEDULE ================= -->
    <section class="es-section">

        <?php if (empty($scheduleEvents)): ?>
            <p style="text-align:center; color:rgba(23,21,51,0.5); font-size:14px;">Belum ada event mendatang.</p>
        <?php else: ?>

        <!-- ===== DESKTOP: garis waktu horizontal + daftar detail ===== -->
        <div class="es-desktop-wrap">

            <div class="eventline-track" id="eventlineTrack">
                <span class="eventline-line"></span>
                <div class="eventline-nodes" id="eventlineNodes"></div>
            </div>

            <div class="eventlist" id="eventlist"></div>

        </div>

        <!-- ===== MOBILE: garis waktu vertikal + kartu bisa diklik ===== -->
        <div class="es-mobile-wrap" id="esMobileWrap"></div>

        <?php endif; ?>

    </section>

    <!-- ================= MODAL DETAIL EVENT (mobile) ================= -->
    <div class="event-modal" id="eventModal">
        <div class="event-modal-backdrop" id="eventModalBackdrop"></div>
        <div class="event-modal-box" role="dialog" aria-modal="true" aria-label="Detail acara">
            <button class="event-modal-close" id="eventModalClose" aria-label="Tutup">&times;</button>
            <div class="event-modal-photo" id="eventModalPhoto"></div>
            <span class="section-eyebrow" id="eventModalDate"></span>
            <h3 id="eventModalName"></h3>
            <p id="eventModalMeta"></p>
        </div>
    </div>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>


    <script>
        const scheduleEvents = <?= json_encode($scheduleEvents, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="js/index.js?v=<?= assetVersion('js/index.js') ?>"></script>
    <script src="js/event-schedule.js?v=<?= assetVersion('js/event-schedule.js') ?>"></script>

</body>
</html>