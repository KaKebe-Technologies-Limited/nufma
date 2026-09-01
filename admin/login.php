<?php
session_start();
require __DIR__ . "/../includes/db.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $u);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin && password_verify($p, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['display_name'];
        header("Location: index.php");
        exit;
    }
    $error = "Incorrect username or password.";
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — NUFA</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <img src="../assets/images/logo/nufa-logo.png" alt="NUFA">
    <h1>NUFA Admin</h1>
    <p>Sign in to manage the site</p>
    <?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
      </div>
      <button type="submit" class="btn btn-brand" style="width:100%">Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
