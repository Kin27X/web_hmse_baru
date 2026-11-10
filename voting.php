<?php
require __DIR__ . '/admin/includes/auth.php'; // cuma buat session_start dengan cookie yang aman
require __DIR__ . '/config.php';
require __DIR__ . '/admin/includes/helpers.php';
require __DIR__ . '/admin/includes/voting-helpers.php';

$pdo = getDbConnection();
$deviceId = votingDeviceId(); // cookie ID perangkat (dibuat sebelum ada output)
$state = votingState($pdo);
$settings = $state['settings'];
$status = $state['status'];
$candidates = votingCandidates($pdo);
$options = votingOptions($candidates);

$error = '';
// Kalau NPM ditolak: ['type' => 'tidak_terdaftar'|'sudah'|'perangkat', 'npm' => ...].
// Nama & jam memilih sengaja TIDAK ditampilkan, supaya daftar nama anggota
// tidak bisa disedot dari sini.
$blocked = null;

// Batas percobaan cek NPM: per perangkat & per IP (IP longgar karena WiFi kampus dipakai bareng).
const VOTE_RL_WINDOW = 600;   // 10 menit
const VOTE_RL_DEVICE = 8;
const VOTE_RL_IP     = 40;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrfCheck()) {
        $error = 'Sesi kamu kedaluwarsa. Silakan coba lagi.';
    } elseif ($action === 'ganti_npm') {
        unset($_SESSION['vote_voter_id']);
        header('Location: voting.php');
        exit;
    } elseif ($status !== 'open') {
        $error = 'Voting sedang tidak dibuka.';
    } elseif ($action === 'cek_npm') {
        $npm = is_string($_POST['npm'] ?? null) ? substr(normalizeNpm($_POST['npm']), 0, 30) : '';
        $wait = max(
            rateLimitWait($pdo, 'vote_dev:' . $deviceId, VOTE_RL_DEVICE),
            rateLimitWait($pdo, 'vote_ip:' . clientIp(), VOTE_RL_IP)
        );

        if ($wait > 0) {
            votingLog($pdo, null, $npm ?: null, 'dibatasi');
            $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . max(1, (int)ceil($wait / 60)) . ' menit.';
        } elseif ($npm === '') {
            $error = 'NPM wajib diisi.';
        } else {
            rateLimitHit($pdo, 'vote_dev:' . $deviceId, VOTE_RL_WINDOW);
            rateLimitHit($pdo, 'vote_ip:' . clientIp(), VOTE_RL_WINDOW);

            $stmt = $pdo->prepare('SELECT * FROM vote_voters WHERE npm = ?');
            $stmt->execute([$npm]);
            $voter = $stmt->fetch();

            if (!$voter) {
                votingLog($pdo, null, $npm, 'npm_tidak_terdaftar');
                $blocked = ['type' => 'tidak_terdaftar', 'npm' => $npm];
            } elseif ((int)$voter['has_voted'] === 1) {
                votingLog($pdo, (int)$voter['id'], $npm, 'ditolak_sudah_memilih');
                $blocked = ['type' => 'sudah', 'npm' => $npm];
            } elseif (votingDeviceBlocked($pdo, $deviceId, $voter)) {
                votingLog($pdo, (int)$voter['id'], $npm, 'ditolak_perangkat');
                $blocked = ['type' => 'perangkat', 'npm' => $npm];
            } else {
                session_regenerate_id(true);
                $_SESSION['vote_voter_id'] = (int)$voter['id'];
                header('Location: voting.php');
                exit;
            }
        }
    } elseif ($action === 'kirim_suara') {
        $voterId = (int)($_SESSION['vote_voter_id'] ?? 0);
        $choice = (string)($_POST['choice'] ?? '');

        if (!$voterId) {
            $error = 'Masukkan NPM kamu dulu.';
        } elseif (!in_array($choice, array_column($options, 'key'), true)) {
            $error = 'Pilih salah satu dulu sebelum mengirim suara.';
        } else {
            $result = castBallot($pdo, $voterId, $choice, $deviceId);
            if ($result === null) {
                unset($_SESSION['vote_voter_id']);
                $_SESSION['vote_done'] = true;
                header('Location: voting.php');
                exit;
            }
            if ($result === 'sudah' || $result === 'perangkat') {
                unset($_SESSION['vote_voter_id']);
                $stmt = $pdo->prepare('SELECT npm FROM vote_voters WHERE id = ?');
                $stmt->execute([$voterId]);
                $blocked = ['type' => $result, 'npm' => (string)$stmt->fetchColumn()];
            } else {
                $error = $result;
            }
        }
    }
}

