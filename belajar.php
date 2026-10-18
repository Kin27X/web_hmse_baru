<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belajar - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/belajar.css">
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
    <section class="bl-banner">
        <div class="bl-banner-glow"></div>
        <div class="bl-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Kegiatan</span>
            <h1>Belajar</h1>
            <p>Belajar pemrograman bareng HMSE, dari dasar sampai siap dipakai bikin proyek beneran.</p>
        </div>
    </section>

    <!-- ================= DAFTAR BAHASA/TOPIK ================= -->
    <!-- CONTOH/PLACEHOLDER: tinggal ganti/tambah/hapus .bl-card sesuai
         bahasa/topik yang beneran diajarkan. Ikonnya pakai Material Icons
         (lihat fonts.google.com/icons buat ganti nama ikonnya). -->
    <section class="bl-section">
        <div class="bl-grid">

            <div class="bl-card reveal reveal-up">
                <div class="bl-card-icon"><i class="material-icons">code</i></div>
                <h3>Python</h3>
                <p>Dasar pemrograman, logika, dan data science.</p>
            </div>

            <div class="bl-card reveal reveal-up">
                <div class="bl-card-icon"><i class="material-icons">javascript</i></div>
                <h3>JavaScript</h3>
                <p>Interaktivitas web, front-end &amp; back-end (Node.js).</p>
            </div>

            <div class="bl-card reveal reveal-up">
                <div class="bl-card-icon"><i class="material-icons">language</i></div>
                <h3>HTML &amp; CSS</h3>
                <p>Dasar membangun tampilan website.</p>
            </div>

            <div class="bl-card reveal reveal-up">
                <div class="bl-card-icon"><i class="material-icons">smartphone</i></div>
                <h3>Java / Kotlin</h3>
                <p>Pengembangan aplikasi Android.</p>
            </div>

        </div>

        <p class="bl-note">*Daftar bahasa/topik di atas masih contoh — tinggal disesuaikan dengan yang beneran diajarkan HMSE.</p>
    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script src="js/index.js"></script>

</body>
</html>
