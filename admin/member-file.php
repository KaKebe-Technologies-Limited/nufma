<?php
// Streams a member's private upload (passport photo / proof of payment) to a logged-in admin.
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/membership.php";

$id = (int)($_GET['id'] ?? 0);
$which = ($_GET['f'] ?? '') === 'proof' ? 'payment_proof' : 'photo';
$row = $conn->query("SELECT photo, payment_proof FROM members WHERE id=$id")->fetch_assoc();
$name = $row ? basename($row[$which]) : '';
$path = MEM_STORAGE_DIR . $name;
if (!$name || !is_file($path)) { http_response_code(404); exit("Not found"); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
header("Content-Type: $mime");
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=300");
readfile($path);
