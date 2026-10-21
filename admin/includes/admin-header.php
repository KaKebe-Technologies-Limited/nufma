<?php
if (!isset($adminTitle)) $adminTitle = "Dashboard";
if (!isset($adminNav)) $adminNav = "";
require_once __DIR__ . "/../../includes/membership.php";

$__pending = 0; $__unread = 0;
if (isset($conn)) {
    $__q = $conn->query("SELECT COUNT(*) c FROM members WHERE status='pending'");  if ($__q) $__pending = (int)$__q->fetch_assoc()['c'];
    $__q = $conn->query("SELECT COUNT(*) c FROM messages WHERE is_read=0");        if ($__q) $__unread = (int)$__q->fetch_assoc()['c'];
}

$__ico = [
    'dashboard'   => '<rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="11" width="8" height="10" rx="2"/><rect x="3" y="14" width="8" height="7" rx="2"/>',
    'members'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.5 1.8-2 3-2s2.4.5 3 2M14 10h4M14 13h3" stroke-linecap="round"/>',
    'messages'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
    'subscribers' => '<path d="M4 20v-1a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v1M15 4.2a3.5 3.5 0 0 1 0 6.6M17 20v-1a5 5 0 0 0-3-4.6" stroke-linecap="round"/><circle cx="9.5" cy="8" r="3.5"/>',
    'posts'       => '<path d="M4 5h13l3 3v11H4V5Z" stroke-linejoin="round"/><path d="M8 12h8M8 16h5" stroke-linecap="round"/>',
    'content'     => '<path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h4" stroke-linecap="round"/>',
    'sliders'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 15l5-4 4 3 5-5 4 3"/>',
    'gallery'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'awards'      => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4Z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M8 20h8" stroke-linecap="round"/>',
    'team'        => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.4"/><path d="M3 19c.5-3 3-5 6-5s5.5 2 6 5M16 14c2.5 0 4.4 1.6 5 4" stroke-linecap="round"/>',
    'partners'    => '<circle cx="12" cy="12" r="9"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18"/>',
    'maintenance' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-2.2-.6-.6-2.2 2.2-2.6Z"/>',
    'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
    'users'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.6-4 4-6 8-6s7.4 2 8 6" stroke-linecap="round"/>',
];
$__enqNew = 0; $__subs = 0; $__msgs = 0; $__supNew = 0; $__sacNew = 0; $__paNew = 0; $__fbNew = 0; $__aevNew = 0; $__acNew = 0;
if (isset($conn)) {
    $__q = $conn->query("SELECT COUNT(*) c FROM enquiries WHERE status='new'"); if ($__q) $__enqNew = (int)$__q->fetch_assoc()['c'];
    $__q = $conn->query("SELECT COUNT(*) c FROM engagements WHERE status='pending'"); if ($__q) $__supNew = (int)$__q->fetch_assoc()['c'];
    $__q = $conn->query("SELECT COUNT(*) c FROM subscribers"); if ($__q) $__subs = (int)$__q->fetch_assoc()['c'];
    try { $__q = $conn->query("SELECT COUNT(*) c FROM sacco_applications WHERE status='pending'"); if ($__q) $__sacNew = (int)$__q->fetch_assoc()['c']; } catch (Throwable $__e) {}
    try { $__q = $conn->query("SELECT COUNT(*) c FROM partner_applications WHERE status='pending'"); if ($__q) $__paNew = (int)$__q->fetch_assoc()['c']; } catch (Throwable $__e) {}
    try { $__q = $conn->query("SELECT COUNT(*) c FROM feedback WHERE status='new'"); if ($__q) $__fbNew = (int)$__q->fetch_assoc()['c']; } catch (Throwable $__e) {}
    try { $__q = $conn->query("SELECT COUNT(*) c FROM award_event_apps WHERE status='pending'"); if ($__q) $__aevNew = (int)$__q->fetch_assoc()['c']; } catch (Throwable $__e) {}
    try { $__q = $conn->query("SELECT (SELECT COUNT(*) FROM academy_trainers WHERE status='pending') + (SELECT COUNT(*) FROM academy_enrolments WHERE status='pending') c"); if ($__q) $__acNew = (int)$__q->fetch_assoc()["c"]; } catch (Throwable $__e) {}
    $__q = $conn->query("SELECT COUNT(*) c FROM messages"); if ($__q) $__msgs = (int)$__q->fetch_assoc()['c'];
}
$__ico += [
    'pages'      => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5" stroke-linecap="round"/>',
    'map'        => '<path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2V6Z"/><path d="M9 4v14M15 6v14"/>',
    'menus'      => '<path d="M4 6h16M4 12h16M4 18h10" stroke-linecap="round"/>',
    'appearance' => '<circle cx="12" cy="12" r="9"/><circle cx="8.5" cy="10" r="1.2"/><circle cx="12" cy="7.5" r="1.2"/><circle cx="15.5" cy="10" r="1.2"/><path d="M12 21c-1.500 0-2-1-2-2s1-2 2-2h2c1.500 0 3-1 3-3"/>',
    'lists'      => '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" stroke-linecap="round"/>',
    'support'    => '<path d="M12 20s-7-4.500-7-10a4 4 0 0 1 7-2.500A4 4 0 0 1 19 10c0 5.500-7 10-7 10Z"/>',
    'schedule'   => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'awardsetup' => '<path d="M12 3l2.600 5.300 5.800.800-4.200 4.100 1 5.800L12 16.300 6.800 19l1-5.800L3.600 9.100l5.800-.800L12 3Z"/>',
    'newsletters' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6" stroke-linecap="round"/>',
    'invitations' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 8l9 5 9-5M8 16h3" stroke-linecap="round"/>',
    'roles'      => '<path d="M12 3l8 3v6c0 4.500-3.400 7.800-8 9-4.600-1.200-8-4.500-8-9V6l8-3Z"/><path d="M9 12l2 2 4-4" stroke-linecap="round"/>',
    'saccoapps'  => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4" stroke-linecap="round"/><circle cx="17" cy="17" r="3.500" fill="#fff"/><path d="M15.500 17l1 1 2-2" stroke-linecap="round"/>',
    'partnerapps' => '<path d="M8 12l3 3 5-6" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0Z"/>',
    'feedback'   => '<path d="M4 5h16v11H9l-5 4V5Z"/><path d="M12 8.500l1 2 2.200.3-1.600 1.500.4 2.200-2-1.100-2 1.100.4-2.200L8.800 10.800 11 10.500l1-2Z"/>',
    'greetings'  => '<path d="M4 11h16v9H4v-9Z"/><path d="M12 11v9M3 7h18v4H3V7Z"/><path d="M12 7c-2-3-5-3-5-1s3 1 5 1c2 0 5 1 5-1s-3-2-5 1Z"/>',
    'awardevents' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l3 2M9 2h6" stroke-linecap="round"/>',
    'saccoadm'   => '<ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v5c0 1.700 2.700 3 6 3s6-1.300 6-3V7M15 11.500c3 0 6 1 6 2.500v3c0 1.700-3 3-6.500 3"/>',
    'programmes' => '<path d="M3 7l9-4 9 4-9 4-9-4Z"/><path d="M7 10v5c0 1.500 2.200 3 5 3s5-1.500 5-3v-5"/>',
    'resources'  => '<path d="M4 4h10l6 6v10H4V4Z"/><path d="M14 4v6h6M8 14h8M8 17h5"/>',
    'guests'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.600 2.900-6 6.500-6s6.500 2.400 6.500 6M16 5.500a3.500 3.500 0 0 1 0 6M18 14.500c2.200.700 3.500 2.700 3.500 5.500"/>',
    'academy'    => '<path d="M3 9l9-5 9 5-9 5-9-5Z"/><path d="M7 11.500V16c0 1.400 2.300 3 5 3s5-1.600 5-3v-4.500M21 9v6"/>',
    'opps'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/><path d="M8 21h8" stroke-linecap="round"/>',
    'enquiries'  => '<path d="M4 5h16v11H9l-5 4V5Z"/><path d="M8 9h8M8 12h5"/>',
];
$__nav = [
    'Overview' => [['dashboard', 'index.php', 'Dashboard']],
    'People' => [
        ['members', 'members.php', 'Membership', $__pending],
        ['saccoapps', 'sacco-applications.php', 'SACCO applications', $__sacNew],
        ['team', 'team.php', 'Leadership & team'],
        ['subscribers', 'subscribers.php', 'Subscribers'],
    ],
    'Inbox' => array_values(array_filter([
        ['enquiries', 'enquiries.php', 'Enquiries', $__enqNew],
        ['feedback', 'feedback.php', 'Feedback', $__fbNew],
        ['support', 'support-requests.php', 'Support requests', $__supNew],
        ['partnerapps', 'partner-applications.php', 'Partnership applications', $__paNew],
        $__msgs > 0 ? ['messages', 'messages.php', 'Messages (earlier)', $__unread] : null,
    ])),
    'Events & awards' => [
        ['schedule', 'schedule.php', 'Calendar & reminders'],
        ['greetings', 'greetings.php', 'Holiday greetings'],
        ['awardevents', 'award-events.php', 'Awards pre-events', $__aevNew],
        ['guests', 'guests.php', 'Distinguished guests'],
        ['awards', 'award-editions.php', 'Award editions'],
        ['awardsetup', 'awards-setup.php', 'Awards setup'],
        ['invitations', 'invitations.php', 'Award invitations'],
    ],
    'Stories & media' => [
        ['posts', 'posts.php', 'News & stories'],
        ['newsletters', 'newsletters.php', 'Newsletters'],
        ['gallery', 'gallery.php', 'Gallery & videos'],
        ['resources', 'resources.php', 'Resources'],
        ['academy', 'academy.php', 'NUFA Academy', $__acNew],
        ['opps', 'opportunities.php', 'Opportunities'],
    ],
    'Programmes & partners' => [
        ['programmes', 'programmes.php', 'Programmes'],
        ['partners', 'partners.php', 'Partners'],
        ['saccoadm', 'sacco-manager.php', 'NUFA SACCO'],
    ],
    'Website' => [
        ['pages', 'pages.php', 'Page editor'],
        ['content', 'content.php', 'Page content'],
        ['sliders', 'sliders.php', 'Hero images'],
        ['menus', 'menus.php', 'Menus & footer'],
        ['appearance', 'appearance.php', 'Appearance & branding'],
        ['lists', 'lists.php', 'Form options'],
        ['map', 'website-map.php', 'Website map'],
    ],
    'System' => [
        ['settings', 'settings.php', 'Site settings'],
        ['maintenance', 'maintenance.php', 'Maintenance mode'],
        ['users', 'users.php', 'Admin users'],
        ['roles', 'roles.php', 'Roles & permissions'],
    ],
];
// hide what this role cannot open
foreach ($__nav as $__g => $__its) {
    $__nav[$__g] = array_values(array_filter($__its, fn($i) => $i && admin_can_file($i[1])));
    if (!$__nav[$__g]) unset($__nav[$__g]);
}
// command palette: every page this role can open + quick actions
$__cmd = [];
foreach ($__nav as $__g => $__its) foreach ($__its as $__i) $__cmd[] = [$__i[2], $__i[1], $__g];
foreach ([['Write a story', 'post-edit.php', 'news'], ['Add a calendar event', 'schedule.php#event-form', 'events'], ['Compose a newsletter', 'newsletter-edit.php?kind=written', 'news'], ['Upload a PDF newsletter', 'newsletter-edit.php?kind=pdf', 'news'], ['Create an award invitation', 'invitations.php', 'invitations'], ['Add a team member', 'team-edit.php', 'team'], ['Add a partner', 'partner-edit.php', 'website'], ['Add a programme', 'programme-edit.php', 'website'], ['Review pending members', 'members.php?status=pending', 'members'], ['Open the website', '../index.php', null]] as [$__l, $__u, $__m]) if ($__m === null || admin_can($__m, ROLE_VIEW)) $__cmd[] = [$__l, $__u, 'Quick action'];
$__attn = array_values(array_filter([
    [$__pending, 'Membership applications to review', 'members.php?status=pending', 'members'],
    [$__enqNew, 'New enquiries', 'enquiries.php?status=new', 'enquiries'],
    [$__fbNew, 'New feedback', 'feedback.php?status=new', 'enquiries'],
    [$__sacNew, 'SACCO applications', 'sacco-applications.php', 'sacco'],
    [$__paNew, 'Partnership applications', 'partner-applications.php', 'support'],
    [$__aevNew, 'Awards event applications', 'award-events.php?tab=apps', 'awards'],
    [$__supNew, 'Support requests', 'support-requests.php', 'support'],
], fn($x) => (int)$x[0] > 0 && admin_can($x[3], ROLE_VIEW)));
$__attnTotal = array_sum(array_map(fn($x) => (int)$x[0], $__attn));
$__initial = strtoupper(mb_substr($_SESSION['admin_name'] ?? 'A', 0, 1));
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($adminTitle); ?> — NUFA Admin</title>
<meta name="robots" content="noindex, nofollow">
<meta name="admin-csrf" content="<?php echo htmlspecialchars(admin_token()); ?>">
<link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/style.css'); ?>">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/admin.css'); ?>">
<link rel="stylesheet" href="../assets/css/admin-ui.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/admin-ui.css'); ?>">
</head>
<body class="admin-body">

