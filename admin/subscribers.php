<?php
$adminTitle = "Subscribers";
$adminNav = "subscribers";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT email, source, created_at FROM subscribers ORDER BY created_at DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="nufa-subscribers.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Source', 'Subscribed At']);
    while ($r = $rows->fetch_assoc()) fputcsv($out, [$r['email'], $r['source'], $r['created_at']]);
    fclose($out);
    exit;
}

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM subscribers WHERE id=" . (int)$_GET['delete']);
    header("Location: subscribers.php?deleted=1");
    exit;
}

$subs = $conn->query("SELECT * FROM subscribers ORDER BY created_at DESC");
$total = $conn->query("SELECT COUNT(*) c FROM subscribers")->fetch_assoc()['c'];
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Subscriber removed.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2><?php echo $total; ?> Subscribers</h2>
    <a href="subscribers.php?export=1" class="admin-btn admin-btn-brand">Export CSV</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Email</th><th>Source</th><th>Subscribed</th><th></th></tr></thead>
    <tbody>
      <?php if ($subs->num_rows === 0): ?>
      <tr><td colspan="4" style="color:var(--text-muted)">No subscribers yet.</td></tr>
      <?php endif; ?>
      <?php while ($s = $subs->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($s['email']); ?></td>
        <td><span class="admin-badge gray"><?php echo htmlspecialchars($s['source']); ?></span></td>
        <td><?php echo date("d M Y", strtotime($s['created_at'])); ?></td>
        <td><a href="subscribers.php?delete=<?php echo $s['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this subscriber?">Delete</a></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
