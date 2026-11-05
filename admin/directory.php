<?php
$adminTitle = "Filmmakers directory";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/directory.php";

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$guilds = $conn->query("SELECT id, name FROM guilds ORDER BY sort_order, name")->fetch_all(MYSQLI_ASSOC);
$tab = in_array($_GET['tab'] ?? '', ['list', 'report', 'settings'], true) ? $_GET['tab'] : 'list';
$guild = (int)($_GET['guild'] ?? 0); $region = in_array($_GET['region'] ?? '', dir_regions(), true) ? $_GET['region'] : ''; $q = trim($_GET['q'] ?? '');
$show = in_array($_GET['show'] ?? '', ['listed', 'unlisted'], true) ? $_GET['show'] : '';
$flash = ''; $flashType = 'ok';

/* ---- report export: full list with contact details, per guild and region ---- */
if (isset($_GET['fmt']) && isset(NUFA_EXPORT_FORMATS[$_GET['fmt']])) {
    $rows = dir_members($conn, ['guild' => $guild, 'region' => $region, 'q' => $q, 'listed' => ($_GET['only'] ?? '') === 'listed', 'active' => ($_GET['active'] ?? '1') === '1', 'order' => ($_GET['group'] ?? 'guild') === 'region' ? 'region' : 'guild']);
    $ex = [];
    foreach ($rows as $r) $ex[] = [$r['guild_name'], $r['region'], $r['full_name'], $r['reg_no'], $r['gender'], $r['phone'], $r['email'], $r['district'], $r['occupation'], $r['experience'], $r['portfolio_url'], $r['valid_until'], $r['in_directory'] ? 'Yes' : 'No'];
    nufa_export_send($_GET['fmt'], 'NUFA filmmakers by guild and region', ['Guild', 'Region', 'Name', 'Membership ID', 'Gender', 'Phone', 'Email', 'District', 'Occupation', 'Experience', 'Portfolio', 'Valid until', 'In public directory'], $ex, 'nufa-filmmakers-by-guild-region', count($ex) . ' member(s) · exported ' . date('d M Y H:i'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $act = $_POST['action'] ?? '';
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
        if (($act === 'list' || $act === 'unlist') && $ids) { $conn->query("UPDATE members SET in_directory=" . ($act === 'list' ? 1 : 0) . " WHERE id IN (" . implode(',', $ids) . ")"); $flash = count($ids) . ' member(s) ' . ($act === 'list' ? 'added to' : 'removed from') . ' the public directory.'; }
        elseif ($act === 'list' || $act === 'unlist') { $flash = 'Tick at least one member.'; $flashType = 'warn'; }
        elseif ($act === 'list_all') { $conn->query("UPDATE members SET in_directory=1 WHERE status='approved' AND (valid_until IS NULL OR valid_until >= CURDATE())"); $flash = 'All approved, active members are now listed.'; }
        elseif ($act === 'unlist_all') { $conn->query("UPDATE members SET in_directory=0"); $flash = 'Everyone has been removed from the public directory.'; }
        elseif ($act === 'settings') { mem_save_setting($conn, 'dir_enabled', !empty($_POST['dir_enabled']) ? '1' : '0'); mem_save_setting($conn, 'dir_intro', trim($_POST['dir_intro'] ?? '')); $flash = 'Directory settings saved.'; $tab = 'settings'; }
    }
}
$D = dir_settings($conn);
$all = dir_members($conn, ['active' => true]);
$nListed = 0; foreach ($all as $r) if ($r['in_directory']) $nListed++;
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="member-dashboard.php">Dashboard</a><a href="members.php">Applications</a><a href="member-add.php">Register &amp; import</a><a href="member-cards.php">Cards</a><a href="directory.php" class="active">Directory</a><a href="guilds.php">Guilds</a><a href="membership-settings.php">Membership settings</a></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $e($flash); ?></div><?php endif; ?>
<div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="admin-stat"><b><?php echo count($all); ?></b><span>Approved, active members</span></div>
  <div class="admin-stat"><b><?php echo $nListed; ?></b><span>Listed in the public directory</span></div>
  <div class="admin-stat"><b><?php echo $D['dir_enabled'] === '1' ? 'On' : 'Off'; ?></b><span>Public directory page · <a href="../directory.php" target="_blank">view ↗</a></span></div>
</div>
<div class="mem-tabs"><a href="directory.php?tab=list" class="<?php echo $tab === 'list' ? 'active' : ''; ?>">Who is listed</a><a href="directory.php?tab=report" class="<?php echo $tab === 'report' ? 'active' : ''; ?>">Lists by guild &amp; region</a><a href="directory.php?tab=settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">Settings</a></div>

<?php if ($tab === 'settings'): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Directory settings</h2></div>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="settings">
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="dir_enabled" value="1" <?php echo $D['dir_enabled'] === '1' ? 'checked' : ''; ?>> Show the public Filmmakers Directory page on the website</label>
    <div class="field"><label>Introduction shown at the top of the page</label><textarea name="dir_intro" style="min-height:90px"><?php echo $e($D['dir_intro']); ?></textarea></div>
    <p class="admin-help">Only approved, non-expired members whom you (or they, on the registration form) have marked “Show in public directory” appear. Phone numbers, emails and ID numbers are never shown publicly.</p>
    <button class="admin-btn admin-btn-brand">Save settings</button>
  </form>
  <form method="post" style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap"><?php echo mem_csrf_field(); ?>
    <button class="admin-btn admin-btn-line" name="action" value="list_all" data-confirm="List every approved, active member in the public directory?">List all approved members</button>
    <button class="admin-btn admin-btn-danger" name="action" value="unlist_all" data-confirm="Remove everyone from the public directory?">Remove everyone</button></form>
</div>

<?php elseif ($tab === 'report'):
    $regions = dir_regions(); $matrix = []; $listedM = [];
    foreach ($all as $r) { $matrix[$r['guild_name']][$r['region']] = ($matrix[$r['guild_name']][$r['region']] ?? 0) + 1; if ($r['in_directory']) $listedM[$r['guild_name']][$r['region']] = ($listedM[$r['guild_name']][$r['region']] ?? 0) + 1; }
    ksort($matrix);
    $link = fn($g, $rg) => 'directory.php?tab=list&guild=' . urlencode((string)(array_column($guilds, 'id', 'name')[$g] ?? '')) . '&region=' . urlencode($rg);
?>
<div class="admin-card"><div class="admin-card-head"><h2>Filmmakers per guild and region</h2><span class="admin-help">Approved, active members. Numbers in brackets are those listed publicly.</span></div>
  <div style="overflow-x:auto"><table class="admin-table" data-noexport><thead><tr><th>Guild</th><?php foreach ($regions as $rg) echo '<th>' . $e($rg) . '</th>'; ?><th>Total</th></tr></thead><tbody>
  <?php $colT = array_fill_keys($regions, 0); $colL = array_fill_keys($regions, 0); foreach ($matrix as $g => $cells): $t = 0; $tl = 0; ?>
    <tr><td><b><?php echo $e($g); ?></b></td><?php foreach ($regions as $rg): $n = $cells[$rg] ?? 0; $l = $listedM[$g][$rg] ?? 0; $t += $n; $tl += $l; $colT[$rg] += $n; $colL[$rg] += $l; ?><td><?php echo $n ? '<a href="' . $e($link($g, $rg)) . '">' . $n . '</a> <span style="color:#888">(' . $l . ')</span>' : '—'; ?></td><?php endforeach; ?><td><b><?php echo $t; ?></b> <span style="color:#888">(<?php echo $tl; ?>)</span></td></tr>
  <?php endforeach; if (!$matrix): ?><tr><td colspan="<?php echo count($regions) + 2; ?>" style="color:var(--text-muted)">No approved members yet.</td></tr><?php else: ?>
    <tr><td><b>All guilds</b></td><?php foreach ($regions as $rg) echo '<td><b>' . $colT[$rg] . '</b> <span style="color:#888">(' . $colL[$rg] . ')</span></td>'; ?><td><b><?php echo array_sum($colT); ?></b> <span style="color:#888">(<?php echo array_sum($colL); ?>)</span></td></tr><?php endif; ?>
  </tbody></table></div></div>
<div class="admin-card"><div class="admin-card-head"><h2>Generate a filmmakers list</h2><span class="admin-help">Full details including contacts — for NUFA's own use. The public page only shows the public details.</span></div>
  <form method="get" class="admin-form"><input type="hidden" name="tab" value="report">
    <div class="admin-form-grid">
      <div class="field"><label>Guild</label><select name="guild"><option value="">All guilds</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '"' . ($guild === (int)$g['id'] ? ' selected' : '') . '>' . $e($g['name']) . '</option>'; ?></select></div>
      <div class="field"><label>Region</label><select name="region"><option value="">All regions</option><?php foreach ($regions as $rg) echo '<option' . ($region === $rg ? ' selected' : '') . '>' . $e($rg) . '</option>'; ?></select></div>
      <div class="field"><label>Arrange by</label><select name="group"><option value="guild">Guild, then region</option><option value="region">Region, then guild</option></select></div>
      <div class="field"><label>Include</label><select name="only"><option value="">All approved members</option><option value="listed">Only those in the public directory</option></select></div>
      <div class="field"><label>Memberships</label><select name="active"><option value="1">Active only</option><option value="0">Including expired</option></select></div>
    </div>
    <p style="display:flex;flex-wrap:wrap;gap:8px"><?php foreach (NUFA_EXPORT_FORMATS as $k => $l): ?><button class="admin-btn <?php echo in_array($k, ['xlsx', 'pdf', 'docx', 'csv'], true) ? 'admin-btn-brand' : 'admin-btn-line'; ?> admin-btn-sm" name="fmt" value="<?php echo $k; ?>">⬇ <?php echo $e($l); ?></button><?php endforeach; ?></p>
  </form></div>

<?php else:
    $rows = dir_members($conn, ['guild' => $guild, 'region' => $region, 'q' => $q, 'active' => true, 'order' => 'guild']);
    if ($show === 'listed') $rows = array_values(array_filter($rows, fn($r) => $r['in_directory']));
    if ($show === 'unlisted') $rows = array_values(array_filter($rows, fn($r) => !$r['in_directory']));
    $total = count($rows); $per = 100; $pages = max(1, (int)ceil($total / $per)); $pg = min($pages, max(1, (int)($_GET['p'] ?? 1))); $rows = array_slice($rows, ($pg - 1) * $per, $per);
    $base = 'directory.php?tab=list&guild=' . $guild . '&region=' . urlencode($region) . '&q=' . urlencode($q) . '&show=' . $show;
?>
<div class="admin-card"><div class="admin-card-head"><h2>Members (<?php echo $total; ?>)</h2><span class="admin-help">Tick people and choose whether they appear on the public directory.</span></div>
  <form method="get" class="mem-filter"><input type="hidden" name="tab" value="list">
    <select name="guild"><option value="">All guilds</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '"' . ($guild === (int)$g['id'] ? ' selected' : '') . '>' . $e($g['name']) . '</option>'; ?></select>
    <select name="region"><option value="">All regions</option><?php foreach (dir_regions() as $rg) echo '<option' . ($region === $rg ? ' selected' : '') . '>' . $e($rg) . '</option>'; ?></select>
    <select name="show"><option value="">Listed and not listed</option><option value="listed"<?php echo $show === 'listed' ? ' selected' : ''; ?>>Listed only</option><option value="unlisted"<?php echo $show === 'unlisted' ? ' selected' : ''; ?>>Not listed</option></select>
    <input type="search" name="q" placeholder="Search name, district or craft" value="<?php echo $e($q); ?>"><button class="admin-btn admin-btn-brand">Filter</button></form>
  <form method="post" action="<?php echo $e($base . '&p=' . $pg); ?>"><?php echo mem_csrf_field(); ?>
    <p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center"><button class="admin-btn admin-btn-brand admin-btn-sm" name="action" value="list">Add selected to directory</button><button class="admin-btn admin-btn-line admin-btn-sm" name="action" value="unlist">Remove selected</button>
      <label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" id="dAll"> Select all on this page</label></p>
    <div style="overflow-x:auto"><table class="admin-table" data-noexport><thead><tr><th></th><th>Name</th><th>Guild</th><th>Region</th><th>District</th><th>Craft</th><th>Public?</th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--text-muted)">No approved members match.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?><tr><td><input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>" class="dRow"></td><td><b><?php echo $e($r['full_name']); ?></b></td><td><?php echo $e($r['guild_name']); ?></td><td><?php echo $e($r['region']); ?></td><td><?php echo $e($r['district']); ?></td><td><?php echo $e($r['occupation']); ?></td><td><?php echo $r['in_directory'] ? '<span class="admin-badge">Listed</span>' : '<span class="admin-badge gray">No</span>'; ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="member-view.php?id=<?php echo $r['id']; ?>">Open</a></td></tr><?php endforeach; ?>
    </tbody></table></div></form>
  <?php if ($pages > 1): ?><div class="mem-tabs" style="margin-top:14px"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?php echo $e($base . '&p=' . $i); ?>" class="<?php echo $i === $pg ? 'active' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?></div><?php endif; ?>
  <script>document.getElementById('dAll')?.addEventListener('change',function(){var v=this.checked;document.querySelectorAll('.dRow').forEach(function(c){c.checked=v})});</script>
</div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
