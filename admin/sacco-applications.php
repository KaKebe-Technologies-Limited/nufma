<?php
$adminTitle = "SACCO applications";
$adminNav = "saccoapps";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/sacco-members.php";
sac_install($conn);

$e = fn($v) => htmlspecialchars((string)$v);
$msg = ''; $err = '';
$tab = ($_GET['tab'] ?? '') === 'settings' ? 'settings' : 'apps';
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };

if (nufa_export_format() !== '') {
    $rows = $conn->query("SELECT * FROM sacco_applications ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    $skip = ['token', 'photo', 'id_copy', 'pay_proof', 'ip', 'id', 'reject_reason'];
    $hd = $rows ? array_values(array_diff(array_keys($rows[0]), $skip)) : ['No data']; $data = [];
    foreach ($rows as $r) { foreach ($skip as $k) unset($r[$k]); $data[] = array_values($r); }
    nufa_export_send(nufa_export_format(), 'SACCO applications', $hd, $data, 'sacco-applications');
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; $r = $conn->query("SELECT photo, id_copy, pay_proof FROM sacco_applications WHERE id=$id")->fetch_assoc();
    if ($r) foreach (['photo', 'id_copy', 'pay_proof'] as $k) if ($r[$k]) @unlink(sac_storage() . basename($r[$k]));
    $conn->query("DELETE FROM sacco_applications WHERE id=$id"); header("Location: sacco-applications.php?deleted=1"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    $app = $id ? $conn->query("SELECT * FROM sacco_applications WHERE id=$id")->fetch_assoc() : null;
    $S0 = sac_settings($conn);
    if ($app && in_array($act, ['approve', 'reject', 'resend_card'], true)) {
        if ($act === 'resend_card' && $app['status'] === 'approved') {
            $v = sac_vars($app, ['{next_steps}' => $S0['sacapp_approved_next_steps']]);
            $ok = mem_send($conn, null, 'sacco-approved', $app['email'], strtr($S0['sacapp_email_approved_subject'], $v), strtr($S0['sacapp_email_approved_body'], $v));
            $conn->query("UPDATE sacco_applications SET card_sent_at=NOW() WHERE id=$id");
            $msg = $ok ? "The approval email with the card link was sent again." : "The mail system refused it (normal on a local XAMPP install). The card link is: " . sac_card_url($app);
        } elseif ($act === 'approve') {
            $minSh = max(2, (int)$S0['sacapp_min_shares']); $sh = (int)($_POST['shares_confirmed'] ?? 0); $feeC = mb_substr(trim($_POST['fee_confirmed'] ?? ''), 0, 40); $shv = mb_substr(trim($_POST['shares_value'] ?? ''), 0, 40);
            if (empty($_POST['pay_ok'])) $err = "Confirm that you have verified the membership fee and share payment before approving.";
            elseif ($sh < $minSh) $err = "A member must hold at least $minSh shares. Enter the number of shares paid for ($minSh or more).";
            else {
                $until = sac_valid_until($S0);
                $u = $conn->prepare("UPDATE sacco_applications SET status='approved', approved_at=COALESCE(approved_at, NOW()), reject_reason='', pay_received=1, pay_verified=1, shares_confirmed=?, fee_confirmed=?, shares_value=?, card_issued_at=COALESCE(card_issued_at, NOW()), card_valid_until=COALESCE(card_valid_until, ?), card_sent_at=NOW() WHERE id=?");
                $u->bind_param("isssi", $sh, $feeC, $shv, $until, $id); $u->execute();
                $app = $conn->query("SELECT * FROM sacco_applications WHERE id=$id")->fetch_assoc();
                $v = sac_vars($app, ['{next_steps}' => $S0['sacapp_approved_next_steps']]);
                $ok = mem_send($conn, null, 'sacco-approved', $app['email'], strtr($S0['sacapp_email_approved_subject'], $v), strtr($S0['sacapp_email_approved_body'], $v));
                $msg = "Application approved and the membership card was issued" . ($ok ? " — the approval email with the card link was sent." : " (the email could not be sent from this server — it is saved in the email log).");
            }
        } else {
            $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 500);
            if ($reason === '') $err = "Enter a reason for rejecting the application.";
            else {
                $u = $conn->prepare("UPDATE sacco_applications SET status='rejected', reject_reason=? WHERE id=?"); $u->bind_param("si", $reason, $id); $u->execute();
                $app = $conn->query("SELECT * FROM sacco_applications WHERE id=$id")->fetch_assoc(); $v = sac_vars($app);
                $ok = mem_send($conn, null, 'sacco-rejected', $app['email'], strtr($S0['sacapp_email_rejected_subject'], $v), strtr($S0['sacapp_email_rejected_body'], $v));
                $msg = "Application rejected" . ($ok ? " and the applicant was emailed." : " (the email could not be sent from this server).");
            }
        }
        $_GET['view'] = $id;
    } elseif ($app && $act === 'note') {
        $u = $conn->prepare("UPDATE sacco_applications SET admin_note=?, pay_received=? WHERE id=?"); $n = mb_substr(trim($_POST['admin_note'] ?? ''), 0, 500); $pr = isset($_POST['pay_received']) ? 1 : 0; $u->bind_param("sii", $n, $pr, $id); $u->execute(); $msg = "Saved."; $_GET['view'] = $id;
    } elseif ($act === 'settings') {
        $tab = 'settings';
        foreach (['sacapp_reg_prefix', 'sacapp_reg_format', 'sacapp_reg_fee', 'sacapp_share_price', 'sacapp_pay_instructions', 'sacapp_intro', 'sacapp_declaration', 'sacapp_income_bands', 'sacapp_pay_methods', 'sacapp_notify_email',
                  'sacapp_email_received_subject', 'sacapp_email_received_body', 'sacapp_email_approved_subject', 'sacapp_email_approved_body', 'sacapp_approved_next_steps', 'sacapp_signer_name', 'sacapp_signer_title', 'sacapp_card_note', 'sacapp_email_rejected_subject', 'sacapp_email_rejected_body'] as $k) $save($k, mb_substr(trim($_POST[$k] ?? ''), 0, 4000));
        $save('sacapp_card_validity_months', (string)max(0, (int)($_POST['sacapp_card_validity_months'] ?? 0)));
        $save('sacapp_reg_pad', (string)max(1, min(8, (int)($_POST['sacapp_reg_pad'] ?? 4)))); $save('sacapp_min_shares', (string)max(2, (int)($_POST['sacapp_min_shares'] ?? 2)));
        foreach (['sacapp_require_photo', 'sacapp_require_id', 'sacapp_require_proof'] as $k) $save($k, isset($_POST[$k]) ? '1' : '0');
        $next = max(1, (int)($_POST['sacapp_reg_next'] ?? 1)); $maxSeq = (int)$conn->query("SELECT COUNT(*) FROM sacco_applications")->fetch_row()[0]; $save('sacapp_reg_next', (string)$next);
        if (!filter_var(trim($_POST['sacapp_notify_email'] ?? ''), FILTER_VALIDATE_EMAIL) && trim($_POST['sacapp_notify_email'] ?? '') !== '') $err = "The notification email is not valid.";
        else $msg = "Settings saved.";
    }
}

$S = sac_settings($conn, true);
$view = (int)($_GET['view'] ?? 0); $app = $view ? $conn->query("SELECT * FROM sacco_applications WHERE id=$view")->fetch_assoc() : null;
$filter = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : '';
$q = trim($_GET['q'] ?? '');
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0]; foreach ($conn->query("SELECT status, COUNT(*) c FROM sacco_applications GROUP BY status")->fetch_all(MYSQLI_ASSOC) as $c) $counts[$c['status']] = (int)$c['c'];
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="sacco-manager.php">← SACCO manager</a><a href="sacco-applications.php" class="<?php echo $tab === 'apps' ? 'active' : ''; ?>">Applications (<?php echo array_sum($counts); ?>)</a><a href="sacco-applications.php?tab=settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">Form, fees &amp; numbering</a><a href="../sacco-apply.php" target="_blank">View the form ↗</a></div>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Application deleted.</div><?php endif; ?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>
<?php $cfgNow = nufa_site_settings($conn); if (($cfgNow['sacco_applications_open'] ?? '0') !== '1'): ?><div class="admin-alert" style="background:#fff8e6;border:1px solid #ecd9a3;color:#6b4e0a">The public application form is <b>closed</b> (the SACCO is not accepting applications). Switch it on in <a href="sacco-manager.php">SACCO manager → Status &amp; regulatory</a> only once the SACCO's registration and readiness are verified.</div><?php endif; ?>

