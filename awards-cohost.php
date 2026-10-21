<?php
require_once __DIR__ . "/includes/award-events.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
aev_install($conn);

$S = aev_settings($conn);
$events = $conn->query("SELECT id, slug, title, event_date, date_confirmed, date_note FROM award_events WHERE is_active=1 AND cohost_open=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$roles = aev_roles(); $currencies = ['UGX', 'USD', 'EUR', 'GBP'];
$benefitOpts = ['Logo on the NUFA Awards website', 'Logo on stage / backdrop and event materials', 'Mention by the MC', 'Social media recognition', 'A speaking or presentation slot', 'VIP seats'];
$errors = []; $done = null;
if (!empty($_GET['done']) && !empty($_SESSION['aev_done'])) $done = $_SESSION['aev_done'];
$preSlug = preg_replace('/[^a-z0-9-]/', '', $_GET['event'] ?? '');
$sel = (array)($_POST['events'] ?? []);
if (!$_POST) foreach ($events as $ev) if ($ev['slug'] === $preSlug) $sel[] = $ev['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done) {
    if ($g = form_guard($conn, 'award_cohost', 'partner_applications')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $valid = array_column($events, 'id'); $chosen = array_values(array_intersect(array_map('intval', (array)($_POST['events'] ?? [])), array_map('intval', $valid)));
    $atype = in_array($_POST['applicant_type'] ?? '', ['Organisation', 'Individual'], true) ? $_POST['applicant_type'] : 'Organisation';
    $org = $p('org_name'); $contact = $p('contact_name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $site = $p('website', 255);
    $role = in_array($_POST['partnership_role'] ?? '', $roles, true) ? $_POST['partnership_role'] : ''; $contribution = form_clean($_POST['contribution'] ?? '', 2500); $value = $p('contribution_value', 40);
    $cur = in_array($_POST['currency'] ?? '', $currencies, true) ? $_POST['currency'] : 'UGX';
    if (!$chosen) $errors[] = "Choose at least one event you would like to co-host or sponsor.";
    if ($atype === 'Organisation' && $org === '') $errors[] = "Enter your organisation's name.";
    if ($contact === '') $errors[] = "Enter a contact person.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone number we can reach you on.";
    if ($site !== '' && !preg_match('#^https?://#i', $site)) $errors[] = "The website must start with https://";
    if ($role === '') $errors[] = "Choose how you would like to take part.";
    if ($contribution === '') $errors[] = "Describe your contribution or sponsorship.";
    if ($value !== '' && !preg_match('/^[\d.,\s]+$/', $value)) $errors[] = "The estimated value should be a number.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted.";
    if (empty($_POST['publish_ok'])) $errors[] = "Please confirm you agree to your name and logo being shown on the event page if your application is approved.";
    $logo = '';
    if (!$errors && !empty($_FILES['logo']['name'])) { [$fn, $er] = mem_store_upload($_FILES['logo'], MEM_IMG_TYPES, 3 * 1048576, 'ev-logo', aev_storage()); if ($er) $errors[] = "Logo: $er"; else $logo = $fn; }
    if (!$errors) {
        $tok = bin2hex(random_bytes(16)); $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $ids = implode(',', $chosen);
        $benefits = implode('; ', array_intersect($benefitOpts, (array)($_POST['benefits'] ?? []))); $about = form_clean($_POST['about'] ?? '', 1500); $msg = form_clean($_POST['message'] ?? '', 2000); $rt = $p('role_title', 120);
        $s = $conn->prepare("INSERT INTO award_event_apps (token, event_ids, applicant_type, org_name, contact_name, role_title, email, phone, website, about, logo, partnership_role, contribution, contribution_value, currency, benefits, message, ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("ssssssssssssssssss", $tok, $ids, $atype, $org, $contact, $rt, $email, $phone, $site, $about, $logo, $role, $contribution, $value, $cur, $benefits, $msg, $ip);
        if ($s->execute()) {
            $id = $conn->insert_id; $ref = 'AE-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); $conn->query("UPDATE award_event_apps SET ref_no='$ref' WHERE id=$id");
            $a = $conn->query("SELECT * FROM award_event_apps WHERE id=$id")->fetch_assoc(); $v = aev_vars($conn, $a);
            mem_send($conn, null, 'award-event-received', $email, strtr($S['aev_email_received_subject'], $v), strtr($S['aev_email_received_body'], $v));
            $mS = mem_settings($conn); $to = filter_var($S['aev_notify_email'], FILTER_VALIDATE_EMAIL) ? $S['aev_notify_email'] : ($mS['admin_notify_email'] ?? '');
            if ($to) { $url = mem_site_url() . '/admin/award-events.php?tab=apps&view=' . $id; mem_send($conn, null, 'award-event-admin', $to, "New awards event application: " . aev_display($a) . " ($ref)", "Someone wants to " . strtolower($role) . " NUFA Awards events: " . aev_names($conn, $ids) . "\n\nFrom: " . aev_display($a) . " ($contact)\nValue: " . ($value !== '' ? "$cur $value" : 'not stated') . "\n\nReview it: $url", $url, $email); }
            $_SESSION['aev_done'] = ['ref' => $ref, 'name' => aev_display($a), 'email' => $email, 'events' => aev_names($conn, $ids)]; $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            header("Location: awards-cohost.php?done=1#top"); exit;
        }
        $errors[] = "We couldn't save your application just now. Please try again."; if ($logo) @unlink(aev_storage() . $logo);
    }
}

$pageTitle = "Co-host or sponsor NUFA Awards events | NUFA";
$pageDesc = "Apply to co-host or sponsor the NUFA Awards launch, nomination night or grand finale.";
$activeNav = "awards"; $canonicalPath = "awards-cohost.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Awards', 'Co-host or sponsor an awards event', 'Join us for the launch, nomination night or the grand finale — as a co-host, headline sponsor, in-kind or media partner.', cms_img_raw('awards', 'hero_image'), [['NUFA Awards', 'awards.php'], ['Co-host or sponsor']]);
$ov = fn($k) => $_POST[$k] ?? '';
?>
<section class="section" style="min-height:50vh"><div class="container narrow">
<?php if ($done): ?>
  <div class="form-card" style="text-align:center"><div class="eyebrow" style="justify-content:center">Application received</div><h2>Thank you, <?php echo ui_e($done['name']); ?></h2>
    <p class="lede">Your reference is</p><div class="reg-badge"><?php echo ui_e($done['ref']); ?></div>
    <p>You applied for: <b><?php echo ui_e($done['events']); ?></b>. We have emailed a copy to <b><?php echo ui_e($done['email']); ?></b>. If approved, you will be shown as a co-host or sponsor on the event page.</p>
    <div class="actions" style="justify-content:center"><a href="awards.php#events" class="btn btn-gold">Back to the NUFA Awards events</a></div></div>
<?php elseif (!$events): ?>
  <div class="placeholder-card"><span class="ph-icon"><?php echo nufa_icon('trophy', 22); ?></span><h2>Applications are closed</h2><p>We are not taking co-host or sponsor applications for the awards events right now. You can still <a href="partners.php">get in touch about partnering</a>.</p></div>
<?php else: ?>
  <p class="lede" style="margin-bottom:18px"><?php echo ui_e($S['aev_intro']); ?></p>
  <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $er): ?><li><?php echo ui_e($er); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <p class="muted" style="margin-bottom:14px">Fields marked <span style="color:var(--err)">*</span> are required.</p>
  <form method="post" enctype="multipart/form-data" class="apply-form"><?php echo form_open_fields(); ?>
    <fieldset class="apply-card"><legend><span>1</span> Which event(s)?</legend>
      <div class="check-grid"><?php foreach ($events as $ev): ?><label class="check"><input type="checkbox" name="events[]" value="<?php echo (int)$ev['id']; ?>" <?php echo in_array((int)$ev['id'], array_map('intval', $sel), true) ? 'checked' : ''; ?>> <span><b><?php echo ui_e($ev['title']); ?></b><br><small class="muted"><?php echo $ev['date_confirmed'] && $ev['event_date'] ? ui_e(date('j M Y', strtotime($ev['event_date']))) : ui_e($ev['date_note'] ?: 'Date to be announced'); ?></small></span></label><?php endforeach; ?></div></fieldset>
    <fieldset class="apply-card"><legend><span>2</span> About you</legend>
      <div class="field"><label>Applying as</label><div class="check-grid"><?php foreach (['Organisation', 'Individual'] as $t): ?><label class="check"><input type="radio" name="applicant_type" value="<?php echo $t; ?>" <?php echo ($ov('applicant_type') ?: 'Organisation') === $t ? 'checked' : ''; ?>> <?php echo $t; ?></label><?php endforeach; ?></div></div>
      <div class="form-grid"><?php echo f_input('org_name', 'Organisation / company name (blank if you are an individual)'); ?><?php echo f_input('contact_name', 'Contact person', ['required' => true, 'autocomplete' => 'name']); ?><?php echo f_input('role_title', 'Your position'); ?><?php echo f_input('email', 'Email', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?><?php echo f_input('phone', 'Phone / WhatsApp', ['type' => 'tel', 'required' => true, 'autocomplete' => 'tel']); ?><?php echo f_input('website', 'Website', ['type' => 'url', 'placeholder' => 'https://']); ?></div>
      <?php echo f_textarea('about', 'A short description of you or your organisation (may appear on the event page)', ['maxlength' => 1500]); ?>
      <div class="field"><label for="f-logo">Logo (optional — JPG, PNG or WebP, up to 3 MB)</label><input id="f-logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div></fieldset>
    <fieldset class="apply-card"><legend><span>3</span> Your contribution</legend>
      <div class="form-grid"><?php echo f_select('partnership_role', 'How would you like to take part?', $roles, ['required' => true]); ?></div>
      <?php echo f_textarea('contribution', 'What will you contribute? (cash sponsorship, venue, equipment, food & drinks, media coverage, expertise…)', ['required' => true, 'maxlength' => 2500]); ?>
      <div class="form-grid"><?php echo f_input('contribution_value', 'Estimated value', ['hint' => 'Optional — numbers only']); ?><div class="field"><label for="f-currency">Currency</label><select id="f-currency" name="currency"><?php foreach ($currencies as $c) echo '<option' . (($ov('currency') ?: 'UGX') === $c ? ' selected' : '') . '>' . $c . '</option>'; ?></select></div></div>
      <div class="field"><label>Recognition you would like</label><div class="check-grid"><?php foreach ($benefitOpts as $b): ?><label class="check"><input type="checkbox" name="benefits[]" value="<?php echo ui_e($b); ?>" <?php echo in_array($b, (array)($_POST['benefits'] ?? []), true) ? 'checked' : ''; ?>> <?php echo ui_e($b); ?></label><?php endforeach; ?></div></div>
      <?php echo f_textarea('message', 'Anything else we should know?', ['maxlength' => 2000]); ?></fieldset>
    <fieldset class="apply-card"><legend><span>4</span> Consent</legend>
      <?php echo f_check('publish_ok', 'If my application is approved, NUFA may show my name, logo and website on the event page as a co-host or sponsor.'); ?>
      <?php echo f_check('consent', 'I consent to NUFA contacting me about this application and have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?></fieldset>
    <button class="btn btn-gold btn-block" type="submit">Submit my application</button>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
