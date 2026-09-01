<?php
$adminTitle = "Hero Slider";
$adminNav = "sliders";
require __DIR__ . "/includes/auth.php";

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
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fname = 'slide-' . time() . '.' . $ext;
            $dest = __DIR__ . "/../assets/images/uploads/posts/" . $fname;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $alt = trim($_POST['alt_text']);
                $image = "uploads/posts/" . $fname;
                $stmt = $conn->prepare("INSERT INTO hero_slides (image, alt_text, sort_order) VALUES (?,?,(SELECT m FROM (SELECT COALESCE(MAX(sort_order),0)+1 m FROM hero_slides) t))");
                $stmt->bind_param("ss", $image, $alt);
                $stmt->execute();
                header("Location: sliders.php?added=1");
                exit;
            }
        }
        $error = "Please upload a JPG, PNG or WEBP image.";
    } else {
        $error = "Please choose an image to upload.";
    }
}

$slides = $conn->query("SELECT * FROM hero_slides ORDER BY sort_order ASC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['added'])): ?><div class="admin-alert ok">Slide added.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Slide removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a Slide</h2></div>
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
