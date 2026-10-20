<?php
require_once __DIR__ . "/includes/security.php";
admin_session_start();
require_once __DIR__ . "/../includes/db.php";
admin_security_install($conn);

// Where to go after signing in — only plain admin page names, never external URLs.
$next = basename($_GET['next'] ?? $_POST['next'] ?? '');
if (!preg_match('/^[a-z0-9-]+\.php$/i', $next) || $next === 'login.php' || !is_file(__DIR__ . '/' . $next)) $next = 'index.php';

if (isset($_SESSION['admin_id'])) { header("Location: $next"); exit; }

$error = '';
$notice = isset($_GET['timeout']) ? "You were signed out after a period of inactivity. Please sign in again." : (isset($_GET['out']) ? "You have been signed out." : '');
$u = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (!admin_token_ok($_POST['csrf'] ?? null)) {
        $error = "Your sign-in page expired. Please try again.";
    } elseif ($wait = admin_lockout_remaining($conn, $u)) {
        $error = "Too many failed attempts. Try again in " . ceil($wait / 60) . " minute(s).";
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $u);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        // Always run a hash check so response time doesn't reveal whether the user exists.
        $hash = $admin['password_hash'] ?? '$2y$10$usesomesillystringforsaltuQ3g1l1yZk8wq8nQ8xq0b0v0m8n8m8n8m8n8';
        $ok = password_verify($p, $hash) && $admin;
        admin_log_attempt($conn, $u, $ok);
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_name'] = $admin['display_name'] ?: 'Admin';
            $_SESSION['last_active'] = time();
            $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                $nh = password_hash($p, PASSWORD_DEFAULT);
                $r = $conn->prepare("UPDATE admins SET password_hash=? WHERE id=?");
                $r->bind_param("si", $nh, $admin['id']); $r->execute();
            }
            header("Location: $next"); exit;
        }
        $left = ADMIN_MAX_FAILS - (int)$conn->query("SELECT COUNT(*) c FROM admin_login_log WHERE success=0 AND created_at > (NOW() - INTERVAL " . ADMIN_LOCK_MINUTES . " MINUTE) AND ip='" . $conn->real_escape_string(admin_client_ip()) . "'")->fetch_assoc()['c'];
        $error = "Incorrect username or password." . ($left > 0 && $left <= 2 ? " $left attempt(s) left before a temporary lock." : "");
    }
}
$token = admin_token();
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — NUFA Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
</head>
<body class="login-body">
<main class="login-split">
  <aside class="login-art">
    <img src="../assets/images/gallery/2026/nufa26-04.jpg" alt="" aria-hidden="true">
    <div class="login-art-in">
      <div class="login-brand"><img src="../assets/images/logo/nufa-logo.png" alt=""><span>NUFA</span></div>
      <h2>Run the association from one place.</h2>
      <p>Manage news, members, awards, partners and everything your visitors see on the NUFA website.</p>
    </div>
  </aside>

  <section class="login-pane">
    <form method="post" class="login-form" autocomplete="on">
      <input type="hidden" name="csrf" value="<?php echo $token; ?>">
      <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
      <img class="login-mark" src="../assets/images/logo/nufa-logo.png" alt="NUFA">
      <h1>Welcome back</h1>
      <p class="login-sub">Sign in to the NUFA admin dashboard.</p>

      <?php if ($notice && !$error): ?><div class="admin-alert ok"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-alert err" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($u); ?>" required autofocus autocomplete="username" autocapitalize="none">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <div class="pw-wrap">
          <input id="password" name="password" type="password" required autocomplete="current-password">
          <button type="button" class="pw-toggle" aria-label="Show password" onclick="var i=document.getElementById('password');var s=i.type==='password';i.type=s?'text':'password';this.textContent=s?'Hide':'Show'">Show</button>
        </div>
      </div>
      <button type="submit" class="btn btn-brand" style="width:100%">Sign in</button>
      <p class="login-foot"><a href="../index.php">&larr; Back to the website</a></p>
    </form>
  </section>
</main>
</body>
</html>
