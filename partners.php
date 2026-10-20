<?php
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$orgTypes = nufa_list($cfg0 = nufa_site_settings($conn), 'list_org_types');
$interests = nufa_list($cfg0, 'list_partner_interests');
$errors = []; $sent = isset($_GET['sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($g = form_guard($conn, 'partner')) $errors[] = $g;
    $org = form_clean($_POST['organisation'] ?? '', 190); $person = form_clean($_POST['name'] ?? '', 150); $email = strtolower(form_clean($_POST['email'] ?? '', 190));
    $phone = form_clean($_POST['phone'] ?? '', 40); $otype = form_clean($_POST['org_type'] ?? '', 80); $interest = form_clean($_POST['interest'] ?? '', 120);
    $proposal = form_clean($_POST['proposal'] ?? '', 1500); $msg = form_clean($_POST['message'] ?? '', 3000);
    if ($org === '') $errors[] = "Please enter your organisation or your name.";
    if ($person === '') $errors[] = "Please enter a contact person.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (!in_array($otype, $orgTypes, true)) $errors[] = "Please choose an organisation type.";
    if (!in_array($interest, $interests, true)) $errors[] = "Please choose an area of interest.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted.";
    if (!$errors) {
        $ok = form_save($conn, 'partner', $interest, $person, $org, $email, $phone, $msg,
            ['Organisation type' => $otype, 'Proposed partnership' => $proposal, 'Sponsorship proposal requested' => !empty($_POST['want_proposal']) ? 'Yes' : 'No'], true);
        if ($ok) { header("Location: partners.php?sent=1#enquiry"); exit; }
        $errors[] = "We couldn't save your enquiry just now. Please try again or email us directly.";
    }
}

$pageTitle = cms_value('seo', 'partners_title');
$pageDesc  = cms_value('seo', 'partners_desc');
$activeNav = "partner";
$canonicalPath = "partners.php";
$ogImage = "assets/images/og/og-partners.jpg";
include __DIR__ . "/includes/header.php";

$current  = $conn->query("SELECT * FROM partners WHERE is_active=1 AND relationship='current' ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$previous = $conn->query("SELECT * FROM partners WHERE is_active=1 AND relationship='previous' ORDER BY sort_order ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$tile = function ($p) {
    $has = $p['logo'] !== '' && is_file(__DIR__ . '/assets/images/' . $p['logo']);
    $inner = $has ? '<img src="assets/images/' . ui_e($p['logo']) . '" alt="' . ui_e($p['name']) . '" loading="lazy">' : '<div><b>' . ui_e($p['name']) . '</b><span>' . ui_e($p['tier']) . '</span></div>';
    return '<div class="partner-tile" title="' . ui_e($p['name'] . ' — ' . $p['tier']) . '">' . ($p['website'] ? '<a href="' . ui_e($p['website']) . '" target="_blank" rel="noopener">' . $inner . '</a>' : $inner) . '</div>';
};
$oppIcons = ['trophy', 'book', 'camera', 'film', 'users', 'heart', 'coins', 'bulb', 'compass'];
$opps = [];
foreach (nufa_list(['x' => cms_value('partners', 'opps_list')], 'x') as $i => $label) $opps[] = [$oppIcons[$i % count($oppIcons)], $label];

echo ui_page_hero('Partner With NUFA', cms_value('partners', 'hero_heading'), cms_value('partners', 'hero_lede'), cms_img_raw('partners', 'hero_image'), [['Partner With Us']],
    '<a href="#enquiry" class="btn btn-gold">Partnership enquiry</a><a href="#enquiry" class="btn btn-outline">Request a sponsorship proposal</a>');
?>

<section class="section">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Partnership opportunities</div><h2><?php echo cms_t('partners', 'opps_heading'); ?></h2><p><?php echo cms_t('partners', 'why_body'); ?></p></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($opps as [$ic, $label]): ?><div class="value"><span class="icon-circle"><?php echo nufa_icon($ic, 24); ?></span><h3><?php echo ui_e($label); ?></h3></div><?php endforeach; ?>
    </div>
    <p class="center muted mt-2"><?php echo cms_t('partners', 'formats_note'); ?></p>
  </div>
</section>

<section class="section section--soft">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Our partners</div><h2><?php echo cms_t('partners', 'partners_heading'); ?></h2></div>
    <?php if ($current):
      $pOrder = nufa_list($cfg, 'list_partner_categories'); $pGroups = [];
      foreach ($current as $p) $pGroups[$p['category'] ?? ''][] = $p;
      uksort($pGroups, function ($a, $b) use ($pOrder) { $ia = array_search($a, $pOrder); $ib = array_search($b, $pOrder); return ($ia === false ? 999 : $ia) <=> ($ib === false ? 999 : $ib); });
      foreach ($pGroups as $gname => $gp): ?>
      <?php if (count($pGroups) > 1): ?><h3 class="center" style="margin:38px 0 16px;font-size:.95rem;letter-spacing:.14em;text-transform:uppercase;color:var(--gold-700)"><?php echo ui_e($gname !== '' ? $gname : 'Other partners'); ?></h3><?php endif; ?>
      <div class="partner-wall" data-reveal-stagger><?php foreach ($gp as $p) echo $tile($p); ?></div>
      <?php endforeach; ?>
    <?php else: ?><div class="empty-state"><h3>Current partners will be listed here</h3><p>Approved partner names and logos are published once confirmed. Interested in joining them? Use the form below.</p></div><?php endif; ?>
    <?php if ($previous): ?>
      <h3 style="margin:48px 0 18px" class="center">Previous supporters</h3>
      <div class="partner-wall"><?php foreach ($previous as $p) echo $tile($p); ?></div>
    <?php endif; ?>
  </div>
</section>

<section class="section" id="enquiry">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <aside class="member-side" data-reveal>
      <h3>Start a conversation</h3>
      <p style="color:var(--muted-on-dark)">Tell us about your organisation and what you would like to support. The NUFA team will respond with next steps and, if you request it, a sponsorship proposal.</p>
      <ol class="member-steps"><li><b>Send your enquiry</b><span>Share who you are and your area of interest.</span></li><li><b>We respond</b><span>A member of the team will contact you.</span></li><li><b>Shape the partnership</b><span>Agree a format that works for both sides.</span></li></ol>
      <p style="font-size:.86rem;color:var(--muted-on-dark)">Or contact us directly: <a href="mailto:<?php echo ui_e($cfg['site_email']); ?>" style="color:var(--gold)"><?php echo ui_e($cfg['site_email']); ?></a></p>
    </aside>
    <div class="form-card" data-reveal>
      <h2 style="font-size:1.6rem">Partnership enquiry</h2>
      <?php echo form_banner($sent, $errors, "Your partnership enquiry has been received. The NUFA team will be in touch."); ?>
      <?php if (!$sent): ?>
      <form method="post" action="partners.php#enquiry">
        <?php echo form_open_fields(); ?>
        <div class="form-grid">
          <?php echo f_input('organisation', 'Organisation or individual name', ['required' => true, 'maxlength' => 190]); ?>
          <?php echo f_input('name', 'Contact person', ['required' => true, 'autocomplete' => 'name']); ?>
          <?php echo f_input('email', 'Email', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
          <?php echo f_input('phone', 'Telephone', ['type' => 'tel', 'autocomplete' => 'tel']); ?>
          <?php echo f_select('org_type', 'Organisation type', $orgTypes, ['required' => true]); ?>
          <?php echo f_select('interest', 'Area of interest', $interests, ['required' => true]); ?>
        </div>
        <?php echo f_textarea('proposal', 'Proposed partnership', ['placeholder' => 'What kind of partnership do you have in mind?', 'maxlength' => 1500]); ?>
        <?php echo f_textarea('message', 'Message', ['maxlength' => 3000]); ?>
        <?php echo f_check('want_proposal', 'Please send me a sponsorship proposal.', false); ?>
        <?php echo f_check('consent', 'I consent to NUFA contacting me about this enquiry and have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?>
        <button class="btn btn-gold btn-block" type="submit">Send enquiry</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
