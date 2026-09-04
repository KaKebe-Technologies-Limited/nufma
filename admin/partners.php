<?php
$adminTitle = "Partners";
$adminNav = "partners";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $row = $conn->query("SELECT logo FROM partners WHERE id=$delId")->fetch_assoc();
    if ($row && $row['logo'] && strpos($row['logo'], 'uploads/partners/') === 0) {
        @unlink(__DIR__ . "/../assets/images/" . $row['logo']);
    }
    $conn->query("DELETE FROM partners WHERE id=$delId");
    header("Location: partners.php?deleted=1");
    exit;
}

if (isset($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    $conn->query("UPDATE partners SET is_active = 1 - is_active WHERE id=$tid");
    header("Location: partners.php?toggled=1");
    exit;
}

$partners = $conn->query("SELECT * FROM partners ORDER BY sort_order ASC, id ASC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Partner saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Partner removed.</div><?php endif; ?>
<?php if (isset($_GET['toggled'])): ?><div class="admin-alert ok">Visibility updated.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Partners &amp; Sponsors</h2>
    <a href="partner-edit.php" class="admin-btn admin-btn-brand">+ New Partner</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Logo</th><th>Name</th><th>Tier</th><th>Order</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if ($partners->num_rows === 0): ?>
      <tr><td colspan="6" style="color:var(--text-muted)">No partners yet — add your first one.</td></tr>
      <?php endif; ?>
      <?php while ($p = $partners->fetch_assoc()):
        $has = $p['logo'] && file_exists(__DIR__ . "/../assets/images/" . $p['logo']); ?>
      <tr>
        <td>
          <?php if ($has): ?>
            <img class="admin-thumb" style="object-fit:contain;background:var(--cream)" src="../assets/images/<?php echo htmlspecialchars($p['logo']); ?>" alt="">
          <?php else: ?>
            <span class="admin-badge gray" title="<?php echo htmlspecialchars($p['logo']); ?>">no file</span>
          <?php endif; ?>
        </td>
        <td><b><?php echo htmlspecialchars($p['name']); ?></b></td>
        <td><?php echo htmlspecialchars($p['tier']); ?></td>
        <td><?php echo (int)$p['sort_order']; ?></td>
        <td>
          <a href="partners.php?toggle=<?php echo $p['id']; ?>" style="text-decoration:none">
            <?php echo $p['is_active']
              ? '<span class="admin-badge">Live</span>'
              : '<span class="admin-badge gray">Hidden</span>'; ?>
          </a>
        </td>
        <td>
          <div class="admin-row-actions">
            <a href="partner-edit.php?id=<?php echo $p['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
            <a href="partners.php?delete=<?php echo $p['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this partner?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
