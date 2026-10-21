<?php
$adminTitle = "Academy study library";
$adminNav = "academy";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/academy.php";
acad_install($conn);
$e = fn($v) => htmlspecialchars((string)$v); $error = '';
if (isset($_GET['delete'])) { $conn->query("DELETE FROM academy_resources WHERE id=" . (int)$_GET['delete']); header("Location: academy-resources.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE academy_resources SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: academy-resources.php"); exit; }
if (isset($_GET['verify'])) { $conn->query("UPDATE academy_resources SET verified_on=CURDATE() WHERE id=" . (int)$_GET['verify']); header("Location: academy-resources.php?verified=1"); exit; }
$id = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0); $t = trim($_POST['title'] ?? ''); $p = trim($_POST['provider'] ?? ''); $type = in_array($_POST['type'] ?? '', acad_resource_types(), true) ? $_POST['type'] : 'Article library';
    $cat = trim($_POST['category'] ?? ''); $lvl = in_array($_POST['level'] ?? '', ['All levels', 'Beginner', 'Intermediate', 'Advanced'], true) ? $_POST['level'] : 'All levels';
    $url = trim($_POST['url'] ?? ''); $d = trim($_POST['description'] ?? ''); $cost = trim($_POST['cost'] ?? 'Free'); $ver = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['verified_on'] ?? '') ? $_POST['verified_on'] : null; $act = isset($_POST['is_active']) ? 1 : 0;
    if ($t === '') $error = "Enter a title."; elseif (!preg_match('#^https?://#i', $url)) $error = "The link must start with https://";
    if (!$error) {
        if ($id) { $s = $conn->prepare("UPDATE academy_resources SET title=?,provider=?,type=?,category=?,level=?,url=?,description=?,cost=?,verified_on=?,is_active=? WHERE id=?"); $s->bind_param("sssssssssii", $t, $p, $type, $cat, $lvl, $url, $d, $cost, $ver, $act, $id); }
        else { $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM academy_resources")->fetch_row()[0]; $s = $conn->prepare("INSERT INTO academy_resources (title,provider,type,category,level,url,description,cost,verified_on,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("sssssssssii", $t, $p, $type, $cat, $lvl, $url, $d, $cost, $ver, $act, $o); }
        $s->execute(); header("Location: academy-resources.php?saved=1"); exit;
    }
}
$cur = $id ? $conn->query("SELECT * FROM academy_resources WHERE id=$id")->fetch_assoc() : null; if ($_SERVER['REQUEST_METHOD'] === 'POST') $cur = array_merge($cur ?: [], $_POST);
$f = fn($k, $d = '') => $e($cur[$k] ?? $d);
$rows = $conn->query("SELECT * FROM academy_resources ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
echo acad_admin_tabs('academy-resources.php');
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Removed.</div><?php endif; ?><?php if (isset($_GET['verified'])): ?><div class="admin-alert ok">Marked as checked today.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>
<div class="admin-card" id="res-form"><div class="admin-card-head"><h2><?php echo $id ? 'Edit resource' : 'Add a study resource'; ?></h2><span class="admin-help">Link only to legitimate sources and respect licences. Use ✓ in the list to mark a link as checked today.</span></div>
  <form method="post" class="admin-form"><input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Title</label><input name="title" required value="<?php echo $f('title'); ?>"></div><div class="field"><label>Provider</label><input name="provider" value="<?php echo $f('provider'); ?>"></div>
      <div class="field"><label>Type</label><select name="type"><?php foreach (acad_resource_types() as $t): ?><option <?php echo ($cur['type'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $e($t); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Subject</label><input name="category" list="rs-cats" value="<?php echo $f('category'); ?>"><datalist id="rs-cats"><?php foreach (acad_categories() as $c) echo '<option value="' . $e($c) . '">'; ?></datalist></div>
      <div class="field"><label>Level</label><select name="level"><?php foreach (['All levels', 'Beginner', 'Intermediate', 'Advanced'] as $l): ?><option <?php echo ($cur['level'] ?? 'All levels') === $l ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Cost</label><input name="cost" value="<?php echo $f('cost', 'Free'); ?>" placeholder="Free, Free & paid, Paid"></div>
      <div class="field"><label>Link</label><input type="url" name="url" required value="<?php echo $f('url'); ?>" placeholder="https://"></div><div class="field"><label>Last checked on</label><input type="date" name="verified_on" value="<?php echo $f('verified_on'); ?>"></div>
    </div>
    <div class="field"><label>Description</label><textarea name="description" rows="3"><?php echo $f('description'); ?></textarea></div>
    <label style="display:block;margin-bottom:12px"><input type="checkbox" name="is_active" <?php echo !$cur || !empty($cur['is_active']) ? 'checked' : ''; ?>> Show on the website</label>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $id ? 'Save changes' : 'Add resource'; ?></button><?php if ($id): ?><a class="admin-btn admin-btn-line" href="academy-resources.php">Cancel</a><?php endif; ?></div></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Study library (<?php echo count($rows); ?>)</h2></div>
  <table class="admin-table" data-export="Study library"><thead><tr><th>Resource</th><th>Type</th><th>Subject</th><th>Cost</th><th>Checked</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><b><a href="<?php echo $e($r['url']); ?>" target="_blank" rel="noopener"><?php echo $e($r['title']); ?></a></b><br><small><?php echo $e($r['provider']); ?></small></td><td><?php echo $e($r['type']); ?></td><td><?php echo $e($r['category']); ?></td><td><?php echo $e($r['cost']); ?></td><td><?php echo $r['verified_on'] ? $e(date('j M Y', strtotime($r['verified_on']))) : '—'; ?> <a href="academy-resources.php?verify=<?php echo $r['id']; ?>" title="Mark as checked today">✓</a></td>
    <td><a href="academy-resources.php?toggle=<?php echo $r['id']; ?>" class="admin-badge <?php echo $r['is_active'] ? '' : 'gray'; ?>"><?php echo $r['is_active'] ? 'Yes' : 'Hidden'; ?></a></td><td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-resources.php?edit=<?php echo $r['id']; ?>#res-form">Edit</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="academy-resources.php?delete=<?php echo $r['id']; ?>" data-confirm="Remove this resource?">Delete</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
