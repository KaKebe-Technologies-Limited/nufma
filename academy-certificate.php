<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/settings.php";
acad_install($conn);
$S = acad_settings($conn); $cfg = nufa_site_settings($conn);
$no = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['no'] ?? '');
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$en = null; $sample = false;
if (($_GET['sample'] ?? '') === '1') {   // admins can preview the certificate with made-up details (Admin → Academy → Preview)
    require_once __DIR__ . '/admin/includes/security.php'; admin_session_start();
    if (isset($_SESSION['admin_id'])) {
        $sample = true; $no = 'NUFA-AC-SAMPLE';
        $c0 = $conn->query('SELECT * FROM academy_courses ORDER BY id LIMIT 1')->fetch_assoc() ?: ['title' => 'Introduction to Film Production', 'level' => 'Beginner', 'category' => 'Production'];
        $en = ['learner_id' => 0, 'course_id' => 0, 'best_score' => 92, 'completed_at' => date('Y-m-d H:i:s')]; $l = ['name' => 'Sample Learner Name']; $c = $c0 + ['pass_mark' => 70];
    }
}
if (!$sample && $no !== '') { $s = $conn->prepare("SELECT * FROM academy_enrolments WHERE cert_no=? AND status='completed'"); $s->bind_param("s", $no); $s->execute(); $en = $s->get_result()->fetch_assoc(); }
if ($en && !$sample) { $l = $conn->query("SELECT name FROM academy_learners WHERE id=" . (int)$en['learner_id'])->fetch_assoc(); $c = $conn->query("SELECT * FROM academy_courses WHERE id=" . (int)$en['course_id'])->fetch_assoc(); }
$logo = ($cfg['site_logo'] !== '' && is_file(__DIR__ . '/assets/images/' . $cfg['site_logo'])) ? $cfg['site_logo'] : 'logo/nufa-logo.png';
$gold = preg_match('/^#[0-9a-f]{6}$/i', $cfg['brand_gold'] ?? '') ? $cfg['brand_gold'] : '#D50000';
// the President signs the certificate: signature picture and name come from Admin → Membership settings (name falls back to the President on the Leadership team)
require_once __DIR__ . '/includes/membership.php';
$MS = mem_settings($conn);
$presSig = ($MS['president_signature'] ?? '') !== '' && is_file(__DIR__ . '/assets/images/' . $MS['president_signature']) ? $MS['president_signature'] : '';
$presName = trim((string)($MS['president_name'] ?? ''));
if ($presName === '') { $tp = @$conn->query("SELECT name FROM team_members WHERE is_active=1 AND role LIKE 'President%' ORDER BY sort_order, id LIMIT 1"); if ($tp && ($tr = $tp->fetch_assoc())) $presName = $tr['name']; }
$signers = [];
foreach ([1, 2] as $__n) {
    $t = trim($S['acad_sign' . $__n . '_title']); if ($t === '') $t = $__n === 1 ? 'Academy Director' : 'President'; $nm = trim($S['acad_sign' . $__n . '_name']); $isPres = (bool)preg_match('/president/i', $t) && !preg_match('/vice/i', $t);
    if ($isPres && $nm === '') $nm = $presName;
    $own = trim((string)($S['acad_sign' . $__n . '_image'] ?? '')); $ownOk = $own !== '' && is_file(__DIR__ . '/assets/images/' . $own);
    $signers[] = ['name' => $nm, 'title' => $t, 'sig' => $ownOk ? $own : ($isPres ? $presSig : '')];
}
/** Gold embossed seal (scalloped edge, ring text, star, ribbon tails) as inline SVG. */
function acad_gold_seal($id = 'gs') {
    $pts = []; for ($i = 0; $i < 56; $i++) { $a = deg2rad($i * 360 / 56 - 90); $r = $i % 2 ? 52 : 57; $pts[] = round(60 + $r * cos($a), 2) . ',' . round(62 + $r * sin($a), 2); }
    $star = []; for ($i = 0; $i < 10; $i++) { $a = deg2rad($i * 36 - 90); $r = $i % 2 ? 6.5 : 15; $star[] = round(60 + $r * cos($a), 2) . ',' . round(62 + $r * sin($a), 2); }
    return '<svg class="seal" viewBox="0 0 120 150" role="img" aria-label="NUFA Academy gold seal"><defs>'
        . '<radialGradient id="' . $id . 'a" cx="34%" cy="28%" r="80%"><stop offset="0" stop-color="#FFF3B0"/><stop offset=".35" stop-color="#EBC84A"/><stop offset=".75" stop-color="#B98A16"/><stop offset="1" stop-color="#8A6410"/></radialGradient>'
        . '<linearGradient id="' . $id . 'b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#F6DD7B"/><stop offset=".5" stop-color="#C9A227"/><stop offset="1" stop-color="#8A6410"/></linearGradient>'
        . '<path id="' . $id . 't" d="M30,62 a30,30 0 1,1 60,0"/><path id="' . $id . 'u" d="M24,62 a36,36 0 0,0 72,0"/></defs>'
        . '<polygon points="44,100 30,148 48,138 58,148 66,104" fill="url(#' . $id . 'b)" stroke="#8A6410" stroke-width=".8"/><polygon points="76,100 90,148 72,138 62,148 54,104" fill="url(#' . $id . 'b)" stroke="#8A6410" stroke-width=".8"/>'
        . '<polygon points="' . implode(' ', $pts) . '" fill="url(#' . $id . 'a)" stroke="#8A6410" stroke-width=".9"/>'
        . '<circle cx="60" cy="62" r="45" fill="none" stroke="#FFF1A8" stroke-width="1.2" opacity=".9"/><circle cx="60" cy="62" r="42" fill="none" stroke="#8A6410" stroke-width=".8"/><circle cx="60" cy="62" r="24" fill="none" stroke="#8A6410" stroke-width=".8"/>'
        . '<g font-family="Georgia,serif" font-weight="700" fill="#5E4308" font-size="7.6" letter-spacing="1.5"><text><textPath href="#' . $id . 't" startOffset="50%" text-anchor="middle">NUFA ACADEMY</textPath></text><text><textPath href="#' . $id . 'u" startOffset="50%" text-anchor="middle">CERTIFIED</textPath></text></g>'
        . '<polygon points="' . implode(' ', $star) . '" fill="#FFF3B0" stroke="#8A6410" stroke-width=".8"/></svg>';
}
header('X-Robots-Tag: noindex');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo $en ? 'Certificate ' . $e($no) : 'Verify a certificate'; ?> — NUFA Academy</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#1A1A1A;font-family:Georgia,'Times New Roman',serif;color:#161616;padding:20px}
.bar{max-width:1000px;margin:0 auto 16px;display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;align-items:center;font-family:system-ui,sans-serif;color:#fff}.bar a,.bar button{background:<?php echo $gold; ?>;color:#161616;border:0;border-radius:99px;padding:10px 20px;font:700 14px system-ui;text-decoration:none;cursor:pointer}
.cert{max-width:1000px;margin:0 auto;background:#FFFFFF;border:14px double <?php echo $gold; ?>;padding:44px 56px;text-align:center;aspect-ratio:1.414/1;display:flex;flex-direction:column;justify-content:space-between;position:relative}
.cert img{height:84px;margin:0 auto 6px;display:block}.org{font:700 .95rem system-ui;letter-spacing:.28em;text-transform:uppercase;color:#B71C1C}.sub{font:.85rem system-ui;color:#757575;margin-top:2px}
h1{font-size:clamp(1.8rem,4.4vw,3rem);margin:14px 0 4px;color:#161616;letter-spacing:.04em}.lead{font-style:italic;color:#757575;margin:0}
.name{font-size:clamp(2rem,5.6vw,3.6rem);margin:10px 0;color:#161616;border-bottom:2px solid <?php echo $gold; ?>;display:inline-block;padding:0 28px 6px}
.course{font-size:clamp(1.1rem,2.6vw,1.7rem);font-weight:700;margin:6px 0}.meta{font:.95rem system-ui;color:#4B4B4B}
.sign{position:relative;display:flex;justify-content:space-around;align-items:flex-end;gap:20px;margin-top:26px}.sign>div{min-width:200px;border-top:1px solid #757575;padding-top:6px;font:.85rem system-ui;position:relative}.sign b{display:block;font-size:.95rem}.sign .sg{position:absolute;left:50%;bottom:calc(100% - 4px);transform:translateX(-50%);height:74px;max-width:210px;width:auto;margin:0}
.seal{position:absolute;left:50%;bottom:-10px;transform:translateX(-50%);width:98px;height:auto;filter:drop-shadow(0 6px 8px rgba(120,84,10,.35));-webkit-print-color-adjust:exact;print-color-adjust:exact}
.cert .sign .sg{display:block}
.foot{font:.72rem system-ui;color:#757575;margin-top:10px}.ok{display:inline-block;background:#1f7a3d;color:#fff;border-radius:99px;padding:3px 12px;font:700 .75rem system-ui;margin-bottom:8px}
.nf{max-width:560px;margin:60px auto;background:#fff;border-radius:18px;padding:34px;text-align:center;font-family:system-ui,sans-serif}.nf input{padding:12px 16px;border:1px solid #ccc;border-radius:99px;width:100%;margin:12px 0;font:inherit}.nf button{background:#161616;color:<?php echo $gold; ?>;border:0;border-radius:99px;padding:12px 26px;font:700 14px system-ui;cursor:pointer}
@media(max-width:700px){.cert{aspect-ratio:auto;padding:28px 20px;border-width:10px}.sign{flex-direction:column;align-items:center;gap:56px}.seal{position:static;transform:none;display:block;margin:6px auto 0;width:96px;order:3}}
@media print{@page{size:A4 landscape;margin:0}body{background:#fff;padding:0}.bar{display:none}.cert{border-width:14px;max-width:none;width:100vw;height:100vh;aspect-ratio:auto}}
</style></head><body>
<?php if ($en): $pass = acad_pass_mark($c, $S); ?>
<div class="bar"><a href="academy-portal.php">← My learning</a><span><?php echo $sample ? 'SAMPLE preview (made-up learner)' : 'Verified certificate · ' . $e($no); ?></span><button onclick="window.print()">Print / save as PDF</button></div>
<div class="cert">
  <div><img src="assets/images/<?php echo $e($logo); ?>" alt=""><div class="org"><?php echo $e($S['acad_cert_org']); ?></div><div class="sub"><?php echo $e($S['acad_cert_sub']); ?></div></div>
  <div><h1><?php echo $e($S['acad_cert_title']); ?></h1><p class="lead">This certifies that</p><div class="name"><?php echo $e($l['name'] ?? ''); ?></div><p class="lead">has successfully completed the course and passed the final assessment</p>
    <div class="course"><?php echo $e($c['title']); ?></div><div class="meta"><?php echo $e($c['level']); ?> level · <?php echo $e($c['category']); ?> · Score <?php echo (int)$en['best_score']; ?>% (pass mark <?php echo $pass; ?>%)</div></div>
  <div><div class="sign"><?php foreach ($signers as $sg): ?><div><?php if ($sg['sig'] !== ''): ?><img class="sg" src="assets/images/<?php echo $e($sg['sig']); ?>" alt="Signature of <?php echo $e($sg['name'] ?: $sg['title']); ?>"><?php endif; ?><b><?php echo $e($sg['name'] ?: ' '); ?></b><?php echo $e($sg['title']); ?></div><?php endforeach; ?><?php echo acad_gold_seal(); ?></div>
  <div class="foot">Issued <?php echo $e(date('j F Y', strtotime($en['completed_at']))); ?> · Certificate no. <?php echo $e($no); ?> · Verify at <?php echo $e(preg_replace('#^https?://#', '', rtrim((($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . dirname($_SERVER['SCRIPT_NAME']), '/'))); ?>/academy-certificate.php<?php echo $S['acad_accreditation'] !== '' ? '<br>' . $e($S['acad_accreditation']) : ''; ?></div></div>
</div>
<?php else: ?>
<div class="nf"><h2>Verify a certificate</h2><?php if ($no !== ''): ?><p style="color:#a02020"><b>No certificate was found for “<?php echo $e($no); ?>”.</b> Check the number and try again.</p><?php else: ?><p>Enter the certificate number printed at the bottom of the certificate.</p><?php endif; ?>
  <form method="get"><input name="no" placeholder="e.g. NUFA-AC-2026-00001" value="<?php echo $e($no); ?>"><button>Verify</button></form><p><a href="academy.php">← NUFA Academy</a></p></div>
<?php endif; ?>
</body></html>
