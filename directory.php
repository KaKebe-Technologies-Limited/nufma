<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/export.php";
require_once __DIR__ . "/includes/directory.php";

$D = dir_settings($conn);
$guilds = $conn->query("SELECT id, name FROM guilds WHERE is_active=1 ORDER BY sort_order, name")->fetch_all(MYSQLI_ASSOC);
$guild = (int)($_GET['guild'] ?? 0); $region = in_array($_GET['region'] ?? '', dir_regions(), true) ? $_GET['region'] : ''; $q = trim($_GET['q'] ?? '');
$group = ($_GET['group'] ?? 'guild') === 'region' ? 'region' : 'guild';
$rows = $D['dir_enabled'] === '1' ? dir_members($conn, ['guild' => $guild, 'region' => $region, 'q' => $q, 'listed' => true, 'active' => true, 'order' => $group]) : [];

$fmt = $_GET['fmt'] ?? '';
if ($D['dir_enabled'] === '1' && in_array($fmt, ['pdf', 'xlsx', 'docx', 'csv'], true)) {
    [$h, $ex] = dir_public_export(array_slice($rows, 0, 3000));
    $sub = trim(($guild ? 'Guild: ' . (array_column($guilds, 'name', 'id')[$guild] ?? '') . '  ' : '') . ($region ? 'Region: ' . $region . '  ' : '') . count($ex) . ' filmmaker(s)');
    nufa_export_send($fmt, 'NUFA filmmakers directory', $h, $ex, 'nufa-filmmakers-directory', $sub);
}

