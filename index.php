<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = cms_value('seo', 'home_title');
$pageDesc  = cms_value('seo', 'home_desc');
$activeNav = "home";
$canonicalPath = "index.php";
// hero data comes first so the first photo can be preloaded in the page head
$heroSlides = array_values(array_filter($conn->query("SELECT image FROM hero_slides WHERE is_active=1 ORDER BY sort_order ASC, id ASC LIMIT 4")->fetch_all(MYSQLI_ASSOC), fn($s) => is_file(__DIR__ . '/assets/images/' . $s['image'])));
if (!$heroSlides) $heroSlides = [['image' => 'production/karamoja-shoot-1.webp']];
$preloadImages = ['assets/images/' . $heroSlides[0]['image']];   // the first slide is the page's largest paint: fetch it early
$heroBtns = [];
foreach ([1, 2, 3] as $n) if (trim(cms_value('home', "hero_btn{$n}_label")) !== '' && trim(cms_value('home', "hero_btn{$n}_url")) !== '') $heroBtns[$n] = ['label' => cms_value('home', "hero_btn{$n}_label"), 'url' => cms_value('home', "hero_btn{$n}_url")];
include __DIR__ . "/includes/header.php";

// programme cards: the five programme areas come from the database, in the order set by NUFA
$progs = [];
foreach ($conn->query("SELECT * FROM programmes WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC) as $p) $progs[$p['slug']] = $p;
$cards = [];
foreach (['capacity-building', 'awards', 'mentorship', 'screenings-outreach', 'creative-enterprise', 'sacco'] as $slug) {
    if ($slug === 'awards')      $cards[] = ['title' => cms_value('home', 'awards_card_title'), 'text' => cms_value('home', 'awards_card_text'), 'img' => 'slider/slide1-1.webp', 'url' => 'awards.php', 'status' => ''];
    elseif ($slug === 'sacco')   $cards[] = ['title' => cms_value('home', 'sacco_card_title'), 'text' => cms_value('home', 'sacco_card_text'), 'img' => 'front1.webp', 'url' => 'sacco.php', 'status' => 'proposed'];
    elseif (isset($progs[$slug])) $cards[] = ['title' => $progs[$slug]['title'], 'text' => $progs[$slug]['summary'], 'img' => $progs[$slug]['image'] ?: 'nufa2.webp', 'url' => 'programme.php?slug=' . $slug, 'status' => $progs[$slug]['status']];
}
$news = $conn->query("SELECT title, slug, category, excerpt, image, published_at FROM posts WHERE is_published=1 ORDER BY is_featured DESC, published_at DESC LIMIT " . max(1, min(6, (int)nufa_fn('home', 'news_count'))))->fetch_all(MYSQLI_ASSOC);
usort($news, fn($a, $b) => strtotime($b['published_at']) <=> strtotime($a['published_at']));
$indicators = [];
for ($i = 1; $i <= 4; $i++) if ($cfg["impact{$i}_value"] !== '' && $cfg["impact{$i}_label"] !== '') $indicators[] = [$cfg["impact{$i}_value"], $cfg["impact{$i}_label"]];
?>

<!-- HERO -->
<section class="hero" aria-label="Introduction">
  <div class="hero-media" aria-hidden="true" data-hero-slides="<?php echo count($heroSlides); ?>">
    <?php foreach ($heroSlides as $i => $hs): ?><?php if ($i === 0): ?><img class="is-active" src="assets/images/<?php echo ui_e($hs['image']); ?>" alt="" width="1920" height="1080" fetchpriority="high" decoding="async"><?php else: ?><img data-src="assets/images/<?php echo ui_e($hs['image']); ?>" alt="" width="1920" height="1080" decoding="async"><?php endif; ?><?php endforeach; ?>
  </div>
  <div class="container-wide">
    <div class="hero-inner">
      <div class="eyebrow"><?php echo cms_t('home', 'hero_eyebrow'); ?></div>
      <h1><?php echo cms_t('home', 'hero_line1'); ?> <?php echo cms_t('home', 'hero_line2'); ?> <span><?php echo cms_t('home', 'hero_line3'); ?></span></h1>
      <p class="lead"><?php echo cms_t('home', 'hero_lead'); ?></p>
      <?php if ($heroBtns): ?><div class="actions hero-actions">
        <?php foreach ($heroBtns as $n => $b): ?><a href="<?php echo ui_e($b['url']); ?>" class="btn <?php echo $n === 1 ? 'btn-gold' : ($n === 2 ? 'btn-outline' : 'btn-ghost'); ?>"><?php echo ui_e($b['label']); ?></a><?php endforeach; ?>
      </div><?php endif; ?>
    </div>
  </div>
  <?php if (count($heroSlides) > 1): ?><div class="hero-dots" role="group" aria-label="Choose a photo"><?php foreach ($heroSlides as $i => $hs): ?><button type="button" class="<?php echo $i === 0 ? 'is-active' : ''; ?>" aria-label="Show photo <?php echo $i + 1; ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button><?php endforeach; ?></div><?php endif; ?>
</section>

<!-- WHO WE ARE -->
<?php if (nufa_show('home', 'who')): ?><section class="section" id="who-we-are">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow"><?php echo cms_t('home', 'who_eyebrow'); ?></div>
      <h2><?php echo cms_t('home', 'who_heading'); ?></h2>
      <div class="lede stack"><?php echo ui_paragraphs(cms_value('home', 'who_body')); ?></div>
      <p class="mt-2"><a href="about.php" class="btn btn-dark"><?php echo cms_t('home', 'who_button'); ?></a></p>
    </div>
    <div class="media-frame" data-reveal>
      <img src="assets/images/<?php echo cms_img('home', 'who_image'); ?>" alt="Filmmakers on location in Northern Uganda working with a camera crew" width="1200" height="900" loading="lazy">
    </div>
  </div>
</section><?php endif; ?>

<!-- NUFA AWARDS FEATURE -->
<?php if (nufa_show('home', 'awards')): ?><section class="section feature-band section--black" aria-labelledby="awards-feature">
  <div class="bg"><img src="assets/images/<?php echo cms_img('home', 'awards_image'); ?>" alt="" loading="lazy"></div>
  <div class="container">
    <div style="max-width:680px" data-reveal>
      <div class="eyebrow">NUFA Awards</div>
      <h2 id="awards-feature"><?php echo cms_t('home', 'awards_heading'); ?></h2>
      <p class="lede" style="color:var(--muted-on-dark)"><?php echo cms_t('home', 'awards_body'); ?></p>
      <div class="actions mt-2">
        <a href="awards.php" class="btn btn-gold">Explore the Awards</a>
        <?php $prev = $conn->query("SELECT slug FROM award_editions WHERE is_active=1 AND status='past' ORDER BY year DESC LIMIT 1")->fetch_assoc(); ?>
        <a href="<?php echo $prev ? 'award.php?e=' . ui_e($prev['slug']) : 'awards.php#archive'; ?>" class="btn btn-outline">View Previous Edition</a>
      </div>
    </div>
  </div>
</section><?php endif; ?>

<!-- PROGRAMMES -->
<?php if (nufa_show('home', 'programmes')): ?><section class="section section--soft" id="programmes">
  <div class="container">
    <div class="section-head center" data-reveal>
      <div class="eyebrow"><?php echo ui_e('What we do'); ?></div>
      <h2><?php echo cms_t('home', 'prog_heading'); ?></h2>
      <p><?php echo cms_t('home', 'prog_lede'); ?></p>
    </div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($cards as $c): ?>
      <article class="card programme-card">
        <a href="<?php echo ui_e($c['url']); ?>" style="display:flex;flex-direction:column;height:100%" aria-label="<?php echo ui_e($c['title']); ?>">
          <div class="card-media"><?php echo ui_img($c['img'], ''); ?><?php echo ui_status_chip($c['status']); ?></div>
          <div class="card-body"><h3><?php echo ui_e($c['title']); ?></h3><p><?php echo ui_e($c['text']); ?></p><span class="link-arrow">Learn more</span></div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="center mt-2"><a href="programmes.php" class="btn btn-outline-dark">View all programmes</a></p>
  </div>
</section><?php endif; ?>

<!-- OUR IMPACT -->
<?php if (nufa_show('home', 'impact')): ?><section class="section section--dark" id="impact">
  <div class="container">
    <div class="section-head" data-reveal>
      <div class="eyebrow">Our Impact</div>
      <h2><?php echo cms_t('home', 'impact_heading'); ?></h2>
      <p><?php echo cms_t('home', 'impact_body'); ?></p>
    </div>
    <div class="impact-grid" data-reveal-stagger>
      <?php foreach ([['users', 1], ['handshake', 2], ['trophy', 3]] as [$ic, $n]): ?>
      <div class="impact-item"><span class="icon-circle" style="margin-bottom:16px"><?php echo nufa_icon($ic, 24); ?></span>
        <h3><?php echo cms_t('home', "impact{$n}_title"); ?></h3><p><?php echo cms_t('home', "impact{$n}_text"); ?></p></div>
      <?php endforeach; ?>
    </div>
    <?php if ($indicators): ?>
    <div class="indicator-row" data-reveal><?php foreach ($indicators as [$v, $l]): ?><div class="indicator"><b><?php echo ui_e($v); ?></b><span><?php echo ui_e($l); ?></span></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<!-- PARTNERSHIP -->
<?php if (nufa_show('home', 'partnership')): ?><section class="section feature-band section--black" aria-labelledby="partner-feature">
  <div class="bg"><img src="assets/images/<?php echo cms_img('home', 'partner_image'); ?>" alt="" loading="lazy"></div>
  <div class="container">
    <div style="max-width:700px" data-reveal>
      <div class="eyebrow">Partnerships</div>
      <h2 id="partner-feature"><?php echo cms_t('home', 'partner_heading'); ?></h2>
      <p class="lede" style="color:var(--muted-on-dark)"><?php echo cms_t('home', 'partner_body'); ?></p>
      <p class="mt-2"><a href="partners.php" class="btn btn-gold">Partner With NUFA</a></p>
    </div>
  </div>
</section><?php endif; ?>

<!-- NEWS -->
<?php if (nufa_show('home', 'news')): ?><section class="section" id="news">
  <div class="container">
    <div class="section-head" style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;max-width:none" data-reveal>
      <div><div class="eyebrow">News &amp; Stories</div><h2 class="mb-0"><?php echo cms_t('home', 'news_heading'); ?></h2></div>
      <a href="news.php" class="link-arrow">View the full news archive</a>
    </div>
    <?php if (!$news): ?>
      <div class="empty-state"><h3>News is coming soon</h3><p>Announcements, filmmaker stories and opportunities will appear here once they are published.</p></div>
    <?php else: ?>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($news as $p): ?>
      <article class="card">
        <a href="post.php?slug=<?php echo urlencode($p['slug']); ?>" style="display:flex;flex-direction:column;height:100%">
          <div class="card-media"><?php echo ui_img($p['image'], ''); ?></div>
          <div class="card-body">
            <div class="card-meta"><b><?php echo ui_e($p['category']); ?></b><span><?php echo ui_date($p['published_at']); ?></span></div>
            <h3><?php echo ui_e($p['title']); ?></h3>
            <p><?php echo ui_e(ui_excerpt($p['excerpt'], 130)); ?></p>
            <span class="link-arrow">Read story</span>
          </div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<!-- MEMBERSHIP CTA -->
<?php if (nufa_show('home', 'cta')): ?><section class="section--tight section--soft">
  <div class="container"><?php echo ui_cta_band(cms_value('home', 'cta_title'), cms_value('home', 'cta_text'), cms_value('home', 'cta_btn1_label'), cms_value('home', 'cta_btn1_url'), cms_value('home', 'cta_btn2_label'), cms_value('home', 'cta_btn2_url')); ?></div>
</section><?php endif; ?>

<?php include __DIR__ . "/includes/footer.php"; ?>
