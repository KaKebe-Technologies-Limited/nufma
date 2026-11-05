<?php
/** Full profile of a leadership / team member (opened from every "Read full profile" button). */
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/db.php";
$id = (int)($_GET['id'] ?? 0);
$st = $conn->prepare("SELECT * FROM team_members WHERE id=? AND is_active=1"); $st->bind_param("i", $id); $st->execute();
$m = $st->get_result()->fetch_assoc();
if (!$m) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$isSacco = in_array($m['category'], ['SACCO Governance', 'SACCO Management', 'SACCO Leadership'], true);
$isAwards = $m['category'] === 'Awards Technical Team';
$backUrl = $isSacco ? 'sacco.php#governance' : ($isAwards ? 'awards.php#team' : 'about.php#leadership');
$backLbl = $isSacco ? 'NUFA SACCO leadership' : ($isAwards ? 'Awards technical team' : 'Leadership & governance');
$photo = '';
foreach (['', 'uploads/team/'] as $pre) if (!empty($m['photo']) && is_file(__DIR__ . '/assets/images/' . $pre . $m['photo'])) { $photo = $pre . $m['photo']; break; }
$soc = []; foreach (['linkedin_url' => ['LinkedIn', 'linkedin'], 'x_url' => ['X', 'x'], 'facebook_url' => ['Facebook', 'facebook'], 'instagram_url' => ['Instagram', 'instagram']] as $k => [$l, $ic]) if (!empty($m[$k])) $soc[] = [$m[$k], $l, $ic];
$bio = trim((string)($m['bio'] ?? ''));
$pageTitle = $m['name'] . ($m['role'] ? ' — ' . $m['role'] : '') . " | NUFA";
$pageDesc = ui_excerpt($bio !== '' ? $bio : ($m['name'] . ', ' . $m['role']), 155);
$activeNav = $isSacco ? "sacco" : "about"; $canonicalPath = "profile.php?id=" . $id;
if ($photo) $ogImage = "assets/images/" . $photo;
include __DIR__ . "/includes/header.php";
$others = $conn->query("SELECT id, name, role, photo FROM team_members WHERE is_active=1 AND id<>$id AND category='" . $conn->real_escape_string($m['category']) . "' ORDER BY sort_order, id LIMIT 4")->fetch_all(MYSQLI_ASSOC);
echo ui_page_hero($m['category'] !== '' ? $m['category'] : 'NUFA', $m['name'], $m['role'], 'gallery/2026/nufa26-35.jpg', [[$backLbl, $backUrl], [$m['name']]]);
?>
<style>
.pf{display:grid;grid-template-columns:minmax(260px,380px) 1fr;gap:clamp(24px,4vw,56px);align-items:start}
.pf-photo{position:sticky;top:120px;background:#fff;border:1px solid #ececec;border-radius:22px;overflow:hidden;box-shadow:0 18px 44px rgba(0,0,0,.1)}
.pf-photo .ph{aspect-ratio:4/4.6;background:linear-gradient(135deg,#f3efe9,#e6e0d6);display:flex;align-items:center;justify-content:center}
.pf-photo img{width:100%;height:100%;object-fit:cover;object-position:center 15%;display:block}.pf-photo .ph span{font-size:5rem;font-weight:800;color:#c9c2b6}
.pf-photo::after{content:"";display:block;height:6px;background:linear-gradient(90deg,#FF7900,#D50000)}
.pf-card{padding:18px 20px 20px}.pf-card h2{font-size:1.25rem;margin:0}.pf-card .role{margin:4px 0 0;font-size:.8rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#D50000}
.pf-soc{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}.pf-soc a{width:40px;height:40px;border-radius:50%;border:1px solid #e3e3e3;display:flex;align-items:center;justify-content:center;color:#1c1c1c;transition:.2s}.pf-soc a:hover{background:#D50000;border-color:#D50000;color:#fff;transform:translateY(-2px)}
.pf-bio h3{font-size:1.4rem;margin:0 0 16px}.pf-bio p{font-size:1.04rem;line-height:1.85;color:#444;margin:0 0 16px;text-align:left}
.pf-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:26px}
.pf-more{margin-top:50px}.pf-more h3{font-size:1.05rem;margin:0 0 14px}.pf-more .row{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
.pf-more a{display:flex;gap:12px;align-items:center;background:#fff;border:1px solid #ececec;border-radius:16px;padding:12px;color:#1c1c1c;transition:.2s}.pf-more a:hover{border-color:#D50000;transform:translateY(-3px)}
.pf-more img,.pf-more .ini{width:48px;height:48px;border-radius:50%;object-fit:cover;background:#eee;display:flex;align-items:center;justify-content:center;font-weight:800;color:#aaa;flex:none}.pf-more b{display:block;font-size:.92rem}.pf-more small{color:#777}
@media(max-width:860px){.pf{grid-template-columns:1fr}.pf-photo{position:static;max-width:420px}}
</style>
<section class="section">
  <div class="container">
    <div class="pf">
      <aside class="pf-photo" data-reveal>
        <div class="ph"><?php if ($photo): ?><img src="assets/images/<?php echo ui_e($photo); ?>" alt="Portrait of <?php echo ui_e($m['name']); ?>"><?php else: ?><span><?php echo ui_e(mb_strtoupper(mb_substr($m['name'], 0, 1))); ?></span><?php endif; ?></div>
        <div class="pf-card"><h2><?php echo ui_e($m['name']); ?></h2><p class="role"><?php echo ui_e($m['role']); ?></p>
          <?php if ($soc): ?><div class="pf-soc"><?php foreach ($soc as [$u, $l, $ic]): ?><a href="<?php echo ui_e($u); ?>" target="_blank" rel="noopener" aria-label="<?php echo ui_e($m['name'] . ' on ' . $l); ?>"><?php echo nufa_icon($ic, 18); ?></a><?php endforeach; ?></div><?php endif; ?></div>
      </aside>
      <div class="pf-bio" data-reveal>
        <h3>Profile</h3>
        <?php echo $bio !== '' ? ui_paragraphs($bio) : '<p class="muted">A full profile for ' . ui_e($m['name']) . ' will be published soon.</p>'; ?>
        <div class="pf-actions"><a class="btn btn-gold" href="<?php echo ui_e($backUrl); ?>">&larr; Back to <?php echo ui_e($backLbl); ?></a><button type="button" class="btn btn-outline-dark" onclick="navigator.clipboard&&navigator.clipboard.writeText(location.href);this.textContent='Link copied'">Copy link to this profile</button></div>
      </div>
    </div>
    <?php if ($others): ?><div class="pf-more"><h3>More from <?php echo ui_e($backLbl); ?></h3><div class="row">
      <?php foreach ($others as $o): $op = (!empty($o['photo']) && is_file(__DIR__ . '/assets/images/' . $o['photo'])) ? $o['photo'] : ''; ?>
      <a href="profile.php?id=<?php echo (int)$o['id']; ?>"><?php if ($op): ?><img src="assets/images/<?php echo ui_e($op); ?>" alt="" loading="lazy"><?php else: ?><span class="ini"><?php echo ui_e(mb_strtoupper(mb_substr($o['name'], 0, 1))); ?></span><?php endif; ?><span><b><?php echo ui_e($o['name']); ?></b><small><?php echo ui_e($o['role']); ?></small></span></a>
      <?php endforeach; ?></div></div><?php endif; ?>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
