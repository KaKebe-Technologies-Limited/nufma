<?php
// Web app manifest — lets visitors install the site as an app on phones and desktops.
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/settings.php";
$cfg = nufa_site_settings($conn);
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
if (($cfg['pwa_enabled'] ?? '1') !== '1') { http_response_code(404); echo '{}'; exit; }
$ink = preg_match('/^#[0-9a-f]{6}$/i', $cfg['brand_ink'] ?? '') ? $cfg['brand_ink'] : '#111111';
$name = $cfg['site_name'] ?: 'NUFA';
$short = 'NUFA';
echo json_encode([
    'name' => $name,
    'short_name' => $short,
    'description' => 'News, awards, programmes, membership and events from the Northern Uganda Filmmakers Association.',
    'id' => './',
    'start_url' => './index.php?source=app',
    'scope' => './',
    'display' => 'standalone',
    'orientation' => 'any',
    'background_color' => '#ffffff',
    'theme_color' => $ink,
    'lang' => 'en',
    'icons' => [
        ['src' => 'assets/images/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'assets/images/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'assets/images/pwa/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
    'shortcuts' => [
        ['name' => 'NUFA Awards', 'url' => './awards.php?source=app', 'icons' => [['src' => 'assets/images/pwa/icon-192.png', 'sizes' => '192x192']]],
        ['name' => 'Calendar', 'url' => './schedule.php?source=app', 'icons' => [['src' => 'assets/images/pwa/icon-192.png', 'sizes' => '192x192']]],
        ['name' => 'Become a member', 'url' => './membership.php?source=app', 'icons' => [['src' => 'assets/images/pwa/icon-192.png', 'sizes' => '192x192']]],
        ['name' => 'News', 'url' => './news.php?source=app', 'icons' => [['src' => 'assets/images/pwa/icon-192.png', 'sizes' => '192x192']]],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
