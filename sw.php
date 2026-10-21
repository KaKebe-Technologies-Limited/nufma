<?php
// Service worker (served as JavaScript). Caches the site shell so it opens fast and keeps working offline.
// Registered from the page footer as sw.php; the cache version changes whenever the CSS/JS change.
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache');
$v = (int)@filemtime(__DIR__ . '/assets/css/site.css') . '-' . (int)@filemtime(__DIR__ . '/assets/js/main.js');
?>
const VERSION = 'nufa-<?php echo $v; ?>';
const STATIC_CACHE = VERSION + '-static';
const PAGE_CACHE = VERSION + '-pages';
const OFFLINE_URL = 'offline.html';
const PRECACHE = [OFFLINE_URL, 'assets/css/site.css?v=<?php echo (int)@filemtime(__DIR__ . '/assets/css/site.css'); ?>', 'assets/images/pwa/icon-192.png'];
// public pages that are safe to keep a copy of for offline reading (never member, admin, card or invitation pages)
const PAGES = ['index.php', 'about.php', 'awards.php', 'programmes.php', 'news.php', 'newsletters.php', 'gallery.php', 'resources.php', 'opportunities.php', 'partners.php', 'contact.php', 'schedule.php', 'network.php', 'sacco.php', 'support.php'];

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(STATIC_CACHE).then(function (c) { return c.addAll(PRECACHE); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.filter(function (k) { return k.indexOf(VERSION) !== 0; }).map(function (k) { return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});

function isPublicPage(url) {
  var p = url.pathname.replace(/^.*\//, '') || 'index.php';
  return PAGES.indexOf(p) !== -1;
}

self.addEventListener('fetch', function (e) {
  var req = e.request, url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== location.origin) return;
  if (/\/admin\//.test(url.pathname) || /\/(storage|cron-reminders|manifest|sw)\b/.test(url.pathname)) return;

  // pages: network first, fall back to the last copy, then to the offline page
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).then(function (res) {
      if (res.ok && isPublicPage(url) && !url.search) { var copy = res.clone(); caches.open(PAGE_CACHE).then(function (c) { c.put(req, copy); }); }
      return res;
    }).catch(function () {
      return caches.match(req).then(function (hit) { return hit || caches.match(OFFLINE_URL); });
    }));
    return;
  }

  // images, styles, scripts, fonts: serve the cached copy at once and refresh it in the background
  if (/\.(css|js|png|jpe?g|webp|svg|gif|ico|woff2?)$/i.test(url.pathname)) {
    e.respondWith(caches.open(STATIC_CACHE).then(function (c) {
      return c.match(req).then(function (hit) {
        var net = fetch(req).then(function (res) { if (res.ok) c.put(req, res.clone()); return res; }).catch(function () { return hit; });
        return hit || net;
      });
    }));
  }
});
