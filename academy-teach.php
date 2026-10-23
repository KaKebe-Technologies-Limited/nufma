<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$S = acad_settings($conn);
$errors = []; $done = $_SESSION['acad_trainer_done'] ?? null; if (empty($_GET['done'])) $done = null;
$modes = ['physical' => 'In person (physical classes)', 'virtual' => 'Online (virtual classes)', 'both' => 'Both in person and online'];
$exp = ['Under 2 years', '2–5 years', '5–10 years', 'Over 10 years'];
$cats = acad_categories();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $S['acad_trainers_open'] === '1') {
    if ($g = form_guard($conn, 'academy_trainer', 'academy_trainers')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $name = $p('name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $org = $p('organisation'); $loc = $p('location');
    $mode = array_key_exists($_POST['mode'] ?? '', $modes) ? $_POST['mode'] : 'both';
    $ex = in_array($_POST['experience'] ?? '', $exp, true) ? $_POST['experience'] : '';
    $areas = array_values(array_intersect($cats, (array)($_POST['areas'] ?? []))); $expertise = implode(', ', $areas);
    $bio = form_clean($_POST['bio'] ?? '', 2500); $topics = form_clean($_POST['topics'] ?? '', 2500); $avail = $p('availability', 250); $site = $p('portfolio_url', 490);
    if (mb_strlen($name) < 2) $errors[] = "Enter your full name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone number we can reach you on.";
    if (!$areas) $errors[] = "Choose at least one area you can teach.";
    if ($ex === '') $errors[] = "Select your years of experience.";
    if ($bio === '') $errors[] = "Tell us about your background and credits.";
    if ($topics === '') $errors[] = "Describe the class or topics you would like to facilitate.";
    if ($site !== '' && !preg_match('#^https?://#i', $site)) $errors[] = "Your portfolio link must start with https://";
    if (empty($_POST['consent'])) $errors[] = "Please confirm that the information is accurate and that you consent to being contacted.";
    $cv = '';
    if (!$errors && !empty($_FILES['cv']['name'])) { [$fn, $er] = mem_store_upload($_FILES['cv'], ['application/pdf' => 'pdf'], 5 * 1048576, 'cv', acad_storage()); if ($er) $errors[] = "CV: $er"; else $cv = $fn; }
    if (!$errors) {
        $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
        $s = $conn->prepare("INSERT INTO academy_trainers (name,email,phone,organisation,expertise,mode,location,experience,bio,topics,availability,portfolio_url,cv,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("ssssssssssssss", $name, $email, $phone, $org, $expertise, $mode, $loc, $ex, $bio, $topics, $avail, $site, $cv, $ip); $s->execute();
        $id = $conn->insert_id; $ref = 'TR-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); $conn->query("UPDATE academy_trainers SET ref='$ref' WHERE id=$id");
        mem_send($conn, null, 'academy-trainer', $email, "We received your facilitator application ($ref)", "Hello $name,\n\nThank you for applying to facilitate classes with NUFA Academy. Your reference is $ref. Our team will review your application and contact you.");
        $to = acad_notify_to($conn, $S); if ($to) mem_send($conn, null, 'academy-trainer-admin', $to, "New facilitator application: $name ($ref)", "$name ($email) applied to facilitate: $expertise.\nMode: " . $modes[$mode] . "\n\nReview it in admin → NUFA Academy → Trainer applications.", mem_site_url() . '/admin/academy-trainers.php?view=' . $id, $email);
        $_SESSION['acad_trainer_done'] = $ref; header("Location: academy-teach.php?done=1"); exit;
    }
}
$pageTitle = "Apply to facilitate classes — NUFA Academy"; $pageDesc = "Experienced film trainers can apply to facilitate NUFA Academy classes in person or online."; $activeNav = "academy"; $canonicalPath = "academy-teach.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Academy', 'Teach with NUFA Academy', $S['acad_trainer_intro'], 'gallery/2026/nufa26-20.jpg', [['NUFA Academy', 'academy.php'], ['Apply to facilitate']]);
$val = fn($k, $d = '') => ui_e($_POST[$k] ?? $d);
?>
<section class="section"><div class="container" style="max-width:780px">
<?php if ($done): ?>
  <div class="ac-result is-pass"><h2>Application received</h2><p>Thank you. Your reference is <b><?php echo ui_e($done); ?></b>. We will review your application and contact you by email.</p><a class="btn btn-outline" href="academy.php">Back to NUFA Academy</a></div>
<?php elseif ($S['acad_trainers_open'] !== '1'): ?>
  <div class="empty-state"><h3>Facilitator applications are closed</h3><p>Please check back later.</p></div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($e); ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data" class="ac-form" novalidate><?php echo form_open_fields(); ?>
    <h3>About you</h3>
    <div class="field"><label>Full name</label><input name="name" value="<?php echo $val('name'); ?>" required></div>
    <div class="ac-two"><div class="field"><label>Email</label><input type="email" name="email" value="<?php echo $val('email'); ?>" required></div><div class="field"><label>Phone / WhatsApp</label><input name="phone" value="<?php echo $val('phone'); ?>" required></div></div>
    <div class="ac-two"><div class="field"><label>Organisation (if any)</label><input name="organisation" value="<?php echo $val('organisation'); ?>"></div><div class="field"><label>Where are you based?</label><input name="location" value="<?php echo $val('location'); ?>" placeholder="Town / country"></div></div>
    <h3 style="margin-top:20px">What you can teach</h3>
    <div class="field"><label>Subjects (tick all that apply)</label><div class="ac-ticks"><?php foreach ($cats as $c): ?><label class="op-check"><input type="checkbox" name="areas[]" value="<?php echo ui_e($c); ?>" <?php echo in_array($c, (array)($_POST['areas'] ?? []), true) ? 'checked' : ''; ?>> <?php echo ui_e($c); ?></label><?php endforeach; ?></div></div>
    <div class="ac-two"><div class="field"><label>How would you like to teach?</label><select name="mode"><?php foreach ($modes as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($_POST['mode'] ?? 'both') === $k ? 'selected' : ''; ?>><?php echo ui_e($l); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Years of professional experience</label><select name="experience"><option value="">Select…</option><?php foreach ($exp as $x): ?><option <?php echo ($_POST['experience'] ?? '') === $x ? 'selected' : ''; ?>><?php echo ui_e($x); ?></option><?php endforeach; ?></select></div></div>
    <div class="field"><label>Your background, credits and training experience</label><textarea name="bio" rows="5" required><?php echo $val('bio'); ?></textarea></div>
    <div class="field"><label>Class or topics you would like to facilitate</label><textarea name="topics" rows="4" required><?php echo $val('topics'); ?></textarea></div>
    <div class="field"><label>Availability</label><input name="availability" value="<?php echo $val('availability'); ?>" placeholder="e.g. weekends, evenings, school holidays"></div>
    <div class="ac-two"><div class="field"><label>Portfolio, showreel or website (optional)</label><input type="url" name="portfolio_url" value="<?php echo $val('portfolio_url'); ?>" placeholder="https://"></div><div class="field"><label>CV (PDF, optional, max 5 MB)</label><input type="file" name="cv" accept="application/pdf"></div></div>
    <label class="op-check" style="margin:10px 0 18px"><input type="checkbox" name="consent" value="1" <?php echo !empty($_POST['consent']) ? 'checked' : ''; ?>> I confirm the information is accurate and I consent to NUFA contacting me and keeping my application for review.</label>
    <button class="btn btn-gold">Send my application</button>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
