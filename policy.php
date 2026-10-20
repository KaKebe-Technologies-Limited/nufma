<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";

$policies = [
    'privacy'          => 'Privacy Policy',
    'cookies'          => 'Cookie Policy',
    'terms'            => 'Website Terms of Use',
    'permissions'      => 'Image and Content Permissions',
    'accessibility'    => 'Accessibility Statement',
    'membership_terms' => 'Membership Terms',
    'complaints'       => 'Complaints and Feedback',
];
$p = $_GET['p'] ?? 'privacy';
if (!isset($policies[$p])) { http_response_code(404); include __DIR__ . "/404.php"; exit; }

$pageTitle = $policies[$p] . " — NUFA";
$pageDesc  = $policies[$p] . " for the Northern Uganda Filmmakers Association website.";
$activeNav = "";
$canonicalPath = "policy.php?p=" . $p;
include __DIR__ . "/includes/header.php";
$draft = $cfg['policies_approved'] !== '1';
?>
<section class="page-hero" style="padding-block:clamp(48px,7vw,84px)">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a><span aria-hidden="true">/</span><span aria-current="page"><?php echo ui_e($policies[$p]); ?></span></nav>
    <h1><?php echo ui_e($policies[$p]); ?></h1>
  </div>
</section>
<section class="section">
  <div class="container">
    <?php if ($draft): ?><div class="notice" style="max-width:78ch;margin:0 auto 34px"><span><?php echo nufa_icon('shield', 22); ?></span><div><b>Draft for review.</b> This document is an editable draft based on the website's current functionality and is awaiting review by a qualified professional before it is treated as final.</div></div><?php endif; ?>
    <div class="policy"><?php echo cms_h('policies', $p); ?></div>
    <nav class="policy" style="margin-top:50px;padding-top:24px;border-top:1px solid var(--line)" aria-label="Other policies">
      <strong>Other policies:</strong>
      <?php foreach ($policies as $k => $label) if ($k !== $p): ?><a class="hl" style="margin-right:14px;text-decoration:underline;white-space:nowrap" href="policy.php?p=<?php echo $k; ?>"><?php echo ui_e($label); ?></a><?php endif; ?>
    </nav>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
