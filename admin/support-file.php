<?php
// Streams a supporter's private proof-of-payment to a signed-in admin.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/support.php";

$id = (int)($_GET['id'] ?? 0);
$row = $conn->query("SELECT payment_proof FROM engagements WHERE id=$id")->fetch_assoc();
$name = $row ? basename($row['payment_proof']) : '';
$path = SUP_STORAGE_DIR . $name;
if (!$name || !is_file($path)) { http_response_code(404); exit("Not found"); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
header("Content-Type: $mime");
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=300");
readfile($path);
