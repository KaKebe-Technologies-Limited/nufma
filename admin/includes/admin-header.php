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
    'settings'    => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
    'users'       => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.6-4 4-6 8-6s7.4 2 8 6" stroke-linecap="round"/>',
];
$__enqNew = 0; $__subs = 0; $__msgs = 0;
if (isset($conn)) {
    $__q = $conn->query("SELECT COUNT(*) c FROM enquiries WHERE status='new'"); if ($__q) $__enqNew = (int)$__q->fetch_assoc()['c'];
    $__q = $conn->query("SELECT COUNT(*) c FROM subscribers"); if ($__q) $__subs = (int)$__q->fetch_assoc()['c'];
    $__q = $conn->query("SELECT COUNT(*) c FROM messages"); if ($__q) $__msgs = (int)$__q->fetch_assoc()['c'];
}
$__ico += [
    'programmes' => '<path d="M3 7l9-4 9 4-9 4-9-4Z"/><path d="M7 10v5c0 1.500 2.200 3 5 3s5-1.500 5-3v-5"/>',
    'resources'  => '<path d="M4 4h10l6 6v10H4V4Z"/><path d="M14 4v6h6M8 14h8M8 17h5"/>',
    'enquiries'  => '<path d="M4 5h16v11H9l-5 4V5Z"/><path d="M8 9h8M8 12h5"/>',
];
$__nav = [
    'Overview' => [['dashboard', 'index.php', 'Dashboard']],
    'Association' => array_values(array_filter([
        ['members', 'members.php', 'Membership', $__pending],
        ['enquiries', 'enquiries.php', 'Enquiries', $__enqNew],
        $__msgs > 0 ? ['messages', 'messages.php', 'Messages (earlier)', $__unread] : null,
        $__subs > 0 ? ['subscribers', 'subscribers.php', 'Subscribers (earlier)'] : null,
    ])),
    'Website content' => [
        ['posts', 'posts.php', 'News & Stories'],
        ['content', 'content.php', 'Page Content'],
        ['programmes', 'programmes.php', 'Programmes'],
        ['resources', 'resources.php', 'Resources'],
        ['gallery', 'gallery.php', 'Gallery & Videos'],
        ['sliders', 'sliders.php', 'Hero Images'],
    ],
    'Awards & people' => [
        ['awards', 'award-editions.php', 'Award Editions'],
        ['team', 'team.php', 'Leadership'],
        ['partners', 'partners.php', 'Partners'],
    ],
    'System' => [
        ['settings', 'settings.php', 'Site Settings'],
        ['users', 'users.php', 'Admin Users'],
    ],
];
$__initial = strtoupper(mb_substr($_SESSION['admin_name'] ?? 'A', 0, 1));
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($adminTitle); ?> — NUFA Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/style.css'); ?>">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/admin.css'); ?>">
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
      <h1><?php echo htmlspecialchars($adminTitle); ?></h1>
      <div class="admin-topbar-right">
        <a href="../index.php" target="_blank" class="admin-top-link">View site</a>
        <div class="admin-user" id="adminUser">
          <button type="button" class="admin-user-btn" aria-haspopup="true" aria-expanded="false">
            <span class="admin-avatar"><?php echo htmlspecialchars($__initial); ?></span>
            <span class="admin-user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
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
