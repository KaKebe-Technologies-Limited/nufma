<?php
$adminNav = "posts";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/ui.php";   // nufa_news_categories()
require_once __DIR__ . "/../includes/newsletter.php";   // also loads membership (uploads) and adds the extra post columns
nl_install($conn);
require_once __DIR__ . "/../includes/guests.php";
guests_install($conn);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = ['title'=>'','category'=>'NUFA News','excerpt'=>'','body'=>'','image'=>'','image_caption'=>'','image_credit'=>'','author'=>'NUFA Team','is_featured'=>0,'is_published'=>1,'published_at'=>date('Y-m-d\TH:i'),'attachment'=>'','attachment_title'=>'','video_url'=>'','slug'=>''];
if ($id) {
    $r = $conn->query("SELECT * FROM posts WHERE id=$id")->fetch_assoc();
    if (!$r) { header("Location: posts.php"); exit; }
    $post = array_merge($post, $r);
    $post['published_at'] = date('Y-m-d\TH:i', strtotime($post['published_at']));
}
$adminTitle = $id ? "Edit story" : "Write a story";
$categories = nufa_news_categories($conn);
if ($post['category'] !== '' && !in_array($post['category'], $categories, true)) $categories[] = $post['category'];
$downloadsDir = __DIR__ . "/../assets/downloads/";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body = $_POST['body'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $caption = trim($_POST['image_caption'] ?? '');
    $credit = trim($_POST['image_credit'] ?? '');
    $video = trim($_POST['video_url'] ?? '');
    $attTitle = trim($_POST['attachment_title'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isPublished = ($_POST['status'] ?? 'published') === 'published' ? 1 : 0;
    $publishedAt = str_replace('T', ' ', $_POST['published_at'] ?? date('Y-m-d H:i')) . ':00';
    $image = $post['image']; $attachment = $post['attachment'];
    $isBlank = trim(strip_tags($body, '<img>')) === '';

    if (!empty($_FILES['image']['name'])) {
        [$fn, $er] = mem_store_upload($_FILES['image'], MEM_IMG_TYPES, 8 * 1048576, strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'post', __DIR__ . "/../assets/images/uploads/posts/");
        if ($er) $error = "Featured image: $er"; else $image = "uploads/posts/" . $fn;
    }
    if (!$error && !empty($_FILES['attachment']['name'])) {
        [$fn, $er] = mem_store_upload($_FILES['attachment'], ['application/pdf' => 'pdf'], 20 * 1048576, 'story', $downloadsDir);
        if ($er) $error = "PDF attachment: $er"; else { if ($attachment) @unlink($downloadsDir . basename($attachment)); $attachment = $fn; }
    }
    if (!$error && isset($_POST['remove_attachment']) && $attachment) { @unlink($downloadsDir . basename($attachment)); $attachment = ''; }
    if (!$error && $video !== '' && !preg_match('#^https?://#i', $video)) $error = "The video link must start with https://";

    if (!$error && $title === '') $error = "Give the story a headline.";
    if (!$error && $isPublished && ($excerpt === '' || $isBlank || $image === '' || $category === '')) $error = "To publish, add a summary, the story text, a featured image and a category — or save it as a draft.";

    if (!$error) {
        if ($isFeatured && $isPublished) $conn->query("UPDATE posts SET is_featured=0" . ($id ? " WHERE id<>$id" : ""));   // only one featured story at a time
        else $isFeatured = 0;
        if ($id) {
            $stmt = $conn->prepare("UPDATE posts SET title=?,category=?,excerpt=?,body=?,image=?,image_caption=?,image_credit=?,author=?,is_featured=?,is_published=?,published_at=?,attachment=?,attachment_title=?,video_url=? WHERE id=?");
            $stmt->bind_param("ssssssssiissssi", $title,$category,$excerpt,$body,$image,$caption,$credit,$author,$isFeatured,$isPublished,$publishedAt,$attachment,$attTitle,$video,$id);
            $stmt->execute();
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'story';
            $base = $slug; $n = 1;
            while ($conn->query("SELECT id FROM posts WHERE slug='" . $conn->real_escape_string($slug) . "'")->num_rows) $slug = $base . '-' . (++$n);
            $stmt = $conn->prepare("INSERT INTO posts (title,slug,category,excerpt,body,image,image_caption,image_credit,author,is_featured,is_published,published_at,attachment,attachment_title,video_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssssiissss", $title,$slug,$category,$excerpt,$body,$image,$caption,$credit,$author,$isFeatured,$isPublished,$publishedAt,$attachment,$attTitle,$video);
            $stmt->execute();
        }
        $sid = $id ?: (int)$conn->insert_id;
        // event details
        $ed = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['event_date'] ?? '') ? $_POST['event_date'] : null;
        $ee = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['event_end_date'] ?? '') ? $_POST['event_end_date'] : null;
        $et = trim($_POST['event_time'] ?? ''); $ev = trim($_POST['event_venue'] ?? ''); $em = trim($_POST['event_map_url'] ?? '');
        if ($em !== '' && !preg_match('#^https?://#i', $em)) $em = '';
        $x = $conn->prepare("UPDATE posts SET event_date=?, event_end_date=?, event_time=?, event_venue=?, event_map_url=? WHERE id=?");
        $x->bind_param("sssssi", $ed, $ee, $et, $ev, $em, $sid); $x->execute();
        // gallery: captions, removals, new pictures
        foreach ((array)($_POST['gcap'] ?? []) as $gid => $cp) { $gid = (int)$gid; $cp = trim($cp); $conn->query("UPDATE post_images SET caption='" . $conn->real_escape_string($cp) . "' WHERE id=$gid AND post_id=$sid"); }
        foreach ((array)($_POST['gdel'] ?? []) as $gid) { $gid = (int)$gid; $gr = $conn->query("SELECT image FROM post_images WHERE id=$gid AND post_id=$sid")->fetch_assoc(); if ($gr) { @unlink(__DIR__ . "/../assets/images/" . $gr['image']); $conn->query("DELETE FROM post_images WHERE id=$gid"); } }
        $gf = $_FILES['gallery'] ?? null; $gnote = '';
        if ($gf && is_array($gf['name'])) {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0) FROM post_images WHERE post_id=$sid")->fetch_row()[0];
            foreach ($gf['name'] as $i => $nm) {
                if ($nm === '') continue;
                $one = ['name' => $nm, 'type' => $gf['type'][$i], 'tmp_name' => $gf['tmp_name'][$i], 'error' => $gf['error'][$i], 'size' => $gf['size'][$i]];
                [$fn, $er] = mem_store_upload($one, MEM_IMG_TYPES, 8 * 1048576, 'story-' . $sid, STORY_DIR);
                if ($er) { $gnote .= " “$nm”: $er"; continue; }
                $rel = 'uploads/posts/' . $fn; $o++; $cp = trim($_POST['gallery_caption'] ?? '');
                $ins = $conn->prepare("INSERT INTO post_images (post_id, image, caption, sort_order) VALUES (?,?,?,?)"); $ins->bind_param("issi", $sid, $rel, $cp, $o); $ins->execute();
            }
        }
        header("Location: " . ($gnote !== '' ? "post-edit.php?id=$sid&gnote=" . urlencode(trim($gnote)) : "posts.php?saved=1"));
        exit;
    }
    $post = array_merge($post, ['title'=>$title,'category'=>$category,'excerpt'=>$excerpt,'body'=>$body,'author'=>$author,'image_caption'=>$caption,'image_credit'=>$credit,'is_featured'=>$isFeatured,'is_published'=>$isPublished,'published_at'=>str_replace(' ', 'T', substr($publishedAt, 0, 16)),'video_url'=>$video,'attachment_title'=>$attTitle]);
}

