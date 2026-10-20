<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = "NUFA Awards — Recognising Excellence. Celebrating Our Stories.";
$pageDesc  = "NUFA Awards celebrates the talent, creativity and technical skill of filmmakers and audiovisual creatives in Northern Uganda. Explore the awards, past editions and how to take part.";
$activeNav = "awards";
$canonicalPath = "awards.php";
$ogImage = "assets/images/og/og-awards.jpg";
include __DIR__ . "/includes/header.php";

$editions = $conn->query("SELECT * FROM award_editions WHERE is_active=1 ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
$upcoming = array_values(array_filter($editions, fn($x) => $x['status'] === 'upcoming'));
$past     = array_values(array_filter($editions, fn($x) => $x['status'] !== 'upcoming'));
$awardNews = $conn->query("SELECT title, slug, category, excerpt, image, published_at FROM posts WHERE is_published=1 AND category='NUFA Awards' ORDER BY published_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$submission = trim(cms_value('awards', 'submission_url'));

echo ui_page_hero('NUFA Awards', cms_value('awards', 'hero_heading'), cms_value('awards', 'hero_lede'), cms_img_raw('awards', 'hero_image'), [['NUFA Awards']],
    '<a href="#archive" class="btn btn-gold">Explore the Archive</a><a href="contact.php?topic=Awards" class="btn btn-outline">Awards enquiries</a>');
?>

<section class="section">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow">Purpose &amp; significance</div>
      <h2><?php echo cms_t('awards', 'purpose_heading'); ?></h2>
      <div class="lede stack"><?php echo ui_paragraphs(cms_value('awards', 'purpose_body')); ?></div>
    </div>
    <div data-reveal>
      <h3>On this platform</h3>
      <ul class="check-list">
        <li>Awards overview and purpose</li><li>Previous editions and the awards archive</li><li>Approved award categories</li>
        <li>Nominees and winners, where verified</li><li>Photographs and video highlights</li><li>Sponsors and partners</li>
        <li>News and announcements</li><li>Participation information and awards enquiries</li>
      </ul>
    </div>
  </div>
</section>

<?php foreach ($upcoming as $u): ?>
<section class="section feature-band section--black" id="upcoming">
  <div class="bg"><img src="assets/images/<?php echo ui_e($u['image'] ?: 'slider/hero/hero-slide-3.webp'); ?>" alt="" loading="lazy"></div>
  <div class="container">
    <div style="max-width:720px" data-reveal>
      <span class="chip chip--dark" style="border:1px solid var(--gold)"><?php echo ui_e($u['badge_text'] ?: 'Upcoming Edition'); ?></span>
      <h2 style="margin-top:18px">NUFA Awards <span class="hl"><?php echo (int)$u['year']; ?></span></h2>
      <p class="lede" style="color:var(--muted-on-dark)"><?php echo ui_e($u['description']); ?></p>
      <ul class="meta-list" style="color:var(--muted-on-dark);margin:20px 0">
        <?php if ($u['event_date_label']): ?><li><?php echo nufa_icon('calendar', 18); ?><span><?php echo ui_e($u['event_date_label']); ?></span></li><?php endif; ?>
        <?php if ($u['location_label']): ?><li><?php echo nufa_icon('pin', 18); ?><span><?php echo ui_e($u['location_label']); ?></span></li><?php endif; ?>
      </ul>
      <?php if ($cfg['tickets_open'] === '1'): ?>
        <div class="notice notice--dark" style="margin-bottom:22px"><span><?php echo nufa_icon('star', 22); ?></span><div><b>Tickets</b><br>Ticket information is available from the NUFA team<?php echo $cfg['ticket_price'] !== '' ? ' — general admission UGX ' . ui_e($cfg['ticket_price']) : ''; ?>. Please enquire through the contact form.</div></div>
      <?php endif; ?>
      <div class="actions"><a href="award.php?e=<?php echo ui_e($u['slug']); ?>" class="btn btn-gold">Edition details</a><a href="partners.php#enquiry" class="btn btn-outline">Sponsorship opportunities</a></div>
    </div>
  </div>
</section>
<?php endforeach; ?>

<section class="section section--soft" id="archive">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Awards archive</div><h2>Previous editions</h2><p>Verified records of each edition. Information that has not been confirmed is not published.</p></div>
    <?php if (!$past): ?>
      <div class="empty-state"><h3>No past editions published yet</h3><p>Archive pages will appear here as editions are documented.</p></div>
    <?php else: ?>
    <div class="grid" style="gap:28px" data-reveal-stagger>
      <?php foreach ($past as $p): ?>
      <article class="edition-card">
        <div class="media"><img src="assets/images/<?php echo ui_e($p['image'] ?: 'slider/hero/hero-slide-1.webp'); ?>" alt="NUFA Awards <?php echo (int)$p['year']; ?> ceremony" loading="lazy"></div>
        <div class="body">
          <span class="chip chip--dark" style="width:max-content"><?php echo ui_e($p['badge_text'] ?: 'Past Edition'); ?></span>
          <div class="year"><?php echo (int)$p['year']; ?></div>
          <?php if ($p['theme_quote']): ?><p style="font-weight:700;margin:0"><?php echo ui_e($p['theme_quote']); ?></p><?php endif; ?>
          <p class="muted"><?php echo ui_e(ui_excerpt($p['description'], 230)); ?></p>
          <ul class="meta-list">
            <?php if ($p['event_date_label']): ?><li><?php echo nufa_icon('calendar', 17); ?><span><?php echo ui_e($p['event_date_label']); ?></span></li><?php endif; ?>
            <?php if ($p['location_label']): ?><li><?php echo nufa_icon('pin', 17); ?><span><?php echo ui_e($p['location_label']); ?></span></li><?php endif; ?>
          </ul>
          <a href="award.php?e=<?php echo ui_e($p['slug']); ?>" class="link-arrow">View edition</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section" id="participate">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow">Take part</div>
      <h2>Participation information</h2>
      <div class="lede stack"><?php echo ui_paragraphs(cms_value('awards', 'participation_body')); ?></div>
      <div class="actions mt-2">
        <?php if ($submission !== ''): ?><a href="<?php echo ui_e($submission); ?>" class="btn btn-gold" target="_blank" rel="noopener">Official submission platform</a><?php endif; ?>
        <a href="contact.php?topic=Awards" class="btn <?php echo $submission !== '' ? 'btn-outline-dark' : 'btn-gold'; ?>">Awards enquiries</a>
      </div>
    </div>
    <div class="media-frame" data-reveal><img src="assets/images/slider/slide1-1.webp" alt="Guests and winners on stage at a NUFA Awards ceremony" width="1920" height="1280" loading="lazy"></div>
  </div>
</section>

<?php if ($awardNews): ?>
<section class="section section--soft">
  <div class="container">
    <div class="section-head" style="max-width:none;display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap"><div><div class="eyebrow">Awards news</div><h2 class="mb-0">News &amp; announcements</h2></div><a class="link-arrow" href="news.php?cat=NUFA+Awards">All awards news</a></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($awardNews as $p): ?>
      <article class="card"><a href="post.php?slug=<?php echo urlencode($p['slug']); ?>" style="display:flex;flex-direction:column;height:100%">
        <div class="card-media"><?php echo ui_img($p['image'], ''); ?></div>
        <div class="card-body"><div class="card-meta"><b><?php echo ui_e($p['category']); ?></b><span><?php echo ui_date($p['published_at']); ?></span></div><h3><?php echo ui_e($p['title']); ?></h3><span class="link-arrow">Read story</span></div></a></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section--tight"><div class="container"><?php echo ui_cta_band('Support the NUFA Awards', 'Sponsor the awards, support emerging filmmakers or partner on film training and audience development.', 'Partner With NUFA', 'partners.php', 'Become a Member', 'membership.php'); ?></div></section>

<?php include __DIR__ . "/includes/footer.php"; ?>
