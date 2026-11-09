<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$state = votingState($pdo);
$settings = $state['settings'];
$p = votingParticipation($pdo);
$url = votingPublicUrl();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar QR Voting - HMSE</title>
    <link rel="icon" type="image/png" href="../images/logo2.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="voting-show.css?v=<?= assetVersion('admin/voting-show.css') ?>">
</head>
<body class="show">

    <header class="show-head">
        <img src="../images/logo2.png" alt="logo HMSE">
        <div>
            <span class="show-eyebrow">Musyawarah Besar HMSE</span>
            <h1><?= e($settings['title']) ?></h1>
        </div>
        <button class="show-icon-btn" id="fsBtn" type="button" title="Layar penuh (F)"><span class="material-icons">fullscreen</span></button>
    </header>

    <main class="qr-stage">
        <div class="qr-card">
            <div id="qr" class="qr-box" aria-label="QR code ke halaman voting"></div>
            <p class="qr-hint">Scan untuk memilih</p>
            <p class="qr-url"><?= e(preg_replace('#^https?://#', '', $url)) ?></p>
        </div>

        <div class="qr-live">
            <span class="show-status show-status-<?= e($state['status']) ?>" id="liveStatus"><?= e(votingStatusLabel($state['status'])) ?></span>
            <div class="qr-count"><span id="liveVoted"><?= $p['voted'] ?></span><small>/<span id="liveTotal"><?= $p['total'] ?></span></small></div>
            <p class="qr-count-label">peserta sudah memilih</p>
            <div class="qr-progress"><span id="liveBar" style="width:<?= $p['total'] ? round($p['voted'] / $p['total'] * 100) : 0 ?>%"></span></div>
            <p class="qr-schedule">
                <span class="material-icons">schedule</span>
                <?= e(formatWaktu($settings['start_at'])) ?> - <?= e($settings['end_at'] ? date('H:i', strtotime($settings['end_at'])) : '-') ?> WIB
            </p>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    new QRCode(document.getElementById('qr'), {
      text: <?= json_encode($url) ?>,
      width: 340, height: 340,
      colorDark: '#0b0a2e', colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M
    });

    const fs = () => document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen();
    document.getElementById('fsBtn').addEventListener('click', fs);
    document.addEventListener('keydown', e => { if(e.key === 'f' || e.key === 'F') fs(); });

    (function(){
      const voted = document.getElementById('liveVoted');
      const total = document.getElementById('liveTotal');
      const bar = document.getElementById('liveBar');
      const statusEl = document.getElementById('liveStatus');
      async function tick(){
        try{
          const res = await fetch('voting-api.php', {cache:'no-store'});
          if(!res.ok) return;
          const d = await res.json();
          if(voted.textContent !== String(d.voted)){
            voted.textContent = d.voted;
            voted.classList.remove('bump'); void voted.offsetWidth; voted.classList.add('bump');
          }
          total.textContent = d.total;
          bar.style.width = (d.total ? Math.round(d.voted / d.total * 100) : 0) + '%';
          statusEl.textContent = d.label;
          statusEl.className = 'show-status show-status-' + d.status;
        }catch(e){}
      }
      setInterval(tick, 4000);
    })();
    </script>
</body>
</html>
