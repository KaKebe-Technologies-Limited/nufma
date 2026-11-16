<?php
/**
 * Site navigation, managed in admin → Menus & Footer.
 *
 * Menu items live in `nav_items`. A child item whose URL is `auto:editions` or
 * `auto:programmes` expands into one link per active award edition / programme,
 * so those dropdowns keep themselves up to date.
 */

/** The shipped menu — used to seed the table and as a safe fallback. Each item: [label, url, nav_key, children]. */
function nufa_menu_defaults() {
    return [
        'header' => [
            ['Home', 'index.php', 'home,about,membership,directory,track', [['About NUFA', 'about.php'], ['Become a member', 'membership.php'], ['Renew membership', 'membership.php?type=renew'], ['Track my registration', 'track.php'], ['Guilds', 'auto:guilds'], ['Filmmakers Directory', 'directory.php']]],
            ['NUFA Awards', 'awards.php', 'awards', [['Awards Overview', 'awards.php'], ['auto:editions', 'auto:editions'], ['Awards Archive', 'awards.php#archive']]],
            ['NUFA SACCO', 'sacco.php', 'sacco', []],
            ['NUFA Film Fund', 'film-fund.php', 'filmfund', []],
            ['More', 'contact.php', 'programmes,network,news,newsletters,resources,gallery,partner,contact,schedule', [['Programmes', 'programmes.php'], ['Industry Network', 'network.php'], ['News & Stories', 'news.php'], ['Resources', 'resources.php'], ['Gallery & Video', 'gallery.php'], ['Partner With Us', 'partners.php'], ['Contact us', 'contact.php']]],
        ],
        'footer1' => [
            ['About NUFA', 'about.php', '', []], ['NUFA Awards', 'awards.php', '', []], ['NUFA SACCO', 'sacco.php', '', []], ['NUFA Film Fund', 'film-fund.php', '', []],
            ['Programmes', 'programmes.php', '', []], ['News & Stories', 'news.php', '', []], ['Gallery & Video', 'gallery.php', '', []],
        ],
        'footer2' => [
            ['Become a member', 'membership.php', '', []], ['Track my application', 'track.php', '', []], ['Opportunities', 'opportunities.php', '', []], ['NUFA Academy', 'academy.php', '', []],
            ['Partner With Us', 'partners.php', '', []], ['Newsletter', 'newsletters.php', '', []], ['Contact us', 'contact.php', '', []],
        ],
        'footer_legal' => [
            ['Privacy', 'policy.php?p=privacy', '', []], ['Terms', 'policy.php?p=terms', '', []], ['Cookies', 'policy.php?p=cookies', '', []], ['Accessibility', 'policy.php?p=accessibility', '', []],
        ],
    ];
}

const NUFA_MENU_LOCATIONS = [
    'header'       => 'Main menu (header)',
    'footer1'      => 'Footer — first link column',
    'footer2'      => 'Footer — second link column',
    'footer_legal' => 'Footer — bottom legal strip',
];

function nufa_menu_seed($conn) {
    $n = $conn->query("SELECT COUNT(*) FROM nav_items");
    if (!$n || (int)$n->fetch_row()[0] > 0) return;
    $ins = $conn->prepare("INSERT INTO nav_items (location, parent_id, label, url, nav_key, sort_order) VALUES (?,?,?,?,?,?)");
    foreach (nufa_menu_defaults() as $loc => $items) {
        foreach ($items as $i => [$label, $url, $key, $children]) {
            $pid = null; $o = $i + 1;
            $ins->bind_param("sisssi", $loc, $pid, $label, $url, $key, $o);
            $ins->execute();
            $parent = $conn->insert_id;
            foreach ($children as $j => [$cl, $cu]) {
                $o2 = $j + 1; $ck = '';
                $ins->bind_param("sisssi", $loc, $parent, $cl, $cu, $ck, $o2);
                $ins->execute();
            }
        }
    }
}

