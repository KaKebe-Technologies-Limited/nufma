<?php if(!isset($adminTitle)) $adminTitle = "Dashboard"; ?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($adminTitle); ?> — NUFA Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">

<div class="admin-shell">
  <aside class="admin-sidebar" id="adminSidebar">
    <a href="index.php" class="admin-brand">
      <img src="../assets/images/logo/nufa-logo.png" alt="NUFA">
      <span>NUFA <small>Admin</small></span>
    </a>
    <nav class="admin-nav">
      <a href="index.php" class="<?php echo ($adminNav??'')==='dashboard'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="3" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="13" width="8" height="8" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>
        Dashboard
      </a>
      <a href="posts.php" class="<?php echo ($adminNav??'')==='posts'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Blog Posts
      </a>
      <a href="sliders.php" class="<?php echo ($adminNav??'')==='sliders'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M3 15l5-4 4 3 5-5 4 3" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        Hero Slider
      </a>
      <a href="gallery.php" class="<?php echo ($adminNav??'')==='gallery'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
        Gallery
      </a>
      <a href="partners.php" class="<?php echo ($adminNav??'')==='partners'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18" stroke="currentColor" stroke-width="1.3"/></svg>
        Partners
      </a>
      <a href="subscribers.php" class="<?php echo ($adminNav??'')==='subscribers'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 20v-1a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v1M15 4.2a3.5 3.5 0 0 1 0 6.6M17 20v-1a5 5 0 0 0-3-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/></svg>
        Subscribers
      </a>
      <a href="messages.php" class="<?php echo ($adminNav??'')==='messages'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Messages
      </a>
      <a href="settings.php" class="<?php echo ($adminNav??'')==='settings'?'active':''; ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
        Settings
      </a>
    </nav>
    <div class="admin-sidebar-foot">
      <a href="../index.php" target="_blank" class="admin-view-site">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M14 4h6v6M20 4 10 14M6 6H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        View Site
      </a>
      <a href="logout.php" class="admin-logout">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Log Out
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
      <div class="admin-topbar-user"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
    </header>
    <div class="admin-content">
