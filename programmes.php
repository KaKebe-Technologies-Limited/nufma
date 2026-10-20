<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = cms_value('seo', 'programmes_title');
$pageDesc  = cms_value('seo', 'programmes_desc');
$activeNav = "programmes";
$canonicalPath = "programmes.php";
include __DIR__ . "/includes/header.php";

$progs = $conn->query("SELECT * FROM programmes WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
echo ui_page_hero('Programmes & Projects', cms_value('programmes', 'hero_title'), cms_value('programmes', 'hero_lede'), cms_img_raw('programmes', 'hero_image'), [['Programmes']],
    '<a href="contact.php?topic=Programmes" class="btn btn-gold">Programme enquiries</a><a href="membership.php" class="btn btn-outline">Become a Member</a>');
?>
<section class="section">
  <div class="container">
    <div class="notice" data-reveal style="margin-bottom:44px"><span><?php echo nufa_icon('shield', 22); ?></span><div><b>About programme status</b><br><?php echo cms_t('programmes', 'status_note'); ?></div></div>
    <?php if (!$progs): ?>
      <div class="empty-state"><h3>Programmes coming soon</h3><p>Programme information will be published here once it has been confirmed.</p></div>
    <?php else: ?>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($progs as $p): ?>
      <article class="card programme-card">
        <a href="programme.php?slug=<?php echo ui_e($p['slug']); ?>" style="display:flex;flex-direction:column;height:100%">
          <div class="card-media"><?php echo ui_img($p['image'] ?: 'nufa2.webp', ''); ?><?php echo ui_status_chip($p['status']); ?></div>
          <div class="card-body"><h3><?php echo ui_e($p['title']); ?></h3><p><?php echo ui_e($p['summary']); ?></p><span class="link-arrow">Programme details</span></div>
        </a>
      </article>
      <?php endforeach; ?>
      <article class="card programme-card">
        <a href="awards.php" style="display:flex;flex-direction:column;height:100%"><div class="card-media"><?php echo ui_img('slider/slide1-1.webp', ''); ?></div>
          <div class="card-body"><h3>NUFA Awards</h3><p>Recognising outstanding work and celebrating the stories and storytellers of Northern Uganda.</p><span class="link-arrow">Explore the awards</span></div></a>
      </article>
      <article class="card programme-card">
        <a href="sacco.php" style="display:flex;flex-direction:column;height:100%"><div class="card-media"><?php echo ui_img('front1.webp', ''); ?><span class="chip chip--proposed">Proposed</span></div>
          <div class="card-body"><h3>NUFA SACCO — Proposed Initiative</h3><p>A proposed initiative exploring financial resilience for filmmakers. Not yet operational.</p><span class="link-arrow">Read the status</span></div></a>
      </article>
    </div>
    <?php endif; ?>
  </div>
</section>
<section class="section--tight section--soft"><div class="container"><?php echo ui_cta_band(cms_value('programmes', 'cta_title'), cms_value('programmes', 'cta_text'), cms_value('programmes', 'cta_btn1_label'), cms_value('programmes', 'cta_btn1_url'), cms_value('programmes', 'cta_btn2_label'), cms_value('programmes', 'cta_btn2_url')); ?></div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
