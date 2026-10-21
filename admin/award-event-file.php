<?php
// Streams an applicant's private logo to a signed-in admin.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/award-events.php";
aev_install($conn);
$row = $conn->query("SELECT logo FROM award_event_apps WHERE id=" . (int)($_GET['id'] ?? 0))->fetch_assoc();
$name = $row ? basename($row['logo']) : ''; $path = aev_storage() . $name;
if (!$name || !is_file($path)) { http_response_code(404); exit("Not found"); }
header("Content-Type: " . (new finfo(FILEINFO_MIME_TYPE))->file($path)); header("Content-Length: " . filesize($path)); header("X-Content-Type-Options: nosniff"); header("Cache-Control: private, max-age=300");
readfile($path);
