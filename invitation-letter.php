<?php
/** Private link to an invitation letter on NUFA headed paper (sent by email / WhatsApp). Print or "Save as PDF" from here. */
require_once __DIR__ . "/includes/letters.php";
ltr_install($conn);
$t = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
$l = null;
if (strlen($t) === 32) { $s = $conn->prepare("SELECT * FROM invitation_letters WHERE token=? LIMIT 1"); $s->bind_param("s", $t); $s->execute(); $l = $s->get_result()->fetch_assoc(); }
header('X-Robots-Tag: noindex, nofollow');
if (!$l) { http_response_code(404); }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?php echo $l ? 'Invitation — ' . htmlspecialchars($l['event_title']) : 'Letter not found'; ?> — NUFA</title>
<style>body{margin:0;background:#e9e9e9;font-family:system-ui,Arial,sans-serif}.bar{position:sticky;top:0;z-index:5;background:#111;color:#fff;padding:10px 16px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}.bar button{background:#D50000;color:#fff;border:0;border-radius:999px;padding:10px 22px;font:700 14px system-ui;cursor:pointer}.bar span{color:#bbb;font-size:13px}.wrap{padding:18px 8px 40px;overflow-x:auto}.nf{max-width:520px;margin:90px auto;background:#fff;padding:30px;border-radius:14px;text-align:center}@media print{.bar,.wrap{padding:0;background:#fff}.bar{display:none}}</style></head><body>
<?php if (!$l): ?><div class="nf"><h2>Letter not found</h2><p>This link is invalid or the letter has been withdrawn. Please contact NUFA.</p></div>
<?php else: ?><div class="bar"><button onclick="window.print()">Print / Save as PDF</button><span>On A4 paper, with margins set to “None” and background graphics ON for the best result.</span></div>
<div class="wrap"><?php echo ltr_html($conn, $l, ['prefix' => '']); ?></div><?php endif; ?>
</body></html>
