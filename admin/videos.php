<?php
$adminTitle = "Video Library";
$adminNav = "gallery";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/settings.php";

if (isset($_GET['delete'])) { $conn->query("DELETE FROM videos WHERE id=" . (int)$_GET['delete']); header("Location: videos.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE videos SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: videos.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cats = nufa_list(nufa_site_settings($conn), 'list_gallery_categories'); $vcat = in_array($_POST['category'] ?? '', $cats, true) ? $_POST['category'] : '';
    $title = trim($_POST['title'] ?? ''); $url = trim($_POST['video_url'] ?? ''); $cap = trim($_POST['caption'] ?? ''); $ed = trim($_POST['edition'] ?? '');
    if ($title === '') $error = "Enter a title.";
    elseif (!ui_video_embed($url)) $error = "Only YouTube and Vimeo links are supported (e.g. https://www.youtube.com/watch?v=…). Use videos from NUFA's official channel.";
    else {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 n FROM videos")->fetch_assoc()['n'];
        $s = $conn->prepare("INSERT INTO videos (title, video_url, edition, caption, category, sort_order) VALUES (?,?,?,?,?,?)");
        $s->bind_param("sssssi", $title, $url, $ed, $cap, $vcat, $o); $s->execute();
        header("Location: videos.php?saved=1"); exit;
    }
}
$rows = $conn->query("SELECT * FROM videos ORDER BY sort_order DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="gallery.php">Photo gallery</a><a href="videos.php" class="active">Video library</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Video added.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Video removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a video</h2><span class="admin-help">Videos are embedded (privacy-enhanced) from YouTube or Vimeo. Only add material NUFA has permission to publish.</span></div>
  <form method="post" class="admin-form">
    <div class="admin-form-grid">
      <div class="field"><label>Title</label><input name="title" required></div>
      <div class="field"><label>YouTube or Vimeo link</label><input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=…" required></div>
      <div class="field"><label>Edition / event (optional)</label><input name="edition" placeholder="e.g. NUFA Awards 2025"></div>
      <div class="field"><label>Category / album</label><select name="category"><option value="">— none —</option><?php foreach (nufa_list(nufa_site_settings($conn), 'list_gallery_categories') as $c): ?><option><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Caption (optional)</label><input name="caption"></div>
    </div>
    <button class="admin-btn admin-btn-brand">Add video</button>
  </form>
</div>
<div class="admin-card">
  <div class="admin-card-head"><h2>Videos (<?php echo count($rows); ?>)</h2></div>
  <?php if (!$rows): ?><p class="admin-help">No videos yet — the public library shows a “coming soon” message.</p><?php else: ?>
  <table class="admin-table"><thead><tr><th>Title</th><th>Category</th><th>Event</th><th>Link</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $v): ?><tr><td><b><?php echo htmlspecialchars($v['title']); ?></b></td><td><?php echo $v['category'] !== '' ? '<span class="admin-badge">' . htmlspecialchars($v['category']) . '</span>' : '—'; ?></td><td><?php echo htmlspecialchars($v['edition'] ?: '—'); ?></td>
    <td><a href="<?php echo htmlspecialchars($v['video_url']); ?>" target="_blank" rel="noopener">Open</a></td>
    <td><a href="videos.php?toggle=<?php echo $v['id']; ?>" class="admin-badge <?php echo $v['is_active'] ? '' : 'gray'; ?>"><?php echo $v['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
    <td><a href="videos.php?delete=<?php echo $v['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this video?">Delete</a></td></tr><?php endforeach; ?></tbody></table>
  <?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
