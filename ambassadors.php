<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/outreach.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
out_install($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$S = out_settings($conn);
$interests = out_lines($S['amb_interests']); $ways = out_lines($S['amb_ways']);
$avail = ['2–4 hours a month', '5–10 hours a month', 'More than 10 hours a month', 'Occasionally, for specific activities'];
$errors = []; $done = $_SESSION['amb_done'] ?? null; if (empty($_GET['done'])) $done = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $S['amb_open'] === '1') {
    if ($g = form_guard($conn, 'ambassador', 'ambassadors')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $name = $p('name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $country = in_array($_POST['country'] ?? '', out_countries(), true) ? $_POST['country'] : ''; $other = $p('country_other', 80); if ($country === 'Other' && $other !== '') $country = $other;
    $city = $p('city', 120); $occ = $p('occupation'); $org = $p('organisation'); $url = $p('profile_url', 250);
    $pi = array_values(array_intersect($interests, (array)($_POST['interests'] ?? []))); $pw = array_values(array_intersect($ways, (array)($_POST['ways'] ?? [])));
    $why = form_clean($_POST['motivation'] ?? '', 2500); $net = form_clean($_POST['network'] ?? '', 2000); $fund = form_clean($_POST['fundraising_idea'] ?? '', 2000);
    $av = in_array($_POST['availability'] ?? '', $avail, true) ? $_POST['availability'] : ''; $tz = $p('timezone', 60); $bt = $p('best_time', 120);
    if (mb_strlen($name) < 2) $errors[] = "Enter your full name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone or WhatsApp number we can reach you on.";
    if ($country === '') $errors[] = "Select your country of residence.";
    if (!$pi) $errors[] = "Choose at least one area of NUFA you are interested in.";
    if (!$pw) $errors[] = "Choose at least one way you can support NUFA.";
    if (mb_strlen($why) < 20) $errors[] = "Tell us why you would like to be a NUFA Ambassador.";
    if ($av === '') $errors[] = "Select how much time you can give.";
    if ($url !== '' && !preg_match('#^https?://#i', $url)) $errors[] = "Your profile link must start with https://";
    if (empty($_POST['meeting_ok'])) $errors[] = "Ambassadors meet the NUFA Board every month — please confirm you can join the monthly meeting.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to NUFA contacting you and keeping your application.";
    if (!$errors) {
        $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $i = out_csv($pi); $w = out_csv($pw);
        $s = $conn->prepare("INSERT INTO ambassadors (name,email,phone,country,city,occupation,organisation,profile_url,interests,support_ways,motivation,network,fundraising_idea,availability,timezone,best_time,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssssssssssssssss", $name, $email, $phone, $country, $city, $occ, $org, $url, $i, $w, $why, $net, $fund, $av, $tz, $bt, $ip); $s->execute();
        $id = $conn->insert_id; $ref = out_ref('AMB', $id); $conn->query("UPDATE ambassadors SET ref='$ref' WHERE id=$id");
        mem_send($conn, null, 'ambassador-received', $email, "We received your ambassador application ($ref)", "Hello $name,\n\nThank you for offering to be a NUFA Ambassador. Your reference is $ref. The NUFA team will review your application and contact you. Ambassadors meet the NUFA Board every month.");
        $to = out_notify_to($conn, $S, 'amb_notify_email'); if ($to) mem_send($conn, null, 'ambassador-admin', $to, "New ambassador application: $name ($country)", "$name ($email) from $country applied to be a NUFA Ambassador.\nInterests: $i\nWays to support: $w\n\nReview it in admin → Ambassadors.", mem_site_url() . '/admin/ambassadors.php?view=' . $id, $email);
        $_SESSION['amb_done'] = $ref; header("Location: ambassadors.php?done=1#apply"); exit;
    }
}
$pageTitle = "Become a NUFA Ambassador — Lobby, Fundraise and Champion Northern Uganda's Filmmakers";
$pageDesc  = "NUFA is calling for ambassadors to lobby for NUFA, share our proposal with donors, hold fundraising and meet the NUFA Board monthly.";
$activeNav = "partner"; $canonicalPath = "ambassadors.php";
include __DIR__ . "/includes/header.php";
$pub = $S['amb_show_public'] === '1' ? $conn->query("SELECT name, country, interests FROM ambassadors WHERE status='approved' AND is_public=1 ORDER BY name")->fetch_all(MYSQLI_ASSOC) : [];
$next = out_next_meeting($conn);
echo ui_page_hero('Partner with us', 'Become a NUFA Ambassador', $S['amb_intro'], cms_img_raw('partners', 'hero_image'), [['Partner With Us', 'partners.php'], ['Ambassadors']],
    '<a href="#apply" class="btn btn-gold">Apply to be an ambassador</a><a href="#role" class="btn btn-outline">What ambassadors do</a>');
$val = fn($k) => ui_e($_POST[$k] ?? '');
$has = fn($k, $v) => in_array($v, (array)($_POST[$k] ?? []), true) ? 'checked' : '';
?>
<section class="section" id="role"><div class="container">
  <div class="section-head center" data-reveal><div class="eyebrow">The role</div><h2>What NUFA Ambassadors do</h2><p>Ambassadors carry NUFA’s story to the people and institutions who can help it grow — wherever in the world they live.</p></div>
  <div class="ac-steps" data-reveal>
    <div><b>1</b><h3>Lobby for NUFA</h3><p>Speak up for Northern Uganda’s filmmakers with leaders, institutions, media and communities.</p></div>
    <div><b>2</b><h3>Share our proposal</h3><p>Put the NUFA proposal and donor pack in front of possible donors, sponsors and partners.</p></div>
    <div><b>3</b><h3>Hold fundraising</h3><p>Organise events, campaigns and giving circles that raise support for NUFA’s work.</p></div>
    <div><b>4</b><h3>Stay connected</h3><p>Meet the NUFA Board every month to report, plan together and share what you have learned.</p></div>
  </div>
</div></section>

<section class="section section--soft"><div class="container">
  <div class="section-head center"><div class="eyebrow">Choose your focus</div><h2>Where you can make a difference</h2><p>Pick the part of NUFA you care about most — you can choose more than one.</p></div>
  <div class="ac-ticks" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));max-width:1000px;margin:0 auto"><?php foreach ($interests as $i): ?><div class="op-note" style="margin:0;text-align:center;font-weight:700"><?php echo ui_e($i); ?></div><?php endforeach; ?></div>
  <?php if ($next): ?><p class="center" style="text-align:center;margin-top:26px"><b>Next ambassadors’ meeting with the Board:</b> <?php echo ui_e(date('l, j F Y', strtotime($next['meet_date']))); ?><?php echo $next['meet_time'] !== '' ? ' · ' . ui_e($next['meet_time']) : ''; ?></p><?php endif; ?>
