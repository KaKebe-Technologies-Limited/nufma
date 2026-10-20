<?php
$adminNav = "enquiries";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";

$id = (int)($_GET['id'] ?? 0);
$r = $conn->query("SELECT * FROM enquiries WHERE id=$id")->fetch_assoc();
if (!$r) { header("Location: enquiries.php"); exit; }
$types = ['contact' => 'Contact enquiry', 'partner' => 'Partnership enquiry', 'sacco' => 'SACCO expression of interest', 'network' => 'Industry network interest'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new = ($_POST['status'] ?? '') === 'handled' ? 'handled' : 'new';
    $s = $conn->prepare("UPDATE enquiries SET status=? WHERE id=?"); $s->bind_param("si", $new, $id); $s->execute();
    header("Location: enquiry-view.php?id=$id&saved=1"); exit;
}
if ($r['status'] === 'new' && !isset($_GET['saved'])) { /* viewing does not change status — the admin decides */ }
$adminTitle = $types[$r['type']] ?? 'Enquiry';
$row = fn($l, $v) => '<div class="mem-row"><span>' . $l . '</span><b>' . ($v !== '' && $v !== null ? nl2br(htmlspecialchars($v)) : '—') . '</b></div>';
include __DIR__ . "/includes/admin-header.php";
$subject = rawurlencode("Re: your enquiry to NUFA" . ($r['topic'] ? " — " . $r['topic'] : ''));
?>
<div class="mem-tabs"><a href="enquiries.php">&larr; All enquiries</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Status updated.</div><?php endif; ?>
<div class="mem-view">
  <div class="admin-card">
    <div class="admin-card-head"><h2><?php echo htmlspecialchars($r['name']); ?></h2><span class="admin-badge <?php echo $r['status'] === 'new' ? '' : 'gray'; ?>"><?php echo $r['status'] === 'new' ? 'New' : 'Handled'; ?></span></div>
    <?php echo $row('Received', date('j F Y, H:i', strtotime($r['created_at']))), $row('Form', $types[$r['type']] ?? $r['type']), $row('Topic / interest', $r['topic']),
        $row('Organisation', $r['organisation']), $row('Email', $r['email']), $row('Telephone', $r['phone']); ?>
    <?php if ($r['message']): ?><h3 class="mem-h3">Message</h3><p style="white-space:pre-wrap;background:var(--cream);padding:14px 16px"><?php echo htmlspecialchars($r['message']); ?></p><?php endif; ?>
    <?php if ($r['details']): ?><h3 class="mem-h3">Details</h3><p style="white-space:pre-wrap;background:var(--cream);padding:14px 16px"><?php echo htmlspecialchars($r['details']); ?></p><?php endif; ?>
    <p class="admin-help" style="margin-top:14px">Consent to be contacted: <b><?php echo $r['consent'] ? 'given' : 'not recorded'; ?></b></p>
  </div>
  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Respond</h2></div>
      <a class="admin-btn admin-btn-brand" href="mailto:<?php echo htmlspecialchars($r['email']); ?>?subject=<?php echo $subject; ?>">Reply by email</a>
      <?php require_once __DIR__ . "/../includes/whatsapp.php"; if ($r['phone'] && ($wa = nufa_wa_reply_url($r['phone'], 'Hello ' . $r['name'] . ', thank you for contacting NUFA. '))): ?><a class="admin-btn admin-btn-line" href="<?php echo htmlspecialchars($wa); ?>" target="_blank" rel="noopener" style="border-color:#25D366;color:#128c4a">Reply on WhatsApp</a><?php endif; ?>
      <?php if ($r['phone']): ?><a class="admin-btn admin-btn-line" href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $r['phone'])); ?>">Call <?php echo htmlspecialchars($r['phone']); ?></a><?php endif; ?>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Status</h2></div>
      <form method="post" class="admin-form"><div class="field"><select name="status"><option value="new" <?php echo $r['status'] === 'new' ? 'selected' : ''; ?>>New — needs attention</option><option value="handled" <?php echo $r['status'] === 'handled' ? 'selected' : ''; ?>>Handled</option></select></div><button class="admin-btn admin-btn-line">Update status</button></form>
    </div>
    <a href="enquiries.php?delete=<?php echo $r['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this enquiry permanently?">Delete enquiry</a>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
