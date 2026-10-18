<?php
require __DIR__ . '/includes/auth.php';
requireLogin();
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/news-helpers.php';

$pdo = getDbConnection();
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$news = ['title' => '', 'event_datetime' => date('Y-m-d\TH:i'), 'author' => '', 'body_html' => ''];
$existingPhotos = [];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: news-list.php');
        exit;
    }
    $news = $found;
    $news['event_datetime'] = str_replace(' ', 'T', substr($found['event_datetime'], 0, 16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim((string) ($_POST['title'] ?? ''));
    $eventDatetime = trim((string) ($_POST['event_datetime'] ?? ''));
    $author = trim((string) ($_POST['author'] ?? ''));
    $bodyHtml = trim((string) ($_POST['body_html'] ?? ''));

    // Simpan apa yang baru diketik, supaya kalau ada error form tidak kembali ke data lama
    $news['title'] = $title;
    $news['event_datetime'] = $eventDatetime;
    $news['author'] = $author;
    $news['body_html'] = $bodyHtml;

    if (!newsCsrfValid()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($title === '' || $eventDatetime === '') {
        $error = 'Judul dan tanggal wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $eventDatetime)) {
        $error = 'Format tanggal & jam tidak valid.';
    } else {

        $eventDatetimeSql = str_replace('T', ' ', substr($eventDatetime, 0, 16)) . ':00';

        try {
            $pdo->beginTransaction();

            if ($id) {
                $stmt = $pdo->prepare('UPDATE news SET title=?, event_datetime=?, author=?, body_html=? WHERE id=?');
                $stmt->execute([$title, $eventDatetimeSql, $author, $bodyHtml, $id]);
                $newsId = $id;
            } else {
                $slug = makeSlug($title) . '-' . substr(uniqid(), -5);
                $stmt = $pdo->prepare('INSERT INTO news (slug, title, event_datetime, author, body_html) VALUES (?,?,?,?,?)');
                $stmt->execute([$slug, $title, $eventDatetimeSql, $author, $bodyHtml]);
                $newsId = (int) $pdo->lastInsertId();
            }

            // Tambahkan foto baru yang diupload (foto lama tidak disentuh --
            // dihapus lewat tombol "Hapus" pada masing-masing foto)
            $newPhotos = handleMultipleImageUploads('photos');
            if (!is_array($newPhotos)) {
                $newPhotos = [];
            }
            if ($newPhotos) {
                $stmt = $pdo->prepare('INSERT INTO news_photos (news_id, filename) VALUES (?,?)');
                foreach ($newPhotos as $filename) {
                    $stmt->execute([$newsId, $filename]);
                }
            }

            $pdo->commit();

            if ($newPhotos) {
                // balik ke form ini supaya foto yang baru diupload bisa langsung disisipkan ke teks
                newsFlashSet(count($newPhotos) . ' foto berhasil diunggah. Sekarang kamu bisa menyisipkannya ke dalam teks lewat bagian "Foto".');
                header('Location: news-form.php?id=' . $newsId);
            } else {
                newsFlashSet('Berita berhasil disimpan.');
                header('Location: news-list.php');
            }
            exit;

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('news-form: ' . $ex->getMessage());
            $error = 'Gagal menyimpan berita. Coba lagi.';
        }
    }
}

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM news_photos WHERE news_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$id]);
    $existingPhotos = $stmt->fetchAll();
}

$flash = newsFlashPull();

$pageTitle = $id ? 'Edit Berita' : 'Berita Baru';
$currentNav = 'news';
require __DIR__ . '/includes/header.php';
?>

<div class="admin-breadcrumb"><a href="news-list.php">&larr; Kembali</a></div>
<div class="admin-title-row"><h2><?= $id ? 'Edit Berita' : 'Berita Baru' ?></h2></div>

