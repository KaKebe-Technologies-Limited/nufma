<?php
$adminTitle = "Payment & giving setup";
$adminNav = "support";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/support.php";

$tab = in_array($_GET['tab'] ?? '', ['methods', 'settings', 'emails'], true) ? $_GET['tab'] : 'methods';
$msg = ''; $err = '';
$save = function ($k, $v) use ($conn) {
    $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $s->bind_param("ss", $k, $v); $s->execute();
};
$types = ['mobile_money' => 'Mobile money', 'bank' => 'Bank transfer', 'online' => 'Card / online payment link', 'cash' => 'Cash at the office', 'other' => 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'method_save' || $act === 'method_add') {
        $id = (int)($_POST['id'] ?? 0);
        $label = trim($_POST['label'] ?? ''); $type = isset($types[$_POST['method_type'] ?? '']) ? $_POST['method_type'] : 'other';
        $link = trim($_POST['link_url'] ?? '');
        if ($label === '') $err = "Give the payment method a name (e.g. “MTN Mobile Money”).";
        elseif ($link !== '' && !preg_match('#^https://#i', $link)) $err = "The online payment link must start with https://";
        else {
            $f = fn($k, $m = 255) => mb_substr(trim($_POST[$k] ?? ''), 0, $m);
            $vals = [$label, $type, $f('provider', 120), $f('account_name', 150), $f('account_number', 120), $f('extra'), $f('reference_hint'), $link, mb_substr(trim($_POST['instructions'] ?? ''), 0, 2000), (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
            if ($act === 'method_add') {
                if (!$vals[9]) $vals[9] = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM payment_methods")->fetch_row()[0];
                $s = $conn->prepare("INSERT INTO payment_methods (label, method_type, provider, account_name, account_number, extra, reference_hint, link_url, instructions, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $s->bind_param("sssssssssii", ...$vals); $s->execute(); $msg = "Payment method added.";
            } else {
                $s = $conn->prepare("UPDATE payment_methods SET label=?, method_type=?, provider=?, account_name=?, account_number=?, extra=?, reference_hint=?, link_url=?, instructions=?, sort_order=?, is_active=? WHERE id=?");
                $vals[] = $id; $s->bind_param("sssssssssiii", ...$vals); $s->execute(); $msg = "Payment method saved.";
            }
        }
    } elseif ($act === 'method_delete') {
        $conn->query("DELETE FROM payment_methods WHERE id=" . (int)$_POST['id']); $msg = "Payment method deleted.";
    } elseif ($act === 'settings') {
        $cur = sup_lines($_POST['sup_currencies'] ?? ''); $cur = array_values(array_unique(array_map(fn($c) => strtoupper(preg_replace('/[^A-Za-z]/', '', $c)), $cur))); $cur = array_filter($cur);
        if (!$cur) $err = "List at least one currency (e.g. UGX).";
        else {
            $def = strtoupper(trim($_POST['sup_default_currency'] ?? '')); if (!in_array($def, $cur, true)) $def = $cur[0];
            $save('sup_currencies', implode("\n", $cur)); $save('sup_default_currency', $def);
            $amts = array_values(array_filter(array_map(fn($a) => sup_amount($a), sup_lines($_POST['sup_suggested_amounts'] ?? ''))));
            $save('sup_suggested_amounts', implode("\n", array_map(fn($a) => (string)(float)$a, $amts)));
            $save('sup_min_amount', (string)(sup_amount($_POST['sup_min_amount'] ?? '') ?? 0));
            $save('sup_tiers', implode("\n", sup_lines($_POST['sup_tiers'] ?? '')));
            $save('sup_volunteer_areas', implode("\n", sup_lines($_POST['sup_volunteer_areas'] ?? '')));
            $save('sup_availability', implode("\n", sup_lines($_POST['sup_availability'] ?? '')));
            $save('sup_payment_intro', mb_substr(trim($_POST['sup_payment_intro'] ?? ''), 0, 1000));
            $save('sup_terms', mb_substr(trim($_POST['sup_terms'] ?? ''), 0, 600));
            $save('sup_require_proof', isset($_POST['sup_require_proof']) ? '1' : '0');
            foreach (['donate', 'sponsor', 'volunteer', 'partner'] as $k) $save("sup_enable_$k", isset($_POST["sup_enable_$k"]) ? '1' : '0');
            $n = trim($_POST['sup_notify_email'] ?? '');
            if ($n !== '' && !filter_var($n, FILTER_VALIDATE_EMAIL)) $err = "Enter a valid notification email (or leave blank to use the default inbox).";
            else { $save('sup_notify_email', $n); $msg = "Giving settings saved."; }
        }
    } elseif ($act === 'emails') {
        foreach (['received', 'approved', 'completed', 'rejected'] as $ev) { $save("sup_email_{$ev}_subject", mb_substr(trim($_POST["sub_$ev"] ?? ''), 0, 200)); $save("sup_email_{$ev}_body", mb_substr(trim($_POST["body_$ev"] ?? ''), 0, 4000)); }
        $msg = "Email wording saved.";
    } elseif ($act === 'emails_reset') {
        foreach (['received', 'approved', 'completed', 'rejected'] as $ev) { $save("sup_email_{$ev}_subject", ''); $save("sup_email_{$ev}_body", ''); }
        $msg = "Email wording restored to the defaults.";
    }
}
$S = sup_settings($conn, true);
$defaults = sup_defaults();
$methods = $conn->query("SELECT * FROM payment_methods ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
$ev = fn($k) => htmlspecialchars(trim($S[$k]) !== '' ? $S[$k] : $defaults[$k]);
?>
<div class="mem-tabs"><a href="support-requests.php">Requests</a>
  <a href="support-setup.php?tab=methods" class="<?php echo $tab === 'methods' ? 'active' : ''; ?>">Payment methods</a>
  <a href="support-setup.php?tab=settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">Giving settings</a>
  <a href="support-setup.php?tab=emails" class="<?php echo $tab === 'emails' ? 'active' : ''; ?>">Email wording</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<?php if ($tab === 'methods'): ?>
  <p class="admin-help" style="margin-bottom:14px">These are the ways supporters can pay a donation or sponsorship. Active methods are shown on the support form and in the thank-you page and approval emails, together with the supporter's reference number. Only enter accounts NUFA actually controls.</p>
  <?php foreach ($methods as $m): ?>
  <form method="post" class="admin-card admin-form pay-edit">
    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Name shown to supporters</label><input name="label" value="<?php echo htmlspecialchars($m['label']); ?>" required></div>
      <div class="field"><label>Type</label><select name="method_type"><?php foreach ($types as $k => $l) echo '<option value="' . $k . '"' . ($m['method_type'] === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
      <div class="field"><label>Provider / bank</label><input name="provider" value="<?php echo htmlspecialchars($m['provider']); ?>" placeholder="e.g. MTN, Airtel, Stanbic Bank"></div>
      <div class="field"><label>Account name</label><input name="account_name" value="<?php echo htmlspecialchars($m['account_name']); ?>"></div>
      <div class="field"><label>Account / mobile number</label><input name="account_number" value="<?php echo htmlspecialchars($m['account_number']); ?>"></div>
      <div class="field"><label>Extra details (branch, SWIFT, merchant code…)</label><input name="extra" value="<?php echo htmlspecialchars($m['extra']); ?>"></div>
      <div class="field"><label>Reference instruction</label><input name="reference_hint" value="<?php echo htmlspecialchars($m['reference_hint']); ?>" placeholder="e.g. enter your reference as the payment reason"></div>
      <div class="field"><label>Online payment link (https://…)</label><input type="url" name="link_url" value="<?php echo htmlspecialchars($m['link_url']); ?>"></div>
    </div>
    <div class="field"><label>Step-by-step instructions</label><textarea name="instructions" style="min-height:80px"><?php echo htmlspecialchars($m['instructions']); ?></textarea></div>
    <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
      <label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo $m['is_active'] ? 'checked' : ''; ?>> Active (visible to supporters)</label>
      <label style="font-size:.82rem;font-weight:700">Order <input type="number" name="sort_order" value="<?php echo (int)$m['sort_order']; ?>" style="width:70px;padding:6px 8px"></label>
      <button name="action" value="method_save" class="admin-btn admin-btn-brand admin-btn-sm">Save</button>
      <button name="action" value="method_delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this payment method?')">Delete</button>
    </div>
  </form>
  <?php endforeach; ?>
  <?php if (!$methods): ?><div class="notice-inline">No payment methods yet. Until you add one, supporters are told that NUFA will share payment details by email after their request is received.</div><?php endif; ?>

  <form method="post" class="admin-card admin-form">
    <div class="admin-card-head"><h2>Add a payment method</h2></div>
    <input type="hidden" name="action" value="method_add"><input type="hidden" name="is_active" value="1">
    <div class="admin-form-grid">
      <div class="field"><label>Name shown to supporters</label><input name="label" required placeholder="e.g. MTN Mobile Money"></div>
      <div class="field"><label>Type</label><select name="method_type"><?php foreach ($types as $k => $l) echo '<option value="' . $k . '">' . $l . '</option>'; ?></select></div>
      <div class="field"><label>Provider / bank</label><input name="provider"></div>
      <div class="field"><label>Account name</label><input name="account_name"></div>
      <div class="field"><label>Account / mobile number</label><input name="account_number"></div>
      <div class="field"><label>Extra details</label><input name="extra"></div>
      <div class="field"><label>Reference instruction</label><input name="reference_hint"></div>
      <div class="field"><label>Online payment link</label><input type="url" name="link_url" placeholder="https://"></div>
    </div>
    <div class="field"><label>Step-by-step instructions</label><textarea name="instructions" style="min-height:80px"></textarea></div>
    <button class="admin-btn admin-btn-brand">Add method</button>
  </form>

<?php elseif ($tab === 'settings'): ?>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="settings">
    <div class="admin-card">
      <div class="admin-card-head"><h2>Which options are open</h2><span class="admin-help">You can also switch an option off for a single programme in Programmes → Edit.</span></div>
      <?php foreach (sup_kinds() as $k => $kd): ?><label class="mem-check"><input type="checkbox" name="sup_enable_<?php echo $k; ?>" value="1" <?php echo $S["sup_enable_$k"] === '1' ? 'checked' : ''; ?>> <?php echo htmlspecialchars($kd['label']); ?> — <?php echo htmlspecialchars($kd['blurb']); ?></label><?php endforeach; ?>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Money</h2></div>
      <div class="admin-form-grid">
        <div class="field"><label>Currencies offered (one per line)</label><textarea name="sup_currencies" style="min-height:90px"><?php echo $ev('sup_currencies'); ?></textarea></div>
        <div class="field"><label>Default currency</label><input name="sup_default_currency" value="<?php echo htmlspecialchars($S['sup_default_currency']); ?>"></div>
        <div class="field"><label>Suggested amounts (one per line — shown as quick buttons)</label><textarea name="sup_suggested_amounts" style="min-height:90px"><?php echo htmlspecialchars($S['sup_suggested_amounts']); ?></textarea><span class="admin-help">Leave empty to show none.</span></div>
        <div class="field"><label>Minimum donation (0 = none)</label><input name="sup_min_amount" value="<?php echo htmlspecialchars($S['sup_min_amount']); ?>"></div>
      </div>
      <div class="field"><label>Sponsorship levels (one per line: Name | amount | what it includes)</label><textarea name="sup_tiers" style="min-height:100px" placeholder="Supporter | 500,000 | Recognition on the programme page"><?php echo htmlspecialchars($S['sup_tiers']); ?></textarea><span class="admin-help">Optional. No levels are published until you add them — sponsors can still enter their own amount.</span></div>
      <div class="field"><label>Payment introduction (shown above the payment methods)</label><textarea name="sup_payment_intro" style="min-height:80px"><?php echo $ev('sup_payment_intro'); ?></textarea></div>
      <label class="mem-check"><input type="checkbox" name="sup_require_proof" value="1" <?php echo $S['sup_require_proof'] === '1' ? 'checked' : ''; ?>> Require proof of payment when a supporter says they have already paid</label>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Volunteers</h2></div>
      <div class="admin-form-grid">
        <div class="field"><label>Areas people can help with (one per line)</label><textarea name="sup_volunteer_areas" style="min-height:130px"><?php echo $ev('sup_volunteer_areas'); ?></textarea></div>
        <div class="field"><label>Availability choices (one per line)</label><textarea name="sup_availability" style="min-height:130px"><?php echo $ev('sup_availability'); ?></textarea></div>
      </div>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Declaration &amp; notifications</h2></div>
      <div class="field"><label>Declaration the supporter ticks before submitting</label><textarea name="sup_terms" style="min-height:70px"><?php echo $ev('sup_terms'); ?></textarea><span class="admin-help">Have any refund/gift terms reviewed by a qualified professional before publishing them here.</span></div>
      <div class="field" style="max-width:380px"><label>Send new-request notifications to (blank = default inbox)</label><input type="email" name="sup_notify_email" value="<?php echo htmlspecialchars($S['sup_notify_email']); ?>"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Save giving settings</button>
  </form>

<?php else: $events = ['received' => 'When a request is received', 'approved' => 'When you approve a request', 'completed' => 'When payment/support is confirmed', 'rejected' => 'When you reject a request']; ?>
  <p class="admin-help" style="margin-bottom:14px">Placeholders you can use: <code>{name}</code> <code>{ref}</code> <code>{kind}</code> <code>{kind_noun}</code> <code>{programme}</code> <code>{amount}</code> <code>{amount_line}</code> <code>{payment_block}</code> (payment instructions) <code>{next_steps}</code> <code>{reason}</code> <code>{contact_email}</code> <code>{site_url}</code></p>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="emails">
    <?php foreach ($events as $k => $label): ?>
    <div class="admin-card">
      <div class="admin-card-head"><h2><?php echo $label; ?></h2></div>
      <div class="field"><label>Subject</label><input name="sub_<?php echo $k; ?>" value="<?php echo $ev("sup_email_{$k}_subject"); ?>"></div>
      <div class="field"><label>Message</label><textarea name="body_<?php echo $k; ?>" style="min-height:170px"><?php echo $ev("sup_email_{$k}_body"); ?></textarea></div>
    </div>
    <?php endforeach; ?>
    <button class="admin-btn admin-btn-brand">Save email wording</button>
  </form>
  <form method="post" style="margin-top:12px" onsubmit="return confirm('Restore all four emails to the default wording?')"><input type="hidden" name="action" value="emails_reset"><button class="admin-btn admin-btn-line admin-btn-sm">Restore default wording</button></form>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
