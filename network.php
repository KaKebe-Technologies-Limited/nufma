<?php
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/membership.php";


// Registered (approved) members per guild — counts only, no personal data.
mem_install($conn);
$guildStats = $conn->query("SELECT g.id, g.name, g.description, COUNT(m.id) AS total FROM guilds g LEFT JOIN members m ON m.guild_id = g.id AND m.status = 'approved' WHERE g.is_active = 1 GROUP BY g.id ORDER BY g.sort_order, g.name")->fetch_all(MYSQLI_ASSOC);
$memberTotal = (int)$conn->query("SELECT COUNT(*) FROM members WHERE status = 'approved'")->fetch_row()[0];
$guildMax = max(1, ...array_map(fn($g) => (int)$g['total'], $guildStats ?: [['total' => 1]]));
$roles = nufa_list(nufa_site_settings($conn), 'list_network_roles');
$errors = []; $sent = isset($_GET['sent']);
$fc = nufa_form_cfg('network');
if (!$fc['enabled'] && $_SERVER['REQUEST_METHOD'] === 'POST') $_SERVER['REQUEST_METHOD'] = 'GET';   // form switched off in the Page Editor
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

$pageTitle = cms_value('seo', 'network_title');
$pageDesc  = cms_value('seo', 'network_desc');
$activeNav = "network";
$canonicalPath = "network.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Industry Network', cms_value('network', 'hero_title'), cms_value('network', 'hero_lede'), cms_img_raw('network', 'hero_image'), [['Industry Network']],
    '<a href="#interest" class="btn btn-gold">Register your interest</a><a href="membership.php" class="btn btn-outline">Become a Member</a>');
$catIcons = ['film', 'compass', 'pen', 'users', 'camera', 'star', 'mic', 'bulb', 'book', 'handshake'];
$cats = [];
foreach (nufa_list(['x' => cms_value('network', 'cats_list')], 'x') as $i => $label) $cats[] = [$catIcons[$i % count($catIcons)], $label];
?>
<?php if (nufa_show('network', 'status')): ?><section class="section--tight"><div class="container"><div class="status-banner" role="note"><?php echo nufa_icon('compass', 26); ?><div><b>Planned feature:</b> <?php echo cms_t('network', 'status_text'); ?></div></div></div></section><?php endif; ?>

<?php if (nufa_show('network', 'numbers') && $guildStats): ?><section class="section guild-numbers" id="numbers">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">The network in numbers</div><h2>Registered members by guild</h2><p>Figures come straight from NUFA's membership register and count approved members only.</p></div>
    <div class="guild-total" data-reveal><b data-count="<?php echo $memberTotal; ?>"><?php echo number_format($memberTotal); ?></b><span>registered NUFA members</span></div>
    <div class="guild-grid" data-reveal-stagger>
      <?php foreach ($guildStats as $i => $g): $pct = $memberTotal ? round($g['total'] / $memberTotal * 100) : 0; ?>
      <div class="guild-card">
        <div class="guild-card__top"><span class="guild-card__n"><?php echo number_format((int)$g['total']); ?></span><span class="guild-card__pct"><?php echo $pct; ?>%</span></div>
        <h3><?php echo ui_e($g['name']); ?></h3>
        <?php if ($g['description'] !== ''): ?><p><?php echo ui_e($g['description']); ?></p><?php endif; ?>
        <div class="guild-bar" role="img" aria-label="<?php echo ui_e($g['name'] . ': ' . $g['total'] . ' of ' . $memberTotal . ' members'); ?>"><i style="width:<?php echo $g['total'] ? max(4, round($g['total'] / $guildMax * 100)) : 0; ?>%"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="center muted" style="margin-top:28px"><a href="membership.php" class="link-arrow">Join a guild</a></p>
  </div>
</section><?php endif; ?>
<?php if (nufa_show('network', 'categories')): ?><section class="section">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Who will be listed</div><h2><?php echo cms_t('network', 'who_heading'); ?></h2><p><?php echo cms_t('network', 'privacy_note'); ?></p></div>
    <div class="grid grid-4" data-reveal-stagger>
      <?php foreach ($cats as [$ic, $l]): ?><div class="value"><span class="icon-circle"><?php echo nufa_icon($ic, 24); ?></span><h3><?php echo ui_e($l); ?></h3></div><?php endforeach; ?>
    </div>
    <div class="grid grid-2 mt-2" style="gap:32px;margin-top:56px">
      <div data-reveal><h3>What each profile will include</h3><?php echo ui_list(cms_value('network', 'profile_fields')); ?></div>
      <div data-reveal><h3>How it will work</h3><ol class="steps"><li><b>Register your interest</b>Tell us about your craft and how you would like to be listed.</li><li><b>Verification</b>NUFA verifies each profile before anything is published.</li><li><b>You stay in control</b>Private contact details are shown only with your explicit consent.</li></ol></div>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('network', 'interest')): ?><section class="section section--soft" id="interest">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <div data-reveal><div class="eyebrow">Registration of interest</div><h2>Be part of the network</h2><p class="lede">Registering interest does not publish a profile and does not make you a NUFA member. We will contact you when the directory is ready to onboard profiles.</p>
      <a href="membership.php" class="link-arrow">Looking for membership instead?</a></div>
    <div class="form-card" data-reveal>
      <?php if ($fc['intro'] !== ''): ?><p class="muted" style="margin-bottom:18px"><?php echo ui_e($fc['intro']); ?></p><?php endif; ?>
      <?php echo form_banner($sent, $errors, $fc['success']); ?>
      <?php if (!$fc['enabled']): ?><div class="alert err" role="status"><?php echo ui_e($fc['closed']); ?></div><?php elseif (!$sent): ?>
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
        <button class="btn btn-gold btn-block" type="submit"><?php echo ui_e($fc['button']); ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section><?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
