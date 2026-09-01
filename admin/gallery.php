<?php
$adminTitle = "Gallery";
$adminNav = "gallery";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM gallery_images WHERE id=" . (int)$_GET['delete']);
    header("Location: gallery.php?deleted=1");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fname = 'gallery-' . time() . '.' . $ext;
            $dest = __DIR__ . "/../assets/images/uploads/posts/" . $fname;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $caption = trim($_POST['caption']);
                $edition = trim($_POST['edition']);
                $image = "uploads/posts/" . $fname;
                $stmt = $conn->prepare("INSERT INTO gallery_images (image, caption, edition) VALUES (?,?,?)");
                $stmt->bind_param("sss", $image, $caption, $edition);
                $stmt->execute();
                header("Location: gallery.php?added=1");
                exit;
            }
        }
        $error = "Please upload a JPG, PNG or WEBP image.";
    } else {
        $error = "Please choose an image to upload.";
    }
}

$images = $conn->query("SELECT * FROM gallery_images ORDER BY created_at DESC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['added'])): ?><div class="admin-alert ok">Photo added to the gallery.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Photo removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a Photo</h2><span class="admin-help">Uploaded photos join the admin-managed gallery pool</span></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field">
        <label>Image</label>
        <input type="file" name="image" accept="image/*" required>
      </div>
      <div class="field">
        <label>Edition</label>
        <select name="edition">
          <option value="2027">NUFA27</option>
          <option value="2026">NUFA26</option>
          <option value="2025">NUFA25</option>
        </select>
      </div>
    </div>
    <div class="field">
      <label>Caption</label>
      <input type="text" name="caption" placeholder="e.g. Guests arriving on the red carpet">
    </div>
    <button type="submit" class="admin-btn admin-btn-brand">Upload Photo</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Admin-Added Photos</h2><span class="admin-help">The main NUFA25/26 sets from the site launch live in /assets/images/gallery/</span></div>
  <table class="admin-table">
    <thead><tr><th></th><th>Caption</th><th>Edition</th><th></th></tr></thead>
    <tbody>
      <?php if ($images->num_rows === 0): ?>
      <tr><td colspan="4" style="color:var(--text-muted)">No admin-uploaded photos yet.</td></tr>
      <?php endif; ?>
      <?php while ($g = $images->fetch_assoc()): ?>
      <tr>
        <td><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($g['image']); ?>" alt=""></td>
        <td><?php echo htmlspecialchars($g['caption'] ?: '—'); ?></td>
        <td><span class="admin-badge gray">NUFA<?php echo substr(htmlspecialchars($g['edition']),-2); ?></span></td>
        <td><a href="gallery.php?delete=<?php echo $g['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this photo?">Delete</a></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
