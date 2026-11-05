<?php
// Streams a renewal payment proof from private storage (admins only).
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/renewals.php";
$id = (int)($_GET['id'] ?? 0);
$r = $conn->query("SELECT payment_proof FROM member_renewals WHERE id=$id")->fetch_assoc();
$path = $r && $r['payment_proof'] ? MEM_STORAGE_DIR . basename($r['payment_proof']) : '';
if (!$path || !is_file($path)) { http_response_code(404); exit('File not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
while (ob_get_level()) ob_end_clean();
header('Content-Type: ' . $mime); header('Content-Length: ' . filesize($path)); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store');
readfile($path);
