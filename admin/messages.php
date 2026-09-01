<?php
$adminTitle = "Messages";
$adminNav = "messages";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['read'])) {
    $conn->query("UPDATE messages SET is_read=1 WHERE id=" . (int)$_GET['read']);
    header("Location: messages.php");
    exit;
}
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM messages WHERE id=" . (int)$_GET['delete']);
    header("Location: messages.php?deleted=1");
    exit;
}

$msgs = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Message deleted.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Contact &amp; Partnership Enquiries</h2></div>
  <table class="admin-table">
    <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th></th></tr></thead>
    <tbody>
      <?php if ($msgs->num_rows === 0): ?>
      <tr><td colspan="5" style="color:var(--text-muted)">No messages yet.</td></tr>
      <?php endif; ?>
      <?php while ($m = $msgs->fetch_assoc()): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($m['name']); ?></b><br><span style="color:var(--text-muted);font-size:.8rem"><?php echo htmlspecialchars($m['email']); ?></span></td>
        <td><?php echo htmlspecialchars($m['subject'] ?: '—'); ?></td>
        <td style="max-width:280px"><?php echo htmlspecialchars(mb_strimwidth($m['message'], 0, 120, '…')); ?></td>
        <td><?php echo date("d M Y, H:i", strtotime($m['created_at'])); ?></td>
        <td>
          <div class="admin-row-actions">
            <?php if (!$m['is_read']): ?><a href="messages.php?read=<?php echo $m['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Mark Read</a><?php endif; ?>
            <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>" class="admin-btn admin-btn-brand admin-btn-sm">Reply</a>
            <a href="messages.php?delete=<?php echo $m['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this message?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
