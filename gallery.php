<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = cms_value('seo', 'gallery_title');
$pageDesc  = cms_value('seo', 'gallery_desc');
$activeNav = "gallery";
$canonicalPath = "gallery.php";
$ogImage = "assets/images/og/og-gallery.jpg";
include __DIR__ . "/includes/header.php";

$root = __DIR__ . '/assets/images/';
$groups = ['2026' => ['label' => 'NUFA Awards 2026', 'items' => []], '2025' => ['label' => 'NUFA Awards 2025', 'items' => []], 'productions' => ['label' => 'Productions & community', 'items' => []]];
foreach (glob($root . 'gallery/2026/nufa26-*.jpg') as $f) $groups['2026']['items'][] = ['gallery/2026/' . basename($f), 'NUFA Awards 2026 red carpet and ceremony'];
foreach (glob($root . 'gallery/2025/nufa25-*.webp') as $f) $groups['2025']['items'][] = ['gallery/2025/' . basename($f), 'NUFA Awards 2025 ceremony moment'];
$prodCaptions = ['crew-monitor-check' => 'Crew checking the monitor on a Northern Uganda film set', 'cultural-portrait' => 'Cultural portrait from a film production', 'karamoja-shoot-1' => 'Filming on location in Karamoja', 'karamoja-shoot-2' => 'Production crew on location in Karamoja', 'on-set-action' => 'On set during a film production'];
foreach (glob($root . 'production/*.webp') as $f) $groups['productions']['items'][] = ['production/' . basename($f), $prodCaptions[basename($f, '.webp')] ?? 'Film production in Northern Uganda'];
foreach (glob($root . 'gallery/2025/nufa-*.webp') as $f) if (!preg_match('/_\d\.webp$/', $f)) $groups['productions']['items'][] = ['gallery/2025/' . basename($f), 'Filmmaking and community storytelling in Northern Uganda'];
foreach ($conn->query("SELECT image, caption, edition FROM gallery_images WHERE image LIKE 'uploads/%' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC) as $u) {
    $k = substr($u['edition'], -2) === '26' ? '2026' : (substr($u['edition'], -2) === '25' ? '2025' : 'productions');
    array_unshift($groups[$k]['items'], [$u['image'], $u['caption'] ?: $groups[$k]['label']]);
}
$videos = $conn->query("SELECT * FROM videos WHERE is_active=1 ORDER BY sort_order ASC, id DESC")->fetch_all(MYSQLI_ASSOC);

echo ui_page_hero('Gallery & Video Library', cms_value('gallery', 'hero_title'), cms_value('gallery', 'hero_lede'), cms_img_raw('gallery', 'hero_image'), [['Resources', 'resources.php'], ['Gallery & Video']],
    '<a href="#photos" class="btn btn-gold">Photo gallery</a><a href="#videos" class="btn btn-outline">Video library</a>');
?>
<section class="section" id="photos">
  <div class="container-wide">
    <div class="section-head" style="margin-inline:var(--gutter)"><div class="eyebrow">Photographs</div><h2>Photo gallery</h2><p><?php echo cms_t('gallery', 'credit_note'); ?></p></div>
    <div class="filter-bar" style="margin-inline:var(--gutter)" role="tablist" aria-label="Filter photographs">
      <button class="gallery-tab is-active" data-filter="all" type="button">All</button>
      <?php foreach ($groups as $k => $g) if ($g['items']): ?><button class="gallery-tab" data-filter="<?php echo ui_e($k); ?>" type="button"><?php echo ui_e($g['label']); ?></button><?php endif; ?>
    </div>
    <div class="masonry" style="margin-inline:var(--gutter)">
      <?php foreach ($groups as $k => $g) foreach ($g['items'] as [$img, $cap]): ?>
      <div class="m-item" data-gallery-item="<?php echo ui_e($k); ?>">
        <img src="assets/images/<?php echo ui_e($img); ?>" data-lightbox="assets/images/<?php echo ui_e($img); ?>" data-caption="<?php echo ui_e($cap); ?>" alt="<?php echo ui_e($cap); ?>" loading="lazy" decoding="async">
        <div class="m-overlay"><span><?php echo ui_e($g['label']); ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--dark" id="videos">
  <div class="container">
    <div class="section-head"><div class="eyebrow">Video library</div><h2>Video highlights</h2><p>Videos are embedded from NUFA's official channels once approved for publication.</p></div>
    <?php if (!$videos): ?>
      <div class="empty-state" style="background:rgba(255,255,255,.05);border-color:var(--line-dark)"><h3>Videos coming soon</h3><p style="color:var(--muted-on-dark)">Highlights from NUFA Awards, screenings and training sessions will appear here once they are added.</p></div>
    <?php else: ?>
    <div class="video-grid">
      <?php foreach ($videos as $v): $embed = ui_video_embed($v['video_url']); if (!$embed) continue; ?>
      <article class="video-card"><div class="frame"><iframe src="<?php echo ui_e($embed); ?>" title="<?php echo ui_e($v['title']); ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>
        <h3><?php echo ui_e($v['title']); ?></h3><?php if ($v['caption']): ?><p class="muted" style="font-size:.88rem"><?php echo ui_e($v['caption']); ?></p><?php endif; ?></article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
