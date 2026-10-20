<?php
$adminTitle = "Guilds";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/membership.php";

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $err = "Session expired — try again."; }
    else {
        $act = $_POST['action'] ?? '';
        $gid = (int)($_POST['id'] ?? 0);
        if ($act === 'add') {
            $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? '');
            if ($name === '') $err = "Enter a guild name.";
            else {
                $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 n FROM guilds")->fetch_assoc()['n'];
                $s = $conn->prepare("INSERT INTO guilds (name, description, sort_order) VALUES (?,?,?)");
                $s->bind_param("ssi", $name, $desc, $o); $s->execute(); $msg = "Guild added.";
            }
        } elseif ($act === 'save') {
            $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? ''); $o = (int)($_POST['sort_order'] ?? 0);
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($name === '') $err = "Guild name can't be empty.";
            else {
                $s = $conn->prepare("UPDATE guilds SET name=?, description=?, sort_order=?, is_active=? WHERE id=?");
                $s->bind_param("ssiii", $name, $desc, $o, $active, $gid); $s->execute(); $msg = "Guild updated.";
            }
        } elseif ($act === 'delete') {
            $n = (int)$conn->query("SELECT COUNT(*) c FROM members WHERE guild_id=$gid")->fetch_assoc()['c'];
            if ($n) $err = "This guild has $n member(s) — deactivate it instead of deleting.";
            else { $conn->query("DELETE FROM guilds WHERE id=$gid"); $msg = "Guild deleted."; }
        }
    }
}
$guilds = $conn->query("SELECT g.*, (SELECT COUNT(*) FROM members m WHERE m.guild_id=g.id) AS cnt FROM guilds g ORDER BY sort_order, name")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="members.php">Applications</a><a href="guilds.php" class="active">Guilds</a><a href="membership-settings.php">Membership settings</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a guild</h2><span class="admin-help">Appears in the “Guild you belong to” list on the membership form</span></div>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="add">
    <div class="admin-form-grid">
      <div class="field"><label>Name</label><input name="name" required placeholder="e.g. Animation &amp; Motion Graphics"></div>
      <div class="field"><label>Description (optional)</label><input name="description"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Add guild</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Guilds</h2></div>
  <?php foreach ($guilds as $g): ?>
  <form method="post" class="mem-guild-row"><?php echo mem_csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
    <input name="name" value="<?php echo htmlspecialchars($g['name']); ?>" required>
    <input name="description" value="<?php echo htmlspecialchars($g['description']); ?>" placeholder="Description">
    <input name="sort_order" type="number" value="<?php echo $g['sort_order']; ?>" title="Order" style="width:70px">
    <label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo $g['is_active'] ? 'checked' : ''; ?>> Active</label>
    <span class="admin-badge gray"><?php echo $g['cnt']; ?> members</span>
    <button name="action" value="save" class="admin-btn admin-btn-line admin-btn-sm">Save</button>
    <button name="action" value="delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this guild?')">Delete</button>
  </form>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
