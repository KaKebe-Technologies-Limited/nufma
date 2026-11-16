<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = cms_value('seo', 'about_title');
$pageDesc  = cms_value('seo', 'about_desc');
$activeNav = "about";
$canonicalPath = "about.php";
$ogImage = "assets/images/og/og-about.jpg";
include __DIR__ . "/includes/header.php";

require_once __DIR__ . "/includes/team-seed.php"; team_seed_okello($conn); team_seed_ojok($conn);
$team = $conn->query("SELECT * FROM team_members WHERE is_active=1 AND category NOT IN ('Awards Technical Team', 'SACCO Leadership', 'SACCO Governance', 'SACCO Management') ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$teamCatOrder = nufa_list($cfg, "list_team_categories");
$teamGroups = [];
foreach ($team as $m) $teamGroups[$m["category"] ?? ""][] = $m;
uksort($teamGroups, function ($a, $b) use ($teamCatOrder) { $ia = array_search($a, $teamCatOrder); $ib = array_search($b, $teamCatOrder); $ia = $ia === false ? 999 : $ia; $ib = $ib === false ? 999 : $ib; return $ia <=> $ib; });
$valueIcons = ['shield', 'star', 'handshake', 'users', 'compass', 'bulb', 'heart'];
$draft = $cfg['vm_approved'] !== '1';

echo ui_page_hero('About NUFA', cms_value('about', 'hero_title'), cms_value('about', 'hero_lede'), cms_img_raw('about', 'hero_image'), [['About NUFA']],
    '<a href="membership.php" class="btn btn-gold">Become a Member</a><a href="programmes.php" class="btn btn-outline">Our Programmes</a>');
?>

<?php if (nufa_show('about', 'story')):
    $__body = (string)cms_h('about', 'story_body'); $__paras = [];
    if (preg_match_all('#<p\b[^>]*>(.*?)</p>#is', $__body, $__m)) foreach ($__m[1] as $__p) if (trim(strip_tags($__p)) !== '') $__paras[] = $__p;
    if (!$__paras && trim(strip_tags($__body)) !== '') $__paras[] = $__body;
    $__labels = array_map('trim', explode(',', (string)cms_value('about', 'story_labels'))); $__n = max(1, count($__paras));
?><section class="section story-sec" id="story">
  <div class="container story-wrap">
    <div class="story-orbit" data-reveal style="--n:<?php echo $__n; ?>" aria-label="Our story, tap a circle to read each part">
      <span class="story-ring" aria-hidden="true"></span><span class="story-ring story-ring--2" aria-hidden="true"></span>
      <div class="story-photo"><img src="assets/images/<?php echo cms_img('about', 'story_image'); ?>" alt="A woman from Northern Uganda in traditional dress" width="1200" height="1500" loading="lazy"></div>
      <div class="story-nodes" role="tablist" aria-label="Parts of our story">
        <?php foreach ($__paras as $__i => $__p): $__l = $__labels[$__i] ?? ('Part ' . ($__i + 1)); if ($__l === '') $__l = 'Part ' . ($__i + 1); ?>
        <button type="button" class="story-node<?php echo $__i === 0 ? ' is-active' : ''; ?>" role="tab" id="stab<?php echo $__i; ?>" aria-controls="spanel" aria-selected="<?php echo $__i === 0 ? 'true' : 'false'; ?>" data-i="<?php echo $__i; ?>" style="--a:<?php echo round(-90 + ($__i + .5) * 360 / $__n - 360 / $__n / 2 + 0, 2); ?>deg"><b><?php echo $__i + 1; ?></b><span><?php echo htmlspecialchars($__l, ENT_QUOTES, 'UTF-8'); ?></span></button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="story-text" data-reveal>
      <div class="eyebrow">Our Story</div>
      <h2><?php echo cms_t('about', 'story_heading'); ?></h2>
      <div class="story-panel" id="spanel" role="tabpanel" aria-live="polite">
        <?php foreach ($__paras as $__i => $__p): ?><div class="story-para<?php echo $__i === 0 ? ' is-active' : ''; ?>" data-i="<?php echo $__i; ?>"><p><?php echo $__p; ?></p></div><?php endforeach; ?>
      </div>
      <div class="story-ctl">
        <button type="button" class="story-arrow" data-dir="-1" aria-label="Previous part">&#8249;</button>
        <span class="story-dots"><?php foreach ($__paras as $__i => $__p): ?><i class="<?php echo $__i === 0 ? 'is-active' : ''; ?>" data-i="<?php echo $__i; ?>"></i><?php endforeach; ?></span>
        <button type="button" class="story-arrow" data-dir="1" aria-label="Next part">&#8250;</button>
        <span class="story-count"><b id="storyNow">1</b> / <?php echo $__n; ?></span>
      </div>
    </div>
  </div>
</section>
<script>(function(){
  var sec=document.getElementById('story');if(!sec)return;
  var nodes=[].slice.call(sec.querySelectorAll('.story-node')),paras=[].slice.call(sec.querySelectorAll('.story-para')),dots=[].slice.call(sec.querySelectorAll('.story-dots i')),now=document.getElementById('storyNow'),orbit=sec.querySelector('.story-orbit');
  var n=paras.length,cur=0,timer=null,reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  function go(i){cur=(i+n)%n;nodes.forEach(function(x,k){var on=k===cur;x.classList.toggle('is-active',on);x.setAttribute('aria-selected',on?'true':'false');});paras.forEach(function(x,k){x.classList.toggle('is-active',k===cur);});dots.forEach(function(x,k){x.classList.toggle('is-active',k===cur);});if(now)now.textContent=cur+1;orbit.style.setProperty('--spin',(cur*360/n)+'deg');}
  function stop(){if(timer){clearInterval(timer);timer=null;}}
  function start(){if(reduce||n<2)return;stop();timer=setInterval(function(){go(cur+1);},9000);}
  nodes.forEach(function(b,k){b.addEventListener('click',function(){go(k);stop();});});
  dots.forEach(function(d,k){d.addEventListener('click',function(){go(k);stop();});});
  [].forEach.call(sec.querySelectorAll('.story-arrow'),function(b){b.addEventListener('click',function(){go(cur+parseInt(b.getAttribute('data-dir'),10));stop();});});
  sec.addEventListener('mouseenter',stop);sec.addEventListener('mouseleave',start);sec.addEventListener('focusin',stop);
  go(0);start();
})();</script><?php endif; ?>

<?php if (nufa_show('about', 'vision')): $__vm = [['Our Vision', 'compass', cms_t('about', 'vision_text'), 'Next: our mission'], ['Our Mission', 'star', cms_t('about', 'mission_text'), 'Back to our vision']]; ?><section class="section section--soft vm-sec" id="vision">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Direction</div><h2>Vision &amp; Mission</h2><p>Where we are going, and how we will get there. Choose a hexagon.</p></div>
    <div class="vm-stage" data-reveal>
      <div class="vm-hexes" role="tablist" aria-label="Vision and mission">
        <?php foreach ($__vm as $__i => [$__t, $__ic]): ?>
        <button type="button" class="vm-hex vm-hex--<?php echo $__i; ?><?php echo $__i === 0 ? ' is-active' : ''; ?>" role="tab" id="vmtab<?php echo $__i; ?>" aria-controls="vmpanel" aria-selected="<?php echo $__i === 0 ? 'true' : 'false'; ?>" data-i="<?php echo $__i; ?>"><span class="vm-hex-in"><i><?php echo nufa_icon($__ic, 30); ?></i><b><?php echo $__t; ?></b></span></button>
        <?php endforeach; ?>
      </div>
      <div class="vm-view" id="vmpanel" role="tabpanel" aria-live="polite">
        <?php foreach ($__vm as $__i => [$__t, $__ic, $__txt, $__next]): ?>
        <article class="vm-slide<?php echo $__i === 0 ? ' is-active' : ''; ?>" data-i="<?php echo $__i; ?>">
          <span class="vm-kick"><?php echo $__i === 0 ? 'Where we are going' : 'How we get there'; ?></span>
          <h3><?php echo $__t; ?></h3>
          <p><?php echo $__txt; ?></p>
          <?php if ($draft): ?><span class="draft-flag">Draft — pending leadership approval</span><?php endif; ?>
          <button type="button" class="vm-next" data-go="<?php echo 1 - $__i; ?>"><?php echo $__next; ?> <span aria-hidden="true">&rarr;</span></button>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<script>(function(){
  var sec=document.getElementById('vision');if(!sec)return;
  var hex=[].slice.call(sec.querySelectorAll('.vm-hex')),sl=[].slice.call(sec.querySelectorAll('.vm-slide')),stage=sec.querySelector('.vm-stage'),timer=null,cur=0;
  var reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  function go(i){cur=i;hex.forEach(function(x,k){x.classList.toggle('is-active',k===i);x.setAttribute('aria-selected',k===i?'true':'false');});sl.forEach(function(x,k){x.classList.toggle('is-active',k===i);});stage.setAttribute('data-cur',i);}
  function stop(){if(timer){clearInterval(timer);timer=null;}}
  function start(){if(reduce)return;stop();timer=setInterval(function(){go(1-cur);},11000);}
  hex.forEach(function(b,k){b.addEventListener('click',function(){go(k);stop();});b.addEventListener('keydown',function(e){if(e.key==='ArrowRight'||e.key==='ArrowLeft'){go(1-cur);hex[cur].focus();stop();}});});
  [].forEach.call(sec.querySelectorAll('.vm-next'),function(b){b.addEventListener('click',function(){go(parseInt(b.getAttribute('data-go'),10));stop();});});
  sec.addEventListener('mouseenter',stop);sec.addEventListener('mouseleave',start);sec.addEventListener('focusin',stop);
  go(0);start();
})();</script>
</section><?php endif; ?>

<?php if (nufa_show('about', 'values')): $__vn = 7; ?><section class="section values-sec" id="values">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">What guides us</div><h2>Our Core Values</h2><p>Seven values keep us on course. They keep circling, so tap or hover over one to stop and read it.</p></div>
    <div class="val-orbit" data-reveal style="--n:<?php echo $__vn; ?>">
      <span class="val-ring" aria-hidden="true"></span><span class="val-ring val-ring--2" aria-hidden="true"></span>
      <div class="val-track" role="tablist" aria-label="Our core values">
        <?php for ($i = 1; $i <= $__vn; $i++): ?>
        <button type="button" class="val-node<?php echo $i === 1 ? ' is-active' : ''; ?>" role="tab" aria-selected="<?php echo $i === 1 ? 'true' : 'false'; ?>" aria-controls="valhub" data-i="<?php echo $i - 1; ?>" aria-label="<?php echo htmlspecialchars(strip_tags((string)cms_t('about', "val{$i}_title")), ENT_QUOTES, 'UTF-8'); ?>" style="--a:<?php echo round(-90 + ($i - 1) * 360 / $__vn, 3); ?>deg"><span class="val-dot"><?php echo nufa_icon($valueIcons[$i - 1], 26); ?></span></button>
        <?php endfor; ?>
      </div>
      <div class="val-hub" id="valhub" role="tabpanel" aria-live="polite">
        <?php for ($i = 1; $i <= $__vn; $i++): ?>
        <div class="val-card<?php echo $i === 1 ? ' is-active' : ''; ?>" data-i="<?php echo $i - 1; ?>"><span class="val-num"><?php echo str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?> / <?php echo str_pad((string)$__vn, 2, '0', STR_PAD_LEFT); ?></span><i class="val-ico"><?php echo nufa_icon($valueIcons[$i - 1], 30); ?></i><h3><?php echo cms_t('about', "val{$i}_title"); ?></h3><p><?php echo cms_t('about', "val{$i}_text"); ?></p></div>
        <?php endfor; ?>
      </div>
    </div>
  </div>
<script>(function(){
  var sec=document.getElementById('values');if(!sec)return;
  var orbit=sec.querySelector('.val-orbit'),nodes=[].slice.call(sec.querySelectorAll('.val-node')),cards=[].slice.call(sec.querySelectorAll('.val-card')),n=nodes.length,cur=0,timer=null;
  var reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  function go(i){cur=(i+n)%n;nodes.forEach(function(x,k){x.classList.toggle('is-active',k===cur);x.setAttribute('aria-selected',k===cur?'true':'false');});cards.forEach(function(x,k){x.classList.toggle('is-active',k===cur);});}
  function stop(){if(timer){clearInterval(timer);timer=null;}}
  function start(){if(reduce)return;stop();timer=setInterval(function(){go(cur+1);},4200);}
  nodes.forEach(function(b,k){b.addEventListener('mouseenter',function(){go(k);});b.addEventListener('focus',function(){go(k);});b.addEventListener('click',function(){go(k);orbit.classList.add('is-held');stop();});});
  orbit.addEventListener('mouseenter',function(){orbit.classList.add('is-paused');stop();});
  orbit.addEventListener('mouseleave',function(){orbit.classList.remove('is-paused');if(!orbit.classList.contains('is-held'))start();});
  document.addEventListener('click',function(e){if(!e.target.closest('.val-orbit')&&orbit.classList.contains('is-held')){orbit.classList.remove('is-held');start();}});
  go(0);start();
})();</script>
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
        <?php if (!empty($m['bio'])): ?><p style="color:var(--muted-on-dark)"><?php echo ui_e(ui_excerpt($m['bio'], 140)); ?></p><a class="leader-link" href="profile.php?id=<?php echo (int)$m['id']; ?>" target="_blank" rel="noopener">Read profile &#8599;</a><?php endif; ?>
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
