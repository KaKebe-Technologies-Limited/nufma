<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/guests.php";
guests_install($conn);
$pageTitle = "Distinguished Guests & VIPs — NUFA Awards";
$pageDesc  = "The VIPs and distinguished guests who have attended NUFA Awards — their profiles, titles and the editions they joined.";
$activeNav = "guests";
$canonicalPath = "guests.php";
include __DIR__ . "/includes/header.php";

$rows = $conn->query("SELECT * FROM vip_guests WHERE is_active=1 ORDER BY is_featured DESC, sort_order, name")->fetch_all(MYSQLI_ASSOC);
$eds = []; foreach ($rows as $r) foreach (guest_editions($r) as $x) $eds[$x] = true;
krsort($eds);

echo ui_page_hero('About NUFA', 'Distinguished Guests', 'The VIPs, leaders and special guests who have joined us at NUFA Awards — and the editions they attended.', 'gallery/2026/nufa26-35.jpg', [['About NUFA', 'about.php'], ['Distinguished Guests']]);
?>
<section class="section" id="guests">
  <div class="container">
    <?php if (!$rows): ?>
      <div class="empty-state"><h3>Guest profiles coming soon</h3><p>We will list our distinguished guests here once their profiles are confirmed.</p></div>
    <?php else: ?>
    <?php if (count($eds) > 0): ?><div class="gs-filter filter-bar" role="tablist" aria-label="Filter by edition">
      <button class="op-tab is-active" type="button" data-ed="all">All guests <small><?php echo count($rows); ?></small></button>
      <?php foreach (array_keys($eds) as $x): ?><button class="op-tab" type="button" data-ed="<?php echo ui_e($x); ?>"><?php echo ui_e($x); ?></button><?php endforeach; ?>
    </div><?php endif; ?>
    <div class="gs-grid" id="gs-grid">
      <?php foreach ($rows as $g): $ed = guest_editions($g); ?>
      <a class="gs-card" href="guest.php?id=<?php echo (int)$g['id']; ?>" data-eds="<?php echo ui_e('|' . implode('|', $ed) . '|'); ?>">
        <div class="gs-photo"><?php if (guest_photo_ok($g)): ?><img src="assets/images/<?php echo ui_e($g['photo']); ?>" alt="<?php echo ui_e($g['name']); ?>" loading="lazy" decoding="async"><?php else: ?><span><?php echo ui_e(guest_initials($g['name'])); ?></span><?php endif; ?></div>
        <div class="gs-body"><span class="gs-cat"><?php echo ui_e($g['category']); ?></span><h3><?php echo ui_e($g['name']); ?></h3>
          <?php $t = trim($g['title'] . ($g['title'] !== '' && $g['organisation'] !== '' ? ', ' : '') . $g['organisation']); if ($t !== ''): ?><p class="gs-title"><?php echo ui_e($t); ?></p><?php endif; ?>
          <?php if ($ed): ?><div class="gs-eds"><?php foreach ($ed as $x) echo '<span>' . ui_e($x) . '</span>'; ?></div><?php endif; ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<section class="section section--soft"><div class="container">
  <div class="section-head center" data-reveal><div class="eyebrow">NUFA Awards 2026</div><h2>Our guests on the night</h2><p>Leaders, elders, creatives and friends who joined the NUFA26 ceremony.</p></div>
  <?php echo ui_photo_grid([
      ['gallery/2026/nufa26-35.jpg', 'Distinguished guests on stage at NUFA26'],
      ['gallery/2026/nufa26-20.jpg', 'Guests follow the ceremony'],
      ['gallery/2026/nufa26-22.jpg', 'A guest at the NUFA26 ceremony'],
      ['gallery/2026/nufa26-24.jpg', 'Guests enjoy the programme'],
      ['gallery/2026/nufa26-27.jpg', 'Guests in the audience'],
      ['gallery/2026/nufa26-30.jpg', 'Smiles from our guests'],
  ], 'strip'); ?>
</div></section>
<script>
(function(){var tabs=document.querySelectorAll('[data-ed]'),cards=document.querySelectorAll('.gs-card');
tabs.forEach(function(b){b.addEventListener('click',function(){tabs.forEach(function(x){x.classList.remove('is-active')});b.classList.add('is-active');var v=b.dataset.ed;cards.forEach(function(c){c.hidden=!(v==='all'||c.dataset.eds.indexOf('|'+v+'|')>-1);});});});})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
