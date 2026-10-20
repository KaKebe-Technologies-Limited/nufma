<?php
$adminTitle = "Gallery";
$adminNav = "gallery";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
require_once __DIR__ . "/../includes/ui.php";

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("SELECT image FROM gallery_images WHERE id=$id")->fetch_assoc();
    if ($row && strpos($row['image'], 'uploads/posts/') === 0) @unlink(__DIR__ . "/../assets/images/" . $row['image']);
    $conn->query("DELETE FROM gallery_images WHERE id=$id");
    header("Location: gallery.php?deleted=1"); exit;
}

$cats = nufa_list(nufa_site_settings($conn), 'list_gallery_categories');
$error = ''; $added = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption'] ?? ''); $edition = trim($_POST['edition'] ?? '');
    $category = in_array($_POST['category'] ?? '', $cats, true) ? $_POST['category'] : '';
    $files = $_FILES['images'] ?? null;
    $list = [];
    if ($files && is_array($files['name'])) foreach ($files['name'] as $i => $nm) if ($nm !== '') $list[] = ['name' => $nm, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
    if (!$list) $error = "Please choose at least one image.";
    $ins = $conn->prepare("INSERT INTO gallery_images (image, caption, edition, category) VALUES (?,?,?,?)");
    foreach ($list as $f) {
        [$fn, $e] = mem_store_upload($f, MEM_IMG_TYPES, 8 * 1048576, 'gallery', __DIR__ . "/../assets/images/uploads/posts/");
        if ($e) { $error = $f['name'] . ": " . $e; continue; }
        $img = "uploads/posts/" . $fn;
        $ins->bind_param("ssss", $img, $caption, $edition, $category); $ins->execute(); $added++;
    }
    if ($added && !$error) { header("Location: gallery.php?added=$added"); exit; }
}

$cat = $_GET['cat'] ?? '';
if ($cat !== '__none' && !in_array($cat, $cats, true)) $cat = '';
$counts = [];
$cr = $conn->query("SELECT category, COUNT(*) c FROM gallery_images GROUP BY category");
while ($c = $cr->fetch_assoc()) $counts[$c['category']] = (int)$c['c'];
$images = $conn->query("SELECT * FROM gallery_images WHERE " . ui_cat_where($conn, $cat) . " ORDER BY created_at DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="gallery.php" class="active">Photo gallery</a><a href="videos.php">Video library</a></div>
<?php if (isset($_GET['added'])): ?><div class="admin-alert ok"><?php echo (int)$_GET['added']; ?> photo(s) added to the gallery.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Photo removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add photographs</h2><span class="admin-help">Choose several at once. Only upload images NUFA has permission to publish.</span></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field"><label>Images (JPG, PNG or WebP, max 8 MB each)</label><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
      <div class="field"><label>Category / album</label><select name="category"><option value="">— none —</option><?php foreach ($cats as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select><span class="admin-help"><a href="lists.php">Edit categories</a></span></div>
      <div class="field"><label>Awards edition (also shows the photo on that edition's page)</label><select name="edition"><option value="">— none —</option><option value="2027">NUFA 2027</option><option value="2026">NUFA 2026</option><option value="2025">NUFA 2025</option></select></div>
      <div class="field"><label>Caption / credit (optional)</label><input name="caption" placeholder="e.g. Red carpet arrivals — Photo: …"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Upload</button>
  </form>
</div>

<?php echo ui_admin_cat_tabs('gallery.php', $cats, $cat, $counts); ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Photographs (<?php echo count($images); ?>)</h2></div>
  <div style="overflow-x:auto"><table class="admin-table">
    <thead><tr><th></th><th>Caption</th><th>Category</th><th>Edition</th><th></th></tr></thead><tbody>
    <?php if (!$images): ?><tr><td colspan="5" style="color:var(--text-muted)">Nothing here yet.</td></tr><?php endif; ?>
    <?php foreach ($images as $g): ?>
      <tr>
        <td><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($g['image']); ?>" alt="" loading="lazy"></td>
        <td><?php echo htmlspecialchars($g['caption'] ?: '—'); ?></td>
        <td><?php echo $g['category'] !== '' ? '<span class="admin-badge">' . htmlspecialchars($g['category']) . '</span>' : '<span class="admin-badge gray">none</span>'; ?></td>
        <td><?php echo $g['edition'] !== '' ? '<span class="admin-badge gray">' . htmlspecialchars(ctype_digit($g['edition']) ? 'NUFA ' . $g['edition'] : $g['edition']) . '</span>' : '—'; ?></td>
        <td><a href="gallery.php?delete=<?php echo $g['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this photo from the gallery?">Delete</a></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
