<?php
$adminTitle = "Registration dashboard";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/membership.php";
$e = fn($v) => htmlspecialchars((string)$v);
$map = mem_subregion_map($conn);
$regions = array_keys($map); $all = array_merge($regions, ['Other / not stated']);
$colour = ['Acholi' => '#F57A0A', 'Karamoja' => '#111111', 'Lango' => '#8A8A8A', 'West Nile' => '#FFB066', 'Other / not stated' => '#D9D9D9'];

// filters
$status = in_array($_GET['status'] ?? '', ['approved', 'pending', 'rejected'], true) ? $_GET['status'] : '';
$period = in_array($_GET['period'] ?? '', ['all', 'year', '12m', '90d', 'custom'], true) ? $_GET['period'] : 'all';
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : ''; $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : '';
$guild = (int)($_GET['guild'] ?? 0);
$where = [];
if ($status) $where[] = "status='$status'";
if ($guild) $where[] = "guild_id=$guild";
if ($period === 'year') $where[] = "created_at >= '" . date('Y') . "-01-01'";
elseif ($period === '12m') $where[] = "created_at >= '" . date('Y-m-01', strtotime('-11 months')) . "'";
elseif ($period === '90d') $where[] = "created_at >= '" . date('Y-m-d', strtotime('-90 days')) . "'";
elseif ($period === 'custom') { if ($from) $where[] = "created_at >= '$from'"; if ($to) $where[] = "created_at < DATE_ADD('$to', INTERVAL 1 DAY)"; }
$W = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$rows = $conn->query("SELECT id, district, gender, status, guild_id, created_at FROM members$W")->fetch_all(MYSQLI_ASSOC);

$stat = []; foreach ($all as $r) $stat[$r] = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0, 'F' => 0, 'M' => 0, 'O' => 0, 'districts' => []];
$months = []; for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i months"))] = array_fill_keys($all, 0);
$unmatched = []; $total = 0;
foreach ($rows as $m) {
    $r = mem_subregion_of($m['district'], $map) ?: 'Other / not stated';
    $s =& $stat[$r]; $s['total']++; $s[$m['status']]++; $total++;
    $s[$m['gender'] === 'Female' ? 'F' : ($m['gender'] === 'Male' ? 'M' : 'O')]++;
    $dn = trim($m['district']) !== '' ? ucwords(mb_strtolower(trim($m['district']))) : '(blank)'; $s['districts'][$dn] = ($s['districts'][$dn] ?? 0) + 1;
    if ($r === 'Other / not stated') $unmatched[$dn] = ($unmatched[$dn] ?? 0) + 1;
    $mk = date('Y-m', strtotime($m['created_at'])); if (isset($months[$mk])) $months[$mk][$r]++;
    unset($s);
}
arsort($unmatched);
$counts = ['approved' => 0, 'pending' => 0, 'rejected' => 0]; foreach ($stat as $s) foreach ($counts as $k => $_) $counts[$k] += $s[$k];
$thisM = array_sum($months[date('Y-m')] ?? []); $lastM = array_sum($months[date('Y-m', strtotime('first day of last month'))] ?? []);
$max = max(1, max(array_column($stat, 'total')));
$monthMax = max(1, max(array_map('array_sum', $months)));
$guilds = $conn->query("SELECT id, name FROM guilds ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$qs = fn(array $x = []) => 'member-dashboard.php?' . http_build_query(array_filter(array_merge(['status' => $status, 'period' => $period === 'all' ? '' : $period, 'from' => $from, 'to' => $to, 'guild' => $guild ?: ''], $x), fn($v) => $v !== '' && $v !== null));
$periodLabel = ['all' => 'All time', 'year' => 'This year', '12m' => 'Last 12 months', '90d' => 'Last 90 days', 'custom' => 'Custom range'][$period];

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="registrations-by-subregion.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF"); fputcsv($o, ['Sub-region', 'Registrations', 'Share %', 'Approved', 'Pending', 'Rejected', 'Female', 'Male', 'Other']);
    foreach ($stat as $r => $s) fputcsv($o, [$r, $s['total'], $total ? round($s['total'] * 100 / $total, 1) : 0, $s['approved'], $s['pending'], $s['rejected'], $s['F'], $s['M'], $s['O']]);
    fputcsv($o, []); fputcsv($o, ['Sub-region', 'District / location as entered', 'Registrations']);
    foreach ($stat as $r => $s) { arsort($s['districts']); foreach ($s['districts'] as $d => $n) fputcsv($o, [$r, $d, $n]); }
    exit;
}
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="member-dashboard.php" class="active">Dashboard</a><a href="members.php">Applications</a><a href="guilds.php">Guilds</a><a href="membership-settings.php">Membership settings</a></div>

