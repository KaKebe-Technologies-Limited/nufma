<?php
// Anonymous counters: a film view or a tap on "Support this filmmaker" (once per visitor session per film).
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/films.php";
film_install($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$id = (int)($_POST['id'] ?? 0); $k = $_POST['k'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && in_array($k, ['view', 'support'], true) && mem_csrf_ok()) {
    $key = "film_$k"; $seen = $_SESSION[$key] ?? [];
    if (!in_array($id, $seen, true)) { $col = $k === 'view' ? 'views' : 'support_clicks'; $conn->query("UPDATE films SET $col = $col + 1 WHERE id=$id AND status='approved'"); $seen[] = $id; $_SESSION[$key] = $seen; }
}
http_response_code(204);
