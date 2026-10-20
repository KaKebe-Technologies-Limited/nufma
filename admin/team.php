<?php
$adminTitle = "Leadership";
$adminNav = "team";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/ui.php";

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $row = $conn->query("SELECT photo FROM team_members WHERE id=$delId")->fetch_assoc();
    if ($row && $row['photo'] && strpos($row['photo'], 'uploads/team/') === 0) @unlink(__DIR__ . "/../assets/images/" . $row['photo']);
    $conn->query("DELETE FROM team_members WHERE id=$delId");
    header("Location: team.php?deleted=1"); exit;
}
if (isset($_GET['toggle'])) {
    $conn->query("UPDATE team_members SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']);
    header("Location: team.php?toggled=1"); exit;
}

$cats = nufa_list(nufa_site_settings($conn), 'list_team_categories');
$cat = $_GET['cat'] ?? '';
if ($cat !== '__none' && !in_array($cat, $cats, true)) $cat = '';
$counts = [];
$cr = $conn->query("SELECT category, COUNT(*) c FROM team_members GROUP BY category");
while ($c = $cr->fetch_assoc()) $counts[$c['category']] = (int)$c['c'];
$rows = $conn->query("SELECT * FROM team_members WHERE " . ui_cat_where($conn, $cat) . " ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Leadership profile saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Profile removed.</div><?php endif; ?>
<?php if (isset($_GET['toggled'])): ?><div class="admin-alert ok">Visibility updated.</div><?php endif; ?>

<?php echo ui_admin_cat_tabs('team.php', $cats, $cat, $counts); ?>
<div class="admin-card">
  <div class="admin-card-head">
    <h2>Leadership &amp; governance (About page)</h2>
    <div style="display:flex;gap:8px"><a href="lists.php" class="admin-btn admin-btn-line admin-btn-sm">Edit groups</a><a href="team-edit.php<?php echo ($cat !== '' && $cat !== '__none') ? '?cat=' . urlencode($cat) : ''; ?>" class="admin-btn admin-btn-brand admin-btn-sm">+ New profile</a></div>
  </div>
  <p class="admin-help" style="margin-bottom:14px">Group people (Board, Management …) so the About page shows them under clear headings. Only publish verified names, roles and biographies.</p>
  <div style="overflow-x:auto"><table class="admin-table">
    <thead><tr><th>Photo</th><th>Name</th><th>Group</th><th>Role</th><th>Bio</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--text-muted)">No profiles here yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $t): $has = $t['photo'] && file_exists(__DIR__ . "/../assets/images/" . $t['photo']); ?>
      <tr>
        <td><?php if ($has): ?><img class="admin-thumb" style="object-fit:cover;border-radius:50%" src="../assets/images/<?php echo htmlspecialchars($t['photo']); ?>" alt=""><?php else: ?><span class="admin-badge gray">no photo</span><?php endif; ?></td>
        <td><b><?php echo htmlspecialchars($t['name']); ?></b></td>
        <td><?php echo $t['category'] !== '' ? '<span class="admin-badge">' . htmlspecialchars($t['category']) . '</span>' : '<span class="admin-badge gray">none</span>'; ?></td>
        <td><?php echo htmlspecialchars($t['role']); ?></td>
        <td><?php echo trim((string)$t['bio']) !== '' ? '✓' : '<span class="admin-help">missing</span>'; ?></td>
        <td><?php echo (int)$t['sort_order']; ?></td>
        <td><a href="team.php?toggle=<?php echo $t['id']; ?>" style="text-decoration:none"><?php echo $t['is_active'] ? '<span class="admin-badge">Live</span>' : '<span class="admin-badge gray">Hidden</span>'; ?></a></td>
        <td><div class="admin-row-actions">
          <a href="team-edit.php?id=<?php echo $t['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
          <a href="team.php?delete=<?php echo $t['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this profile?">Delete</a></div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
