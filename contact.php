<?php
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$topics = ['General enquiry', 'Membership', 'Awards', 'Programmes', 'Partnership and sponsorship'];
$errors = []; $sent = isset($_GET['sent']);
if (!isset($_POST['topic']) && isset($_GET['topic'])) {
    foreach ($topics as $t) if (stripos($t, (string)$_GET['topic']) === 0) $_POST['topic_prefill'] = $t;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    if ($g = form_guard($conn, 'contact')) $errors[] = $g;
    $name = form_clean($_POST['name'] ?? '', 150); $email = strtolower(form_clean($_POST['email'] ?? '', 190));
    $phone = form_clean($_POST['phone'] ?? '', 40); $topic = form_clean($_POST['topic'] ?? '', 80); $msg = form_clean($_POST['message'] ?? '', 3000);
    if ($name === '') $errors[] = "Please enter your name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (!in_array($topic, $topics, true)) $errors[] = "Please choose what your enquiry is about.";
    if (mb_strlen($msg) < 10) $errors[] = "Please write a message of at least 10 characters.";
    if (empty($_POST['consent'])) $errors[] = "Please confirm you have read the privacy notice.";
    if (!$errors) {
        $ref = form_clean($_GET['ref'] ?? '', 80);
        $ok = form_save($conn, 'contact', $topic, $name, '', $email, $phone, $msg, $ref ? ['Programme reference' => $ref] : [], true);
        if ($ok) { header("Location: contact.php?sent=1#form"); exit; }
        $errors[] = "We couldn't save your message just now. Please try again or email us directly.";
    }
}

$pageTitle = "Contact NUFA — Northern Uganda Filmmakers Association, Gulu";
$pageDesc  = "Contact the Northern Uganda Filmmakers Association in Gulu City for membership, awards, programme and partnership enquiries.";
$activeNav = "contact";
$canonicalPath = "contact.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Contact', "Let's talk film", cms_value('contact', 'hero_lede'), cms_img_raw('contact', 'hero_image'), [['Contact']]);
$defaultTopic = $_POST['topic'] ?? ($_POST['topic_prefill'] ?? '');
?>
<section class="section">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <aside class="member-side" data-reveal>
      <h3>Northern Uganda Filmmakers Association (NUFA)</h3>
      <ul style="display:grid;gap:20px;margin-top:24px">
        <li style="display:flex;gap:14px"><span class="icon-circle"><?php echo nufa_icon('pin', 22); ?></span><span><b style="display:block">Visit</b><?php echo ui_e($cfg['site_address']); ?></span></li>
        <li style="display:flex;gap:14px"><span class="icon-circle"><?php echo nufa_icon('mail', 22); ?></span><span><b style="display:block">Email</b><a href="mailto:<?php echo ui_e($cfg['site_email']); ?>" style="color:var(--gold)"><?php echo ui_e($cfg['site_email']); ?></a></span></li>
        <li style="display:flex;gap:14px"><span class="icon-circle"><?php echo nufa_icon('phone', 22); ?></span><span><b style="display:block">Telephone</b><a href="tel:<?php echo nufa_tel_href($cfg['site_phone']); ?>"><?php echo ui_e($cfg['site_phone']); ?></a><?php if ($cfg['site_phone2'] !== ''): ?> / <a href="tel:<?php echo nufa_tel_href($cfg['site_phone2']); ?>"><?php echo ui_e($cfg['site_phone2']); ?></a><?php endif; ?></span></li>
        <li style="display:flex;gap:14px"><span class="icon-circle"><?php echo nufa_icon('globe', 22); ?></span><span><b style="display:block">Website</b><a href="<?php echo ui_e($cfg['site_website']); ?>" style="color:var(--gold)"><?php echo ui_e(preg_replace('#^https?://#', '', rtrim($cfg['site_website'], '/'))); ?></a></span></li>
        <?php $hours = trim(cms_value('contact', 'hours')); if ($hours !== ''): ?><li style="display:flex;gap:14px"><span class="icon-circle"><?php echo nufa_icon('calendar', 22); ?></span><span><b style="display:block">Office hours</b><?php echo ui_e($hours); ?></span></li><?php endif; ?>
      </ul>
      <h4 style="color:var(--gold);font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;margin:30px 0 4px">Follow NUFA</h4>
      <div class="social-row" style="margin-top:8px">
        <?php foreach (['x_url' => ['X', 'x'], 'facebook_url' => ['Facebook', 'facebook'], 'instagram_url' => ['Instagram', 'instagram'], 'linkedin_url' => ['LinkedIn', 'linkedin'], 'youtube_url' => ['YouTube', 'youtube']] as $k => [$l, $ic]) if ($cfg[$k] !== ''): ?>
          <a href="<?php echo ui_e($cfg[$k]); ?>" target="_blank" rel="noopener" aria-label="<?php echo $l; ?>"><?php echo nufa_icon($ic, 16); ?></a><?php endif; ?>
      </div>
    </aside>

    <div class="form-card" id="form" data-reveal>
      <h2 style="font-size:1.6rem">Send us a message</h2>
      <p class="muted">We aim to respond as soon as we can. Fields marked <span class="req" style="color:var(--err)">*</span> are required.</p>
      <?php echo form_banner($sent, $errors, "Your message has been sent. The NUFA team will get back to you."); ?>
      <?php if (!$sent): ?>
      <form method="post" action="contact.php<?php echo isset($_GET['ref']) ? '?ref=' . urlencode($_GET['ref']) : ''; ?>#form">
        <?php echo form_open_fields(); ?>
        <div class="form-grid">
          <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name', 'maxlength' => 150]); ?>
          <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
          <?php echo f_input('phone', 'Telephone', ['type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '+256…']); ?>
          <?php echo f_select('topic', 'My enquiry is about', $topics, ['required' => true, 'default' => $defaultTopic]); ?>
        </div>
        <?php echo f_textarea('message', 'Message', ['required' => true, 'placeholder' => 'How can NUFA help?']); ?>
        <?php echo f_check('consent', 'I have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a> and agree to NUFA contacting me about this enquiry.'); ?>
        <button class="btn btn-gold btn-block" type="submit">Send message</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
