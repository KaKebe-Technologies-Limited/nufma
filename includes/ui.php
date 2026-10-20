<?php
/** Small presentation helpers shared by the public pages. */
require_once __DIR__ . "/icons.php";

function ui_e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** <img> pointing into assets/images with lazy loading and intrinsic size to avoid layout shift. */
function ui_img($rel, $alt, $opts = []) {
    $path = __DIR__ . '/../assets/images/' . $rel;
    $w = $h = '';
    if (is_file($path) && ($sz = @getimagesize($path))) { $w = ' width="' . $sz[0] . '" height="' . $sz[1] . '"'; }
    $lazy = array_key_exists('lazy', $opts) ? $opts['lazy'] : true;
    $cls = !empty($opts['class']) ? ' class="' . ui_e($opts['class']) . '"' : '';
    return '<img src="assets/images/' . ui_e($rel) . '" alt="' . ui_e($alt) . '"' . $w . $cls . ($lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"') . '>';
}

/** Standard inner-page banner. $crumbs: [['Label','url'], ..., ['Current']] */
function ui_page_hero($eyebrow, $title, $lede, $imageRel, array $crumbs = [], $actionsHtml = '') {
    ob_start(); ?>
<section class="page-hero">
  <div class="page-hero-bg"><img src="assets/images/<?php echo ui_e($imageRel); ?>" alt="" fetchpriority="high"></div>
  <div class="container">
    <?php if ($crumbs): ?>
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a>
      <?php foreach ($crumbs as $i => $c): ?><span aria-hidden="true">/</span><?php if (isset($c[1])): ?><a href="<?php echo ui_e($c[1]); ?>"><?php echo ui_e($c[0]); ?></a><?php else: ?><span aria-current="page"><?php echo ui_e($c[0]); ?></span><?php endif; ?><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <?php if ($eyebrow): ?><div class="eyebrow"><?php echo ui_e($eyebrow); ?></div><?php endif; ?>
    <h1><?php echo ui_e($title); ?></h1>
    <?php if ($lede): ?><p><?php echo ui_e($lede); ?></p><?php endif; ?>
    <?php if ($actionsHtml): ?><div class="actions"><?php echo $actionsHtml; ?></div><?php endif; ?>
  </div>
</section>
<?php return ob_get_clean();
}

function ui_status_label($status) {
    return ['planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'under_development' => 'Under Development', 'proposed' => 'Proposed'][$status] ?? '';
}
function ui_status_chip($status) {
    $l = ui_status_label($status);
    return $l ? '<span class="chip chip--' . ui_e($status) . '">' . ui_e($l) . '</span>' : '';
}

/** Turns a plain-text block into paragraphs. */
function ui_paragraphs($text) {
    $out = '';
    foreach (preg_split('/\R{2,}/', trim((string)$text)) as $p) if (trim($p) !== '') $out .= '<p>' . nl2br(ui_e(trim($p))) . '</p>';
    return $out;
}
/** Newline-separated list → <ul>. */
function ui_list($text, $class = 'check-list') {
    $items = array_filter(array_map('trim', preg_split('/\R/', (string)$text)));
    if (!$items) return '';
    return '<ul class="' . $class . '">' . implode('', array_map(fn($i) => '<li>' . ui_e($i) . '</li>', $items)) . '</ul>';
}

function ui_cta_band($title, $text, $btnLabel, $btnUrl, $btn2Label = '', $btn2Url = '') {
    ob_start(); ?>
<div class="cta-band" data-reveal>
  <div><h2><?php echo ui_e($title); ?></h2><p><?php echo ui_e($text); ?></p></div>
  <div class="actions"><a href="<?php echo ui_e($btnUrl); ?>" class="btn btn-gold"><?php echo ui_e($btnLabel); ?></a><?php if ($btn2Label): ?><a href="<?php echo ui_e($btn2Url); ?>" class="btn btn-outline"><?php echo ui_e($btn2Label); ?></a><?php endif; ?></div>
</div>
<?php return ob_get_clean();
}

/** Safe external video embed (YouTube / Vimeo only). Returns embed URL or ''. */
function ui_video_embed($url) {
    $url = trim((string)$url);
    if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,15})#', $url, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    if (preg_match('#vimeo\.com/(?:video/)?(\d{5,12})#', $url, $m)) return 'https://player.vimeo.com/video/' . $m[1];
    return '';
}

