<?php
require __DIR__ . "/includes/db.php";

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'news.php';

if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $stmt = $conn->prepare("INSERT IGNORE INTO subscribers (email) VALUES (?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $status = "subscribed";
} else {
    $status = "invalid";
}

$sep = (strpos($redirect, '?') === false) ? '?' : '&';
header("Location: " . $redirect . $sep . "subscribe=" . $status . "#top");
exit;
