<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = cms_value('seo', 'about_title');
$pageDesc  = cms_value('seo', 'about_desc');
$activeNav = "about";
$canonicalPath = "about.php";
$ogImage = "assets/images/og/og-about.jpg";
include __DIR__ . "/includes/header.php";

$team = $conn->query("SELECT * FROM team_members WHERE is_active=1 AND category <> 'Awards Technical Team' ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$teamCatOrder = nufa_list($cfg, "list_team_categories");
$teamGroups = [];
foreach ($team as $m) $teamGroups[$m["category"] ?? ""][] = $m;
uksort($teamGroups, function ($a, $b) use ($teamCatOrder) { $ia = array_search($a, $teamCatOrder); $ib = array_search($b, $teamCatOrder); $ia = $ia === false ? 999 : $ia; $ib = $ib === false ? 999 : $ib; return $ia <=> $ib; });
$valueIcons = ['shield', 'star', 'handshake', 'users', 'compass', 'bulb', 'heart'];
$draft = $cfg['vm_approved'] !== '1';

echo ui_page_hero('About NUFA', cms_value('about', 'hero_title'), cms_value('about', 'hero_lede'), cms_img_raw('about', 'hero_image'), [['About NUFA']],
    '<a href="membership.php" class="btn btn-gold">Become a Member</a><a href="programmes.php" class="btn btn-outline">Our Programmes</a>');
?>

<?php if (nufa_show('about', 'story')): ?><section class="section" id="story">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow">Our Story</div>
      <h2><?php echo cms_t('about', 'story_heading'); ?></h2>
      <div class="stack"><?php echo cms_h('about', 'story_body'); ?></div>
    </div>
    <div class="media-frame media-frame--tall" data-reveal>
      <img src="assets/images/<?php echo cms_img('about', 'story_image'); ?>" alt="A woman from Northern Uganda in traditional dress" width="1200" height="1500" loading="lazy">
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('about', 'vision')): ?><section class="section section--soft" id="vision">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Direction</div><h2>Vision &amp; Mission</h2></div>
    <div class="vm-grid" data-reveal-stagger>
      <div class="vm-card"><h3>Our Vision</h3><p><?php echo cms_t('about', 'vision_text'); ?></p><?php if ($draft): ?><span class="draft-flag">Draft — pending leadership approval</span><?php endif; ?></div>
      <div class="vm-card"><h3>Our Mission</h3><p><?php echo cms_t('about', 'mission_text'); ?></p><?php if ($draft): ?><span class="draft-flag">Draft — pending leadership approval</span><?php endif; ?></div>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('about', 'values')): ?><section class="section" id="values">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">What guides us</div><h2>Our Core Values</h2></div>
    <div class="grid grid-4" data-reveal-stagger>
      <?php for ($i = 1; $i <= 7; $i++): ?>
      <div class="value"><span class="icon-circle"><?php echo nufa_icon($valueIcons[$i - 1], 24); ?></span><h3><?php echo cms_t('about', "val{$i}_title"); ?></h3><p><?php echo cms_t('about', "val{$i}_text"); ?></p></div>
      <?php endfor; ?>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('about', 'leadership')): ?><section class="section section--dark" id="leadership">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Leadership &amp; Governance</div><h2>The people guiding NUFA</h2><p><?php echo cms_t('about', 'leadership_note'); ?></p></div>
    <?php if (!$team): ?>
      <div class="empty-state" style="background:rgba(255,255,255,.05);border-color:var(--line-dark)"><h3>Leadership profiles coming soon</h3><p style="color:var(--muted-on-dark)">Profiles will be published once names, roles and portraits have been approved.</p></div>
    <?php else: ?>
    <?php foreach ($teamGroups as $gname => $members): ?>
    <?php if ($gname !== "" && count($teamGroups) > 1): ?><h3 class="group-title" style="color:var(--gold);margin:44px 0 22px;font-size:1rem;letter-spacing:.14em;text-transform:uppercase"><?php echo ui_e($gname); ?></h3><?php endif; ?>
    <div class="grid grid-4" data-reveal-stagger>
      <?php foreach ($members as $m): ?>
      <article class="leader-card">
        <div class="photo"><?php if ($m['photo'] && is_file(__DIR__ . '/assets/images/' . $m['photo'])): ?><img src="assets/images/<?php echo ui_e($m['photo']); ?>" alt="Portrait of <?php echo ui_e($m['name']); ?>" width="600" height="750" loading="lazy"><?php endif; ?></div>
        <h3 style="color:var(--white)"><?php echo ui_e($m['name']); ?></h3>
        <div class="role" style="color:var(--gold)"><?php echo ui_e($m['role']); ?></div>
        <?php if (!empty($m['bio'])): ?><p style="color:var(--muted-on-dark)"><?php echo ui_e(ui_excerpt($m['bio'], 160)); ?></p><?php endif; ?>
        <?php if ($m['linkedin_url'] || $m['x_url'] || $m['facebook_url'] || $m['instagram_url']): ?>
        <div class="social-row" style="justify-content:center;margin-top:12px">
          <?php foreach (['linkedin_url' => ['LinkedIn', 'linkedin'], 'x_url' => ['X', 'x'], 'facebook_url' => ['Facebook', 'facebook'], 'instagram_url' => ['Instagram', 'instagram']] as $k => [$l, $ic]) if ($m[$k]): ?>
            <a href="<?php echo ui_e($m[$k]); ?>" target="_blank" rel="noopener" aria-label="<?php echo ui_e($m['name'] . ' on ' . $l); ?>"><?php echo nufa_icon($ic, 15); ?></a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('about', 'membership')): ?><section class="section" id="membership">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow">Membership</div>
      <h2><?php echo cms_t('about', 'membership_heading'); ?></h2>
      <p class="lede"><?php echo cms_t('about', 'membership_intro'); ?></p>
      <div class="notice mt-2"><span><?php echo nufa_icon('shield', 22); ?></span><div><b>Please note</b><br><?php echo cms_t('about', 'membership_criteria'); ?></div></div>
      <div class="actions mt-2"><a href="membership.php" class="btn btn-gold">Apply for Membership</a><a href="contact.php?topic=Membership" class="btn btn-outline-dark">Ask a membership question</a></div>
    </div>
    <div data-reveal>
      <?php echo ui_steps(cms_value('about', 'membership_steps')); ?>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('about', 'cta')): ?><section class="section--tight section--soft"><div class="container"><?php echo ui_cta_band(cms_value('about', 'cta_title'), cms_value('about', 'cta_text'), cms_value('about', 'cta_btn1_label'), cms_value('about', 'cta_btn1_url'), cms_value('about', 'cta_btn2_label'), cms_value('about', 'cta_btn2_url')); ?></div></section><?php endif; ?>

<?php include __DIR__ . "/includes/footer.php"; ?>
