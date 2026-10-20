<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$slug = preg_replace('/[^a-z0-9-]/i', '', $_GET['slug'] ?? '');
$st = $conn->prepare("SELECT * FROM programmes WHERE slug=? AND is_active=1 LIMIT 1");
$st->bind_param("s", $slug);
$st->execute();
$p = $st->get_result()->fetch_assoc();
if (!$p) { http_response_code(404); include __DIR__ . "/404.php"; exit; }

$pageTitle = $p['title'] . " — NUFA Programmes";
$pageDesc  = ui_excerpt($p['summary'] ?: $p['overview'], 155);
$activeNav = "programmes";
$canonicalPath = "programme.php?slug=" . $p['slug'];
$ogImage = "assets/images/" . ($p['image'] ?: 'og/og-default.jpg');
include __DIR__ . "/includes/header.php";

$storyCats = ['capacity-building' => ['Training and Workshops', 'Training'], 'mentorship' => ['Filmmaker Profiles', 'Training and Workshops', 'Spotlight'], 'screenings-outreach' => ['Events and Screenings', 'Community'],
              'creative-enterprise' => ['Opportunities and Calls', 'Training'], 'cultural-preservation' => ['Culture and Storytelling', 'Culture']][$p['slug']] ?? [];
$stories = [];
if ($storyCats) {
    $in = "'" . implode("','", array_map([$conn, 'real_escape_string'], $storyCats)) . "'";
    $stories = $conn->query("SELECT title, slug, category, image, published_at FROM posts WHERE is_published=1 AND category IN ($in) ORDER BY published_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
}
$others = $conn->query("SELECT slug, title FROM programmes WHERE is_active=1 AND slug<>'" . $conn->real_escape_string($p['slug']) . "' ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);

$sections = [
    ['Overview', 'overview', 'p'], ['Context and need', 'context', 'p'], ['Objectives', 'objectives', 'list'], ['Intended beneficiaries', 'beneficiaries', 'list'],
    ['Key activities', 'activities', 'list'], ['Expected outputs and outcomes', 'outputs', 'list'], ['Geographic focus', 'geography', 'inline'],
    ['Partners and supporters', 'partners_text', 'p'], ['Progress updates and reports', 'progress', 'p'], ['How your support helps', 'support_text', 'p'],
];

echo ui_page_hero('Programme', $p['title'], $p['summary'], $p['image'] ?: 'nufa2.webp', [['Programmes', 'programmes.php'], [$p['title']]],
    '<a href="contact.php?topic=Programmes&amp;ref=' . ui_e($p['slug']) . '" class="btn btn-gold">Express interest</a><a href="programmes.php" class="btn btn-outline">All programmes</a>');
?>
<section class="section">
  <div class="container member-grid" style="grid-template-columns:1.6fr .8fr">
    <div>
      <?php if ($p['status']): ?><p><?php echo ui_status_chip($p['status']); ?></p><?php else: ?><p class="muted" style="font-size:.85rem">Status: to be confirmed by NUFA.</p><?php endif; ?>
      <?php foreach ($sections as [$label, $key, $mode]):
        $val = trim((string)$p[$key]); if ($val === '') continue; ?>
        <div class="mb-2" data-reveal>
          <h2 style="font-size:1.5rem"><?php echo ui_e($label); ?></h2>
          <?php if ($mode === 'list'): echo ui_list($val); elseif ($mode === 'inline'): echo '<p>' . ui_e($val) . '</p>'; else: echo ui_paragraphs($val); endif; ?>
          <?php if ($key === 'activities' && $p['status'] === ''): ?><p class="muted" style="font-size:.85rem;margin-top:10px">Potential activities. Each activity is labelled planned, ongoing or completed only once confirmed.</p><?php endif; ?>
        </div>
      <?php endforeach; ?>

      <?php if ($stories): ?>
      <h2 style="font-size:1.5rem;margin-top:2.4rem">Related stories</h2>
      <div class="grid grid-3" style="margin-top:18px">
        <?php foreach ($stories as $s): ?><article class="card"><a href="post.php?slug=<?php echo urlencode($s['slug']); ?>"><div class="card-media"><?php echo ui_img($s['image'], ''); ?></div><div class="card-body"><div class="card-meta"><b><?php echo ui_e($s['category']); ?></b></div><h3 style="font-size:1rem"><?php echo ui_e($s['title']); ?></h3></div></a></article><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <aside class="member-side" style="top:calc(var(--header-h) + 24px)">
      <h3>Take part</h3>
      <p style="color:var(--muted-on-dark);font-size:.93rem">Interested in this programme as a participant, mentor or partner? Send us a message and we will share participation information as it is confirmed.</p>
      <div class="actions"><a href="contact.php?topic=Programmes&amp;ref=<?php echo ui_e($p['slug']); ?>" class="btn btn-gold btn-block">Express interest</a><a href="feedback.php?cat=Programme&amp;about=<?php echo urlencode($p['title']); ?>#form" class="btn btn-outline btn-block">Give feedback</a><a href="#get-involved" class="btn btn-outline btn-block">Sponsor, donate, volunteer or partner</a></div>
      <h4 style="color:var(--gold);font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;margin:26px 0 10px">Other programmes</h4>
      <ul style="font-size:.9rem;display:grid;gap:8px"><?php foreach ($others as $o): ?><li><a href="programme.php?slug=<?php echo ui_e($o['slug']); ?>" style="color:var(--white)"><?php echo ui_e($o['title']); ?></a></li><?php endforeach; ?><li><a href="awards.php" style="color:var(--white)">NUFA Awards</a></li><li><a href="sacco.php" style="color:var(--white)">NUFA SACCO (proposed)</a></li></ul>
    </aside>
  </div>
</section>
<?php
require_once __DIR__ . "/includes/support.php";
$supS = sup_settings($conn); $supKinds = sup_kinds();
$openKinds = array_filter($supKinds, fn($kd, $k) => ($supS["sup_enable_$k"] ?? '1') === '1' && (int)($p["allow_$k"] ?? 1) === 1, ARRAY_FILTER_USE_BOTH);
?>
<?php if ($openKinds): ?>
<section class="section section--dark" id="get-involved">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Get involved</div><h2><?php echo cms_t('support', 'involved_heading'); ?></h2><p><?php echo cms_t('support', 'involved_intro'); ?></p></div>
    <div class="involve-grid" data-reveal-stagger>
      <?php foreach ($openKinds as $k => $kd): ?>
      <a class="support-type" href="support.php?type=<?php echo $k; ?>&amp;programme=<?php echo ui_e($p['slug']); ?>">
        <span class="icon-circle"><?php echo nufa_icon($kd['icon'], 22); ?></span><b><?php echo ui_e($kd['label']); ?></b><span><?php echo cms_t('support', $k . '_blurb'); ?></span>
        <span class="link-arrow" style="margin-top:6px;color:var(--ink)"><?php echo ui_e($kd['label']); ?> now</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
