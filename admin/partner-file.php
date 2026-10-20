<?php
// Streams an applicant's private logo/photo to a signed-in admin.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/partner-apps.php";
pa_install($conn);
$row = $conn->query("SELECT logo FROM partner_applications WHERE id=" . (int)($_GET['id'] ?? 0))->fetch_assoc();
$name = $row ? basename($row['logo']) : '';
$path = pa_storage() . $name;
if (!$name || !is_file($path)) { http_response_code(404); exit("Not found"); }
header("Content-Type: " . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=300");
readfile($path);
