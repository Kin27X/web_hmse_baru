<?php
/**
 * voting-hitung.php - layar proyektor penghitungan suara.
 * Surat suara dibuka satu per satu (urutan diacak), lalu dicatat sebagai
 * turus/lidi di papan tiap paslon, seperti hitung suara manual pakai kertas.
 * Cuma bisa dibuka setelah voting ditutup.
 */
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$state = votingState($pdo);
$settings = $state['settings'];
$isClosed = $state['status'] === 'closed';
$options = votingOptions(votingCandidates($pdo));

$ballots = [];
if ($isClosed) {
    foreach ($pdo->query('SELECT candidate_id FROM vote_ballots') as $row) {
        $key = $row['candidate_id'] === null ? 'kosong' : (string)$row['candidate_id'];
        if (in_array($key, array_column($options, 'key'), true)) $ballots[] = $key;
    }
    shuffle($ballots);
}

$data = [
    'options'   => array_map(fn($o) => [
        'key'    => $o['key'],
        'label'  => $o['label'],
        'names'  => $o['names'],
        'number' => $o['number'],
        'kosong' => $o['kosong'],
        'photo'  => $o['photo'] ? '../images/' . $o['photo'] : null,
    ], $options),
    'ballots'   => $ballots,
    'published' => (int)$settings['results_public'] === 1,
];
$cols = max(1, count($options));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penghitungan Suara - HMSE</title>
    <link rel="icon" type="image/png" href="../images/logo2.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="voting-show.css?v=<?= assetVersion('admin/voting-show.css') ?>">
</head>
<body class="show">

    <header class="show-head">
        <img src="../images/logo2.png" alt="logo HMSE">
        <div>
            <span class="show-eyebrow">Penghitungan Suara</span>
            <h1><?= e($settings['title']) ?></h1>
        </div>
        <?php if ($isClosed && $ballots): ?>
            <div class="show-progress"><b id="progCur">0</b><span>/<?= count($ballots) ?></span><small>surat suara</small></div>
        <?php endif; ?>
    </header>

<?php if (!$isClosed): ?>
    <main class="show-message">
        <span class="material-icons">lock_clock</span>
        <h2>Voting masih berlangsung</h2>
        <p>Penghitungan suara baru bisa dimulai setelah voting ditutup.</p>
        <a class="show-btn" href="voting.php">Kembali ke admin</a>
    </main>
<?php elseif (!$ballots): ?>
    <main class="show-message">
        <span class="material-icons">inbox</span>
        <h2>Belum ada suara</h2>
        <p>Kotak suara kosong.</p>
        <a class="show-btn" href="voting.php">Kembali ke admin</a>
    </main>
<?php else: ?>
    <main class="count-stage">

        <!-- Kotak suara + surat suara yang sedang dibuka -->
        <section class="ballot-zone">
            <div class="ballot-slot">
                <div class="ballot" id="ballot">
                    <div class="ballot-sheet">
                        <div class="ballot-sheet-head">
                            <img src="../images/logo2.png" alt="">
                            <div><b>SURAT SUARA</b><span><?= e($settings['title']) ?></span></div>
                        </div>
                        <div class="ballot-options" style="--bcols:<?= min($cols, 3) ?>">
                            <?php foreach ($data['options'] as $o): ?>
                                <div class="ballot-opt" data-key="<?= e($o['key']) ?>">
                                    <div class="ballot-opt-photo">
                                        <?php if ($o['photo']): ?><img src="<?= e($o['photo']) ?>" alt=""><?php else: ?><span class="material-icons"><?= $o['kosong'] ? 'check_box_outline_blank' : 'groups' ?></span><?php endif; ?>
                                        <span class="ballot-hole"></span>
                                    </div>
                                    <b><?= $o['kosong'] ? 'KOTAK KOSONG' : sprintf('%02d', $o['number']) ?></b>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ballot-stamp">SAH</div>
                    </div>
                    <div class="ballot-flap ballot-flap-top"><span>HMSE</span></div>
                    <div class="ballot-flap ballot-flap-bottom"><span>SURAT SUARA</span></div>
                </div>
            </div>
            <div class="ballot-box">
                <div class="ballot-box-lid"></div>
                <div class="ballot-box-body">
                    <span class="material-icons">how_to_vote</span>
                    <b id="boxLeft"><?= count($ballots) ?></b>
                    <small>surat suara tersisa</small>
                </div>
            </div>
            <div class="announce" id="announce" aria-live="polite"></div>
        </section>

        <!-- Papan turus per paslon -->
        <section class="board" style="--cols:<?= $cols ?>">
            <?php foreach ($data['options'] as $o): ?>
                <div class="board-col<?= $o['kosong'] ? ' is-kosong' : '' ?>" data-key="<?= e($o['key']) ?>">
                    <div class="board-photo">
                        <?php if ($o['photo']): ?><img src="<?= e($o['photo']) ?>" alt=""><?php else: ?><span class="material-icons"><?= $o['kosong'] ? 'check_box_outline_blank' : 'groups' ?></span><?php endif; ?>
                        <?php if (!$o['kosong']): ?><span class="board-no"><?= sprintf('%02d', $o['number']) ?></span><?php endif; ?>
                    </div>
                    <div class="board-names"><?= e($o['kosong'] ? 'Kotak Kosong' : $o['names']) ?></div>
                    <div class="board-tally" aria-hidden="true"></div>
                    <div class="board-count">
                        <span class="board-num">0</span>
                        <span class="board-pct">0%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <footer class="show-controls">
        <button class="show-btn show-btn-primary" id="playBtn" type="button"><span class="material-icons">play_arrow</span><span>Buka surat suara pertama</span></button>
        <div class="speed" role="group" aria-label="Kecepatan">
            <button type="button" data-speed="1" class="active">1x</button>
            <button type="button" data-speed="2">2x</button>
            <button type="button" data-speed="4">4x</button>
        </div>
        <button class="show-btn" id="skipBtn" type="button"><span class="material-icons">skip_next</span><span>Lewati ke hasil</span></button>
        <button class="show-icon-btn" id="fsBtn" type="button" title="Layar penuh (F)"><span class="material-icons">fullscreen</span></button>
        <span class="show-keys">Spasi / panah kanan: buka surat suara berikutnya &middot; F: layar penuh</span>
    </footer>

    <!-- Hasil akhir -->
    <div class="final" id="final" hidden>
        <div class="final-box">
            <span class="show-eyebrow">Hasil Akhir</span>
            <h2 id="finalTitle"></h2>
            <p id="finalSub"></p>
            <div class="final-actions">
                <?php if ((int)$settings['results_public'] === 1): ?>
                    <span class="show-btn is-done"><span class="material-icons">check</span> Sudah diumumkan</span>
                <?php else: ?>
                    <form method="POST" action="voting.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="publish">
                        <button type="submit" class="show-btn show-btn-primary"><span class="material-icons">campaign</span> Umumkan ke publik</button>
                    </form>
                <?php endif; ?>
                <button type="button" class="show-btn" id="finalClose">Lihat papan</button>
                <button type="button" class="show-btn" onclick="location.reload()">Ulangi</button>
            </div>
        </div>
        <div class="confetti" id="confetti"></div>
    </div>

    <script>window.HITUNG = <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
    <script src="voting-hitung.js?v=<?= assetVersion('admin/voting-hitung.js') ?>"></script>
<?php endif; ?>

</body>
</html>
