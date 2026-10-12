<?php
$adminNav = "sliders";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slide = $conn->query("SELECT * FROM hero_slides WHERE id=$id")->fetch_assoc();
if (!$slide) { header("Location: sliders.php"); exit; }
$adminTitle = "Edit Slide";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alt = trim($_POST['alt_text'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $image = $slide['image'];

    if (!empty($_FILES['image']['name'])) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = "Upload failed (error code " . $_FILES['image']['error'] . ").";
        } else {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $error = "Please upload a JPG, PNG or WEBP image.";
            } else {
                $destDir = __DIR__ . "/../assets/images/uploads/posts/";
                if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
                $fname = 'slide-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
                if (is_writable($destDir) && move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $fname)) {
                    $image = "uploads/posts/" . $fname;
                } else {
                    $error = "Could not save the new image — keeping the existing one.";
                }
            }
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE hero_slides SET alt_text=?, image=?, is_active=? WHERE id=?");
        $stmt->bind_param("ssii", $alt, $image, $isActive, $id);
        $stmt->execute();
        header("Location: sliders.php?updated=1");
        exit;
    }
    $slide['alt_text'] = $alt;
    $slide['is_active'] = $isActive;
}

include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Edit Slide</h2></div>
  <img class="admin-thumb" style="width:160px;height:160px;margin-bottom:20px" src="../assets/images/<?php echo htmlspecialchars($slide['image']); ?>" alt="">
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="field">
      <label>Alt text (for accessibility &amp; SEO)</label>
      <input type="text" name="alt_text" value="<?php echo htmlspecialchars($slide['alt_text']); ?>" placeholder="e.g. Guests arriving at NUFA26">
    </div>
    <div class="field">
      <label>Replace image (optional)</label>
      <input type="file" name="image" accept="image/*">
      <p class="admin-help">Leave empty to keep the current photo.</p>
    </div>
    <div class="field">
      <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" style="width:auto" <?php echo $slide['is_active']?'checked':''; ?>> Active (shown in the homepage collage)</label>
    </div>
    <button type="submit" class="admin-btn admin-btn-brand">Save Changes</button>
    <a href="sliders.php" class="admin-btn admin-btn-line">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
