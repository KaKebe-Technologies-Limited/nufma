<?php
session_start();
require __DIR__ . "/../../includes/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
