<?php
require_once __DIR__ . "/includes/sacco-members.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
sac_install($conn);

$cfg0 = nufa_site_settings($conn);
$S = sac_settings($conn);
$open = ($cfg0['sacco_applications_open'] ?? '0') === '1';
$status = in_array($cfg0['sacco_status'] ?? '', ['proposed', 'in_development', 'operational'], true) ? $cfg0['sacco_status'] : 'proposed';
$products = $conn->query("SELECT name FROM sacco_products WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$incomeBands = sac_lines($S['sacapp_income_bands']); $payMethods = sac_lines($S['sacapp_pay_methods']);
$fee = trim($S['sacapp_reg_fee']); $sharePrice = trim($S['sacapp_share_price']); $minShares = max(0, (int)$S['sacapp_min_shares']);
$errors = []; $done = null;
if (!empty($_GET['done']) && !empty($_SESSION['sac_done'])) $done = $_SESSION['sac_done'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $open && !$done) {
    if ($g = form_guard($conn, 'sacco_apply', 'sacco_applications')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $name = $p('full_name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $dob = $p('dob', 10); $idn = $p('id_number', 60);
    $shares = (int)($_POST['shares_applied'] ?? 0);
    $need = ['full_name' => 'Full name', 'gender' => 'Gender', 'nationality' => 'Nationality', 'marital_status' => 'Marital status', 'id_type' => 'ID type', 'id_number' => 'ID number', 'district' => 'District', 'village' => 'Village / parish',
             'occupation' => 'Occupation', 'creative_role' => 'Area of film / creative practice', 'kin_name' => 'Next of kin name', 'kin_relationship' => 'Next of kin relationship', 'kin_phone' => 'Next of kin phone'];
    foreach ($need as $k => $l) if ($p($k) === '') $errors[] = "$l is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a valid phone number.";
    if (!$dob || !strtotime($dob) || strtotime($dob) > strtotime('-16 years') || strtotime($dob) < strtotime('-110 years')) $errors[] = "Enter a valid date of birth (applicants must be at least 16).";
    if ($shares < $minShares) $errors[] = "You must apply for at least $minShares share" . ($minShares === 1 ? '' : 's') . ".";
    if (empty($_POST['d_true']) || empty($_POST['d_bylaws']) || empty($_POST['d_privacy'])) $errors[] = "Please tick all three declarations.";
    if ($p('signature_name', 150) === '') $errors[] = "Type your full name as your signature.";
    if ($p('ben_name') !== '' && ($p('ben_share', 10) !== '' && (!is_numeric($p('ben_share', 10)) || $p('ben_share', 10) < 1 || $p('ben_share', 10) > 100))) $errors[] = "The beneficiary share must be between 1 and 100%.";
    if ($fee !== '' && $p('pay_ref') === '' && $S['sacapp_require_proof'] === '1') $errors[] = "Enter the payment reference / transaction ID for the registration fee.";
    if (!$errors) {
        $d = $conn->prepare("SELECT status FROM sacco_applications WHERE (email=? OR (id_number=? AND id_number<>'')) AND status IN ('pending','approved') LIMIT 1"); $d->bind_param("ss", $email, $idn); $d->execute();
        if ($row = $d->get_result()->fetch_assoc()) $errors[] = $row['status'] === 'approved' ? "This email or ID number already belongs to an approved SACCO member." : "An application with this email or ID number is already waiting for review.";
    }
    $files = ['photo' => '', 'id_copy' => '', 'pay_proof' => ''];
    if (!$errors) {
        $dir = sac_storage();
        $spec = ['photo' => ['Passport photo', MEM_IMG_TYPES, 3, $S['sacapp_require_photo'] === '1'], 'id_copy' => ['Copy of ID', MEM_PROOF_TYPES, 5, $S['sacapp_require_id'] === '1'], 'pay_proof' => ['Proof of payment', MEM_PROOF_TYPES, 5, $S['sacapp_require_proof'] === '1' && $fee !== '']];
        foreach ($spec as $k => [$label, $types, $mb, $req]) {
            if (empty($_FILES[$k]['name'])) { if ($req) $errors[] = "$label is required."; continue; }
            [$fn, $er] = mem_store_upload($_FILES[$k], $types, $mb * 1048576, 'sac-' . $k, $dir);
            if ($er) $errors[] = "$label: $er"; else $files[$k] = $fn;
        }
        if ($errors) foreach ($files as $fn) if ($fn) @unlink($dir . $fn);
    }
    if (!$errors) {
        $reg = sac_next_reg_no($conn); $tok = bin2hex(random_bytes(16)); $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
        $prods = implode(', ', array_map(fn($x) => mb_substr(form_clean($x, 100), 0, 100), array_slice((array)($_POST['products'] ?? []), 0, 12)));
        $payDate = $p('pay_date', 10) ?: null;
        $cols = ['reg_no' => $reg, 'token' => $tok, 'full_name' => $name, 'gender' => $p('gender', 20), 'dob' => $dob, 'nationality' => $p('nationality', 80), 'marital_status' => $p('marital_status', 30), 'id_type' => $p('id_type', 40), 'id_number' => $idn,
            'phone' => $phone, 'alt_phone' => $p('alt_phone', 40), 'email' => $email, 'district' => $p('district', 100), 'subcounty' => $p('subcounty', 100), 'village' => $p('village', 150), 'address' => $p('address', 255),
            'occupation' => $p('occupation', 150), 'employer' => $p('employer'), 'creative_role' => $p('creative_role', 150), 'years_in_industry' => $p('years_in_industry', 40), 'income_band' => $p('income_band', 60),
            'nufa_member' => in_array($_POST['nufa_member'] ?? '', ['Yes', 'No'], true) ? $_POST['nufa_member'] : '', 'nufa_reg_no' => $p('nufa_reg_no', 60), 'shares_applied' => $shares, 'initial_deposit' => $p('initial_deposit', 40),
            'monthly_savings' => $p('monthly_savings', 40), 'frequency' => $p('frequency', 30), 'products' => $prods, 'purpose' => form_clean($_POST['purpose'] ?? '', 1500),
            'kin_name' => $p('kin_name', 150), 'kin_relationship' => $p('kin_relationship', 60), 'kin_phone' => $p('kin_phone', 40), 'kin_address' => $p('kin_address', 255),
            'ben_name' => $p('ben_name', 150), 'ben_relationship' => $p('ben_relationship', 60), 'ben_share' => $p('ben_share', 10), 'referee_name' => $p('referee_name', 150), 'referee_phone' => $p('referee_phone', 40),
            'pay_method' => $p('pay_method', 60), 'pay_ref' => $p('pay_ref', 120), 'pay_date' => $payDate, 'pay_amount' => $p('pay_amount', 40),
            'photo' => $files['photo'], 'id_copy' => $files['id_copy'], 'pay_proof' => $files['pay_proof'], 'signature_name' => $p('signature_name', 150), 'ip' => $ip];
        $names = implode(',', array_map(fn($c) => "`$c`", array_keys($cols))); $ph = implode(',', array_fill(0, count($cols), '?'));
        $types = ''; foreach ($cols as $c => $v) $types .= $c === 'shares_applied' ? 'i' : 's';
        $st = $conn->prepare("INSERT INTO sacco_applications ($names) VALUES ($ph)"); $vals = array_values($cols); $st->bind_param($types, ...$vals);
        if ($st->execute()) {
            $app = $conn->query("SELECT * FROM sacco_applications WHERE id=" . (int)$conn->insert_id)->fetch_assoc();
            $v = sac_vars($app);
            mem_send($conn, null, 'sacco-received', $email, strtr($S['sacapp_email_received_subject'], $v), strtr($S['sacapp_email_received_body'], $v));
            $mS = mem_settings($conn); $to = filter_var($S['sacapp_notify_email'], FILTER_VALIDATE_EMAIL) ? $S['sacapp_notify_email'] : ($mS['admin_notify_email'] ?? '');
            if ($to) { $url = mem_site_url() . '/admin/sacco-applications.php?view=' . $app['id']; mem_send($conn, null, 'sacco-admin', $to, "New SACCO application: $name ($reg)", "A new NUFA SACCO membership application is waiting for review.\n\nName: $name\nRegistration number: $reg\nPhone: $phone\nShares: $shares\n\nReview it: $url", $url, $email); }
            $_SESSION['sac_done'] = ['reg' => $reg, 'name' => $name, 'email' => $email]; $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            header("Location: sacco-apply.php?done=1#top"); exit;
        }
        $errors[] = "We couldn't save your application just now. Please try again.";
        foreach ($files as $fn) if ($fn) @unlink(sac_storage() . $fn);
    }
}

$pageTitle = "Apply for NUFA SACCO membership | NUFA";
$pageDesc = "Apply to join the NUFA SACCO.";
$noindex = true; $activeNav = "sacco"; $canonicalPath = "sacco-apply.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA SACCO', 'Membership application', $open ? 'Apply to join the NUFA SACCO — a member-owned savings and credit society for filmmakers and creatives in Northern Uganda.' : 'Membership applications are not open yet.', cms_img_raw('sacco', 'hero_image'), [['NUFA SACCO', 'sacco.php'], ['Apply']]);
$old = fn($k, $d = '') => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES, 'UTF-8');
$file = fn($k, $label, $req, $hint) => '<div class="field full"><label for="f-' . $k . '">' . $label . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label><input id="f-' . $k . '" type="file" name="' . $k . '"' . ($req ? ' required' : '') . ' accept="image/jpeg,image/png,image/webp' . ($k === 'photo' ? '' : ',application/pdf') . '"><span class="hint">' . $hint . '</span></div>';
?>
<section class="section" style="min-height:50vh"><div class="container narrow">
<?php if ($done): ?>
  <div class="form-card" style="text-align:center"><div class="eyebrow" style="justify-content:center">Application received</div>
    <h2>Thank you, <?php echo ui_e($done['name']); ?></h2>
    <p class="lede">Your NUFA SACCO registration number is</p>
    <div class="reg-badge"><?php echo ui_e($done['reg']); ?></div>
    <p>Please keep it safe and quote it whenever you contact the SACCO. We have emailed a copy to <b><?php echo ui_e($done['email']); ?></b>. Your application now waits for review by the SACCO committee — <b>it is not membership until you receive an approval message</b>.</p>
    <div class="actions" style="justify-content:center"><a href="sacco.php" class="btn btn-gold">Back to the SACCO page</a></div></div>
<?php elseif (!$open): ?>
  <div class="placeholder-card"><span class="ph-icon"><?php echo nufa_icon('coins', 22); ?></span><h2>Applications are not open</h2><p>The NUFA SACCO is not accepting membership applications yet. You can register your interest on the SACCO page and we will contact you when the process opens.</p><div class="actions" style="justify-content:center;margin-top:16px"><a href="sacco.php#enquiry" class="btn btn-gold">Register your interest</a></div></div>
<?php else: ?>
  <p class="lede" style="margin-bottom:20px"><?php echo ui_e($S['sacapp_intro']); ?></p>
  <?php if ($status !== 'operational'): ?><div class="status-banner" role="note" style="margin-bottom:20px"><?php echo nufa_icon('shield', 24); ?><div>The SACCO's current status is <b><?php echo ui_e(['proposed' => 'Proposed', 'in_development' => 'In Development'][$status] ?? ''); ?></b>. Read the status and regulatory details on the <a href="sacco.php">SACCO page</a> before you apply.</div></div><?php endif; ?>
  <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $e): ?><li><?php echo ui_e($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <p class="muted" style="margin-bottom:14px">Fields marked <span style="color:var(--err)">*</span> are required.</p>
  <form method="post" enctype="multipart/form-data" class="apply-form"><?php echo form_open_fields(); ?>
    <fieldset class="apply-card"><legend><span>1</span> Personal details</legend><div class="form-grid">
      <?php echo f_input('full_name', 'Full name (as on your ID)', ['required' => true, 'autocomplete' => 'name']); ?>
      <?php echo f_select('gender', 'Gender', ['Female', 'Male', 'Other'], ['required' => true]); ?>
      <?php echo f_input('dob', 'Date of birth', ['type' => 'date', 'required' => true]); ?>
      <?php echo f_input('nationality', 'Nationality', ['required' => true, 'default' => 'Ugandan']); ?>
      <?php echo f_select('marital_status', 'Marital status', ['Single', 'Married', 'Divorced / separated', 'Widowed'], ['required' => true]); ?>
      <?php echo f_select('id_type', 'ID type', ['National ID', 'Passport', 'Driving permit', 'Refugee ID'], ['required' => true]); ?>
      <?php echo f_input('id_number', 'ID number', ['required' => true]); ?>
      <?php echo f_input('phone', 'Phone (WhatsApp if possible)', ['type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'placeholder' => '+256…']); ?>
      <?php echo f_input('alt_phone', 'Alternative phone', ['type' => 'tel']); ?>
      <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
    </div></fieldset>
    <fieldset class="apply-card"><legend><span>2</span> Where you live</legend><div class="form-grid">
      <?php echo f_input('district', 'District', ['required' => true]); ?><?php echo f_input('subcounty', 'Sub-county / division'); ?><?php echo f_input('village', 'Village / parish', ['required' => true]); ?><?php echo f_input('address', 'Physical address / landmark'); ?>
    </div></fieldset>
    <fieldset class="apply-card"><legend><span>3</span> Work &amp; NUFA</legend><div class="form-grid">
      <?php echo f_input('occupation', 'Occupation', ['required' => true]); ?><?php echo f_input('employer', 'Employer or business name'); ?>
      <?php echo f_input('creative_role', 'Area of film / creative practice', ['required' => true, 'placeholder' => 'e.g. Director, editor, actor']); ?><?php echo f_input('years_in_industry', 'Years in the industry'); ?>
      <?php echo $incomeBands ? f_select('income_band', 'Average monthly income', $incomeBands) : ''; ?>
      <?php echo f_select('nufa_member', 'Are you a NUFA member?', ['Yes', 'No']); ?><?php echo f_input('nufa_reg_no', 'NUFA registration number (if a member)'); ?>
    </div></fieldset>
    <fieldset class="apply-card"><legend><span>4</span> Your savings plan</legend>
      <?php if ($sharePrice !== '' || $fee !== ''): ?><div class="info-card" style="margin-bottom:14px"><?php if ($fee !== ''): ?><div><b>Registration fee:</b> <?php echo ui_e($fee); ?></div><?php endif; ?><?php if ($sharePrice !== ''): ?><div><b>Price of one share:</b> <?php echo ui_e($sharePrice); ?></div><?php endif; ?><?php if ($minShares): ?><div><b>Minimum shares:</b> <?php echo $minShares; ?></div><?php endif; ?></div><?php endif; ?>
      <div class="form-grid">
      <?php echo f_input('shares_applied', 'Number of shares you wish to buy', ['type' => 'number', 'required' => true, 'default' => max(1, $minShares)]); ?>
      <?php echo f_input('initial_deposit', 'Planned initial savings deposit (UGX)'); ?>
      <?php echo f_input('monthly_savings', 'Planned regular savings (UGX)'); ?>
      <?php echo f_select('frequency', 'How often will you save?', ['Weekly', 'Monthly', 'Quarterly', 'Irregular / when I earn']); ?></div>
      <?php if ($products): ?><div class="field"><label>Products you are interested in</label><div class="check-grid"><?php foreach ($products as $pr): ?><label class="check"><input type="checkbox" name="products[]" value="<?php echo ui_e($pr['name']); ?>" <?php echo in_array($pr['name'], (array)($_POST['products'] ?? []), true) ? 'checked' : ''; ?>> <?php echo ui_e($pr['name']); ?></label><?php endforeach; ?></div><span class="hint">Interest only — it is not a loan application.</span></div><?php endif; ?>
      <?php echo f_textarea('purpose', 'Why do you want to join the SACCO?', ['maxlength' => 1500]); ?>
    </fieldset>
    <fieldset class="apply-card"><legend><span>5</span> Next of kin</legend><div class="form-grid">
      <?php echo f_input('kin_name', 'Full name', ['required' => true]); ?><?php echo f_input('kin_relationship', 'Relationship to you', ['required' => true]); ?><?php echo f_input('kin_phone', 'Phone', ['type' => 'tel', 'required' => true]); ?><?php echo f_input('kin_address', 'Address'); ?>
    </div></fieldset>
    <fieldset class="apply-card"><legend><span>6</span> Beneficiary &amp; introducer <small>(optional)</small></legend><div class="form-grid">
      <?php echo f_input('ben_name', 'Beneficiary name'); ?><?php echo f_input('ben_relationship', 'Relationship'); ?><?php echo f_input('ben_share', 'Share of benefits (%)', ['type' => 'number']); ?>
      <?php echo f_input('referee_name', 'Introduced by (a NUFA / SACCO member)'); ?><?php echo f_input('referee_phone', 'Their phone', ['type' => 'tel']); ?></div></fieldset>
    <?php if ($fee !== ''): ?><fieldset class="apply-card"><legend><span>7</span> Registration fee</legend>
      <?php if (trim($S['sacapp_pay_instructions']) !== ''): ?><div class="info-card" style="margin-bottom:14px"><?php echo ui_paragraphs($S['sacapp_pay_instructions']); ?></div><?php endif; ?>
      <div class="form-grid"><?php echo $payMethods ? f_select('pay_method', 'Payment method', $payMethods) : ''; ?><?php echo f_input('pay_ref', 'Reference / transaction ID', ['required' => $S['sacapp_require_proof'] === '1']); ?><?php echo f_input('pay_date', 'Payment date', ['type' => 'date']); ?><?php echo f_input('pay_amount', 'Amount paid (UGX)'); ?></div></fieldset><?php endif; ?>
    <fieldset class="apply-card"><legend><span><?php echo $fee !== '' ? 8 : 7; ?></span> Documents</legend><div class="form-grid">
      <?php echo $file('photo', 'Passport-size photo', $S['sacapp_require_photo'] === '1', 'JPG, PNG or WebP, up to 3 MB'); ?>
      <?php echo $file('id_copy', 'Copy of your ID', $S['sacapp_require_id'] === '1', 'Photo or PDF, up to 5 MB'); ?>
      <?php echo $fee !== '' ? $file('pay_proof', 'Proof of payment of the registration fee', $S['sacapp_require_proof'] === '1', 'Photo or PDF, up to 5 MB') : ''; ?></div></fieldset>
    <fieldset class="apply-card"><legend><span><?php echo $fee !== '' ? 9 : 8; ?></span> Declaration</legend>
      <div class="info-card" style="margin-bottom:12px"><?php echo ui_list($S['sacapp_declaration']); ?></div>
      <?php echo f_check('d_true', 'I declare that the information I have given is true and complete.'); ?>
      <?php echo f_check('d_bylaws', 'I have read and agree to the NUFA SACCO bylaws and membership rules.'); ?>
      <?php echo f_check('d_privacy', 'I consent to NUFA using my details to process this application, as set out in the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?>
      <div class="form-grid" style="margin-top:10px"><?php echo f_input('signature_name', 'Type your full name as your signature', ['required' => true]); ?></div></fieldset>
    <button class="btn btn-gold btn-block" type="submit">Submit my application</button>
    <p class="muted" style="text-align:center;margin-top:10px;font-size:.85rem">You will get a registration number straight away. Your application is reviewed by the SACCO committee.</p>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
