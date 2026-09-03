<?php
/**
 * Database connection.
 *
 * Resolution order:
 *   1. includes/db.config.php  — if present, it wins (git-ignored; use it to
 *      override credentials on any environment without editing this file).
 *   2. Auto-detected environment — local XAMPP vs. live server.
 */
$configFile = __DIR__ . "/db.config.php";

if (is_file($configFile)) {
    $cfg = require $configFile;
} else {
    $path = str_replace("\\", "/", __DIR__);
    $httpHost = $_SERVER["HTTP_HOST"] ?? "";
    $isLocal =
        strpos($path, "/xampp/") !== false ||
        strpos($path, "/htdocs/") !== false ||
        $httpHost === "localhost" ||
        $httpHost === "127.0.0.1" ||
        strncmp($httpHost, "localhost:", 10) === 0 ||
        strncmp($httpHost, "127.0.0.1:", 10) === 0;

    if ($isLocal) {
        // Local development (XAMPP)
        $cfg = [
            "host" => "localhost",
            "user" => "root",
            "pass" => "",
            "name" => "nufa_db",
        ];
    } else {
        // Live server
        $cfg = [
            "host" => "localhost",
            "user" => "u850523537_NUFAUser2",
            "pass" => "bodA37@ds",
            "name" => "u850523537_NUFADB2",
        ];
    }
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($cfg["host"], $cfg["user"], $cfg["pass"], $cfg["name"]);
if ($conn->connect_error) {
    http_response_code(503);
    die("Database connection failed.");
}
$conn->set_charset("utf8mb4");
