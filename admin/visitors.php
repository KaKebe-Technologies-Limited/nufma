<?php
$adminTitle = "Visitors";
$adminNav = "visitors";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/analytics.php";
ana_install($conn);
$e = 'ana_h';
$days = (int)($_GET['days'] ?? 30); if (!in_array($days, [7, 30, 90, 365], true)) $days = 30;
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['act'] ?? '') === 'settings') { ana_set($conn, 'ana_geo_lookup', isset($_POST['ana_geo_lookup']) ? '1' : '0'); header("Location: visitors.php?days=$days&saved=1"); exit; }
    if (($_POST['act'] ?? '') === 'clear') { $conn->query("TRUNCATE TABLE visit_log"); $conn->query("TRUNCATE TABLE geo_cache"); header("Location: visitors.php?days=$days&cleared=1"); exit; }
}
$r = ana_report($conn, $days);
$legacy = (int)($conn->query("SELECT COALESCE(SUM(hits),0) FROM page_hits WHERE day >= '" . ana_since($days) . "'")->fetch_row()[0] ?? 0);
$maxH = max(1, max($r['hours'])); $pps = $r['visitors'] ? round($r['views'] / $r['visitors'], 1) : 0; $mob = array_sum($r['devices']) ? round($r['devices']['m'] * 100 / array_sum($r['devices'])) : 0;
$src = array_column($r['sources'], 'n', 'csrc'); $geo = ana_setting($conn, 'ana_geo_lookup') === '1';
// friendly names for detail pages (stories, films)
$names = [];
foreach ($r['items'] as $it) {
    if ($it['page'] === 'post') { $q = $conn->prepare("SELECT title FROM posts WHERE slug=?"); $q->bind_param("s", $it['item']); $q->execute(); if ($x = $q->get_result()->fetch_assoc()) $names[$it['page'] . '|' . $it['item']] = $x['title']; }
    if ($it['page'] === 'film') { $q = $conn->prepare("SELECT title FROM films WHERE slug=?"); $q->bind_param("s", $it['item']); $q->execute(); if ($x = @$q->get_result()->fetch_assoc()) $names[$it['page'] . '|' . $it['item']] = $x['title']; }
}
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['cleared'])): ?><div class="admin-alert ok">All visitor statistics were cleared.</div><?php endif; ?>
<div class="an-range"><?php foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months'] as $k => $l): ?><a href="visitors.php?days=<?php echo $k; ?>" class="<?php echo $days === $k ? 'on' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?><a href="index.php">← Dashboard</a></div>

<div class="dx-kpis">
  <div class="dx-kpi dark"><span>Visitors · <?php echo $days; ?> days</span><b><?php echo number_format($r['visitors']); ?></b><small><?php echo $r['prev_visitors'] ? ana_trend($r['visitors'], $r['prev_visitors']) . 'the previous ' . $days . ' days' : 'Counting started recently'; ?></small></div>
  <div class="dx-kpi"><span>Page views</span><b><?php echo number_format($r['views']); ?></b><small><?php echo $pps; ?> pages per visitor</small></div>
  <div class="dx-kpi"><span>Visitors today</span><b><?php echo number_format($r['today']); ?></b><small>Since midnight</small></div>
  <div class="dx-kpi"><span>On mobile</span><b><?php echo $mob; ?>%</b><small><?php echo 100 - $mob; ?>% tablet or desktop</small></div>
  <div class="dx-kpi"><span>Countries</span><b><?php echo count(array_filter($r['countries'], fn($c) => $c['country'] !== 'ZZ')); ?></b><small>Top: <?php echo $r['countries'] ? $e(ana_flag($r['countries'][0]['country']) . ' ' . ana_country_name($r['countries'][0]['country'])) : '—'; ?></small></div>
</div>