// Peserta yang sudah lolos cek NPM dan sedang di layar surat suara
$sessionVoter = null;
if (!$blocked && !empty($_SESSION['vote_voter_id'])) {
    $stmt = $pdo->prepare('SELECT * FROM vote_voters WHERE id = ?');
    $stmt->execute([(int)$_SESSION['vote_voter_id']]);
    $sessionVoter = $stmt->fetch() ?: null;
    if (!$sessionVoter || (int)$sessionVoter['has_voted'] === 1) {
        unset($_SESSION['vote_voter_id']);
        $sessionVoter = null;
    }
}

$justVoted = !empty($_SESSION['vote_done']);
unset($_SESSION['vote_done']);

// Link WA ke panitia kalau NPM ditolak (mungkin dipakai orang lain / pinjam HP teman)
$waLink = null;
if ($blocked) {
    $base = buildWhatsappLink($settings['panitia_wa']);
    if ($base) {
        $text = [
            'perangkat'       => 'Halo panitia, saya NPM ' . $blocked['npm'] . '. Saya mau memilih tapi ditolak karena perangkat ini sudah dipakai memilih (saya meminjam HP orang lain). Mohon diizinkan.',
            'sudah'           => 'Halo panitia, saya NPM ' . $blocked['npm'] . '. Di web voting NPM saya tercatat sudah memilih, padahal saya belum memilih. Mohon dicek.',
            'tidak_terdaftar' => 'Halo panitia, saya NPM ' . $blocked['npm'] . '. NPM saya tidak terdaftar di web voting, padahal saya anggota HMSE. Mohon dicek.',
        ][$blocked['type']];
        $waLink = $base . '?text=' . rawurlencode($text);
    }
}

