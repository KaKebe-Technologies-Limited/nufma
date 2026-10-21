<?php
$adminTitle = "Distinguished guests";
$adminNav = "guests";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/guests.php";
guests_install($conn);
$cats = guest_categories(); $edOpts = guest_edition_options($conn);

if (isset($_GET['delete'])) { $id = (int)$_GET['delete']; $r = $conn->query("SELECT photo FROM vip_guests WHERE id=$id")->fetch_assoc(); if ($r && strpos($r['photo'], 'uploads/guests/') === 0) @unlink(__DIR__ . "/../assets/images/" . $r['photo']); $conn->query("DELETE FROM vip_guests WHERE id=$id"); header("Location: guests.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE vip_guests SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: guests.php"); exit; }

$error = ''; $id = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? ''); $cat = in_array($_POST['category'] ?? '', $cats, true) ? $_POST['category'] : 'Distinguished guest';
    $title = trim($_POST['title'] ?? ''); $org = trim($_POST['organisation'] ?? ''); $profile = trim($_POST['profile'] ?? '');
    $picked = array_values(array_intersect($edOpts, (array)($_POST['eds'] ?? [])));
    foreach (array_filter(array_map('trim', explode(',', $_POST['eds_other'] ?? ''))) as $x) $picked[] = mb_substr($x, 0, 60);
    $eds = implode(', ', array_unique($picked));
    $feat = isset($_POST['is_featured']) ? 1 : 0; $act = isset($_POST['is_active']) ? 1 : 0;
    $old = $id ? ($conn->query("SELECT photo FROM vip_guests WHERE id=$id")->fetch_assoc()['photo'] ?? '') : ''; $photo = $old;
    if ($name === '') $error = "Enter the guest's name.";
    if (!$error && !empty($_FILES['photo']['name'])) {
        [$fn, $er] = mem_store_upload($_FILES['photo'], MEM_IMG_TYPES, 6 * 1048576, 'guest', GUEST_DIR);
        if ($er) $error = "Photo: $er"; else { $photo = 'uploads/guests/' . $fn; if (strpos($old, 'uploads/guests/') === 0) @unlink(__DIR__ . "/../assets/images/" . $old); }
    }
    if (!$error && isset($_POST['remove_photo']) && empty($_FILES['photo']['name'])) { if (strpos($old, 'uploads/guests/') === 0) @unlink(__DIR__ . "/../assets/images/" . $old); $photo = ''; }
    if (!$error) {
        if ($id) { $s = $conn->prepare("UPDATE vip_guests SET name=?,category=?,title=?,organisation=?,photo=?,editions=?,profile=?,is_featured=?,is_active=? WHERE id=?"); $s->bind_param("sssssssiii", $name, $cat, $title, $org, $photo, $eds, $profile, $feat, $act, $id); }
        else { $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM vip_guests")->fetch_row()[0]; $s = $conn->prepare("INSERT INTO vip_guests (name,category,title,organisation,photo,editions,profile,is_featured,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("sssssssiii", $name, $cat, $title, $org, $photo, $eds, $profile, $feat, $act, $o); }
        $s->execute(); header("Location: guests.php?saved=1"); exit;
    }
}
$cur = $id ? $conn->query("SELECT * FROM vip_guests WHERE id=$id")->fetch_assoc() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') $cur = array_merge($cur ?: [], $_POST, ['editions' => implode(', ', (array)($_POST['eds'] ?? []))]);
$f = fn($k, $d = '') => htmlspecialchars((string)($cur[$k] ?? $d));
$curEds = $cur ? guest_editions($cur) : [];
$rows = $conn->query("SELECT * FROM vip_guests ORDER BY is_featured DESC, sort_order, name")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="guests.php" class="active">Distinguished guests</a><a href="../guests.php" target="_blank">View on the website ↗</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card" id="guest-form">
  <div class="admin-card-head"><h2><?php echo $id ? 'Edit guest' : 'Add a distinguished guest'; ?></h2><span class="admin-help">Only list guests who attended and whose profile you have permission to publish. Profiles must be accurate — never invented.</span></div>
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Full name</label><input name="name" required value="<?php echo $f('name'); ?>"></div>
      <div class="field"><label>Type</label><select name="category"><?php foreach ($cats as $c): ?><option <?php echo ($cur['category'] ?? 'Distinguished guest') === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Title / position</label><input name="title" value="<?php echo $f('title'); ?>" placeholder="e.g. Minister, Director, Mayor"></div>
      <div class="field"><label>Organisation</label><input name="organisation" value="<?php echo $f('organisation'); ?>"></div>
      <div class="field"><label>Photo (JPG, PNG, WebP — portrait works best)</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($cur['photo'])): ?><img src="../assets/images/<?php echo $f('photo'); ?>" alt="" style="width:84px;height:104px;object-fit:cover;border-radius:10px;margin-top:8px"><label class="mem-check"><input type="checkbox" name="remove_photo"> Remove photo</label><?php endif; ?></div>
    </div>
    <div class="field"><label>Edition(s) attended</label>
      <div style="display:flex;gap:16px;flex-wrap:wrap"><?php foreach ($edOpts as $x): ?><label class="mem-check"><input type="checkbox" name="eds[]" value="<?php echo htmlspecialchars($x); ?>" <?php echo in_array($x, $curEds, true) ? 'checked' : ''; ?>> <?php echo htmlspecialchars($x); ?></label><?php endforeach; ?></div>
      <input name="eds_other" placeholder="Other editions or events, separated by commas (optional)" style="margin-top:8px" value="<?php echo htmlspecialchars(implode(', ', array_diff($curEds, $edOpts))); ?>"></div>
    <div class="field"><label>Profile</label><textarea name="profile" rows="7" placeholder="A short, accurate biography and why they matter to the film industry."><?php echo $f('profile'); ?></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin:6px 0 14px"><label><input type="checkbox" name="is_featured" <?php echo !empty($cur['is_featured']) ? 'checked' : ''; ?>> Feature at the top</label><label><input type="checkbox" name="is_active" <?php echo !$cur || !empty($cur['is_active']) ? 'checked' : ''; ?>> Show on the website</label></div>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $id ? 'Save changes' : 'Add guest'; ?></button><?php if ($id): ?><a class="admin-btn admin-btn-line" href="guests.php">Cancel</a><?php endif; ?></div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>All guests (<?php echo count($rows); ?>)</h2></div>
  <?php if (!$rows): ?><p class="admin-help">No guests yet — the public page shows “coming soon”.</p><?php else: ?>
  <table class="admin-table" data-export="Distinguished guests"><thead><tr><th>Guest</th><th>Type</th><th>Title / organisation</th><th>Editions</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
  <tr><td><div class="ax-title-cell"><?php if (guest_photo_ok($r)): ?><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($r['photo']); ?>" alt=""><?php endif; ?><div><b><?php echo htmlspecialchars($r['name']); ?></b><?php echo $r['is_featured'] ? ' <span class="admin-badge">★</span>' : ''; ?></div></div></td>
    <td><?php echo htmlspecialchars($r['category']); ?></td><td><?php echo htmlspecialchars(trim($r['title'] . ' ' . $r['organisation']) ?: '—'); ?></td><td><?php echo htmlspecialchars($r['editions'] ?: '—'); ?></td>
    <td><a href="guests.php?toggle=<?php echo $r['id']; ?>" class="admin-badge <?php echo $r['is_active'] ? '' : 'gray'; ?>"><?php echo $r['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
    <td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="guests.php?edit=<?php echo $r['id']; ?>#guest-form">Edit</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="guests.php?delete=<?php echo $r['id']; ?>" data-confirm="Remove this guest?">Delete</a></td></tr>
  <?php endforeach; ?></tbody></table><?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
