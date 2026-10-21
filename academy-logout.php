<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
acad_session();
if (isset($_GET['t']) && isset($_SESSION['mem_csrf']) && hash_equals($_SESSION['mem_csrf'], (string)$_GET['t'])) { unset($_SESSION['learner_id']); session_regenerate_id(true); header("Location: academy-login.php?out=1"); exit; }
header("Location: academy-portal.php"); exit;
