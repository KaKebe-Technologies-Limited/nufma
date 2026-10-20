<?php
$adminTitle = "Newsletters";
$adminNav = "newsletters";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/newsletter.php";
nl_install($conn);

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("SELECT pdf_file FROM newsletters WHERE id=$id")->fetch_assoc();
    if ($row && $row['pdf_file'] !== '') @unlink(NL_PDF_DIR . basename($row['pdf_file']));
    $conn->query("DELETE FROM newsletters WHERE id=$id"); $conn->query("DELETE FROM newsletter_sends WHERE newsletter_id=$id");
    header("Location: newsletters.php?deleted=1"); exit;
}
$e = fn($v) => htmlspecialchars((string)$v);
$rows = $conn->query("SELECT * FROM newsletters ORDER BY COALESCE(published_at, created_at) DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
$subs = (int)$conn->query("SELECT COUNT(*) FROM subscribers WHERE unsubscribed=0")->fetch_row()[0];
$pub = count(array_filter($rows, fn($r) => $r['status'] === 'published'));
include __DIR__ . "/includes/admin-header.php";
$newsTab = 'newsletters'; include __DIR__ . "/includes/news-tabs.php";
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Newsletter deleted.</div><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Newsletter saved.</div><?php endif; ?>

<div class="stat-row">
  <div><b><?php echo count($rows); ?></b><span>Issues</span></div><div><b><?php echo $pub; ?></b><span>Published</span></div>
  <a href="subscribers.php" style="text-decoration:none;color:inherit"><div style="height:100%"><b><?php echo $subs; ?></b><span>Active subscribers</span></div></a>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>All newsletters</h2><span><a href="newsletter-edit.php?kind=written" class="admin-btn admin-btn-brand">+ Write a newsletter</a> <a href="newsletter-edit.php?kind=pdf" class="admin-btn admin-btn-line">+ Upload a PDF newsletter</a></span></div>
  <table class="admin-table">
    <thead><tr><th></th><th>Issue</th><th>Type</th><th>Date</th><th>Status</th><th>E-mailed</th><th></th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="7" style="color:var(--text-muted)">No newsletters yet — write your first issue or upload a PDF.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php if ($r['cover_image']): ?><img class="admin-thumb" src="../assets/images/<?php echo $e($r['cover_image']); ?>" alt=""><?php endif; ?></td>
        <td><b><?php echo $e($r['title']); ?></b><br><small><?php echo $e($r['issue_label']); ?></small></td>
        <td><?php echo $r['kind'] === 'pdf' ? 'PDF upload' : 'Written online'; ?><?php echo $r['kind'] === 'written' && $r['pdf_file'] ? ' + PDF' : ''; ?></td>
        <td><?php echo $r['published_at'] ? date('d M Y', strtotime($r['published_at'])) : '—'; ?></td>
        <td><?php echo $r['status'] === 'published' ? '<span class="admin-badge">Published</span>' : '<span class="admin-badge gray">Draft</span>'; ?></td>
        <td><?php echo $r['sent_at'] ? (int)$r['sent_total'] . ' sent<br><small>' . date('d M Y', strtotime($r['sent_at'])) . '</small>' : '<span class="muted">Not sent</span>'; ?></td>
        <td><div class="admin-row-actions"><a class="admin-btn admin-btn-line admin-btn-sm" href="newsletter-edit.php?id=<?php echo $r['id']; ?>">Edit / Send</a>
          <?php if ($r['status'] === 'published'): ?><a class="admin-btn admin-btn-line admin-btn-sm" target="_blank" href="../newsletter.php?slug=<?php echo urlencode($r['slug']); ?>">View</a><?php endif; ?>
          <a class="admin-btn admin-btn-danger admin-btn-sm" href="newsletters.php?delete=<?php echo $r['id']; ?>" data-confirm="Delete this newsletter permanently?">Delete</a></div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
