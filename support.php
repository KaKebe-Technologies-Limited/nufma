<?php
require_once __DIR__ . "/includes/support.php";   // also loads membership, schema, settings
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$S = sup_settings($conn);
$kinds = sup_kinds();
$fc = nufa_form_cfg('support');
$cfg0 = nufa_site_settings($conn);

// programmes people can support
$programmes = $conn->query("SELECT * FROM programmes WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$bySlug = []; foreach ($programmes as $p) $bySlug[$p['slug']] = $p;

$kind = $_REQUEST['type'] ?? 'donate';
if (!isset($kinds[$kind])) $kind = 'donate';
$progSlug = $_REQUEST['programme'] ?? '';
$prog = $bySlug[$progSlug] ?? null;
$kindOpen = function ($k, $p = null) use ($S) { return ($S["sup_enable_$k"] ?? '1') === '1' && ($p === null || (int)($p["allow_$k"] ?? 1) === 1); };

$errors = [];
$done = null;
if (!empty($_GET['done']) && !empty($_SESSION['sup_done']) && (int)$_GET['done'] === (int)$_SESSION['sup_done']['id']) $done = $_SESSION['sup_done'];

$currencies = sup_lines($S['sup_currencies']) ?: ['UGX'];
$methods = sup_active_methods($conn);
$payG = pay_groups($conn, true);
$tiers = sup_tiers($S);
$suggested = sup_lines($S['sup_suggested_amounts']);
$areas = sup_lines($S['sup_volunteer_areas']);
$availability = sup_lines($S['sup_availability']);
$orgTypes = nufa_list($cfg0, 'list_org_types');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $fc['enabled']) {
    if ($g = form_guard($conn, 'support', 'engagements')) $errors[] = $g;
    $kind = isset($kinds[$_POST['type'] ?? '']) ? $_POST['type'] : 'donate';
    $prog = $bySlug[$_POST['programme'] ?? ''] ?? null;
    if (!$kindOpen($kind, $prog)) $errors[] = "This way of supporting is not open " . ($prog ? "for the selected programme." : "right now.");

    $name = form_clean($_POST['name'] ?? '', 150); $org = form_clean($_POST['organisation'] ?? '', 190);
    $email = strtolower(form_clean($_POST['email'] ?? '', 190)); $phone = form_clean($_POST['phone'] ?? '', 40);
    $location = form_clean($_POST['location'] ?? '', 150); $message = form_clean($_POST['message'] ?? '', 3000);
    if ($name === '') $errors[] = "Please enter your name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (empty($_POST['consent'])) $errors[] = "Please confirm the declaration at the bottom of the form.";

    $amount = null; $currency = ''; $frequency = ''; $method = ''; $payRef = ''; $proof = ''; $details = []; $anon = !empty($_POST['anonymous']) ? 1 : 0;
    if (in_array($kind, ['donate', 'sponsor'], true)) {
        $currency = in_array($_POST['currency'] ?? '', $currencies, true) ? $_POST['currency'] : $S['sup_default_currency'];
        $amount = sup_amount($_POST['amount'] ?? '');
        $tier = form_clean($_POST['tier'] ?? '', 120);
        if ($kind === 'donate') {
            if ($amount === null) $errors[] = "Enter the amount you would like to give.";
            elseif ($amount < (float)$S['sup_min_amount']) $errors[] = "The minimum gift is " . sup_money($S['sup_min_amount'], $currency) . ".";
            $frequency = in_array($_POST['frequency'] ?? '', ['One-off', 'Monthly pledge', 'Quarterly pledge', 'Yearly pledge'], true) ? $_POST['frequency'] : 'One-off';
        } else {
            if ($org === '' && $name === '') $errors[] = "Enter the sponsoring organisation or your name.";
            if ($tier !== '') $details['Sponsorship level'] = $tier;
            if ($amount === null && $tier === '') $errors[] = "Choose a sponsorship level or enter the amount you would like to sponsor with.";
            $what = form_clean($_POST['sponsor_focus'] ?? '', 500); if ($what) $details['What to sponsor'] = $what;
            if (!empty($_POST['recognition'])) $details['Wants public recognition'] = 'Yes';
        }
        $methodKey = form_clean($_POST['method'] ?? '', 40);
        if ($payG && !isset($payG[$methodKey])) $errors[] = "Choose how you plan to pay: Bank or Mobile Money.";
        $method = isset($payG[$methodKey]) ? pay_label($methodKey) : '';
        $paid = ($_POST['paid'] ?? 'later') === 'paid';
        if ($paid) {
            $payRef = form_clean($_POST['payment_ref'] ?? '', 150);
            if ($payRef === '') $errors[] = "Enter the transaction reference of your payment, or choose “I will pay after approval”.";
            if (!empty($_FILES['payment_proof']['name'])) {
                sup_storage_ready();
                [$proof, $e] = mem_store_upload($_FILES['payment_proof'], MEM_PROOF_TYPES, 5 * 1048576, 'support', SUP_STORAGE_DIR);
                if ($e) $errors[] = "Proof of payment: " . $e;
            } elseif ($S['sup_require_proof'] === '1') $errors[] = "Please attach your proof of payment.";
        }
    } elseif ($kind === 'volunteer') {
        $picked = array_values(array_intersect($areas, (array)($_POST['areas'] ?? [])));
        if (!$picked) $errors[] = "Choose at least one area you can help with.";
        $details['Areas'] = implode(', ', $picked);
        $av = form_clean($_POST['availability'] ?? '', 120); if ($av) $details['Availability'] = $av;
        $ex = form_clean($_POST['experience'] ?? '', 800); if ($ex) $details['Skills & experience'] = $ex;
    } else { // partner
        if ($org === '') $errors[] = "Enter your organisation's name.";
        $ot = form_clean($_POST['org_type'] ?? '', 80); if ($ot) $details['Organisation type'] = $ot;
        $idea = form_clean($_POST['idea'] ?? '', 1500); if ($idea === '') $errors[] = "Describe the partnership you have in mind.";
        $details['Proposed partnership'] = $idea;
    }

    if (!$errors) {
        $detailsText = implode("\n", array_map(fn($k, $v) => "$k: $v", array_keys($details), $details));
        $pid = $prog ? (int)$prog['id'] : null; $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $cons = 1;
        $st = $conn->prepare("INSERT INTO engagements (kind, programme_id, name, organisation, email, phone, location, amount, currency, frequency, method, payment_ref, payment_proof, message, details, anonymous, consent, ip)
                              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param("sisssssdsssssssiis", $kind, $pid, $name, $org, $email, $phone, $location, $amount, $currency, $frequency, $method, $payRef, $proof, $message, $detailsText, $anon, $cons, $ip);
        if ($st->execute()) {
            $id = $conn->insert_id;
            $ref = $kinds[$kind]['prefix'] . '-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
            $conn->query("UPDATE engagements SET ref_no='" . $conn->real_escape_string($ref) . "' WHERE id=$id");
            $e = $conn->query("SELECT * FROM engagements WHERE id=$id")->fetch_assoc();
            sup_notify($conn, $e, 'received');
            sup_notify_admin($conn, $e);
            $_SESSION['sup_done'] = ['id' => $id, 'ref' => $ref, 'kind' => $kind, 'method' => $method, 'prog' => $prog['title'] ?? '', 'payable' => in_array($kind, ['donate', 'sponsor'], true), 'paid' => !empty($payRef)];
            $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            header("Location: support.php?done=$id"); exit;
        }
        $errors[] = "We couldn't save your request just now. Please try again.";
        if ($proof) @unlink(SUP_STORAGE_DIR . $proof);
    } elseif ($proof) @unlink(SUP_STORAGE_DIR . $proof);
}

$pageTitle = cms_value('seo', 'support_title');
$pageDesc  = cms_value('seo', 'support_desc');
$activeNav = "programmes";
$canonicalPath = "support.php";
if ($done) $noindex = true;
include __DIR__ . "/includes/header.php";
$heroLede = $prog ? 'You are supporting: ' . $prog['title'] . '. ' . cms_value('support', 'hero_lede') : cms_value('support', 'hero_lede');
echo ui_page_hero('Support a programme', cms_value('support', 'hero_title'), $heroLede, $prog && $prog['image'] ? $prog['image'] : cms_img_raw('support', 'hero_image'), [['Programmes', 'programmes.php'], ['Support']]);
$post = fn($k, $d = '') => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES, 'UTF-8');
$typeUrl = fn($k) => 'support.php?' . http_build_query(array_filter(['type' => $k, 'programme' => $prog['slug'] ?? '']));
?>

