<?php
$adminTitle = "Award Editions";
$adminNav = "awards";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM award_editions WHERE id=" . (int)$_GET['delete']);
    header("Location: award-editions.php?deleted=1");
    exit;
}
if (isset($_GET['toggle'])) {
    $conn->query("UPDATE award_editions SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']);
    header("Location: award-editions.php?toggled=1");
    exit;
}

$editions = $conn->query("SELECT e.*, (SELECT COUNT(*) FROM award_winners w WHERE w.edition_id=e.id) AS winner_count FROM award_editions e ORDER BY sort_order ASC, year DESC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Edition saved — the Awards page reflects it immediately.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Edition removed.</div><?php endif; ?>
<?php if (isset($_GET['toggled'])): ?><div class="admin-alert ok">Visibility updated.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>NUFA Awards Editions</h2>
    <a href="award-edition-edit.php" class="admin-btn admin-btn-brand">+ New Edition</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Year</th><th>Slug</th><th>Status</th><th>Winners</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if ($editions->num_rows === 0): ?>
      <tr><td colspan="7" style="color:var(--text-muted)">No editions yet — add NUFA25, NUFA26, NUFA27...</td></tr>
      <?php endif; ?>
      <?php while ($e = $editions->fetch_assoc()): ?>
      <tr>
        <td><b><?php echo (int)$e['year']; ?></b></td>
        <td><?php echo htmlspecialchars($e['slug']); ?></td>
        <td><?php echo $e['status'] === 'upcoming' ? '<span class="admin-badge">Upcoming</span>' : '<span class="admin-badge gray">Past</span>'; ?></td>
        <td>
          <?php echo (int)$e['winner_count']; ?>
          <?php if ($e['status'] === 'past'): ?><a href="award-winners.php?edition=<?php echo $e['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm" style="margin-left:8px">Manage</a><?php endif; ?>
        </td>
        <td><?php echo (int)$e['sort_order']; ?></td>
        <td>
          <a href="award-editions.php?toggle=<?php echo $e['id']; ?>" style="text-decoration:none">
            <?php echo $e['is_active'] ? '<span class="admin-badge">Live</span>' : '<span class="admin-badge gray">Hidden</span>'; ?>
          </a>
        </td>
        <td>
          <div class="admin-row-actions">
            <a href="award-edition-edit.php?id=<?php echo $e['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
            <a href="award-editions.php?delete=<?php echo $e['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this edition and all its winners?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
