<?php
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/membership.php";

$id = (int)($_GET['id'] ?? 0);
$load = function () use ($conn, $id) {
    return $conn->query("SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE m.id=$id")->fetch_assoc();
};
$m = $load();
if (!$m) { header("Location: members.php"); exit; }
$S = mem_settings($conn);

$flash = ''; $flashType = 'ok';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) {
        $flash = "Session expired — please try again."; $flashType = 'err';
    } else {
        $act = $_POST['action'] ?? '';

        if ($act === 'approve') {
            $from = $_POST['valid_from'] ?: date('Y-m-d');
            $until = $_POST['valid_until'] ?: date('Y-m-d', strtotime("$from +" . max(1, (int)$S['validity_months']) . " months -1 day"));
            if (strtotime($until) < strtotime($from)) {
                $flash = "“Valid until” must be after “Valid from”."; $flashType = 'err';
            } else {
                $reg = $m['reg_no'] ?: mem_next_reg_no($conn);
                $u = $conn->prepare("UPDATE members SET status='approved', reg_no=?, valid_from=?, valid_until=?, reject_reason='', approved_at=COALESCE(approved_at, NOW()) WHERE id=?");
                $u->bind_param("sssi", $reg, $from, $until, $id);
                $u->execute();
                $m = $load();
                $flash = "Approved. Registration number: " . $reg . ".";
                if (!empty($_POST['send_email'])) {
                    $v = mem_member_vars($conn, $m);
                    $ok = mem_send($conn, $id, 'approved', $m['email'], mem_fill($S['email_approved_subject'], $v), mem_fill($S['email_approved_body'], $v), $v['card_link']);
                    $flash .= $ok ? " Email with the registration number and card link sent to " . $m['email'] . "."
                                  : " The email could NOT be sent (mail isn't available on this server) — it's saved under “Email log” below; you can copy the card link manually.";
                    if (!$ok) $flashType = 'warn';
                }
            }
        } elseif ($act === 'reject') {
            $reason = trim($_POST['reason'] ?? '');
            $u = $conn->prepare("UPDATE members SET status='rejected', reject_reason=? WHERE id=?");
            $u->bind_param("si", $reason, $id); $u->execute();
            $m = $load();
            $flash = "Application rejected.";
            if (!empty($_POST['send_email'])) {
                $v = mem_member_vars($conn, $m, ['reason' => $reason ?: 'Not specified']);
                $ok = mem_send($conn, $id, 'rejected', $m['email'], mem_fill($S['email_rejected_subject'], $v), mem_fill($S['email_rejected_body'], $v));
                $flash .= $ok ? " The applicant was notified." : " The notification email could not be sent.";
                if (!$ok) $flashType = 'warn';
            }
        } elseif ($act === 'resend' && $m['status'] === 'approved') {
            $v = mem_member_vars($conn, $m);
            $ok = mem_send($conn, $id, 'approved', $m['email'], mem_fill($S['email_approved_subject'], $v), mem_fill($S['email_approved_body'], $v), $v['card_link']);
            $flash = $ok ? "Approval email re-sent to " . $m['email'] . "." : "The email could not be sent from this server."; if (!$ok) $flashType = 'warn';
        } elseif ($act === 'note') {
            $n = trim($_POST['admin_note'] ?? '');
            $u = $conn->prepare("UPDATE members SET admin_note=? WHERE id=?"); $u->bind_param("si", $n, $id); $u->execute();
            $m = $load(); $flash = "Note saved.";
        } elseif ($act === 'delete') {
            foreach (['photo', 'payment_proof'] as $col) if ($m[$col]) @unlink(MEM_STORAGE_DIR . basename($m[$col]));
            $conn->query("DELETE FROM member_emails WHERE member_id=$id");
            $conn->query("DELETE FROM members WHERE id=$id");
            header("Location: members.php?deleted=1"); exit;
        }
    }
}

