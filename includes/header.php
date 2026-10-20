<?php
/**
 * Shared public header. Pages set before including it:
 *   $pageTitle, $pageDesc, $activeNav, $canonicalPath, $ogImage (optional), $ogType (optional)
 */
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/schema.php";
require_once __DIR__ . "/settings.php";
require_once __DIR__ . "/icons.php";
require_once __DIR__ . "/fonts.php";

if (!isset($pageTitle)) $pageTitle = "NUFA — Northern Uganda Filmmakers Association | Our Stories. Our Culture. Our Future.";
if (!isset($pageDesc))  $pageDesc  = "The Northern Uganda Filmmakers Association (NUFA) brings filmmakers together to strengthen the craft of storytelling, celebrate creative excellence and build a stronger film industry in Northern Uganda.";
if (!isset($activeNav)) $activeNav = "";
if (!isset($base))      $base = "";
if (!isset($ogImage))   $ogImage = "assets/images/og/og-default.jpg";
if (!isset($ogType))    $ogType = "website";
if (!isset($canonicalPath)) $canonicalPath = basename($_SERVER['PHP_SELF']);

$cfg = nufa_site_settings($conn);
$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$siteUrl = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$canonical = $siteUrl . '/' . ltrim($canonicalPath, '/');
$font = nufa_get_font_preset($conn);
$navProgrammes = ($__r = $conn->query("SELECT slug, title FROM programmes WHERE is_active=1 ORDER BY sort_order, id")) ? $__r->fetch_all(MYSQLI_ASSOC) : [];
$navEditions = ($__r = $conn->query("SELECT slug, year FROM award_editions WHERE is_active=1 ORDER BY year DESC")) ? $__r->fetch_all(MYSQLI_ASSOC) : [];
$sameAs = array_values(array_filter([$cfg['facebook_url'], $cfg['instagram_url'], $cfg['x_url'], $cfg['linkedin_url'], $cfg['youtube_url']]));
$hx = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$cssVer = @filemtime(__DIR__ . '/../assets/css/site.css');
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $hx($pageTitle); ?></title>
<meta name="description" content="<?php echo $hx($pageDesc); ?>">
<meta name="theme-color" content="#111111">
<link rel="canonical" href="<?php echo $hx($canonical); ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow"><?php endif; ?>

<meta property="og:site_name" content="NUFA — Northern Uganda Filmmakers Association">
<meta property="og:title" content="<?php echo $hx($pageTitle); ?>">
<meta property="og:description" content="<?php echo $hx($pageDesc); ?>">
<meta property="og:type" content="<?php echo $hx($ogType); ?>">
<meta property="og:url" content="<?php echo $hx($canonical); ?>">
<meta property="og:image" content="<?php echo $hx($siteUrl . '/' . ltrim($ogImage, '/')); ?>">
<meta property="og:locale" content="en_UG">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $hx($pageTitle); ?>">
<meta name="twitter:description" content="<?php echo $hx($pageDesc); ?>">
<meta name="twitter:image" content="<?php echo $hx($siteUrl . '/' . ltrim($ogImage, '/')); ?>">

