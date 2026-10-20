<?php
$adminTitle = "Dashboard";
$adminNav = "dashboard";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/membership.php";
require_once __DIR__ . "/../includes/settings.php";
$cfg = nufa_site_settings($conn);

$one = function ($sql) use ($conn) { $r = $conn->query($sql); return $r ? (int)($r->fetch_row()[0] ?? 0) : 0; };

// ---- numbers
$mApproved = $one("SELECT COUNT(*) FROM members WHERE status='approved'");
$mPending  = $one("SELECT COUNT(*) FROM members WHERE status='pending'");
$mExpired  = $one("SELECT COUNT(*) FROM members WHERE status='approved' AND valid_until < CURDATE()");
$mExpiring = $one("SELECT COUNT(*) FROM members WHERE status='approved' AND valid_until BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)");
$msgUnread = $one("SELECT COUNT(*) FROM messages WHERE is_read=0");
$msgTotal  = $one("SELECT COUNT(*) FROM messages");
$subTotal  = $one("SELECT COUNT(*) FROM subscribers");
$subMonth  = $one("SELECT COUNT(*) FROM subscribers WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$postLive  = $one("SELECT COUNT(*) FROM posts WHERE is_published=1");
$postDraft = $one("SELECT COUNT(*) FROM posts WHERE is_published=0");
$views     = $one("SELECT COALESCE(SUM(views),0) FROM posts");
$partners  = $one("SELECT COUNT(*) FROM partners WHERE is_active=1");
$enqNew    = $one("SELECT COUNT(*) FROM enquiries WHERE status='new'");
$supNew    = $one("SELECT COUNT(*) FROM engagements WHERE status='pending'");
$enqTotal  = $one("SELECT COUNT(*) FROM enquiries");
$progNoDetail = $one("SELECT COUNT(*) FROM programmes WHERE is_active=1 AND (TRIM(COALESCE(context,''))='' OR TRIM(COALESCE(objectives,''))='')");
$bioMissing = $one("SELECT COUNT(*) FROM team_members WHERE is_active=1 AND TRIM(COALESCE(bio,''))=''");
$resCount  = $one("SELECT COUNT(*) FROM resources WHERE is_active=1");
$vidCount  = $one("SELECT COUNT(*) FROM videos WHERE is_active=1");
$failed24  = $one("SELECT COUNT(*) FROM admin_login_log WHERE success=0 AND created_at > (NOW() - INTERVAL 24 HOUR)");

// ---- last 6 months series
$months = [];
for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i month"))] = ['label' => date('M', strtotime("first day of -$i month")), 'apps' => 0, 'subs' => 0];
$r = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c FROM members WHERE created_at >= (CURDATE() - INTERVAL 6 MONTH) GROUP BY ym");
while ($x = $r->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['apps'] = (int)$x['c'];
$r = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c FROM subscribers WHERE created_at >= (CURDATE() - INTERVAL 6 MONTH) GROUP BY ym");
while ($x = $r->fetch_assoc()) if (isset($months[$x['ym']])) $months[$x['ym']]['subs'] = (int)$x['c'];
$maxApps = max(1, ...array_column($months, 'apps'));
$maxSubs = max(1, ...array_column($months, 'subs'));

$byGuild = $conn->query("SELECT g.name, COUNT(m.id) c FROM guilds g LEFT JOIN members m ON m.guild_id=g.id AND m.status='approved' GROUP BY g.id ORDER BY c DESC, g.sort_order LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$maxGuild = max(1, ...array_map(fn($g) => (int)$g['c'], $byGuild ?: [['c' => 1]]));

$recentApps  = $conn->query("SELECT m.id, m.full_name, m.status, m.created_at, g.name guild FROM members m LEFT JOIN guilds g ON g.id=m.guild_id ORDER BY m.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recentMsgs  = $conn->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$topPosts    = $conn->query("SELECT id, title, views, published_at FROM posts WHERE is_published=1 ORDER BY views DESC, published_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$signins     = $conn->query("SELECT username, ip, success, created_at FROM admin_login_log ORDER BY id DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// ---- site health
$S = mem_settings($conn);
$uploadDir = __DIR__ . "/../assets/images/uploads";
$health = [];
$add = function ($state, $label, $hint = '') use (&$health) { $health[] = [$state, $label, $hint]; };

$meRow = $conn->query("SELECT password_hash FROM admins WHERE id=" . (int)$_SESSION['admin_id'])->fetch_assoc();
if ($meRow && password_verify('admin123', $meRow['password_hash'])) $add('bad', 'You are still using the default password', 'Change it under My account.');
$add(is_dir($uploadDir) && is_writable($uploadDir) ? 'ok' : 'bad', 'Image uploads folder is writable', is_writable($uploadDir) ? '' : 'Posts, partners and gallery uploads will fail.');
$prot = is_file(MEM_STORAGE_DIR . ".htaccess");
$add($prot ? 'ok' : 'bad', 'Member documents folder is protected', $prot ? '' : 'storage/members/.htaccess is missing.');
$add($S['president_signature'] !== '' && $S['president_name'] !== '' ? 'ok' : 'meh', 'Membership card signature & president name', ($S['president_signature'] === '' || $S['president_name'] === '') ? 'Set them under Membership → Settings so cards show a signature.' : '');
$add($S['membership_fee'] !== '' && stripos($S['payment_instructions'], '0000000000') === false ? 'ok' : 'meh', 'Membership fee & payment instructions', stripos($S['payment_instructions'], '0000000000') !== false ? 'Still showing placeholder account details.' : '');
$add($S['membership_open'] === '1' ? 'ok' : 'meh', $S['membership_open'] === '1' ? 'Membership applications are open' : 'Membership applications are closed');
$add(function_exists('mail') ? 'ok' : 'bad', 'Email sending (PHP mail) is available', 'Send a test by approving a test member; failures appear in the member’s email log.');
$add($failed24 > 10 ? 'bad' : 'ok', $failed24 . ' failed sign-in attempt(s) in the last 24 hours', $failed24 > 10 ? 'Possible password guessing — consider changing passwords.' : '');

$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
include __DIR__ . "/includes/admin-header.php";
$first = explode(' ', trim($_SESSION['admin_name'] ?? 'Admin'))[0];
?>
<div class="dash-hello">
  <div>
    <h2><?php echo $greet . ', ' . htmlspecialchars($first); ?> 👋</h2>
    <p><?php echo date('l, j F Y'); ?> — here's what's happening on the NUFA website.</p>
  </div>
  <div class="dash-actions">
    <a class="admin-btn admin-btn-brand" href="post-edit.php">+ New post</a>
    <a class="admin-btn admin-btn-line" href="members.php?status=pending">Review applications</a>
    <a class="admin-btn admin-btn-line" href="enquiries.php">Inbox</a>
    <a class="admin-btn admin-btn-line" href="pages.php">Edit pages</a>
    <a class="admin-btn admin-btn-line" href="website-map.php">Website map</a>
  </div>
</div>

<div class="dash-kpis">
  <a class="kpi" href="members.php?status=approved"><span>Active members</span><b><?php echo $mApproved; ?></b><small><?php echo $mExpired; ?> expired · <?php echo $mExpiring; ?> expiring in 30 days</small></a>
  <a class="kpi <?php echo $mPending ? 'warn' : ''; ?>" href="members.php?status=pending"><span>Applications to review</span><b><?php echo $mPending; ?></b><small><?php echo $mPending ? 'Waiting for your approval' : 'All caught up'; ?></small></a>
  <a class="kpi <?php echo $enqNew ? 'warn' : 'dark'; ?>" href="enquiries.php?status=new"><span>New enquiries</span><b><?php echo $enqNew; ?></b><small><?php echo $enqTotal; ?> received in total<?php echo $msgUnread ? ' · ' . $msgUnread . ' earlier messages unread' : ''; ?></small></a>
  <a class="kpi dark" href="posts.php"><span>Published stories</span><b><?php echo $postLive; ?></b><small><?php echo number_format($views); ?> views · <?php echo $postDraft; ?> drafts</small></a>
</div>

<div class="dash-grid">
  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Membership applications — last 6 months</h2><a href="members.php" class="admin-btn admin-btn-line admin-btn-sm">Open membership</a></div>
      <div class="bars">
        <?php foreach ($months as $m): ?>
          <div class="bar" title="<?php echo $m['apps']; ?> applications"><b><?php echo $m['apps']; ?></b><i style="height:<?php echo max(2, round($m['apps'] / $maxApps * 120)); ?>px"></i><span><?php echo $m['label']; ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Recent applications</h2><a href="members.php" class="admin-btn admin-btn-line admin-btn-sm">View all</a></div>
      <?php if (!$recentApps): ?><p class="admin-help">No membership applications yet. Share <b>membership.php</b> to start receiving them.</p><?php else: ?>
      <table class="admin-table">
        <thead><tr><th>Applicant</th><th>Guild</th><th>Status</th><th>Applied</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recentApps as $a): ?>
          <tr><td><b><?php echo htmlspecialchars($a['full_name']); ?></b></td><td><?php echo htmlspecialchars($a['guild'] ?? '—'); ?></td><td><?php echo mem_status_badge($a['status']); ?></td>
              <td><?php echo date('d M Y', strtotime($a['created_at'])); ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="member-view.php?id=<?php echo $a['id']; ?>"><?php echo $a['status'] === 'pending' ? 'Review' : 'View'; ?></a></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Recent messages</h2><a href="messages.php" class="admin-btn admin-btn-line admin-btn-sm">Open inbox</a></div>
      <?php if (!$recentMsgs): ?><p class="admin-help">No messages yet.</p><?php else: ?>
      <table class="admin-table">
        <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recentMsgs as $m): ?>
          <tr><td><b><?php echo htmlspecialchars($m['name']); ?></b><br><span style="color:var(--text-muted);font-size:.8rem"><?php echo htmlspecialchars($m['email']); ?></span></td>
              <td><?php echo htmlspecialchars($m['subject'] ?: '—'); ?></td><td><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
              <td><?php echo $m['is_read'] ? '<span class="admin-badge gray">Read</span>' : '<span class="admin-badge">New</span>'; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Most read posts</h2><a href="posts.php" class="admin-btn admin-btn-line admin-btn-sm">Manage posts</a></div>
      <table class="admin-table">
        <thead><tr><th>Title</th><th>Published</th><th>Views</th></tr></thead>
        <tbody>
        <?php foreach ($topPosts as $p): ?>
          <tr><td><a href="post-edit.php?id=<?php echo $p['id']; ?>"><b><?php echo htmlspecialchars($p['title']); ?></b></a></td><td><?php echo date('d M Y', strtotime($p['published_at'])); ?></td><td><?php echo (int)$p['views']; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Needs your attention</h2></div>
      <div class="todo">
        <a href="members.php?status=pending"><span class="n <?php echo $mPending ? 'red' : 'ok'; ?>"><?php echo $mPending; ?></span> Membership applications to review<i>&rarr;</i></a>
        <a href="enquiries.php?status=new"><span class="n <?php echo $enqNew ? 'red' : 'ok'; ?>"><?php echo $enqNew; ?></span> New enquiries (contact, partnership, SACCO, network)<i>&rarr;</i></a>
        <a href="support-requests.php?status=pending"><span class="n <?php echo $supNew ? 'red' : 'ok'; ?>"><?php echo $supNew; ?></span> Support requests (donate, sponsor, volunteer, partner)<i>&rarr;</i></a>
        <a href="members.php?status=approved"><span class="n <?php echo $mExpiring ? '' : 'ok'; ?>"><?php echo $mExpiring; ?></span> Memberships expiring within 30 days<i>&rarr;</i></a>
        <a href="posts.php"><span class="n <?php echo $postDraft ? '' : 'ok'; ?>"><?php echo $postDraft; ?></span> Draft posts not yet published<i>&rarr;</i></a>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Content to complete</h2></div>
      <p class="admin-help" style="margin-bottom:10px">Items the master brief says must be verified or approved before they are treated as final.</p>
      <div class="todo">
        <a href="settings.php"><span class="n <?php echo $cfg['vm_approved'] === '1' ? 'ok' : ''; ?>"><?php echo $cfg['vm_approved'] === '1' ? '✓' : '!'; ?></span> Vision &amp; mission approval by leadership<i>&rarr;</i></a>
        <a href="settings.php"><span class="n <?php echo $cfg['policies_approved'] === '1' ? 'ok' : ''; ?>"><?php echo $cfg['policies_approved'] === '1' ? '✓' : '!'; ?></span> Policy pages reviewed by a professional<i>&rarr;</i></a>
        <a href="programmes.php"><span class="n <?php echo $progNoDetail ? '' : 'ok'; ?>"><?php echo $progNoDetail; ?></span> Programmes still missing context / objectives<i>&rarr;</i></a>
        <a href="team.php"><span class="n <?php echo $bioMissing ? '' : 'ok'; ?>"><?php echo $bioMissing; ?></span> Leadership profiles without an approved biography<i>&rarr;</i></a>
        <a href="resources.php"><span class="n <?php echo $resCount ? 'ok' : ''; ?>"><?php echo $resCount; ?></span> Resources in the library<i>&rarr;</i></a>
        <a href="videos.php"><span class="n <?php echo $vidCount ? 'ok' : ''; ?>"><?php echo $vidCount; ?></span> Videos in the library<i>&rarr;</i></a>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Site health</h2></div>
      <div class="health">
        <?php foreach ($health as [$state, $label, $hint]): ?>
          <div><span class="dot <?php echo $state === 'ok' ? '' : ($state === 'bad' ? 'bad' : 'meh'); ?>"></span><span><?php echo htmlspecialchars($label); ?><?php if ($hint): ?><small><?php echo htmlspecialchars($hint); ?></small><?php endif; ?></span></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Members by guild</h2></div>
      <?php foreach ($byGuild as $g): ?>
        <div class="hbar"><div><span><?php echo htmlspecialchars($g['name']); ?></span><b><?php echo (int)$g['c']; ?></b></div><i style="--w:<?php echo round($g['c'] / $maxGuild * 100); ?>%"></i></div>
      <?php endforeach; ?>
      <?php if (!$byGuild): ?><p class="admin-help">No guilds yet.</p><?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>New subscribers</h2></div>
      <div class="bars" style="height:130px">
        <?php foreach ($months as $m): ?>
          <div class="bar" title="<?php echo $m['subs']; ?> subscribers"><b><?php echo $m['subs']; ?></b><i style="background:var(--ink);height:<?php echo max(2, round($m['subs'] / $maxSubs * 80)); ?>px"></i><span><?php echo $m['label']; ?></span></div>
        <?php endforeach; ?>
      </div>
      <p class="admin-help" style="margin-top:12px"><?php echo $postLive; ?> published posts · <?php echo number_format($views); ?> total views · <?php echo $partners; ?> active partners</p>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><h2>Recent sign-ins</h2></div>
      <?php foreach ($signins as $s): ?>
        <div class="mem-row"><span style="width:auto;flex:1"><?php echo htmlspecialchars($s['username'] ?: '—'); ?> <small style="color:var(--text-muted)">· <?php echo htmlspecialchars($s['ip']); ?></small></span>
          <span class="admin-badge <?php echo $s['success'] ? '' : 'red'; ?>" style="width:auto"><?php echo $s['success'] ? 'signed in' : 'failed'; ?></span>
          <span style="width:auto;font-size:.76rem"><?php echo date('d M H:i', strtotime($s['created_at'])); ?></span></div>
      <?php endforeach; ?>
      <?php if (!$signins): ?><p class="admin-help">No sign-ins recorded yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
