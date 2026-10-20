<?php
// Inline image upload for the rich-text editor. Returns JSON {path} (relative to the site root) or {error}.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST only']); exit; }
[$fn, $err] = mem_store_upload($_FILES['image'] ?? [], MEM_IMG_TYPES, 6 * 1048576, 'inline', __DIR__ . '/../assets/images/uploads/posts/');
echo $err ? json_encode(['error' => $err]) : json_encode(['path' => 'assets/images/uploads/posts/' . $fn]);
