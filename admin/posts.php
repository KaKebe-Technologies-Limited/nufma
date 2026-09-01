<?php
$adminTitle = "Blog Posts";
$adminNav = "posts";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM posts WHERE id=$id");
    header("Location: posts.php?deleted=1");
    exit;
}

$posts = $conn->query("SELECT * FROM posts ORDER BY published_at DESC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Post deleted.</div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Post saved.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>All Posts</h2>
    <a href="post-edit.php" class="admin-btn admin-btn-brand">+ New Post</a>
  </div>
  <table class="admin-table">
    <thead><tr><th></th><th>Title</th><th>Category</th><th>Published</th><th>Views</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if ($posts->num_rows === 0): ?>
      <tr><td colspan="7" style="color:var(--text-muted)">No posts yet — create your first one.</td></tr>
      <?php endif; ?>
      <?php while ($p = $posts->fetch_assoc()): ?>
      <tr>
        <td><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt=""></td>
        <td><b><?php echo htmlspecialchars($p['title']); ?></b><?php if($p['is_featured']): ?> <span class="admin-badge">Featured</span><?php endif; ?></td>
        <td><?php echo htmlspecialchars($p['category']); ?></td>
        <td><?php echo date("d M Y", strtotime($p['published_at'])); ?></td>
        <td><?php echo $p['views']; ?></td>
        <td><?php echo $p['is_published'] ? '<span class="admin-badge">Live</span>' : '<span class="admin-badge gray">Draft</span>'; ?></td>
        <td>
          <div class="admin-row-actions">
            <a href="post-edit.php?id=<?php echo $p['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a>
            <a href="posts.php?delete=<?php echo $p['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this post permanently?">Delete</a>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
