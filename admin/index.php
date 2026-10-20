<?php
$adminTitle = "Dashboard";
$adminNav = "dashboard";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/membership.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/schedule.php";
$cfg = nufa_site_settings($conn);

$one = function ($sql) use ($conn) { try { $r = $conn->query($sql); return $r ? (float)($r->fetch_row()[0] ?? 0) : 0; } catch (Throwable $e) { return 0; } };
$rows = function ($sql) use ($conn) { try { $r = $conn->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; } catch (Throwable $e) { return []; } };

// holiday & special-day greetings: if no daily task is set up, check the calendar the first time an admin opens the dashboard each day
try {
    $lastGreet = $conn->query("SELECT setting_value FROM settings WHERE setting_key='sch_greet_last_run'")->fetch_row()[0] ?? '';
    if ($lastGreet !== date('Y-m-d')) sch_run_greetings($conn, 40);
} catch (Throwable $e) { }

// ---- numbers
$mApproved = (int)$one("SELECT COUNT(*) FROM members WHERE status='approved'");
$mExpiring = (int)$one("SELECT COUNT(*) FROM members WHERE status='approved' AND valid_until BETWEEN CURDATE() AND (CURDATE() + INTERVAL 30 DAY)");
$mExpired  = (int)$one("SELECT COUNT(*) FROM members WHERE status='approved' AND valid_until < CURDATE()");
$mNew30    = (int)$one("SELECT COUNT(*) FROM members WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
$subTotal  = (int)$one("SELECT COUNT(*) FROM subscribers WHERE unsubscribed=0");
$postLive  = (int)$one("SELECT COUNT(*) FROM posts WHERE is_published=1");
$postDraft = (int)$one("SELECT COUNT(*) FROM posts WHERE is_published=0");
$visits30  = (int)$one("SELECT COALESCE(SUM(hits),0) FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY");
$visitsPrev = (int)$one("SELECT COALESCE(SUM(hits),0) FROM page_hits WHERE day BETWEEN CURDATE() - INTERVAL 59 DAY AND CURDATE() - INTERVAL 30 DAY");
$fbAvg     = $one("SELECT AVG(rating) FROM feedback"); $fbCount = (int)$one("SELECT COUNT(*) FROM feedback");
$failed24  = (int)$one("SELECT COUNT(*) FROM admin_login_log WHERE success=0 AND created_at > (NOW() - INTERVAL 24 HOUR)");

// ---- series: visits (30 days), memberships (6 months), guilds
$visitSeries = []; for ($i = 29; $i >= 0; $i--) $visitSeries[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach ($rows("SELECT day, SUM(hits) h FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY GROUP BY day") as $x) if (isset($visitSeries[$x['day']])) $visitSeries[$x['day']] = (int)$x['h'];
$maxVisit = max(1, max($visitSeries));
$months = []; for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i month"))] = ['label' => date('M', strtotime("first day of -$i month")), 'n' => 0];
foreach ($rows("SELECT DATE_FORMAT(created_at,'%Y-%m') ym, COUNT(*) c FROM members WHERE created_at >= (CURDATE() - INTERVAL 6 MONTH) GROUP BY ym") as $x) if (isset($months[$x['ym']])) $months[$x['ym']]['n'] = (int)$x['c'];
$maxM = max(1, max(array_column($months, 'n')));
$byGuild = $rows("SELECT g.name, COUNT(m.id) c FROM guilds g LEFT JOIN members m ON m.guild_id=g.id AND m.status='approved' GROUP BY g.id ORDER BY c DESC, g.sort_order LIMIT 6");
$maxG = max(1, max(array_map(fn($g) => (int)$g['c'], $byGuild ?: [['c' => 1]])));
$topPages = $rows("SELECT page, SUM(hits) h FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY GROUP BY page ORDER BY h DESC LIMIT 6"); $maxP = max(1, ...array_map(fn($x) => (int)$x['h'], $topPages ?: [['h' => 1]]));
$topPosts = $rows("SELECT id, title, views FROM posts WHERE is_published=1 ORDER BY views DESC LIMIT 5");

// ---- calendar: the next events & holidays
$upcoming = []; try { $upcoming = array_slice(sch_occurrences($conn, date('Y-m-d'), date('Y-m-d', strtotime('+60 day'))), 0, 7); } catch (Throwable $e) { }
$eventsMonth = count(array_filter($upcoming, fn($o) => $o['kind'] !== 'holiday' && $o['date'] <= date('Y-m-d', strtotime('+30 day'))));

// ---- recent activity (all sources, newest first)
$feed = [];
$push = function ($when, $icon, $title, $sub, $url) use (&$feed) { $feed[] = ['t' => strtotime($when), 'i' => $icon, 'a' => $title, 'b' => $sub, 'u' => $url]; };
if (admin_can('members')) foreach ($rows("SELECT id, full_name, status, created_at FROM members ORDER BY id DESC LIMIT 5") as $x) $push($x['created_at'], '👤', $x['full_name'] . ' applied for membership', ucfirst($x['status']), 'member-view.php?id=' . $x['id']);
if (admin_can('enquiries')) foreach ($rows("SELECT id, name, topic, type, created_at FROM enquiries ORDER BY id DESC LIMIT 5") as $x) $push($x['created_at'], '✉️', 'Enquiry from ' . $x['name'], $x['topic'] ?: ucfirst($x['type']), 'enquiry-view.php?id=' . $x['id']);
if (admin_can('enquiries')) foreach ($rows("SELECT id, ref_no, about, rating, created_at FROM feedback ORDER BY id DESC LIMIT 4") as $x) $push($x['created_at'], '⭐', 'New feedback · ' . $x['rating'] . '/5', $x['about'] ?: $x['ref_no'], 'feedback.php?view=' . $x['id']);
if (admin_can('sacco')) foreach ($rows("SELECT id, full_name, reg_no, created_at FROM sacco_applications ORDER BY id DESC LIMIT 4") as $x) $push($x['created_at'], '🏦', $x['full_name'] . ' applied to the SACCO', $x['reg_no'], 'sacco-applications.php?view=' . $x['id']);
if (admin_can('support')) foreach ($rows("SELECT id, ref_no, org_name, contact_name, partnership_type, created_at FROM partner_applications ORDER BY id DESC LIMIT 4") as $x) $push($x['created_at'], '🤝', ($x['org_name'] ?: $x['contact_name']) . ' wants to partner', $x['partnership_type'], 'partner-applications.php?view=' . $x['id']);
if (admin_can('support')) foreach ($rows("SELECT id, name, kind, ref_no, created_at FROM engagements ORDER BY id DESC LIMIT 4") as $x) $push($x['created_at'], '❤️', ucfirst($x['kind']) . ' request from ' . $x['name'], $x['ref_no'], 'support-view.php?id=' . $x['id']);
usort($feed, fn($a, $b) => $b['t'] <=> $a['t']); $feed = array_slice($feed, 0, 9);
$ago = function ($t) { $d = time() - $t; return $d < 3600 ? max(1, (int)($d / 60)) . ' min ago' : ($d < 86400 ? (int)($d / 3600) . ' h ago' : ($d < 172800 ? 'yesterday' : date('d M', $t))); };

// ---- site health (compact)
$S = mem_settings($conn);
$health = []; $add = function ($state, $label, $hint = '') use (&$health) { $health[] = [$state, $label, $hint]; };
$meRow = $conn->query("SELECT password_hash FROM admins WHERE id=" . (int)$_SESSION['admin_id'])->fetch_assoc();
if ($meRow && password_verify('admin123', $meRow['password_hash'])) $add('bad', 'You are still using the default password', 'Change it under My account.');
$uploadDir = __DIR__ . "/../assets/images/uploads"; $add(is_dir($uploadDir) && is_writable($uploadDir) ? 'ok' : 'bad', 'Image uploads folder is writable');
$add(is_file(MEM_STORAGE_DIR . ".htaccess") ? 'ok' : 'bad', 'Private member documents are protected');
$add(($S['president_signature'] ?? '') !== '' && ($S['president_name'] ?? '') !== '' ? 'ok' : 'meh', 'Membership card signature set', 'Upload the president\'s signature in Membership settings.');
$add($failed24 > 10 ? 'bad' : 'ok', $failed24 . ' failed sign-in(s) in 24 h', $failed24 > 10 ? 'Possible password guessing.' : '');
$cronLast = ''; try { $cronLast = $conn->query("SELECT setting_value FROM settings WHERE setting_key='sch_greet_last_run'")->fetch_row()[0] ?? ''; } catch (Throwable $e) { }
$add($cronLast === date('Y-m-d') ? 'ok' : 'meh', $cronLast === date('Y-m-d') ? 'Daily task (reminders & greetings) ran today' : 'Daily task has not run today', 'Set up the daily task in Calendar → Email & SMS settings.');

$hour = (int)date('G'); $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$first = explode(' ', trim($_SESSION['admin_name'] ?? 'Admin'))[0];
include __DIR__ . "/includes/admin-header.php";

$quick = array_filter([
    ['✍️', 'Write a story', 'Publish news with pictures', 'post-edit.php', 'news'],
    ['📅', 'Add an event', 'Calendar, venue & link', 'schedule.php#event-form', 'events'],
    ['📨', 'Compose a newsletter', 'Write or upload a PDF', 'newsletter-edit.php?kind=written', 'news'],
    ['👥', 'Review members', 'Approve & issue numbers', 'members.php?status=pending', 'members'],
    ['🎟️', 'Send invitations', 'Personalised PDF + RSVP', 'invitations.php', 'invitations'],
    ['🎉', 'Holiday greetings', 'Message members on holidays', 'greetings.php', 'events'],
    ['💬', 'Reply to feedback', 'Read, publish, respond', 'feedback.php?status=new', 'enquiries'],
    ['🖋️', 'Edit a page', 'Text, images, sections', 'pages.php', 'website'],
], fn($q) => admin_can($q[4], ROLE_VIEW));
$reports = array_filter([
    ['Members', 'Registered members', 'members.php', 'members'], ['Enquiries', 'All enquiries', 'enquiries.php', 'enquiries'], ['Feedback', 'Ratings & comments', 'feedback.php', 'enquiries'],
    ['Subscribers', 'Newsletter list', 'subscribers.php', 'news'], ['Support requests', 'Donations & sponsors', 'support-requests.php', 'support'], ['SACCO applications', 'Applicants & numbers', 'sacco-applications.php', 'sacco'],
    ['Partnerships', 'Co-host applications', 'partner-applications.php', 'support'],
], fn($r) => admin_can($r[3], ROLE_VIEW));
$trend = $visitsPrev > 0 ? round(($visits30 - $visitsPrev) / $visitsPrev * 100) : null;
?>
<div class="dx-hello">
  <div><h2><?php echo $greet . ', ' . htmlspecialchars($first); ?> 👋</h2><p><?php echo date('l, j F Y'); ?> · <?php echo htmlspecialchars(admin_role_label()); ?> — everything you need to run NUFA, in one place.</p></div>
  <div class="dx-chips"><a class="admin-btn admin-btn-line" href="../index.php" target="_blank">View website ↗</a><a class="admin-btn admin-btn-brand" href="#" onclick="document.getElementById('axSearch').click();return false;">Search / jump to… <kbd style="margin-left:6px;font-size:.66rem">Ctrl K</kbd></a></div>
</div>

<?php if ($__attn): ?>
<div class="dx-attn"><?php foreach ($__attn as $x): ?><a href="<?php echo $x[2]; ?>"><b><?php echo (int)$x[0]; ?></b><span><?php echo htmlspecialchars($x[1]); ?><small>Open and take action →</small></span></a><?php endforeach; ?></div>
<?php else: ?><div class="dx-ok">✅ You're all caught up — nothing is waiting for review.</div><?php endif; ?>

<div class="dx-kpis">
  <a class="dx-kpi dark" href="members.php?status=approved"><span>Active members</span><b><?php echo number_format($mApproved); ?></b><small>+<?php echo $mNew30; ?> applied in 30 days<?php echo $mExpiring ? ' · ' . $mExpiring . ' expiring soon' : ''; ?></small></a>
  <a class="dx-kpi" href="subscribers.php"><span>Newsletter subscribers</span><b><?php echo number_format($subTotal); ?></b><small>Active on the mailing list</small></a>
  <a class="dx-kpi" href="posts.php"><span>Stories published</span><b><?php echo $postLive; ?></b><small><?php echo $postDraft; ?> draft<?php echo $postDraft === 1 ? '' : 's'; ?></small></a>
  <a class="dx-kpi" href="schedule.php"><span>Events · next 30 days</span><b><?php echo $eventsMonth; ?></b><small>Plus public holidays ahead</small></a>
  <div class="dx-kpi"><span>Website visits · 30 days</span><b><?php echo number_format($visits30); ?></b><small><?php echo $trend === null ? 'Counting started recently' : ('<span class="' . ($trend >= 0 ? 'up' : '') . '">' . ($trend >= 0 ? '▲ ' : '▼ ') . abs($trend) . '%</span> vs previous 30 days'); ?></small></div>
  <a class="dx-kpi" href="feedback.php?tab=insights"><span>Feedback rating</span><b><?php echo $fbCount ? number_format($fbAvg, 1) . ' ★' : '—'; ?></b><small><?php echo $fbCount; ?> response<?php echo $fbCount === 1 ? '' : 's'; ?></small></a>
</div>

<div class="dx-grid">
  <div>
    <div class="admin-card"><div class="admin-card-head"><h2>Quick actions</h2><span class="admin-help">The most common jobs, one click away</span></div>
      <div class="dx-quick"><?php foreach ($quick as $q): ?><a href="<?php echo $q[3]; ?>"><i><?php echo $q[0]; ?></i><span><?php echo htmlspecialchars($q[1]); ?><small><?php echo htmlspecialchars($q[2]); ?></small></span></a><?php endforeach; ?></div></div>

    <div class="admin-card"><div class="admin-card-head"><h2>Website visits — last 30 days</h2><span class="admin-help">Anonymous page counts · no cookies or IP addresses</span></div>
      <?php if (!array_sum($visitSeries)): ?><p class="admin-help">Visits will appear here as people browse the website (your own admin visits are not counted).</p><?php endif; ?>
      <div class="dx-chart wide"><?php foreach ($visitSeries as $d => $n): ?><div class="c" title="<?php echo date('d M', strtotime($d)) . ': ' . $n; ?> visits"><i style="height:<?php echo max(3, round($n / $maxVisit * 105)); ?>px"></i><span><?php echo date('j', strtotime($d)) % 5 === 0 ? date('j', strtotime($d)) : '&nbsp;'; ?></span></div><?php endforeach; ?></div></div>

    <div class="edit-grid" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:20px">
      <div class="admin-card"><div class="admin-card-head"><h2>Membership applications</h2><span class="admin-help">last 6 months</span></div>
        <div class="dx-chart"><?php foreach ($months as $m): ?><div class="c"><b><?php echo $m['n']; ?></b><i style="height:<?php echo max(3, round($m['n'] / $maxM * 80)); ?>px"></i><span><?php echo $m['label']; ?></span></div><?php endforeach; ?></div></div>
      <div class="admin-card"><div class="admin-card-head"><h2>Members by guild</h2></div>
        <div class="dx-top"><?php foreach ($byGuild as $g): ?><div class="r"><span><?php echo htmlspecialchars($g['name']); ?></span><div class="bar"><i style="width:<?php echo round($g['c'] / $maxG * 100); ?>%"></i></div><em><?php echo (int)$g['c']; ?></em></div><?php endforeach; if (!$byGuild) echo '<p class="admin-help">No guilds yet.</p>'; ?></div></div>
    </div>

    <div class="admin-card"><div class="admin-card-head"><h2>Reports &amp; downloads</h2><span class="admin-help">Every list can be exported</span></div>
      <div class="dx-reports"><?php foreach ($reports as $r): ?><div class="dx-report"><div><b><?php echo $r[0]; ?></b><small><?php echo $r[1]; ?></small></div><div class="fmt"><?php foreach (['pdf' => 'PDF', 'xlsx' => 'XLS', 'docx' => 'DOC', 'csv' => 'CSV'] as $k => $l): ?><a href="<?php echo $r[2] . '?export=' . $k; ?>" title="Download as <?php echo $l; ?>"><?php echo $l; ?></a><?php endforeach; ?></div></div><?php endforeach; ?></div>
      <p class="admin-help" style="margin-top:10px">Every table in the admin also has an <b>Export</b> button (PDF, Excel, Word, CSV) for exactly what you see on screen.</p></div>
  </div>

  <div>
    <div class="admin-card"><div class="admin-card-head"><h2>Coming up</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="schedule.php">Calendar</a></div>
      <div class="dx-cal"><?php foreach ($upcoming as $o): $t = strtotime($o['date']); $cls = $o['kind'] === 'holiday' ? 'hol' : ($o['kind'] === 'important' ? 'imp' : ''); ?>
        <a href="schedule.php?<?php echo 'am=' . date('Y-m', $t); ?>"><div class="dx-date <?php echo $cls; ?>"><b><?php echo date('j', $t); ?></b><span><?php echo date('M', $t); ?></span></div><div><div class="tt"><?php echo htmlspecialchars($o['title']); ?></div><div class="ss"><?php echo date('l', $t) . ' · ' . htmlspecialchars(sch_kinds()[$o['kind']] ?? '') . ($o['kind'] === 'programme' ? ' · ' . htmlspecialchars(sch_time($o)) : ''); ?></div></div></a>
      <?php endforeach; if (!$upcoming) echo '<p class="admin-help">Nothing in the next 60 days. <a href="schedule.php">Add events</a>.</p>'; ?></div></div>

    <div class="admin-card"><div class="admin-card-head"><h2>Recent activity</h2></div>
      <div class="dx-feed"><?php foreach ($feed as $x): ?><a href="<?php echo htmlspecialchars($x['u']); ?>"><span class="ic"><?php echo $x['i']; ?></span><div><div class="tt"><?php echo htmlspecialchars($x['a']); ?></div><div class="ss"><?php echo htmlspecialchars((string)$x['b']); ?> · <?php echo $ago($x['t']); ?></div></div></a><?php endforeach; if (!$feed) echo '<p class="admin-help">Nothing yet — new applications, enquiries and feedback will show up here.</p>'; ?></div></div>

    <div class="admin-card"><div class="admin-card-head"><h2>Most visited pages</h2><span class="admin-help">30 days</span></div>
      <div class="dx-top"><?php foreach ($topPages as $p): ?><div class="r"><span><?php echo htmlspecialchars(ucfirst(str_replace(['-', '_'], ' ', $p['page']))); ?></span><div class="bar"><i style="width:<?php echo round($p['h'] / $maxP * 100); ?>%"></i></div><em><?php echo number_format((int)$p['h']); ?></em></div><?php endforeach; if (!$topPages) echo '<p class="admin-help">No visits recorded yet.</p>'; ?></div>
      <?php if ($topPosts): ?><h3 class="mem-h3" style="margin-top:16px">Top stories</h3><div class="dx-top"><?php foreach ($topPosts as $p): ?><div class="r"><span><a href="post-edit.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></a></span><em><?php echo number_format((int)$p['views']); ?> views</em></div><?php endforeach; ?></div><?php endif; ?></div>

    <div class="admin-card"><div class="admin-card-head"><h2>Health check</h2></div>
      <div class="dx-health"><?php foreach ($health as [$st, $label, $hint]): ?><div><i class="<?php echo $st; ?>"></i><span><?php echo htmlspecialchars($label); ?><?php if ($hint && $st !== 'ok'): ?><small><?php echo htmlspecialchars($hint); ?></small><?php endif; ?></span></div><?php endforeach; ?></div></div>
  </div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