function ui_date($d, $fmt = 'j F Y') { return $d ? date($fmt, strtotime($d)) : ''; }
function ui_excerpt($text, $len = 160) {
    $t = trim(preg_replace('/\s+/', ' ', strip_tags((string)$text)));
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len - 1) . '…' : $t;
}

/** Privacy-friendly page counter: one anonymous row per page per day — no cookies, IPs or personal data. */
function nufa_hit($conn, $page) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    if (preg_match('/bot|crawl|spider|slurp|curl|wget|monitor/i', $_SERVER['HTTP_USER_AGENT'] ?? '')) return;
    $p = $conn->real_escape_string($page);
    $conn->query("INSERT INTO page_hits (page, day, hits) VALUES ('$p', CURDATE(), 1) ON DUPLICATE KEY UPDATE hits = hits + 1");
}

/** The editorial categories defined in the master brief. */
const NUFA_NEWS_CATEGORIES = ['NUFA News', 'NUFA Awards', 'Filmmaker Profiles', 'Film Industry Updates', 'Training and Workshops', 'Opportunities and Calls', 'Events and Screenings', 'Culture and Storytelling', 'Partner News'];
const NUFA_RESOURCE_CATEGORIES = ['Film production guides', 'Training materials', 'Industry reports', 'Calls for submissions', 'Funding and grant opportunities', 'Filmmaking opportunities', 'Policies and guidelines', 'Annual reports', 'Event reports', 'Publications'];

/** Numbered "Title | description" lines → <ol class="steps">. */
function ui_steps($text) {
    $out = '<ol class="steps">';
    foreach (array_filter(array_map('trim', preg_split('/\R/', (string)$text))) as $line) {
        [$t, $d] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        $out .= '<li><b>' . ui_e($t) . '</b>' . ui_e($d) . '</li>';
    }
    return $out . '</ol>';
}
/** Admin-editable category lists (Site Settings → Lists), falling back to the brief's defaults. */
function nufa_news_categories($conn) {
    require_once __DIR__ . "/settings.php";
    return nufa_list(nufa_site_settings($conn), 'list_news_categories') ?: NUFA_NEWS_CATEGORIES;
}
function nufa_resource_categories($conn) {
    require_once __DIR__ . "/settings.php";
    return nufa_list(nufa_site_settings($conn), 'list_resource_categories') ?: NUFA_RESOURCE_CATEGORIES;
}

/** Admin helper: category filter tabs. $counts = [category => n]; "" = uncategorised. */
function ui_admin_cat_tabs($page, array $cats, $current, array $counts, $extra = []) {
    $q = fn($c) => $page . '?' . http_build_query(array_filter(array_merge($extra, ['cat' => $c]), fn($v) => $v !== ''));
    $total = array_sum($counts);
    $h = '<div class="mem-tabs"><a href="' . ui_e($q('')) . '" class="' . ($current === '' ? 'active' : '') . '">All (' . $total . ')</a>';
    foreach ($cats as $c) $h .= '<a href="' . ui_e($q($c)) . '" class="' . ($current === $c ? 'active' : '') . '">' . ui_e($c) . ' (' . (int)($counts[$c] ?? 0) . ')</a>';
    if (!empty($counts['']) ) $h .= '<a href="' . ui_e($q('__none')) . '" class="' . ($current === '__none' ? 'active' : '') . '">Uncategorised (' . (int)$counts[''] . ')</a>';
    return $h . '</div>';
}
/** SQL fragment for a category filter value from ui_admin_cat_tabs ('' = all, '__none' = uncategorised). */
function ui_cat_where($conn, $cat) {
    if ($cat === '') return '1=1';
    if ($cat === '__none') return "category=''";
    return "category='" . $conn->real_escape_string($cat) . "'";
}
