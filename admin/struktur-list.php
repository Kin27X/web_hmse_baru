<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';

$pdo = getDbConnection();
$allNodes = $pdo->query('SELECT * FROM struktur_nodes ORDER BY sort_order ASC, id ASC')->fetchAll();

$leaders = [];
$branches = [];
$divisions = [];
$childrenByParent = [];

foreach ($allNodes as $node) {
    if ($node['parent_id']) {
        $childrenByParent[$node['parent_id']][] = $node;
    } elseif ($node['group_type'] === 'leader') {
        $leaders[] = $node;
    } elseif ($node['group_type'] === 'branch') {
        $branches[] = $node;
    } elseif ($node['group_type'] === 'division') {
        $divisions[] = $node;
    }
}

function renderGroupCard($group, $childrenByParent, $showDeleteHead = true) {
    $members = $childrenByParent[$group['id']] ?? [];
    echo '<div class="admin-card">';
    echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">';
    echo '<div><strong>' . e($group['name']) . '</strong> <span class="badge">' . e($group['label']) . '</span></div>';
    echo '<div class="actions" style="display:flex; gap:8px;">';
    echo '<a class="btn btn-secondary btn-small" href="struktur-form.php?id=' . (int)$group['id'] . '">Edit</a>';
    if ($showDeleteHead) {
        echo '<form class="confirm-delete-form" method="POST" action="struktur-delete.php" onsubmit="return confirm(' . e(json_encode("Hapus grup '" . $group['name'] . "' beserta seluruh anggotanya?")) . ');">';
        echo csrfField();
        echo '<input type="hidden" name="id" value="' . (int)$group['id'] . '">';
        echo '<button type="submit" class="btn btn-danger btn-small">Hapus Grup</button>';
        echo '</form>';
    }
    echo '</div></div>';

    if ($members) {
        echo '<table class="admin-table" style="margin-top:10px;"><tbody>';
        foreach ($members as $m) {
            echo '<tr><td>' . e($m['name']) . '</td><td>' . e($m['label']) . '</td><td class="actions">';
            echo '<a class="btn btn-secondary btn-small" href="struktur-form.php?id=' . (int)$m['id'] . '">Edit</a>';
            echo '<form class="confirm-delete-form" method="POST" action="struktur-delete.php" onsubmit="return confirm(' . e(json_encode('Hapus ' . $m['name'] . '?')) . ');">';
            echo csrfField();
            echo '<input type="hidden" name="id" value="' . (int)$m['id'] . '">';
            echo '<button type="submit" class="btn btn-danger btn-small">Hapus</button>';
            echo '</form></td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="hint">Belum ada anggota.</p>';
    }

    echo '<div style="margin-top:12px;"><a class="btn btn-secondary btn-small" href="struktur-form.php?type=member&parent_id=' . (int)$group['id'] . '"><i class="material-icons" style="font-size:14px;">add</i> Tambah Anggota</a></div>';
    echo '</div>';
}

$pageTitle = 'Struktur Organisasi';
$currentNav = 'struktur';
require __DIR__ . '/includes/header.php';

?>

<div class="admin-title-row">
    <h2>Struktur Organisasi</h2>
    <a href="dashboard.php" class="btn btn-back">Kembali</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-success"><?= e($_GET['msg']) ?></div>
<?php endif; ?>

<h3 style="font-size:14px; text-transform:uppercase; letter-spacing:0.5px; color:rgba(23,21,51,0.5); margin:24px 0 10px;">Pimpinan</h3>
<?php foreach ($leaders as $l): ?>
    <div class="admin-card" style="display:flex; justify-content:space-between; align-items:center;">
        <div><strong><?= e($l['name']) ?></strong> <span class="badge"><?= e($l['label']) ?></span></div>
        <a class="btn btn-secondary btn-small" href="struktur-form.php?id=<?= (int)$l['id'] ?>">Edit</a>
    </div>
<?php endforeach; ?>

<h3 style="font-size:14px; text-transform:uppercase; letter-spacing:0.5px; color:rgba(23,21,51,0.5); margin:28px 0 10px; display:flex; justify-content:space-between; align-items:center;">
    <span>Cabang (Sekretaris / Bendahara)</span>
    <a class="btn btn-secondary btn-small" href="struktur-form.php?type=branch"><i class="material-icons" style="font-size:14px;">add</i> Cabang Baru</a>
</h3>
<?php foreach ($branches as $b): renderGroupCard($b, $childrenByParent); endforeach; ?>

<h3 style="font-size:14px; text-transform:uppercase; letter-spacing:0.5px; color:rgba(23,21,51,0.5); margin:28px 0 10px; display:flex; justify-content:space-between; align-items:center;">
    <span>Divisi</span>
    <a class="btn btn-secondary btn-small" href="struktur-form.php?type=division"><i class="material-icons" style="font-size:14px;">add</i> Divisi Baru</a>
</h3>
<?php foreach ($divisions as $d): renderGroupCard($d, $childrenByParent); endforeach; ?>