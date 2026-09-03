<?php
/**
 * Shared site header. Each page sets $pageTitle, $pageDesc and $activeNav
 * before including this file.
 */
if(!isset($pageTitle)) $pageTitle = "NUFA — Northern Uganda Filmmakers Association | Home of Northern Storytellers";
if(!isset($pageDesc)) $pageDesc = "NUFA is the home of Northern Uganda's filmmakers — uniting, training and celebrating storytellers across Acholi, Lango, West Nile and Karamoja through the annual NUFA Awards.";
if(!isset($activeNav)) $activeNav = "";
if(!isset($base)) $base = "";
if(!isset($ogImage)) $ogImage = "assets/images/og/og-default.jpg";
if(!isset($ogImageAlt)) $ogImageAlt = "NUFA — Northern Uganda Filmmakers Association";
if(!isset($ogType)) $ogType = "website";
if(!isset($metaRobots)) $metaRobots = "index, follow, max-image-preview:large, max-snippet:-1";
if(!isset($articleMeta)) $articleMeta = null; // ['published'=>, 'modified'=>, 'section'=>, 'author'=>]
if(!isset($canonicalPath)) $canonicalPath = basename($_SERVER['PHP_SELF']);
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https://' : 'http://';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
$siteUrl = $scheme . $_SERVER['HTTP_HOST'] . $scriptDir;
$canonicalUrl = $siteUrl . '/' . ltrim($canonicalPath, '/');
$ogImageUrl = preg_match('~^https?://~', $ogImage) ? $ogImage : $siteUrl . '/' . ltrim($ogImage, '/');
if(!isset($conn)) require __DIR__ . "/db.php";
require __DIR__ . "/fonts.php";
$activeFont = nufa_get_font_preset($conn);
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $pageTitle; ?></title>
<meta name="description" content="<?php echo $pageDesc; ?>">
<meta name="robots" content="<?php echo htmlspecialchars($metaRobots); ?>">
<meta name="theme-color" content="#FAF6EE">
<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

<meta property="og:site_name" content="NUFA — Northern Uganda Filmmakers Association">
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($pageDesc); ?>">
<meta property="og:type" content="<?php echo htmlspecialchars($ogType); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($ogImageUrl); ?>">
<meta property="og:image:secure_url" content="<?php echo htmlspecialchars($ogImageUrl); ?>">
<meta property="og:image:type" content="<?php echo str_ends_with($ogImageUrl, '.png') ? 'image/png' : 'image/jpeg'; ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?php echo htmlspecialchars($ogImageAlt); ?>">
<meta property="og:locale" content="en_UG">
<?php if ($articleMeta): ?>
<meta property="article:published_time" content="<?php echo htmlspecialchars($articleMeta['published'] ?? ''); ?>">
<meta property="article:modified_time" content="<?php echo htmlspecialchars($articleMeta['modified'] ?? $articleMeta['published'] ?? ''); ?>">
<meta property="article:section" content="<?php echo htmlspecialchars($articleMeta['section'] ?? 'News'); ?>">
<meta property="article:author" content="<?php echo htmlspecialchars($articleMeta['author'] ?? 'NUFA Team'); ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($pageDesc); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImageUrl); ?>">
<meta name="twitter:image:alt" content="<?php echo htmlspecialchars($ogImageAlt); ?>">
<meta name="twitter:site" content="@NUFA_OFFICIAL1">
<meta name="twitter:creator" content="@NUFA_OFFICIAL1">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "PerformingArtsOrganization",
  "name": "Northern Uganda Filmmakers Association",
  "alternateName": "NUFA",
  "url": "<?php echo $siteUrl; ?>/",
  "logo": "<?php echo $siteUrl; ?>/assets/images/logo/nufa-logo.png",
  "sameAs": [
    "https://x.com/NUFA_OFFICIAL1",
    "https://www.facebook.com/nufa2026",
    "https://www.instagram.com/nufa2026",
    "https://www.linkedin.com/company/northern-uganda-film-makers-association-nufa"
  ],
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Acholi Inn",
    "addressLocality": "Gulu City",
    "addressRegion": "Northern Region",
    "addressCountry": "UG"
  },
  "description": "NUFA unites, trains and celebrates filmmakers across Acholi, Lango, West Nile and Karamoja through the annual NUFA Awards."
}
</script>

<link rel="icon" href="<?php echo $base; ?>assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?<?php echo $activeFont['google']; ?>&display=swap">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
<style>
:root{
  --font-heading:<?php echo $activeFont['heading']; ?>;
  --font-body:<?php echo $activeFont['body']; ?>;
  --font-display:<?php echo $activeFont['display']; ?>;
}
</style>
</head>
<body>