/** Menu tree for a location: [ ['label','url','nav_key','children'=>[['label','url'],…]], … ] with auto-expansion applied. */
function nufa_menu($conn, $location) {
    static $cache = [];
    if (isset($cache[$location])) return $cache[$location];
    $rows = [];
    $r = $conn->query("SELECT * FROM nav_items WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    if ($r) $rows = $r->fetch_all(MYSQLI_ASSOC);
    $top = []; $kids = [];
    foreach ($rows as $row) {
        if ($row['location'] !== $location) continue;
        if ($row['parent_id']) $kids[(int)$row['parent_id']][] = $row; else $top[] = $row;
    }
    if (!$top && !$rows) {   // table missing/empty: fall back to the shipped menu
        $out = [];
        foreach (nufa_menu_defaults()[$location] ?? [] as [$l, $u, $k, $ch]) $out[] = ['label' => $l, 'url' => $u, 'nav_key' => $k, 'new_tab' => 0, 'children' => array_map(fn($c) => ['label' => $c[0], 'url' => $c[1], 'new_tab' => 0], $ch)];
        $top = null; $rows = null;
        return $cache[$location] = nufa_menu_expand($conn, $out);
    }
    $out = [];
    foreach ($top as $t) {
        $children = array_map(fn($c) => ['label' => $c['label'], 'url' => $c['url'], 'new_tab' => (int)$c['new_tab']], $kids[(int)$t['id']] ?? []);
        $out[] = ['label' => $t['label'], 'url' => $t['url'], 'nav_key' => $t['nav_key'], 'new_tab' => (int)$t['new_tab'], 'children' => $children];
    }
    return $cache[$location] = nufa_menu_expand($conn, $out);
}

function nufa_menu_expand($conn, array $items) {
    foreach ($items as &$it) {
        $exp = [];
        foreach ($it['children'] as $c) {
            if ($c['url'] === 'auto:editions') {
                $q = $conn->query("SELECT slug, year FROM award_editions WHERE is_active=1 ORDER BY year DESC");
                if ($q) foreach ($q->fetch_all(MYSQLI_ASSOC) as $e) $exp[] = ['label' => 'NUFA Awards ' . (int)$e['year'], 'url' => 'award.php?e=' . $e['slug'], 'new_tab' => 0];
            } elseif ($c['url'] === 'auto:guilds') {
                $q = @$conn->query("SELECT id, name FROM guilds WHERE is_active=1 ORDER BY sort_order, name");
                $gs = $q ? $q->fetch_all(MYSQLI_ASSOC) : [];
                if ($gs) $exp[] = ['label' => $c['label'] !== '' && $c['label'] !== 'auto:guilds' ? $c['label'] : 'Guilds', 'url' => 'network.php#numbers', 'new_tab' => 0, 'sub' => array_map(fn($g) => ['label' => $g['name'], 'url' => 'directory.php?guild=' . (int)$g['id'], 'new_tab' => 0], $gs)];
            } elseif ($c['url'] === 'auto:programmes') {
                $q = $conn->query("SELECT slug, title FROM programmes WHERE is_active=1 ORDER BY sort_order, id");
                if ($q) foreach ($q->fetch_all(MYSQLI_ASSOC) as $p) $exp[] = ['label' => $p['title'], 'url' => 'programme.php?slug=' . $p['slug'], 'new_tab' => 0];
            } else $exp[] = $c;
        }
        $it['children'] = $exp;
    }
    return $items;
}
/** One-time: replaces the long footer link lists with short, tidy ones. Later edits in Admin → Menus are kept. */
function nufa_footer_v2_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = @$conn->query("SELECT 1 FROM settings WHERE setting_key='footer_v2_simple'");
    if (!$f || $f->num_rows) return;
    if (!@$conn->query("SELECT 1 FROM nav_items LIMIT 1")) return;
    foreach (['track_menu_added', 'dir_menu_added', 'footer_v2_simple'] as $k) $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k','1') ON DUPLICATE KEY UPDATE setting_value='1'");
    $conn->query("DELETE FROM nav_items WHERE location IN ('footer1','footer2','footer_legal')");
    $ins = $conn->prepare("INSERT INTO nav_items (location, parent_id, label, url, nav_key, sort_order) VALUES (?,NULL,?,?,'',?)");
    foreach (['footer1', 'footer2', 'footer_legal'] as $loc) foreach (nufa_menu_defaults()[$loc] as $i => [$label, $url]) { $o = $i + 1; $ins->bind_param("sssi", $loc, $label, $url, $o); $ins->execute(); }
}

/** One-time: replaces the long header menu with the simple one (Home · NUFA Awards · NUFA SACCO · NUFA Film Fund · More). Admins can still edit it afterwards under Menus. */
function nufa_menu_v2_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = @$conn->query("SELECT 1 FROM settings WHERE setting_key='menu_v2_simple'");
    if (!$f || $f->num_rows) return;
    $t = @$conn->query("SELECT COUNT(*) FROM nav_items"); if (!$t) return;
    $conn->query("DELETE FROM nav_items WHERE location='header'");
    $ins = $conn->prepare("INSERT INTO nav_items (location, parent_id, label, url, nav_key, sort_order) VALUES (?,?,?,?,?,?)");
    $loc = 'header';
    foreach (nufa_menu_defaults()['header'] as $i => [$label, $url, $key, $children]) {
        $pid = null; $o = $i + 1; $ins->bind_param("sisssi", $loc, $pid, $label, $url, $key, $o); $ins->execute(); $parent = $conn->insert_id;
        foreach ($children as $j => [$cl, $cu]) { $o2 = $j + 1; $ck = ''; $ins->bind_param("sisssi", $loc, $parent, $cl, $cu, $ck, $o2); $ins->execute(); }
    }
    if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE location='footer1' AND url='film-fund.php'")->fetch_row()[0]) {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer1'")->fetch_row()[0];
        $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer1', 'NUFA Film Fund', 'film-fund.php', $o)");
    }
    foreach ([['footer1', 'Partner With Us', 'partners.php'], ['footer1', 'Contact us', 'contact.php']] as [$l, $lab, $u]) {
        if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE location IN ('footer1','footer2') AND url='" . $conn->real_escape_string($u) . "'")->fetch_row()[0]) {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer1'")->fetch_row()[0];
            $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('$l', '" . $conn->real_escape_string($lab) . "', '" . $conn->real_escape_string($u) . "', $o)");
        }
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('menu_v2_simple','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

function nufa_menu_is_active($item, $activeNav) {
    return $activeNav !== '' && in_array($activeNav, array_filter(array_map('trim', explode(',', (string)($item['nav_key'] ?? '')))), true);
}

function nufa_link_attrs($item) { return !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : ''; }

/** Escaped href for a menu/button URL: absolute links and anchors pass through, site paths are used as-is (the site lives at its web root). */
function nufa_href($url) {
    return htmlspecialchars(trim((string)$url), ENT_QUOTES, 'UTF-8');
}
