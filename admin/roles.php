<?php
$adminTitle = "Roles & permissions";
$adminNav = "roles";
require __DIR__ . "/includes/auth.php";

$msg = ''; $err = '';
$modules = admin_modules();
$defaults = admin_default_roles();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $key = preg_replace('/[^a-z0-9_]/', '', $_POST['role_key'] ?? '');
    if ($act === 'save') {
        $perms = [];
        foreach ($modules as $m => $_) $perms[$m] = max(0, min(2, (int)($_POST['perm'][$m] ?? 0)));
        $label = mb_substr(trim($_POST['label'] ?? ''), 0, 80); $desc = mb_substr(trim($_POST['description'] ?? ''), 0, 400);
        if ($key === 'administrator') $err = "The Administrator role always has full access.";
        elseif ($label === '') $err = "Give the role a name.";
        else { $json = json_encode($perms); $s = $conn->prepare("UPDATE admin_roles SET label=?, description=?, perms=? WHERE role_key=?"); $s->bind_param("ssss", $label, $desc, $json, $key); $s->execute(); $msg = "Role “$label” saved."; }
    } elseif ($act === 'reset' && isset($defaults[$key]) && $key !== 'administrator') {
        [$label, $desc, $perms] = $defaults[$key]; $json = json_encode($perms);
        $s = $conn->prepare("UPDATE admin_roles SET label=?, description=?, perms=? WHERE role_key=?"); $s->bind_param("ssss", $label, $desc, $json, $key); $s->execute(); $msg = "Role reset to the recommended permissions.";
    } elseif ($act === 'add') {
        $label = mb_substr(trim($_POST['label'] ?? ''), 0, 80); $k = 'custom_' . substr(preg_replace('/[^a-z0-9]+/', '_', strtolower($label)), 0, 24) . '_' . substr(bin2hex(random_bytes(2)), 0, 3);
        if ($label === '') $err = "Give the new role a name.";
        else {
            $json = json_encode(array_fill_keys(array_keys($modules), 0)); $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM admin_roles")->fetch_row()[0]; $sys = 0;
            $s = $conn->prepare("INSERT INTO admin_roles (role_key, label, description, perms, sort_order, is_system) VALUES (?,?,'Custom role',?,?,?)"); $s->bind_param("sssii", $k, $label, $json, $o, $sys); $s->execute(); $msg = "Role added — tick what it may do below.";
        }
    } elseif ($act === 'delete') {
        $inUse = (int)$conn->query("SELECT COUNT(*) FROM admins WHERE role_key='" . $conn->real_escape_string($key) . "'")->fetch_row()[0];
        if (isset($defaults[$key])) $err = "The built-in roles can be edited but not deleted.";
        elseif ($inUse) $err = "That role is assigned to $inUse admin(s). Move them to another role first.";
        else { $conn->query("DELETE FROM admin_roles WHERE role_key='" . $conn->real_escape_string($key) . "'"); $msg = "Role deleted."; }
    }
}
$roles = roles_all($conn);
$count = []; foreach ($conn->query("SELECT role_key, COUNT(*) c FROM admins GROUP BY role_key")->fetch_all(MYSQLI_ASSOC) as $r) $count[$r['role_key']] = (int)$r['c'];
$e = fn($v) => htmlspecialchars((string)$v);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<div class="mem-tabs"><a href="users.php">Admin users</a><a href="roles.php" class="active">Roles &amp; permissions</a></div>
<p class="admin-help" style="margin-bottom:14px">Each role gets a level for every area: <b>No access</b> (hidden), <b>View only</b> (can open pages but not change anything) or <b>Manage</b> (view and change). Assign roles to people in <a href="users.php">Admin users</a>.</p>

<?php foreach ($roles as $r): $locked = $r['role_key'] === 'administrator'; ?>
<form method="post" class="admin-card admin-form role-card">
  <input type="hidden" name="role_key" value="<?php echo $e($r['role_key']); ?>">
  <div class="admin-card-head"><h2><?php echo $e($r['label']); ?> <small class="admin-help">· <?php echo (int)($count[$r['role_key']] ?? 0); ?> user(s)</small></h2>
    <span><?php if (!$locked): ?><button name="action" value="save" class="admin-btn admin-btn-brand admin-btn-sm">Save role</button>
      <?php if (isset($defaults[$r['role_key']])): ?><button name="action" value="reset" class="admin-btn admin-btn-line admin-btn-sm" onclick="return confirm('Reset this role to the recommended permissions?')">Reset to recommended</button>
      <?php else: ?><button name="action" value="delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this role?')">Delete</button><?php endif; ?><?php else: ?><span class="admin-badge">Full access</span><?php endif; ?></span></div>
  <div class="admin-form-grid"><div class="field"><label>Role name</label><input name="label" value="<?php echo $e($r['label']); ?>" <?php echo $locked ? 'readonly' : ''; ?>></div>
    <div class="field"><label>What this role is for</label><input name="description" value="<?php echo $e($r['description']); ?>" <?php echo $locked ? 'readonly' : ''; ?>></div></div>
  <div class="perm-grid">
    <?php foreach ($modules as $m => [$ml, $md]): $lv = $r['perm'][$m]; ?>
    <label class="perm perm--<?php echo $lv; ?>" title="<?php echo $e($md); ?>"><span><?php echo $e($ml); ?></span>
      <select name="perm[<?php echo $m; ?>]" <?php echo $locked ? 'disabled' : ''; ?> onchange="this.parentNode.className='perm perm--'+this.value">
        <?php foreach ([0, 1, 2] as $l): ?><option value="<?php echo $l; ?>" <?php echo $lv === $l ? 'selected' : ''; ?>><?php echo roles_level_label($l); ?></option><?php endforeach; ?>
      </select></label>
    <?php endforeach; ?>
  </div>
</form>
<?php endforeach; ?>

<form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="add">
  <div class="admin-card-head"><h2>Add a custom role</h2></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end"><div class="field" style="margin:0;flex:1;min-width:240px"><label>Role name</label><input name="label" placeholder="e.g. Communications Officer" required></div><button class="admin-btn admin-btn-brand">Add role</button></div>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
