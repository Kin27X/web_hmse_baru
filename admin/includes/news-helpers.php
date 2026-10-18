<?php
/**
 * news-helpers.php — fungsi bantu KHUSUS fitur News (dipakai halaman publik
 * news.php / news_detail.php dan halaman admin news-*.php).
 *
 * Semua nama fungsi diawali "news" supaya tidak bentrok dengan helpers.php.
 * Di-include dengan require_once.
 *
 * Penyisipan foto di tengah teks:
 *   admin menulis token  [foto:ID]  di dalam isi berita (ID = id baris di
 *   tabel news_photos). Saat ditampilkan, token itu diganti jadi foto.
 *   Ukuran & perataan opsional:  [foto:ID|60|center]
 *     60     = lebar foto 60% dari lebar artikel (10-100, 100 = penuh)
 *     center = left | center | right (default left)
 *   Tanpa parameter = lebar penuh.
 */

if (!defined('NEWS_PHOTO_TOKEN_PATTERN')) {
    define('NEWS_PHOTO_TOKEN_PATTERN', '/\[(foto:\d+(?:\|(?:\d{1,3}|left|center|right)){0,2})\]/i');
}

/* ---------------------------------------------------------------
 * Escape HTML (aman untuk teks maupun atribut)
 * ------------------------------------------------------------- */
function newsE($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------------------------------------------------------------
 * Tanggal berbahasa Indonesia
 * ------------------------------------------------------------- */
function newsFormatDate(?string $datetime): array {
    static $hari  = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
    static $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                     7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

    $ts = $datetime ? strtotime($datetime) : false;
    if ($ts === false) {
        return ['iso' => '', 'day' => '', 'full' => ''];
    }
    $day = $hari[(int) date('w', $ts)] . ', ' . date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    return [
        'iso'  => date('Y-m-d\TH:i', $ts),
        'day'  => $day,
        'full' => $day . ' · ' . date('H:i', $ts),
    ];
}

/* ---------------------------------------------------------------
 * Teks polos & ringkasan (aman untuk UTF-8)
 * ------------------------------------------------------------- */
function newsPlainText(?string $html): string {
    $html = preg_replace(NEWS_PHOTO_TOKEN_PATTERN, ' ', (string) $html);
    // ganti penutup paragraf/baris dengan spasi supaya "<p>A.</p><p>B</p>" tidak jadi "A.B"
    $html = preg_replace('~<\s*(?:br|/p|/li|/h[1-6]|/blockquote)\b[^>]*>~i', ' ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = preg_replace('/\s+/u', ' ', $text);
    return trim($clean ?? $text);
}

function newsExcerpt(string $plain, int $len = 160): string {
    if (mb_strlen($plain, 'UTF-8') <= $len) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $len, 'UTF-8')) . '...';
}

/* ---------------------------------------------------------------
 * Sanitasi HTML isi berita (daftar tag yang diizinkan / allowlist)
 * Tag lain dibuang (isinya tetap), atribut dibuang semua kecuali
 * href pada <a> yang aman. <script>, <iframe>, dst dibuang beserta isinya.
 * ------------------------------------------------------------- */
function newsCleanNode(DOMNode $node, array $allowed): void {
    static $dropWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math', 'noscript', 'template'];

    $children = [];
    foreach ($node->childNodes as $c) {
        $children[] = $c;
    }

    foreach ($children as $child) {
        if ($child->nodeType === XML_COMMENT_NODE) {
            $node->removeChild($child);
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (in_array($tag, $dropWithContent, true)) {
            $node->removeChild($child);
            continue;
        }

        newsCleanNode($child, $allowed);

        if (!in_array($tag, $allowed, true)) {
            // buang tag-nya, pertahankan isinya
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        $attrNames = [];
        foreach ($child->attributes as $a) {
            $attrNames[] = $a->nodeName;
        }
        foreach ($attrNames as $name) {
            if ($tag === 'a' && $name === 'href') {
                $href = trim($child->getAttribute('href'));
                if (!preg_match('~^(https?://|mailto:|/|#)~i', $href)) {
                    $child->removeAttribute('href');
                }
                continue;
            }
            $child->removeAttribute($name);
        }
        if ($tag === 'a' && $child->hasAttribute('href')) {
            $child->setAttribute('target', '_blank');
            $child->setAttribute('rel', 'noopener noreferrer');
        }
    }
}

function newsSanitizeHtml(string $html): string {
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a'];

    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }

    newsCleanNode($root, $allowed);

    // buang paragraf kosong (mis. sisa dari "<p>[foto:1]</p>")
    foreach (iterator_to_array($root->getElementsByTagName('p')) as $p) {
        if (trim($p->textContent) === '' && $p->parentNode) {
            $p->parentNode->removeChild($p);
        }
    }

    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $dom->saveHTML($c);
    }
    return $out;
}

