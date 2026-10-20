<?php
$adminTitle = "Admin users";
$adminNav = "users";
require __DIR__ . "/includes/auth.php";

$me = (int)$_SESSION['admin_id'];
$roleList = roles_all($conn); $roleKeys = array_column($roleList, 'role_key');
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $uid = (int)($_POST['id'] ?? 0);
    if ($act === 'add') {
        $user = strtolower(trim($_POST['username'] ?? '')); $name = trim($_POST['display_name'] ?? ''); $pw = $_POST['password'] ?? '';
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $user)) $err = "Username: 3–40 characters, letters, numbers, dot, dash or underscore.";
        elseif ($name === '') $err = "Enter a display name.";
        elseif (!in_array($_POST['role_key'] ?? '', $roleKeys, true)) $err = "Choose a role for this person.";
        elseif (strlen($pw) < 8) $err = "Password must be at least 8 characters.";
        elseif ($conn->query("SELECT id FROM admins WHERE username='" . $conn->real_escape_string($user) . "'")->num_rows) $err = "That username is already taken.";
        else {
            $h = password_hash($pw, PASSWORD_DEFAULT);
            $s = $conn->prepare("INSERT INTO admins (username, password_hash, display_name, role_key) VALUES (?,?,?,?)");
            $s->bind_param("ssss", $user, $h, $name, $_POST['role_key']); $s->execute();
            $msg = "Admin “$user” created. Share the password with them securely and ask them to change it.";
        }
    } elseif ($act === 'role') {
        $rk = $_POST['role_key'] ?? ''; $cur = $conn->query("SELECT role_key FROM admins WHERE id=$uid")->fetch_assoc();
        $admins = (int)$conn->query("SELECT COUNT(*) FROM admins WHERE role_key='administrator'")->fetch_row()[0];
        if (!in_array($rk, $roleKeys, true) || !$cur) $err = "Choose a valid role.";
        elseif ($uid === $me) $err = "You can't change your own role — ask another administrator.";
        elseif ($cur['role_key'] === 'administrator' && $rk !== 'administrator' && $admins <= 1) $err = "There must always be at least one Administrator.";
        else { $s = $conn->prepare("UPDATE admins SET role_key=? WHERE id=?"); $s->bind_param("si", $rk, $uid); $s->execute(); $msg = "Role updated."; }
    } elseif ($act === 'reset') {
        $pw = $_POST['password'] ?? '';
        if (strlen($pw) < 8) $err = "Password must be at least 8 characters.";
        else {
            $h = password_hash($pw, PASSWORD_DEFAULT);
            $s = $conn->prepare("UPDATE admins SET password_hash=? WHERE id=?");
            $s->bind_param("si", $h, $uid); $s->execute();
            $msg = "Password reset.";
        }
    } elseif ($act === 'delete') {
        $total = (int)$conn->query("SELECT COUNT(*) c FROM admins")->fetch_assoc()['c'];
        $tgt = $conn->query("SELECT role_key FROM admins WHERE id=$uid")->fetch_assoc(); $nAdm = (int)$conn->query("SELECT COUNT(*) FROM admins WHERE role_key='administrator'")->fetch_row()[0];
        if ($tgt && $tgt['role_key'] === 'administrator' && $nAdm <= 1) $err = "You can't delete the last Administrator.";
        elseif ($uid === $me) $err = "You can't delete your own account while signed in.";
        elseif ($total <= 1) $err = "You can't delete the last admin.";
        else { $conn->query("DELETE FROM admins WHERE id=$uid"); $msg = "Admin deleted."; }
    }
}
$admins = $conn->query("SELECT * FROM admins ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$lastLogin = [];   // looked up separately to avoid collation clashes between tables
$ll = $conn->query("SELECT username, MAX(created_at) t FROM admin_login_log WHERE success=1 GROUP BY username");
while ($row = $ll->fetch_assoc()) $lastLogin[strtolower($row['username'])] = $row['t'];
foreach ($admins as &$a) $a['last_login'] = $lastLogin[strtolower($a['username'])] ?? null;
unset($a);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="mem-tabs"><a href="users.php" class="active">Admin users</a><a href="roles.php">Roles &amp; permissions</a></div>
<div class="admin-card">
  <div class="admin-card-head"><h2>Admins</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="account.php">My account</a></div>
  <table class="admin-table">
    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Last sign-in</th><th>Reset password</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($admins as $a): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($a['display_name']); ?></b><?php if ((int)$a['id'] === $me): ?> <span class="admin-badge">you</span><?php endif; ?></td>
        <td><?php echo htmlspecialchars($a['username']); ?></td>
        <td><?php if ((int)$a['id'] === $me): ?><span class="admin-badge"><?php echo htmlspecialchars(admin_role_label()); ?></span><?php else: ?><form method="post" class="admin-form" style="display:flex;gap:6px"><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><select name="role_key" onchange="this.form.submit()" style="padding:6px 8px;max-width:190px"><?php foreach ($roleList as $rl): ?><option value="<?php echo htmlspecialchars($rl['role_key']); ?>" <?php echo ($a['role_key'] ?? 'administrator') === $rl['role_key'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($rl['label']); ?></option><?php endforeach; ?></select></form><?php endif; ?></td>
        <td><?php echo $a['last_login'] ? date('d M Y H:i', strtotime($a['last_login'])) : '—'; ?></td>
        <td>
          <form method="post" class="admin-form" style="display:flex;gap:6px;align-items:center">
            <input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?php echo $a['id']; ?>">
            <input type="password" name="password" placeholder="New password" minlength="8" required style="padding:7px 10px;max-width:170px" autocomplete="new-password">
            <button class="admin-btn admin-btn-line admin-btn-sm">Reset</button>
          </form>
        </td>
        <td>
          <?php if ((int)$a['id'] !== $me): ?>
          <form method="post" onsubmit="return confirm('Delete this admin account?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><button class="admin-btn admin-btn-danger admin-btn-sm">Delete</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add an admin</h2><span class="admin-help">Pick the role that matches what they do — it decides what they can see and change.</span></div>
  <form method="post" class="admin-form" autocomplete="off">
    <input type="hidden" name="action" value="add">
    <div class="admin-form-grid">
      <div class="field"><label>Display name</label><input name="display_name" required></div>
      <div class="field"><label>Username</label><input name="username" required pattern="[A-Za-z0-9._-]{3,40}"></div>
      <div class="field"><label>Role</label><select name="role_key" required><?php foreach ($roleList as $rl): ?><option value="<?php echo htmlspecialchars($rl['role_key']); ?>"><?php echo htmlspecialchars($rl['label']); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Temporary password (min. 8)</label><input type="password" name="password" minlength="8" required autocomplete="new-password"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Create admin</button>
  </form>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
