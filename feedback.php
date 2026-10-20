<?php
require_once __DIR__ . "/includes/feedback.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
fb_install($conn);

$S = fb_settings($conn);
$enabled = $S['fb_enabled'] === '1';
$cats = fb_categories(); $types = fb_types(); $subjects = fb_subjects($conn);
$errors = []; $done = null;
if (!empty($_GET['done']) && !empty($_SESSION['fb_done'])) $done = $_SESSION['fb_done'];
$pre = ['category' => in_array($_GET['cat'] ?? '', array_keys($cats), true) ? $_GET['cat'] : '', 'about' => mb_substr(trim($_GET['about'] ?? ''), 0, 200)];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $enabled && !$done) {
    if ($g = form_guard($conn, 'feedback', 'enquiries')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $cat = array_key_exists($_POST['category'] ?? '', $cats) ? $_POST['category'] : '';
    $about = $p('about'); if ($about === '__other') $about = $p('about_other');
    $type = array_key_exists($_POST['feedback_type'] ?? '', $types) ? $_POST['feedback_type'] : '';
    $rating = (int)($_POST['rating'] ?? 0); $comment = form_clean($_POST['comment'] ?? '', 3000); $sugg = form_clean($_POST['suggestion'] ?? '', 2000);
    $ident = ($_POST['identity'] ?? '') === 'named' ? 'named' : 'anonymous';
    $part = in_array($_POST['participated'] ?? '', ['Yes', 'No', 'Not sure'], true) ? $_POST['participated'] : ''; $rec = in_array($_POST['recommend'] ?? '', ['Yes', 'Maybe', 'No'], true) ? $_POST['recommend'] : '';
    if ($cat === '') $errors[] = "Choose what your feedback is about.";
    if ($about === '' && $cat !== 'General' && $cat !== 'Website') $errors[] = "Tell us which programme, activity or initiative it is about.";
    if ($type === '') $errors[] = "Choose the type of feedback.";
    if ($rating < 1 || $rating > 5) $errors[] = "Give an overall rating from 1 to 5 stars.";
    if (mb_strlen($comment) < 10) $errors[] = "Please write your feedback (at least a sentence).";
    $name = $email = $phone = ''; $allowContact = 0; $allowPublish = 0;
    if ($ident === 'named') {
        $name = $p('name', 150); $email = strtolower($p('email')); $phone = $p('phone', 40); $allowContact = !empty($_POST['allow_contact']) ? 1 : 0;
        if ($name === '') $errors[] = "Enter your name, or choose to stay anonymous.";
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "That email address does not look right.";
        if ($allowContact && $email === '' && $phone === '') $errors[] = "Add an email or phone number if you would like us to contact you.";
    }
    $allowPublish = !empty($_POST['allow_publish']) ? 1 : 0;
    if (!$errors) {   // light spam control: five submissions per hour per visitor (kept as a keyed hash, never the address itself)
        $h = fb_ip_hash(); $c = $conn->prepare("SELECT COUNT(*) FROM feedback WHERE ip_hash=? AND created_at > (NOW() - INTERVAL 1 HOUR)"); $c->bind_param("s", $h); $c->execute();
        if ((int)$c->get_result()->fetch_row()[0] >= 5) $errors[] = "You have sent several messages in a short time. Please try again later.";
    }
    if (!$errors) {
        $h = fb_ip_hash();
        $s = $conn->prepare("INSERT INTO feedback (category, about, feedback_type, rating, participated, recommend, comment, suggestion, identity, name, email, phone, allow_contact, allow_publish, ip_hash) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("sssissssssssiis", $cat, $about, $type, $rating, $part, $rec, $comment, $sugg, $ident, $name, $email, $phone, $allowContact, $allowPublish, $h);
        if ($s->execute()) {
            $id = $conn->insert_id; $ref = 'FB-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); $conn->query("UPDATE feedback SET ref_no='$ref' WHERE id=$id");
            $mS = mem_settings($conn); $to = filter_var($S['fb_notify_email'], FILTER_VALIDATE_EMAIL) ? $S['fb_notify_email'] : ($mS['admin_notify_email'] ?? '');
            if ($to) { $url = mem_site_url() . '/admin/feedback.php?view=' . $id; mem_send($conn, null, 'feedback-admin', $to, "New feedback ($type, $rating/5): " . ($about ?: $cat), "New feedback received ($ref).\n\nAbout: " . ($about ?: $cat) . "\nType: $type\nRating: $rating/5\nFrom: " . ($ident === 'named' ? $name : 'Anonymous') . "\n\n" . mb_substr($comment, 0, 600) . "\n\nOpen it: $url", $url); }
            $_SESSION['fb_done'] = ['ref' => $ref, 'ident' => $ident, 'name' => $name]; $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            header("Location: feedback.php?done=1#form"); exit;
        }
        $errors[] = "We couldn't save your feedback just now. Please try again.";
    }
}

$pageTitle = cms_value('seo', 'feedback_title');
$pageDesc = cms_value('seo', 'feedback_desc');
$activeNav = "contact"; $canonicalPath = "feedback.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Feedback', cms_value('feedback', 'hero_title'), cms_value('feedback', 'hero_lede'), cms_img_raw('feedback', 'hero_image'), [['Contact', 'contact.php'], ['Feedback']], '<a href="#form" class="btn btn-gold">Give feedback</a>');
$ov = fn($k, $d = '') => $_POST[$k] ?? $d;
$selCat = $ov('category', $pre['category']); $selAbout = $ov('about', $pre['about']);
$aboutInList = false; foreach ($subjects as $list) if (in_array($selAbout, $list, true)) $aboutInList = true;
$showOther = $selAbout === '__other' || ($selAbout !== '' && !$aboutInList);
$public = $S['fb_show_public'] === '1' ? $conn->query("SELECT * FROM feedback WHERE is_public=1 ORDER BY created_at DESC LIMIT 9")->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php if (nufa_show('feedback', 'form')): ?><section class="section" id="form"><div class="container narrow">
<?php if (!$enabled): ?>
  <div class="placeholder-card"><span class="ph-icon"><?php echo nufa_icon('mail', 22); ?></span><h2>Feedback is closed for now</h2><p>We are not collecting feedback at the moment. You can still <a href="contact.php">contact us</a>.</p></div>
<?php elseif ($done): ?>
  <div class="form-card" style="text-align:center"><div class="eyebrow" style="justify-content:center">Thank you</div>
    <h2><?php echo $done['ident'] === 'named' && $done['name'] !== '' ? 'Thank you, ' . ui_e($done['name']) : 'Thank you for your feedback'; ?></h2>
    <p class="lede">Your feedback reference is</p><div class="reg-badge"><?php echo ui_e($done['ref']); ?></div>
    <p><?php echo $done['ident'] === 'anonymous' ? 'Your feedback was sent <b>anonymously</b> — we have not kept your name, email or phone number.' : 'The NUFA team will read it carefully.'; ?> Every piece of feedback is reviewed and used to improve our work.</p>
    <div class="actions" style="justify-content:center"><a href="feedback.php" class="btn btn-gold">Send more feedback</a><a href="index.php" class="btn btn-outline-dark">Back to the website</a></div></div>
<?php else: ?>
  <p class="lede" style="margin-bottom:22px"><?php echo ui_e($S['fb_intro']); ?></p>
  <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $er): ?><li><?php echo ui_e($er); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <?php require_once __DIR__ . "/includes/whatsapp.php"; if (nufa_wa_enabled($cfg0 = nufa_site_settings($conn))): ?><div class="info-card wa-card"><span class="wa-ic"><?php echo nufa_icon('whatsapp', 26); ?></span><div><b>Prefer to chat?</b><br><span class="muted">Send your feedback or a question on WhatsApp and the NUFA team will respond.</span></div><a class="btn btn-sm wa-btn" href="<?php echo ui_e(nufa_wa_url($cfg0, 'Hello NUFA, I would like to share some feedback about: ')); ?>" target="_blank" rel="noopener">Open WhatsApp</a></div><?php endif; ?>
  <p class="muted" style="margin-bottom:14px">Fields marked <span style="color:var(--err)">*</span> are required. It takes about two minutes.</p>
  <form method="post" class="apply-form" id="fbform"><?php echo form_open_fields(); ?>
    <fieldset class="apply-card"><legend><span>1</span> What is your feedback about?</legend>
      <div class="field"><label>Choose one <span class="req">*</span></label><div class="pill-grid"><?php foreach ($cats as $k => $l): ?><label class="pill"><input type="radio" name="category" value="<?php echo $k; ?>" <?php echo $selCat === $k ? 'checked' : ''; ?> required><span><?php echo ui_e($l); ?></span></label><?php endforeach; ?></div></div>
      <div class="field" id="about-wrap"><label for="f-about">Which one? <span class="req">*</span></label>
        <select id="f-about" name="about" onchange="document.getElementById('about-other').hidden=this.value!=='__other'"><option value="">Select…</option>
          <?php foreach ($subjects as $grp => $list): ?><optgroup label="<?php echo ui_e($grp); ?>"><?php foreach ($list as $it): ?><option<?php echo $selAbout === $it ? ' selected' : ''; ?>><?php echo ui_e($it); ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
          <option value="__other"<?php echo $showOther ? ' selected' : ''; ?>>Something else (type it below)</option></select></div>
      <div class="field" id="about-other" <?php echo $showOther ? '' : 'hidden'; ?>><label for="f-about_other">Describe the activity or initiative</label><input id="f-about_other" name="about_other" value="<?php echo ui_e($ov('about_other', $showOther && $selAbout !== '__other' ? $selAbout : '')); ?>"></div>
    </fieldset>
    <fieldset class="apply-card"><legend><span>2</span> Your experience</legend>
      <div class="field"><label>Overall rating <span class="req">*</span></label>
        <div class="star-input" role="radiogroup" aria-label="Overall rating"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo (int)$ov('rating') === $i ? 'checked' : ''; ?>><label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>"><svg width="34" height="34" viewBox="0 0 24 24"><path d="M12 2l3 6.500 7 .9-5.200 4.800 1.400 7L12 17.800 5.800 21.200l1.400-7L2 9.400l7-.9L12 2Z"/></svg><span class="sr-only"><?php echo $i; ?> stars</span></label><?php endfor; ?></div>
        <span class="hint">1 = very poor · 5 = excellent</span></div>
      <div class="field"><label>Type of feedback <span class="req">*</span></label><div class="pill-grid"><?php foreach ($types as $k => $l): ?><label class="pill"><input type="radio" name="feedback_type" value="<?php echo $k; ?>" <?php echo $ov('feedback_type') === $k ? 'checked' : ''; ?> required><span><?php echo ui_e($l); ?></span></label><?php endforeach; ?></div></div>
      <?php echo f_textarea('comment', 'Your feedback', ['required' => true, 'maxlength' => 3000, 'placeholder' => 'Tell us what happened, what you thought, and why.']); ?>
      <?php echo f_textarea('suggestion', 'What could we do better? (optional)', ['maxlength' => 2000]); ?>
      <div class="form-grid"><?php echo f_select('participated', 'Did you take part?', ['Yes', 'No', 'Not sure']); ?><?php echo f_select('recommend', 'Would you recommend it to others?', ['Yes', 'Maybe', 'No']); ?></div>
    </fieldset>
    <fieldset class="apply-card"><legend><span>3</span> Who is this from?</legend>
      <div class="field"><div class="pill-grid two"><label class="pill"><input type="radio" name="identity" value="anonymous" <?php echo $ov('identity', 'anonymous') !== 'named' ? 'checked' : ''; ?>><span><b>Anonymous</b><small>We keep no name, email or phone number.</small></span></label><label class="pill"><input type="radio" name="identity" value="named" <?php echo $ov('identity') === 'named' ? 'checked' : ''; ?>><span><b>Share my name</b><small>So we can follow up if needed.</small></span></label></div></div>
      <div id="named-fields" <?php echo $ov('identity') === 'named' ? '' : 'hidden'; ?>><div class="form-grid">
        <?php echo f_input('name', 'Your name', ['autocomplete' => 'name']); ?><?php echo f_input('email', 'Email (optional)', ['type' => 'email', 'autocomplete' => 'email']); ?><?php echo f_input('phone', 'Phone (optional)', ['type' => 'tel', 'autocomplete' => 'tel']); ?></div>
        <?php echo f_check('allow_contact', 'You may contact me about this feedback.', false); ?></div>
      <?php echo f_check('allow_publish', 'NUFA may share my comments publicly on this website (<span id="pub-note">without my name</span>).', false); ?>
      <button class="btn btn-gold btn-block" type="submit" style="margin-top:6px">Send my feedback</button>
      <p class="muted" style="text-align:center;margin-top:10px;font-size:.85rem">Please do not include sensitive personal details. Read our <a href="policy.php?p=privacy">privacy notice</a>.</p>
    </fieldset>
  </form>
  <script>
  (function(){var f=document.getElementById('fbform');if(!f)return;var n=document.getElementById('named-fields'),p=document.getElementById('pub-note');
    function upd(){var named=f.querySelector('input[name=identity][value=named]').checked;n.hidden=!named;p.textContent=named?'with my name':'without my name';}
    f.querySelectorAll('input[name=identity]').forEach(function(r){r.addEventListener('change',upd)});upd();
    var a=document.getElementById('f-about');if(a)document.getElementById('about-other').hidden=a.value!=='__other'&&!document.getElementById('f-about_other').value;})();
  </script>
<?php endif; ?>
</div></section><?php endif; ?>

<?php if (nufa_show('feedback', 'showcase') && $public): ?><section class="section section--soft" id="said"><div class="container">
  <div class="section-head center" data-reveal><div class="eyebrow">What people say</div><h2><?php echo cms_t('feedback', 'showcase_heading'); ?></h2><p><?php echo cms_t('feedback', 'showcase_intro'); ?></p></div>
  <div class="fb-grid" data-reveal-stagger><?php foreach ($public as $f): ?>
    <article class="fb-card"><?php echo fb_stars($f['rating']); ?><p>“<?php echo ui_e(mb_strimwidth($f['comment'], 0, 320, '…')); ?>”</p>
      <div class="fb-meta"><b><?php echo ui_e($f['public_name'] !== '' ? $f['public_name'] : ($f['identity'] === 'named' && $f['name'] !== '' && $f['allow_publish'] ? $f['name'] : 'Anonymous')); ?></b><?php if ($f['about']): ?><span><?php echo ui_e($f['about']); ?></span><?php endif; ?></div></article>
  <?php endforeach; ?></div></div></section><?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