<?php if ($tab === 'settings'): $nextPreview = strtr($S['sacapp_reg_format'], ['{PREFIX}' => $S['sacapp_reg_prefix'], '{YEAR}' => date('Y'), '{SEQ}' => str_pad($S['sacapp_reg_next'], max(1, (int)$S['sacapp_reg_pad']), '0', STR_PAD_LEFT)]); ?>
<form method="post" class="admin-form"><input type="hidden" name="action" value="settings">
  <div class="admin-card"><div class="admin-card-head"><h2>Registration numbers</h2><span class="admin-help">Every applicant gets one automatically the moment they submit.</span></div>
    <div class="admin-form-grid"><div class="field"><label>Prefix</label><input name="sacapp_reg_prefix" value="<?php echo $e($S['sacapp_reg_prefix']); ?>"></div>
      <div class="field"><label>Format</label><input name="sacapp_reg_format" value="<?php echo $e($S['sacapp_reg_format']); ?>"><span class="admin-help">Use {PREFIX}, {YEAR} and {SEQ}</span></div>
      <div class="field"><label>Number of digits</label><input type="number" min="1" max="8" name="sacapp_reg_pad" value="<?php echo $e($S['sacapp_reg_pad']); ?>"></div>
      <div class="field"><label>Next sequence number</label><input type="number" min="1" name="sacapp_reg_next" value="<?php echo $e($S['sacapp_reg_next']); ?>"></div></div>
    <p class="admin-help">The next applicant will receive: <b><?php echo $e($nextPreview); ?></b></p></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Fees &amp; shares (shown on the form)</h2><span class="admin-help">Leave blank if there is no fee yet — the payment section is then hidden.</span></div>
    <div class="admin-form-grid"><div class="field"><label>Registration fee</label><input name="sacapp_reg_fee" value="<?php echo $e($S['sacapp_reg_fee']); ?>" placeholder="e.g. UGX 20,000"></div>
      <div class="field"><label>Price of one share</label><input name="sacapp_share_price" value="<?php echo $e($S['sacapp_share_price']); ?>" placeholder="e.g. UGX 10,000"></div>
      <div class="field"><label>Minimum shares per member</label><input type="number" min="2" name="sacapp_min_shares" value="<?php echo $e($S['sacapp_min_shares']); ?>"><span class="admin-help">Never below 2.</span></div></div>
    <div class="field"><label>How to pay the fee (account / mobile money details, shown to applicants)</label><textarea name="sacapp_pay_instructions" style="min-height:90px"><?php echo $e($S['sacapp_pay_instructions']); ?></textarea></div>
    <div class="field"><label>Payment methods (one per line)</label><textarea name="sacapp_pay_methods" style="min-height:70px"><?php echo $e($S['sacapp_pay_methods']); ?></textarea></div>
    <p class="admin-help" style="margin-top:10px"><b>The membership fee and shares are always paid with the application</b> — the form requires the payment method, reference, date, amount (checked against fee + shares when both are numbers) and proof of payment.</p>
    <label class="mem-check"><input type="checkbox" name="sacapp_require_photo" value="1" <?php echo $S['sacapp_require_photo'] === '1' ? 'checked' : ''; ?>> Passport photo is required</label>
    <label class="mem-check"><input type="checkbox" name="sacapp_require_id" value="1" <?php echo $S['sacapp_require_id'] === '1' ? 'checked' : ''; ?>> A copy of the ID is required</label>
    <label class="mem-check"><input type="checkbox" name="sacapp_require_proof" value="1" <?php echo $S['sacapp_require_proof'] === '1' ? 'checked' : ''; ?>> Proof of payment is required (only when a fee is set)</label></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Membership card</h2><span class="admin-help">Issued automatically when you approve an application. The signature image comes from Membership settings (president's signature).</span></div>
    <div class="admin-form-grid"><div class="field"><label>Signed by (name) — blank uses the president's name</label><input name="sacapp_signer_name" value="<?php echo $e($S['sacapp_signer_name']); ?>"></div><div class="field"><label>Signer's title on the card</label><input name="sacapp_signer_title" value="<?php echo $e($S['sacapp_signer_title']); ?>"></div>
      <div class="field"><label>Card valid for (months; 0 = no expiry)</label><input type="number" min="0" name="sacapp_card_validity_months" value="<?php echo $e($S['sacapp_card_validity_months']); ?>"></div></div>
    <div class="field"><label>Note printed on the card</label><textarea name="sacapp_card_note" style="min-height:60px"><?php echo $e($S['sacapp_card_note']); ?></textarea></div></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Form text</h2></div>
    <div class="field"><label>Introduction above the form</label><textarea name="sacapp_intro" style="min-height:80px"><?php echo $e($S['sacapp_intro']); ?></textarea></div>
    <div class="field"><label>Declaration shown before the tick-boxes (one point per line)</label><textarea name="sacapp_declaration" style="min-height:80px"><?php echo $e($S['sacapp_declaration']); ?></textarea></div>
    <div class="field"><label>Income bands (one per line; leave empty to hide the question)</label><textarea name="sacapp_income_bands" style="min-height:90px"><?php echo $e($S['sacapp_income_bands']); ?></textarea></div></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Emails</h2><span class="admin-help">Placeholders: {name} {reg_no} {reason} {next_steps} {shares} {card_link} {valid_line}</span></div>
    <div class="field"><label>Send new-application alerts to</label><input type="email" name="sacapp_notify_email" value="<?php echo $e($S['sacapp_notify_email']); ?>" placeholder="Defaults to the membership alert address"></div>
    <?php foreach (['received' => 'Application received (sent immediately, with the registration number)', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?>
    <h3 class="mem-h3"><?php echo $l; ?></h3><div class="field"><label>Subject</label><input name="sacapp_email_<?php echo $k; ?>_subject" value="<?php echo $e($S["sacapp_email_{$k}_subject"]); ?>"></div><div class="field"><label>Message</label><textarea name="sacapp_email_<?php echo $k; ?>_body" style="min-height:110px"><?php echo $e($S["sacapp_email_{$k}_body"]); ?></textarea></div>
    <?php endforeach; ?>
    <div class="field"><label>“Next steps” text inserted into the approval email</label><textarea name="sacapp_approved_next_steps" style="min-height:70px"><?php echo $e($S['sacapp_approved_next_steps']); ?></textarea></div></div>
  <button class="admin-btn admin-btn-brand">Save settings</button>
</form>

<?php elseif ($app): $a = $app; ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($a['full_name']); ?> <?php echo sac_status_badge($a['status']); ?></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="sacco-applications.php">← All applications</a></div>
  <?php require_once __DIR__ . "/../includes/whatsapp.php"; if (($wa = nufa_wa_reply_url($a['phone'], 'Hello ' . $a['full_name'] . ', this is the NUFA SACCO about your application ' . $a['reg_no'] . '. '))): ?><p><a class="admin-btn admin-btn-line admin-btn-sm" href="<?php echo $e($wa); ?>" target="_blank" rel="noopener" style="border-color:#25D366;color:#128c4a">Message on WhatsApp</a></p><?php endif; ?>
  <p><b>Registration number:</b> <span class="reg-badge-sm"><?php echo $e($a['reg_no']); ?></span> · submitted <?php echo date('d M Y H:i', strtotime($a['created_at'])); ?><?php echo $a['approved_at'] ? ' · approved ' . date('d M Y', strtotime($a['approved_at'])) : ''; ?></p>
  <div class="app-files"><?php foreach (['photo' => 'Passport photo', 'id_copy' => 'ID copy', 'pay_proof' => 'Proof of payment'] as $k => $l): if ($a[$k] === '') continue; $img = preg_match('/\.(jpg|png|webp)$/i', $a[$k]); ?>
    <a class="app-file" href="sacco-file.php?id=<?php echo $a['id']; ?>&f=<?php echo $k; ?>" target="_blank"><?php if ($img): ?><img src="sacco-file.php?id=<?php echo $a['id']; ?>&f=<?php echo $k; ?>" alt=""><?php else: ?><span>PDF</span><?php endif; ?><b><?php echo $l; ?></b></a><?php endforeach; ?></div>
  <?php foreach (sac_sections() as $title => $fields): $rows = array_filter($fields, fn($f) => trim((string)$a[$f[1]]) !== ''); if (!$rows) continue; ?>
  <h3 class="mem-h3"><?php echo $e($title); ?></h3><dl class="app-dl"><?php foreach ($rows as [$l, $c]): ?><dt><?php echo $e($l); ?></dt><dd><?php echo nl2br($e($a[$c])); ?></dd><?php endforeach; ?></dl>
  <?php endforeach; ?>
  <p class="admin-help">Signed (typed) by <b><?php echo $e($a['signature_name']); ?></b> after accepting the three declarations.</p>
</div>
<div class="edit-grid"><div class="admin-card"><div class="admin-card-head"><h2>Decision</h2></div>
  <?php if ($a['reject_reason']): ?><div class="admin-alert err">Rejected: <?php echo $e($a['reject_reason']); ?></div><?php endif; ?>
  <?php $due = sac_total_due($S, (int)$a['shares_applied']); $minSh = max(2, (int)$S['sacapp_min_shares']); ?>
  <div class="admin-alert" style="background:#fff8e6;border:1px solid #ecd9a3;color:#6b4e0a"><b>Payment to verify:</b> applicant says they paid <b><?php echo $e($a['pay_amount'] ?: '—'); ?></b> by <?php echo $e($a['pay_method'] ?: '—'); ?> (ref <?php echo $e($a['pay_ref'] ?: '—'); ?><?php echo $a['pay_date'] ? ', ' . date('d M Y', strtotime($a['pay_date'])) : ''; ?>). <?php if ($due): ?><br>Expected: fee <?php echo number_format($due[0]); ?> + <?php echo (int)$a['shares_applied']; ?> share(s) × <?php echo number_format($due[1]); ?> = <b><?php echo number_format($due[2]); ?></b>.<?php endif; ?> <?php echo $a['pay_proof'] ? 'Check the uploaded proof above.' : '<b>No proof was uploaded.</b>'; ?></div>
  <?php if ($a['status'] === 'approved'): ?><div class="admin-alert ok">Approved — card issued <?php echo $a['card_issued_at'] ? date('d M Y', strtotime($a['card_issued_at'])) : ''; ?>. Shares confirmed: <b><?php echo (int)$a['shares_confirmed']; ?></b>.</div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px"><a class="admin-btn admin-btn-brand" href="<?php echo $e(sac_card_url($a)); ?>" target="_blank">Open the member's card</a><form method="post"><input type="hidden" name="action" value="resend_card"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><button class="admin-btn admin-btn-line">Email the card link again</button></form></div><?php endif; ?>
  <form method="post" class="admin-form" onsubmit="return confirm('Approve this member and issue the membership card?')"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?php echo $a['id']; ?>">
    <h3 class="mem-h3" style="margin-top:0"><?php echo $a['status'] === 'approved' ? 'Update what was verified' : 'Verify payment & approve'; ?></h3>
    <div class="admin-form-grid"><div class="field"><label>Shares paid for (minimum <?php echo $minSh; ?>)</label><input type="number" min="<?php echo $minSh; ?>" name="shares_confirmed" value="<?php echo (int)($a['shares_confirmed'] ?: $a['shares_applied']); ?>"></div>
      <div class="field"><label>Membership fee received</label><input name="fee_confirmed" value="<?php echo $e($a['fee_confirmed'] ?: $S['sacapp_reg_fee']); ?>"></div>
      <div class="field"><label>Value of shares received</label><input name="shares_value" value="<?php echo $e($a['shares_value']); ?>" placeholder="e.g. UGX 20,000"></div></div>
    <label class="mem-check"><input type="checkbox" name="pay_ok" value="1" <?php echo $a['pay_verified'] ? 'checked' : ''; ?>> I have checked the proof of payment and confirm the membership fee and at least <?php echo $minSh; ?> shares have been paid</label>
    <button class="admin-btn admin-btn-brand"><?php echo $a['status'] === 'approved' ? 'Save' : 'Approve, issue card &amp; email'; ?></button></form>
  <form method="post" class="admin-form" style="margin-top:14px"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><div class="field"><label>Reason for rejecting (sent to the applicant)</label><input name="reason" value="<?php echo $e($a['reject_reason']); ?>"></div><button class="admin-btn admin-btn-danger" onclick="return confirm('Reject this application and email the applicant?')">Reject &amp; email</button></form>
  <p class="admin-help" style="margin-top:10px">Emails are sent through the server's mail system; failed or local sends are kept in the email log.</p></div>
<div class="admin-card"><div class="admin-card-head"><h2>Internal notes</h2></div><form method="post" class="admin-form"><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?php echo $a['id']; ?>">
  <label class="mem-check"><input type="checkbox" name="pay_received" value="1" <?php echo $a['pay_received'] ? 'checked' : ''; ?>> Registration fee received &amp; verified</label>
  <div class="field"><label>Note (not shown to the applicant)</label><textarea name="admin_note" style="min-height:80px"><?php echo $e($a['admin_note']); ?></textarea></div><button class="admin-btn admin-btn-line">Save</button></form>
  <p style="margin-top:14px"><a class="admin-btn admin-btn-danger admin-btn-sm" href="sacco-applications.php?delete=<?php echo $a['id']; ?>" data-confirm="Delete this application and its uploaded files permanently?">Delete application</a></p></div></div>

<?php else:
    $where = []; if ($filter) $where[] = "status='$filter'"; if ($q !== '') { $qq = $conn->real_escape_string($q); $where[] = "(full_name LIKE '%$qq%' OR reg_no LIKE '%$qq%' OR email LIKE '%$qq%' OR phone LIKE '%$qq%' OR id_number LIKE '%$qq%')"; }
    $rows = $conn->query("SELECT id, reg_no, full_name, phone, email, shares_applied, status, created_at, pay_received FROM sacco_applications" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY id DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC); ?>
<div class="stat-row"><div><b><?php echo $counts['pending']; ?></b><span>Pending</span></div><div><b><?php echo $counts['approved']; ?></b><span>Approved</span></div><div><b><?php echo $counts['rejected']; ?></b><span>Rejected</span></div></div>
<div class="admin-card"><div class="admin-card-head"><h2>Applications</h2><span><form method="get" style="display:inline-flex;gap:8px"><select name="status" onchange="this.form.submit()" style="padding:8px 10px;border:1px solid #e4e0d6;border-radius:8px"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected'] as $s) echo '<option value="' . $s . '"' . ($filter === $s ? ' selected' : '') . '>' . ucfirst($s) . '</option>'; ?></select><input name="q" value="<?php echo $e($q); ?>" placeholder="Search name, number, phone…" style="padding:8px 12px;border:1px solid #e4e0d6;border-radius:8px"><button class="admin-btn admin-btn-line admin-btn-sm">Search</button></form> <?php echo nufa_export_menu('sacco-applications.php'); ?></span></div>
  <table class="admin-table"><thead><tr><th>Registration no.</th><th>Applicant</th><th>Phone</th><th>Shares</th><th>Fee</th><th>Status</th><th>Submitted</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--text-muted)">No applications yet.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?><tr><td><b><?php echo $e($r['reg_no']); ?></b></td><td><?php echo $e($r['full_name']); ?><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e($r['phone']); ?></td><td><?php echo (int)$r['shares_applied']; ?></td><td><?php echo $r['pay_received'] ? '✔ received' : '<small class="muted">—</small>'; ?></td><td><?php echo sac_status_badge($r['status']); ?></td><td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="sacco-applications.php?view=<?php echo $r['id']; ?>">Open</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
