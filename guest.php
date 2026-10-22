<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/guests.php";
require_once __DIR__ . "/includes/db.php";
guests_install($conn);
$st = $conn->prepare("SELECT * FROM vip_guests WHERE id=? AND is_active=1");
$gid = (int)($_GET['id'] ?? 0); $st->bind_param("i", $gid); $st->execute();
$g = $st->get_result()->fetch_assoc();
if (!$g) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$ed = guest_editions($g);
$line = trim($g['title'] . ($g['title'] !== '' && $g['organisation'] !== '' ? ', ' : '') . $g['organisation']);
$pageTitle = $g['name'] . " — Distinguished Guest | NUFA Awards";
$pageDesc  = ui_excerpt($g['profile'] ?: ($line . ($ed ? ' — attended ' . implode(', ', $ed) : '')), 155);
$activeNav = "guests";
$canonicalPath = "guest.php?id=" . $gid;
if (guest_photo_ok($g)) $ogImage = "assets/images/" . $g['photo'];
include __DIR__ . "/includes/header.php";
$others = $conn->query("SELECT id, name, title, organisation, photo FROM vip_guests WHERE is_active=1 AND id<>$gid ORDER BY is_featured DESC, RAND() LIMIT 4")->fetch_all(MYSQLI_ASSOC);
echo ui_page_hero('Distinguished Guest', $g['name'], $line, cms_img_raw('gallery', 'hero_image'), [['About NUFA', 'about.php'], ['Distinguished Guests', 'guests.php'], [$g['name']]]);
?>
<section class="section">
  <div class="container">
    <div class="guest-hero">
      <div class="gs-photo"><?php if (guest_photo_ok($g)): ?><img src="assets/images/<?php echo ui_e($g['photo']); ?>" alt="<?php echo ui_e($g['name']); ?>"><?php else: ?><span><?php echo ui_e(guest_initials($g['name'])); ?></span><?php endif; ?></div>
      <div>
        <span class="gs-cat"><?php echo ui_e($g['category']); ?></span>
        <h2 style="margin:6px 0 4px"><?php echo ui_e($g['name']); ?></h2>
        <?php if ($line !== ''): ?><p class="gs-title" style="font-size:1.05rem"><?php echo ui_e($line); ?></p><?php endif; ?>
        <?php if ($ed): ?><h4 style="margin:22px 0 8px;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:#8A8A8A">Editions attended</h4><div class="gs-eds"><?php foreach ($ed as $x) echo '<span>' . ui_e($x) . '</span>'; ?></div><?php endif; ?>
        <?php if (trim((string)$g['profile']) !== ''): ?><h4 style="margin:26px 0 8px;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:#8A8A8A">Profile</h4><div class="guest-profile"><?php echo ui_e($g['profile']); ?></div><?php endif; ?>
        <p style="margin-top:28px"><a href="guests.php" class="link-arrow" style="transform:none">All distinguished guests</a></p>
      </div>
    </div>
  </div>
</section>
<?php if ($others): ?>
<section class="section section--soft"><div class="container">
  <div class="section-head center"><div class="eyebrow">More guests</div><h2>Other distinguished guests</h2></div>
  <div class="gs-grid"><?php foreach ($others as $o): $t = trim($o['title'] . ($o['title'] !== '' && $o['organisation'] !== '' ? ', ' : '') . $o['organisation']); ?>
    <a class="gs-card" href="guest.php?id=<?php echo (int)$o['id']; ?>"><div class="gs-photo"><?php if (guest_photo_ok($o)): ?><img src="assets/images/<?php echo ui_e($o['photo']); ?>" alt="<?php echo ui_e($o['name']); ?>" loading="lazy"><?php else: ?><span><?php echo ui_e(guest_initials($o['name'])); ?></span><?php endif; ?></div><div class="gs-body"><h3><?php echo ui_e($o['name']); ?></h3><?php if ($t !== ''): ?><p class="gs-title"><?php echo ui_e($t); ?></p><?php endif; ?></div></a>
  <?php endforeach; ?></div>
</div></section>
<?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
