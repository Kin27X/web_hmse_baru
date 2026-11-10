<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/voting-helpers.php';

$pdo = getDbConnection();
$state = votingState($pdo);
$linkAvailable = (int)$state['settings']['link_wiped'] === 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $msg = '';

    if (!csrfCheck()) {
        $error = 'Sesi kedaluwarsa, silakan ulangi.';
    } elseif ($action === 'add_bulk') {
        // Tiap baris: NPM lalu nama, dipisah tab (paste dari Excel), koma, titik koma, atau spasi.
        $added = 0; $skipped = 0; $invalid = [];
        $stmt = $pdo->prepare('INSERT IGNORE INTO vote_voters (npm, name) VALUES (?, ?)');
        foreach (preg_split('/\r\n|\r|\n/', $_POST['bulk'] ?? '') as $line) {
            if (trim($line) === '' || preg_match('/^\s*npm\b/i', $line)) continue; // baris kosong / header Excel
            if (!preg_match('/^\s*([0-9][0-9A-Za-z.\-]*)\s*[\t,;]?\s*(.+?)\s*$/u', $line, $m)) {
                $invalid[] = trim($line);
                continue;
            }
            $stmt->execute([normalizeNpm($m[1]), $m[2]]);
            $stmt->rowCount() ? $added++ : $skipped++;
        }
        $msg = "$added peserta ditambahkan.";
        if ($skipped) $msg .= " $skipped dilewati (NPM sudah ada).";
        if ($invalid) $msg .= ' Baris tidak dikenali: ' . implode(' | ', array_slice($invalid, 0, 5)) . (count($invalid) > 5 ? ' ...' : '');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM vote_voters WHERE id = ? AND has_voted = 0');
        $stmt->execute([(int)$_POST['id']]);
        $msg = $stmt->rowCount() ? 'Peserta dihapus.' : 'Peserta yang sudah memilih tidak bisa dihapus. Batalkan suaranya dulu.';
    } elseif ($action === 'cancel_vote') {
        $voterId = (int)$_POST['id'];
        if (!$linkAvailable) {
            $error = 'Voting sudah ditutup, sambungan NPM-suara sudah dihapus. Suara tidak bisa dibatalkan lagi.';
        } else {
            $npm = votingCancelVote($pdo, $voterId);
            if ($npm !== null) {
                $msg = "Suara NPM $npm dibatalkan. Peserta ini bisa memilih lagi.";
            } else {
                $error = 'Suara untuk peserta ini tidak ditemukan.';
            }
        }
    } elseif ($action === 'device_ok') {
        // Izinkan NPM ini memilih dari perangkat yang sudah dipakai orang lain (pinjam HP teman).
        $voterId = (int)$_POST['id'];
        $allow = (int)($_POST['allow'] ?? 0) === 1 ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE vote_voters SET device_ok = ? WHERE id = ?');
        $stmt->execute([$allow, $voterId]);
        $npmStmt = $pdo->prepare('SELECT npm FROM vote_voters WHERE id = ?');
        $npmStmt->execute([$voterId]);
        $npm = (string)$npmStmt->fetchColumn();
        votingLog($pdo, $voterId, $npm, $allow ? 'diizinkan_perangkat' : 'izin_perangkat_dicabut');
        $msg = $allow
            ? "NPM $npm sekarang boleh memilih dari perangkat yang sudah dipakai orang lain."
            : "Izin perangkat untuk NPM $npm dicabut.";
    }

    if (!$error) {
        header('Location: voting-peserta.php?msg=' . urlencode($msg));
        exit;
    }
}

$voters = $pdo->query('SELECT * FROM vote_voters ORDER BY name ASC')->fetchAll();
$votedCount = count(array_filter($voters, fn($v) => (int)$v['has_voted'] === 1));

$pageTitle = 'Peserta Voting';
$currentNav = 'voting';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="voting.php">&larr; Voting Mubes</a></div>
<div class="admin-title-row">
    <h2>Anggota HMSE <span class="badge"><?= $votedCount ?>/<?= count($voters) ?> sudah memilih</span></h2>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] !== ''): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add_bulk">
        <label for="bulk">Tambah peserta</label>
        <textarea id="bulk" name="bulk" rows="5" placeholder="2411010001	Budi Santoso&#10;2411010002, Siti Aminah" required></textarea>
        <div class="hint">Satu peserta per baris: NPM lalu nama. Bisa langsung copy 2 kolom (NPM, Nama) dari Excel lalu paste di sini. NPM yang sudah ada dilewati.</div>
        <div class="form-actions"><button type="submit" class="btn">Tambahkan</button></div>
    </form>
</div>

<?php if (!$voters): ?>
    <div class="admin-empty">Belum ada peserta.</div>
