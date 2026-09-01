<?php
$adminTitle = "Dashboard";
$adminNav = "dashboard";
require __DIR__ . "/includes/auth.php";

$postCount = $conn->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'];
$subCount  = $conn->query("SELECT COUNT(*) c FROM subscribers")->fetch_assoc()['c'];
$msgCount  = $conn->query("SELECT COUNT(*) c FROM messages")->fetch_assoc()['c'];
$unreadCount = $conn->query("SELECT COUNT(*) c FROM messages WHERE is_read=0")->fetch_assoc()['c'];
$slideCount = $conn->query("SELECT COUNT(*) c FROM hero_slides WHERE is_active=1")->fetch_assoc()['c'];
$partnerCount = $conn->query("SELECT COUNT(*) c FROM partners WHERE is_active=1")->fetch_assoc()['c'];

$recentMsgs = $conn->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5");
$recentPosts = $conn->query("SELECT * FROM posts ORDER BY published_at DESC LIMIT 5");

include __DIR__ . "/includes/admin-header.php";
?>

<div class="admin-grid">
  <div class="admin-stat">
    <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
    <b><?php echo $postCount; ?></b>
    <span>Blog posts</span>
  </div>
  <div class="admin-stat">
    <span class="ico" style="background:#FDEBEC;color:var(--red-600)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 20v-1a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v1M15 4.2a3.5 3.5 0 0 1 0 6.6M17 20v-1a5 5 0 0 0-3-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/></svg></span>
    <b><?php echo $subCount; ?></b>
    <span>Newsletter subscribers</span>
  </div>
  <div class="admin-stat">
    <span class="ico" style="background:var(--cream-2);color:var(--ink)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
    <b><?php echo $msgCount; ?></b>
    <span><?php echo $unreadCount; ?> unread messages</span>
  </div>
  <div class="admin-stat">
    <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M3 15l5-4 4 3 5-5 4 3" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
    <b><?php echo $slideCount; ?></b>
    <span>Active hero slides · <?php echo $partnerCount; ?> partners</span>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Recent Messages</h2>
    <a href="messages.php" class="admin-btn admin-btn-line admin-btn-sm">View all</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th></tr></thead>
    <tbody>
      <?php if ($recentMsgs->num_rows === 0): ?>
      <tr><td colspan="4" style="color:var(--text-muted)">No messages yet.</td></tr>
      <?php endif; ?>
      <?php while ($m = $recentMsgs->fetch_assoc()): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($m['name']); ?></b><br><span style="color:var(--text-muted);font-size:.8rem"><?php echo htmlspecialchars($m['email']); ?></span></td>
        <td><?php echo htmlspecialchars($m['subject'] ?: '—'); ?></td>
        <td><?php echo date("d M Y", strtotime($m['created_at'])); ?></td>
        <td><?php echo $m['is_read'] ? '<span class="admin-badge gray">Read</span>' : '<span class="admin-badge">New</span>'; ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Recent Posts</h2>
    <a href="posts.php" class="admin-btn admin-btn-line admin-btn-sm">Manage all</a>
  </div>
  <table class="admin-table">
    <thead><tr><th></th><th>Title</th><th>Category</th><th>Published</th><th>Views</th></tr></thead>
    <tbody>
      <?php while ($p = $recentPosts->fetch_assoc()): ?>
      <tr>
        <td><img class="admin-thumb" src="../assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt=""></td>
        <td><b><?php echo htmlspecialchars($p['title']); ?></b></td>
        <td><?php echo htmlspecialchars($p['category']); ?></td>
        <td><?php echo date("d M Y", strtotime($p['published_at'])); ?></td>
        <td><?php echo $p['views']; ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
