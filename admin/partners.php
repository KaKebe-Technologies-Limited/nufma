<?php
$adminTitle = "Partners";
$adminNav = "partners";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/ui.php";

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("SELECT logo FROM partners WHERE id=$id")->fetch_assoc();
    if ($row && strpos($row['logo'], 'uploads/partners/') === 0) @unlink(__DIR__ . "/../assets/images/" . $row['logo']);
    $conn->query("DELETE FROM partners WHERE id=$id");
    header("Location: partners.php?deleted=1"); exit;
}
if (isset($_GET['toggle'])) {
    $conn->query("UPDATE partners SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']);
    header("Location: partners.php"); exit;
}

$cats = nufa_list(nufa_site_settings($conn), 'list_partner_categories');
$cat = $_GET['cat'] ?? '';
if ($cat !== '__none' && !in_array($cat, $cats, true)) $cat = '';
$rel = in_array($_GET['rel'] ?? '', ['current', 'previous'], true) ? $_GET['rel'] : '';
$counts = [];
$cr = $conn->query("SELECT category, COUNT(*) c FROM partners GROUP BY category");
while ($c = $cr->fetch_assoc()) $counts[$c['category']] = (int)$c['c'];
$where = ui_cat_where($conn, $cat) . ($rel ? " AND relationship='$rel'" : '');
$rows = $conn->query("SELECT * FROM partners WHERE $where ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Partner saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Partner removed.</div><?php endif; ?>

<?php echo ui_admin_cat_tabs('partners.php', $cats, $cat, $counts, ['rel' => $rel]); ?>
<div class="admin-card">
  <div class="admin-card-head">
    <h2>Partners &amp; supporters</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="partners.php?<?php echo http_build_query(array_filter(['cat' => $cat, 'rel' => $rel === 'current' ? '' : 'current'])); ?>" class="admin-btn admin-btn-line admin-btn-sm"><?php echo $rel === 'current' ? '✓ ' : ''; ?>Current only</a>
      <a href="partners.php?<?php echo http_build_query(array_filter(['cat' => $cat, 'rel' => $rel === 'previous' ? '' : 'previous'])); ?>" class="admin-btn admin-btn-line admin-btn-sm"><?php echo $rel === 'previous' ? '✓ ' : ''; ?>Previous only</a>
      <a href="lists.php" class="admin-btn admin-btn-line admin-btn-sm">Edit categories</a>
      <a href="partner-edit.php<?php echo ($cat !== '' && $cat !== '__none') ? '?cat=' . urlencode($cat) : ''; ?>" class="admin-btn admin-btn-brand admin-btn-sm">+ New partner</a>
    </div>
  </div>
  <p class="admin-help" style="margin-bottom:14px">Categories group the partner wall on “Partner With Us”. Mark an organisation as a <b>previous supporter</b> if it is no longer a current partner.</p>
  <div style="overflow-x:auto"><table class="admin-table">
    <thead><tr><th></th><th>Name</th><th>Category</th><th>Role / tier</th><th>Relationship</th><th>Order</th><th>Visible</th><th></th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--text-muted)">No partners here yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $p): $has = $p['logo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $p['logo']); ?>
      <tr>
        <td><?php if ($has): ?><img class="admin-thumb" style="object-fit:contain;background:var(--cream)" src="../assets/images/<?php echo htmlspecialchars($p['logo']); ?>" alt=""><?php endif; ?></td>
        <td><b><?php echo htmlspecialchars($p['name']); ?></b></td>
        <td><?php echo $p['category'] !== '' ? '<span class="admin-badge">' . htmlspecialchars($p['category']) . '</span>' : '<span class="admin-badge gray">none</span>'; ?></td>
        <td><?php echo htmlspecialchars($p['tier']); ?></td>
        <td><?php echo $p['relationship'] === 'previous' ? '<span class="admin-badge gray">Previous</span>' : '<span class="admin-badge">Current</span>'; ?></td>
        <td><?php echo (int)$p['sort_order']; ?></td>
        <td><a href="partners.php?toggle=<?php echo $p['id']; ?>" class="admin-badge <?php echo $p['is_active'] ? '' : 'gray'; ?>"><?php echo $p['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
        <td style="white-space:nowrap"><a href="partner-edit.php?id=<?php echo $p['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
          <a href="partners.php?delete=<?php echo $p['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this partner?">Delete</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
