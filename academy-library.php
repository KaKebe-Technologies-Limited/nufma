<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn);
$pageTitle = "Study Library — Free Film Learning Resources | NUFA Academy";
$pageDesc  = "Free courses, video channels, script libraries, software, templates and sound libraries for filmmakers, collected by NUFA Academy.";
$activeNav = "academy"; $canonicalPath = "academy-library.php";
include __DIR__ . "/includes/header.php";
$rows = $conn->query("SELECT * FROM academy_resources WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$cats = []; foreach ($rows as $r) if ($r['category'] !== '') $cats[$r['category']] = ($cats[$r['category']] ?? 0) + 1;
$types = []; foreach ($rows as $r) $types[$r['type']] = true;
echo ui_page_hero('NUFA Academy', 'Study Library', 'Free and low-cost learning resources from around the world — courses, channels, script libraries, software, templates and sound libraries — organised by subject.', cms_img_raw('gallery', 'hero_image'), [['NUFA Academy', 'academy.php'], ['Study Library']],
    '<a href="#list" class="btn btn-gold">Browse resources</a><a href="academy.php#courses" class="btn btn-outline">NUFA courses</a>');
?>
<section class="section" id="list"><div class="container">
  <div class="op-note" data-reveal><b>Please note.</b> These are third-party resources that NUFA has collected for learners. Availability, prices and licences change, so always check the provider’s page. Resources marked “Checked” were opened and confirmed on the date shown; others are well-known sources still to be checked. NUFA does not own or endorse their content.</div>
  <div class="op-tools">
    <div class="filter-bar"><button class="op-tab is-active" type="button" data-cat="">All <small><?php echo count($rows); ?></small></button><?php foreach ($cats as $k => $n): ?><button class="op-tab" type="button" data-cat="<?php echo ui_e($k); ?>"><?php echo ui_e($k); ?> <small><?php echo $n; ?></small></button><?php endforeach; ?></div>
    <div class="op-search"><select id="rs-type" aria-label="Type"><option value="">All types</option><?php foreach (array_keys($types) as $t) echo '<option>' . ui_e($t) . '</option>'; ?></select><input type="search" id="rs-q" placeholder="Search resources" aria-label="Search resources"></div>
  </div>
  <?php if (!$rows): ?><div class="empty-state"><h3>Resources coming soon</h3></div><?php else: ?>
  <div class="op-grid" id="rs-grid">
    <?php foreach ($rows as $r): ?>
    <article class="op-card" data-cat="<?php echo ui_e($r['category']); ?>" data-type="<?php echo ui_e($r['type']); ?>" data-hay="<?php echo ui_e(strtolower($r['title'] . ' ' . $r['provider'] . ' ' . $r['description'] . ' ' . $r['category'])); ?>">
      <div class="op-top"><span class="op-cat"><?php echo ui_e($r['type']); ?></span><span class="op-st"><?php echo ui_e($r['cost']); ?></span></div>
      <h3><?php echo ui_e($r['title']); ?></h3><p class="op-org"><?php echo ui_e($r['provider']); ?> · <?php echo ui_e($r['category']); ?> · <?php echo ui_e($r['level']); ?></p>
      <p class="op-sum"><?php echo ui_e($r['description']); ?></p>
      <div class="op-foot"><a class="btn btn-gold btn-sm" href="<?php echo ui_e($r['url']); ?>" target="_blank" rel="noopener noreferrer">Open resource ↗</a><?php if ($r['verified_on']): ?><span class="op-ver">Checked <?php echo ui_e(date('j M Y', strtotime($r['verified_on']))); ?></span><?php endif; ?></div>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="empty-state" id="rs-none" hidden>No resources match. Try another filter.</p>
  <?php endif; ?>
</div></section>
<script>
(function(){var cat='',cards=[].slice.call(document.querySelectorAll('.op-card')),q=document.getElementById('rs-q'),ty=document.getElementById('rs-type'),none=document.getElementById('rs-none');
function run(){var t=(q.value||'').toLowerCase().trim(),n=0;cards.forEach(function(c){var ok=(!cat||c.dataset.cat===cat)&&(!ty.value||c.dataset.type===ty.value)&&(!t||c.dataset.hay.indexOf(t)>-1);c.hidden=!ok;if(ok)n++;});if(none)none.hidden=n>0;}
document.querySelectorAll('.op-tab').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.op-tab').forEach(function(x){x.classList.remove('is-active')});b.classList.add('is-active');cat=b.dataset.cat;run();});});
q.addEventListener('input',run);ty.addEventListener('change',run);})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
