<?php
$adminTitle = "Hero Images";
$adminNav = "sliders";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/image-advice.php";

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM hero_slides WHERE id=" . (int)$_GET['delete']);
    header("Location: sliders.php?deleted=1");
    exit;
}
if (isset($_GET['toggle'])) {
    $conn->query("UPDATE hero_slides SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']);
    header("Location: sliders.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['image']['name'])) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = "Upload failed (error code " . $_FILES['image']['error'] . "). Try a smaller file.";
        } else {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $error = "Please upload a JPG, PNG or WEBP image.";
            } else {
                [$advErr, $advWarn] = hero_image_advice($_FILES['image']['tmp_name'], $_FILES['image']['size']);
                if ($advErr) $error = $advErr;
                else {
                $destDir = __DIR__ . "/../assets/images/uploads/posts/";
                if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
                $fname = 'slide-' . time() . '-' . mt_rand(1000,9999) . '.' . $ext;
                $dest = $destDir . $fname;
                if (is_writable($destDir) && move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $alt = trim($_POST['alt_text'] ?? '');
                    $image = "uploads/posts/" . $fname;
                    $stmt = $conn->prepare("INSERT INTO hero_slides (image, alt_text, sort_order) VALUES (?,?,(SELECT m FROM (SELECT COALESCE(MAX(sort_order),0)+1 m FROM hero_slides) t))");
                    $stmt->bind_param("ss", $image, $alt);
                    $stmt->execute();
                    if ($advWarn) $_SESSION['hero_warn'] = $advWarn;
                    header("Location: sliders.php?added=1");
                    exit;
                } else {
                    $error = "Could not save the file — check that assets/images/uploads/posts/ is writable on the server.";
                }
                }
            }
        }
    } else {
        $error = "Please choose an image to upload.";
    }
}

$slides = $conn->query("SELECT * FROM hero_slides ORDER BY sort_order ASC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['added'])): ?><div class="admin-alert ok">Slide added.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="admin-alert ok">Slide updated.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Slide removed.</div><?php endif; ?>
<?php if (!empty($_SESSION['hero_warn'])): ?><div class="admin-alert err" style="background:#FFF4EA;border-color:#FFD2A6;color:#8A3F00"><?php echo htmlspecialchars($_SESSION['hero_warn']); unset($_SESSION['hero_warn']); ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a Slide</h2></div>
  <p class="admin-help" style="margin:0 0 14px"><b>For a fast, sharp homepage:</b> use wide photos (16:9), about <b>1920 × 1080 px</b>, saved as WebP or JPG and <b>under 400 KB</b>. The first active slide shows immediately; up to four active slides are used, in this order.</p>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field">
        <label>Image</label>
        <input type="file" name="image" accept="image/*" required>
      </div>
      <div class="field">
        <label>Alt text (for accessibility &amp; SEO)</label>
        <input type="text" name="alt_text" placeholder="e.g. Guests arriving at NUFA26">
      </div>
    </div>
    <button type="submit" class="admin-btn admin-btn-brand">Add Slide</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Current Slides</h2><span class="admin-help">Shown in the homepage photo collage</span></div>
  <table class="admin-table">
    <thead><tr><th></th><th>Alt Text</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php while ($s = $slides->fetch_assoc()): ?>
      <tr>
        <td><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($s['image']); ?>" alt=""></td>
        <td><?php echo htmlspecialchars($s['alt_text'] ?: '—'); ?></td>
        <td><?php echo $s['is_active'] ? '<span class="admin-badge">Active</span>' : '<span class="admin-badge gray">Hidden</span>'; ?></td>
        <td>
          <div class="admin-row-actions">
            <a href="slide-edit.php?id=<?php echo $s['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
            <a href="sliders.php?toggle=<?php echo $s['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm"><?php echo $s['is_active']?'Hide':'Show'; ?></a>
            <a href="sliders.php?delete=<?php echo $s['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this slide?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
