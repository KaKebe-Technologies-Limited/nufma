<?php
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$cfg0 = nufa_site_settings($conn);
$status = in_array($cfg0['sacco_status'], ['proposed', 'in_development', 'ongoing', 'operational'], true) ? $cfg0['sacco_status'] : 'ongoing';
$statusLabel = ['proposed' => 'Proposed', 'in_development' => 'In Development', 'ongoing' => 'Ongoing', 'operational' => 'Operational'][$status];
$appsOpen = $cfg0['sacco_applications_open'] === '1';
$errors = []; $sent = isset($_GET['sent']); $sentKind = $_GET['k'] ?? '';
$fc = nufa_form_cfg('sacco');
if (!$fc['enabled'] && $_SERVER['REQUEST_METHOD'] === 'POST') $_SERVER['REQUEST_METHOD'] = 'GET';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($g = form_guard($conn, 'sacco')) $errors[] = $g;
    $isApp = false;   // membership applications use the dedicated form (sacco-apply.php)
    $name = form_clean($_POST['name'] ?? '', 150); $email = strtolower(form_clean($_POST['email'] ?? '', 190)); $phone = form_clean($_POST['phone'] ?? '', 40);
    $district = form_clean($_POST['district'] ?? '', 100); $msg = form_clean($_POST['message'] ?? '', 2000);
    if ($name === '') $errors[] = "Please enter your name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (empty($_POST['ack'])) $errors[] = "Please confirm you understand the current status of the SACCO.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to being contacted and confirm you have read the privacy notice.";
    $details = ['District / location' => $district];
    if ($isApp) {
        $details['Area of creative practice'] = form_clean($_POST['craft'] ?? '', 120);
        $details['Years in the industry'] = form_clean($_POST['years'] ?? '', 40);
        if ($details['Area of creative practice'] === '') $errors[] = "Please tell us your area of creative practice.";
    }
    if (!$errors) {
        $ok = form_save($conn, 'sacco', $isApp ? 'Membership application' : 'Membership enquiry', $name, '', $email, $phone, $msg, $details, true);
        if ($ok) { header("Location: sacco.php?sent=1&k=" . ($isApp ? 'app' : 'enq') . "#enquiry"); exit; }
        $errors[] = "We couldn't save your submission just now. Please try again.";
    }
}

$pageTitle = cms_value('seo', 'sacco_title');
$pageDesc  = cms_value('seo', 'sacco_desc');
$activeNav = "sacco";
$canonicalPath = "sacco.php";
include __DIR__ . "/includes/header.php";
nufa_hit($conn, 'sacco');