<?php if ($done): ?>
<section class="section">
  <div class="container" style="max-width:820px">
    <div class="member-done">
      <div class="member-done-ico" aria-hidden="true">✓</div>
      <h2 style="font-size:1.8rem"><?php echo cms_t('support', 'thanks_title'); ?></h2>
      <p><?php echo cms_t('support', 'thanks_text'); ?></p>
      <p style="margin-top:18px"><span class="chip chip--dark" style="font-size:.9rem;padding:9px 16px">Reference: <?php echo ui_e($done['ref']); ?></span></p>
    </div>
    <?php if ($done['payable']): ?>
    <div class="form-card" style="margin-top:28px">
      <h3>How to pay</h3>
      <?php $__rev = ['Bank' => 'bank', 'Mobile Money' => 'momo', 'Pay online' => 'online']; echo pay_block($conn, $__rev[$done['method']] ?? '', ['ref' => $done['ref']], true, 'Your payment details'); ?>
      <?php if ($done['paid']): ?><p class="notice"><span><?php echo nufa_icon('shield', 20); ?></span><span>We have your payment reference and will verify it before confirming.</span></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <p class="center" style="margin-top:28px"><a href="programmes.php" class="btn btn-outline-dark">Back to programmes</a></p>
  </div>
</section>

<?php else: ?>
<section class="section">
  <div class="container">
    <div class="support-types" role="tablist" aria-label="Way to support">
      <?php foreach ($kinds as $k => $kd): $open = $kindOpen($k, $prog); ?>
        <a href="<?php echo $open ? ui_e($typeUrl($k)) : '#'; ?>" class="support-type <?php echo $kind === $k ? 'active' : ''; ?> <?php echo $open ? '' : 'is-off'; ?>" <?php echo $open ? '' : 'aria-disabled="true" tabindex="-1"'; ?>>
          <span class="icon-circle"><?php echo nufa_icon($kd['icon'], 22); ?></span>
          <b><?php echo ui_e($kd['label']); ?></b>
          <span><?php echo $open ? cms_t('support', $k . '_blurb') : 'Not open for this programme'; ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="member-grid" style="grid-template-columns:.8fr 1.4fr;margin-top:36px">
      <aside class="member-side">
        <?php if ($prog): ?>
          <h3><?php echo ui_e($prog['title']); ?></h3>
          <?php if ($prog['image']): ?><img src="assets/images/<?php echo ui_e($prog['image']); ?>" alt="" style="margin:14px 0;aspect-ratio:3/2;object-fit:cover;width:100%"><?php endif; ?>
          <p style="color:var(--muted-on-dark);font-size:.92rem"><?php echo ui_e($prog['summary']); ?></p>
          <?php if (trim((string)$prog['support_text']) !== ''): ?><h4 style="color:var(--gold);font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;margin:20px 0 8px">What support enables</h4><p style="color:var(--muted-on-dark);font-size:.92rem"><?php echo ui_e($prog['support_text']); ?></p><?php endif; ?>
          <p><a href="programme.php?slug=<?php echo ui_e($prog['slug']); ?>" class="link-arrow" style="color:var(--white)">Read about this programme</a></p>
        <?php else: ?>
          <h3>Choose a programme</h3>
          <p style="color:var(--muted-on-dark);font-size:.92rem">Pick a programme in the form, or give where it is needed most. Each programme page explains what your support makes possible.</p>
          <ul style="display:grid;gap:8px;margin-top:14px"><?php foreach ($programmes as $p): ?><li><a href="support.php?<?php echo ui_e(http_build_query(['type' => $kind, 'programme' => $p['slug']])); ?>" style="color:var(--white)"><?php echo ui_e($p['title']); ?></a></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <p style="font-size:.82rem;color:var(--muted-on-dark);margin-top:22px">Questions? <a href="contact.php?topic=Partnership" style="color:var(--gold)">Contact the NUFA team</a>.</p>
      </aside>

      <div class="form-card">
        <h2 style="font-size:1.6rem"><?php echo ui_e($kinds[$kind]['label']); ?><?php echo $prog ? ' — ' . ui_e($prog['title']) : ''; ?></h2>
        <?php if ($fc['intro'] !== ''): ?><p class="muted"><?php echo ui_e($fc['intro']); ?></p><?php endif; ?>
        <?php if ($errors): ?><div class="alert err" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $e) echo '<li>' . ui_e($e) . '</li>'; ?></ul></div><?php endif; ?>

        <?php if (!$fc['enabled'] || !$kindOpen($kind, $prog)): ?>
          <div class="alert err" role="status"><?php echo ui_e($fc['enabled'] ? 'This way of supporting is not open ' . ($prog ? 'for this programme' : 'right now') . '. Please choose another option above or contact us.' : $fc['closed']); ?></div>
        <?php else: ?>
        <form method="post" action="<?php echo ui_e($typeUrl($kind)); ?>" enctype="multipart/form-data">
          <?php echo form_open_fields(); ?>
          <input type="hidden" name="type" value="<?php echo ui_e($kind); ?>">

          <div class="field full"><label for="f-programme"><?php echo cms_t('support', 'choose_programme'); ?></label>
            <select id="f-programme" name="programme"><option value=""><?php echo cms_t('support', 'general_option'); ?></option>
              <?php foreach ($programmes as $p): if (!$kindOpen($kind, $p)) continue; ?><option value="<?php echo ui_e($p['slug']); ?>" <?php echo ($prog['slug'] ?? '') === $p['slug'] ? 'selected' : ''; ?>><?php echo ui_e($p['title']); ?></option><?php endforeach; ?></select></div>

          <h3 class="form-section">Your details</h3>
          <div class="form-grid">
            <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name']); ?>
            <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
            <?php echo f_input('phone', 'Telephone', ['type' => 'tel', 'autocomplete' => 'tel']); ?>
            <?php echo f_input('location', 'Country / district'); ?>
            <?php if (in_array($kind, ['sponsor', 'partner'], true)): ?><?php echo f_input('organisation', 'Organisation' . ($kind === 'sponsor' ? ' (if sponsoring as an organisation)' : ''), ['required' => $kind === 'partner', 'full' => true, 'autocomplete' => 'organization']); ?><?php endif; ?>
          </div>

          <?php if (in_array($kind, ['donate', 'sponsor'], true)): ?>
          <h3 class="form-section"><?php echo $kind === 'donate' ? 'Your gift' : 'Your sponsorship'; ?></h3>
          <?php if ($kind === 'sponsor' && $tiers): ?>
            <div class="field full"><label>Sponsorship level</label>
              <div class="tier-grid">
              <?php foreach ($tiers as $t): ?><label class="tier"><input type="radio" name="tier" value="<?php echo ui_e($t['name']); ?>" <?php echo ($_POST['tier'] ?? '') === $t['name'] ? 'checked' : ''; ?>><span><b><?php echo ui_e($t['name']); ?></b><?php echo $t['amount'] !== '' ? '<em>' . ui_e($t['amount']) . '</em>' : ''; ?><small><?php echo ui_e($t['desc']); ?></small></span></label><?php endforeach; ?>
              </div></div>
          <?php endif; ?>
          <div class="form-grid">
            <div class="field"><label for="f-amount"><?php echo $kind === 'donate' ? 'Amount' : 'Amount (optional if you chose a level)'; ?><?php echo $kind === 'donate' ? ' <span class="req">*</span>' : ''; ?></label>
              <input id="f-amount" name="amount" inputmode="decimal" value="<?php echo $post('amount'); ?>" <?php echo $kind === 'donate' ? 'required' : ''; ?> placeholder="e.g. 100000">
              <?php if ($suggested): ?><span class="amount-chips"><?php foreach ($suggested as $a): ?><button type="button" onclick="document.getElementById('f-amount').value='<?php echo ui_e($a); ?>'"><?php echo ui_e($a); ?></button><?php endforeach; ?></span><?php endif; ?></div>
            <div class="field"><label for="f-currency">Currency</label><select id="f-currency" name="currency"><?php foreach ($currencies as $c): ?><option <?php echo (($_POST['currency'] ?? $S['sup_default_currency']) === $c) ? 'selected' : ''; ?>><?php echo ui_e($c); ?></option><?php endforeach; ?></select></div>
            <?php if ($kind === 'donate'): ?><?php echo f_select('frequency', 'How often', ['One-off', 'Monthly pledge', 'Quarterly pledge', 'Yearly pledge'], ['default' => 'One-off']); ?><?php endif; ?>
          </div>
          <?php if ($kind === 'sponsor'): ?>
            <?php echo f_input('sponsor_focus', 'What would you like to sponsor? (optional)', ['full' => true, 'placeholder' => 'e.g. a workshop series, equipment, a screening tour']); ?>
            <?php echo f_check('recognition', 'I would like public recognition as a sponsor (to be agreed with NUFA).', false); ?>
          <?php else: ?>
            <?php echo f_check('anonymous', 'I would like my gift to remain anonymous.', false); ?>
          <?php endif; ?>

          <h3 class="form-section">Payment</h3>
          <div class="field full"><label>How do you plan to pay?<?php echo $payG ? ' <span class="req">*</span>' : ''; ?></label><?php echo pay_picker($conn, 'method', $_POST['method'] ?? '', ['ref' => 'The reference shown after you submit'], true); ?></div>
          <div class="field full"><label for="f-paid">Payment status</label>
            <select id="f-paid" name="paid" onchange="document.getElementById('paidBox').style.display=this.value==='paid'?'block':'none'">
              <option value="later" <?php echo ($_POST['paid'] ?? 'later') === 'later' ? 'selected' : ''; ?>>I will pay after my request is accepted</option>
              <option value="paid" <?php echo ($_POST['paid'] ?? '') === 'paid' ? 'selected' : ''; ?>>I have already paid</option>
            </select></div>
          <div id="paidBox" style="display:<?php echo ($_POST['paid'] ?? '') === 'paid' ? 'block' : 'none'; ?>">
            <div class="form-grid">
              <?php echo f_input('payment_ref', 'Transaction reference'); ?>
              <div class="field"><label for="f-proof">Proof of payment<?php echo $S['sup_require_proof'] === '1' ? ' <span class="req">*</span>' : ' (optional)'; ?></label><input id="f-proof" type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf"><span class="hint">JPG, PNG, WebP or PDF, max 5 MB. Stored privately.</span></div>
            </div>
          </div>
          <?php elseif ($kind === 'volunteer'): ?>
          <h3 class="form-section">How you can help</h3>
          <div class="field full"><label>Areas you can help with <span class="req">*</span></label>
            <div class="check-grid"><?php foreach ($areas as $a): ?><label class="check" style="margin:0"><input type="checkbox" name="areas[]" value="<?php echo ui_e($a); ?>" <?php echo in_array($a, (array)($_POST['areas'] ?? []), true) ? 'checked' : ''; ?>><span><?php echo ui_e($a); ?></span></label><?php endforeach; ?></div></div>
          <?php echo f_select('availability', 'Availability', $availability, ['full' => true]); ?>
          <?php echo f_textarea('experience', 'Skills and experience', ['placeholder' => 'Tell us about your skills in film, training, events or community work.', 'maxlength' => 800]); ?>
          <?php else: ?>
          <h3 class="form-section">The partnership</h3>
          <?php echo f_select('org_type', 'Organisation type', $orgTypes, ['full' => true]); ?>
          <?php echo f_textarea('idea', 'Proposed partnership', ['required' => true, 'placeholder' => 'What would you like to do together with NUFA on this programme?', 'maxlength' => 1500]); ?>
          <?php endif; ?>

          <?php echo f_textarea('message', 'Anything else you would like us to know (optional)', ['maxlength' => 3000]); ?>
          <?php echo f_check('consent', ui_e($S['sup_terms']) . ' I have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?>
          <button class="btn btn-gold btn-block" type="submit"><?php echo $fc['button'] === 'Submit' ? ui_e($kinds[$kind]['label'] . ' — submit request') : ui_e($fc['button']); ?></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
