<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/settings.php";
acad_install($conn);
$S = acad_settings($conn); $cfg = nufa_site_settings($conn);
$no = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['no'] ?? '');
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$en = null;
if ($no !== '') { $s = $conn->prepare("SELECT * FROM academy_enrolments WHERE cert_no=? AND status='completed'"); $s->bind_param("s", $no); $s->execute(); $en = $s->get_result()->fetch_assoc(); }
if ($en) { $l = $conn->query("SELECT name FROM academy_learners WHERE id=" . (int)$en['learner_id'])->fetch_assoc(); $c = $conn->query("SELECT * FROM academy_courses WHERE id=" . (int)$en['course_id'])->fetch_assoc(); }
$logo = ($cfg['site_logo'] !== '' && is_file(__DIR__ . '/assets/images/' . $cfg['site_logo'])) ? $cfg['site_logo'] : 'logo/nufa-logo.png';
$gold = preg_match('/^#[0-9a-f]{6}$/i', $cfg['brand_gold'] ?? '') ? $cfg['brand_gold'] : '#F57A0A';
header('X-Robots-Tag: noindex');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo $en ? 'Certificate ' . $e($no) : 'Verify a certificate'; ?> — NUFA Academy</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#1A1A1A;font-family:Georgia,'Times New Roman',serif;color:#161616;padding:20px}
.bar{max-width:1000px;margin:0 auto 16px;display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:center;font-family:system-ui,sans-serif;color:#fff}.bar a,.bar button{background:<?php echo $gold; ?>;color:#161616;border:0;border-radius:99px;padding:10px 20px;font:700 14px system-ui;text-decoration:none;cursor:pointer}
.cert{max-width:1000px;margin:0 auto;background:#FFFFFF;border:14px double <?php echo $gold; ?>;padding:44px 56px;text-align:center;aspect-ratio:1.414/1;display:flex;flex-direction:column;justify-content:space-between;position:relative}
.cert img{height:84px;margin:0 auto 6px;display:block}.org{font:700 .95rem system-ui;letter-spacing:.28em;text-transform:uppercase;color:#B04A00}.sub{font:.85rem system-ui;color:#666666;margin-top:2px}
h1{font-size:clamp(1.8rem,4.4vw,3rem);margin:14px 0 4px;color:#161616;letter-spacing:.04em}.lead{font-style:italic;color:#666666;margin:0}
.name{font-size:clamp(2rem,5.6vw,3.6rem);margin:10px 0;color:#161616;border-bottom:2px solid <?php echo $gold; ?>;display:inline-block;padding:0 28px 6px}
.course{font-size:clamp(1.1rem,2.6vw,1.7rem);font-weight:700;margin:6px 0}.meta{font:.95rem system-ui;color:#4B4B4B}
.sign{display:flex;justify-content:space-around;gap:20px;margin-top:18px}.sign div{min-width:200px;border-top:1px solid #8A8A8A;padding-top:6px;font:.85rem system-ui}.sign b{display:block;font-size:.95rem}
.foot{font:.72rem system-ui;color:#666666;margin-top:10px}.ok{display:inline-block;background:#1f7a3d;color:#fff;border-radius:99px;padding:3px 12px;font:700 .75rem system-ui;margin-bottom:8px}
.nf{max-width:560px;margin:60px auto;background:#fff;border-radius:18px;padding:34px;text-align:center;font-family:system-ui,sans-serif}.nf input{padding:12px 16px;border:1px solid #ccc;border-radius:99px;width:100%;margin:12px 0;font:inherit}.nf button{background:#161616;color:<?php echo $gold; ?>;border:0;border-radius:99px;padding:12px 26px;font:700 14px system-ui;cursor:pointer}
@media(max-width:700px){.cert{aspect-ratio:auto;padding:28px 20px;border-width:10px}.sign{flex-direction:column;align-items:center}}
@media print{@page{size:A4 landscape;margin:0}body{background:#fff;padding:0}.bar{display:none}.cert{border-width:14px;max-width:none;width:100vw;height:100vh;aspect-ratio:auto}}
</style></head><body>
<?php if ($en): $pass = acad_pass_mark($c, $S); ?>
<div class="bar"><a href="academy-portal.php">← My learning</a><span>Verified certificate · <?php echo $e($no); ?></span><button onclick="window.print()">Print / save as PDF</button></div>
<div class="cert">
  <div><img src="assets/images/<?php echo $e($logo); ?>" alt=""><div class="org"><?php echo $e($S['acad_cert_org']); ?></div><div class="sub"><?php echo $e($S['acad_cert_sub']); ?></div></div>
  <div><h1><?php echo $e($S['acad_cert_title']); ?></h1><p class="lead">This certifies that</p><div class="name"><?php echo $e($l['name'] ?? ''); ?></div><p class="lead">has successfully completed the course and passed the final assessment</p>
    <div class="course"><?php echo $e($c['title']); ?></div><div class="meta"><?php echo $e($c['level']); ?> level · <?php echo $e($c['category']); ?> · Score <?php echo (int)$en['best_score']; ?>% (pass mark <?php echo $pass; ?>%)</div></div>
  <div><div class="sign"><div><b><?php echo $e($S['acad_sign1_name'] ?: ' '); ?></b><?php echo $e($S['acad_sign1_title']); ?></div><div><b><?php echo $e($S['acad_sign2_name'] ?: ' '); ?></b><?php echo $e($S['acad_sign2_title']); ?></div></div>
  <div class="foot">Issued <?php echo $e(date('j F Y', strtotime($en['completed_at']))); ?> · Certificate no. <?php echo $e($no); ?> · Verify at <?php echo $e(preg_replace('#^https?://#', '', rtrim((($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . dirname($_SERVER['SCRIPT_NAME']), '/'))); ?>/academy-certificate.php<?php echo $S['acad_accreditation'] !== '' ? '<br>' . $e($S['acad_accreditation']) : ''; ?></div></div>
</div>
<?php else: ?>
<div class="nf"><h2>Verify a certificate</h2><?php if ($no !== ''): ?><p style="color:#a02020"><b>No certificate was found for “<?php echo $e($no); ?>”.</b> Check the number and try again.</p><?php else: ?><p>Enter the certificate number printed at the bottom of the certificate.</p><?php endif; ?>
  <form method="get"><input name="no" placeholder="e.g. NUFA-AC-2026-00001" value="<?php echo $e($no); ?>"><button>Verify</button></form><p><a href="academy.php">← NUFA Academy</a></p></div>
<?php endif; ?>
</body></html>