<form method="get" class="ax-toolbar rd-filters">
  <div class="ax-seg"><?php foreach (['' => 'All statuses', 'approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $k => $l): ?><a href="<?php echo $e($qs(['status' => $k])); ?>" class="<?php echo $status === $k ? 'on' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?></div>
  <select name="period" onchange="this.form.submit()"><?php foreach (['all' => 'All time', 'year' => 'This year', '12m' => 'Last 12 months', '90d' => 'Last 90 days', 'custom' => 'Custom range'] as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo $period === $k ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select>
  <?php if ($period === 'custom'): ?><input type="date" name="from" value="<?php echo $e($from); ?>"><input type="date" name="to" value="<?php echo $e($to); ?>"><button class="admin-btn admin-btn-line admin-btn-sm">Apply</button><?php endif; ?>
  <select name="guild" onchange="this.form.submit()"><option value="0">All guilds</option><?php foreach ($guilds as $g): ?><option value="<?php echo $g['id']; ?>" <?php echo $guild === (int)$g['id'] ? 'selected' : ''; ?>><?php echo $e($g['name']); ?></option><?php endforeach; ?></select>
  <input type="hidden" name="status" value="<?php echo $e($status); ?>">
  <a class="admin-btn admin-btn-line" href="<?php echo $e($qs(['export' => 'csv'])); ?>">Export CSV</a>
</form>

<div class="admin-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr))">
  <div class="admin-stat"><b><?php echo $total; ?></b><span>Registrations · <?php echo $e($periodLabel); ?></span></div>
  <div class="admin-stat"><b><?php echo $counts['approved']; ?></b><span>Approved members</span></div>
  <div class="admin-stat"><b><?php echo $counts['pending']; ?></b><span>Pending review</span></div>
  <div class="admin-stat"><b><?php echo $counts['rejected']; ?></b><span>Rejected</span></div>
  <div class="admin-stat"><b><?php echo $thisM; ?></b><span>This month <?php echo $lastM ? '(' . ($thisM >= $lastM ? '+' : '') . round(($thisM - $lastM) * 100 / $lastM) . '% vs last month)' : ($thisM ? '(none last month)' : ''); ?></span></div>
</div>

<div class="rd-regions">
<?php foreach ($regions as $r): $s = $stat[$r]; $share = $total ? round($s['total'] * 100 / $total) : 0; arsort($s['districts']); $top = array_key_first($s['districts']); $gt = max(1, $s['F'] + $s['M'] + $s['O']); ?>
  <div class="admin-card rd-card" style="border-top:5px solid <?php echo $colour[$r]; ?>">
    <div class="rd-head"><h3><?php echo $e($r); ?></h3><b><?php echo $s['total']; ?></b></div>
    <div class="rd-bar"><i style="width:<?php echo round($s['total'] * 100 / $max); ?>%;background:<?php echo $colour[$r]; ?>"></i></div>
    <p class="rd-sub"><?php echo $share; ?>% of registrations<?php echo $top ? ' · most from ' . $e($top) . ' (' . $s['districts'][$top] . ')' : ''; ?></p>
    <div class="rd-pills"><span><b><?php echo $s['approved']; ?></b> approved</span><span><b><?php echo $s['pending']; ?></b> pending</span><span><b><?php echo $s['rejected']; ?></b> rejected</span></div>
    <div class="rd-gender" title="Female / Male / Other"><i style="width:<?php echo round($s['F'] * 100 / $gt); ?>%;background:#F57A0A"></i><i style="width:<?php echo round($s['M'] * 100 / $gt); ?>%;background:#111"></i><i style="width:<?php echo round($s['O'] * 100 / $gt); ?>%;background:#B8B8B8"></i></div>
    <p class="rd-sub">Female <?php echo $s['F']; ?> · Male <?php echo $s['M']; ?><?php echo $s['O'] ? ' · Other ' . $s['O'] : ''; ?></p>
    <?php if ($s['districts']): ?><details class="rd-dist"><summary>Districts (<?php echo count($s['districts']); ?>)</summary><table class="admin-table" data-noexport><tbody><?php foreach ($s['districts'] as $d => $n): ?><tr><td><?php echo $e($d); ?></td><td style="text-align:right"><b><?php echo $n; ?></b></td></tr><?php endforeach; ?></tbody></table></details><?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Registrations by sub-region</h2><span class="admin-help"><?php echo $e($periodLabel); ?><?php echo $status ? ' · ' . $e(ucfirst($status)) . ' only' : ''; ?></span></div>
  <div class="rd-legend"><?php foreach ($all as $r): ?><span><i style="background:<?php echo $colour[$r]; ?>"></i><?php echo $e($r); ?></span><?php endforeach; ?></div>
  <div class="rd-stack" role="img" aria-label="Share of registrations by sub-region"><?php foreach ($all as $r): $w = $total ? $stat[$r]['total'] * 100 / $total : 0; if ($w > 0): ?><div style="width:<?php echo round($w, 2); ?>%;background:<?php echo $colour[$r]; ?>;color:<?php echo in_array($r, ['Karamoja'], true) ? '#fff' : '#111'; ?>" title="<?php echo $e($r . ': ' . $stat[$r]['total']); ?>"><?php echo $w >= 7 ? round($w) . '%' : ''; ?></div><?php endif; endforeach; ?></div>
  <?php if (!$total): ?><p class="admin-help" style="margin-top:12px">No registrations match these filters yet.</p><?php endif; ?>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Monthly registrations — last 12 months</h2></div>
  <div class="rd-cols"><?php foreach ($months as $mk => $v): $t = array_sum($v); ?>
    <div class="rd-col"><div class="rd-colbar" title="<?php echo $e(date('F Y', strtotime($mk . '-01')) . ': ' . $t); ?>" style="height:<?php echo round($t * 100 / $monthMax); ?>%"><?php foreach (array_reverse($all) as $r) if ($v[$r]): ?><i style="height:<?php echo round($v[$r] * 100 / max(1, $t)); ?>%;background:<?php echo $colour[$r]; ?>" title="<?php echo $e($r . ': ' . $v[$r]); ?>"></i><?php endif; ?></div><b><?php echo $t ?: ''; ?></b><span><?php echo date('M', strtotime($mk . '-01')); ?></span></div>
  <?php endforeach; ?></div>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Summary table</h2></div>
  <table class="admin-table" data-export="Registrations by sub-region"><thead><tr><th>Sub-region</th><th>Registrations</th><th>Share</th><th>Approved</th><th>Pending</th><th>Rejected</th><th>Female</th><th>Male</th></tr></thead><tbody>
  <?php foreach ($stat as $r => $s): ?><tr><td><span class="rd-dot" style="background:<?php echo $colour[$r]; ?>"></span><b><?php echo $e($r); ?></b></td><td><?php echo $s['total']; ?></td><td><?php echo $total ? round($s['total'] * 100 / $total, 1) : 0; ?>%</td><td><?php echo $s['approved']; ?></td><td><?php echo $s['pending']; ?></td><td><?php echo $s['rejected']; ?></td><td><?php echo $s['F']; ?></td><td><?php echo $s['M']; ?></td></tr><?php endforeach; ?>
  <tr><td><b>Total</b></td><td><b><?php echo $total; ?></b></td><td>100%</td><td><?php echo $counts['approved']; ?></td><td><?php echo $counts['pending']; ?></td><td><?php echo $counts['rejected']; ?></td><td><?php echo array_sum(array_column($stat, 'F')); ?></td><td><?php echo array_sum(array_column($stat, 'M')); ?></td></tr></tbody></table>
</div>

<?php if ($unmatched): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Locations not matched to a sub-region (<?php echo array_sum($unmatched); ?>)</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="lists.php">Edit sub-region districts</a></div>
  <p class="admin-help">These entries were not found in the four district lists, so they are counted as “Other / not stated”. If one is a district of Acholi, Karamoja, Lango or West Nile, add it to that list under Form options and it will be counted automatically.</p>
  <div class="rd-pills"><?php foreach (array_slice($unmatched, 0, 40, true) as $d => $n): ?><span><b><?php echo $n; ?></b> <?php echo $e($d); ?></span><?php endforeach; ?></div></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
