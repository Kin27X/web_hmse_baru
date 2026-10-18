<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/event.css">
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
    <section class="ev-banner">
        <div class="ev-banner-glow"></div>
        <div class="ev-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Kegiatan</span>
            <h1>Event</h1>
            <p>Berbagai acara yang diselenggarakan HMSE, mulai dari seminar sampai kegiatan kebersamaan.</p>
        </div>
    </section>

    <!-- ================= JENIS EVENT ================= -->
    <!-- CONTOH/PLACEHOLDER: tinggal ganti/tambah/hapus .ev-card sesuai
         jenis acara yang beneran diadakan HMSE. -->
    <section class="ev-section">
        <div class="ev-grid">

            <div class="ev-card reveal reveal-up">
                <div class="ev-card-icon"><i class="material-icons">record_voice_over</i></div>
                <h3>Seminar &amp; Workshop</h3>
                <p>Belajar dari praktisi/pembicara tamu seputar teknologi dan pengembangan diri.</p>
            </div>

            <div class="ev-card reveal reveal-up">
                <div class="ev-card-icon"><i class="material-icons">groups</i></div>
                <h3>Makrab</h3>
                <p>Malam keakraban buat mempererat kekeluargaan antar mahasiswa/i SE.</p>
            </div>

            <div class="ev-card reveal reveal-up">
                <div class="ev-card-icon"><i class="material-icons">terminal</i></div>
                <h3>Bootcamp</h3>
                <p>Pelatihan intensif seputar pengembangan perangkat lunak.</p>
            </div>

        </div>

        <div class="ev-cta">
            <a href="event-schedule.php">
                Lihat Jadwal Event Mendatang
                <i class="material-icons">arrow_forward</i>
            </a>
        </div>

        <p class="ev-note">*Jenis event di atas masih contoh — tinggal disesuaikan dengan yang beneran diadakan HMSE.</p>
    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script src="js/index.js"></script>

</body>
</html>
