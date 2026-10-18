<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olahraga - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/olahraga.css">
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
    <section class="ol-banner">
        <div class="ol-banner-glow"></div>
        <div class="ol-banner-content reveal reveal-up">
            <span class="section-eyebrow section-eyebrow-light">Kegiatan</span>
            <h1>Olahraga</h1>
            <p>Ikuti kegiatan olahraga bersama HMSE, sekaligus jaga kebugaran dan mempererat kekeluargaan.</p>
        </div>
    </section>

    <!-- ================= DAFTAR CABANG OLAHRAGA ================= -->
    <section class="ol-section">
        <div class="ol-grid">

            <div class="ol-card reveal reveal-up">
                <div class="ol-card-photo">
                    <img src="images/foto.jpg" alt="Futsal">
                </div>
                <div class="ol-card-body">
                    <h3>Futsal</h3>
                    <p>Latihan &amp; tanding futsal rutin bareng anggota HMSE. Cocok buat yang suka main bola dan mau sekalian olahraga bareng temen satu jurusan.</p>
                    <a class="ol-wa-btn" href="https://chat.whatsapp.com/GANTI_DENGAN_LINK_GRUP_FUTSAL" target="_blank" rel="noopener"> <!-- Kalo mau ganti grup wa nya, langsung aja temepel disini linknya  -->
                        <i class="fa-brands fa-whatsapp"></i> Gabung Grup WhatsApp
                    </a>
                </div>
            </div>

            <div class="ol-card reveal reveal-up">
                <div class="ol-card-photo">
                    <img src="images/motif_biru.jpg" alt="Badminton">
                </div>
                <div class="ol-card-body">
                    <h3>Badminton</h3>
                    <p>Main bulu tangkis santai tiap minggu, buat semua level dari pemula sampai yang udah jago.</p>
                    <a class="ol-wa-btn" href="https://chat.whatsapp.com/GANTI_DENGAN_LINK_GRUP_BADMINTON" target="_blank" rel="noopener"> <!-- Kalo mau ganti grup wa nya, langsung aja temepel disini linknya  -->
                        <i class="fa-brands fa-whatsapp"></i> Gabung Grup WhatsApp
                    </a>
                </div>
            </div>

            <div class="ol-card reveal reveal-up">
              <div class="ol-card-photo">
                  <img src="images/motif_biru.jpg" alt="Badminton">
              </div>
              <div class="ol-card-body">
                  <h3>Tesn</h3>
                  <p>Main bulu tangkis santai tiap minggu, buat semua level dari pemula sampai yang udah jago.</p>
                  <a class="ol-wa-btn" href="https://chat.whatsapp.com/GANTI_DENGAN_LINK_GRUP_BADMINTON" target="_blank" rel="noopener"> <!-- Kalo mau ganti grup wa nya, langsung aja temepel disini linknya  -->
                      <i class="fa-brands fa-whatsapp"></i> Gabung Grup WhatsApp
                  </a>
              </div>
          </div>
              <!-- Kalo mau ganti ato nambah grup lain, samain aja kyk atasnya trs ganti juga link nya. Auto posisi itu  -->
        </div>

        <p class="ol-note">*Foto, deskripsi, dan link grup WhatsApp di atas masih contoh — tinggal diganti sesuai cabang olahraga yang sebenarnya.</p>
    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script src="js/index.js"></script>

</body>
</html>
