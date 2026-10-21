<?php
$adminTitle = "News & stories";
$adminNav = "posts";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/ui.php";   // nufa_news_categories()

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("SELECT attachment FROM posts WHERE id=$id")->fetch_assoc();
    if (!empty($row['attachment'])) @unlink(__DIR__ . "/../assets/downloads/" . basename($row['attachment']));
    require_once __DIR__ . "/../includes/guests.php"; guests_install($conn);
    foreach ($conn->query("SELECT image FROM post_images WHERE post_id=$id")->fetch_all(MYSQLI_ASSOC) as $gi) @unlink(__DIR__ . "/../assets/images/" . $gi['image']);
    $conn->query("DELETE FROM post_images WHERE post_id=$id");
    $conn->query("DELETE FROM posts WHERE id=$id");
    header("Location: posts.php?deleted=1"); exit;
}
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE posts SET is_published = 1 - is_published WHERE id=$id");
    header("Location: posts.php?saved=1" . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '')); exit;
}

$e = fn($v) => htmlspecialchars((string)$v);
$status = in_array($_GET['status'] ?? '', ['live', 'draft'], true) ? $_GET['status'] : '';
$cat = trim($_GET['cat'] ?? ''); $q = trim($_GET['q'] ?? '');
$sort = ['new' => 'published_at DESC', 'old' => 'published_at ASC', 'views' => 'views DESC', 'title' => 'title ASC'][$_GET['sort'] ?? 'new'] ?? 'published_at DESC';
$where = [];
if ($status === 'live') $where[] = "is_published=1"; elseif ($status === 'draft') $where[] = "is_published=0";
if ($cat !== '') $where[] = "category='" . $conn->real_escape_string($cat) . "'";
if ($q !== '') { $qq = $conn->real_escape_string($q); $where[] = "(title LIKE '%$qq%' OR excerpt LIKE '%$qq%' OR author LIKE '%$qq%')"; }
$W = $where ? ' WHERE ' . implode(' AND ', $where) : '';

if (nufa_export_format() !== '') {
    $all = $conn->query("SELECT title, category, author, published_at, views, is_published, is_featured FROM posts$W ORDER BY $sort")->fetch_all(MYSQLI_ASSOC);
    $data = array_map(fn($p) => [$p['title'], $p['category'], $p['author'], date('d M Y', strtotime($p['published_at'])), $p['views'], $p['is_published'] ? 'Live' : 'Draft', $p['is_featured'] ? 'Yes' : ''], $all);
    nufa_export_send(nufa_export_format(), 'News & stories', ['Title', 'Category', 'Author', 'Published', 'Views', 'Status', 'Featured'], $data, 'nufa-stories');
}

$counts = ['all' => 0, 'live' => 0, 'draft' => 0];
foreach ($conn->query("SELECT is_published, COUNT(*) c FROM posts GROUP BY is_published")->fetch_all(MYSQLI_ASSOC) as $c) { $counts[$c['is_published'] ? 'live' : 'draft'] = (int)$c['c']; $counts['all'] += (int)$c['c']; }
$per = 20; $page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)$conn->query("SELECT COUNT(*) FROM posts$W")->fetch_row()[0]; $pages = max(1, (int)ceil($total / $per)); $page = min($page, $pages);
$posts = $conn->query("SELECT * FROM posts$W ORDER BY $sort LIMIT $per OFFSET " . (($page - 1) * $per))->fetch_all(MYSQLI_ASSOC);
$cats = nufa_news_categories($conn);
$qs = fn(array $x = []) => 'posts.php?' . http_build_query(array_filter(array_merge(['status' => $status, 'cat' => $cat, 'q' => $q, 'sort' => $_GET['sort'] ?? ''], $x), fn($v) => $v !== '' && $v !== null));

include __DIR__ . "/includes/admin-header.php";
$newsTab = 'posts'; include __DIR__ . "/includes/news-tabs.php";
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Story deleted.</div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?>

