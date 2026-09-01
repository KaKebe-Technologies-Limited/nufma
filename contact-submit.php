<?php
require __DIR__ . "/includes/db.php";

$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'contact.php';

if ($name && $email && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
    $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    $stmt->execute();
    $status = "sent";
} else {
    $status = "invalid";
}

$sep = (strpos($redirect, '?') === false) ? '?' : '&';
header("Location: " . $redirect . $sep . "contact=" . $status . "#top");
exit;
