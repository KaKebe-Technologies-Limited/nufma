<?php
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$errors = []; $sent = isset($_GET['sent']);
$fc = nufa_form_cfg('sacco');
if (!$fc['enabled'] && $_SERVER['REQUEST_METHOD'] === 'POST') $_SERVER['REQUEST_METHOD'] = 'GET';   // form switched off in the Page Editor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($g = form_guard($conn, 'sacco')) $errors[] = $g;
    $name = form_clean($_POST['name'] ?? '', 150); $email = strtolower(form_clean($_POST['email'] ?? '', 190)); $phone = form_clean($_POST['phone'] ?? '', 40);
    $district = form_clean($_POST['district'] ?? '', 100); $msg = form_clean($_POST['message'] ?? '', 2000);
    if ($name === '') $errors[] = "Please enter your name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (empty($_POST['ack'])) $errors[] = "Please confirm you understand the SACCO is proposed and not operational.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted with progress updates.";
    if (!$errors) {
        $ok = form_save($conn, 'sacco', 'Expression of interest', $name, '', $email, $phone, $msg, ['District / location' => $district], true);
        if ($ok) { header("Location: sacco.php?sent=1#interest"); exit; }
        $errors[] = "We couldn't save your submission just now. Please try again.";
    }
}

$pageTitle = cms_value('seo', 'sacco_title');
$pageDesc  = cms_value('seo', 'sacco_desc');
$activeNav = "sacco";
$canonicalPath = "sacco.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA SACCO · Proposed initiative', cms_value('sacco', 'hero_heading'), 'A proposed, member-focused initiative under development.', cms_img_raw('sacco', 'hero_image'), [['NUFA SACCO']],
    '<a href="#interest" class="btn btn-gold">Express interest</a><a href="#faq" class="btn btn-outline">Read the FAQs</a>');
?>
<?php if (nufa_show('sacco', 'status')): ?><section class="section--tight">
  <div class="container"><div class="status-banner" role="note"><?php echo nufa_icon('shield', 26); ?><div><b>Development status:</b> <?php echo cms_t('sacco', 'status_text'); ?></div></div></div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'background')): ?><section class="section" id="background">
  <div class="container split">
    <div data-reveal><div class="eyebrow">Introduction</div><h2>What is being explored</h2><div class="lede stack"><?php echo ui_paragraphs(cms_value('sacco', 'intro_body')); ?></div></div>
    <div data-reveal><h3>Background and rationale</h3><div class="stack"><?php echo cms_h('sacco', 'rationale'); ?></div></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'details')): ?><section class="section section--soft">
  <div class="container">
    <div class="grid grid-2" style="gap:clamp(24px,4vw,56px)">
      <div data-reveal><h3>Intended objectives</h3><div class="stack"><?php echo cms_h('sacco', 'objectives'); ?></div></div>
      <div data-reveal><h3>Potential areas of development</h3><div class="stack"><?php echo cms_h('sacco', 'areas'); ?></div></div>
      <div data-reveal><h3>Proposed benefits</h3><div class="stack"><?php echo cms_h('sacco', 'benefits'); ?></div></div>
      <div data-reveal><h3>Governance and regulatory considerations</h3><div class="stack"><?php echo cms_h('sacco', 'governance'); ?></div></div>
    </div>
    <div class="notice" style="margin-top:40px" data-reveal><span><?php echo nufa_icon('star', 22); ?></span><div><?php echo cms_h('sacco', 'development_status'); ?></div></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'faq')): ?><section class="section" id="faq">
  <div class="container" style="max-width:860px">
    <div class="section-head" data-reveal><div class="eyebrow">Questions</div><h2>Frequently asked questions</h2></div>
    <div class="accordion" data-reveal><?php echo cms_h('sacco', 'faq'); ?></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'interest')): ?><section class="section section--dark" id="interest">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <div data-reveal>
      <div class="eyebrow">Stay informed</div><h2>Expression of interest</h2>
      <p style="color:var(--muted-on-dark)">Register your interest to receive formal progress updates. This is not an application for membership, savings or a loan, and no money should be sent.</p>
      <div class="notice notice--dark"><span><?php echo nufa_icon('shield', 22); ?></span><div>NUFA is not collecting deposits or accepting loan applications. Be cautious of anyone who says otherwise.</div></div>
    </div>
    <div class="form-card" style="color:var(--ink)" data-reveal>
      <?php if ($fc['intro'] !== ''): ?><p class="muted" style="margin-bottom:18px"><?php echo ui_e($fc['intro']); ?></p><?php endif; ?>
      <?php echo form_banner($sent, $errors, $fc['success']); ?>
      <?php if (!$fc['enabled']): ?><div class="alert err" role="status"><?php echo ui_e($fc['closed']); ?></div><?php elseif (!$sent): ?>
      <form method="post" action="sacco.php#interest">
        <?php echo form_open_fields(); ?>
        <div class="form-grid">
          <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name']); ?>
          <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
          <?php echo f_input('phone', 'Telephone', ['type' => 'tel']); ?>
          <?php echo f_input('district', 'District or location'); ?>
        </div>
        <?php echo f_textarea('message', 'Anything you would like us to consider? (optional)', ['maxlength' => 2000]); ?>
        <?php echo f_check('ack', 'I understand the NUFA SACCO is proposed and under development, and that this form does not create any financial relationship.'); ?>
        <?php echo f_check('consent', 'I consent to NUFA contacting me with progress updates and have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?>
        <button class="btn btn-gold btn-block" type="submit"><?php echo ui_e($fc['button']); ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section><?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
