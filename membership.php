<?php
require __DIR__ . "/includes/membership.php";   // also loads db.php + creates tables on first run
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$S = mem_settings($conn);
require_once __DIR__ . "/includes/payments.php";
$payGroups = pay_groups($conn);
$guilds = [];
$gr = $conn->query("SELECT id, name, description FROM guilds WHERE is_active=1 ORDER BY sort_order ASC, name ASC");
while ($g = $gr->fetch_assoc()) $guilds[] = $g;

$old = [];
$errors = [];
$type = ($_GET['type'] ?? $_POST['type'] ?? 'new') === 'renew' ? 'renew' : 'new';
$F = mem_fees($S); $curL = $S['membership_currency'];

/* ---------- renewal of an existing membership (subscription only) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'renew') {
    $type = 'renew'; $old = $_POST; $f = fn($k) => trim($_POST[$k] ?? '');
    if (!mem_csrf_ok()) $errors[] = "Your session expired. Please try again.";
    if ($f('website') !== '') $errors[] = "Submission rejected.";
    $rm = mem_renew_find($conn, $f('email'), $f('who'));
    if (!$rm && !$errors) $errors[] = "We could not find an approved NUFA member with that email and membership ID (or phone number). Check the details, or register as a new member instead.";
    $payChoice = $_POST['payment_choice'] ?? '';
    if ($payGroups && !isset($payGroups[$payChoice])) $errors[] = "Choose how you will pay: Bank or Mobile Money.";
    if (pay_proof_required($conn) && $f('payment_ref') === '') $errors[] = "Enter the payment reference / transaction ID.";
    if (empty($_POST['agree'])) $errors[] = "Please confirm the declaration.";
    if (!$errors && $conn->query("SELECT 1 FROM member_renewals WHERE member_id=" . (int)$rm['id'] . " AND status='pending'")->num_rows) $errors[] = "A renewal for this membership is already awaiting review.";
    $proof = '';
    if (!$errors && (!empty($_FILES['payment_proof']['name']) || pay_proof_required($conn))) {
        [$proof, $e2] = mem_store_upload($_FILES['payment_proof'] ?? [], MEM_PROOF_TYPES, 5 * 1048576, 'renewproof');
        if ($e2) $errors[] = "Proof of payment: " . $e2;
    }
    if (!$errors) {
        $ref = mem_renew_ref(); $amt = mem_money($F['renew'], $curL); $payDate = $f('payment_date') ?: null; $pm = $payChoice !== '' ? pay_label($payChoice) : ''; $pr = $f('payment_ref');
        $st = $conn->prepare("INSERT INTO member_renewals (ref, member_id, name, email, phone, amount, payment_method, payment_ref, payment_date, payment_proof) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $mid = (int)$rm['id']; $st->bind_param("sissssssss", $ref, $mid, $rm['full_name'], $rm['email'], $rm['phone'], $amt, $pm, $pr, $payDate, $proof);
        if ($st->execute()) {
            $until = $rm['valid_until'] ? date('j F Y', strtotime($rm['valid_until'])) : 'not set';
            mem_send($conn, $mid, 'renew-received', $rm['email'], "We received your NUFA membership renewal ($ref)",
                "Hello {$rm['full_name']},\n\nThank you for renewing your NUFA membership. We have your renewal request.\n\nReference: $ref\nMembership ID: {$rm['reg_no']}\nSubscription: $amt\nCurrent validity ends: $until\n\nOnce the NUFA team confirms your payment, your membership will be extended and we will email you.\n\n" . pay_text($conn, $payChoice, ['amount' => $amt, 'ref' => $rm['reg_no']]) . "\n\n— NUFA Membership Team");
            if ($S['notify_admin'] === '1' && $S['admin_notify_email']) { $au = mem_site_url() . '/admin/member-renewals.php'; mem_send($conn, $mid, 'admin', $S['admin_notify_email'], "Membership renewal: {$rm['full_name']}", "A member has asked to renew.\n\nName: {$rm['full_name']}\nMembership ID: {$rm['reg_no']}\nAmount: $amt\nPayment ref: $pr\n\nReview it: $au", $au, $rm['email']); }
            $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            $_SESSION['mem_pay'] = ['choice' => $payChoice, 'name' => $rm['reg_no'], 'amount' => $amt];
            header("Location: membership.php?type=renew&done=1&ref=$ref#top"); exit;
        }
        $errors[] = "We couldn't save your renewal. Please try again.";
        if ($proof) @unlink(MEM_STORAGE_DIR . $proof);
    } elseif ($proof) @unlink(MEM_STORAGE_DIR . $proof);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') !== 'renew') {
    $old = $_POST;
    $f = fn($k) => trim($_POST[$k] ?? '');

    if (!mem_csrf_ok()) $errors[] = "Your session expired. Please try again.";
    if ($S['membership_open'] !== '1') $errors[] = "Membership applications are currently closed.";
    if ($f('website') !== '') $errors[] = "Submission rejected.";   // honeypot

    $name = $f('full_name'); $email = strtolower($f('email'));
    if ($name === '') $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if ($f('phone') === '') $errors[] = "Phone number is required.";
    if (!in_array($f('gender'), ['Female', 'Male', 'Other'], true)) $errors[] = "Select your gender.";
    $dob = $f('dob');
    if (!$dob || !strtotime($dob) || strtotime($dob) > strtotime('-10 years')) $errors[] = "Enter a valid date of birth.";
    if ($f('district') === '') $errors[] = "District / town is required.";
    $subregion = $f('subregion');
    if (!in_array($subregion, mem_subregion_options(), true)) $errors[] = "Choose your sub-region.";
    $guildId = (int)($_POST['guild_id'] ?? 0);
    $guildOk = false; foreach ($guilds as $g) if ((int)$g['id'] === $guildId) $guildOk = true;
    if (!$guildOk) $errors[] = "Choose the guild you belong to.";
    $payChoice = $_POST['payment_choice'] ?? '';
    if ($payGroups && !isset($payGroups[$payChoice])) $errors[] = "Choose how you will pay: Bank or Mobile Money.";
    if (pay_proof_required($conn) && $f('payment_ref') === '') $errors[] = "Enter the payment reference / transaction ID.";
    if (empty($_POST['agree'])) $errors[] = "Please accept the declaration.";

    if (!$errors) {
        $dup = $conn->prepare("SELECT status FROM members WHERE email=? AND status IN ('pending','approved') LIMIT 1");
        $dup->bind_param("s", $email); $dup->execute();
        if ($row = $dup->get_result()->fetch_assoc()) {
            $errors[] = $row['status'] === 'approved'
                ? "This email already belongs to an approved NUFA member."
                : "An application with this email is already awaiting review.";
        }
    }

    $photo = $proof = '';
    if (!$errors) {
        [$photo, $e1] = mem_store_upload($_FILES['photo'] ?? [], MEM_IMG_TYPES, 3 * 1048576, 'photo');
        if ($e1) $errors[] = "Passport photo: " . $e1;
        if (!empty($_FILES['payment_proof']['name']) || pay_proof_required($conn)) {
            [$proof, $e2] = mem_store_upload($_FILES['payment_proof'] ?? [], MEM_PROOF_TYPES, 5 * 1048576, 'proof');
            if ($e2) $errors[] = "Proof of payment: " . $e2;
        }
        if ($errors) {
            foreach ([$photo, $proof] as $fn) if ($fn) @unlink(MEM_STORAGE_DIR . $fn);
        }
    }

    if (!$errors) {
        $token = bin2hex(random_bytes(16));
        $payDate = $f('payment_date') ?: null;
        $stmt = $conn->prepare("INSERT INTO members
            (card_token, full_name, gender, dob, phone, email, id_number, district, address, occupation, experience, portfolio_url, bio, guild_id,
             photo, payment_method, payment_ref, payment_date, amount_paid, payment_proof)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $vals = [$token, $name, $f('gender'), $dob, $f('phone'), $email, $f('id_number'), $f('district'), $f('address'), $f('occupation'),
                 $f('experience'), $f('portfolio_url'), $f('bio'), $guildId, $photo, ($payChoice !== '' ? pay_label($payChoice) : ''), $f('payment_ref'), $payDate,
                 $f('amount_paid'), $proof];
        $stmt->bind_param("sssssssssssssissssss", ...$vals);
        if ($stmt->execute()) {
            $mid = $conn->insert_id;
            $inDir = !empty($_POST['in_directory']) ? 1 : 0;
            $su = $conn->prepare("UPDATE members SET subregion=?, in_directory=? WHERE id=?"); $su->bind_param("sii", $subregion, $inDir, $mid); $su->execute();
            $member = $conn->query("SELECT * FROM members WHERE id=$mid")->fetch_assoc();
            $vars = mem_member_vars($conn, $member);
            if ($S['send_receipt_email'] === '1') {
                $payCtx = ['amount' => $S['membership_fee'] !== '' ? $S['membership_currency'] . ' ' . $S['membership_fee'] : '', 'ref' => $name];
                mem_send($conn, $mid, 'received', $email, mem_fill($S['email_received_subject'], $vars), mem_fill($S['email_received_body'], $vars) . "\n\n" . pay_text($conn, $payChoice, $payCtx));
            }
            if ($S['notify_admin'] === '1' && $S['admin_notify_email']) {
                $adminUrl = mem_site_url() . '/admin/member-view.php?id=' . $mid;
                mem_send($conn, $mid, 'admin', $S['admin_notify_email'], "New membership application: $name",
                    "A new membership application is waiting for review.\n\nName: $name\nGuild: {$vars['guild']}\nPhone: " . $f('phone') . "\nPayment ref: " . $f('payment_ref') . "\n\nReview it: $adminUrl",
                    $adminUrl, $email);
            }
            $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            $_SESSION['mem_pay'] = ['choice' => $payChoice, 'name' => $name, 'amount' => $S['membership_fee'] !== '' ? $S['membership_currency'] . ' ' . $S['membership_fee'] : ''];
            require_once __DIR__ . "/includes/track.php";
            header("Location: membership.php?done=1&ref=" . track_member_ref($token) . "#top");
            exit;
        }
        $errors[] = "We couldn't save your application. Please try again.";
        foreach ([$photo, $proof] as $fn) if ($fn) @unlink(MEM_STORAGE_DIR . $fn);
    }
}

require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$pageTitle = cms_value('seo', 'membership_title');
$pageDesc  = cms_value('seo', 'membership_desc');
$activeNav = "membership";
$canonicalPath = "membership.php";
include __DIR__ . "/includes/header.php";
$val = fn($k, $d = '') => htmlspecialchars($old[$k] ?? $d);
$cur = htmlspecialchars($S['membership_currency']);
echo ui_page_hero('Membership', cms_value('membership', 'hero_title'), cms_value('membership', 'hero_lede'), 'production/karamoja-shoot-2.webp', [['About NUFA', 'about.php#membership'], ['Membership']]);
?>
<section class="section">
  <div class="container member-grid">
    <aside class="member-side" data-reveal>
      <h3>How it works</h3>
      <?php if ($type === 'renew'): ?><ol class="member-steps">
        <li><b>Confirm who you are</b><span>Your email and membership ID (or phone number).</span></li>
        <li><b>Pay the annual subscription</b><span>Choose Bank or Mobile Money — the payment details appear straight away.</span></li>
        <li><b>NUFA confirms your payment</b><span>You get an email once it is confirmed.</span></li>
        <li><b>Your membership is extended</b><span>Your card shows the new validity period.</span></li>
      </ol>
      <?php else: ?><ol class="member-steps">
        <li><b>Complete the application</b><span>Your details, area of creative practice and a passport photo.</span></li>
        <li><b>Pay the membership fee</b><span>Choose Bank or Mobile Money — the payment details appear straight away.</span></li>
        <li><b>Review &amp; confirmation</b><span>NUFA reviews your application and emails you the outcome and your registration number.</span></li>
        <li><b>Receive your card</b><span>Confirmed members get a membership card showing the validity period.</span></li>
      </ol>
      <?php endif; ?>
      <?php if ($type === 'renew'): ?><div class="member-fee"><span>Renewal fee</span><b><?php echo htmlspecialchars(mem_money($F['renew'], $curL)); ?></b></div>
      <?php else: ?><div class="member-fee"><span>New member fee</span><b><?php echo htmlspecialchars(mem_money($F['new'], $curL)); ?></b></div><p style="font-size:.8rem;color:var(--muted-on-dark);margin:8px 0 0"><?php echo htmlspecialchars(mem_money($F['joining'], $curL)); ?> joining fee + <?php echo htmlspecialchars(mem_money($F['subscription'], $curL)); ?> annual subscription.</p><?php endif; ?>
      <div class="member-pay"><h4>How to pay</h4><p><?php echo $payGroups ? 'Choose <b>Bank</b> or <b>Mobile Money</b> in step 3 of the form. The account details appear immediately, and again once you submit.' : htmlspecialchars('NUFA will show you the payment details as soon as you submit your application.'); ?></p></div>
      <p style="font-size:.82rem;color:var(--muted-on-dark);margin-top:20px"><?php echo cms_t('about', 'membership_criteria'); ?></p>
    </aside>

    <div class="form-card" data-reveal>
    <?php if (!isset($_GET['done'])): ?>
      <style>.mtype{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:0 0 22px}.mtype a{display:block;border:2px solid #E3E3E3;border-radius:14px;padding:14px 16px;color:#111;background:#fff;transition:border-color .2s,box-shadow .2s}.mtype a b{display:block;font-size:1rem}.mtype a span{display:block;font-size:.82rem;color:#666;margin-top:3px}.mtype a em{display:inline-block;font-style:normal;font-weight:800;font-size:.8rem;color:var(--nufa-primary,#D50000);margin-top:6px}.mtype a.on{border-color:var(--nufa-primary,#D50000);box-shadow:0 6px 20px rgba(213,0,0,.12)}.mtype a:hover{border-color:var(--nufa-primary,#D50000)}@media(max-width:560px){.mtype{grid-template-columns:1fr}}</style>
      <div class="mtype" role="tablist" aria-label="Registration type">
        <a href="membership.php?type=new" class="<?php echo $type === 'new' ? 'on' : ''; ?>" role="tab" aria-selected="<?php echo $type === 'new' ? 'true' : 'false'; ?>"><b>New member</b><span>First time joining NUFA</span><em><?php echo htmlspecialchars(mem_money($F['new'], $curL)); ?> · <?php echo htmlspecialchars(number_format($F['joining'])); ?> joining + <?php echo htmlspecialchars(number_format($F['subscription'])); ?> subscription</em></a>
        <a href="membership.php?type=renew" class="<?php echo $type === 'renew' ? 'on' : ''; ?>" role="tab" aria-selected="<?php echo $type === 'renew' ? 'true' : 'false'; ?>"><b>Renew my membership</b><span>Already a NUFA member</span><em><?php echo htmlspecialchars(mem_money($F['renew'], $curL)); ?> · annual subscription</em></a>
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['done']) && $type === 'renew'): ?>
      <div class="member-done">
        <div class="member-done-ico" aria-hidden="true">✓</div>
        <h2 style="font-size:1.6rem">Renewal request received</h2>
        <p>Thank you. We have emailed you a confirmation. Once the NUFA team confirms your subscription payment, your membership will be extended and we will email you.</p>
        <?php if (preg_match('/^REN-[A-F0-9]{8}$/', $_GET['ref'] ?? '')): ?><p style="margin-top:14px">Your renewal reference: <span class="reg-badge" style="display:inline-block;font-size:1.1rem"><?php echo htmlspecialchars($_GET['ref']); ?></span></p><p>You can <a href="track.php?ref=<?php echo htmlspecialchars($_GET['ref']); ?>" style="text-decoration:underline">track your renewal</a> at any time.</p><?php endif; ?>
        <?php $__mp = $_SESSION['mem_pay'] ?? []; echo pay_block($conn, $__mp['choice'] ?? '', ['amount' => $__mp['amount'] ?? '', 'ref' => $__mp['name'] ?? ''], false, 'Now pay your annual subscription'); ?>
        <p style="margin-top:20px"><a href="index.php" class="btn btn-gold">Back to home</a></p>
      </div>
    <?php elseif (isset($_GET['done'])): ?>
      <div class="member-done">
        <div class="member-done-ico" aria-hidden="true">✓</div>
        <h2 style="font-size:1.6rem">Application received</h2>
        <p>Thank you for applying to join NUFA. We've emailed you a confirmation. Your application is now subject to NUFA's membership criteria and confirmation process — submitting it does not automatically make you a member. You'll receive another email once it has been reviewed.</p>
        <?php if (preg_match('/^MEM-[A-F0-9]{8}$/', $_GET['ref'] ?? '')): ?><p style="margin-top:14px">Your application reference: <span class="reg-badge" style="display:inline-block;font-size:1.1rem"><?php echo htmlspecialchars($_GET['ref']); ?></span></p><p>Keep it safe — you can <a href="track.php?ref=<?php echo htmlspecialchars($_GET['ref']); ?>" style="text-decoration:underline">track your application</a> at any time.</p><?php else: ?><p style="margin-top:14px">You can <a href="track.php" style="text-decoration:underline">track your application</a> at any time with your email and phone number.</p><?php endif; ?>
        <?php $__mp = $_SESSION['mem_pay'] ?? []; echo pay_block($conn, $__mp['choice'] ?? '', ['amount' => $__mp['amount'] ?? '', 'ref' => $__mp['name'] ?? ''], false, 'Now pay your membership fee'); ?>
        <p style="margin-top:20px"><a href="index.php" class="btn btn-gold">Back to home</a></p>
      </div>
    <?php elseif ($type === 'renew'): ?>
      <h2 style="font-size:1.6rem;margin-bottom:6px">Renew your membership</h2>
      <p class="muted" style="margin-bottom:24px">Already a NUFA member? Confirm who you are, then pay the <b><?php echo htmlspecialchars(mem_money($F['renew'], $curL)); ?></b> annual subscription. You do not need to register again.</p>
      <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" autocomplete="on">
        <?php echo mem_csrf_field(); ?><input type="hidden" name="form" value="renew"><input type="hidden" name="type" value="renew">
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">
        <h3 class="form-section">1 · Who is renewing?</h3>
        <div class="form-grid">
          <div class="field"><label for="r-email">Email you joined with <span class="req">*</span></label><input id="r-email" name="email" type="email" required value="<?php echo $val('email'); ?>"></div>
          <div class="field"><label for="r-who">Membership ID or phone number <span class="req">*</span></label><input id="r-who" name="who" required placeholder="e.g. NUFA/2026/0001 or 0772…" value="<?php echo $val('who'); ?>"><span class="hint">Your membership ID is on your membership card and in your approval email.</span></div>
        </div>
        <h3 class="form-section">2 · Subscription payment</h3>
        <p class="muted" style="font-size:.9rem;margin-bottom:14px">Annual subscription: <b><?php echo htmlspecialchars(mem_money($F['renew'], $curL)); ?></b></p>
        <div class="field full"><label>How will you pay?<?php echo $payGroups ? ' <span class="req">*</span>' : ''; ?></label><?php echo pay_picker($conn, 'payment_choice', $old['payment_choice'] ?? '', ['amount' => mem_money($F['renew'], $curL), 'ref' => 'Your membership ID']); ?></div>
        <details class="pay-already"<?php echo (!empty($old['payment_ref']) || pay_proof_required($conn)) ? ' open' : ''; ?>><summary><?php echo pay_proof_required($conn) ? 'Payment proof (required)' : 'Already paid? Add your transaction reference (optional)'; ?></summary>
          <div class="form-grid">
            <div class="field"><label for="r-ref">Transaction ID / reference<?php echo pay_proof_required($conn) ? ' <span class="req">*</span>' : ''; ?></label><input id="r-ref" name="payment_ref" value="<?php echo $val('payment_ref'); ?>"></div>
            <div class="field"><label for="r-pd">Date paid</label><input id="r-pd" name="payment_date" type="date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $val('payment_date'); ?>"></div>
          </div>
          <div class="field"><label for="r-proof">Proof of payment<?php echo pay_proof_required($conn) ? ' <span class="req">*</span>' : ' (optional)'; ?></label><input id="r-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"<?php echo pay_proof_required($conn) ? ' required' : ''; ?>><span class="hint">Receipt or screenshot — JPG, PNG, WebP or PDF, max 5 MB.</span></div>
        </details>
        <label class="check"><input type="checkbox" name="agree" value="1" required <?php echo !empty($old['agree']) ? 'checked' : ''; ?>><span>I confirm I am renewing my own NUFA membership and the payment details above are correct.</span></label>
        <button type="submit" class="btn btn-gold btn-block">Submit renewal</button>
      </form>
    <?php elseif ($S['membership_open'] !== '1'): ?>
      <div class="member-done"><h2 style="font-size:1.6rem">Applications are closed</h2><p>Membership registration is not open right now. Please check back soon or <a href="contact.php?topic=Membership" style="text-decoration:underline">contact us</a>.</p></div>
    <?php else: ?>
      <h2 style="font-size:1.6rem;margin-bottom:6px">Membership application</h2>
      <p class="muted" style="margin-bottom:24px">Fields marked <span style="color:var(--err)">*</span> are required. We only collect what is needed to process your application.</p>
      <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul></div><?php endif; ?>

      <form method="post" enctype="multipart/form-data" autocomplete="on">
        <?php echo mem_csrf_field(); ?>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">

        <h3 class="form-section">1 · Personal details</h3>
        <div class="form-grid">
          <div class="field"><label for="m-name">Full name <span class="req">*</span></label><input id="m-name" name="full_name" required autocomplete="name" value="<?php echo $val('full_name'); ?>"></div>
          <div class="field"><label for="m-email">Email address <span class="req">*</span></label><input id="m-email" name="email" type="email" required autocomplete="email" value="<?php echo $val('email'); ?>"></div>
          <div class="field"><label for="m-phone">Telephone <span class="req">*</span></label><input id="m-phone" name="phone" type="tel" required autocomplete="tel" placeholder="+256…" value="<?php echo $val('phone'); ?>"></div>
          <div class="field"><label for="m-gender">Gender <span class="req">*</span></label>
            <select id="m-gender" name="gender" required><option value="">Select…</option><?php foreach (['Female', 'Male', 'Other'] as $o) echo '<option' . (($old['gender'] ?? '') === $o ? ' selected' : '') . '>' . $o . '</option>'; ?></select></div>
          <div class="field"><label for="m-dob">Date of birth <span class="req">*</span></label><input id="m-dob" name="dob" type="date" required max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>" value="<?php echo $val('dob'); ?>"></div>
          <div class="field"><label for="m-id">National ID / passport no.</label><input id="m-id" name="id_number" value="<?php echo $val('id_number'); ?>"></div>
          <div class="field"><label for="m-district">District or location <span class="req">*</span></label><input id="m-district" name="district" required list="district-list" autocomplete="off" value="<?php echo $val('district'); ?>"><datalist id="district-list"><?php foreach (mem_subregion_map($conn) as $__r => $__ds) foreach ($__ds as $__d) echo '<option value="' . htmlspecialchars(ucwords($__d)) . '" label="' . $__r . '">'; ?></datalist></div>
          <div class="field"><label for="m-sub">Sub-region <span class="req">*</span></label>
            <select id="m-sub" name="subregion" required><option value="">Select your sub-region…</option><?php foreach (mem_subregion_options() as $o) echo '<option value="' . $o . '"' . (($old['subregion'] ?? '') === $o ? ' selected' : '') . '>' . ($o === 'Other' ? 'Other / outside Northern Uganda' : $o) . '</option>'; ?></select>
            <span class="hint">Filled in for you when you type your district; change it if needed.</span></div>
          <div class="field"><label for="m-addr">Physical address</label><input id="m-addr" name="address" value="<?php echo $val('address'); ?>"></div>
        </div>
        <div class="field"><label for="m-photo">Passport-size photo <span class="req">*</span></label><input id="m-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required><span class="hint">JPG, PNG or WebP, max 3 MB. Used on your membership card.</span></div>

        <h3 class="form-section">2 · Your craft</h3>
        <div class="form-grid">
          <div class="field"><label for="m-guild">Guild / area of creative practice <span class="req">*</span></label>
            <select id="m-guild" name="guild_id" required><option value="">Select your guild…</option>
              <?php foreach ($guilds as $g): ?><option value="<?php echo (int)$g['id']; ?>" <?php echo ((int)($old['guild_id'] ?? 0) === (int)$g['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['name']); ?></option><?php endforeach; ?>
            </select></div>
          <div class="field"><label for="m-occ">Occupation / role</label><input id="m-occ" name="occupation" placeholder="e.g. Cinematographer" value="<?php echo $val('occupation'); ?>"></div>
          <div class="field"><label for="m-exp">Experience level</label>
            <select id="m-exp" name="experience"><option value="">Select…</option><?php foreach (['Less than 1 year', '1–3 years', '4–7 years', '8+ years'] as $o) echo '<option' . (($old['experience'] ?? '') === $o ? ' selected' : '') . '>' . $o . '</option>'; ?></select></div>
          <div class="field"><label for="m-port">Portfolio or showreel link</label><input id="m-port" name="portfolio_url" type="url" placeholder="https://" value="<?php echo $val('portfolio_url'); ?>"><span class="hint">Optional</span></div>
        </div>
        <div class="field full"><label for="m-bio">Short professional profile</label><textarea id="m-bio" name="bio" placeholder="Tell us briefly about your work in film."><?php echo $val('bio'); ?></textarea></div>

        <h3 class="form-section">3 · Membership payment</h3>
        <?php if ($S['membership_fee'] !== ''): ?><p class="muted" style="font-size:.9rem;margin-bottom:14px">New member fee: <b><?php echo htmlspecialchars(mem_money($F['new'], $curL)); ?></b> <span style="color:#777">(<?php echo htmlspecialchars(number_format($F['joining'])); ?> joining fee + <?php echo htmlspecialchars(number_format($F['subscription'])); ?> annual subscription)</span></p><?php endif; ?>
        <div class="field full"><label>How will you pay?<?php echo $payGroups ? ' <span class="req">*</span>' : ''; ?></label><?php echo pay_picker($conn, 'payment_choice', $old['payment_choice'] ?? '', ['amount' => $S['membership_fee'] !== '' ? $S['membership_currency'] . ' ' . $S['membership_fee'] : '', 'ref' => 'Your full name']); ?></div>
        <details class="pay-already"<?php echo (!empty($old['payment_ref']) || pay_proof_required($conn)) ? ' open' : ''; ?>><summary><?php echo pay_proof_required($conn) ? 'Payment proof (required)' : 'Already paid? Add your transaction reference (optional)'; ?></summary>
          <div class="form-grid">
            <div class="field"><label for="m-ref">Transaction ID / reference<?php echo pay_proof_required($conn) ? ' <span class="req">*</span>' : ''; ?></label><input id="m-ref" name="payment_ref" value="<?php echo $val('payment_ref'); ?>"></div>
            <div class="field"><label for="m-amt">Amount paid (<?php echo $cur; ?>)</label><input id="m-amt" name="amount_paid" value="<?php echo $val('amount_paid', $S['membership_fee']); ?>"></div>
            <div class="field"><label for="m-pd">Date paid</label><input id="m-pd" name="payment_date" type="date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $val('payment_date'); ?>"></div>
          </div>
          <div class="field"><label for="m-proof">Proof of payment<?php echo pay_proof_required($conn) ? ' <span class="req">*</span>' : ' (optional)'; ?></label><input id="m-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"<?php echo pay_proof_required($conn) ? ' required' : ''; ?>><span class="hint">Receipt or screenshot — JPG, PNG, WebP or PDF, max 5 MB. Stored privately.</span></div>
        </details>
        <label class="check"><input type="checkbox" name="in_directory" value="1" <?php echo !empty($old['in_directory']) ? 'checked' : ''; ?>><span>Optional: once I am an approved member, list my <b>name, guild, region, district, craft and portfolio link</b> in the public <a href="directory.php" target="_blank">Filmmakers Directory</a> so people can find me. My phone and email are never shown.</span></label>
        <label class="check"><input type="checkbox" name="agree" value="1" required <?php echo !empty($old['agree']) ? 'checked' : ''; ?>><span>I confirm the information above is correct, I have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>, and I understand that my application is subject to NUFA's membership criteria and confirmation process.</span></label>
        <button type="submit" class="btn btn-gold btn-block">Submit application</button>
      </form>
    <?php endif; ?>
    </div>
  </div>
</section>
<script>(function(){var map=<?php echo json_encode(mem_subregion_map($conn), JSON_UNESCAPED_UNICODE); ?>,d=document.getElementById("m-district"),s=document.getElementById("m-sub");if(!d||!s)return;function guess(){if(s.value)return;var t=" "+d.value.toLowerCase().replace(/[^a-z0-9]+/g," ")+" ";for(var r in map){for(var i=0;i<map[r].length;i++){var x=map[r][i].replace(/[^a-z0-9]+/g," ").trim();if(x&&t.indexOf(" "+x+" ")>-1){s.value=r;return;}}}}d.addEventListener("change",guess);d.addEventListener("blur",guess);})();</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
