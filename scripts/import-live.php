<?php
/**
 * One-off: imports news posts and partners from the live site into the local DB,
 * and downloads any uploaded images that are missing locally.
 * Usage: C:\xampp\php\php.exe scripts\import-live.php
 */
require __DIR__ . "/../includes/db.php";
$base = "https://nufawards.com/";
$root = realpath(__DIR__ . "/..") . "/assets/images/";

function get($u) { return @file_get_contents($u, false, stream_context_create(['http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]])); }
function fetch_image($base, $root, $rel) {
    $dest = $root . $rel;
    if (is_file($dest) || $rel === '') return;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
    $data = get($base . "assets/images/" . rawurlencode_path($rel));
    if ($data !== false) { file_put_contents($dest, $data); echo "  downloaded $rel\n"; }
    else echo "  MISSING on live: $rel\n";
}
function rawurlencode_path($p) { return implode('/', array_map('rawurlencode', explode('/', $p))); }
function inner($html, $re) { return preg_match($re, $html, $m) ? html_entity_decode(trim($m[1]), ENT_QUOTES) : ''; }

// ---- posts
$news = get($base . "news.php");
preg_match_all('#post\.php\?slug=([^"&]+)#', $news, $m);
$slugs = array_values(array_unique($m[1]));
$featuredSlug = $slugs[0] ?? '';
foreach ($slugs as $slug) {
    $h = get($base . "post.php?slug=" . $slug);
    if (!$h) { echo "skip $slug\n"; continue; }
    $title = inner($h, '#<h1[^>]*>(.*?)</h1>#s');
    $cat   = inner($h, '#<span class="news-tag">(.*?)</span>#');
    $date  = inner($h, '#</svg>\s*(\d{1,2} \w+ \d{4})</span>#');
    $author = inner($h, '#By ([^<]+)</span>#');
    $img   = inner($h, '#class="article-hero-img"[^>]*>\s*<img src="assets/images/([^"]+)"#s');
    $excerpt = inner($h, '#<meta name="description" content="([^"]*)"#');
    preg_match('#<div class="article-body"[^>]*>(.*?)</div>\s*<div style="max-width:74ch#s', $h, $bm);
    $body = trim($bm[1] ?? '');
    $pub = date('Y-m-d H:i:s', strtotime($date ?: 'now'));
    $feat = $slug === $featuredSlug ? 1 : 0;
    fetch_image($base, $root, $img);
    $s = $conn->prepare("INSERT INTO posts (title,slug,category,excerpt,body,image,author,is_featured,is_published,published_at) VALUES (?,?,?,?,?,?,?,?,1,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),category=VALUES(category),excerpt=VALUES(excerpt),body=VALUES(body),image=VALUES(image),author=VALUES(author),is_featured=VALUES(is_featured),published_at=VALUES(published_at)");
    $s->bind_param("sssssssis", $title, $slug, $cat, $excerpt, $body, $img, $author, $feat, $pub);
    $s->execute();
    echo "post: $title ($cat, $date)\n";
}

// ---- partners
$ph = get($base . "partners.php");
preg_match('#<div class="partner-wall".*?</div>\s*</div>\s*</div>#s', $ph, $wall);
preg_match_all('#<div class="partner-tile">(.*?)</div>\s*(?=<div class="partner-tile">|</div>)#s', $wall[0] ?? $ph, $tiles);
$conn->query("DELETE FROM partners");
$n = 0;
foreach ($tiles[1] as $t) {
    $logo = ''; $name = ''; $tier = 'Partner';
    if (preg_match('#<img src="assets/images/([^"]+)" alt="([^"]*)"#', $t, $x)) {
        $logo = $x[1];
        $alt = html_entity_decode($x[2], ENT_QUOTES);
        if (preg_match('#^(.*?) — NUFA (.*)$#u', $alt, $y)) { $name = $y[1]; $tier = $y[2]; } else $name = $alt;
        fetch_image($base, $root, $logo);
    } elseif (preg_match('#<b>(.*?)</b><span>(.*?)</span>#s', $t, $x)) {
        $name = html_entity_decode($x[1], ENT_QUOTES); $tier = html_entity_decode($x[2], ENT_QUOTES);
    } else continue;
    $n++;
    $s = $conn->prepare("INSERT INTO partners (name,logo,website,tier,sort_order,is_active) VALUES (?,?,'',?,?,1)");
    $s->bind_param("sssi", $name, $logo, $tier, $n);
    $s->execute();
    echo "partner: $name [$tier]" . ($logo ? " logo=$logo" : "") . "\n";
}
echo "Done.\n";
