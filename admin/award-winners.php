<?php
$adminTitle = "Award Winners";
$adminNav = "awards";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM award_winners WHERE id=" . (int)$_GET['delete']);
    header("Location: award-winners.php?deleted=1" . (isset($_GET['edition']) ? '&edition=' . (int)$_GET['edition'] : ''));
    exit;
}

$editions = $conn->query("SELECT id, slug, year FROM award_editions ORDER BY year DESC");
$editionList = [];
while ($e = $editions->fetch_assoc()) $editionList[] = $e;

$activeEdition = isset($_GET['edition']) ? (int)$_GET['edition'] : ($editionList[0]['id'] ?? 0);

$winners = [];
if ($activeEdition) {
    $stmt = $conn->prepare("SELECT * FROM award_winners WHERE edition_id=? ORDER BY sort_order ASC");
    $stmt->bind_param("i", $activeEdition);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($w = $res->fetch_assoc()) $winners[] = $w;
}

include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Winner saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Winner removed.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Award Winners</h2></div>
  <?php if (!$editionList): ?>
    <p class="admin-help" style="padding:0 0 16px">No editions yet — <a href="award-editions.php">create an edition</a> first.</p>
  <?php else: ?>
  <form method="get" style="padding:0 0 20px;max-width:320px">
    <div class="field" style="margin:0">
      <label>Edition</label>
      <select name="edition" onchange="this.form.submit()">
        <?php foreach ($editionList as $e): ?>
        <option value="<?php echo $e['id']; ?>" <?php echo $activeEdition == $e['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(strtoupper($e['slug'])); ?> (<?php echo (int)$e['year']; ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <div class="admin-card-head"><h3 style="font-size:1rem">Winners for <?php echo htmlspecialchars(strtoupper($editionList[array_search($activeEdition, array_column($editionList,'id'))]['slug'] ?? '')); ?></h3>
    <a href="award-winner-edit.php?edition=<?php echo $activeEdition; ?>" class="admin-btn admin-btn-brand admin-btn-sm">+ Add Winner</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Category</th><th>Winner / Film</th><th>Person</th><th>Honorary</th><th>Order</th><th></th></tr></thead>
    <tbody>
      <?php if (!$winners): ?>
      <tr><td colspan="6" style="color:var(--text-muted)">No winners added for this edition yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($winners as $w): ?>
      <tr>
        <td><?php echo htmlspecialchars($w['category']); ?></td>
        <td><b><?php echo htmlspecialchars($w['winner_title']); ?></b></td>
        <td><?php echo htmlspecialchars($w['person_name']); ?></td>
        <td><?php echo $w['is_honorary'] ? '<span class="admin-badge">Yes</span>' : ''; ?></td>
        <td><?php echo (int)$w['sort_order']; ?></td>
        <td>
          <div class="admin-row-actions">
            <a href="award-winner-edit.php?id=<?php echo $w['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
            <a href="award-winners.php?delete=<?php echo $w['id']; ?>&edition=<?php echo $activeEdition; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this winner?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
