<?php
$adminNav = "messages";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = $conn->query("SELECT * FROM messages WHERE id=$id")->fetch_assoc();
if (!$msg) { header("Location: messages.php"); exit; }
$adminTitle = "Reply to " . $msg['name'];

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($subject && $body) {
        $fromEmail = 'info@nufa.media';
        $headers  = "From: NUFA <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $ok = @mail($msg['email'], $subject, $body, $headers);
        if ($ok) {
            $conn->query("UPDATE messages SET is_read=1 WHERE id=" . (int)$id);
            $sent = true;
        } else {
            $error = "This server isn't configured to send mail directly. Copy the message below and send it from your own email client instead — the mailto link does that for you.";
        }
    } else {
        $error = "Please fill in both the subject and the message.";
    }
}

$defaultSubject = "Re: " . ($msg['subject'] ?: "Your message to NUFA");
$defaultBody = "Hi " . $msg['name'] . ",\n\nThank you for reaching out to NUFA.\n\n\n\n— NUFA Team";

include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($sent): ?><div class="admin-alert ok">Reply sent to <?php echo htmlspecialchars($msg['email']); ?>.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Original Message</h2>
  </div>
  <p style="margin-bottom:6px"><b><?php echo htmlspecialchars($msg['name']); ?></b> — <?php echo htmlspecialchars($msg['email']); ?></p>
  <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:14px"><?php echo htmlspecialchars($msg['subject'] ?: 'No subject'); ?> · <?php echo date("d M Y, H:i", strtotime($msg['created_at'])); ?></p>
  <p style="white-space:pre-wrap;background:var(--cream);border-radius:var(--radius-sm);padding:14px 16px"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Send a Reply</h2><span class="admin-help">To: <?php echo htmlspecialchars($msg['email']); ?></span></div>
  <form class="admin-form" method="post">
    <div class="field">
      <label>Subject</label>
      <input type="text" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? $defaultSubject); ?>" required>
    </div>
    <div class="field">
      <label>Message</label>
      <textarea name="body" style="min-height:220px" required><?php echo htmlspecialchars($_POST['body'] ?? $defaultBody); ?></textarea>
    </div>
    <button type="submit" class="admin-btn admin-btn-brand">Send Reply</button>
    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=<?php echo urlencode($defaultSubject); ?>" class="admin-btn admin-btn-line">Open in Email App Instead</a>
    <a href="messages.php" class="admin-btn admin-btn-line">Back to Messages</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
