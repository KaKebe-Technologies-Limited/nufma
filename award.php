<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/db.php";

$slug = preg_replace('/[^a-z0-9-]/i', '', $_GET['e'] ?? '');
$st = $conn->prepare("SELECT * FROM award_editions WHERE slug=? AND is_active=1 LIMIT 1");
$st->bind_param("s", $slug);
$st->execute();
$ed = $st->get_result()->fetch_assoc();
if (!$ed) { http_response_code(404); include __DIR__ . "/404.php"; exit; }

$year = (int)$ed['year'];
$upcoming = $ed['status'] === 'upcoming';
$pageTitle = "NUFA Awards {$year} — " . ($upcoming ? 'Upcoming edition' : 'Edition archive') . " | NUFA";
$pageDesc  = ui_excerpt($ed['description'], 155);
$activeNav = "awards";
$canonicalPath = "award.php?e=" . $ed['slug'];
$ogImage = "assets/images/" . ($ed['image'] ?: 'og/og-awards.jpg');
include __DIR__ . "/includes/header.php";

$w = $conn->prepare("SELECT * FROM award_winners WHERE edition_id=? ORDER BY sort_order ASC, id ASC");
$w->bind_param("i", $ed['id']);
$w->execute();
$winners = $w->get_result()->fetch_all(MYSQLI_ASSOC);
$categories = array_values(array_unique(array_map(fn($x) => $x['category'], $winners)));

