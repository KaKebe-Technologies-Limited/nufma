<?php
/**
 * Shared public header. Pages set before including it:
 *   $pageTitle, $pageDesc, $activeNav, $canonicalPath, $ogImage (optional), $ogType (optional)
 * Everything visible here — logo, colours, menu, call-to-action, top bar — is configured in the admin.
 */
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/schema.php";
require_once __DIR__ . "/settings.php";
require_once __DIR__ . "/icons.php";
require_once __DIR__ . "/fonts.php";
require_once __DIR__ . "/menu.php";
require_once __DIR__ . "/sections.php";
require_once __DIR__ . "/content.php";

$cfg = nufa_site_settings($conn);
$hx = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
if (!isset($pageTitle)) $pageTitle = cms_value('seo', 'home_title');
if (!isset($pageDesc))  $pageDesc  = cms_value('seo', 'home_desc');
if (!isset($activeNav)) $activeNav = "";
if (!isset($base))      $base = "";
if (!isset($ogImage))   $ogImage = $cfg['og_default_image'] !== '' ? "assets/images/" . $cfg['og_default_image'] : "assets/images/og/og-default.jpg";
if (!isset($ogType))    $ogType = "website";
if (!isset($canonicalPath)) $canonicalPath = basename($_SERVER['PHP_SELF']);

