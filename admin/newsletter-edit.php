<?php
$adminNav = "newsletters";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/newsletter.php";
nl_install($conn);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$n = ['title'=>'','slug'=>'','issue_label'=>'','kind'=>(($_GET['kind'] ?? '') === 'pdf' ? 'pdf' : 'written'),'summary'=>'','body'=>'','cover_image'=>'','pdf_file'=>'','post_ids'=>'','status'=>'draft','published_at'=>date('Y-m-d\TH:i'),'sent_at'=>null,'sent_total'=>0];
if ($id) {
    $r = $conn->query("SELECT * FROM newsletters WHERE id=$id")->fetch_assoc();
    if (!$r) { header("Location: newsletters.php"); exit; }
    $n = array_merge($n, $r); $n['published_at'] = $r['published_at'] ? date('Y-m-d\TH:i', strtotime($r['published_at'])) : date('Y-m-d\TH:i');
}
$adminTitle = $id ? "Edit newsletter" : ($n['kind'] === 'pdf' ? "Upload a PDF newsletter" : "Write a newsletter");
$e = fn($v) => htmlspecialchars((string)$v);
$error = ''; $notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? 'save';
    if ($act === 'save') {
        $title = trim($_POST['title'] ?? ''); $kind = ($_POST['kind'] ?? '') === 'pdf' ? 'pdf' : 'written';
        $label = trim($_POST['issue_label'] ?? ''); $summary = trim($_POST['summary'] ?? ''); $body = $kind === 'pdf' ? '' : ($_POST['body'] ?? '');
        $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
        $pubAt = str_replace('T', ' ', $_POST['published_at'] ?? date('Y-m-d H:i')) . ':00';
        $posts = implode(',', array_map('intval', (array)($_POST['post_ids'] ?? [])));
        $cover = $n['cover_image']; $pdf = $n['pdf_file'];
        if (!empty($_FILES['cover']['name'])) { [$fn, $er] = mem_store_upload($_FILES['cover'], MEM_IMG_TYPES, 8 * 1048576, 'cover', NL_COVER_DIR); if ($er) $error = "Cover image: $er"; else $cover = 'uploads/newsletters/' . $fn; }
        $pdfUp = !empty($_FILES['pdf']['name']) ? $_FILES['pdf'] : ($_FILES['pdf_extra'] ?? []);
        if (!$error && !empty($pdfUp['name'])) { [$fn, $er] = mem_store_upload($pdfUp, ['application/pdf' => 'pdf'], 30 * 1048576, 'newsletter', NL_PDF_DIR); if ($er) $error = "PDF: $er"; else { if ($pdf) @unlink(NL_PDF_DIR . basename($pdf)); $pdf = $fn; } }
        if (!$error && isset($_POST['remove_pdf']) && $kind === 'written' && $pdf) { @unlink(NL_PDF_DIR . basename($pdf)); $pdf = ''; }
        if (!$error && $title === '') $error = "Give the newsletter a title.";
        if (!$error && $kind === 'pdf' && $pdf === '') $error = "Upload the PDF file for this newsletter.";
        if (!$error && $status === 'published' && $kind === 'written' && trim(strip_tags($body, '<img>')) === '' && !$posts && $summary === '') $error = "Add some content (text or stories) before publishing, or save it as a draft.";
        if (!$error) {
            $slug = $id ? $n['slug'] : nl_slug($conn, $title);
            if ($id) { $s = $conn->prepare("UPDATE newsletters SET title=?, issue_label=?, kind=?, summary=?, body=?, cover_image=?, pdf_file=?, post_ids=?, status=?, published_at=? WHERE id=?"); $s->bind_param("ssssssssssi", $title, $label, $kind, $summary, $body, $cover, $pdf, $posts, $status, $pubAt, $id); }
            else { $s = $conn->prepare("INSERT INTO newsletters (title, slug, issue_label, kind, summary, body, cover_image, pdf_file, post_ids, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("sssssssssss", $title, $slug, $label, $kind, $summary, $body, $cover, $pdf, $posts, $status, $pubAt); }
            $s->execute(); if (!$id) $id = $conn->insert_id;
            header("Location: newsletter-edit.php?id=$id&saved=1"); exit;
        }
        $n = array_merge($n, ['title'=>$title,'kind'=>$kind,'issue_label'=>$label,'summary'=>$summary,'body'=>$body,'status'=>$status,'post_ids'=>$posts,'published_at'=>str_replace(' ', 'T', substr($pubAt, 0, 16))]);
    } elseif ($id && $n['status'] === 'published') {
        if ($act === 'test') {
            $to = trim($_POST['test_to'] ?? '');
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $error = "Enter a valid email address for the test.";
            else { $ok = nl_mail($conn, $to, "[TEST] " . $n['title'], nl_email_html($conn, $n, ['token' => '']), nl_email_text($conn, $n, ['token' => ''])); $notice = $ok ? "Test email handed to the mail system for $to." : "The mail system refused the test email (this is normal on a local XAMPP install)."; }
        } elseif ($act === 'retry') {
            $conn->query("DELETE FROM newsletter_sends WHERE newsletter_id=$id AND status='failed'"); $notice = "Failed addresses are queued again — press “Send next batch”.";
        } elseif ($act === 'send') {
            [$sent, $failed, $rem] = nl_send_batch($conn, $n, 40);
            $notice = "Batch done — $sent sent" . ($failed ? ", $failed failed" : '') . ". " . ($rem ? "$rem recipients still waiting: press “Send next batch”." : "Everyone on the list has now been processed.");
            $n = $conn->query("SELECT * FROM newsletters WHERE id=$id")->fetch_assoc(); $n['published_at'] = date('Y-m-d\TH:i', strtotime($n['published_at']));
        }
    }
}
if (isset($_GET['saved'])) $notice = "Saved.";

$allPosts = $conn->query("SELECT id, title, category, published_at FROM posts WHERE is_published=1 ORDER BY published_at DESC LIMIT 60")->fetch_all(MYSQLI_ASSOC);
$chosen = array_map('intval', array_filter(explode(',', (string)$n['post_ids'])));
$active = (int)$conn->query("SELECT COUNT(*) FROM subscribers WHERE unsubscribed=0")->fetch_row()[0];
$pending = $id ? (int)$conn->query("SELECT COUNT(*) FROM subscribers s WHERE s.unsubscribed=0 AND NOT EXISTS (SELECT 1 FROM newsletter_sends x WHERE x.newsletter_id=$id AND x.email COLLATE utf8mb4_unicode_ci = s.email COLLATE utf8mb4_unicode_ci)")->fetch_row()[0] : 0;
$doneCount = $active - $pending;
$failedCount = $id ? (int)$conn->query("SELECT COUNT(*) FROM newsletter_sends WHERE newsletter_id=$id AND status='failed'")->fetch_row()[0] : 0;
include __DIR__ . "/includes/admin-header.php";
$newsTab = 'newsletters'; include __DIR__ . "/includes/news-tabs.php";
?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>
<?php if ($notice): ?><div class="admin-alert ok"><?php echo $e($notice); ?></div><?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data">
  <input type="hidden" name="action" value="save">
  <div class="edit-grid">
    <div class="edit-main">
      <div class="admin-card">
        <div class="field"><label>How is this newsletter made?</label>
          <div class="type-switch">
            <label><input type="radio" name="kind" value="written" <?php echo $n['kind'] === 'written' ? 'checked' : ''; ?> onchange="document.getElementById('written-only').hidden=false;document.getElementById('pdf-only').hidden=true"><b>Write it here</b><span>Compose the issue with the editor, add stories, publish online and e-mail it.</span></label>
            <label><input type="radio" name="kind" value="pdf" <?php echo $n['kind'] === 'pdf' ? 'checked' : ''; ?> onchange="document.getElementById('written-only').hidden=true;document.getElementById('pdf-only').hidden=false"><b>Upload a PDF</b><span>Publish a finished PDF newsletter for readers to view and download.</span></label>
          </div></div>
        <div class="admin-form-grid">
          <div class="field"><label>Title</label><input type="text" name="title" value="<?php echo $e($n['title']); ?>" required style="font-size:1.15rem;font-weight:700"></div>
          <div class="field"><label>Issue label</label><input type="text" name="issue_label" value="<?php echo $e($n['issue_label']); ?>" placeholder="e.g. Issue 4 · March 2027"></div>
        </div>
        <div class="field"><label>Introduction / summary <span class="admin-help">(shown in the archive and at the top of the e-mail)</span></label><textarea name="summary" style="min-height:80px" maxlength="600"><?php echo $e($n['summary']); ?></textarea></div>
        <div id="pdf-only" <?php echo $n['kind'] === 'pdf' ? '' : 'hidden'; ?>>
          <div class="field"><label>PDF file (max 30 MB)</label><input type="file" name="pdf" accept="application/pdf"><?php if ($n['pdf_file']): ?><span class="admin-help">Current: <a href="../assets/newsletters/<?php echo rawurlencode($n['pdf_file']); ?>" target="_blank"><?php echo $e($n['pdf_file']); ?></a></span><?php endif; ?></div>
        </div>
        <div id="written-only" <?php echo $n['kind'] === 'written' ? '' : 'hidden'; ?>>
          <div class="field"><label>Newsletter content</label><textarea name="body" data-rich data-upload="upload-image.php"><?php echo $e($n['body']); ?></textarea></div>
          <div class="field"><label>Also attach a PDF version (optional)</label><input type="file" name="pdf_extra" accept="application/pdf"><?php if ($n['kind'] === 'written' && $n['pdf_file']): ?><span class="admin-help">Current: <?php echo $e($n['pdf_file']); ?></span><label class="mem-check"><input type="checkbox" name="remove_pdf" value="1"> Remove it</label><?php endif; ?></div>
          <div class="field"><label>Stories to feature in “In this issue” (tick any)</label>
            <div class="post-pick"><?php foreach ($allPosts as $p): ?><label><input type="checkbox" name="post_ids[]" value="<?php echo $p['id']; ?>" <?php echo in_array((int)$p['id'], $chosen, true) ? 'checked' : ''; ?>><span><b><?php echo $e($p['title']); ?></b><br><small><?php echo $e($p['category']) . ' · ' . date('d M Y', strtotime($p['published_at'])); ?></small></span></label><?php endforeach; if (!$allPosts) echo '<p class="admin-help" style="padding:8px">No published stories yet.</p>'; ?></div></div>
        </div>
      </div>
    </div>
    <aside class="edit-side">
      <div class="admin-card"><div class="admin-card-head"><h2>Publish</h2></div>
        <div class="field"><label>Status</label><select name="status"><option value="published" <?php echo $n['status'] === 'published' ? 'selected' : ''; ?>>Published (visible on the website)</option><option value="draft" <?php echo $n['status'] === 'published' ? '' : 'selected'; ?>>Draft (hidden)</option></select></div>
        <div class="field"><label>Publication date</label><input type="datetime-local" name="published_at" value="<?php echo $e($n['published_at']); ?>"></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand">Save newsletter</button><a href="newsletters.php" class="admin-btn admin-btn-line">Back</a>
          <?php if ($id && $n['status'] === 'published'): ?><a class="admin-btn admin-btn-line" target="_blank" href="../newsletter.php?slug=<?php echo urlencode($n['slug']); ?>">View</a><?php endif; ?></div>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Cover image</h2></div>
        <?php if ($n['cover_image']): ?><img src="../assets/images/<?php echo $e($n['cover_image']); ?>" alt="" style="width:100%;border-radius:10px;margin-bottom:10px"><?php endif; ?>
        <div class="field"><label><?php echo $n['cover_image'] ? 'Replace cover' : 'Upload cover'; ?> (JPG, PNG, WebP)</label><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></div>
      </div>
    </aside>
  </div>
</form>

<?php if ($id && $n['status'] === 'published'): ?>
<div class="edit-grid" style="margin-top:20px"><div class="admin-card">
  <div class="admin-card-head"><h2>E-mail this newsletter</h2><span class="admin-help"><?php echo $active; ?> active subscribers</span></div>
  <?php if ($n['sent_at']): ?><p class="admin-help">Sent so far: <b><?php echo (int)$n['sent_total']; ?></b> (first sent <?php echo date('d M Y', strtotime($n['sent_at'])); ?>). Subscribers who join later can still be sent this issue with the button below.</p><?php endif; ?>
  <div class="progress"><i style="width:<?php echo $active ? round($doneCount / $active * 100) : 0; ?>%"></i></div>
  <p class="admin-help"><?php echo $doneCount; ?> of <?php echo $active; ?> processed · <?php echo $pending; ?> waiting</p>
  <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:14px"><input type="hidden" name="action" value="test">
    <div class="field" style="margin:0;flex:1;min-width:220px"><label>Send a test to</label><input type="email" name="test_to" placeholder="your@email.com"></div><button class="admin-btn admin-btn-line">Send test</button></form>
  <?php if ($failedCount): ?><form method="post" style="margin-bottom:10px"><input type="hidden" name="action" value="retry"><span class="admin-help"><?php echo $failedCount; ?> address(es) were refused by the mail system.</span> <button class="admin-btn admin-btn-line admin-btn-sm">Retry failed</button></form><?php endif; ?>
  <form method="post" onsubmit="return confirm('Send this newsletter to the next batch of subscribers now?')"><input type="hidden" name="action" value="send">
    <button class="admin-btn admin-btn-brand" <?php echo $pending ? '' : 'disabled'; ?>><?php echo $n['sent_at'] ? 'Send next batch' : 'Send to subscribers'; ?> (up to 40 at a time)</button></form>
  <p class="admin-help" style="margin-top:10px">Mail is sent through the server’s mail system, 40 recipients per click, and nobody gets the same issue twice. “Sent” means the server accepted it — delivery cannot be confirmed. On a local XAMPP install no mail is delivered.</p>
</div><div></div></div>
<?php elseif ($id): ?><p class="admin-help" style="margin-top:16px">Publish the newsletter to unlock e-mail sending.</p><?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