<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $cfg['site_name'],
    'alternateName' => 'NUFA',
    'url' => $siteUrl . '/',
    'logo' => $siteUrl . '/assets/images/logo/nufa-logo.png',
    'email' => $cfg['site_email'],
    'telephone' => $cfg['site_phone'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Plot 75, Jomo Kenyatta Road', 'addressLocality' => 'Gulu City', 'addressCountry' => 'UG'],
    'sameAs' => $sameAs,
    'description' => 'A regional association bringing together filmmakers and audiovisual stakeholders in Northern Uganda.',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

</script>
<?php if (!empty($schemaExtra)) echo '<script type="application/ld+json">' . json_encode($schemaExtra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n"; ?>

<link rel="icon" href="<?php echo $base; ?>assets/images/logo/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?<?php echo $font['google']; ?>&display=swap">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/site.css?v=<?php echo $cssVer; ?>">
<style>:root{--font-heading:<?php echo $font['heading']; ?>;--font-body:<?php echo $font['body']; ?>}</style>
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>

<div class="topbar">
  <div class="container-wide topbar-in">
    <div class="topbar-info">
      <a href="tel:<?php echo nufa_tel_href($cfg['site_phone']); ?>"><?php echo nufa_icon('phone', 13) . $hx($cfg['site_phone']); ?></a>
      <a href="mailto:<?php echo $hx($cfg['site_email']); ?>" class="hide-sm"><?php echo nufa_icon('mail', 13) . $hx($cfg['site_email']); ?></a>
      <span class="hide-sm"><?php echo nufa_icon('pin', 13) . $hx($cfg['site_address']); ?></span>
    </div>
    <div class="topbar-social" aria-label="Social media">
      <?php foreach (['x_url' => ['X', 'x'], 'facebook_url' => ['Facebook', 'facebook'], 'instagram_url' => ['Instagram', 'instagram'], 'linkedin_url' => ['LinkedIn', 'linkedin'], 'youtube_url' => ['YouTube', 'youtube']] as $sk => [$slabel, $sic]):
        if ($cfg[$sk] === '') continue; ?>
        <a href="<?php echo $hx($cfg[$sk]); ?>" target="_blank" rel="noopener" aria-label="<?php echo $slabel; ?>"><?php echo nufa_icon($sic, 12); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<header class="site-header" id="top">
  <div class="container-wide nav-row">
    <a href="<?php echo $base; ?>index.php" class="brand" aria-label="NUFA home">
      <span class="mark"><img src="<?php echo $base; ?>assets/images/logo/nufa-logo.png" alt="" width="46" height="46"></span>
      <span class="word">NUFA<small>Filmmakers Association</small></span>
    </a>

    <nav class="main-nav" aria-label="Primary">
      <ul>
        <li class="<?php echo $activeNav === 'home' ? 'active' : ''; ?>"><a href="<?php echo $base; ?>index.php">Home</a></li>
        <li class="has-dropdown <?php echo in_array($activeNav, ['about', 'membership']) ? 'active' : ''; ?>">
          <a href="<?php echo $base; ?>about.php">About NUFA</a>
          <button type="button" class="caret-btn" aria-label="Open About menu" aria-expanded="false"><svg class="caret" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
          <div class="dropdown">
            <a href="<?php echo $base; ?>about.php#story">Our Story</a>
            <a href="<?php echo $base; ?>about.php#vision">Vision &amp; Mission</a>
            <a href="<?php echo $base; ?>about.php#values">Core Values</a>
            <a href="<?php echo $base; ?>about.php#leadership">Leadership &amp; Governance</a>
            <a href="<?php echo $base; ?>membership.php">Membership</a>
          </div>
        </li>
        <li class="has-dropdown <?php echo $activeNav === 'awards' ? 'active' : ''; ?>">
          <a href="<?php echo $base; ?>awards.php">NUFA Awards</a>
          <button type="button" class="caret-btn" aria-label="Open Awards menu" aria-expanded="false"><svg class="caret" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
          <div class="dropdown">
            <a href="<?php echo $base; ?>awards.php">Awards Overview</a>
            <?php foreach ($navEditions as $navEd): ?><a href="<?php echo $base; ?>award.php?e=<?php echo $hx($navEd['slug']); ?>">NUFA Awards <?php echo (int)$navEd['year']; ?></a><?php endforeach; ?>
            <a href="<?php echo $base; ?>awards.php#archive">Awards Archive</a>
          </div>
        </li>
        <li class="has-dropdown <?php echo $activeNav === 'programmes' ? 'active' : ''; ?>">
          <a href="<?php echo $base; ?>programmes.php">Programmes</a>
          <button type="button" class="caret-btn" aria-label="Open Programmes menu" aria-expanded="false"><svg class="caret" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
          <div class="dropdown">
            <a href="<?php echo $base; ?>programmes.php">All Programmes</a>
            <?php foreach ($navProgrammes as $navPg): ?><a href="<?php echo $base; ?>programme.php?slug=<?php echo $hx($navPg['slug']); ?>"><?php echo $hx($navPg['title']); ?></a><?php endforeach; ?>
          </div>
        </li>
        <li class="<?php echo $activeNav === 'sacco' ? 'active' : ''; ?>"><a href="<?php echo $base; ?>sacco.php">NUFA SACCO</a></li>
        <li class="<?php echo $activeNav === 'network' ? 'active' : ''; ?>"><a href="<?php echo $base; ?>network.php">Industry Network</a></li>
        <li class="<?php echo $activeNav === 'news' ? 'active' : ''; ?>"><a href="<?php echo $base; ?>news.php">News &amp; Stories</a></li>
        <li class="has-dropdown <?php echo in_array($activeNav, ['resources', 'gallery']) ? 'active' : ''; ?>">
          <a href="<?php echo $base; ?>resources.php">Resources</a>
          <button type="button" class="caret-btn" aria-label="Open Resources menu" aria-expanded="false"><svg class="caret" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
          <div class="dropdown">
            <a href="<?php echo $base; ?>resources.php">Resource Library</a>
            <a href="<?php echo $base; ?>gallery.php">Gallery &amp; Video Library</a>
          </div>
        </li>
        <li class="<?php echo $activeNav === 'partner' ? 'active' : ''; ?>"><a href="<?php echo $base; ?>partners.php">Partner With Us</a></li>
        <li class="<?php echo $activeNav === 'contact' ? 'active' : ''; ?>"><a href="<?php echo $base; ?>contact.php">Contact</a></li>
      </ul>
    </nav>

    <div class="header-cta">
      <a href="<?php echo $base; ?>membership.php" class="btn btn-gold btn-sm">Become a Member</a>
      <button class="nav-toggle" aria-label="Open menu" aria-controls="mobileNav"><?php echo nufa_icon('menu', 24); ?></button>
    </div>
  </div>
</header>

<div class="mobile-nav" id="mobileNav" role="dialog" aria-label="Menu" aria-hidden="true">
  <div class="mobile-nav-top">
    <a href="<?php echo $base; ?>index.php" class="brand"><span class="mark"><img src="<?php echo $base; ?>assets/images/logo/nufa-logo.png" alt="" width="46" height="46"></span><span class="word">NUFA</span></a>
    <button class="mobile-nav-close" aria-label="Close menu"><?php echo nufa_icon('close', 24); ?></button>
  </div>
  <ul>
    <li><a href="<?php echo $base; ?>index.php">Home</a></li>
    <li><a href="#" data-toggle-sub onclick="return false;">About NUFA <?php echo nufa_icon('down', 16); ?></a>
      <div class="mobile-sub"><a href="<?php echo $base; ?>about.php#story">Our Story</a><a href="<?php echo $base; ?>about.php#vision">Vision &amp; Mission</a><a href="<?php echo $base; ?>about.php#values">Core Values</a><a href="<?php echo $base; ?>about.php#leadership">Leadership</a><a href="<?php echo $base; ?>membership.php">Membership</a></div></li>
    <li><a href="#" data-toggle-sub onclick="return false;">NUFA Awards <?php echo nufa_icon('down', 16); ?></a>
      <div class="mobile-sub"><a href="<?php echo $base; ?>awards.php">Overview</a><?php foreach ($navEditions as $navEd): ?><a href="<?php echo $base; ?>award.php?e=<?php echo $hx($navEd['slug']); ?>">NUFA Awards <?php echo (int)$navEd['year']; ?></a><?php endforeach; ?><a href="<?php echo $base; ?>awards.php#archive">Archive</a></div></li>
    <li><a href="#" data-toggle-sub onclick="return false;">Programmes <?php echo nufa_icon('down', 16); ?></a>
      <div class="mobile-sub"><a href="<?php echo $base; ?>programmes.php">All Programmes</a><?php foreach ($navProgrammes as $navPg): ?><a href="<?php echo $base; ?>programme.php?slug=<?php echo $hx($navPg['slug']); ?>"><?php echo $hx($navPg['title']); ?></a><?php endforeach; ?></div></li>
    <li><a href="<?php echo $base; ?>sacco.php">NUFA SACCO</a></li>
    <li><a href="<?php echo $base; ?>network.php">Industry Network</a></li>
    <li><a href="<?php echo $base; ?>news.php">News &amp; Stories</a></li>
    <li><a href="#" data-toggle-sub onclick="return false;">Resources <?php echo nufa_icon('down', 16); ?></a>
      <div class="mobile-sub"><a href="<?php echo $base; ?>resources.php">Resource Library</a><a href="<?php echo $base; ?>gallery.php">Gallery &amp; Video Library</a></div></li>
    <li><a href="<?php echo $base; ?>partners.php">Partner With Us</a></li>
    <li><a href="<?php echo $base; ?>contact.php">Contact</a></li>
  </ul>
  <div class="mobile-nav-foot">
    <a href="<?php echo $base; ?>membership.php" class="btn btn-gold btn-block">Become a Member</a>
    <a href="<?php echo $base; ?>partners.php" class="btn btn-outline btn-block">Partner With NUFA</a>
  </div>
</div>
<main id="main">
