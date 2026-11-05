<?php
$adminTitle = "Email delivery";
$adminNav = "emaildelivery";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/membership.php";
require_once __DIR__ . "/../includes/mailer.php";
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$flash = ''; $flashType = 'ok'; $testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $act = $_POST['action'] ?? '';
        if ($act === 'save') {
            $save('smtp_enabled', isset($_POST['smtp_enabled']) ? '1' : '0');
            foreach (['smtp_host', 'smtp_user', 'smtp_from_name'] as $k) $save($k, trim($_POST[$k] ?? ''));
            $save('smtp_port', (string)max(1, (int)($_POST['smtp_port'] ?? 465)));
            $save('smtp_secure', in_array($_POST['smtp_secure'] ?? '', ['ssl', 'tls', 'none'], true) ? $_POST['smtp_secure'] : 'ssl');
            $save('smtp_from', trim($_POST['smtp_user'] ?? ''));
            if (($_POST['smtp_pass'] ?? '') !== '') $save('smtp_pass', $_POST['smtp_pass']);
            $flash = 'Email settings saved. Send a test email below to check them.';
        } elseif ($act === 'test') {
            $to = trim($_POST['to'] ?? '');
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $flash = 'Enter a valid email address to send the test to.'; $flashType = 'err'; }
            else {
                $hd = "From: NUFA <info@nufawards.com>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                $ok = nufa_mail($to, 'NUFA website test email', "This is a test email from the NUFA website sent at " . date('j M Y H:i') . ".\n\nIf you can read this, email delivery works.", $hd);
                $testResult = [$ok, nufa_mail_error()];
            }
        }
    }
}
$C = nufa_mail_cfg();
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $e($flash); ?></div><?php endif; ?>
<?php if ($testResult): ?><div class="admin-alert <?php echo $testResult[0] ? 'ok' : 'err'; ?>"><?php echo $testResult[0] ? 'The message was accepted for delivery. Check the inbox (and the spam folder) of the address you entered. ' . ($C['smtp_enabled'] === '1' ? '' : 'Note: this used PHP mail(), which many hosts do not deliver reliably — switch on SMTP below if it does not arrive.') : 'The test email FAILED. ' . $e($testResult[1]); ?></div><?php endif; ?>
<div class="admin-card"><div class="admin-card-head"><h2>Email delivery (SMTP)</h2><span class="admin-badge <?php echo $C['smtp_enabled'] === '1' ? '' : 'gray'; ?>"><?php echo $C['smtp_enabled'] === '1' ? 'SMTP on' : 'Using PHP mail()'; ?></span></div>
  <p class="admin-help">Emails sent from the website — invitation letters, membership approvals and renewals, newsletters, replies — are most reliable when sent through a real mailbox on your domain. Create a mailbox such as <b>info@nufawards.com</b> in Hostinger (hPanel → Emails), then enter its details here.</p>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="save">
    <label style="display:flex;gap:8px;align-items:center;margin:6px 0 12px"><input type="checkbox" name="smtp_enabled" value="1" <?php echo $C['smtp_enabled'] === '1' ? 'checked' : ''; ?>> Send all website email through this mailbox</label>
    <div class="admin-form-grid">
      <div class="field"><label>Mailbox (email address)</label><input type="email" name="smtp_user" value="<?php echo $e($C['smtp_user']); ?>" placeholder="info@nufawards.com"></div>
      <div class="field"><label>Mailbox password</label><input type="password" name="smtp_pass" autocomplete="new-password" placeholder="<?php echo $C['smtp_pass'] !== '' ? '•••••••• (saved — leave blank to keep)' : 'Password of that mailbox'; ?>"></div>
      <div class="field"><label>SMTP server</label><input name="smtp_host" value="<?php echo $e($C['smtp_host']); ?>"><span class="admin-help">Hostinger: smtp.hostinger.com</span></div>
      <div class="field"><label>Port</label><input type="number" name="smtp_port" value="<?php echo $e($C['smtp_port']); ?>"><span class="admin-help">Hostinger: 465 with SSL (or 587 with TLS)</span></div>
      <div class="field"><label>Security</label><select name="smtp_secure"><?php foreach (['ssl' => 'SSL (port 465)', 'tls' => 'TLS / STARTTLS (port 587)', 'none' => 'None'] as $k => $l) echo '<option value="' . $k . '"' . ($C['smtp_secure'] === $k ? ' selected' : '') . '>' . $e($l) . '</option>'; ?></select></div>
      <div class="field"><label>Sender name</label><input name="smtp_from_name" value="<?php echo $e($C['smtp_from_name']); ?>" placeholder="NUFA"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Save</button>
  </form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Send a test email</h2></div>
  <form method="post" class="admin-form" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="test">
    <div class="field" style="flex:1;min-width:260px;margin:0"><label>Send the test to</label><input type="email" name="to" required placeholder="you@example.com"></div>
    <button class="admin-btn admin-btn-brand">Send test</button></form>
  <p class="admin-help" style="margin-top:10px">If the test fails, the exact reason is shown above (wrong password, wrong server, blocked connection…). Replies to website emails go to the sender address set in Membership settings, or to the mailbox above.</p></div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
