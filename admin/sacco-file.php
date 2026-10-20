<?php
// Streams a SACCO applicant's private upload (photo / ID copy / proof of payment) to a signed-in admin with SACCO access.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/sacco-members.php";
sac_install($conn);
$id = (int)($_GET['id'] ?? 0);
$col = ['photo' => 'photo', 'id_copy' => 'id_copy', 'pay_proof' => 'pay_proof'][$_GET['f'] ?? ''] ?? null;
$row = $col ? $conn->query("SELECT `$col` AS f FROM sacco_applications WHERE id=$id")->fetch_assoc() : null;
$name = $row ? basename($row['f']) : '';
$path = sac_storage() . $name;
if (!$name || !is_file($path)) { http_response_code(404); exit("Not found"); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
header("Content-Type: $mime");
header("Content-Length: " . filesize($path));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=300");
readfile($path);
