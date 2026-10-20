<?php
require_once __DIR__ . "/includes/partner-apps.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
pa_install($conn);

$S = pa_settings($conn);
$types = pa_types();
$progs = array_column($conn->query("SELECT title FROM programmes WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC), 'title');
$events = [];
try {
    require_once __DIR__ . "/includes/schedule.php";
    foreach (sch_occurrences($conn, date('Y-m-d'), date('Y-m-d', strtotime('+365 day'))) as $o) if ($o['kind'] === 'programme') $events[] = $o['title'] . ' — ' . date('j M Y', strtotime($o['date']));
} catch (Throwable $e) { $events = []; }
$activities = array_values(array_unique(array_merge($progs, array_slice($events, 0, 25))));
$currencies = ['UGX', 'USD', 'EUR', 'GBP'];
$benefitOpts = ['Logo and details on the NUFA website', 'Mention at the event / activity', 'Logo on event materials', 'Social media recognition', 'A speaking or presentation slot', 'Report on the impact of the activity'];
$errors = []; $done = null;
if (!empty($_GET['done']) && !empty($_SESSION['pa_done'])) $done = $_SESSION['pa_done'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done) {
    if ($g = form_guard($conn, 'partner_apply', 'partner_applications')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $atype = in_array($_POST['applicant_type'] ?? '', ['Organisation', 'Individual'], true) ? $_POST['applicant_type'] : 'Organisation';
    $org = $p('org_name'); $contact = $p('contact_name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $site = $p('website', 255);
    $activity = $p('activity'); if ($activity === '__other') $activity = $p('activity_other');
    $ptype = $p('partnership_type', 120); $contribution = form_clean($_POST['contribution'] ?? '', 2500); $value = $p('contribution_value', 40);
    $cur = in_array($_POST['currency'] ?? '', $currencies, true) ? $_POST['currency'] : 'UGX';
    if ($atype === 'Organisation' && $org === '') $errors[] = "Enter your organisation's name.";
    if ($contact === '') $errors[] = "Enter a contact person.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone number we can reach you on.";
    if ($site !== '' && !preg_match('#^https?://#i', $site)) $errors[] = "The website must start with https://";
    if ($activity === '') $errors[] = "Tell us which activity or outreach you would like to join.";
    if (!in_array($ptype, $types, true)) $errors[] = "Choose how you would like to partner.";
    if ($contribution === '') $errors[] = "Describe your contribution or sponsorship.";
    if ($value !== '' && !preg_match('/^[\d.,\s]+$/', $value)) $errors[] = "The estimated value should be a number.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted.";
    if (empty($_POST['publish_ok'])) $errors[] = "Please confirm you agree to your name, logo and description being published if your application is approved and your commitment is met.";
    $logo = '';
    if (!$errors && !empty($_FILES['logo']['name'])) { [$fn, $er] = mem_store_upload($_FILES['logo'], MEM_IMG_TYPES, 3 * 1048576, 'pa-logo', pa_storage()); if ($er) $errors[] = "Logo: $er"; else $logo = $fn; }
    if (!$errors) {
        $tok = bin2hex(random_bytes(16)); $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
        $benefits = implode('; ', array_intersect($benefitOpts, (array)($_POST['benefits'] ?? [])));
        $about = form_clean($_POST['about'] ?? '', 1500); $msg = form_clean($_POST['message'] ?? '', 2000); $loc = $p('location', 150); $role = $p('role_title', 120); $timing = $p('timing');
        $auto = $S['paapp_auto_publish'] === '1' ? 1 : 0;
        $s = $conn->prepare("INSERT INTO partner_applications (token, applicant_type, org_name, contact_name, role_title, email, phone, website, location, about, logo, activity, partnership_type, contribution, contribution_value, currency, timing, benefits, message, auto_publish, ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssssssssssssssssssis", $tok, $atype, $org, $contact, $role, $email, $phone, $site, $loc, $about, $logo, $activity, $ptype, $contribution, $value, $cur, $timing, $benefits, $msg, $auto, $ip);
        if ($s->execute()) {
            $id = $conn->insert_id; $ref = 'PA-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
            $conn->query("UPDATE partner_applications SET ref_no='$ref' WHERE id=$id");
            $a = $conn->query("SELECT * FROM partner_applications WHERE id=$id")->fetch_assoc(); $v = pa_vars($a);
            mem_send($conn, null, 'partner-received', $email, strtr($S['paapp_email_received_subject'], $v), strtr($S['paapp_email_received_body'], $v));
            $mS = mem_settings($conn); $to = filter_var($S['paapp_notify_email'], FILTER_VALIDATE_EMAIL) ? $S['paapp_notify_email'] : ($mS['admin_notify_email'] ?? '');
            if ($to) { $url = mem_site_url() . '/admin/partner-applications.php?view=' . $id; mem_send($conn, null, 'partner-admin', $to, "New partnership application: " . pa_display($a) . " ($ref)", "A new partnership application is waiting for review.\n\nFrom: " . pa_display($a) . " ($contact)\nActivity: $activity\nType: $ptype\nValue: " . ($value !== '' ? "$cur $value" : 'not stated') . "\n\nReview it: $url", $url, $email); }
            $_SESSION['pa_done'] = ['ref' => $ref, 'name' => pa_display($a), 'email' => $email]; $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            header("Location: partner-apply.php?done=1#top"); exit;
        }
        $errors[] = "We couldn't save your application just now. Please try again.";
        if ($logo) @unlink(pa_storage() . $logo);
    }
}

$pageTitle = "Co-host or partner with NUFA | NUFA";
$pageDesc = "Apply to co-host an activity, join an outreach or support NUFA with a contribution or sponsorship. Approved partners are listed on the NUFA website.";
$activeNav = "partner"; $canonicalPath = "partner-apply.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Partner With NUFA', 'Co-host or partner on an activity', 'Tell us who you are, which activity or outreach you want to join, and what you can contribute.', cms_img_raw('partners', 'hero_image'), [['Partner With Us', 'partners.php'], ['Apply to partner']]);
$oldv = fn($k) => $_POST[$k] ?? '';
?>
<section class="section" style="min-height:50vh"><div class="container narrow">
<?php if ($done): ?>
  <div class="form-card" style="text-align:center"><div class="eyebrow" style="justify-content:center">Application received</div><h2>Thank you, <?php echo ui_e($done['name']); ?></h2>
    <p class="lede">Your application reference is</p><div class="reg-badge"><?php echo ui_e($done['ref']); ?></div>
    <p>We have emailed a copy to <b><?php echo ui_e($done['email']); ?></b>. The NUFA team will review your application. Once it is approved and your commitment has been met, your logo and details are added to the <a href="partners.php">Partners</a> section of this website.</p>
    <div class="actions" style="justify-content:center"><a href="partners.php" class="btn btn-gold">Back to Partners</a></div></div>
<?php else: ?>
  <p class="lede" style="margin-bottom:18px"><?php echo ui_e($S['paapp_intro']); ?></p>
  <ol class="steps" style="margin-bottom:24px"><li><b>Apply</b>Share your details and what you would like to contribute.</li><li><b>We review</b>The NUFA team replies and agrees the commitment with you.</li><li><b>Deliver</b>Your support is confirmed once the commitment is met.</li><li><b>Be listed</b>Your logo and details appear under Partners automatically.</li></ol>
  <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $e): ?><li><?php echo ui_e($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <p class="muted" style="margin-bottom:14px">Fields marked <span style="color:var(--err)">*</span> are required.</p>
  <form method="post" enctype="multipart/form-data" class="apply-form"><?php echo form_open_fields(); ?>
    <fieldset class="apply-card"><legend><span>1</span> About you</legend>
      <div class="field"><label>Applying as</label><div class="check-grid"><?php foreach (['Organisation', 'Individual'] as $i => $t): ?><label class="check"><input type="radio" name="applicant_type" value="<?php echo $t; ?>" <?php echo ($oldv('applicant_type') ?: 'Organisation') === $t ? 'checked' : ''; ?>> <?php echo $t; ?></label><?php endforeach; ?></div></div>
      <div class="form-grid">
        <?php echo f_input('org_name', 'Organisation / company name (leave blank if you are an individual)'); ?>
        <?php echo f_input('contact_name', 'Contact person', ['required' => true, 'autocomplete' => 'name']); ?>
        <?php echo f_input('role_title', 'Your position'); ?>
        <?php echo f_input('email', 'Email', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
        <?php echo f_input('phone', 'Phone / WhatsApp', ['type' => 'tel', 'required' => true, 'autocomplete' => 'tel']); ?>
        <?php echo f_input('website', 'Website', ['type' => 'url', 'placeholder' => 'https://']); ?>
        <?php echo f_input('location', 'Based in (district / country)'); ?></div>
      <?php echo f_textarea('about', 'About you / your organisation (this short description can appear on the website)', ['maxlength' => 1500]); ?>
      <div class="field"><label for="f-logo">Logo or photo (optional — JPG, PNG or WebP, up to 3 MB)</label><input id="f-logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div></fieldset>
    <fieldset class="apply-card"><legend><span>2</span> The activity</legend><div class="form-grid">
      <div class="field full"><label for="f-activity">Which activity, programme or outreach do you want to co-host or support? <span class="req">*</span></label><select id="f-activity" name="activity" required onchange="document.getElementById('other-act').hidden=this.value!=='__other'"><option value="">Select…</option><?php foreach ($activities as $a): ?><option<?php echo $oldv('activity') === $a ? ' selected' : ''; ?>><?php echo ui_e($a); ?></option><?php endforeach; ?><option value="__other"<?php echo $oldv('activity') === '__other' ? ' selected' : ''; ?>>Something else / my own idea</option></select></div>
      <div class="field full" id="other-act" <?php echo $oldv('activity') === '__other' ? '' : 'hidden'; ?>><label for="f-activity_other">Describe the activity or outreach</label><input id="f-activity_other" name="activity_other" value="<?php echo ui_e($oldv('activity_other')); ?>"></div>
      <?php echo f_select('partnership_type', 'How would you like to partner?', $types, ['required' => true, 'full' => true]); ?></div></fieldset>
    <fieldset class="apply-card"><legend><span>3</span> Your contribution</legend>
      <?php echo f_textarea('contribution', 'What will you contribute? (cash, venue, equipment, transport, expertise, media coverage, volunteers…)', ['required' => true, 'maxlength' => 2500]); ?>
      <div class="form-grid"><?php echo f_input('contribution_value', 'Estimated value of your contribution', ['hint' => 'Optional — numbers only']); ?>
        <div class="field"><label for="f-currency">Currency</label><select id="f-currency" name="currency"><?php foreach ($currencies as $c) echo '<option' . (($oldv('currency') ?: 'UGX') === $c ? ' selected' : '') . '>' . $c . '</option>'; ?></select></div>
        <?php echo f_input('timing', 'When can you deliver it?', ['placeholder' => 'e.g. Before 1 March, or on the event day']); ?></div>
      <div class="field"><label>What recognition would you like?</label><div class="check-grid"><?php foreach ($benefitOpts as $b): ?><label class="check"><input type="checkbox" name="benefits[]" value="<?php echo ui_e($b); ?>" <?php echo in_array($b, (array)($_POST['benefits'] ?? []), true) ? 'checked' : ''; ?>> <?php echo ui_e($b); ?></label><?php endforeach; ?></div></div>
      <?php echo f_textarea('message', 'Anything else we should know?', ['maxlength' => 2000]); ?></fieldset>
    <fieldset class="apply-card"><legend><span>4</span> Consent</legend>
      <?php echo f_check('publish_ok', 'If my application is approved and my commitment is met, I agree that my name, logo and the description above may be published in the Partners section of the NUFA website.'); ?>
      <?php echo f_check('consent', 'I consent to NUFA contacting me about this application and have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?></fieldset>
    <button class="btn btn-gold btn-block" type="submit">Submit my partnership application</button>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
