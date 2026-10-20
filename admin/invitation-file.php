<?php
// Streams a campaign's PDF to the admin: ?id=&mode=template | preview (sample guest) | guest&guest=ID
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/invitations.php";
inv_install($conn);
$id = (int)($_GET['id'] ?? 0);
$camp = $conn->query("SELECT * FROM invite_campaigns WHERE id=$id")->fetch_assoc();
if (!$camp) { http_response_code(404); exit('Not found'); }
$mode = $_GET['mode'] ?? 'preview';
if ($mode === 'guest') $g = $conn->query("SELECT * FROM invite_guests WHERE id=" . (int)($_GET['guest'] ?? 0) . " AND campaign_id=$id")->fetch_assoc();
else $g = ['title_text' => mb_substr(trim($_GET['t'] ?? 'Hon.'), 0, 80), 'name' => mb_substr(trim($_GET['n'] ?? 'Jane Akello Odongo'), 0, 190), 'org' => mb_substr(trim($_GET['o'] ?? 'Guest of honour'), 0, 190), 'email' => '', 'token' => ''];
if (!$g) { http_response_code(404); exit('Guest not found'); }
if ($mode === 'template') {
    $f = inv_storage() . basename($camp['template_file']);
    if (!is_file($f)) { http_response_code(404); exit('No template'); }
    $bytes = file_get_contents($f);
} else {
    [$bytes, $note] = inv_pdf($camp, $g);
    if ($bytes === null) { http_response_code(404); exit($note); }
}
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="invitation-preview.pdf"');
header('Content-Length: ' . strlen($bytes));
header('X-Content-Type-Options: nosniff');
echo $bytes;
