<?php
$adminTitle = "Programmes";
$adminNav = "programmes";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM programmes WHERE id=" . (int)$_GET['delete']);
    header("Location: programmes.php?deleted=1"); exit;
}
if (isset($_GET['toggle'])) {
    $conn->query("UPDATE programmes SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']);
    header("Location: programmes.php"); exit;
}
$rows = $conn->query("SELECT * FROM programmes ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$detailKeys = ['overview', 'context', 'objectives', 'beneficiaries', 'activities', 'outputs', 'geography', 'partners_text', 'progress'];
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Programme saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Programme deleted.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Programme areas</h2><a href="programme-edit.php" class="admin-btn admin-btn-brand admin-btn-sm">+ New programme</a></div>
  <p class="admin-help" style="margin-bottom:16px">Each programme has its own public page. Set a status (Planned, Ongoing, Completed, Under Development) <b>only</b> when it has been confirmed — with no status, no label is shown. Sections left empty are hidden on the public page.</p>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr><th></th><th>Programme</th><th>Status</th><th>Detail completeness</th><th>Order</th><th>Visible</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p):
      $filled = count(array_filter($detailKeys, fn($k) => trim((string)$p[$k]) !== '')); ?>
      <tr>
        <td><?php if ($p['image'] && is_file(__DIR__ . '/../assets/images/' . $p['image'])): ?><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt=""><?php endif; ?></td>
        <td><b><?php echo htmlspecialchars($p['title']); ?></b><br><span class="admin-help">/programme.php?slug=<?php echo htmlspecialchars($p['slug']); ?></span></td>
        <td><?php echo $p['status'] ? ui_status_chip($p['status']) : '<span class="admin-badge gray">Not set</span>'; ?></td>
        <td><?php echo $filled; ?> of <?php echo count($detailKeys); ?> sections</td>
        <td><?php echo (int)$p['sort_order']; ?></td>
        <td><a href="programmes.php?toggle=<?php echo $p['id']; ?>" class="admin-badge <?php echo $p['is_active'] ? '' : 'gray'; ?>"><?php echo $p['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
        <td style="white-space:nowrap"><a href="programme-edit.php?id=<?php echo $p['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
          <a href="programmes.php?delete=<?php echo $p['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this programme?">Delete</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
