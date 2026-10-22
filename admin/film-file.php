<?php
// Streams a submitted film to a signed-in admin for review (any status).
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/films.php";
film_install($conn);
$f = $conn->query("SELECT video_file FROM films WHERE id=" . (int)($_GET['id'] ?? 0))->fetch_assoc();
if (!$f || $f['video_file'] === '') { http_response_code(404); exit("Not found"); }
film_stream_file(film_storage() . basename($f['video_file']));
