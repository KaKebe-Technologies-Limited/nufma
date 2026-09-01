<?php
$adminNav = "posts";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = ['title'=>'','category'=>'News','excerpt'=>'','body'=>'','image'=>'','author'=>'NUFA Team','is_featured'=>0,'is_published'=>1,'published_at'=>date('Y-m-d\TH:i')];
if ($id) {
    $r = $conn->query("SELECT * FROM posts WHERE id=$id")->fetch_assoc();
    if ($r) {
        $post = $r;
        $post['published_at'] = date('Y-m-d\TH:i', strtotime($post['published_at']));
    }
}
$adminTitle = $id ? "Edit Post" : "New Post";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $excerpt = trim($_POST['excerpt']);
    $body = $_POST['body'];
    $author = trim($_POST['author']);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedAt = str_replace('T', ' ', $_POST['published_at']) . ':00';
    $image = $post['image'];

    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $safeSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'post';
            $fname = $safeSlug . '-' . time() . '.' . $ext;
            $dest = __DIR__ . "/../assets/images/uploads/posts/" . $fname;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image = "uploads/posts/" . $fname;
            }
        }
    }

    if ($title && $excerpt && $body && $image) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE posts SET title=?,category=?,excerpt=?,body=?,image=?,author=?,is_featured=?,is_published=?,published_at=? WHERE id=?");
            $stmt->bind_param("ssssssiisi", $title,$category,$excerpt,$body,$image,$author,$isFeatured,$isPublished,$publishedAt,$id);
            $stmt->execute();
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
            $base = $slug; $n = 1;
            while ($conn->query("SELECT id FROM posts WHERE slug='" . $conn->real_escape_string($slug) . "'")->num_rows) {
                $slug = $base . '-' . (++$n);
            }
            $stmt = $conn->prepare("INSERT INTO posts (title,slug,category,excerpt,body,image,author,is_featured,is_published,published_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssssis", $title,$slug,$category,$excerpt,$body,$image,$author,$isFeatured,$isPublished,$publishedAt);
            $stmt->execute();
        }
        header("Location: posts.php?saved=1");
        exit;
    }
    $error = "Please fill in the title, excerpt, body, and upload an image.";
    $post = array_merge($post, compact('title','category','excerpt','body','author','isFeatured','isPublished'));
}

include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field">
        <label>Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
      </div>
      <div class="field">
        <label>Category</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($post['category']); ?>" placeholder="e.g. Awards Recap" required>
      </div>
    </div>

    <div class="field">
      <label>Excerpt (short summary shown in listings)</label>
      <textarea name="excerpt" style="min-height:70px" required><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
    </div>

    <div class="field">
      <label>Body (HTML — wrap paragraphs in &lt;p&gt;...&lt;/p&gt;)</label>
      <textarea name="body" required><?php echo htmlspecialchars($post['body']); ?></textarea>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Featured image</label>
        <input type="file" name="image" accept="image/*">
        <?php if ($post['image']): ?><p class="admin-help">Current: <?php echo htmlspecialchars($post['image']); ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label>Author</label>
        <input type="text" name="author" value="<?php echo htmlspecialchars($post['author']); ?>">
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Published date</label>
        <input type="datetime-local" name="published_at" value="<?php echo htmlspecialchars($post['published_at']); ?>" required>
      </div>
      <div class="field" style="display:flex;gap:24px;align-items:center;padding-top:28px">
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_featured" style="width:auto" <?php echo $post['is_featured']?'checked':''; ?>> Featured</label>
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_published" style="width:auto" <?php echo $post['is_published']?'checked':''; ?>> Published</label>
      </div>
    </div>

    <button type="submit" class="admin-btn admin-btn-brand">Save Post</button>
    <a href="posts.php" class="admin-btn admin-btn-line">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
