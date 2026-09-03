<?php
/**
 * Database connection.
 *
 * Credentials are loaded from includes/db.config.php when that file exists
 * (use it on the live server — it is NOT committed to git). When it is
 * absent, the local XAMPP development defaults below are used.
 */
$configFile = __DIR__ . "/db.config.php";
if (is_file($configFile)) {
    $cfg = require $configFile;
} else {
    $cfg = [
        "host" => "localhost",
        "user" => "root",
        "pass" => "",
        "name" => "nufa_db",
    ];
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($cfg["host"], $cfg["user"], $cfg["pass"], $cfg["name"]);
if ($conn->connect_error) {
    http_response_code(503);
    die("Database connection failed.");
}
$conn->set_charset("utf8mb4");
