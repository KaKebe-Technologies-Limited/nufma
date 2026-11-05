<?php
/** Private link to an invitation letter on NUFA headed paper (sent by email / WhatsApp). Print or "Save as PDF" from here. */
require_once __DIR__ . "/includes/letters.php";
ltr_install($conn);
$t = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
$l = null;
if (strlen($t) === 32) { $s = $conn->prepare("SELECT * FROM invitation_letters WHERE token=? LIMIT 1"); $s->bind_param("s", $t); $s->execute(); $l = $s->get_result()->fetch_assoc(); }
header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0'); header('Pragma: no-cache');
if (!$l) { http_response_code(404); }
$ver = $l ? (string)(strtotime($l['updated_at'] ?: $l['created_at']) . '-' . md5($l['body'] . $l['recipient_name'] . $l['event_title'] . $l['event_date'] . $l['venue'] . $l['event_time'])) : '';
if (isset($_GET['ping'])) { header('Content-Type: application/json'); echo json_encode(['v' => $ver]); exit; }
$sentAt = $l ? max(strtotime($l['email_sent_at'] ?: '2000-01-01'), strtotime($l['wa_sent_at'] ?: '2000-01-01')) : 0;
$updated = $l && $l['updated_at'] && strtotime($l['updated_at']) > $sentAt && $sentAt > 946684800;
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?php echo $l ? 'Invitation — ' . htmlspecialchars($l['event_title']) : 'Letter not found'; ?> — NUFA</title>
<style>body{margin:0;background:#e9e9e9;font-family:system-ui,Arial,sans-serif}.bar{position:sticky;top:0;z-index:5;background:#111;color:#fff;padding:10px 16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}.bar button{background:#D50000;color:#fff;border:0;border-radius:999px;padding:10px 22px;font:700 14px system-ui;cursor:pointer}.bar span{color:#bbb;font-size:13px}.wrap{padding:18px 8px 40px;overflow-x:auto}.nf{max-width:520px;margin:90px auto;background:#fff;padding:30px;border-radius:14px;text-align:center}@media print{.bar,.wrap{padding:0;background:#fff}.bar{display:none}}</style></head><body>
<?php if (!$l): ?><div class="nf"><h2>Letter not found</h2><p>This link is invalid or the letter has been withdrawn. Please contact NUFA.</p></div>
<?php else: ?><div class="bar"><button onclick="window.print()">Print / Save as PDF</button><?php if ($updated): ?><span style="background:#0E7A3F;color:#fff;border-radius:99px;padding:6px 14px;font-size:13px;font-weight:700">Updated version — this replaces any earlier copy</span><?php endif; ?><span>On A4 paper, with margins set to “None” and background graphics ON for the best result.</span></div>
<div class="wrap"><?php echo ltr_html($conn, $l, ['prefix' => '']); ?></div><?php endif; ?>
<?php if ($l): ?><script>(function(){var v=<?php echo json_encode($ver); ?>,u=location.pathname+"?t=<?php echo htmlspecialchars($t); ?>&ping=1";setInterval(function(){fetch(u,{cache:"no-store"}).then(function(r){return r.json()}).then(function(d){if(d.v&&d.v!==v)location.reload();}).catch(function(){});},30000);})();</script><?php endif; ?>
</body></html>