<?php else: ?>
    <input type="search" id="voterFilter" class="vote-filter" placeholder="Cari NPM atau nama...">
    <div style="overflow-x:auto;">
    <table class="admin-table" id="voterTable">
        <thead>
            <tr>
                <th class="sortable" data-col="0" data-type="text" title="Klik untuk urutkan">NPM <span class="sort-ind"></span></th>
                <th class="sortable" data-col="1" data-type="text" title="Klik untuk urutkan">Nama <span class="sort-ind">&#9650;</span></th>
                <th class="sortable" data-col="2" data-type="text" title="Klik untuk urutkan">Status <span class="sort-ind"></span></th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($voters as $v): $voted = (int)$v['has_voted'] === 1; ?>
            <tr>
                <td data-v="<?= e($v['npm']) ?>"><?= e($v['npm']) ?></td>
                <td data-v="<?= e(mb_strtolower($v['name'])) ?>"><?= e($v['name']) ?></td>
                <td data-v="<?= $voted ? '1 ' . e($v['voted_at']) : '0' ?>">
                    <?php if ($voted): ?>
                        <span class="badge">Sudah &middot; <?= e(date('H:i', strtotime($v['voted_at']))) ?></span>
                    <?php else: ?>
                        <span class="badge badge-muted">Belum</span>
                    <?php endif; ?>
                    <?php if ((int)$v['device_ok'] === 1): ?>
                        <span class="badge badge-warn" title="Boleh memilih dari perangkat yang sudah dipakai orang lain">Boleh pinjam HP</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if ($voted): ?>
                        <?php if ($linkAvailable): ?>
                            <form class="confirm-delete-form" method="POST" onsubmit="return confirm(<?= e(json_encode('Batalkan suara NPM ' . $v['npm'] . '? Suaranya dihapus (isi pilihannya tidak ditampilkan) dan peserta bisa memilih lagi.')) ?>);">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="cancel_vote">
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-small">Batalkan suara</button>
                            </form>
                        <?php else: ?>
                            <span class="vote-muted" style="font-size:12px;">Terkunci (voting ditutup)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <form class="confirm-delete-form" method="POST" onsubmit="return confirm(<?= e(json_encode((int)$v['device_ok'] === 1
                            ? 'Cabut izin pinjam HP untuk NPM ' . $v['npm'] . '?'
                            : 'Izinkan NPM ' . $v['npm'] . ' memilih dari perangkat yang sudah dipakai orang lain? Lakukan ini hanya setelah memastikan orangnya benar (mis. lewat chat WA dari nomornya).')) ?>);">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="device_ok">
                            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                            <input type="hidden" name="allow" value="<?= (int)$v['device_ok'] === 1 ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-secondary btn-small"><?= (int)$v['device_ok'] === 1 ? 'Cabut izin HP' : 'Izinkan pinjam HP' ?></button>
                        </form>
                        <form class="confirm-delete-form" method="POST" onsubmit="return confirm(<?= e(json_encode('Hapus peserta ' . $v['name'] . '?')) ?>);">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-small">Hapus</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <script>
    document.getElementById('voterFilter').addEventListener('input', function(){
      const q = this.value.toLowerCase().trim();
      document.querySelectorAll('#voterTable tbody tr').forEach(tr => {
        tr.hidden = q !== '' && !tr.textContent.toLowerCase().includes(q);
      });
    });

    // ===== Urutkan: klik judul kolom (klik lagi = balik arah) =====
    (function(){
      const table = document.getElementById('voterTable');
      const tbody = table.tBodies[0];
      const coll = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
      let cur = { col: 1, dir: 1 }; // awal: urut nama A-Z (sesuai urutan dari server)

      table.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', () => {
          const col = Number(th.dataset.col);
          cur = { col, dir: cur.col === col ? -cur.dir : 1 };
          const rows = Array.from(tbody.rows);
          rows.sort((a, b) => cur.dir * coll.compare(a.cells[col].dataset.v || '', b.cells[col].dataset.v || ''));
          rows.forEach(r => tbody.appendChild(r));
          table.querySelectorAll('th.sortable .sort-ind').forEach(s => s.textContent = '');
          th.querySelector('.sort-ind').textContent = cur.dir === 1 ? '▲' : '▼';
        });
      });
    })();

    // ===== Blok per kolom: tarik-seleksi cuma kena 1 kolom, copy jadi 1 nilai per baris =====
    (function(){
      const table = document.getElementById('voterTable');
      const style = document.createElement('style');
      document.head.appendChild(style);
      let selCol = null;

      table.tBodies[0].addEventListener('mousedown', e => {
        const td = e.target.closest('td');
        if (!td || e.target.closest('button, form, input, a')) return;
        selCol = td.cellIndex;
        if (td.classList.contains('actions')) { selCol = null; style.textContent = ''; return; }
        // Sel di kolom lain tidak bisa ikut terblok
        style.textContent = `#voterTable tbody td:not(:nth-child(${selCol + 1})){ user-select:none; -webkit-user-select:none; }
                             #voterTable tbody td:nth-child(${selCol + 1}){ user-select:text; }`;
      });

      // true kalau ada TEKS sel ini yang benar-benar terblok (bukan cuma ujung
      // blok yang "nyentuh" awal sel baris berikutnya).
      function cellSelected(range, cell) {
        if (!range.intersectsNode(cell)) return false;
        const c = document.createRange();
        c.selectNodeContents(cell);
        const o = document.createRange();
        if (range.compareBoundaryPoints(Range.START_TO_START, c) <= 0) o.setStart(c.startContainer, c.startOffset);
        else o.setStart(range.startContainer, range.startOffset);
        if (range.compareBoundaryPoints(Range.END_TO_END, c) >= 0) o.setEnd(c.endContainer, c.endOffset);
        else o.setEnd(range.endContainer, range.endOffset);
        return o.toString().trim() !== '';
      }

      document.addEventListener('copy', e => {
        const sel = window.getSelection();
        if (selCol === null || !sel.rangeCount || !table.contains(sel.anchorNode)) return;
        const range = sel.getRangeAt(0);
        const values = Array.from(table.tBodies[0].rows)
          .filter(r => !r.hidden && cellSelected(range, r.cells[selCol]))
          .map(r => r.cells[selCol].innerText.replace(/\s+/g, ' ').trim());
        if (values.length < 2) return; // 1 sel: biarkan copy biasa
        e.clipboardData.setData('text/plain', values.join('\n'));
        e.preventDefault();
      });
    })();
    </script>
<?php endif; ?>

</main>
</body>
</html>
