<?php
$adminNav = "posts";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/ui.php";   // NUFA_NEWS_CATEGORIES

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = ['title'=>'','category'=>'NUFA News','excerpt'=>'','body'=>'','image'=>'','image_caption'=>'','image_credit'=>'','author'=>'NUFA Team','is_featured'=>0,'is_published'=>1,'published_at'=>date('Y-m-d\TH:i'),'slug'=>''];
if ($id) {
    $r = $conn->query("SELECT * FROM posts WHERE id=$id")->fetch_assoc();
    if (!$r) { header("Location: posts.php"); exit; }
    $post = array_merge($post, $r);
    $post['published_at'] = date('Y-m-d\TH:i', strtotime($post['published_at']));
}
$adminTitle = $id ? "Edit Story" : "New Story";
$categories = NUFA_NEWS_CATEGORIES;
if ($post['category'] !== '' && !in_array($post['category'], $categories, true)) $categories[] = $post['category'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = $_POST['body'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $caption = trim($_POST['image_caption'] ?? '');
    $credit = trim($_POST['image_credit'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedAt = str_replace('T', ' ', $_POST['published_at'] ?? date('Y-m-d H:i')) . ':00';
    $image = $post['image'];

    if (!empty($_FILES['image']['name'])) {
        $mime = $_FILES['image']['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']) : '';
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? '';
        if (!$ext) $error = "Please upload a JPG, PNG or WebP image (max size set by the server).";
        else {
            $safeSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'post';
            $fname = $safeSlug . '-' . time() . '.' . $ext;
            $dir = __DIR__ . "/../assets/images/uploads/posts/";
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) $image = "uploads/posts/" . $fname;
            else $error = "The image could not be saved — check that assets/images/uploads/posts/ is writable.";
        }
    }

    if (!$error && $title && $excerpt && $body && $image && $category) {
        if ($isFeatured) $conn->query("UPDATE posts SET is_featured=0" . ($id ? " WHERE id<>$id" : ""));   // only one featured story at a time
        if ($id) {
            $stmt = $conn->prepare("UPDATE posts SET title=?,category=?,excerpt=?,body=?,image=?,image_caption=?,image_credit=?,author=?,is_featured=?,is_published=?,published_at=? WHERE id=?");
            $stmt->bind_param("ssssssssiisi", $title,$category,$excerpt,$body,$image,$caption,$credit,$author,$isFeatured,$isPublished,$publishedAt,$id);
            $stmt->execute();
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'story';
            $base = $slug; $n = 1;
            while ($conn->query("SELECT id FROM posts WHERE slug='" . $conn->real_escape_string($slug) . "'")->num_rows) $slug = $base . '-' . (++$n);
            $stmt = $conn->prepare("INSERT INTO posts (title,slug,category,excerpt,body,image,image_caption,image_credit,author,is_featured,is_published,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssssiis", $title,$slug,$category,$excerpt,$body,$image,$caption,$credit,$author,$isFeatured,$isPublished,$publishedAt);
            $stmt->execute();
        }
        header("Location: posts.php?saved=1");
        exit;
    }
    if (!$error) $error = "Please fill in the title, category, excerpt, body, and upload a featured image.";
    $post = array_merge($post, ['title'=>$title,'category'=>$category,'excerpt'=>$excerpt,'body'=>$body,'author'=>$author,'image_caption'=>$caption,'image_credit'=>$credit,'is_featured'=>$isFeatured,'is_published'=>$isPublished]);
}

include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="notice-inline">Only publish verified news. Mark any demonstration content as unpublished — the master brief forbids fabricated articles, interviews or quotations on the live site.</div>

<div class="admin-card">
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field"><label>Headline</label><input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required></div>
      <div class="field"><label>Category</label>
        <select name="category" required>
          <?php foreach ($categories as $c): ?><option <?php echo $post['category'] === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?>
        </select></div>
    </div>

    <div class="field"><label>Summary (shown in listings and search results)</label><textarea name="excerpt" style="min-height:70px" maxlength="500" required><?php echo htmlspecialchars($post['excerpt']); ?></textarea></div>
    <div class="field"><label>Article content (HTML — wrap paragraphs in &lt;p&gt;…&lt;/p&gt;)</label><textarea name="body" required><?php echo htmlspecialchars($post['body']); ?></textarea></div>

    <div class="admin-form-grid">
      <div class="field"><label>Featured image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"<?php echo $post['image'] ? '' : ' required'; ?>>
        <?php if ($post['image']): ?><p class="admin-help">Current: <?php echo htmlspecialchars($post['image']); ?></p><?php endif; ?></div>
      <div class="field"><label>Author or editorial team</label><input type="text" name="author" value="<?php echo htmlspecialchars($post['author']); ?>"></div>
      <div class="field"><label>Image caption</label><input type="text" name="image_caption" value="<?php echo htmlspecialchars($post['image_caption']); ?>"></div>
      <div class="field"><label>Image credit (photographer)</label><input type="text" name="image_credit" value="<?php echo htmlspecialchars($post['image_credit']); ?>"></div>
    </div>

    <div class="admin-form-grid">
      <div class="field"><label>Publication date</label><input type="datetime-local" name="published_at" value="<?php echo htmlspecialchars($post['published_at']); ?>" required></div>
      <div class="field" style="display:flex;gap:24px;align-items:center;padding-top:28px">
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_featured" style="width:auto" <?php echo $post['is_featured'] ? 'checked' : ''; ?>> Featured story</label>
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="is_published" style="width:auto" <?php echo $post['is_published'] ? 'checked' : ''; ?>> Published</label>
      </div>
    </div>

    <button type="submit" class="admin-btn admin-btn-brand">Save story</button>
    <a href="posts.php" class="admin-btn admin-btn-line">Cancel</a>
    <?php if ($id && $post['is_published']): ?><a href="../post.php?slug=<?php echo urlencode($post['slug']); ?>" target="_blank" class="admin-btn admin-btn-line">View on site</a><?php endif; ?>
  </form>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