$e = fn($v) => htmlspecialchars((string)$v);
include __DIR__ . "/includes/admin-header.php";
$newsTab = 'posts'; include __DIR__ . "/includes/news-tabs.php";
?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>
<?php if (!empty($_GET['gnote'])): ?><div class="admin-alert err">Story saved, but some pictures were skipped:<?php echo $e($_GET['gnote']); ?></div><?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data">
  <div class="edit-grid">
    <div class="edit-main">
      <div class="admin-card">
        <div class="field"><label>Headline</label><input type="text" name="title" value="<?php echo $e($post['title']); ?>" placeholder="Write a clear, specific headline" required style="font-size:1.25rem;font-weight:700"></div>
        <div class="field"><label>Summary <span class="admin-help">(shown in listings, search results and when shared — one or two sentences)</span></label><textarea name="excerpt" style="min-height:70px" maxlength="500"><?php echo $e($post['excerpt']); ?></textarea></div>
        <div class="field"><label>Story</label>
          <textarea name="body" data-rich data-upload="upload-image.php"><?php echo $e($post['body']); ?></textarea>
          <span class="admin-help">Use the toolbar to format text, add links and upload pictures into the story. Only publish verified news — never invented articles, interviews or quotations.</span></div>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Event details</h2><span class="admin-help">Fill these when the story is about an event — they appear as a Date / Time / Venue panel on the story page. Leave empty otherwise.</span></div>
        <div class="admin-form-grid">
          <div class="field"><label>Event date</label><input type="date" name="event_date" value="<?php echo $e($post['event_date'] ?? ''); ?>"></div>
          <div class="field"><label>Last day (if it ran over several days)</label><input type="date" name="event_end_date" value="<?php echo $e($post['event_end_date'] ?? ''); ?>"></div>
          <div class="field"><label>Time</label><input type="text" name="event_time" value="<?php echo $e($post['event_time'] ?? ''); ?>" placeholder="e.g. 6:00 PM – 11:00 PM"></div>
          <div class="field"><label>Venue</label><input type="text" name="event_venue" value="<?php echo $e($post['event_venue'] ?? ''); ?>" placeholder="Venue name and town"></div>
          <div class="field"><label>Map link (optional)</label><input type="url" name="event_map_url" value="<?php echo $e($post['event_map_url'] ?? ''); ?>" placeholder="https://maps.google.com/…"></div>
        </div>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Photo gallery</h2><span class="admin-help">Add as many pictures as you like (JPG, PNG, WebP, 8 MB each). They show as a gallery under the story and open in a full-screen viewer.</span></div>
        <?php $gimgs = $id ? $conn->query("SELECT * FROM post_images WHERE post_id=$id ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC) : []; if ($gimgs): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:14px">
          <?php foreach ($gimgs as $gi): ?><div style="border:1px solid #E5E5E5;border-radius:10px;padding:8px"><img src="../assets/images/<?php echo $e($gi['image']); ?>" alt="" style="width:100%;height:100px;object-fit:cover;border-radius:8px"><input type="text" name="gcap[<?php echo $gi['id']; ?>]" value="<?php echo $e($gi['caption']); ?>" placeholder="Caption" style="margin-top:6px;width:100%"><label class="mem-check" style="margin-top:4px"><input type="checkbox" name="gdel[]" value="<?php echo $gi['id']; ?>"> Remove</label></div><?php endforeach; ?>
        </div><?php endif; ?>
        <div class="field"><label>Add pictures (select several at once)</label><input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple></div>
        <div class="field"><label>Caption for the new pictures (optional)</label><input type="text" name="gallery_caption" placeholder="e.g. Red carpet arrivals"></div>
      </div>
    </div>

    <aside class="edit-side">
      <div class="admin-card"><div class="admin-card-head"><h2>Publish</h2></div>
        <div class="field"><label>Status</label><select name="status"><option value="published" <?php echo $post['is_published'] ? 'selected' : ''; ?>>Published (live)</option><option value="draft" <?php echo $post['is_published'] ? '' : 'selected'; ?>>Draft (hidden)</option></select></div>
        <div class="field"><label>Publication date</label><input type="datetime-local" name="published_at" value="<?php echo $e($post['published_at']); ?>" required></div>
        <label class="mem-check"><input type="checkbox" name="is_featured" value="1" <?php echo $post['is_featured'] ? 'checked' : ''; ?>> Featured story (top of the news page)</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px"><button class="admin-btn admin-btn-brand">Save story</button><a href="posts.php" class="admin-btn admin-btn-line">Cancel</a>
          <?php if ($id && $post['is_published']): ?><a href="../post.php?slug=<?php echo urlencode($post['slug']); ?>" target="_blank" class="admin-btn admin-btn-line">View</a><?php endif; ?></div>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Category &amp; author</h2></div>
        <div class="field"><label>Category</label><select name="category"><?php foreach ($categories as $c): ?><option <?php echo $post['category'] === $c ? 'selected' : ''; ?>><?php echo $e($c); ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Author or editorial team</label><input type="text" name="author" value="<?php echo $e($post['author']); ?>"></div>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Featured image</h2></div>
        <?php if ($post['image']): ?><img src="../assets/images/<?php echo $e($post['image']); ?>" alt="" style="width:100%;border-radius:10px;margin-bottom:10px"><?php endif; ?>
        <div class="field"><label><?php echo $post['image'] ? 'Replace image' : 'Upload image'; ?> (JPG, PNG, WebP)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
        <div class="field"><label>Caption</label><input type="text" name="image_caption" value="<?php echo $e($post['image_caption']); ?>"></div>
        <div class="field"><label>Photo credit</label><input type="text" name="image_credit" value="<?php echo $e($post['image_credit']); ?>"></div>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Extras</h2></div>
        <div class="field"><label>Video link (YouTube or Vimeo)</label><input type="url" name="video_url" value="<?php echo $e($post['video_url']); ?>" placeholder="https://"></div>
        <div class="field"><label>PDF attachment (report, programme, release — max 20 MB)</label><input type="file" name="attachment" accept="application/pdf">
          <?php if ($post['attachment']): ?><span class="admin-help">Current: <?php echo $e($post['attachment']); ?></span><label class="mem-check"><input type="checkbox" name="remove_attachment" value="1"> Remove it</label><?php endif; ?></div>
        <div class="field"><label>Attachment button label</label><input type="text" name="attachment_title" value="<?php echo $e($post['attachment_title']); ?>" placeholder="e.g. Download the full report"></div>
      </div>
    </aside>
  </div>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
