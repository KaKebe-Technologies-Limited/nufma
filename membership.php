<?php
require __DIR__ . "/includes/membership.php";   // also loads db.php + creates tables on first run
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$S = mem_settings($conn);
$guilds = [];
$gr = $conn->query("SELECT id, name, description FROM guilds WHERE is_active=1 ORDER BY sort_order ASC, name ASC");
while ($g = $gr->fetch_assoc()) $guilds[] = $g;

$old = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $guildId = (int)($_POST['guild_id'] ?? 0);
    $guildOk = false; foreach ($guilds as $g) if ((int)$g['id'] === $guildId) $guildOk = true;
    if (!$guildOk) $errors[] = "Choose the guild you belong to.";
    if ($f('payment_ref') === '') $errors[] = "Enter the payment reference / transaction ID.";
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
        [$proof, $e2] = mem_store_upload($_FILES['payment_proof'] ?? [], MEM_PROOF_TYPES, 5 * 1048576, 'proof');
        if ($e2) $errors[] = "Proof of payment: " . $e2;
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
                 $f('experience'), $f('portfolio_url'), $f('bio'), $guildId, $photo, $f('payment_method'), $f('payment_ref'), $payDate,
                 $f('amount_paid'), $proof];
        $stmt->bind_param("sssssssssssssissssss", ...$vals);
        if ($stmt->execute()) {
            $mid = $conn->insert_id;
            $member = $conn->query("SELECT * FROM members WHERE id=$mid")->fetch_assoc();
            $vars = mem_member_vars($conn, $member);
            if ($S['send_receipt_email'] === '1') {
                mem_send($conn, $mid, 'received', $email, mem_fill($S['email_received_subject'], $vars), mem_fill($S['email_received_body'], $vars));
            }
            if ($S['notify_admin'] === '1' && $S['admin_notify_email']) {
                $adminUrl = mem_site_url() . '/admin/member-view.php?id=' . $mid;
                mem_send($conn, $mid, 'admin', $S['admin_notify_email'], "New membership application: $name",
                    "A new membership application is waiting for review.\n\nName: $name\nGuild: {$vars['guild']}\nPhone: " . $f('phone') . "\nPayment ref: " . $f('payment_ref') . "\n\nReview it: $adminUrl",
                    $adminUrl, $email);
            }
            $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            header("Location: membership.php?done=1#top");
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
      <ol class="member-steps">
        <li><b>Complete the application</b><span>Your details, area of creative practice and a passport photo.</span></li>
        <li><b>Pay &amp; upload proof</b><span>Pay the membership fee and attach your receipt or screenshot.</span></li>
        <li><b>Review &amp; confirmation</b><span>NUFA reviews your application and emails you the outcome and your registration number.</span></li>
        <li><b>Receive your card</b><span>Confirmed members get a membership card showing the validity period.</span></li>
      </ol>
      <?php if ($S['membership_fee'] !== ''): ?><div class="member-fee"><span>Membership fee</span><b><?php echo $cur . ' ' . htmlspecialchars($S['membership_fee']); ?></b></div><?php endif; ?>
      <div class="member-pay"><h4>How to pay</h4><p><?php echo nl2br(htmlspecialchars($S['payment_instructions'])); ?></p></div>
      <p style="font-size:.82rem;color:var(--muted-on-dark);margin-top:20px"><?php echo cms_t('about', 'membership_criteria'); ?></p>
    </aside>

    <div class="form-card" data-reveal>
    <?php if (isset($_GET['done'])): ?>
      <div class="member-done">
        <div class="member-done-ico" aria-hidden="true">✓</div>
        <h2 style="font-size:1.6rem">Application received</h2>
        <p>Thank you for applying to join NUFA. We've emailed you a confirmation. Your application is now subject to NUFA's membership criteria and confirmation process — submitting it does not automatically make you a member. You'll receive another email once it has been reviewed.</p>
        <p style="margin-top:20px"><a href="index.php" class="btn btn-gold">Back to home</a></p>
      </div>
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
        <?php if ($S['membership_fee'] !== ''): ?><p class="muted" style="font-size:.9rem;margin-bottom:14px">Fee: <b><?php echo $cur . ' ' . htmlspecialchars($S['membership_fee']); ?></b> — see “How to pay” for account details.</p><?php endif; ?>
        <div class="form-grid">
          <div class="field"><label for="m-pm">Payment method</label><select id="m-pm" name="payment_method"><?php foreach (['Mobile Money', 'Bank deposit', 'Cash at office', 'Other'] as $o) echo '<option' . (($old['payment_method'] ?? '') === $o ? ' selected' : '') . '>' . $o . '</option>'; ?></select></div>
          <div class="field"><label for="m-ref">Transaction ID / reference <span class="req">*</span></label><input id="m-ref" name="payment_ref" required value="<?php echo $val('payment_ref'); ?>"></div>
          <div class="field"><label for="m-amt">Amount paid (<?php echo $cur; ?>)</label><input id="m-amt" name="amount_paid" value="<?php echo $val('amount_paid', $S['membership_fee']); ?>"></div>
          <div class="field"><label for="m-pd">Date paid</label><input id="m-pd" name="payment_date" type="date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo $val('payment_date'); ?>"></div>
        </div>
        <div class="field"><label for="m-proof">Proof of payment <span class="req">*</span></label><input id="m-proof" name="payment_proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required><span class="hint">Receipt or screenshot — JPG, PNG, WebP or PDF, max 5 MB. Stored privately.</span></div>

        <label class="check"><input type="checkbox" name="agree" value="1" required <?php echo !empty($old['agree']) ? 'checked' : ''; ?>><span>I confirm the information above is correct, I have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>, and I understand that my application is subject to NUFA's membership criteria and confirmation process.</span></label>
        <button type="submit" class="btn btn-gold btn-block">Submit application</button>
      </form>
    <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