/* Satu potongan teks (di antara dua foto) -> HTML aman */
function newsFormatTextSegment(string $text): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    // Kalau admin menulis teks biasa (tanpa <p>/<ul>/dst), baris kosong = paragraf baru
    if (!preg_match('/<\s*(p|ul|ol|li|h[1-6]|blockquote)\b/i', $text)) {
        $paras = preg_split('/\R{2,}/', $text);
        $text = '';
        foreach ($paras as $p) {
            $p = trim($p);
            if ($p !== '') {
                $text .= '<p>' . nl2br($p, false) . '</p>';
            }
        }
    }
    return newsSanitizeHtml($text);
}

/* ---------------------------------------------------------------
 * Render isi berita: teks + foto yang disisipkan lewat [foto:ID]
 *   $photoSrcById : [id => src] semua foto milik berita ini
 *   $usedIds      : (output) id foto yang benar-benar tersisip
 * ------------------------------------------------------------- */
function newsRenderBody(string $body, array $photoSrcById, string $alt, &$usedIds = null): string {
    // pola token = 1 grup tangkap ("foto:22|60|center"), jadi preg_split mengembalikan
    // [teks, token, teks, token, ...]
    $parts = preg_split(NEWS_PHOTO_TOKEN_PATTERN, $body, -1, PREG_SPLIT_DELIM_CAPTURE);
    $out = '';
    $used = [];

    foreach ($parts as $i => $part) {
        if ($i % 2 === 1) {
            $bits = explode('|', $part);          // ['foto:22', '60', 'center']
            $pid = (int) substr($bits[0], 5);     // buang "foto:"

            $w = 100;
            $align = 'left';
            foreach (array_slice($bits, 1) as $b) {
                $b = strtolower($b);
                if (ctype_digit($b)) {
                    $w = max(10, min(100, (int) $b));
                } else {
                    $align = $b; // left | center | right (sudah dijamin oleh pola)
                }
            }

            if (isset($photoSrcById[$pid])) { // token foto yang sudah dihapus diabaikan
                $used[$pid] = true;
                $class = 'nd-figure';
                $style = '';
                if ($w < 100) {
                    $class .= ' nd-align-' . $align;
                    $style = ' style="width:' . $w . '%"'; // $w sudah dipastikan angka 10-100
                }
                $out .= '<figure class="' . $class . '"' . $style . '><img src="' . newsE($photoSrcById[$pid]) . '" alt="' . newsE($alt) . '" loading="lazy"></figure>';
            }
        } else {
            $out .= newsFormatTextSegment($part);
        }
    }

    $usedIds = array_keys($used);
    return $out;
}

/* ---------------------------------------------------------------
 * URL absolut (untuk og:image dsb)
 * ------------------------------------------------------------- */
function newsOrigin(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/* ---------------------------------------------------------------
 * CSRF (khusus form/aksi news di admin; session sudah dimulai auth.php)
 * ------------------------------------------------------------- */
function newsCsrfToken(): string {
    if (empty($_SESSION['news_csrf'])) {
        $_SESSION['news_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['news_csrf'];
}

function newsCsrfField(): string {
    return '<input type="hidden" name="csrf" value="' . newsE(newsCsrfToken()) . '">';
}

function newsCsrfValid(): bool {
    $t = $_POST['csrf'] ?? '';
    return is_string($t) && !empty($_SESSION['news_csrf']) && hash_equals($_SESSION['news_csrf'], $t);
}

/* ---------------------------------------------------------------
 * Flash message lewat session (bukan lewat ?msg= di URL)
 * ------------------------------------------------------------- */
function newsFlashSet(string $message, string $type = 'success'): void {
    $_SESSION['news_flash'] = ['message' => $message, 'type' => $type];
}

function newsFlashPull(): ?array {
    if (empty($_SESSION['news_flash'])) {
        return null;
    }
    $f = $_SESSION['news_flash'];
    unset($_SESSION['news_flash']);
    return $f;
}

/* ---------------------------------------------------------------
 * Hapus file foto dari folder images/ (hanya kalau sudah tidak
 * dipakai baris news_photos mana pun). Panggil SETELAH baris DB dihapus.
 * ------------------------------------------------------------- */
function newsDeleteImageFiles(PDO $pdo, array $filenames): void {
    $dir = dirname(__DIR__, 2) . '/images/';
    $check = $pdo->prepare('SELECT COUNT(*) FROM news_photos WHERE filename = ?');

    foreach (array_unique($filenames) as $f) {
        $f = basename((string) $f);
        if ($f === '' || $f === '.' || $f === '..') {
            continue;
        }
        $check->execute([$f]);
        if ((int) $check->fetchColumn() > 0) {
            continue; // masih dipakai berita lain
        }
        $path = $dir . $f;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}