<div class="ax-toolbar">
  <div class="ax-seg"><?php foreach (['' => ['All', $counts['all']], 'live' => ['Live', $counts['live']], 'draft' => ['Drafts', $counts['draft']]] as $k => [$l, $n]): ?><a href="<?php echo $e($qs(['status' => $k, 'page' => ''])); ?>" class="<?php echo $status === $k ? 'on' : ''; ?>"><?php echo $l; ?><small><?php echo $n; ?></small></a><?php endforeach; ?></div>
  <form method="get" class="ax-toolbar" style="margin:0;flex:1;min-width:260px"><input type="hidden" name="status" value="<?php echo $e($status); ?>">
    <div class="grow"><input type="search" name="q" value="<?php echo $e($q); ?>" placeholder="Search stories by title, summary or author…"></div>
    <select name="cat" onchange="this.form.submit()"><option value="">All categories</option><?php foreach ($cats as $c) echo '<option' . ($cat === $c ? ' selected' : '') . '>' . $e($c) . '</option>'; ?></select>
    <select name="sort" onchange="this.form.submit()"><?php foreach (['new' => 'Newest first', 'old' => 'Oldest first', 'views' => 'Most viewed', 'title' => 'A → Z'] as $k => $l) echo '<option value="' . $k . '"' . (($_GET['sort'] ?? 'new') === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select>
  </form>
  <?php echo nufa_export_menu($qs(['page' => ''])); ?>
  <a href="post-edit.php" class="admin-btn admin-btn-brand">+ Write a story</a>
</div>

<div class="admin-card" style="padding:8px 8px 4px">
  <div class="admin-table-wrap"><table class="admin-table" data-noexport>
    <thead><tr><th>Story</th><th>Category</th><th>Published</th><th>Views</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
    <tbody>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td><div class="ax-title-cell"><?php if ($p['image']): ?><img class="admin-thumb" src="../assets/images/<?php echo $e($p['image']); ?>" alt=""><?php endif; ?>
          <div><div class="t"><a href="post-edit.php?id=<?php echo $p['id']; ?>"><?php echo $e($p['title']); ?></a></div>
            <div class="s"><?php echo $p['is_featured'] ? '<span class="admin-badge">★ Featured</span> ' : ''; ?><?php echo $e($p['author']); ?><?php echo !empty($p['attachment']) ? ' · 📎 PDF' : ''; ?><?php echo !empty($p['video_url']) ? ' · ▶ video' : ''; ?></div></div></div></td>
        <td class="nowrap"><?php echo $e($p['category']); ?></td>
        <td class="is-date"><?php echo date("d M Y", strtotime($p['published_at'])); ?></td>
        <td><?php echo number_format((int)$p['views']); ?></td>
        <td><?php echo $p['is_published'] ? '<span class="admin-badge" style="background:#eef6ee;color:#1f6b2b">● Live</span>' : '<span class="admin-badge gray">○ Draft</span>'; ?></td>
        <td><div class="admin-row-actions">
          <a href="post-edit.php?id=<?php echo $p['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
          <?php if ($p['is_published']): ?><a href="../post.php?slug=<?php echo urlencode($p['slug']); ?>" target="_blank" class="admin-btn admin-btn-line admin-btn-sm">View</a><?php endif; ?>
          <a href="posts.php?toggle=<?php echo $p['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm" data-confirm="<?php echo $p['is_published'] ? 'Move this story back to drafts?' : 'Publish this story now?'; ?>"><?php echo $p['is_published'] ? 'Unpublish' : 'Publish'; ?></a>
          <a href="posts.php?delete=<?php echo $p['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this story permanently?">Delete</a></div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if (!$posts): ?><div class="ax-empty"><b>No stories found</b><?php echo ($q !== '' || $cat !== '' || $status !== '') ? 'Try clearing the filters.' : 'Write your first story to get started.'; ?><br><br><a href="post-edit.php" class="admin-btn admin-btn-brand">+ Write a story</a></div><?php endif; ?>
</div>

<?php if ($pages > 1): ?><div class="ax-toolbar" style="justify-content:center"><div class="ax-seg"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?php echo $e($qs(['page' => $i])); ?>" class="<?php echo $i === $page ? 'on' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?></div></div><?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
