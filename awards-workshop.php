<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/outreach.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
out_install($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$S = out_settings($conn);
$contribs = out_lines($S['bld_contributions']);
$errors = []; $done = $_SESSION['ws_done'] ?? null; if (empty($_GET['done'])) $done = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $S['bld_workshops_open'] === '1') {
    if ($g = form_guard($conn, 'award_workshop', 'award_workshops')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $host = $p('host_name'); $ht = in_array($_POST['host_type'] ?? '', ['Organisation', 'Individual'], true) ? $_POST['host_type'] : 'Organisation'; $contact = $p('contact_name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40);
    $title = $p('title'); $desc = form_clean($_POST['description'] ?? '', 2500); $aud = $p('audience', 250);
    $d1 = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['proposed_date'] ?? '') ? $_POST['proposed_date'] : null; $d2 = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['proposed_end'] ?? '') ? $_POST['proposed_end'] : null; $dur = $p('duration_text', 80);
    $venue = $p('venue', 250); $town = $p('town', 120); $cap = max(0, (int)($_POST['venue_capacity'] ?? 0)); $need = max(0, (int)($_POST['filmmakers_needed'] ?? 0));
    $ct = array_values(array_intersect($contribs, (array)($_POST['contribution_types'] ?? []))); $contrib = form_clean($_POST['contribution'] ?? '', 2500); $nufa = form_clean($_POST['needs_from_nufa'] ?? '', 2000);
    if ($host === '') $errors[] = "Enter the name of the person or organisation hosting.";
    if ($contact === '') $errors[] = "Enter a contact person.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone number we can reach you on.";
    if ($title === '') $errors[] = "Enter the workshop topic or title.";
    if (mb_strlen($desc) < 20) $errors[] = "Describe what the workshop will cover.";
    if ($venue === '' || $town === '') $errors[] = "Enter the venue and the town where it will take place.";
    if ($need < 1) $errors[] = "Enter how many filmmakers you would like to have at the workshop.";
    if ($d1 && $d2 && $d2 < $d1) $errors[] = "The end date must not be before the start date.";
    if (!$ct && $contrib === '') $errors[] = "Tell us what you will contribute.";
    if (mb_strlen($contrib) < 10) $errors[] = "Describe your contribution in a few words.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted about your workshop.";
    if (!$errors) {
        $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $cts = out_csv($ct);
        $s = $conn->prepare("INSERT INTO award_workshops (host_name,host_type,contact_name,email,phone,title,description,audience,proposed_date,proposed_end,duration_text,venue,town,venue_capacity,filmmakers_needed,contribution_types,contribution,needs_from_nufa,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssssssssssssiissss", $host, $ht, $contact, $email, $phone, $title, $desc, $aud, $d1, $d2, $dur, $venue, $town, $cap, $need, $cts, $contrib, $nufa, $ip); $s->execute();
        $id = $conn->insert_id; $ref = out_ref('WSH', $id); $conn->query("UPDATE award_workshops SET ref='$ref' WHERE id=$id");
        mem_send($conn, null, 'workshop-received', $email, "We received your workshop proposal: $title ($ref)", "Hello $contact,\n\nThank you for offering to host a pre-event workshop for the NUFA Awards. Your reference is $ref. The NUFA team will review it and contact you.");
        $to = out_notify_to($conn, $S, 'bld_notify_email'); if ($to) mem_send($conn, null, 'workshop-admin', $to, "New workshop proposal: $title ($town)", "$host ($email) proposes “$title” at $venue, $town for $need filmmakers.\nContribution: " . ($cts ?: '—') . "\n\nReview it in admin → Awards build-up.", mem_site_url() . '/admin/award-acts.php?tab=workshops&view=' . $id, $email);
        $_SESSION['ws_done'] = $ref; header("Location: awards-workshop.php?done=1"); exit;
    }
}
$pageTitle = "Host a pre-event workshop — NUFA Awards"; $pageDesc = "Host a pre-event workshop for filmmakers in the build-up to the NUFA Awards: add your venue, the number of filmmakers you need and your contribution."; $activeNav = "awards"; $canonicalPath = "awards-workshop.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Awards', 'Host a pre-event workshop', $S['bld_workshop_intro'], cms_img_raw('awards', 'hero_image'), [['NUFA Awards', 'awards.php'], ['Host a workshop']]);
$val = fn($k) => ui_e($_POST[$k] ?? '');
?>
<section class="section"><div class="container" style="max-width:860px">
<?php if ($done): ?><div class="ac-result is-pass"><h2>Proposal received</h2><p>Thank you. Your reference is <b><?php echo ui_e($done); ?></b>. We will review your workshop proposal and contact you.</p><a class="btn btn-outline" href="awards.php#buildup">Back to the NUFA Awards</a></div>
<?php elseif ($S['bld_workshops_open'] !== '1'): ?><div class="empty-state"><h3>Workshop proposals are closed for now</h3></div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($e); ?></div><?php endforeach; ?>
  <form method="post" class="ac-form" novalidate><?php echo form_open_fields(); ?>
    <h3>Who is hosting</h3>
    <div class="ac-two"><div class="field"><label>Host name</label><input name="host_name" value="<?php echo $val('host_name'); ?>" required></div><div class="field"><label>Hosting as</label><select name="host_type"><?php foreach (['Organisation', 'Individual'] as $t): ?><option <?php echo ($_POST['host_type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option><?php endforeach; ?></select></div></div>
    <div class="ac-two"><div class="field"><label>Contact person</label><input name="contact_name" value="<?php echo $val('contact_name'); ?>" required></div><div class="field"><label>Email</label><input type="email" name="email" value="<?php echo $val('email'); ?>" required></div></div>
    <div class="field"><label>Phone / WhatsApp</label><input name="phone" value="<?php echo $val('phone'); ?>" required></div>
    <h3 style="margin-top:20px">The workshop</h3>
    <div class="field"><label>Workshop topic or title</label><input name="title" value="<?php echo $val('title'); ?>" required></div>
    <div class="field"><label>What will it cover?</label><textarea name="description" rows="4" required><?php echo $val('description'); ?></textarea></div>
    <div class="field"><label>Who is it for? (e.g. emerging filmmakers, actors, students)</label><input name="audience" value="<?php echo $val('audience'); ?>"></div>
    <div class="ac-two"><div class="field"><label>Proposed start date</label><input type="date" name="proposed_date" value="<?php echo $val('proposed_date'); ?>"></div><div class="field"><label>Last day (if several days)</label><input type="date" name="proposed_end" value="<?php echo $val('proposed_end'); ?>"></div></div>
    <div class="field"><label>How long? (e.g. one day, 2 days, half day)</label><input name="duration_text" value="<?php echo $val('duration_text'); ?>"></div>
    <h3 style="margin-top:20px">Venue and filmmakers</h3>
    <div class="ac-two"><div class="field"><label>Venue</label><input name="venue" value="<?php echo $val('venue'); ?>" required></div><div class="field"><label>Town / district</label><input name="town" value="<?php echo $val('town'); ?>" required></div></div>
    <div class="ac-two"><div class="field"><label>Venue capacity (people)</label><input type="number" name="venue_capacity" min="0" value="<?php echo $val('venue_capacity'); ?>"></div><div class="field"><label>Number of filmmakers you need</label><input type="number" name="filmmakers_needed" min="1" value="<?php echo $val('filmmakers_needed'); ?>" required></div></div>
    <h3 style="margin-top:20px">Your contribution</h3>
    <div class="field"><label>What will you provide?</label><div class="ac-ticks"><?php foreach ($contribs as $c): ?><label class="op-check"><input type="checkbox" name="contribution_types[]" value="<?php echo ui_e($c); ?>" <?php echo in_array($c, (array)($_POST['contribution_types'] ?? []), true) ? 'checked' : ''; ?>> <?php echo ui_e($c); ?></label><?php endforeach; ?></div></div>
    <div class="field"><label>Describe your contribution</label><textarea name="contribution" rows="3" required><?php echo $val('contribution'); ?></textarea></div>
    <div class="field"><label>What do you need from NUFA? (e.g. help mobilising filmmakers, facilitators, publicity)</label><textarea name="needs_from_nufa" rows="3"><?php echo $val('needs_from_nufa'); ?></textarea></div>
    <label class="op-check" style="margin:8px 0 16px"><input type="checkbox" name="consent" value="1" <?php echo !empty($_POST['consent']) ? 'checked' : ''; ?>> I consent to NUFA contacting me about this workshop. If it is approved, NUFA may announce it publicly with the venue and host name.</label>
    <button class="btn btn-gold">Send my proposal</button>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
