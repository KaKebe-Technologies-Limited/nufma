<?php
$adminTitle = "Gallery";
$adminNav = "gallery";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/gallery-lib.php";
gallery_hl_import($conn);   // adds the Highlights photo set (once)

function gal_remove($conn, $id) {
    $row = $conn->query("SELECT image FROM gallery_images WHERE id=$id")->fetch_assoc();
    if ($row && strpos($row['image'], 'uploads/posts/') === 0) @unlink(__DIR__ . "/../assets/images/" . $row['image']);
    if ($row && strpos($row['image'], GALLERY_HL_DIR) === 0) { @unlink(__DIR__ . "/../assets/images/" . $row['image']); @unlink(__DIR__ . "/../assets/images/" . gallery_thumb($row['image'])); }
    $conn->query("DELETE FROM gallery_images WHERE id=$id");
}

if (isset($_GET['delete'])) { gal_remove($conn, (int)$_GET['delete']); header("Location: gallery.php?deleted=1"); exit; }

$cats = nufa_list(nufa_site_settings($conn), 'list_gallery_categories');
$error = ''; $added = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk'])) {
    $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
    $op = $_POST['bulk'];
    if ($ids) {
        $in = implode(',', $ids);
        if ($op === 'delete') { foreach ($ids as $i) gal_remove($conn, $i); }
        elseif ($op === 'category') { $c = in_array($_POST['category'] ?? '', $cats, true) ? $_POST['category'] : ''; $u = $conn->prepare("UPDATE gallery_images SET category=? WHERE id IN ($in)"); $u->bind_param('s', $c); $u->execute(); }
        elseif ($op === 'edition') { $c = in_array($_POST['edition'] ?? '', ['2027', '2026', '2025'], true) ? $_POST['edition'] : ''; $u = $conn->prepare("UPDATE gallery_images SET edition=? WHERE id IN ($in)"); $u->bind_param('s', $c); $u->execute(); }
        elseif ($op === 'feature' || $op === 'unfeature') { $conn->query('UPDATE gallery_images SET featured=' . ($op === 'feature' ? 1 : 0) . " WHERE id IN ($in)"); }
        elseif ($op === 'caption') { $c = trim($_POST['caption'] ?? ''); $u = $conn->prepare("UPDATE gallery_images SET caption=? WHERE id IN ($in)"); $u->bind_param('s', $c); $u->execute(); }
    }
    header('Location: gallery.php?updated=' . count($ids) . '&cat=' . urlencode($_GET['cat'] ?? '') . '&p=' . (int)($_GET['p'] ?? 1)); exit;
}
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
$per = 60; $pg = max(1, (int)($_GET['p'] ?? 1));
$where = ui_cat_where($conn, $cat);
$totalImgs = (int)$conn->query("SELECT COUNT(*) FROM gallery_images WHERE $where")->fetch_row()[0];
$pages = max(1, (int)ceil($totalImgs / $per)); $pg = min($pg, $pages);
$images = $conn->query("SELECT * FROM gallery_images WHERE $where ORDER BY created_at DESC, id ASC LIMIT $per OFFSET " . (($pg - 1) * $per))->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="gallery.php" class="active">Photo gallery</a><a href="videos.php">Video library</a></div>
<?php if (isset($_GET['added'])): ?><div class="admin-alert ok"><?php echo (int)$_GET['added']; ?> photo(s) added to the gallery.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="admin-alert ok"><?php echo (int)$_GET['updated']; ?> photo(s) updated.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Photo removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add photographs</h2><span class="admin-help">Choose several at once. Only upload images NUFA has permission to publish.</span></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field"><label>Images (JPG, PNG or WebP, max 8 MB each)</label><input data-no-picker type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
      <div class="field"><label>Category / album</label><select name="category"><option value="">— none —</option><?php foreach ($cats as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select><span class="admin-help"><a href="lists.php">Edit categories</a></span></div>
      <div class="field"><label>Awards edition (also shows the photo on that edition's page)</label><select name="edition"><option value="">— none —</option><option value="2027">NUFA 2027</option><option value="2026">NUFA 2026</option><option value="2025">NUFA 2025</option></select></div>
      <div class="field"><label>Caption / credit (optional)</label><input name="caption" placeholder="e.g. Red carpet arrivals — Photo: …"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Upload</button>
  </form>
</div>

<?php echo ui_admin_cat_tabs('gallery.php', $cats, $cat, $counts); ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Photographs (<?php echo $totalImgs; ?>)</h2><span class="admin-help">Tick photos and press <b>Feature on home page</b> to show them in the home page photo section (up to 9; none ticked = the default NUFA26 set). Any photo here can be chosen with the <b>Choose from gallery</b> button on every image field in the admin (home slider, news, pages, programmes, awards…).</span></div>
  <form method="post" action="gallery.php?cat=<?php echo urlencode($cat); ?>&amp;p=<?php echo $pg; ?>" class="admin-form">
    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:12px">
      <label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" id="galAll"> Select all on this page</label>
      <select name="category"><option value="">— album —</option><?php foreach ($cats as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select><button class="admin-btn admin-btn-sm" name="bulk" value="category">Set album</button>
      <select name="edition"><option value="">— edition —</option><option value="2027">NUFA 2027</option><option value="2026">NUFA 2026</option><option value="2025">NUFA 2025</option></select><button class="admin-btn admin-btn-sm" name="bulk" value="edition">Set edition</button>
      <input name="caption" placeholder="Caption for selected" style="min-width:200px"><button class="admin-btn admin-btn-sm" name="bulk" value="caption">Set caption</button>
      <button class="admin-btn admin-btn-sm" name="bulk" value="feature">&#9733; Feature on home page</button><button class="admin-btn admin-btn-sm" name="bulk" value="unfeature">Remove from home page</button>
      <button class="admin-btn admin-btn-danger admin-btn-sm" name="bulk" value="delete" data-confirm="Delete the selected photos from the gallery?">Delete selected</button>
    </div>
    <div style="overflow-x:auto"><table class="admin-table">
    <thead><tr><th></th><th></th><th>Caption</th><th>Album</th><th>Edition</th><th></th></tr></thead><tbody>
    <?php if (!$images): ?><tr><td colspan="6" style="color:var(--text-muted)">Nothing here yet.</td></tr><?php endif; ?>
    <?php foreach ($images as $g): ?>
      <tr>
        <td><input type="checkbox" name="ids[]" value="<?php echo $g['id']; ?>" class="galRow"></td>
        <td><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars(gallery_thumb($g['image'])); ?>" alt="" loading="lazy"></td>
        <td><?php echo htmlspecialchars($g['caption'] ?: '—'); ?><?php if (!empty($g['featured'])): ?> <span class="admin-badge">&#9733; On home page</span><?php endif; ?></td>
        <td><?php echo $g['category'] !== '' ? '<span class="admin-badge">' . htmlspecialchars($g['category']) . '</span>' : '<span class="admin-badge gray">none</span>'; ?></td>
        <td><?php echo $g['edition'] !== '' ? '<span class="admin-badge gray">' . htmlspecialchars(ctype_digit($g['edition']) ? 'NUFA ' . $g['edition'] : $g['edition']) . '</span>' : '—'; ?></td>
        <td><a href="gallery.php?delete=<?php echo $g['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this photo from the gallery?">Delete</a></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  </form>
  <?php if ($pages > 1): ?><div class="mem-tabs" style="margin-top:14px"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="gallery.php?cat=<?php echo urlencode($cat); ?>&amp;p=<?php echo $i; ?>" class="<?php echo $i === $pg ? 'active' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?></div><?php endif; ?>
</div>
<script>document.getElementById('galAll')?.addEventListener('change',function(){var v=this.checked;document.querySelectorAll('.galRow').forEach(function(c){c.checked=v})});</script>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