</div></section>

<?php if ($pub): ?><section class="section"><div class="container"><div class="section-head center"><div class="eyebrow">Our ambassadors</div><h2>Champions of NUFA</h2></div>
  <div class="gs-grid"><?php foreach ($pub as $a): ?><div class="gs-card"><div class="gs-body"><span class="gs-cat"><?php echo ui_e($a['country']); ?></span><h3><?php echo ui_e($a['name']); ?></h3><p class="gs-title"><?php echo ui_e(implode(', ', array_slice(out_split($a['interests']), 0, 3))); ?></p></div></div><?php endforeach; ?></div></div></section><?php endif; ?>

<section class="section" id="apply"><div class="container" style="max-width:860px">
  <div class="section-head center"><div class="eyebrow">Apply</div><h2>Apply to be an ambassador</h2></div>
<?php if ($done): ?>
  <div class="ac-result is-pass"><h2>Application received</h2><p>Thank you. Your reference is <b><?php echo ui_e($done); ?></b>. The NUFA team will review your application and be in touch.</p><a class="btn btn-outline" href="partners.php">Back to Partner With Us</a></div>
<?php elseif ($S['amb_open'] !== '1'): ?><div class="empty-state"><h3>Ambassador applications are closed for now</h3><p>Please check back soon.</p></div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($e); ?></div><?php endforeach; ?>
  <form method="post" class="ac-form" novalidate><?php echo form_open_fields(); ?>
    <h3>About you</h3>
    <div class="ac-two"><div class="field"><label>Full name</label><input name="name" value="<?php echo $val('name'); ?>" required></div><div class="field"><label>Email</label><input type="email" name="email" value="<?php echo $val('email'); ?>" required></div></div>
    <div class="ac-two"><div class="field"><label>Phone / WhatsApp</label><input name="phone" value="<?php echo $val('phone'); ?>" required></div><div class="field"><label>Country of residence</label><select name="country"><option value="">Select…</option><?php foreach (out_countries() as $c): ?><option <?php echo ($_POST['country'] ?? '') === $c ? 'selected' : ''; ?>><?php echo ui_e($c); ?></option><?php endforeach; ?></select></div></div>
    <div class="ac-two"><div class="field"><label>If “Other”, which country?</label><input name="country_other" value="<?php echo $val('country_other'); ?>"></div><div class="field"><label>City / town</label><input name="city" value="<?php echo $val('city'); ?>"></div></div>
    <div class="ac-two"><div class="field"><label>Occupation</label><input name="occupation" value="<?php echo $val('occupation'); ?>"></div><div class="field"><label>Organisation (if any)</label><input name="organisation" value="<?php echo $val('organisation'); ?>"></div></div>
    <div class="field"><label>LinkedIn, website or social profile (optional)</label><input type="url" name="profile_url" value="<?php echo $val('profile_url'); ?>" placeholder="https://"></div>
    <h3 style="margin-top:20px">Your interests in NUFA</h3>
    <div class="field"><label>Which parts of NUFA would you like to focus on?</label><div class="ac-ticks"><?php foreach ($interests as $i): ?><label class="op-check"><input type="checkbox" name="interests[]" value="<?php echo ui_e($i); ?>" <?php echo $has('interests', $i); ?>> <?php echo ui_e($i); ?></label><?php endforeach; ?></div></div>
    <div class="field"><label>How can you support NUFA?</label><div class="ac-ticks"><?php foreach ($ways as $w): ?><label class="op-check"><input type="checkbox" name="ways[]" value="<?php echo ui_e($w); ?>" <?php echo $has('ways', $w); ?>> <?php echo ui_e($w); ?></label><?php endforeach; ?></div></div>
    <div class="field"><label>Why do you want to be a NUFA Ambassador?</label><textarea name="motivation" rows="4" required><?php echo $val('motivation'); ?></textarea></div>
    <div class="field"><label>Who can you reach? (networks, communities, institutions, companies, diaspora groups…)</label><textarea name="network" rows="3"><?php echo $val('network'); ?></textarea></div>
    <div class="field"><label>Any fundraising idea you would like to try? (optional)</label><textarea name="fundraising_idea" rows="3"><?php echo $val('fundraising_idea'); ?></textarea></div>
    <h3 style="margin-top:20px">Availability</h3>
    <div class="ac-two"><div class="field"><label>Time you can give</label><select name="availability"><option value="">Select…</option><?php foreach ($avail as $a): ?><option <?php echo ($_POST['availability'] ?? '') === $a ? 'selected' : ''; ?>><?php echo ui_e($a); ?></option><?php endforeach; ?></select></div><div class="field"><label>Your time zone</label><input name="timezone" value="<?php echo $val('timezone'); ?>" placeholder="e.g. East Africa Time, GMT+1"></div></div>
    <div class="field"><label>Best days / times for the monthly meeting</label><input name="best_time" value="<?php echo $val('best_time'); ?>" placeholder="e.g. weekday evenings, Saturdays"></div>
    <label class="op-check"><input type="checkbox" name="meeting_ok" value="1" <?php echo $has('meeting_ok', '1'); ?>> I can join the monthly ambassadors’ meeting with the NUFA Board and stay in touch with the NUFA team.</label>
    <label class="op-check" style="margin:8px 0 16px"><input type="checkbox" name="consent" value="1" <?php echo $has('consent', '1'); ?>> I consent to NUFA contacting me and keeping my application for review.</label>
    <button class="btn btn-gold">Send my application</button>
  </form>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
