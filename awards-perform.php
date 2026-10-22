<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/outreach.php";
require_once __DIR__ . "/includes/award-events.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
out_install($conn); aev_install($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$S = out_settings($conn);
$types = out_lines($S['bld_act_types']);
$events = $conn->query("SELECT id, slug, title, event_date, date_confirmed, date_note FROM award_events WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$errors = []; $done = $_SESSION['act_done'] ?? null; if (empty($_GET['done'])) $done = null;
$preSlug = preg_replace('/[^a-z0-9-]/', '', $_GET['event'] ?? ''); $sel = array_map('intval', (array)($_POST['events'] ?? []));
if (!$_POST) foreach ($events as $ev) if ($ev['slug'] === $preSlug) $sel[] = (int)$ev['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $S['bld_acts_open'] === '1') {
    if ($g = form_guard($conn, 'award_act', 'award_acts')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $name = $p('name'); $type = in_array($_POST['act_type'] ?? '', $types, true) ? $_POST['act_type'] : ''; $evs = array_values(array_intersect($sel, array_map('intval', array_column($events, 'id'))));
    $count = max(1, (int)($_POST['performers'] ?? 1)); $dur = (int)($_POST['duration_min'] ?? 0); $desc = form_clean($_POST['description'] ?? '', 2500); $samples = $p('samples_url', 490); $tech = $p('tech_needs', 590);
    $fee = in_array($_POST['fee_option'] ?? '', ['free', 'negotiable', 'fee'], true) ? $_POST['fee_option'] : 'free'; $feeAmt = $p('fee_amount', 80); $bio = form_clean($_POST['bio'] ?? '', 2500);
    $contact = $p('contact_name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $social = $p('social_url', 250);
    if ($name === '') $errors[] = "Enter your name or your group’s name.";
    if ($type === '') $errors[] = "Choose the type of act.";
    if (!$evs) $errors[] = "Choose at least one NUFA Awards event you would like to perform at.";
    if ($dur < 1) $errors[] = "Enter how long your performance runs, in minutes.";
    if (mb_strlen($desc) < 20) $errors[] = "Describe your performance.";
    if ($samples === '' || !preg_match('#^https?://#i', $samples)) $errors[] = "Add a link to a video or audio sample of your work (YouTube, SoundCloud, Instagram…).";
    if ($social !== '' && !preg_match('#^https?://#i', $social)) $errors[] = "Your social link must start with https://";
    if (mb_strlen($bio) < 20) $errors[] = "Tell us a little about you or your group.";
    if ($contact === '') $errors[] = "Enter a contact person.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone number we can reach you on.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted and to your details being used to plan the line-up.";
    $photo = '';
    if (!$errors && !empty($_FILES['photo']['name'])) { [$fn, $er] = mem_store_upload($_FILES['photo'], MEM_IMG_TYPES, 4 * 1048576, 'act', OUT_IMG_DIR); if ($er) $errors[] = "Photo: $er"; else $photo = 'uploads/outreach/' . $fn; }
    if (!$errors) {
        $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $ids = implode(',', $evs);
        $s = $conn->prepare("INSERT INTO award_acts (name,act_type,event_ids,performers,duration_min,description,samples_url,tech_needs,fee_option,fee_amount,bio,photo,contact_name,email,phone,social_url,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssiissssssssssss", $name, $type, $ids, $count, $dur, $desc, $samples, $tech, $fee, $feeAmt, $bio, $photo, $contact, $email, $phone, $social, $ip); $s->execute();
        $id = $conn->insert_id; $ref = out_ref('ACT', $id); $conn->query("UPDATE award_acts SET ref='$ref' WHERE id=$id");
        mem_send($conn, null, 'act-received', $email, "We received your act: $name ($ref)", "Hello $contact,\n\nThank you for applying to showcase at the NUFA Awards. Your reference is $ref. The NUFA team will review your application and contact you about the line-up.");
        $to = out_notify_to($conn, $S, 'bld_notify_email'); if ($to) mem_send($conn, null, 'act-admin', $to, "New performer application: $name ($type)", "$name ($email) wants to perform at: " . aev_names($conn, $evs) . ".\nDuration: $dur min · Performers: $count\n\nReview it in admin → Awards build-up.", mem_site_url() . '/admin/award-acts.php?view=' . $id, $email);
        $_SESSION['act_done'] = $ref; header("Location: awards-perform.php?done=1"); exit;
    }
}
$pageTitle = "Perform at the NUFA Awards — Musicians, Poets and Spoken-Word Artists"; $pageDesc = "Apply to showcase your music, poetry or spoken word at the NUFA Awards Launch, Nomination Night or Grand Finale."; $activeNav = "awards"; $canonicalPath = "awards-perform.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Awards', 'Perform at the NUFA Awards', $S['bld_acts_intro'], cms_img_raw('awards', 'hero_image'), [['NUFA Awards', 'awards.php'], ['Perform']]);
$val = fn($k) => ui_e($_POST[$k] ?? '');
?>
<section class="section"><div class="container" style="max-width:860px">
<?php if ($done): ?><div class="ac-result is-pass"><h2>Application received</h2><p>Thank you. Your reference is <b><?php echo ui_e($done); ?></b>. We will review your act and contact you about the line-up.</p><a class="btn btn-outline" href="awards.php#events">Back to the NUFA Awards</a></div>
<?php elseif ($S['bld_acts_open'] !== '1'): ?><div class="empty-state"><h3>Performer applications are closed for now</h3></div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($e); ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data" class="ac-form" novalidate><?php echo form_open_fields(); ?>
    <h3>Your act</h3>
    <div class="ac-two"><div class="field"><label>Name or stage name (or group)</label><input name="name" value="<?php echo $val('name'); ?>" required></div><div class="field"><label>Type of act</label><select name="act_type"><option value="">Select…</option><?php foreach ($types as $t): ?><option <?php echo ($_POST['act_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo ui_e($t); ?></option><?php endforeach; ?></select></div></div>
    <div class="field"><label>Which event(s) would you like to perform at?</label><div class="ac-ticks"><?php foreach ($events as $ev): ?><label class="op-check"><input type="checkbox" name="events[]" value="<?php echo (int)$ev['id']; ?>" <?php echo in_array((int)$ev['id'], $sel, true) ? 'checked' : ''; ?>> <?php echo ui_e($ev['title']); ?> <small>(<?php echo ui_e(aev_when($ev)); ?>)</small></label><?php endforeach; ?></div></div>
    <div class="ac-two"><div class="field"><label>Number of performers on stage</label><input type="number" name="performers" min="1" value="<?php echo $val('performers') ?: 1; ?>"></div><div class="field"><label>Length of your performance (minutes)</label><input type="number" name="duration_min" min="1" value="<?php echo $val('duration_min'); ?>"></div></div>
    <div class="field"><label>Describe what you will perform</label><textarea name="description" rows="4" required><?php echo $val('description'); ?></textarea></div>
    <div class="field"><label>Link to a video or audio sample (YouTube, SoundCloud, Instagram…)</label><input type="url" name="samples_url" value="<?php echo $val('samples_url'); ?>" placeholder="https://" required></div>
    <div class="field"><label>Technical needs (microphones, backing track, instruments, lighting…)</label><input name="tech_needs" value="<?php echo $val('tech_needs'); ?>"></div>
    <div class="ac-two"><div class="field"><label>Performance fee</label><select name="fee_option"><?php foreach (['free' => 'I will perform for free / exposure', 'negotiable' => 'Open to discussion', 'fee' => 'I charge a fee'] as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($_POST['fee_option'] ?? 'free') === $k ? 'selected' : ''; ?>><?php echo ui_e($l); ?></option><?php endforeach; ?></select></div><div class="field"><label>Your fee, if any</label><input name="fee_amount" value="<?php echo $val('fee_amount'); ?>" placeholder="e.g. UGX 500,000"></div></div>
    <div class="field"><label>About you or your group</label><textarea name="bio" rows="3" required><?php echo $val('bio'); ?></textarea></div>
    <div class="field"><label>Photo (optional, JPG/PNG/WebP up to 4 MB)</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>
    <h3 style="margin-top:20px">Contact</h3>
    <div class="ac-two"><div class="field"><label>Contact person</label><input name="contact_name" value="<?php echo $val('contact_name'); ?>" required></div><div class="field"><label>Email</label><input type="email" name="email" value="<?php echo $val('email'); ?>" required></div><div class="field"><label>Phone / WhatsApp</label><input name="phone" value="<?php echo $val('phone'); ?>" required></div><div class="field"><label>Instagram / Facebook / YouTube (optional)</label><input type="url" name="social_url" value="<?php echo $val('social_url'); ?>" placeholder="https://"></div></div>
    <label class="op-check" style="margin:10px 0 16px"><input type="checkbox" name="consent" value="1" <?php echo !empty($_POST['consent']) ? 'checked' : ''; ?>> I consent to NUFA contacting me and using these details to plan the line-up. If my act is selected, NUFA may announce my name, photo and act publicly.</label>
    <button class="btn btn-gold">Send my application</button>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
