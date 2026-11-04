<?php
$adminTitle = "Membership card";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/member-card.php";
mem_card_ensure($conn);

$id = (int)($_GET['id'] ?? 0);
$load = fn() => $conn->query("SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE m.id=$id")->fetch_assoc();
$m = $load();
if (!$m) { header("Location: members.php"); exit; }
$flash = ''; $flashType = 'ok';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    elseif (($_POST['action'] ?? '') === 'issue') {
        $had = !empty($m['reg_no']);
        $reg = mem_card_issue($conn, $id, !empty($_POST['notify'])); $m = $load();
        $flash = 'Card issued' . ($had ? '' : ' — new membership ID ' . htmlspecialchars($reg)) . '.' . (!empty($_POST['notify']) ? ' The member was emailed the card link (if they have an email address).' : '');
    } elseif (($_POST['action'] ?? '') === 'email') {
        require_once __DIR__ . "/../includes/member-admin.php";
        $ok = mem_admin_notify($conn, $id);
        $flash = $ok ? 'Card link emailed to ' . htmlspecialchars($m['email']) . '.' : 'The email could not be sent (no valid email address, or mail is unavailable on this server).'; if (!$ok) $flashType = 'warn';
    }
}
$issued = !empty($m['card_issued_at']); $hasId = !empty($m['reg_no']);
$link = mem_card_link($m);
include __DIR__ . "/includes/admin-header.php";
?>
<link rel="stylesheet" href="../assets/css/member-card.css">
<div class="mem-tabs no-print"><a href="member-cards.php">&larr; All cards</a><a href="member-view.php?id=<?php echo $id; ?>">Member record</a><a href="members.php">Applications</a></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?> no-print"><?php echo $flash; ?></div><?php endif; ?>

<div class="admin-card no-print">
  <div class="admin-card-head"><h2><?php echo htmlspecialchars($m['full_name']); ?></h2>
    <span><?php echo mem_status_badge($m['status']); ?> <?php echo $issued ? '<span class="admin-badge">Card issued ' . date('d M Y', strtotime($m['card_issued_at'])) . '</span>' : '<span class="admin-badge gray">Card not issued</span>'; ?></span></div>
  <form method="post" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center"><?php echo mem_csrf_field(); ?>
    <button class="admin-btn admin-btn-brand" name="action" value="issue"><?php echo $issued ? 'Re-issue card' : ($hasId ? 'Issue card' : 'Approve, issue ID and card'); ?></button>
    <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="notify" value="1"> Email the member the card link</label>
    <button type="button" class="admin-btn admin-btn-line" onclick="window.print()">Print / Save as PDF</button>
    <?php if ($m['status'] === 'approved'): ?><button class="admin-btn admin-btn-line" name="action" value="email">Email card link</button>
    <a class="admin-btn admin-btn-line" href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener">Open public card ↗</a><?php endif; ?>
  </form>
  <?php if (!$hasId): ?><p class="admin-help" style="margin-top:10px">This member has no membership ID yet — the preview shows “NOT YET ISSUED”. Issuing the card approves them and assigns the next ID.</p><?php endif; ?>
  <?php if ($m['status'] === 'approved'): ?><p class="admin-help" style="margin-top:10px">QR code on the back opens: <code><?php echo htmlspecialchars($link); ?></code></p><?php endif; ?>
</div>

<div class="admin-card" style="background:#F2F2F2">
  <div class="idc-single"><?php echo mem_card_html($conn, $m, ['prefix' => '../']); ?></div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
