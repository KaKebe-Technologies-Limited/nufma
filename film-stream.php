<?php
// Public stream of an APPROVED film (supports seeking). Unapproved films are never served here.
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/films.php";
film_install($conn);
$f = $conn->query("SELECT video_file, status, source_type FROM films WHERE id=" . (int)($_GET['id'] ?? 0))->fetch_assoc();
if (!$f || $f['status'] !== 'approved' || $f['source_type'] !== 'mp4' || $f['video_file'] === '') { http_response_code(404); exit("Not found"); }
film_stream_file(film_storage() . basename($f['video_file']));
