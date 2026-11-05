<?php
/**
 * Every picture on the website that is not tied to a database record has a "slot". Admins replace slot pictures in
 * Admin → All pictures; the public pages call site_img() so a replacement shows immediately. The original picture is
 * always the fallback, so clearing a replacement restores it.
 */
require_once __DIR__ . "/content.php";

/** Slot id for the banner of the current page (several Academy / Film pages share one banner). */
function site_hero_slot() {
    $f = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
    if (strpos($f, 'academy') === 0) $f = 'academy';
    elseif (in_array($f, ['film-submit', 'film-status'], true)) $f = 'films';
    elseif ($f === 'guest') $f = 'guests';
    return 'hero:' . $f;
}

/** Replacement picture for a slot (relative to assets/images/), or the default. */
function site_img($slot, $default) {
    global $conn;
    if (!isset($conn)) return $default;
    $ov = cms_overrides($conn, 'img');
    $v = $ov[$slot] ?? '';
    return ($v !== '' && is_file(__DIR__ . '/../assets/images/' . $v)) ? $v : $default;
}

/** All slots that are not CMS fields: [page key => [label, url, [section => [[slot, label, default, hint], …]]]]. */
function site_slot_registry() {
    $n26 = fn($n) => 'gallery/2026/nufa26-' . $n . '.jpg';
    return [
        'home' => ['Home', 'index.php', [
            'Photo moments (picture strip)' => [
                ['grid:index:0', 'Picture 1 (large)', $n26(16), 'A winner receives a trophy'], ['grid:index:1', 'Picture 2', $n26(18), ''], ['grid:index:2', 'Picture 3', $n26(31), ''],
                ['grid:index:3', 'Picture 4', $n26(32), ''], ['grid:index:4', 'Picture 5', $n26(35), ''], ['grid:index:5', 'Picture 6', $n26(33), ''],
                ['grid:index:6', 'Picture 7', $n26(20), ''], ['grid:index:7', 'Picture 8', $n26(29), ''], ['grid:index:8', 'Picture 9', $n26(26), ''],
            ],
            'Programme cards' => [['card:awards', 'NUFA Awards card', 'slider/slide1-1.webp', 'Also used on the Programmes page'], ['card:sacco', 'NUFA SACCO card', 'front1.webp', 'Also used on the Programmes page']],
        ]],
        'membership' => ['Membership', 'membership.php', ['Page banner' => [['hero:membership', 'Banner picture', 'production/karamoja-shoot-2.webp', 'Top of the membership page']]]],
        'directory' => ['Filmmakers Directory', 'directory.php', ['Page banner' => [['hero:directory', 'Banner picture', $n26(26), '']]]],
        'guests' => ['Distinguished Guests', 'guests.php', [
            'Page banner' => [['hero:guests', 'Banner picture (also on each guest page)', $n26(35), '']],
            'Picture strip' => [['grid:guests:0', 'Picture 1', $n26(35), ''], ['grid:guests:1', 'Picture 2', $n26(20), ''], ['grid:guests:2', 'Picture 3', $n26(22), ''], ['grid:guests:3', 'Picture 4', $n26(24), ''], ['grid:guests:4', 'Picture 5', $n26(27), ''], ['grid:guests:5', 'Picture 6', $n26(30), '']],
        ]],
        'awards-extra' => ['NUFA Awards — build-up pages', 'awards.php', ['Page banners' => [['hero:awards-perform', 'Perform at the Awards — banner', $n26(31), ''], ['hero:awards-workshop', 'Host a workshop — banner', $n26(20), '']]]],
        'ambassadors' => ['Ambassadors', 'ambassadors.php', ['Page banner' => [['hero:ambassadors', 'Banner picture', $n26(18), '']]]],
        'films' => ['Film Showcase', 'films.php', ['Page banner' => [['hero:films', 'Banner picture (showcase, submit and status pages)', $n26(31), '']]]],
        'academy' => ['NUFA Academy', 'academy.php', ['Page banner' => [['hero:academy', 'Banner picture (all Academy pages)', $n26(20), '']]]],
        'opportunities' => ['Opportunities', 'opportunities.php', ['Page banner' => [['hero:opportunities', 'Banner picture', $n26(18), '']]]],
        'track' => ['Track my registration', 'track.php', ['Page banner' => [['hero:track', 'Banner picture', $n26(31), '']]]],
    ];
}

/** Pictures that live in a database record and are managed on their own admin page. [label, admin url, description, SQL count or '']. */
function site_managed_images() {
    return [
        ['Home hero slider', 'sliders.php', 'The big pictures at the very top of the home page — change, add, remove and reorder slides.', "SELECT COUNT(*) FROM hero_slides"],
        ['Leadership & team portraits', 'team.php', 'The photo on every leadership card (About page).', "SELECT COUNT(*) FROM team_members"],
        ['Programme pictures', 'programmes.php', 'Each programme’s card and banner picture.', "SELECT COUNT(*) FROM programmes"],
        ['Partner & sponsor logos', 'partners.php', 'Logos on the partners page and home page.', "SELECT COUNT(*) FROM partners"],
        ['Award editions', 'award-editions.php', 'Banner and picture mosaic of each NUFA Awards edition.', "SELECT COUNT(*) FROM award_editions"],
        ['News & story pictures', 'posts.php', 'The main picture and gallery of every story.', "SELECT COUNT(*) FROM posts"],
        ['Photo gallery', 'gallery.php', 'All gallery photographs (any of them can be chosen for the pages above).', "SELECT COUNT(*) FROM gallery_images"],
        ['Distinguished guest portraits', 'guests.php', 'Photos of VIP guests.', "SELECT COUNT(*) FROM vip_guests"],
        ['Film Showcase posters', 'films.php', 'Poster of every submitted film.', "SELECT COUNT(*) FROM films"],
        ['Site logo, favicon & share image', 'appearance.php', 'The NUFA logo, browser icon and the default social-sharing picture.', ''],
        ['Newsletter covers', 'newsletters.php', 'Cover picture of each newsletter.', "SELECT COUNT(*) FROM newsletters"],
    ];
}