<?php echo ana_panel_chart($r); ?>
<div class="edit-grid" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px"><?php echo ana_panel_devices($r); echo ana_panel_countries($r, 12); ?></div>
<div class="edit-grid" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px">
  <?php echo ana_panel_pages($r, 12); ?>
  <div class="admin-card"><div class="admin-card-head"><h2>Most read stories &amp; films</h2></div>
    <?php if (!$r['items']): ?><p class="admin-help">Individual stories and films will be listed once they have been opened.</p><?php else: $mx = max(1, (int)$r['items'][0]['views']); ?><div class="dx-top"><?php foreach ($r['items'] as $it): ?><div class="r"><span><?php echo $e($names[$it['page'] . '|' . $it['item']] ?? (ana_page_label($it['page']) . ': ' . $it['item'])); ?></span><div class="bar"><i style="width:<?php echo round($it['views'] / $mx * 100); ?>%"></i></div><em><?php echo number_format((int)$it['views']); ?></em></div><?php endforeach; ?></div><?php endif; ?>
    <h3 class="mem-h3" style="margin-top:18px">Where they came from</h3>
    <?php if (!$r['refs']): ?><p class="admin-help">No visits from other websites yet — most visitors arrive directly or from search, which browsers do not always report.</p><?php else: $mx = max(1, (int)$r['refs'][0]['v']); ?><div class="dx-top"><?php foreach ($r['refs'] as $x): ?><div class="r"><span><?php echo $e($x['ref']); ?></span><div class="bar"><i style="width:<?php echo round($x['v'] / $mx * 100); ?>%"></i></div><em><?php echo number_format((int)$x['v']); ?></em></div><?php endforeach; ?></div><?php endif; ?></div>
</div>
<div class="admin-card"><div class="admin-card-head"><h2>Busiest hours</h2><span class="admin-help">page views by hour of the day (server time)</span></div>
  <div class="an-hours"><?php foreach ($r['hours'] as $h => $n): ?><div title="<?php echo sprintf('%02d:00', $h) . ' — ' . $n . ' views'; ?>" style="height:<?php echo max(2, round($n / $maxH * 88)); ?>px"></div><?php endforeach; ?></div>
  <div class="an-hours-l"><?php foreach (range(0, 23) as $h): ?><span><?php echo $h % 3 === 0 ? $h : ''; ?></span><?php endforeach; ?></div></div>

<div class="admin-card" id="accuracy"><div class="admin-card-head"><h2>How this is counted</h2></div>
  <ul class="admin-help" style="line-height:1.8;padding-left:18px">
    <li><b>Visitors</b> are anonymous: a one-way code that changes every day. No cookies are used and no IP addresses or names are stored. The same person returning on another day counts as a new visitor.</li>
    <li><b>Mobile, tablet or desktop</b> is read from the browser type. Search-engine robots are not counted, and neither are signed-in admins.</li>
    <li><b>Country</b> comes from the hosting or CDN location header when your host provides one (<?php echo number_format((int)($src['h'] ?? 0)); ?> visits), from the optional look-up below (<?php echo number_format((int)($src['i'] ?? 0)); ?>), or is estimated from the browser language (<?php echo number_format((int)($src['l'] ?? 0)); ?>). Estimates are less accurate: someone in Uganda using an English-US browser would be counted as the United States. Visits with no clue are shown as “Unknown” (<?php echo number_format((int)($src['u'] ?? 0)); ?>).</li>
    <?php if ($legacy && !$r['views']): ?><li>The older anonymous page counter recorded <?php echo number_format($legacy); ?> page views in this period; the detailed statistics start from the day this feature was switched on.</li><?php endif; ?>
  </ul>
  <form method="post" class="admin-form"><input type="hidden" name="act" value="settings">
    <label class="mem-check"><input type="checkbox" name="ana_geo_lookup" value="1" <?php echo $geo ? 'checked' : ''; ?>> Look up the country from the visitor’s IP address (more accurate)</label>
    <p class="admin-help">When on, the IP address of a first-time visitor is sent once to the free service <b>ipwho.is</b> to find the country, then only a one-way code is kept for 30 days — the IP address itself is never stored by NUFA. Because this shares IP addresses with a third party, tell your visitors in your Privacy Policy before switching it on. <b>It is off by default.</b> A more private alternative is to ask your host to enable a country header (for example Cloudflare’s “IP Geolocation”), which this page uses automatically.</p>
    <button class="admin-btn admin-btn-brand admin-btn-sm">Save</button></form>
  <form method="post" style="margin-top:16px"><input type="hidden" name="act" value="clear"><button class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete all visitor statistics? This cannot be undone.">Clear all visitor statistics</button></form></div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
