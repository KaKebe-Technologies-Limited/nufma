<?php
$adminTitle = "Team";
$adminNav = "team";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $row = $conn->query("SELECT photo FROM team_members WHERE id=$delId")->fetch_assoc();
    if ($row && $row['photo'] && strpos($row['photo'], 'uploads/team/') === 0) {
        @unlink(__DIR__ . "/../assets/images/" . $row['photo']);
    }
    $conn->query("DELETE FROM team_members WHERE id=$delId");
    header("Location: team.php?deleted=1");
    exit;
}
if (isset($_GET['toggle'])) {
    $conn->query("UPDATE team_members SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']);
    header("Location: team.php?toggled=1");
    exit;
}

$team = $conn->query("SELECT * FROM team_members ORDER BY sort_order ASC, id ASC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Team member saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Team member removed.</div><?php endif; ?>
<?php if (isset($_GET['toggled'])): ?><div class="admin-alert ok">Visibility updated.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Our Team (About page)</h2>
    <a href="team-edit.php" class="admin-btn admin-btn-brand">+ New Team Member</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Photo</th><th>Name</th><th>Role</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if ($team->num_rows === 0): ?>
      <tr><td colspan="6" style="color:var(--text-muted)">No team members yet.</td></tr>
      <?php endif; ?>
      <?php while ($t = $team->fetch_assoc()):
        $has = $t['photo'] && file_exists(__DIR__ . "/../assets/images/" . $t['photo']); ?>
      <tr>
        <td>
          <?php if ($has): ?>
            <img class="admin-thumb" style="object-fit:cover;border-radius:50%" src="../assets/images/<?php echo htmlspecialchars($t['photo']); ?>" alt="">
          <?php else: ?>
            <span class="admin-badge gray">no photo</span>
          <?php endif; ?>
        </td>
        <td><b><?php echo htmlspecialchars($t['name']); ?></b></td>
        <td><?php echo htmlspecialchars($t['role']); ?></td>
        <td><?php echo (int)$t['sort_order']; ?></td>
        <td>
          <a href="team.php?toggle=<?php echo $t['id']; ?>" style="text-decoration:none">
            <?php echo $t['is_active'] ? '<span class="admin-badge">Live</span>' : '<span class="admin-badge gray">Hidden</span>'; ?>
          </a>
        </td>
        <td>
          <div class="admin-row-actions">
            <a href="team-edit.php?id=<?php echo $t['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
            <a href="team.php?delete=<?php echo $t['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this team member?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