$adminTitle = $m['full_name'];
$emails = $conn->query("SELECT * FROM member_emails WHERE member_id=$id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$defFrom = $m['valid_from'] ?: date('Y-m-d');
$defUntil = $m['valid_until'] ?: date('Y-m-d', strtotime("$defFrom +" . max(1, (int)$S['validity_months']) . " months -1 day"));
$previewReg = $m['reg_no'] ?: mem_format_reg($S, max(1, (int)$S['reg_next']));
$proofIsPdf = strtolower(pathinfo($m['payment_proof'], PATHINFO_EXTENSION)) === 'pdf';
include __DIR__ . "/includes/admin-header.php";
$row = fn($l, $v) => '<div class="mem-row"><span>' . $l . '</span><b>' . ($v !== '' && $v !== null ? htmlspecialchars($v) : '—') . '</b></div>';
?>
<div class="mem-tabs"><a href="members.php">&larr; All applications</a></div>

<?php if ($flash): ?><div class="admin-alert <?php echo $flashType === 'err' ? 'err' : ($flashType === 'warn' ? 'err' : 'ok'); ?>"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

<div class="mem-view">
  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Applicant</h2><?php echo mem_status_badge($m['status']); ?></div>
      <div class="mem-profile">
        <?php if ($m['photo']): ?><a href="member-file.php?id=<?php echo $id; ?>&f=photo" target="_blank"><img src="member-file.php?id=<?php echo $id; ?>&f=photo" alt="Photo"></a><?php endif; ?>
        <div style="flex:1">
          <?php echo $row('Full name', $m['full_name']), $row('Email', $m['email']), $row('Phone', $m['phone']), $row('Gender', $m['gender']),
                $row('Date of birth', $m['dob'] ? date('j M Y', strtotime($m['dob'])) : ''), $row('ID / passport', $m['id_number']),
                $row('District / town', $m['district']), $row('Address', $m['address']); ?>
        </div>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Craft</h2></div>
      <?php echo $row('Guild', $m['guild_name']), $row('Occupation', $m['occupation']), $row('Experience', $m['experience']), $row('Portfolio', $m['portfolio_url']); ?>
      <?php if ($m['bio']): ?><p style="margin-top:12px;background:var(--cream);padding:12px 14px;white-space:pre-wrap;font-size:.9rem"><?php echo htmlspecialchars($m['bio']); ?></p><?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Payment</h2></div>
      <?php echo $row('Method', $m['payment_method']), $row('Reference', $m['payment_ref']), $row('Amount', $m['amount_paid']),
            $row('Date paid', $m['payment_date'] ? date('j M Y', strtotime($m['payment_date'])) : ''), $row('Fee expected', trim($S['membership_currency'] . ' ' . $S['membership_fee'])); ?>
      <?php if ($m['payment_proof']): ?>
        <div style="margin-top:14px">
        <?php if ($proofIsPdf): ?>
          <a class="admin-btn admin-btn-line" target="_blank" href="member-file.php?id=<?php echo $id; ?>&f=proof">Open proof of payment (PDF)</a>
        <?php else: ?>
          <a href="member-file.php?id=<?php echo $id; ?>&f=proof" target="_blank"><img src="member-file.php?id=<?php echo $id; ?>&f=proof" alt="Proof of payment" style="max-width:100%;max-height:420px;border:1px solid var(--line)"></a>
        <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2><?php echo $m['status'] === 'approved' ? 'Membership' : 'Decision'; ?></h2></div>
      <?php if ($m['status'] === 'approved'): ?>
        <div class="mem-regbox"><span>NUFA registration number</span><b><?php echo htmlspecialchars($m['reg_no']); ?></b></div>
        <a class="admin-btn admin-btn-brand" style="margin:12px 0" target="_blank" href="../card.php?t=<?php echo $m['card_token']; ?>">View membership card</a>
        <p style="font-size:.8rem;color:var(--text-muted);word-break:break-all">Card link: <?php echo htmlspecialchars(mem_card_link($m)); ?></p>
      <?php else: ?>
        <div class="mem-regbox"><span>Registration number on approval</span><b><?php echo htmlspecialchars($previewReg); ?></b></div>
      <?php endif; ?>

      <form method="post" class="admin-form" style="margin-top:16px">
        <?php echo mem_csrf_field(); ?>
        <input type="hidden" name="action" value="approve">
        <div class="admin-form-grid">
          <div class="field"><label>Valid from</label><input type="date" name="valid_from" value="<?php echo $defFrom; ?>"></div>
          <div class="field"><label>Valid until</label><input type="date" name="valid_until" value="<?php echo $defUntil; ?>"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;font-weight:600;margin-bottom:14px"><input type="checkbox" name="send_email" value="1" checked style="width:auto"> Email the member their registration number &amp; card</label>
        <button class="admin-btn admin-btn-brand"><?php echo $m['status'] === 'approved' ? 'Update validity' : 'Approve member'; ?></button>
      </form>

      <?php if ($m['status'] === 'approved'): ?>
      <form method="post" style="margin-top:10px"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="resend"><button class="admin-btn admin-btn-line admin-btn-sm">Re-send approval email</button></form>
      <?php endif; ?>

      <?php if ($m['status'] !== 'rejected'): ?>
      <hr style="border:0;border-top:1px solid var(--line);margin:20px 0">
      <form method="post" class="admin-form">
        <?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="reject">
        <div class="field"><label>Reject — reason (shown to applicant)</label><input type="text" name="reason" placeholder="e.g. Payment could not be verified"></div>
        <label style="display:flex;gap:8px;align-items:center;font-weight:600;margin-bottom:12px"><input type="checkbox" name="send_email" value="1" checked style="width:auto"> Notify applicant by email</label>
        <button class="admin-btn admin-btn-danger" onclick="return confirm('Reject this application?')">Reject application</button>
      </form>
      <?php elseif ($m['reject_reason']): ?>
        <p style="margin-top:12px;color:var(--red-600)"><b>Rejected:</b> <?php echo htmlspecialchars($m['reject_reason']); ?></p>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Internal note</h2></div>
      <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="note">
        <div class="field"><textarea name="admin_note" style="min-height:80px"><?php echo htmlspecialchars($m['admin_note']); ?></textarea></div>
        <button class="admin-btn admin-btn-line admin-btn-sm">Save note</button></form>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Email log</h2></div>
      <?php if (!$emails): ?><p style="color:var(--text-muted);font-size:.88rem">No emails sent yet.</p><?php endif; ?>
      <?php foreach ($emails as $e): ?>
        <details style="border-bottom:1px solid var(--line);padding:8px 0">
          <summary style="cursor:pointer;font-size:.86rem"><b><?php echo htmlspecialchars($e['kind']); ?></b> · <?php echo htmlspecialchars($e['subject']); ?>
            <span class="admin-badge <?php echo $e['sent'] ? '' : 'red'; ?>"><?php echo $e['sent'] ? 'sent' : 'not delivered'; ?></span>
            <span style="color:var(--text-muted);font-size:.75rem"><?php echo date('d M H:i', strtotime($e['created_at'])); ?> → <?php echo htmlspecialchars($e['to_email']); ?></span></summary>
          <pre style="white-space:pre-wrap;background:var(--cream);padding:10px;font-size:.8rem;margin-top:8px"><?php echo htmlspecialchars($e['body']); ?></pre>
        </details>
      <?php endforeach; ?>
    </div>

    <form method="post" onsubmit="return confirm('Permanently delete this member and their uploaded files?')">
      <?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="delete">
      <button class="admin-btn admin-btn-danger admin-btn-sm">Delete member</button>
    </form>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