$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$siteUrl = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$canonical = $siteUrl . '/' . ltrim($canonicalPath, '/');
$font = nufa_get_font_preset($conn);
$menu = nufa_menu($conn, 'header');
$sameAs = array_values(array_filter([$cfg['facebook_url'], $cfg['instagram_url'], $cfg['x_url'], $cfg['linkedin_url'], $cfg['youtube_url']]));
$cssVer = @filemtime(__DIR__ . '/../assets/css/site.css');
$__pgKey = nufa_registry_key_by_file(basename($_SERVER['SCRIPT_NAME']));
if ($__pgKey !== '' && ($cfg["noindex_$__pgKey"] ?? '0') === '1') $noindex = true;
if ($__pgKey !== '' && !empty($cfg["seo_image_$__pgKey"]) && is_file(__DIR__ . '/../assets/images/' . $cfg["seo_image_$__pgKey"])) $ogImage = 'assets/images/' . $cfg["seo_image_$__pgKey"];
$logoRel = ($cfg['site_logo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $cfg['site_logo'])) ? $cfg['site_logo'] : 'logo/nufa-logo.png';
$faviconHref = ($cfg['site_favicon'] !== '' && is_file(__DIR__ . '/../assets/images/' . $cfg['site_favicon'])) ? 'assets/images/' . $cfg['site_favicon'] : 'assets/images/logo/favicon.svg';
$gold = preg_match('/^#[0-9a-f]{6}$/i', $cfg['brand_gold']) ? $cfg['brand_gold'] : '#D4AF63';
$ink  = preg_match('/^#[0-9a-f]{6}$/i', $cfg['brand_ink']) ? $cfg['brand_ink'] : '#111111';
$ctaLabel = cms_value('site', 'header_cta_label');
$ctaUrl = cms_value('site', 'header_cta_url');

$dropdownToggle = function ($label) { return '<button type="button" class="caret-btn" aria-label="Open ' . htmlspecialchars($label, ENT_QUOTES) . ' menu" aria-expanded="false"><svg class="caret" viewBox="0 0 10 6" fill="none"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>'; };
?><!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $hx($pageTitle); ?></title>
<meta name="description" content="<?php echo $hx($pageDesc); ?>">
<meta name="theme-color" content="<?php echo $hx($ink); ?>">
<link rel="canonical" href="<?php echo $hx($canonical); ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow"><?php endif; ?>

<meta property="og:site_name" content="<?php echo $hx($cfg['site_name']); ?>">
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
    'logo' => $siteUrl . '/assets/images/' . $logoRel,
    'email' => $cfg['site_email'],
    'telephone' => $cfg['site_phone'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Plot 75, Jomo Kenyatta Road', 'addressLocality' => 'Gulu City', 'addressCountry' => 'UG'],
    'sameAs' => $sameAs,
    'description' => 'A regional association bringing together filmmakers and audiovisual stakeholders in Northern Uganda.',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

</script>
<?php if (!empty($schemaExtra)) echo '<script type="application/ld+json">' . json_encode($schemaExtra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n"; ?>

<link rel="icon" href="<?php echo $base . $hx($faviconHref); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?<?php echo $font['google']; ?>&display=swap">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/site.css?v=<?php echo $cssVer; ?>">
<style>:root{--font-heading:<?php echo $font['heading']; ?>;--font-body:<?php echo $font['body']; ?>;
--gold:<?php echo $gold; ?>;--gold-600:<?php echo nufa_shade($gold, -12); ?>;--gold-300:<?php echo nufa_shade($gold, 28); ?>;--gold-700:<?php echo nufa_shade($gold, -38); ?>;--gold-100:<?php echo nufa_shade($gold, 88); ?>;
--ink:<?php echo $ink; ?>;--ink-700:<?php echo nufa_shade($ink, 12); ?>;--ink-900:<?php echo nufa_shade($ink, -55); ?>}</style>
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>

<?php if ($cfg['show_topbar'] === '1'): ?>
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
<?php endif; ?>

<header class="site-header" id="top">
  <div class="container-wide nav-row">
    <a href="<?php echo $base; ?>index.php" class="brand" aria-label="NUFA home">
      <span class="mark"><img src="<?php echo $base; ?>assets/images/<?php echo $hx($logoRel); ?>" alt="" width="46" height="46"></span>
      <span class="word">NUFA<small><?php echo $hx(cms_value('site', 'brand_subtitle')); ?></small></span>
    </a>

    <nav class="main-nav" aria-label="Primary">
      <ul>
        <?php foreach ($menu as $item): $active = nufa_menu_is_active($item, $activeNav); $has = !empty($item['children']); ?>
        <li class="<?php echo ($has ? 'has-dropdown ' : '') . ($active ? 'active' : ''); ?>">
          <a href="<?php echo nufa_href($item['url']); ?>"<?php echo nufa_link_attrs($item); ?>><?php echo $hx($item['label']); ?></a>
          <?php if ($has): echo $dropdownToggle($item['label']); ?>
          <div class="dropdown"><?php foreach ($item['children'] as $c): ?><a href="<?php echo nufa_href($c['url']); ?>"<?php echo nufa_link_attrs($c); ?>><?php echo $hx($c['label']); ?></a><?php endforeach; ?></div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-cta">
      <?php if ($ctaLabel !== ''): ?><a href="<?php echo nufa_href($ctaUrl); ?>" class="btn btn-gold btn-sm"><?php echo $hx($ctaLabel); ?></a><?php endif; ?>
      <button class="nav-toggle" aria-label="Open menu" aria-controls="mobileNav"><?php echo nufa_icon('menu', 24); ?></button>
    </div>
  </div>
</header>

<div class="mobile-nav" id="mobileNav" role="dialog" aria-label="Menu" aria-hidden="true">
  <div class="mobile-nav-top">
    <a href="<?php echo $base; ?>index.php" class="brand"><span class="mark"><img src="<?php echo $base; ?>assets/images/<?php echo $hx($logoRel); ?>" alt="" width="46" height="46"></span><span class="word">NUFA</span></a>
    <button class="mobile-nav-close" aria-label="Close menu"><?php echo nufa_icon('close', 24); ?></button>
  </div>
  <ul>
    <?php foreach ($menu as $item): $has = !empty($item['children']); ?>
    <li>
      <?php if ($has): ?>
        <a href="#" data-toggle-sub onclick="return false;"><?php echo $hx($item['label']) . ' ' . nufa_icon('down', 16); ?></a>
        <div class="mobile-sub"><a href="<?php echo nufa_href($item['url']); ?>"><?php echo $hx($item['label']); ?></a><?php foreach ($item['children'] as $c): ?><a href="<?php echo nufa_href($c['url']); ?>"><?php echo $hx($c['label']); ?></a><?php endforeach; ?></div>
      <?php else: ?><a href="<?php echo nufa_href($item['url']); ?>"<?php echo nufa_link_attrs($item); ?>><?php echo $hx($item['label']); ?></a><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="mobile-nav-foot">
    <?php if ($ctaLabel !== ''): ?><a href="<?php echo nufa_href($ctaUrl); ?>" class="btn btn-gold btn-block"><?php echo $hx($ctaLabel); ?></a><?php endif; ?>
    <?php $m2 = cms_value('site', 'mobile_cta2_label'); if ($m2 !== ''): ?><a href="<?php echo nufa_href(cms_value('site', 'mobile_cta2_url')); ?>" class="btn btn-outline btn-block"><?php echo $hx($m2); ?></a><?php endif; ?>
  </div>
</div>
<main id="main">