<header class="site-header">
  <div class="container-wide">
    <a href="<?php echo $base; ?>index.php" class="brand">
      <span class="mark"><img src="<?php echo $base; ?>assets/images/logo/nufa-logo.png" alt="NUFA logo" width="42" height="42"></span>
      <span class="word">NUFA<small>Filmmakers Association</small></span>
    </a>

    <nav class="main-nav" aria-label="Primary">
      <ul>
        <li class="<?php echo $activeNav==='home'?'active':''; ?>"><a href="<?php echo $base; ?>index.php">Home</a></li>
        <li class="<?php echo $activeNav==='about'?'active':''; ?>"><a href="<?php echo $base; ?>about.php">About</a></li>
        <li class="has-dropdown <?php echo $activeNav==='awards'?'active':''; ?>">
          <a href="<?php echo $base; ?>awards.php">Awards</a>
          <button type="button" class="caret-btn" aria-label="Toggle Awards menu" aria-expanded="false">
            <svg class="caret" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
          </button>
          <div class="dropdown">
            <a href="<?php echo $base; ?>awards.php#nufa27"><span class="tag">Upcoming</span>2027 Awards</a>
            <a href="<?php echo $base; ?>awards.php#nufa26"><span class="tag">Past edition</span>2026 Awards</a>
            <a href="<?php echo $base; ?>awards.php#nufa25"><span class="tag">Past edition</span>2025 Awards</a>
          </div>
        </li>
        <li class="<?php echo $activeNav==='gallery'?'active':''; ?>"><a href="<?php echo $base; ?>gallery.php">Gallery</a></li>
        <li class="<?php echo $activeNav==='news'?'active':''; ?>"><a href="<?php echo $base; ?>news.php">News</a></li>
        <li class="<?php echo $activeNav==='partners'?'active':''; ?>"><a href="<?php echo $base; ?>partners.php">Partners</a></li>
        <li class="<?php echo $activeNav==='contact'?'active':''; ?>"><a href="<?php echo $base; ?>contact.php">Contact</a></li>
      </ul>
    </nav>

    <div class="header-cta">
      <a href="<?php echo $base; ?>partners.php#become" class="btn btn-brand btn-sm">Become a Partner</a>
      <button class="nav-toggle" aria-label="Open menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </button>
    </div>
  </div>
</header>

<div class="mobile-nav" aria-hidden="true">
  <div class="mobile-nav-top">
    <a href="<?php echo $base; ?>index.php" class="brand">
      <span class="mark"><img src="<?php echo $base; ?>assets/images/logo/nufa-logo.png" alt="NUFA logo" width="42" height="42"></span>
      <span class="word">NUFA</span>
    </a>
    <button class="mobile-nav-close" aria-label="Close menu">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </button>
  </div>

  <ul>
    <li><a href="<?php echo $base; ?>index.php">Home</a></li>
    <li><a href="<?php echo $base; ?>about.php">About</a></li>
    <li>
      <a href="#" data-toggle-sub onclick="return false;">Awards
        <svg width="14" height="14" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      </a>
      <div class="mobile-sub">
        <a href="<?php echo $base; ?>awards.php#nufa27">2027 Awards — Upcoming</a>
        <a href="<?php echo $base; ?>awards.php#nufa26">2026 Awards — Past edition</a>
        <a href="<?php echo $base; ?>awards.php#nufa25">2025 Awards — Past edition</a>
      </div>
    </li>
    <li><a href="<?php echo $base; ?>gallery.php">Gallery</a></li>
    <li><a href="<?php echo $base; ?>news.php">News</a></li>
    <li><a href="<?php echo $base; ?>partners.php">Partners</a></li>
    <li><a href="<?php echo $base; ?>contact.php">Contact</a></li>
  </ul>

  <div class="mobile-nav-foot">
    <a href="<?php echo $base; ?>partners.php#become" class="btn btn-brand">Become a Partner</a>
    <div class="mobile-social social-row">
      <a href="https://x.com/NUFA_OFFICIAL1" target="_blank" rel="noopener" aria-label="X / Twitter"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8.2-9.3L1 2h7.2l5 6.6L18.9 2Zm-1.2 18h1.7L7.4 4h-1.8l12.1 16Z"/></svg></a>
      <a href="https://www.facebook.com/nufa2026" target="_blank" rel="noopener" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-8.4h2.8l.4-3.3h-3.2V8.1c0-.95.27-1.6 1.63-1.6H17V3.5c-.3-.04-1.3-.13-2.5-.13-2.47 0-4.16 1.5-4.16 4.27v2.6H7.5v3.3h2.84V22h3.16Z"/></svg></a>
      <a href="https://www.instagram.com/nufa2026" target="_blank" rel="noopener" aria-label="Instagram"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8.4a3.6 3.6 0 1 0 0 7.2 3.6 3.6 0 0 0 0-7.2Zm0 5.9a2.3 2.3 0 1 1 0-4.6 2.3 2.3 0 0 1 0 4.6ZM16.9 6a.85.85 0 1 1 0 1.7.85.85 0 0 1 0-1.7Z"/><path d="M17 2H7a5 5 0 0 0-5 5v10a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V7a5 5 0 0 0-5-5Zm3.3 15a3.3 3.3 0 0 1-3.3 3.3H7A3.3 3.3 0 0 1 3.7 17V7A3.3 3.3 0 0 1 7 3.7h10A3.3 3.3 0 0 1 20.3 7v10Z"/></svg></a>
      <a href="https://www.linkedin.com/company/northern-uganda-film-makers-association-nufa" target="_blank" rel="noopener" aria-label="LinkedIn"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.94 8.5H3.56V20h3.38V8.5ZM5.25 3a1.96 1.96 0 1 0 0 3.92 1.96 1.96 0 0 0 0-3.92ZM20.44 20h-3.37v-5.9c0-1.4-.03-3.2-1.95-3.2-1.96 0-2.26 1.53-2.26 3.1V20H9.5V8.5h3.24v1.57h.05c.45-.86 1.56-1.77 3.21-1.77 3.44 0 4.07 2.26 4.07 5.2V20Z"/></svg></a>
    </div>
  </div>
</div>
