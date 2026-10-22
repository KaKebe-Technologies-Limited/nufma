<?php
$adminTitle = "Membership settings";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/membership.php";

$msg = ''; $err = '';
$S = mem_settings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $err = "Session expired — try again."; }
    else {
        $text = ['membership_fee', 'membership_currency', 'payment_instructions', 'reg_prefix', 'reg_format', 'president_name', 'president_title',
                 'card_note', 'email_from', 'email_from_name', 'admin_notify_email',
                 'email_received_subject', 'email_received_body', 'email_approved_subject', 'email_approved_body', 'email_rejected_subject', 'email_rejected_body'];
        foreach ($text as $k) mem_save_setting($conn, $k, trim($_POST[$k] ?? ''));
        mem_save_setting($conn, 'membership_open', isset($_POST['membership_open']) ? '1' : '0');
        mem_save_setting($conn, 'send_receipt_email', isset($_POST['send_receipt_email']) ? '1' : '0');
        mem_save_setting($conn, 'notify_admin', isset($_POST['notify_admin']) ? '1' : '0');
        mem_save_setting($conn, 'reg_pad', (string)max(1, min(10, (int)($_POST['reg_pad'] ?? 4))));
        mem_save_setting($conn, 'reg_next', (string)max(1, (int)($_POST['reg_next'] ?? 1)));
        mem_save_setting($conn, 'validity_months', (string)max(1, min(120, (int)($_POST['validity_months'] ?? 12))));
        if (strpos($_POST['reg_format'] ?? '', '{SEQ}') === false) {
            mem_save_setting($conn, 'reg_format', '{PREFIX}/{YEAR}/{SEQ}');
            $err = "The registration format must contain {SEQ} — reset to the default.";
        }

        // president signature
        $sigDir = __DIR__ . "/../assets/images/uploads/signature/";
        if (!empty($_POST['remove_signature']) && $S['president_signature']) {
            @unlink(__DIR__ . "/../assets/images/" . $S['president_signature']);
            mem_save_setting($conn, 'president_signature', '');
            $S['president_signature'] = '';
        }
        if (!empty($_FILES['president_signature']['name'])) {
            [$fn, $e] = mem_store_upload($_FILES['president_signature'], MEM_IMG_TYPES, 2 * 1048576, 'president-signature', $sigDir);
            if ($e) $err = "Signature: " . $e;
            else {
                if ($S['president_signature']) @unlink(__DIR__ . "/../assets/images/" . $S['president_signature']);
                mem_save_setting($conn, 'president_signature', 'uploads/signature/' . $fn);
            }
        }
        $S = mem_settings($conn, true);
        if (!$err) $msg = "Membership settings saved.";
    }
}
$sigOk = $S['president_signature'] && is_file(__DIR__ . "/../assets/images/" . $S['president_signature']);
include __DIR__ . "/includes/admin-header.php";
$v = fn($k) => htmlspecialchars($S[$k]);
?>
<div class="mem-tabs"><a href="member-dashboard.php">Dashboard</a><a href="members.php">Applications</a><a href="guilds.php">Guilds</a><a href="membership-settings.php" class="active">Membership settings</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $msg; ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form"><?php echo mem_csrf_field(); ?>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Registration &amp; payment</h2></div>
    <label class="mem-check" style="margin-bottom:16px"><input type="checkbox" name="membership_open" value="1" <?php echo $S['membership_open'] === '1' ? 'checked' : ''; ?>> Accept new membership applications</label>
    <div class="admin-form-grid">
      <div class="field"><label>Membership fee</label><input name="membership_fee" value="<?php echo $v('membership_fee'); ?>" placeholder="50,000"></div>
      <div class="field"><label>Currency</label><input name="membership_currency" value="<?php echo $v('membership_currency'); ?>"></div>
    </div>
    <div class="field"><label>Extra payment note (the bank and Mobile Money details come from Payment details in the menu)</label><textarea name="payment_instructions" style="min-height:130px"><?php echo $v('payment_instructions'); ?></textarea></div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>NUFA registration numbers</h2><span class="admin-help">Next number: <b><?php echo htmlspecialchars(mem_format_reg($S, max(1, (int)$S['reg_next']))); ?></b></span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Prefix</label><input name="reg_prefix" value="<?php echo $v('reg_prefix'); ?>"></div>
      <div class="field"><label>Format</label><input name="reg_format" value="<?php echo $v('reg_format'); ?>"><small class="admin-help">Use {PREFIX}, {YEAR} and {SEQ}. Example: {PREFIX}/{YEAR}/{SEQ} → NUFA/<?php echo date('Y'); ?>/0001</small></div>
      <div class="field"><label>Number length (digits)</label><input type="number" min="1" max="10" name="reg_pad" value="<?php echo $v('reg_pad'); ?>"></div>
      <div class="field"><label>Next sequence number</label><input type="number" min="1" name="reg_next" value="<?php echo $v('reg_next'); ?>"></div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Membership card</h2></div>
    <div class="admin-form-grid">
      <div class="field"><label>Validity period (months from approval)</label><input type="number" min="1" max="120" name="validity_months" value="<?php echo $v('validity_months'); ?>"><small class="admin-help">You can still change the dates for an individual member when approving.</small></div>
      <div class="field"><label>President's name</label><input name="president_name" value="<?php echo $v('president_name'); ?>"></div>
      <div class="field"><label>Title under signature</label><input name="president_title" value="<?php echo $v('president_title'); ?>"></div>
      <div class="field"><label>Small print on the card</label><input name="card_note" value="<?php echo $v('card_note'); ?>"></div>
    </div>
    <div class="field">
      <label>President's signature (PNG with transparent background works best)</label>
      <?php if ($sigOk): ?>
        <div class="mem-sig"><img src="../assets/images/<?php echo htmlspecialchars($S['president_signature']); ?>" alt="Signature"></div>
        <label class="mem-check"><input type="checkbox" name="remove_signature" value="1"> Remove current signature</label>
      <?php endif; ?>
      <input type="file" name="president_signature" accept="image/png,image/jpeg,image/webp">
    </div>
    <p class="admin-help">To preview a real card: Applications → open an approved member → View membership card.</p>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Emails</h2><span class="admin-help">Placeholders: {name} {reg_no} {guild} {valid_from} {valid_until} {card_link} {reason} {site_url}</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Send from (email)</label><input type="email" name="email_from" value="<?php echo $v('email_from'); ?>"><small class="admin-help">Use an address on your own domain so mail isn't marked as spam.</small></div>
      <div class="field"><label>Sender name</label><input name="email_from_name" value="<?php echo $v('email_from_name'); ?>"></div>
      <div class="field"><label>Notify this address of new applications</label><input type="email" name="admin_notify_email" value="<?php echo $v('admin_notify_email'); ?>"></div>
      <div class="field" style="justify-content:flex-end">
        <label class="mem-check"><input type="checkbox" name="send_receipt_email" value="1" <?php echo $S['send_receipt_email'] === '1' ? 'checked' : ''; ?>> Email applicants a receipt when they apply</label>
        <label class="mem-check"><input type="checkbox" name="notify_admin" value="1" <?php echo $S['notify_admin'] === '1' ? 'checked' : ''; ?>> Email me when someone applies</label>
      </div>
    </div>
    <?php foreach (['received' => 'Application received', 'approved' => 'Approved (sends registration number &amp; card)', 'rejected' => 'Rejected'] as $k => $label): ?>
      <h3 class="mem-h3"><?php echo $label; ?></h3>
      <div class="field"><label>Subject</label><input name="email_<?php echo $k; ?>_subject" value="<?php echo $v("email_{$k}_subject"); ?>"></div>
      <div class="field"><label>Message</label><textarea name="email_<?php echo $k; ?>_body" style="min-height:170px"><?php echo $v("email_{$k}_body"); ?></textarea></div>
    <?php endforeach; ?>
  </div>

  <button class="admin-btn admin-btn-brand">Save settings</button>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