$gallery = [];
foreach (['masonry1', 'masonry2', 'masonry3', 'masonry4'] as $k) if ($ed[$k] && is_file(__DIR__ . '/assets/images/' . $ed[$k])) $gallery[] = ['image' => $ed[$k], 'caption' => 'NUFA Awards ' . $year];
$g = $conn->prepare("SELECT image, caption FROM gallery_images WHERE edition IN (?, ?) ORDER BY created_at DESC LIMIT 24");
$yr = (string)$year;
$g->bind_param("ss", $ed['slug'], $yr);
$g->execute();
foreach ($g->get_result()->fetch_all(MYSQLI_ASSOC) as $row) if (is_file(__DIR__ . '/assets/images/' . $row['image'])) $gallery[] = ['image' => $row['image'], 'caption' => $row['caption'] ?: 'NUFA Awards ' . $year];
$gallery = array_slice($gallery, 0, 24);
$embed = ui_video_embed($ed['video_url'] ?? '');
$highlights = trim($ed['highlights_html'] ?? '') !== '' ? $ed['highlights_html'] : ($ed['extra_html'] ?? '');
$stats = [];
for ($i = 1; $i <= 4; $i++) if ($ed["stat{$i}_value"] !== '' && $ed["stat{$i}_label"] !== '') $stats[] = [$ed["stat{$i}_value"], $ed["stat{$i}_label"]];
$others = $conn->query("SELECT slug, year FROM award_editions WHERE is_active=1 AND slug<>'" . $conn->real_escape_string($ed['slug']) . "' ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);

echo ui_page_hero('NUFA Awards ' . $year, 'NUFA Awards ' . $year, $ed['theme_quote'] ?: ($upcoming ? 'Upcoming edition' : 'Edition archive'), $ed['image'] ?: 'slider/hero/hero-slide-3.webp',
    [['NUFA Awards', 'awards.php'], ['Awards ' . $year]], '<a href="#overview" class="btn btn-gold">Overview</a><a href="awards.php#archive" class="btn btn-outline">All editions</a>');
?>

<section class="section--tight">
  <div class="container">
    <div class="fact-bar">
      <div><span>Edition</span><b>NUFA Awards <?php echo $year; ?></b></div>
      <?php if ($ed['event_date_label']): ?><div><span>Date</span><b><?php echo ui_e($ed['event_date_label']); ?></b></div><?php endif; ?>
      <?php if ($ed['location_label']): ?><div><span>Venue</span><b><?php echo ui_e($ed['location_label']); ?></b></div><?php endif; ?>
      <div><span>Status</span><b><?php echo $upcoming ? 'Upcoming' : 'Completed'; ?></b></div>
    </div>
  </div>
</section>

<section class="section" id="overview">
  <div class="container">
    <div class="split">
      <div data-reveal><div class="eyebrow">Event overview</div><h2>About NUFA Awards <?php echo $year; ?></h2><div class="lede stack"><?php echo ui_paragraphs($ed['description']); ?></div></div>
      <?php if ($ed['image']): ?><div class="media-frame" data-reveal><img src="assets/images/<?php echo ui_e($ed['image']); ?>" alt="NUFA Awards <?php echo $year; ?>" loading="lazy"></div><?php endif; ?>
    </div>
    <?php if ($stats): ?>
    <div class="indicator-row" style="margin-top:50px" data-reveal><?php foreach ($stats as [$v, $l]): ?><div class="indicator" style="background:var(--ink);"><b><?php echo ui_e($v); ?></b><span><?php echo ui_e($l); ?></span></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</section>

<?php if (trim($ed['categories_html'] ?? '') !== '' || $categories): ?>
<section class="section section--soft" id="categories">
  <div class="container">
    <div class="section-head" data-reveal><div class="eyebrow">Award categories</div><h2>Verified categories</h2></div>
    <?php if (trim($ed['categories_html'] ?? '') !== ''): ?><div class="stack"><?php echo $ed['categories_html']; ?></div>
    <?php else: ?><ul class="check-list" style="columns:2 280px"><?php foreach ($categories as $c): ?><li><?php echo ui_e($c); ?></li><?php endforeach; ?></ul><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if (trim($ed['nominees_html'] ?? '') !== ''): ?>
<section class="section" id="nominees"><div class="container"><div class="section-head"><div class="eyebrow">Nominees</div><h2>Nominees</h2></div><div class="stack"><?php echo $ed['nominees_html']; ?></div></div></section>
<?php endif; ?>

<?php if ($winners): ?>
<section class="section <?php echo trim($ed['nominees_html'] ?? '') !== '' ? 'section--soft' : ''; ?>" id="winners">
  <div class="container">
    <div class="section-head" data-reveal><div class="eyebrow">Winners</div><h2>Winners</h2></div>
    <div data-reveal>
    <?php foreach ($winners as $win): ?>
      <div class="winner"><div class="cat"><?php echo ui_e($win['category']); ?><?php if ($win['is_honorary']): ?><br><span class="tag-honorary">Honorary</span><?php endif; ?></div>
        <div class="who"><b><?php echo ui_e($win['winner_title']); ?></b><?php if ($win['person_name']): ?><span><?php echo ui_e($win['person_name']); ?></span><?php endif; ?></div></div>
    <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (trim($highlights) !== ''): ?>
<section class="section section--soft" id="highlights"><div class="container"><div class="section-head"><div class="eyebrow">Ceremony highlights</div><h2>Highlights</h2></div><div class="stack"><?php echo $highlights; ?></div></div></section>
<?php endif; ?>

<?php if ($gallery): ?>
<section class="section" id="gallery">
  <div class="container-wide">
    <div class="section-head" style="margin-inline:var(--gutter)"><div class="eyebrow">Photo gallery</div><h2>Moments from <?php echo $year; ?></h2></div>
    <div class="masonry" data-reveal>
      <?php foreach ($gallery as $it): ?>
      <div class="m-item"><img src="assets/images/<?php echo ui_e($it['image']); ?>" data-lightbox="assets/images/<?php echo ui_e($it['image']); ?>" data-caption="<?php echo ui_e($it['caption']); ?>" alt="<?php echo ui_e($it['caption']); ?>" loading="lazy"></div>
      <?php endforeach; ?>
    </div>
    <p class="center mt-2"><a href="gallery.php" class="link-arrow">Open the full gallery</a></p>
  </div>
</section>
<?php endif; ?>

<?php if ($embed): ?>
<section class="section section--dark" id="video"><div class="container"><div class="section-head"><div class="eyebrow">Video highlights</div><h2>Watch the highlights</h2></div>
  <div class="video-card"><div class="frame"><iframe src="<?php echo ui_e($embed); ?>" title="NUFA Awards <?php echo $year; ?> video highlights" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div></div></section>
<?php endif; ?>

<?php if (trim($ed['sponsors_html'] ?? '') !== ''): ?>
<section class="section" id="sponsors"><div class="container"><div class="section-head"><div class="eyebrow">Sponsors &amp; partners</div><h2>Sponsors and partners</h2></div><div class="stack"><?php echo $ed['sponsors_html']; ?></div></div></section>
<?php endif; ?>

<?php if (trim($ed['press_html'] ?? '') !== ''): ?>
<section class="section section--soft" id="press"><div class="container"><div class="section-head"><div class="eyebrow">Press coverage</div><h2>In the press</h2></div><div class="stack"><?php echo $ed['press_html']; ?></div></div></section>
<?php endif; ?>

<section class="section--tight">
  <div class="container">
    <?php echo ui_cta_band('Questions about NUFA Awards ' . $year . '?', 'Get in touch with the awards team, or explore other editions.', 'Awards enquiries', 'contact.php?topic=Awards', 'All editions', 'awards.php#archive'); ?>
    <?php if ($others): ?><p class="center muted mt-2">Other editions: <?php foreach ($others as $i => $o): ?><a class="hl" style="font-weight:700" href="award.php?e=<?php echo ui_e($o['slug']); ?>"><?php echo (int)$o['year']; ?></a><?php echo $i < count($others) - 1 ? ' · ' : ''; ?><?php endforeach; ?></p><?php endif; ?>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
