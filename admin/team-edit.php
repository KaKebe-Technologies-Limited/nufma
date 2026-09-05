<?php
$adminNav = "team";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$t = ['name'=>'', 'role'=>'', 'photo'=>'', 'facebook_url'=>'', 'x_url'=>'', 'instagram_url'=>'', 'linkedin_url'=>'', 'sort_order'=>0, 'is_active'=>1];
if ($id) {
    $r = $conn->query("SELECT * FROM team_members WHERE id=$id")->fetch_assoc();
    if ($r) $t = $r; else { header("Location: team.php"); exit; }
}
$adminTitle = $id ? "Edit Team Member" : "New Team Member";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $fb = trim($_POST['facebook_url'] ?? '');
    $x = trim($_POST['x_url'] ?? '');
    $ig = trim($_POST['instagram_url'] ?? '');
    $li = trim($_POST['linkedin_url'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $photo = $t['photo'];

    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'member';
            $dir = __DIR__ . "/../assets/images/uploads/team/";
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $fname = $slug . '-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $fname)) {
                $old = $t['photo'];
                $photo = "uploads/team/" . $fname;
                if ($old && strpos($old, 'uploads/team/') === 0) @unlink(__DIR__ . "/../assets/images/" . $old);
            }
        } else {
            $error = "Photo must be a JPG, PNG or WEBP file.";
        }
    }

    if (!$error && $name === '') $error = "Name is required.";
    if (!$error && $photo === '') $error = "Please upload a photo.";

    if (!$error) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE team_members SET name=?, role=?, photo=?, facebook_url=?, x_url=?, instagram_url=?, linkedin_url=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("sssssssiii", $name, $role, $photo, $fb, $x, $ig, $li, $sortOrder, $isActive, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO team_members (name, role, photo, facebook_url, x_url, instagram_url, linkedin_url, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssii", $name, $role, $photo, $fb, $x, $ig, $li, $sortOrder, $isActive);
        }
        $stmt->execute();
        header("Location: team.php?saved=1");
        exit;
    }
    $t = array_merge($t, compact('name','role','fb','x','ig','li','sortOrder','isActive','photo'));
    $t['facebook_url']=$fb; $t['x_url']=$x; $t['instagram_url']=$ig; $t['linkedin_url']=$li;
    $t['sort_order']=$sortOrder; $t['is_active']=$isActive;
}

$photoExists = $t['photo'] && file_exists(__DIR__ . "/../assets/images/" . $t['photo']);
include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo $id ? 'Edit Team Member' : 'New Team Member'; ?></h2></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($t['name']); ?>" required>
      </div>
      <div class="field">
        <label>Role</label>
        <input type="text" name="role" value="<?php echo htmlspecialchars($t['role']); ?>" placeholder="e.g. President" required>
      </div>
    </div>

    <div class="field">
      <label>Photo <?php echo $id ? '(leave empty to keep the current one)' : ''; ?></label>
      <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/*" <?php echo $id ? '' : 'required'; ?>>
      <?php if ($t['photo']): ?>
        <div class="admin-help" style="margin-top:10px">
          <?php if ($photoExists): ?>
            <img src="../assets/images/<?php echo htmlspecialchars($t['photo']); ?>" alt="" style="height:60px;width:60px;object-fit:cover;border-radius:50%;vertical-align:middle">
            <span style="margin-left:8px">Current: <?php echo htmlspecialchars($t['photo']); ?></span>
          <?php else: ?>
            <span style="color:var(--admin-danger,#c0392b)">Current file missing: <?php echo htmlspecialchars($t['photo']); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="admin-card-head" style="margin-top:8px"><h3 style="font-size:1rem">Social links (all optional)</h3></div>
    <div class="admin-form-grid">
      <div class="field">
        <label>Facebook URL</label>
        <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($t['facebook_url']); ?>" placeholder="https://">
      </div>
      <div class="field">
        <label>X (Twitter) URL</label>
        <input type="url" name="x_url" value="<?php echo htmlspecialchars($t['x_url']); ?>" placeholder="https://">
      </div>
    </div>
    <div class="admin-form-grid">
      <div class="field">
        <label>Instagram URL</label>
        <input type="url" name="instagram_url" value="<?php echo htmlspecialchars($t['instagram_url']); ?>" placeholder="https://">
      </div>
      <div class="field">
        <label>LinkedIn URL</label>
        <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($t['linkedin_url']); ?>" placeholder="https://">
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Sort order (lower shows first)</label>
        <input type="number" name="sort_order" value="<?php echo (int)$t['sort_order']; ?>">
      </div>
      <div class="field" style="display:flex;align-items:center;padding-top:28px">
        <label style="display:flex;align-items:center;gap:8px;margin:0">
          <input type="checkbox" name="is_active" style="width:auto" <?php echo $t['is_active'] ? 'checked' : ''; ?>>
          Show on the About page
        </label>
      </div>
    </div>

    <button type="submit" class="admin-btn admin-btn-brand"><?php echo $id ? 'Save Changes' : 'Add Team Member'; ?></button>
    <a href="team.php" class="admin-btn admin-btn-line">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
