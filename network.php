<?php
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$roles = ['Film production company', 'Director or producer', 'Screenwriter', 'Actor or casting professional', 'Cinematographer', 'Editor or post-production specialist', 'Sound professional',
          'Production designer', 'Film trainer or mentor', 'Industry partner', 'Other'];
$errors = []; $sent = isset($_GET['sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($g = form_guard($conn, 'network')) $errors[] = $g;
    $name = form_clean($_POST['name'] ?? '', 150); $email = strtolower(form_clean($_POST['email'] ?? '', 190)); $phone = form_clean($_POST['phone'] ?? '', 40);
    $role = form_clean($_POST['role'] ?? '', 80); $loc = form_clean($_POST['location'] ?? '', 120); $profile = form_clean($_POST['message'] ?? '', 2000);
    $portfolio = form_clean($_POST['portfolio'] ?? '', 255); $pref = form_clean($_POST['contact_pref'] ?? '', 80);
    if ($name === '') $errors[] = "Please enter your name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (!in_array($role, $roles, true)) $errors[] = "Please choose your professional role.";
    if ($portfolio !== '' && !filter_var($portfolio, FILTER_VALIDATE_URL)) $errors[] = "The portfolio link must be a full web address starting with https://";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted and confirm you have read the privacy notice.";
    if (!$errors) {
        $ok = form_save($conn, 'network', $role, $name, '', $email, $phone, $profile, ['Location' => $loc, 'Portfolio' => $portfolio, 'Preferred contact' => $pref], true);
        if ($ok) { header("Location: network.php?sent=1#interest"); exit; }
        $errors[] = "We couldn't save your submission just now. Please try again.";
    }
}

$pageTitle = "Industry Network — Connecting Filmmakers in Northern Uganda | NUFA";
$pageDesc  = "A planned filmmaker directory and industry network connecting directors, producers, writers, actors, crews and partners across Northern Uganda. Register your interest.";
$activeNav = "network";
$canonicalPath = "network.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Industry Network', 'Connect with the people behind Northern Uganda’s film industry', cms_value('network', 'hero_lede'), cms_img_raw('network', 'hero_image'), [['Industry Network']],
    '<a href="#interest" class="btn btn-gold">Register your interest</a><a href="membership.php" class="btn btn-outline">Become a Member</a>');
$cats = [['film', 'Film production companies'], ['compass', 'Directors and producers'], ['pen', 'Screenwriters'], ['users', 'Actors and casting professionals'], ['camera', 'Cinematographers'],
         ['star', 'Editors and post-production specialists'], ['mic', 'Sound professionals'], ['bulb', 'Production designers'], ['book', 'Film trainers and mentors'], ['handshake', 'Industry partners']];
?>
<section class="section--tight"><div class="container"><div class="status-banner" role="note"><?php echo nufa_icon('compass', 26); ?><div><b>Planned feature:</b> <?php echo cms_t('network', 'status_text'); ?></div></div></div></section>

<section class="section">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Who will be listed</div><h2>Professionals across the film value chain</h2><p><?php echo cms_t('network', 'privacy_note'); ?></p></div>
    <div class="grid grid-4" data-reveal-stagger>
      <?php foreach ($cats as [$ic, $l]): ?><div class="value"><span class="icon-circle"><?php echo nufa_icon($ic, 24); ?></span><h3><?php echo ui_e($l); ?></h3></div><?php endforeach; ?>
    </div>
    <div class="grid grid-2 mt-2" style="gap:32px;margin-top:56px">
      <div data-reveal><h3>What each profile will include</h3><ul class="check-list"><li>Name and professional role</li><li>Biography</li><li>Location</li><li>Portfolio or showreel</li><li>Contact preferences</li><li>An approved photograph</li></ul></div>
      <div data-reveal><h3>How it will work</h3><ol class="steps"><li><b>Register your interest</b>Tell us about your craft and how you would like to be listed.</li><li><b>Verification</b>NUFA verifies each profile before anything is published.</li><li><b>You stay in control</b>Private contact details are shown only with your explicit consent.</li></ol></div>
    </div>
  </div>
</section>

<section class="section section--soft" id="interest">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <div data-reveal><div class="eyebrow">Registration of interest</div><h2>Be part of the network</h2><p class="lede">Registering interest does not publish a profile and does not make you a NUFA member. We will contact you when the directory is ready to onboard profiles.</p>
      <a href="membership.php" class="link-arrow">Looking for membership instead?</a></div>
    <div class="form-card" data-reveal>
      <?php echo form_banner($sent, $errors, "Your interest has been recorded. We will contact you when the network opens."); ?>
      <?php if (!$sent): ?>
      <form method="post" action="network.php#interest">
        <?php echo form_open_fields(); ?>
        <div class="form-grid">
          <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name']); ?>
          <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
          <?php echo f_input('phone', 'Telephone', ['type' => 'tel']); ?>
          <?php echo f_select('role', 'Professional role', $roles, ['required' => true]); ?>
          <?php echo f_input('location', 'District or location'); ?>
          <?php echo f_input('portfolio', 'Portfolio or showreel link', ['type' => 'url', 'placeholder' => 'https://', 'hint' => 'Optional']); ?>
          <?php echo f_select('contact_pref', 'How should we contact you?', ['Email', 'Phone call', 'WhatsApp', 'Any of these']); ?>
        </div>
        <?php echo f_textarea('message', 'Short professional profile', ['placeholder' => 'Tell us about your work in film.', 'maxlength' => 2000]); ?>
        <?php echo f_check('consent', 'I consent to NUFA contacting me about the industry network and have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?>
        <button class="btn btn-gold btn-block" type="submit">Register interest</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