$statusText = ['operational' => cms_value('sacco', 'status_text_op'), 'ongoing' => cms_value('sacco', 'status_text_ongoing'), 'in_development' => cms_value('sacco', 'status_text_dev')][$status] ?? cms_value('sacco', 'status_text');
$products = $conn->query("SELECT * FROM sacco_products WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$docs = $conn->query("SELECT * FROM sacco_documents WHERE is_active=1 ORDER BY COALESCE(published_on, DATE(created_at)) DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
$news = $conn->query("SELECT * FROM sacco_announcements WHERE is_active=1 ORDER BY published_on DESC, id DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$prodCats = ['savings' => 'Savings products', 'development' => 'Development & production loans', 'equipment' => 'Equipment financing', 'working_capital' => 'Working-capital financing', 'credit' => 'Other credit'];
$reg = array_filter(['Regulator' => $cfg0['sacco_regulator'], 'Registration number' => $cfg0['sacco_reg_number'], 'Licence / authorisation status' => $cfg0['sacco_licence_status'], 'Date' => $cfg0['sacco_reg_date']], fn($v) => $v !== '');
$draftVm = $cfg0['sacco_vm_approved'] !== '1';
$govPeople = trim(cms_value('sacco', 'governance_people'));

echo ui_page_hero('NUFA SACCO · ' . $statusLabel, cms_value('sacco', 'hero_heading'), cms_value('sacco', 'hero_lede'), cms_img_raw('sacco', 'hero_image'), [['NUFA SACCO']],
    '<a href="#about" class="btn btn-gold">Learn More</a><a href="#enquiry" class="btn btn-gold">Membership Enquiry</a><a href="contact.php?topic=General" class="btn btn-outline">Contact NUFA</a>');
?>
<section class="section--tight" style="padding-bottom:0">
  <div class="container">
    <div class="sacco-tools">
      <nav class="sacco-nav" aria-label="On this page">
        <a href="#about">About</a><a href="#benefits">Benefits</a><a href="#membership">Membership</a><a href="#products">Products</a><a href="#how">How it works</a><a href="#governance">Governance</a><a href="#faq">FAQs</a><a href="#documents">Documents</a><a href="#announcements">Announcements</a><a href="#regulatory">Regulatory</a><a href="#enquiry">Enquiry</a>
      </nav>
      <div class="search-form" role="search"><label class="sr-only" for="saccoSearch">Search this page</label><input id="saccoSearch" type="search" placeholder="Search products, FAQs, documents, announcements…" autocomplete="off"><button type="button" onclick="document.getElementById('saccoSearch').focus()">Search</button></div>
    </div>
    <p id="saccoNoMatch" class="muted" style="display:none;margin-top:12px">No matches on this page — try another word or <a href="#enquiry" class="hl" style="text-decoration:underline">send us an enquiry</a>.</p>
  </div>
</section>

<?php if (nufa_show('sacco', 'status')): ?><section class="section--tight" id="status">
  <div class="container"><div class="status-banner" role="note"><?php echo nufa_icon('shield', 26); ?><div><span class="chip chip--<?php echo in_array($status, ['operational', 'ongoing'], true) ? 'ongoing' : 'proposed'; ?>" style="margin-right:8px"><?php echo ui_e($statusLabel); ?></span> <?php echo ui_e($statusText); ?></div></div></div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'about')): ?><section class="section" id="about">
  <div class="container">
    <div class="split">
      <div data-reveal><div class="eyebrow">About NUFA SACCO</div><h2>What is being explored</h2><div class="lede stack"><?php echo ui_paragraphs(cms_value('sacco', 'intro_body')); ?></div>
        <h3 style="margin-top:28px">Background and rationale</h3><div class="stack"><?php echo cms_h('sacco', 'rationale'); ?></div></div>
      <div data-reveal>
        <h3>Relationship with NUFA</h3><div class="stack"><?php echo cms_h('sacco', 'about_relationship'); ?></div>
        <h3 style="margin-top:26px">Intended beneficiaries</h3><div class="stack"><?php echo cms_h('sacco', 'about_beneficiaries'); ?></div>
      </div>
    </div>
    <div class="vm-grid" style="margin-top:44px" data-reveal-stagger>
      <div class="vm-card"><h3>Vision</h3><p><?php echo cms_t('sacco', 'vision_text'); ?></p><?php if ($draftVm): ?><span class="draft-flag">Draft — subject to approval</span><?php endif; ?></div>
      <div class="vm-card"><h3>Mission</h3><p><?php echo cms_t('sacco', 'mission_text'); ?></p><?php if ($draftVm): ?><span class="draft-flag">Draft — subject to approval</span><?php endif; ?></div>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'benefits')): ?><section class="section section--soft" id="benefits">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Purpose &amp; benefits</div><h2>What it aims to do for filmmakers</h2></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php $bIcons = ['coins', 'handshake', 'film', 'compass', 'book']; foreach (nufa_list(['x' => cms_value('sacco', 'purpose_list')], 'x') as $i => $b): ?>
      <div class="value"><span class="icon-circle"><?php echo nufa_icon($bIcons[$i % 5], 24); ?></span><h3><?php echo ui_e($b); ?></h3></div>
      <?php endforeach; ?>
    </div>
    <div class="grid grid-2" style="gap:clamp(24px,4vw,56px);margin-top:48px">
      <div data-reveal><h3>Intended objectives</h3><div class="stack"><?php echo cms_h('sacco', 'objectives'); ?></div></div>
      <div data-reveal><h3>Potential areas of development</h3><div class="stack"><?php echo cms_h('sacco', 'areas'); ?></div></div>
      <div data-reveal><h3>Benefits</h3><div class="stack"><?php echo cms_h('sacco', 'benefits'); ?></div></div>
      <div data-reveal><h3>Governance and regulatory considerations</h3><div class="stack"><?php echo cms_h('sacco', 'governance'); ?></div></div>
    </div>
    <div class="notice" style="margin-top:36px" data-reveal><span><?php echo nufa_icon('star', 22); ?></span><div><?php echo cms_h('sacco', 'development_status'); ?></div></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'membership')): ?><section class="section" id="membership">
  <div class="container">
    <div class="section-head" data-reveal><div class="eyebrow">Membership information</div><h2>Joining the SACCO</h2></div>
    <div class="grid grid-2" style="gap:clamp(24px,4vw,56px)">
      <div data-reveal><h3>Eligibility requirements</h3><div class="stack"><?php echo cms_h('sacco', 'membership_eligibility'); ?></div>
        <h3 style="margin-top:26px">Required documents</h3><?php echo ui_list(cms_value('sacco', 'membership_documents')); ?>
        <h3 style="margin-top:26px">Membership fees and share requirements</h3><div class="stack"><?php echo cms_h('sacco', 'membership_fees'); ?></div></div>
      <div data-reveal><h3>Application process</h3><?php echo ui_steps(cms_value('sacco', 'membership_process')); ?>
        <h3 style="margin-top:26px">Rights, responsibilities and obligations</h3><div class="stack"><?php echo cms_h('sacco', 'membership_rights'); ?></div></div>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'products')): ?><section class="section section--dark" id="products">
  <div class="container">
    <div class="section-head" data-reveal><div class="eyebrow">Savings &amp; credit products</div><h2>Product catalogue</h2><p><?php echo cms_t('sacco', 'products_note'); ?></p></div>
    <?php if (!$products): ?>
      <div class="empty-state" style="background:rgba(255,255,255,.05);border-color:var(--line-dark)"><h3>No products published yet</h3><p style="color:var(--muted-on-dark)">Savings and credit products will be listed here with their terms once they are approved.</p></div>
    <?php else: foreach ($prodCats as $ck => $cl):
        $in = array_filter($products, fn($p) => $p['category'] === $ck); if (!$in) continue; ?>
      <h3 style="color:var(--gold);font-size:.9rem;letter-spacing:.14em;text-transform:uppercase;margin:34px 0 16px"><?php echo ui_e($cl); ?></h3>
      <div class="grid grid-2">
        <?php foreach ($in as $p): $approved = $p['status'] === 'approved'; ?>
        <article class="card card--dark" data-s style="padding:24px">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start"><h3 style="margin:0;font-size:1.1rem"><?php echo ui_e($p['name']); ?></h3><span class="chip chip--<?php echo $approved ? 'ongoing' : 'proposed'; ?>"><?php echo $approved ? 'Approved' : 'Terms to be announced'; ?></span></div>
          <?php if ($p['description']): ?><p><?php echo nl2br(ui_e($p['description'])); ?></p><?php endif; ?>
          <?php if ($approved && trim((string)$p['eligibility']) !== ''): ?><h4 style="color:var(--gold);font-size:.74rem;letter-spacing:.12em;text-transform:uppercase;margin:12px 0 4px">Eligibility</h4><p style="white-space:pre-wrap"><?php echo ui_e($p['eligibility']); ?></p><?php endif; ?>
          <?php if ($approved && trim((string)$p['terms']) !== ''): ?><h4 style="color:var(--gold);font-size:.74rem;letter-spacing:.12em;text-transform:uppercase;margin:12px 0 4px">Terms, interest, fees &amp; security</h4><p style="white-space:pre-wrap"><?php echo ui_e($p['terms']); ?></p><?php endif; ?>
          <?php if (!$approved): ?><p style="font-size:.8rem;margin-top:10px"><em>Eligibility, repayment terms, interest, fees and security are published once this product's terms are approved.</em></p><?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'how')): ?><section class="section" id="how">
  <div class="container" style="max-width:900px">
    <div class="section-head" data-reveal><div class="eyebrow">How it works</div><h2>A SACCO in simple steps</h2></div>
    <div data-reveal><?php echo ui_steps(cms_value('sacco', 'how_it_works')); ?></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'governance')): ?><section class="section section--soft" id="governance">
  <div class="container">
    <div class="section-head" data-reveal><div class="eyebrow">Governance &amp; leadership</div><h2>Who will run it</h2></div>
    <?php if ($govPeople !== ''): ?><div class="stack" data-reveal><?php echo $govPeople; ?></div>
    <?php else: ?><div class="empty-state"><h3>To be published</h3><p><?php echo cms_t('sacco', 'governance_note'); ?></p></div><?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'faq')): ?><section class="section" id="faq">
  <div class="container" style="max-width:860px">
    <div class="section-head" data-reveal><div class="eyebrow">Questions</div><h2>Frequently asked questions</h2></div>
    <div class="accordion" data-reveal><?php echo cms_h('sacco', 'faq'); ?></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'documents')): ?><section class="section section--soft" id="documents">
  <div class="container" style="max-width:960px">
    <div class="section-head" data-reveal><div class="eyebrow">Policies &amp; documents</div><h2>Approved documents</h2><p><?php echo cms_t('sacco', 'documents_intro'); ?></p></div>
    <?php if (!$docs): ?><div class="empty-state"><h3>No documents yet</h3><p>Nothing is listed until it has been approved and supplied.</p></div>
    <?php else: foreach ($docs as $d): $href = $d['file_path'] ? 'assets/sacco-docs/' . rawurlencode($d['file_path']) : $d['external_url']; ?>
      <div class="resource-item" data-s>
        <span class="icon-circle"><?php echo nufa_icon($d['file_path'] ? 'download' : 'globe', 22); ?></span>
        <div><div class="card-meta"><b><?php echo ui_e($d['doc_type']); ?></b><span><?php echo ui_date($d['published_on'] ?: substr($d['created_at'], 0, 10)); ?></span></div><h3><?php echo ui_e($d['title']); ?></h3><?php if ($d['description']): ?><p><?php echo ui_e($d['description']); ?></p><?php endif; ?></div>
        <?php if ($href): ?><a href="<?php echo ui_e($href); ?>" class="btn btn-outline-dark btn-sm" <?php echo $d['file_path'] ? 'download' : 'target="_blank" rel="noopener"'; ?>><?php echo $d['file_path'] ? 'Download ' . ui_e(strtoupper(pathinfo($d['file_path'], PATHINFO_EXTENSION))) : 'Open'; ?></a><?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'announcements')): ?><section class="section" id="announcements">
  <div class="container" style="max-width:900px">
    <div class="section-head" data-reveal><div class="eyebrow">Announcements</div><h2>Official notices</h2><p><?php echo cms_t('sacco', 'announcements_intro'); ?></p></div>
    <?php if (!$news): ?><div class="empty-state"><h3>No announcements yet</h3><p>Registration updates, member meetings and product launches will be posted here.</p></div>
    <?php else: foreach ($news as $a): ?>
      <article class="announce" data-s><div class="card-meta"><b><?php echo ui_e($a['kind']); ?></b><span><?php echo ui_date($a['published_on']); ?></span></div><h3><?php echo ui_e($a['title']); ?></h3><div class="stack"><?php echo ui_paragraphs($a['body']); ?></div></article>
    <?php endforeach; endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'regulatory')): ?><section class="section section--soft" id="regulatory">
  <div class="container" style="max-width:900px">
    <div class="section-head" data-reveal><div class="eyebrow">Regulatory information</div><h2>Registration &amp; authorisation</h2></div>
    <?php if ($reg): ?><table class="data" style="margin-bottom:20px"><tbody><?php foreach ($reg as $k => $v): ?><tr><th style="width:240px"><?php echo ui_e($k); ?></th><td><?php echo ui_e($v); ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
    <div class="stack"><?php echo cms_h('sacco', 'regulatory_note'); ?></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('sacco', 'interest')): ?><section class="section section--dark" id="enquiry">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <div data-reveal>
      <div class="eyebrow">Contact &amp; support</div><h2><?php echo cms_t('sacco', 'enquiry_heading'); ?></h2>
      <p style="color:var(--muted-on-dark)"><?php echo cms_t('sacco', 'enquiry_intro'); ?></p>
      <div class="notice notice--dark"><span><?php echo nufa_icon('shield', 22); ?></span><div>NUFA is not collecting deposits, loan repayments or loan applications through this website. Be cautious of anyone who says otherwise.</div></div>
      <ul style="display:grid;gap:12px;margin-top:22px;font-size:.92rem">
        <li><?php echo nufa_icon('mail', 15); ?> <a href="mailto:<?php echo ui_e($cfg0['site_email']); ?>" style="color:var(--gold)"><?php echo ui_e($cfg0['site_email']); ?></a></li>
        <li><?php echo nufa_icon('phone', 15); ?> <a href="tel:<?php echo nufa_tel_href($cfg0['site_phone']); ?>"><?php echo ui_e($cfg0['site_phone']); ?></a></li>
        <li><?php echo nufa_icon('pin', 15); ?> <?php echo ui_e($cfg0['site_address']); ?></li>
      </ul>
    </div>
    <div class="form-card" style="color:var(--ink)" data-reveal>
      <?php if ($fc['intro'] !== ''): ?><p class="muted" style="margin-bottom:18px"><?php echo ui_e($fc['intro']); ?></p><?php endif; ?>
      <?php echo form_banner($sent, $errors, $sentKind === 'app' ? 'Your membership application has been received. NUFA will review it and contact you.' : $fc['success']); ?>
      <?php if (!$fc['enabled']): ?><div class="alert err" role="status"><?php echo ui_e($fc['closed']); ?></div>
      <?php elseif (!$sent): ?>
      <?php if ($appsOpen): ?><div class="info-card" style="margin-bottom:16px"><b>Ready to join?</b> Membership applications are open. <a class="link-arrow" href="sacco-apply.php">Start your membership application</a></div>
      <?php else: ?><p class="muted" style="font-size:.88rem;margin-bottom:16px">Membership applications are <b>not open</b> yet. You can send an enquiry below.</p><?php endif; ?>
      <form method="post" action="sacco.php#enquiry">
        <?php echo form_open_fields(); ?>
        <input type="hidden" name="form_kind" id="formKind" value="enquiry">
        <div class="form-grid">
          <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name']); ?>
          <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
          <?php echo f_input('phone', 'Telephone', ['type' => 'tel']); ?>
          <?php echo f_input('district', 'District or location'); ?>
        </div>
        <?php echo f_textarea('message', 'Your question or message (optional)', ['maxlength' => 2000]); ?>
        <?php echo f_check('ack', 'I understand the current status of the NUFA SACCO shown on this page, and that this form does not create any financial relationship.'); ?>
        <?php echo f_check('consent', 'I consent to NUFA contacting me and have read the <a href="policy.php?p=privacy" target="_blank">privacy notice</a>.'); ?>
        <button class="btn btn-gold btn-block" type="submit"><?php echo ui_e($fc['button']); ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section><?php endif; ?>

<script>
(function(){
  var box=document.getElementById('saccoSearch'); if(!box) return;
  var items=document.querySelectorAll('[data-s], #faq details'), none=document.getElementById('saccoNoMatch');
  box.addEventListener('input',function(){
    var q=box.value.trim().toLowerCase(), shown=0;
    items.forEach(function(el){ var m=!q||el.textContent.toLowerCase().indexOf(q)>-1; el.style.display=m?'':'none'; if(m) shown++; if(q&&m&&el.tagName==='DETAILS') el.open=true; });
    none.style.display=(q&&!shown)?'block':'none';
  });
})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