// Hasil (cuma kalau voting sudah tutup DAN admin sudah mengumumkan)
$showResults = $status === 'closed' && (int)$settings['results_public'] === 1;
if ($showResults) {
    $tally = votingTally($pdo, $options);
    $totalVotes = array_sum($tally);
    $maxVotes = $tally ? max($tally) : 0;
    $participation = votingParticipation($pdo);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Mubes - HMSE</title>
    <link rel="icon" type="image/png" href="images/logo2.png">
    <link rel="apple-touch-icon" href="images/logo2.png">
    <link rel="stylesheet" href="css/style.css?v=<?= assetVersion('css/style.css') ?>">
    <link rel="stylesheet" href="css/voting.css?v=<?= assetVersion('css/voting.css') ?>">
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
    <section class="vt-banner">
        <div class="vt-banner-glow"></div>
        <div class="vt-banner-content">
            <span class="section-eyebrow section-eyebrow-light">Musyawarah Besar</span>
            <h1><?= e($settings['title']) ?></h1>
            <p>
                <span class="vt-status vt-status-<?= e($status) ?>"><?= e(votingStatusLabel($status)) ?></span>
                <?php if ($settings['start_at'] && $settings['end_at']): ?>
                    <span class="vt-schedule"><?= e(formatWaktu($settings['start_at'])) ?> - <?= e(date('H:i', strtotime($settings['end_at']))) ?> WIB</span>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="vt-section">
        <div class="vt-wrap">

        <?php if ($error): ?>
            <div class="vt-alert vt-alert-error"><span class="material-icons">error_outline</span><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($justVoted): ?>
            <!-- ===== Terima kasih ===== -->
            <div class="vt-card vt-center">
                <div class="vt-icon vt-icon-success"><span class="material-icons">how_to_vote</span></div>
                <h2>Suara kamu sudah tercatat</h2>
                <p>Terima kasih sudah menggunakan hak suara. Pilihan kamu bersifat rahasia, panitia pun tidak bisa melihat siapa memilih siapa.</p>
                <?php if ($status === 'open'): ?>
                    <a class="vt-btn vt-btn-ghost" href="voting.php">Selesai</a>
                <?php endif; ?>
            </div>

        <?php elseif ($blocked && $blocked['type'] === 'perangkat'): ?>
            <!-- ===== Perangkat sudah dipakai NPM lain ===== -->
            <div class="vt-card vt-center">
                <div class="vt-icon vt-icon-warn"><span class="material-icons">phonelink_lock</span></div>
                <h2>Perangkat ini sudah dipakai memilih</h2>
                <p>Satu perangkat hanya bisa dipakai untuk satu pemilih. Silakan memilih dari HP kamu sendiri.</p>
                <div class="vt-actions">
                    <?php if ($waLink): ?>
                        <a class="vt-btn vt-btn-wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Hubungi Panitia</a>
                    <?php endif; ?>
                    <a class="vt-btn vt-btn-ghost" href="voting.php">Kembali</a>
                </div>
            </div>

        <?php elseif ($blocked && $blocked['type'] === 'tidak_terdaftar'): ?>
            <!-- ===== NPM tidak terdaftar ===== -->
            <div class="vt-card vt-center">
                <div class="vt-icon vt-icon-warn"><span class="material-icons">person_off</span></div>
                <h2>NPM tidak terdaftar</h2>
                <p>NPM <strong><?= e($blocked['npm']) ?></strong> tidak ada di daftar peserta voting.</p>
                <div class="vt-actions">
                    <?php if ($waLink): ?>
                        <a class="vt-btn vt-btn-wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Hubungi Panitia</a>
                    <?php endif; ?>
                    <a class="vt-btn vt-btn-ghost" href="voting.php">Coba lagi</a>
                </div>
            </div>

        <?php elseif ($blocked && $blocked['type'] === 'sudah'): ?>
            <!-- ===== NPM sudah memilih (nama & jam sengaja tidak ditampilkan) ===== -->
            <div class="vt-card vt-center">
                <div class="vt-icon vt-icon-warn"><span class="material-icons">block</span></div>
                <h2>NPM ini sudah menggunakan hak suara</h2>
                <p>NPM <strong><?= e($blocked['npm']) ?></strong> tercatat sudah memilih.</p>
                <div class="vt-actions">
                    <?php if ($waLink): ?>
                        <a class="vt-btn vt-btn-wa" href="<?= e($waLink) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Hubungi Panitia</a>
                    <?php endif; ?>
                    <a class="vt-btn vt-btn-ghost" href="voting.php">Kembali</a>
                </div>
            </div>

        <?php elseif ($status === 'open' && $sessionVoter): ?>
            <!-- ===== Surat suara ===== -->
            <div class="vt-voter-bar">
                <div>
                    <span class="vt-muted vt-small">Memilih sebagai</span>
                    <strong><?= e(votingMaskName($sessionVoter['name'])) ?></strong> <span class="vt-muted">&middot; <?= e($sessionVoter['npm']) ?></span>
                </div>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="ganti_npm">
                    <button type="submit" class="vt-link">Bukan kamu? Ganti NPM</button>
                </form>
            </div>

            <?php if (!$options): ?>
                <div class="vt-card vt-center"><p>Belum ada pasangan calon. Hubungi panitia.</p></div>
            <?php else: ?>
            <form method="POST" id="ballotForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="kirim_suara">

                <h2 class="vt-heading">Pilih salah satu</h2>
                <div class="vt-grid vt-grid-<?= count($options) ?>">
                    <?php foreach ($options as $o):
                        $cand = null;
                        foreach ($candidates as $c) if ((string)$c['id'] === $o['key']) $cand = $c; ?>
                        <div class="paslon-card<?= $o['kosong'] ? ' paslon-kosong' : '' ?>">
                            <label class="paslon-pick">
                                <input type="radio" name="choice" value="<?= e($o['key']) ?>" data-label="<?= e($o['label']) ?>" data-names="<?= e($o['names']) ?>" required>
                                <div class="paslon-photo">
                                    <?php if ($o['kosong']): ?>
                                        <span class="material-icons">check_box_outline_blank</span>
                                    <?php elseif ($o['photo']): ?>
                                        <img src="images/<?= e($o['photo']) ?>" alt="Foto <?= e($o['label']) ?>">
                                    <?php else: ?>
                                        <span class="material-icons">groups</span>
                                    <?php endif; ?>
                                    <?php if (!$o['kosong']): ?>
                                        <span class="paslon-no"><?= sprintf('%02d', $o['number']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="paslon-body">
                                    <?php if ($cand): ?>
                                        <div class="paslon-person"><span>Ketua</span><strong><?= e($cand['ketua_name']) ?></strong><?php if ($cand['ketua_npm']): ?><em><?= e($cand['ketua_npm']) ?></em><?php endif; ?></div>
                                        <?php if (votingHasWakil($cand)): ?>
                                            <div class="paslon-person"><span>Wakil</span><strong><?= e($cand['wakil_name']) ?></strong><?php if ($cand['wakil_npm']): ?><em><?= e($cand['wakil_npm']) ?></em><?php endif; ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="paslon-person"><strong>Kotak Kosong</strong><em><?= e($o['names']) ?></em></div>
                                    <?php endif; ?>
                                </div>
                                <span class="paslon-check"><span class="pc-idle">Pilih</span><span class="pc-on"><span class="material-icons">check_circle</span> Dipilih</span></span>
                            </label>
                            <?php if ($cand && ($cand['visi'] || $cand['misi'])): ?>
                                <details class="paslon-detail">
                                    <summary>Lihat visi &amp; misi</summary>
                                    <?php if ($cand['visi']): ?><h4>Visi</h4><p><?= nl2br(e($cand['visi'])) ?></p><?php endif; ?>
                                    <?php if ($cand['misi']): ?><h4>Misi</h4><p><?= nl2br(e($cand['misi'])) ?></p><?php endif; ?>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="vt-submit-row">
                    <button type="submit" class="vt-btn vt-btn-primary" id="ballotSubmit" disabled>Kirim Suara</button>
                    <p class="vt-muted vt-small">Suara yang sudah dikirim tidak bisa diubah.</p>
                </div>
            </form>

            <!-- Konfirmasi sebelum kirim -->
            <div class="vt-modal" id="confirmModal" hidden>
                <div class="vt-modal-box" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                    <span class="material-icons vt-modal-icon">how_to_vote</span>
                    <h3 id="confirmTitle">Yakin dengan pilihanmu?</h3>
                    <p>Kamu memilih <strong id="confirmLabel"></strong><br><span id="confirmNames" class="vt-muted"></span></p>
                    <p class="vt-muted vt-small">Setelah dikirim, suara tidak bisa diubah.</p>
                    <div class="vt-actions">
                        <button type="button" class="vt-btn vt-btn-ghost" id="confirmBack">Kembali</button>
                        <button type="button" class="vt-btn vt-btn-primary" id="confirmSend">Kirim Suara</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($status === 'open'): ?>
            <!-- ===== Masukkan NPM ===== -->
            <div class="vt-card vt-narrow">
                <div class="vt-icon"><span class="material-icons">badge</span></div>
                <h2>Masukkan NPM kamu</h2>
                <p class="vt-muted">Hanya anggota HMSE yang terdaftar yang bisa memilih. Satu NPM, satu suara.</p>
                <form method="POST" class="vt-npm-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="cek_npm">
                    <input type="text" name="npm" inputmode="numeric" autocomplete="off" placeholder="Contoh: 2411010001" value="<?= e(is_string($_POST['npm'] ?? null) ? $_POST['npm'] : '') ?>" required autofocus>
                    <button type="submit" class="vt-btn vt-btn-primary">Lanjut</button>
                </form>
            </div>

        <?php elseif ($showResults): ?>
            <!-- ===== Hasil ===== -->
            <div class="vt-card">
                <h2 class="vt-heading vt-heading-tight">Hasil Pemilihan</h2>
                <p class="vt-muted"><?= $totalVotes ?> suara masuk dari <?= $participation['total'] ?> peserta
                    (partisipasi <?= $participation['total'] ? round($participation['voted'] / $participation['total'] * 100) : 0 ?>%).</p>
                <div class="vt-results">
                    <?php foreach ($options as $o):
                        $n = $tally[$o['key']];
                        $pct = $totalVotes ? round($n / $totalVotes * 100, 1) : 0;
                        $win = $n > 0 && $n === $maxVotes; ?>
                        <div class="vt-result<?= $win ? ' is-winner' : '' ?>">
                            <div class="vt-result-photo">
                                <?php if ($o['photo']): ?><img src="images/<?= e($o['photo']) ?>" alt=""><?php else: ?><span class="material-icons"><?= $o['kosong'] ? 'check_box_outline_blank' : 'groups' ?></span><?php endif; ?>
                            </div>
                            <div class="vt-result-main">
                                <div class="vt-result-top">
                                    <strong><?= e($o['label']) ?></strong>
                                    <span><?= $n ?> suara &middot; <?= $pct ?>%</span>
                                </div>
                                <div class="vt-result-names"><?= e($o['names']) ?></div>
                                <div class="vt-bar"><span style="width:<?= $pct ?>%"></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- ===== Belum buka / sudah tutup ===== -->
            <div class="vt-card vt-center">
                <div class="vt-icon"><span class="material-icons"><?= $status === 'closed' ? 'lock' : 'schedule' ?></span></div>
                <?php if ($status === 'closed'): ?>
                    <h2>Voting telah ditutup</h2>
                    <p class="vt-muted">Hasil akan diumumkan setelah penghitungan suara di forum.</p>
                <?php else: ?>
                    <h2>Voting belum dibuka</h2>
                    <p class="vt-muted">
                        <?php if ($settings['start_at']): ?>
                            Voting dibuka <?= e(formatWaktu($settings['start_at'])) ?> WIB.
                        <?php else: ?>
                            Jadwal voting akan diumumkan panitia.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        </div>
    </section>

    <?php include __DIR__ . '/admin/includes/footer.html'; ?>

    <script src="js/index.js?v=<?= assetVersion('js/index.js') ?>"></script>
    <script src="js/voting.js?v=<?= assetVersion('js/voting.js') ?>"></script>

</body>
</html>
