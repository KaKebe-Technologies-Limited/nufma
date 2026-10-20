<?php
$adminNav = "partners";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partner = ['name'=>'','logo'=>'','website'=>'','tier'=>'Partner','relationship'=>'current','category'=>($_GET['cat'] ?? ''),'sort_order'=>0,'is_active'=>1];
if ($id) {
    $r = $conn->query("SELECT * FROM partners WHERE id=$id")->fetch_assoc();
    if ($r) $partner = $r;
    else { header("Location: partners.php"); exit; }
}
$adminTitle = $id ? "Edit Partner" : "New Partner";
require_once __DIR__ . "/../includes/settings.php";
$pcats = nufa_list(nufa_site_settings($conn), 'list_partner_categories');
if (!empty($partner['category']) && !in_array($partner['category'], $pcats, true)) $pcats[] = $partner['category'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $tier     = trim($_POST['tier'] ?? '');
    $website  = trim($_POST['website'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $relationship = ($_POST['relationship'] ?? 'current') === 'previous' ? 'previous' : 'current';
    $category = trim($_POST['category'] ?? '');
    $logo     = $partner['logo'];

    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','svg'])) {
            $slug  = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-')) ?: 'partner';
            $fname = $slug . '-' . time() . '.' . $ext;
            $dir   = __DIR__ . "/../assets/images/uploads/partners/";
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fname)) {
                $old = $partner['logo'];
                $logo = "uploads/partners/" . $fname;
                // remove the previous file only if it was an admin upload
                if ($old && strpos($old, 'uploads/partners/') === 0) {
                    @unlink(__DIR__ . "/../assets/images/" . $old);
                }
            } else {
                $error = "Could not save the uploaded file. Check folder permissions on assets/images/uploads/partners/.";
            }
        } else {
            $error = "Logo must be a JPG, PNG, WEBP or SVG file.";
        }
    }

    if (!$error && $name === '') $error = "Organisation name is required.";

    if (!$error) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE partners SET name=?, logo=?, website=?, tier=?, relationship=?, category=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->bind_param("ssssssiii", $name, $logo, $website, $tier, $relationship, $category, $sortOrder, $isActive, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO partners (name, logo, website, tier, relationship, category, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssii", $name, $logo, $website, $tier, $relationship, $category, $sortOrder, $isActive);
        }
        $stmt->execute();
        header("Location: partners.php?saved=1");
        exit;
    }

    $partner = array_merge($partner, [
        'name' => $name, 'tier' => $tier, 'website' => $website,
        'sort_order' => $sortOrder, 'is_active' => $isActive, 'logo' => $logo, 'relationship' => $relationship, 'category' => $category,
    ]);
}

$logoExists = $partner['logo'] && file_exists(__DIR__ . "/../assets/images/" . $partner['logo']);
include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo $id ? 'Edit Partner' : 'New Partner'; ?></h2></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">

    <div class="admin-form-grid">
      <div class="field">
        <label>Organisation name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($partner['name']); ?>" required>
      </div>
      <div class="field">
        <label>Tier / role</label>
        <input type="text" name="tier" value="<?php echo htmlspecialchars($partner['tier']); ?>" placeholder="e.g. Media Partner">
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Logo <?php echo $id ? '(leave empty to keep the current one)' : '(optional)'; ?></label>
        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg,image/*"><span class="admin-help">Optional — without a logo the partner appears as a name and role tile.</span>
        <?php if ($partner['logo']): ?>
          <div class="admin-help" style="margin-top:10px">
            <?php if ($logoExists): ?>
              <img src="../assets/images/<?php echo htmlspecialchars($partner['logo']); ?>" alt=""
                   style="height:48px;max-width:160px;object-fit:contain;background:var(--cream);padding:6px;border-radius:6px;vertical-align:middle">
              <span style="margin-left:8px">Current: <?php echo htmlspecialchars($partner['logo']); ?></span>
            <?php else: ?>
              <span style="color:var(--admin-danger,#c0392b)">Current file missing: <?php echo htmlspecialchars($partner['logo']); ?> — upload a replacement.</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="field">
        <label>Website (optional)</label>
        <input type="url" name="website" value="<?php echo htmlspecialchars($partner['website'] ?? ''); ?>" placeholder="https://">
      </div>
    </div>
    <div class="admin-form-grid">
      <div class="field"><label>Category</label><select name="category"><option value="">— none —</option><?php foreach (array_filter(array_unique(array_merge($pcats, [$partner['category'] ?? '']))) as $c): ?><option <?php echo ($partner['category'] ?? '') === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select><span class="admin-help">Groups the partner wall. Edit the list under <a href="lists.php">Form options</a>.</span></div>
      <div class="field"><label>Relationship</label><select name="relationship"><option value="current"<?php echo ($partner['relationship'] ?? 'current') === 'current' ? ' selected' : ''; ?>>Current partner</option><option value="previous"<?php echo ($partner['relationship'] ?? '') === 'previous' ? ' selected' : ''; ?>>Previous supporter</option></select><span class="admin-help">Current partners and previous supporters are shown separately on the Partner page.</span></div>
    </div>


    <div class="admin-form-grid">
      <div class="field">
        <label>Sort order (lower shows first)</label>
        <input type="number" name="sort_order" value="<?php echo (int)$partner['sort_order']; ?>">
      </div>
      <div class="field" style="display:flex;align-items:center;padding-top:28px">
        <label style="display:flex;align-items:center;gap:8px;margin:0">
          <input type="checkbox" name="is_active" style="width:auto" <?php echo $partner['is_active'] ? 'checked' : ''; ?>>
          Show on the website
        </label>
      </div>
    </div>

    <button type="submit" class="admin-btn admin-btn-brand"><?php echo $id ? 'Save Changes' : 'Add Partner'; ?></button>
    <a href="partners.php" class="admin-btn admin-btn-line">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