<div class="admin-shell">
  <aside class="admin-sidebar" id="adminSidebar">
    <a href="index.php" class="admin-brand">
      <img src="../assets/images/logo/nufa-logo.png" alt="NUFA">
      <span>NUFA <small>Admin</small></span>
    </a>
    <nav class="admin-nav" aria-label="Admin">
      <?php foreach ($__nav as $group => $items): ?>
        <div class="admin-nav-group"><?php echo $group; ?></div>
        <?php foreach ($items as $it): ?>
        <a href="<?php echo $it[1]; ?>" class="<?php echo $adminNav === $it[0] ? 'active' : ''; ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><?php echo $__ico[$it[0]]; ?></svg>
          <?php echo $it[2]; ?>
          <?php if (!empty($it[3])): ?><span class="admin-count"><?php echo $it[3]; ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-foot">
      <a href="../index.php" target="_blank" class="admin-view-site">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14 4h6v6M20 4 10 14M6 6H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        View website
      </a>
      <a href="logout.php" class="admin-logout">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Log out
      </a>
    </div>
  </aside>

  <div class="admin-sidebar-overlay" id="adminOverlay"></div>

  <main class="admin-main">
    <header class="admin-topbar">
      <button class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </button>
      <div style="min-width:0"><span class="crumb"><?php echo htmlspecialchars(admin_role_label()); ?></span><h1><?php echo htmlspecialchars($adminTitle); ?></h1></div>
      <div class="admin-topbar-right">
        <button type="button" class="ax-search" id="axSearch" aria-label="Search or jump to a page"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg><span class="t">Search or jump to…</span><kbd>Ctrl K</kbd></button>
        <div class="ax-wrap"><button type="button" class="ax-new" id="axNewBtn" aria-haspopup="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span>New</span></button>
          <div class="ax-pop" id="axNew" hidden><h4>Create</h4>
            <?php foreach ([['Write a story', 'post-edit.php', 'news'], ['Add a calendar event', 'schedule.php#event-form', 'events'], ['Compose a newsletter', 'newsletter-edit.php?kind=written', 'news'], ['Create an award invitation', 'invitations.php', 'invitations'], ['Add a team member', 'team-edit.php', 'team'], ['Add a partner', 'partner-edit.php', 'website']] as [$__l, $__u, $__m]) if (admin_can($__m, ROLE_MANAGE)): ?><a href="<?php echo $__u; ?>"><?php echo $__l; ?></a><?php endif; ?></div></div>
        <div class="ax-wrap"><button type="button" class="ax-icon-btn" id="axBellBtn" aria-label="Things that need attention" aria-haspopup="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 1 1 12 0c0 6 2 7 2 7H4s2-1 2-7Z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg><?php if ($__attnTotal): ?><span class="dot"><?php echo $__attnTotal > 99 ? '99+' : $__attnTotal; ?></span><?php endif; ?></button>
          <div class="ax-pop" id="axBell" hidden><h4>Needs your attention</h4><?php foreach ($__attn as $__x): ?><a href="<?php echo $__x[2]; ?>"><?php echo $__x[1]; ?><b><?php echo (int)$__x[0]; ?></b></a><?php endforeach; if (!$__attn) echo '<p style="padding:10px 12px;color:var(--a-mut);margin:0">You are all caught up. 🎉</p>'; ?></div></div>
        <div class="admin-user" id="adminUser">
          <button type="button" class="admin-user-btn" aria-haspopup="true" aria-expanded="false">
            <span class="admin-avatar"><?php echo htmlspecialchars($__initial); ?></span>
            <span class="admin-user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?><small style="display:block;font-size:.68rem;opacity:.7;font-weight:600"><?php echo htmlspecialchars(admin_role_label()); ?></small></span>
          </button>
          <div class="admin-user-menu">
            <a href="account.php">My account</a>
            <a href="users.php">Admin users</a>
            <a href="logout.php">Log out</a>
          </div>
        </div>
      </div>
    </header>
    <div class="admin-content">