$pageTitle = "Filmmakers Directory — NUFA"; $pageDesc = "Find Northern Uganda filmmakers by guild and region."; $activeNav = "about"; $canonicalPath = "directory.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA members', 'Filmmakers Directory', $D['dir_intro'], 'gallery/2026/nufa26-26.jpg', [['Home', 'index.php'], ['Filmmakers Directory']]);
$per = 60; $total = count($rows); $pages = max(1, (int)ceil($total / $per)); $pg = min($pages, max(1, (int)($_GET['p'] ?? 1)));
$show = array_slice($rows, ($pg - 1) * $per, $per);
$qs = fn($extra = []) => '?' . http_build_query(array_filter(array_merge(['guild' => $guild ?: '', 'region' => $region, 'q' => $q, 'group' => $group], $extra), fn($v) => $v !== '' && $v !== null));
$counts = []; foreach ($rows as $r) { $counts[$r['region']] = ($counts[$r['region']] ?? 0) + 1; }
?>
<style>
.dr-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;background:#fff;border:1px solid #E6E6E6;border-radius:16px;padding:18px;box-shadow:0 8px 26px rgba(0,0,0,.06)}
.dr-bar label{font-size:.75rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#666;display:block;margin-bottom:5px}
.dr-bar select,.dr-bar input{width:100%;padding:11px 12px;border:1px solid #CFCFCF;border-radius:10px;font:inherit;background:#fff}
.dr-sum{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0 6px}.dr-sum a{padding:7px 14px;border-radius:99px;border:1px solid #DDD;font-weight:700;font-size:.84rem;background:#fff;color:#111}.dr-sum a.on{background:var(--nufa-primary,#D50000);border-color:var(--nufa-primary,#D50000);color:#fff}
.dr-h{margin:34px 0 14px;display:flex;align-items:center;gap:12px;font-size:1.15rem}.dr-h::after{content:"";flex:1;height:2px;background:linear-gradient(90deg,var(--nufa-primary,#D50000),transparent)}.dr-h small{font-size:.8rem;color:#777;font-weight:700}
.dr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
.dr-card{background:#fff;border:1px solid #E6E6E6;border-radius:14px;padding:16px 18px;display:flex;gap:14px}
.dr-av{flex:none;width:46px;height:46px;border-radius:50%;background:#111;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;border:2px solid var(--nufa-primary,#D50000)}
.dr-card h3{font-size:1rem;margin:0 0 2px}.dr-card p{margin:0;font-size:.84rem;color:#555;line-height:1.45}.dr-tag{display:inline-block;font-size:.7rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--nufa-primary,#D50000);margin-bottom:4px}
.dr-card a{font-size:.82rem;text-decoration:underline}
.dr-dl{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:14px 0 0}
</style>
<section class="section"><div class="container-wide" style="margin-inline:var(--gutter)">
<?php if ($D['dir_enabled'] !== '1'): ?>
  <div class="empty-state"><h3>The directory is not available right now</h3><p>Please check back soon.</p></div>
<?php else: ?>
  <form method="get" class="dr-bar" role="search">
    <div><label for="d-guild">Guild / craft</label><select id="d-guild" name="guild"><option value="">All guilds</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '"' . ($guild === (int)$g['id'] ? ' selected' : '') . '>' . ui_e($g['name']) . '</option>'; ?></select></div>
    <div><label for="d-region">Region</label><select id="d-region" name="region"><option value="">All regions</option><?php foreach (dir_regions() as $r) echo '<option' . ($region === $r ? ' selected' : '') . '>' . ui_e($r) . '</option>'; ?></select></div>
    <div><label for="d-q">Name, district or craft</label><input id="d-q" name="q" type="search" value="<?php echo ui_e($q); ?>" placeholder="Search…"></div>
    <div><label for="d-g">Group list by</label><select id="d-g" name="group"><option value="guild"<?php echo $group === 'guild' ? ' selected' : ''; ?>>Guild, then region</option><option value="region"<?php echo $group === 'region' ? ' selected' : ''; ?>>Region, then guild</option></select></div>
    <div><button class="btn btn-gold" type="submit" style="width:100%">Show list</button></div>
  </form>

  <div class="dr-sum"><a href="directory.php" class="<?php echo !$region && !$guild && $q === '' ? 'on' : ''; ?>">Everyone</a><?php foreach ($counts as $r => $n): ?><a href="<?php echo ui_e($qs(['region' => $r, 'p' => ''])); ?>" class="<?php echo $region === $r ? 'on' : ''; ?>"><?php echo ui_e($r); ?> · <?php echo $n; ?></a><?php endforeach; ?></div>
  <div class="dr-dl"><b><?php echo $total; ?> filmmaker<?php echo $total === 1 ? '' : 's'; ?></b><span style="color:#777">— download this list:</span>
    <?php foreach (['pdf' => 'PDF', 'xlsx' => 'Excel', 'docx' => 'Word', 'csv' => 'CSV'] as $k => $l): ?><a class="btn btn-outline btn-sm" href="<?php echo ui_e($qs(['fmt' => $k, 'p' => ''])); ?>"><?php echo $l; ?></a><?php endforeach; ?>
    <button class="btn btn-outline btn-sm" type="button" onclick="window.print()">Print</button></div>

  <?php if (!$show): ?>
    <div class="empty-state" style="margin-top:24px"><h3>No filmmakers found</h3><p>Try a different guild or region. Members appear here once they are approved and have agreed to be listed.</p></div>
  <?php else:
    $last = ''; $open = false;
    foreach ($show as $r):
      $key = $group === 'region' ? $r['region'] . '|' . $r['guild_name'] : $r['guild_name'] . '|' . $r['region'];
      if ($key !== $last): if ($open) echo '</div>'; $last = $key; $open = true; ?>
      <h2 class="dr-h"><?php echo ui_e($group === 'region' ? $r['region'] : $r['guild_name']); ?> <small>· <?php echo ui_e($group === 'region' ? $r['guild_name'] : $r['region']); ?></small></h2><div class="dr-grid">
    <?php endif; ?>
      <article class="dr-card"><span class="dr-av" aria-hidden="true"><?php echo ui_e(mb_strtoupper(mb_substr($r['full_name'], 0, 1))); ?></span>
        <div><span class="dr-tag"><?php echo ui_e($r['guild_name']); ?></span><h3><?php echo ui_e($r['full_name']); ?></h3>
          <p><?php echo ui_e(trim($r['occupation'] . ($r['occupation'] && $r['experience'] ? ' · ' : '') . $r['experience'])); ?></p>
          <p><?php echo ui_e($r['district'] ?: $r['region']); ?> · <?php echo ui_e($r['region']); ?></p>
          <?php if ($r['portfolio_url'] && preg_match('#^https?://#i', $r['portfolio_url'])): ?><p><a href="<?php echo ui_e($r['portfolio_url']); ?>" target="_blank" rel="noopener nofollow">Portfolio ↗</a></p><?php endif; ?></div></article>
    <?php endforeach; if ($open) echo '</div>'; ?>
    <?php if ($pages > 1): ?><div class="filter-bar" style="margin-top:28px;justify-content:center"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="gallery-tab<?php echo $i === $pg ? ' is-active' : ''; ?>" href="<?php echo ui_e($qs(['p' => $i])); ?>"><?php echo $i; ?></a><?php endfor; ?></div><?php endif; ?>
  <?php endif; ?>
  <p class="muted" style="margin-top:26px;font-size:.86rem">Listings show only name, guild, region, craft and portfolio link, and only for members who agreed to be listed. Want to be listed? <a href="membership.php" style="text-decoration:underline">Join NUFA</a>. To contact a filmmaker, <a href="contact.php" style="text-decoration:underline">write to NUFA</a>.</p>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