<?php if ($flash): ?><div class="flash flash-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>"><?= newsE($flash['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= newsE($error) ?></div><?php endif; ?>

<div class="admin-card">
    <form class="admin-form" id="newsForm" method="POST" enctype="multipart/form-data">
        <?= newsCsrfField() ?>

        <label for="title">Judul</label>
        <input type="text" id="title" name="title" value="<?= newsE($news['title']) ?>" required>

        <div class="field-row">
            <div>
                <label for="event_datetime">Tanggal &amp; Jam</label>
                <input type="datetime-local" id="event_datetime" name="event_datetime" value="<?= newsE($news['event_datetime']) ?>" required>
            </div>
            <div>
                <label for="author">Author</label>
                <input type="text" id="author" name="author" value="<?= newsE($news['author']) ?>" placeholder="opsional">
            </div>
        </div>

        <label for="body_html">Isi Berita</label>
        <textarea id="body_html" name="body_html" rows="14" placeholder="Tulis isi berita di sini. Baris kosong = paragraf baru."><?= newsE($news['body_html']) ?></textarea>
        <p class="hint">
            Tulis biasa saja &mdash; satu baris kosong membuat paragraf baru. HTML sederhana juga boleh
            (&lt;strong&gt;, &lt;em&gt;, &lt;h3&gt;, &lt;ul&gt;&lt;li&gt;, &lt;a href="..."&gt;); tag lain otomatis dibuang demi keamanan.
            Untuk menaruh foto di tengah teks, letakkan kursor di posisi yang diinginkan lalu klik <strong>"Sisipkan ke teks"</strong> pada foto di bawah.
            Klik fotonya untuk mengatur ukuran dan posisinya.
            <br>
            khusus buat yang si h1 itu udh otomatis dari JUDUL
        </p>

        <label>Foto</label>
        <?php if ($existingPhotos): ?>
            <div id="photoPanel" style="display:flex; flex-wrap:wrap; gap:14px; margin-bottom:8px;">
                <?php foreach ($existingPhotos as $i => $ph): ?>
                    <div data-photo-id="<?= (int) $ph['id'] ?>" data-label="Foto <?= $i + 1 ?>" style="width:140px; text-align:center;">
                        <div class="photo-thumb" tabindex="0" role="button" title="Klik untuk mengatur ukuran foto ini" style="width:140px; height:96px; border-radius:10px; overflow:hidden; cursor:pointer;">
                            <img src="../images/<?= newsE(rawurlencode($ph['filename'])) ?>" style="width:100%; height:100%; object-fit:cover; display:block;" alt="">
                        </div>
                        <div style="font-size:12px; margin:5px 0;">
                            Foto <?= $i + 1 ?>
                            <span class="photo-used" hidden style="color:#16a34a;">&check; di teks</span>
                        </div>
                        <button type="button" class="btn btn-secondary btn-small" data-insert="<?= (int) $ph['id'] ?>" style="margin-bottom:4px;">Sisipkan ke teks</button>
                        <button type="button" class="btn btn-danger btn-small" data-delete="<?= (int) $ph['id'] ?>">Hapus</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Editor ukuran foto: muncul saat sebuah foto diklik -->
            <div class="se" id="sizeEditor" hidden>
                <div class="se-head">
                    <span id="seTitle">Atur ukuran foto</span>
                    <button type="button" class="se-close" id="seClose" aria-label="Tutup">&times;</button>
                </div>
                <div class="se-frame" id="seFrame">
                    <div class="se-stage" id="seStage">
                        <img id="seImg" alt="" draggable="false">
                        <span class="se-badge" id="seBadge">100%</span>
                        <span class="se-handle is-right" id="seHandle" tabindex="0" role="slider" aria-label="Ubah ukuran foto" title="Tarik untuk mengubah ukuran"></span>
                    </div>
                </div>
                <div class="se-actions">
                    <button type="button" class="btn btn-secondary btn-small" data-se="full">Penuh</button>
                    <button type="button" class="btn btn-secondary btn-small" data-se="left">Kiri</button>
                    <button type="button" class="btn btn-secondary btn-small" data-se="center">Tengah</button>
                    <button type="button" class="btn btn-secondary btn-small" data-se="right">Kanan</button>
                </div>
                <p class="se-note">
                    Garis putus-putus = batas lebar maksimal di halaman berita. Tarik kotak kecil di sudut foto untuk
                    memperbesar/memperkecil (proporsi tetap). Ukuran tersimpan di teks berita, jadi jangan lupa klik <strong>Simpan</strong>.
                    Foto yang belum disisipkan ke teks: ukuran ini dipakai saat kamu klik "Sisipkan ke teks".
                </p>
            </div>

            <p class="hint">
                Foto yang disisipkan muncul tepat di posisinya di dalam teks. Foto yang <em>tidak</em> disisipkan:
                yang pertama jadi foto utama di atas artikel, sisanya jadi galeri di bawah teks.
                Kalau belum ada foto yang disisipkan sama sekali, tampilan seperti sebelumnya (foto utama + deretan thumbnail).
            </p>
        <?php else: ?>
            <p class="hint">
                <?= $id
                    ? 'Belum ada foto untuk berita ini.'
                    : 'Foto yang diunggah bisa disisipkan ke dalam teks setelah berita disimpan (kamu otomatis dibawa kembali ke halaman ini).' ?>
            </p>
        <?php endif; ?>

        <label for="photos"><?= $existingPhotos ? 'Tambah Foto Lagi' : 'Unggah Foto (bisa pilih lebih dari satu)' ?></label>
        <input type="file" id="photos" name="photos[]" accept="image/*" multiple>

        <div class="form-actions">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-secondary" href="news-list.php">Batal</a>
        </div>
    </form>
</div>

<style>
    .photo-thumb.is-selected{ outline:2px solid #9ca3af; outline-offset:3px; }
    .photo-thumb:focus-visible{ outline:2px solid #6b7280; outline-offset:3px; }

    .se{ border:1px solid #e3e6ee; border-radius:14px; padding:14px; margin:6px 0 12px; background:#fafbfd; max-width:700px; }
    .se[hidden]{ display:none; }
    .se-head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; font-weight:600; font-size:14px; }
    .se-close{ border:0; background:#eceef5; width:28px; height:28px; border-radius:50%; cursor:pointer; font-size:18px; line-height:1; }
    .se-close:hover{ background:#dfe2ee; }

    .se-frame{ border:1px dashed #9ca3af; border-radius:6px; background:#fff; box-sizing:border-box; width:100%; }
    .se-stage{ position:relative; line-height:0; }
    .se-stage img{ display:block; width:100%; height:auto; user-select:none; -webkit-user-drag:none; pointer-events:none; border-radius:4px; }
    .se-badge{ position:absolute; top:6px; left:6px; background:rgba(17,24,39,.72); color:#fff; font-size:12px; line-height:1; padding:4px 8px; border-radius:999px; pointer-events:none; }
    .se-handle{ position:absolute; bottom:6px; width:18px; height:18px; border-radius:5px; background:#fff; border:2px solid #4b5563; box-shadow:0 1px 4px rgba(0,0,0,.35); touch-action:none; }
    .se-handle.is-right{ right:6px; cursor:nwse-resize; }
    .se-handle.is-left{ left:6px; cursor:nesw-resize; }

    .se-actions{ display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
    .se-actions .btn.is-active{ outline:2px solid #6b7280; outline-offset:1px; }
    .se-note{ font-size:12px; color:#7a8296; margin:10px 0 0; line-height:1.5; }
</style>

<script>
(function () {
    var form = document.getElementById('newsForm');
    var ta = document.getElementById('body_html');
    var csrf = form.querySelector('[name="csrf"]').value;
    var newsId = <?= (int) $id ?>;

    var editor = document.getElementById('sizeEditor');
    var seFrame = document.getElementById('seFrame');
    var seStage = document.getElementById('seStage');
    var seImg = document.getElementById('seImg');
    var seBadge = document.getElementById('seBadge');
    var seHandle = document.getElementById('seHandle');
    var seTitle = document.getElementById('seTitle');

    var sizes = {};        // id foto -> {w: 10..100, a: 'left'|'center'|'right'} (ingatan untuk foto yang belum ada di teks)
    var selectedId = null;

    /* ---------- token [foto:ID|lebar|rata] ---------- */
    function tokenRegex(id) {
        return new RegExp('\\[foto:' + id + '(?:\\|[^\\]]*)?\\]', 'gi');
    }
    function clamp(n) { return Math.max(10, Math.min(100, Math.round(n))); }

    function tokenFor(id, s) {
        return s.w >= 100 ? '[foto:' + id + ']' : '[foto:' + id + '|' + s.w + '|' + s.a + ']';
    }

    // baca ukuran dari token pertama foto ini di dalam teks (null kalau belum ada)
    function readFromText(id) {
        var m = tokenRegex(id).exec(ta.value);
        if (!m) return null;
        var bits = m[0].slice(1, -1).split('|'); // ['foto:22','60','center']
        var s = { w: 100, a: 'left' };
        for (var i = 1; i < bits.length; i++) {
            var b = bits[i].toLowerCase();
            if (/^\d+$/.test(b)) s.w = clamp(parseInt(b, 10));
            else if (/^(left|center|right)$/.test(b)) s.a = b;
        }
        return s;
    }

    function getSize(id) {
        var t = readFromText(id);
        if (t) sizes[id] = t;
        if (!sizes[id]) sizes[id] = { w: 100, a: 'left' };
        return sizes[id];
    }

    // tulis ulang SEMUA token foto ini di teks; posisi kursor di textarea dijaga
    function rewriteTokens(id, token) {
        var text = ta.value, re = tokenRegex(id), m, out = '', last = 0, found = false;
        var s = ta.selectionStart, e = ta.selectionEnd, ds = 0, de = 0;
        while ((m = re.exec(text)) !== null) {
            found = true;
            out += text.slice(last, m.index) + token;
            var end = m.index + m[0].length, diff = token.length - m[0].length;
            if (end <= s) ds += diff;
            if (end <= e) de += diff;
            last = end;
        }
        if (!found) return false;
        ta.value = out + text.slice(last);
        try { ta.setSelectionRange(s + ds, e + de); } catch (err) {}
        return true;
    }

    function insertAtCursor(text) {
        var s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.slice(0, s) + text + ta.value.slice(e);
        var pos = s + text.length;
        ta.focus();
        ta.setSelectionRange(pos, pos);
    }

    /* ---------- badge "di teks" ---------- */
    function refreshBadges() {
        document.querySelectorAll('[data-photo-id]').forEach(function (item) {
            var badge = item.querySelector('.photo-used');
            if (!badge) return;
            var t = readFromText(item.dataset.photoId);
            badge.hidden = !t;
            if (t) badge.innerHTML = '&check; di teks' + (t.w < 100 ? ' &middot; ' + t.w + '%' : '');
        });
    }

    /* ---------- editor ukuran ---------- */
    function render(s) {
        seStage.style.width = s.w + '%';
        seStage.style.marginLeft = s.a === 'left' ? '0' : 'auto';
        seStage.style.marginRight = s.a === 'right' ? '0' : 'auto';
        seHandle.className = 'se-handle ' + (s.a === 'right' ? 'is-left' : 'is-right');
        seBadge.textContent = s.w + '%';
        seHandle.setAttribute('aria-valuenow', s.w);
        editor.querySelectorAll('[data-se]').forEach(function (b) {
            var k = b.dataset.se;
            b.classList.toggle('is-active', k === 'full' ? s.w >= 100 : (s.w < 100 && k === s.a));
        });
    }

    function commit(s) {
        sizes[selectedId] = s;
        render(s);
        rewriteTokens(selectedId, tokenFor(selectedId, s));
        refreshBadges();
    }

    function selectPhoto(id) {
        id = String(id);
        var item = document.querySelector('[data-photo-id="' + id + '"]');
        if (!item) return;
        selectedId = id;
        document.querySelectorAll('.photo-thumb').forEach(function (t) {
            t.classList.toggle('is-selected', t.parentNode === item);
        });
        seImg.src = item.querySelector('img').src;
        seTitle.textContent = 'Atur ukuran \u2014 ' + (item.dataset.label || 'Foto');
        editor.hidden = false;
        var s = getSize(id);
        render({ w: s.w, a: s.a });
        if (editor.scrollIntoView) editor.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function closeEditor() {
        selectedId = null;
        editor.hidden = true;
        document.querySelectorAll('.photo-thumb').forEach(function (t) { t.classList.remove('is-selected'); });
    }

    document.querySelectorAll('.photo-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () { selectPhoto(thumb.parentNode.dataset.photoId); });
        thumb.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); selectPhoto(thumb.parentNode.dataset.photoId); }
        });
    });
    document.getElementById('seClose').addEventListener('click', closeEditor);

    // tombol Penuh / Kiri / Tengah / Kanan
    editor.querySelectorAll('[data-se]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (selectedId === null) return;
            var cur = getSize(selectedId), k = btn.dataset.se;
            commit(k === 'full' ? { w: 100, a: cur.a } : { w: cur.w, a: k });
        });
    });

    // tarik kotak di sudut untuk mengubah ukuran (proporsional: yang berubah hanya lebar, tinggi mengikuti)
    var dragging = false;

    function widthFromPointer(clientX, align) {
        var r = seFrame.getBoundingClientRect();
        var left = r.left + seFrame.clientLeft, fw = seFrame.clientWidth;
        var x = clientX - left, w;
        if (align === 'right') w = (fw - x) / fw * 100;
        else if (align === 'center') w = 2 * (x - fw / 2) / fw * 100;
        else w = x / fw * 100;
        w = clamp(w);
        return w >= 96 ? 100 : w; // dekat batas -> "menempel" ke penuh
    }

    function onMove(ev) {
        if (!dragging || selectedId === null) return;
        var cur = getSize(selectedId);
        commit({ w: widthFromPointer(ev.clientX, cur.a), a: cur.a });
    }
    function onUp() {
        dragging = false;
        document.removeEventListener('pointermove', onMove);
        document.removeEventListener('pointerup', onUp);
        document.removeEventListener('pointercancel', onUp);
    }
    seHandle.addEventListener('pointerdown', function (ev) {
        if (selectedId === null) return;
        ev.preventDefault();
        dragging = true;
        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
        document.addEventListener('pointercancel', onUp);
    });
    // alternatif keyboard: panah kiri/kanan mengubah ukuran 2%
    seHandle.addEventListener('keydown', function (ev) {
        if (selectedId === null) return;
        var d = (ev.key === 'ArrowRight' || ev.key === 'ArrowUp') ? 2 : (ev.key === 'ArrowLeft' || ev.key === 'ArrowDown') ? -2 : 0;
        if (!d) return;
        ev.preventDefault();
        var cur = getSize(selectedId);
        commit({ w: clamp(cur.w + d), a: cur.a });
    });

    // kalau admin mengetik/mengubah token langsung di textarea, editor ikut menyesuaikan
    ta.addEventListener('input', function () {
        refreshBadges();
        if (selectedId !== null) {
            var t = readFromText(selectedId);
            if (t) { sizes[selectedId] = t; render(t); }
        }
    });

    /* ---------- sisipkan & hapus ---------- */
    document.querySelectorAll('[data-insert]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.insert;
            insertAtCursor('\n\n' + tokenFor(id, getSize(id)) + '\n\n');
            refreshBadges();
            selectPhoto(id); // langsung buka editor ukuran supaya bisa disesuaikan
        });
    });

    // Hapus foto lewat fetch (TIDAK memakai form bersarang), jadi teks yang
    // belum disimpan di halaman ini tidak hilang.
    document.querySelectorAll('[data-delete]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var photoId = btn.dataset.delete;
            if (!confirm('Hapus foto ini? File fotonya juga ikut dihapus.')) return;

            var fd = new FormData();
            fd.append('csrf', csrf);
            fd.append('news_id', newsId);
            fd.append('photo_id', photoId);

            btn.disabled = true;
            fetch('news-photo-delete.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) throw new Error(data.error || 'Gagal menghapus foto.');
                // buang juga token fotonya (apa pun ukurannya) dari teks
                ta.value = ta.value.replace(tokenRegex(photoId), '');
                if (selectedId === String(photoId)) closeEditor();
                var item = document.querySelector('[data-photo-id="' + photoId + '"]');
                if (item) item.remove();
                refreshBadges();
            })
            .catch(function (err) {
                btn.disabled = false;
                alert(err.message || 'Gagal menghapus foto. Coba muat ulang halaman.');
            });
        });
    });

    refreshBadges();
})();
</script>