<?php
$adminNav = "support";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/support.php";

$id = (int)($_GET['id'] ?? 0);
$load = fn() => $conn->query("SELECT e.*, p.title AS programme FROM engagements e LEFT JOIN programmes p ON p.id=e.programme_id WHERE e.id=$id")->fetch_assoc();
$e = $load();
if (!$e) { header("Location: support-requests.php"); exit; }
$kinds = sup_kinds();
$flash = ''; $flashType = 'ok';
$payable = in_array($e['kind'], ['donate', 'sponsor'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $mail = !empty($_POST['send_email']);
    $setStatus = function ($status, $event, $extraSql = '', $reason = '') use ($conn, $id, &$e, $load, $mail, &$flash, &$flashType) {
        $r = $conn->real_escape_string($reason);
        $conn->query("UPDATE engagements SET status='$status', reject_reason='$r' $extraSql WHERE id=$id");
        $e = $load();
        $flash = "Status updated to " . ucfirst($status) . ".";
        if ($mail) {
            $ok = sup_notify($conn, $e, $event);
            $flash .= $ok ? " The supporter was e-mailed." : " The e-mail could not be sent from this server — see the e-mail log below.";
            if (!$ok) $flashType = 'warn';
        }
    };
    if ($act === 'approve')       $setStatus('approved', 'approved', ", approved_at=COALESCE(approved_at, NOW())");
    elseif ($act === 'complete')  {
        $amt = trim($_POST['confirmed_amount'] ?? '');
        $extra = ", completed_at=NOW(), approved_at=COALESCE(approved_at, NOW())";
        if ($amt !== '' && ($v = sup_amount($amt)) !== null) $extra .= ", amount=" . (float)$v;
        $setStatus('completed', 'completed', $extra);
    }
    elseif ($act === 'reject')    $setStatus('rejected', 'rejected', '', trim($_POST['reason'] ?? ''));
    elseif ($act === 'reopen')    $setStatus('pending', 'received', ", completed_at=NULL");
    elseif ($act === 'resend')    { $ev = ['pending' => 'received', 'approved' => 'approved', 'completed' => 'completed', 'rejected' => 'rejected'][$e['status']]; $ok = sup_notify($conn, $e, $ev); $flash = $ok ? "E-mail re-sent." : "The e-mail could not be sent from this server."; if (!$ok) $flashType = 'warn'; }
    elseif ($act === 'note')      { $s = $conn->prepare("UPDATE engagements SET admin_note=? WHERE id=?"); $n = mb_substr(trim($_POST['admin_note'] ?? ''), 0, 500); $s->bind_param("si", $n, $id); $s->execute(); $e = $load(); $flash = "Note saved."; }
}

$adminTitle = $e['ref_no'] . " — " . ($kinds[$e['kind']]['label'] ?? $e['kind']);
$emails = $conn->query("SELECT * FROM member_emails WHERE kind LIKE 'support-%' AND to_email='" . $conn->real_escape_string($e['email']) . "' ORDER BY id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$row = fn($l, $v) => '<div class="mem-row"><span>' . $l . '</span><b>' . ($v !== '' && $v !== null ? nl2br(htmlspecialchars($v)) : '—') . '</b></div>';
$proofExt = strtolower(pathinfo($e['payment_proof'], PATHINFO_EXTENSION));
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="support-requests.php">&larr; All requests</a></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

<div class="mem-view">
  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2><?php echo htmlspecialchars($e['name']); ?></h2><?php echo sup_status_badge($e['status']); ?></div>
      <?php echo $row('Reference', $e['ref_no']), $row('Type', $kinds[$e['kind']]['label'] ?? $e['kind']), $row('Programme', $e['programme'] ?? 'General — where needed most'),
          $row('Organisation', $e['organisation']), $row('Email', $e['email']), $row('Telephone', $e['phone']), $row('Country / district', $e['location']),
          $row('Received', date('j F Y, H:i', strtotime($e['created_at']))); ?>
      <?php if ($e['approved_at']): ?><?php echo $row('Approved', date('j F Y, H:i', strtotime($e['approved_at']))); ?><?php endif; ?>
      <?php if ($e['completed_at']): ?><?php echo $row('Completed', date('j F Y, H:i', strtotime($e['completed_at']))); ?><?php endif; ?>
      <?php if ($e['reject_reason']): ?><?php echo $row('Rejection reason', $e['reject_reason']); ?><?php endif; ?>
    </div>

    <?php if ($payable): ?>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Payment</h2></div>
      <?php echo $row('Amount', $e['amount'] !== null ? sup_money($e['amount'], $e['currency']) : ''), $row('Frequency', $e['frequency']), $row('Planned method', $e['method']),
          $row('Transaction reference', $e['payment_ref']), $row('Anonymous', $e['anonymous'] ? 'Yes' : 'No'); ?>
      <?php if ($e['payment_proof']): ?>
        <div style="margin-top:14px">
          <?php if ($proofExt === 'pdf'): ?><a class="admin-btn admin-btn-line" target="_blank" href="support-file.php?id=<?php echo $id; ?>">Open proof of payment (PDF)</a>
          <?php else: ?><a href="support-file.php?id=<?php echo $id; ?>" target="_blank"><img src="support-file.php?id=<?php echo $id; ?>" alt="Proof of payment" style="max-width:100%;max-height:380px;border:1px solid var(--line)"></a><?php endif; ?>
        </div>
      <?php else: ?><p class="admin-help" style="margin-top:12px">No proof of payment uploaded<?php echo $e['payment_ref'] ? '' : ' — the supporter said they will pay after approval'; ?>.</p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($e['details'] || $e['message']): ?>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Details</h2></div>
      <?php if ($e['details']): ?><p style="white-space:pre-wrap;background:var(--cream);padding:14px 16px"><?php echo htmlspecialchars($e['details']); ?></p><?php endif; ?>
      <?php if ($e['message']): ?><h3 class="mem-h3">Message</h3><p style="white-space:pre-wrap;background:var(--cream);padding:14px 16px"><?php echo htmlspecialchars($e['message']); ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Decision</h2></div>
      <form method="post" class="admin-form">
        <label class="mem-check"><input type="checkbox" name="send_email" value="1" checked> E-mail the supporter about this change</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
          <?php if ($e['status'] !== 'approved' && $e['status'] !== 'completed'): ?><button name="action" value="approve" class="admin-btn admin-btn-brand">Approve<?php echo $payable ? ' (sends payment instructions)' : ''; ?></button><?php endif; ?>
          <?php if ($e['status'] !== 'completed'): ?><button name="action" value="complete" class="admin-btn admin-btn-brand"><?php echo $payable ? 'Mark payment received' : 'Mark completed'; ?></button><?php endif; ?>
          <?php if ($e['status'] !== 'pending'): ?><button name="action" value="reopen" class="admin-btn admin-btn-line">Re-open as pending</button><?php endif; ?>
        </div>
        <?php if ($payable && $e['status'] !== 'completed'): ?><div class="field"><label>Amount actually received (optional — updates the amount)</label><input name="confirmed_amount" placeholder="<?php echo htmlspecialchars((string)$e['amount']); ?>"></div><?php endif; ?>
        <?php if ($e['status'] !== 'rejected'): ?>
        <div class="field"><label>Reject — reason (shown to the supporter)</label><input name="reason" placeholder="e.g. We could not verify the payment"></div>
        <button name="action" value="reject" class="admin-btn admin-btn-danger" onclick="return confirm('Reject this request?')">Reject</button>
        <?php endif; ?>
      </form>
      <form method="post" style="margin-top:12px"><input type="hidden" name="action" value="resend"><button class="admin-btn admin-btn-line admin-btn-sm">Re-send the e-mail for the current status</button></form>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Internal note</h2></div>
      <form method="post" class="admin-form"><input type="hidden" name="action" value="note"><div class="field"><textarea name="admin_note" style="min-height:80px"><?php echo htmlspecialchars($e['admin_note']); ?></textarea></div><button class="admin-btn admin-btn-line admin-btn-sm">Save note</button></form>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>E-mail log</h2></div>
      <?php if (!$emails): ?><p class="admin-help">No e-mails recorded for this address.</p><?php endif; ?>
      <?php foreach ($emails as $m): ?>
        <details style="border-bottom:1px solid var(--line);padding:8px 0"><summary style="cursor:pointer;font-size:.86rem"><b><?php echo htmlspecialchars(str_replace('support-', '', $m['kind'])); ?></b> · <?php echo htmlspecialchars($m['subject']); ?> <span class="admin-badge <?php echo $m['sent'] ? '' : 'red'; ?>"><?php echo $m['sent'] ? 'sent' : 'not delivered'; ?></span></summary>
        <pre style="white-space:pre-wrap;background:var(--cream);padding:10px;font-size:.8rem;margin-top:8px"><?php echo htmlspecialchars($m['body']); ?></pre></details>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
