<?php
$adminTitle = "My account";
$adminNav = "users";
require __DIR__ . "/includes/auth.php";

$id = (int)$_SESSION['admin_id'];
$me = $conn->query("SELECT * FROM admins WHERE id=$id")->fetch_assoc();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'profile') {
        $name = trim($_POST['display_name'] ?? '');
        if ($name === '') $err = "Enter your name.";
        else {
            $s = $conn->prepare("UPDATE admins SET display_name=? WHERE id=?");
            $s->bind_param("si", $name, $id); $s->execute();
            $_SESSION['admin_name'] = $name; $me['display_name'] = $name;
            $msg = "Profile updated.";
        }
    } elseif ($act === 'password') {
        $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $conf = $_POST['confirm'] ?? '';
        if (!password_verify($cur, $me['password_hash'])) $err = "Your current password is incorrect.";
        elseif (strlen($new) < 8) $err = "The new password must be at least 8 characters.";
        elseif ($new !== $conf) $err = "The new passwords don't match.";
        elseif ($new === $cur) $err = "Choose a password different from the current one.";
        else {
            $h = password_hash($new, PASSWORD_DEFAULT);
            $s = $conn->prepare("UPDATE admins SET password_hash=? WHERE id=?");
            $s->bind_param("si", $h, $id); $s->execute();
            session_regenerate_id(true);
            $msg = "Password changed.";
        }
    }
}
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="admin-form-grid" style="align-items:start">
  <div class="admin-card">
    <div class="admin-card-head"><h2>Profile</h2></div>
    <form method="post" class="admin-form">
      <input type="hidden" name="action" value="profile">
      <div class="field"><label>Username</label><input value="<?php echo htmlspecialchars($me['username']); ?>" disabled></div>
      <div class="field"><label>Display name</label><input name="display_name" value="<?php echo htmlspecialchars($me['display_name']); ?>" required></div>
      <button class="admin-btn admin-btn-brand">Save profile</button>
    </form>
  </div>
  <div class="admin-card">
    <div class="admin-card-head"><h2>Change password</h2></div>
    <form method="post" class="admin-form" autocomplete="off">
      <input type="hidden" name="action" value="password">
      <div class="field"><label>Current password</label><input type="password" name="current" required autocomplete="current-password"></div>
      <div class="field"><label>New password (min. 8 characters)</label><input type="password" name="new" minlength="8" required autocomplete="new-password"></div>
      <div class="field"><label>Confirm new password</label><input type="password" name="confirm" minlength="8" required autocomplete="new-password"></div>
      <button class="admin-btn admin-btn-brand">Change password</button>
    </form>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
