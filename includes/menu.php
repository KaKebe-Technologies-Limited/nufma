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
            ['Home', 'index.php', 'home', []],
            ['About NUFA', 'about.php', 'about,membership', [['Our Story', 'about.php#story'], ['Vision & Mission', 'about.php#vision'], ['Core Values', 'about.php#values'], ['Leadership & Governance', 'about.php#leadership'], ['Membership', 'membership.php']]],
            ['NUFA Awards', 'awards.php', 'awards', [['Awards Overview', 'awards.php'], ['auto:editions', 'auto:editions'], ['Awards Archive', 'awards.php#archive']]],
            ['Programmes', 'programmes.php', 'programmes', [['All Programmes', 'programmes.php'], ['auto:programmes', 'auto:programmes']]],
            ['NUFA SACCO', 'sacco.php', 'sacco', []],
            ['Industry Network', 'network.php', 'network', []],
            ['News & Stories', 'news.php', 'news', []],
            ['Resources', 'resources.php', 'resources,gallery', [['Resource Library', 'resources.php'], ['Gallery & Video Library', 'gallery.php']]],
            ['Partner With Us', 'partners.php', 'partner', []],
            ['Contact', 'contact.php', 'contact', []],
            ['Calendar', 'schedule.php', 'schedule', []],
        ],
        'footer1' => [
            ['About NUFA', 'about.php', '', []], ['Programmes', 'programmes.php', '', []], ['NUFA SACCO (proposed)', 'sacco.php', '', []], ['Industry Network', 'network.php', '', []],
            ['News & Stories', 'news.php', '', []], ['Resources', 'resources.php', '', []], ['Gallery & Video', 'gallery.php', '', []],
        ],
        'footer2' => [
            ['NUFA Awards', 'awards.php', '', []], ['Membership', 'membership.php', '', []], ['Partnership enquiries', 'partners.php#enquiry', '', []], ['Contact us', 'contact.php', '', []],
            ['Privacy Policy', 'policy.php?p=privacy', '', []], ['Terms of Use', 'policy.php?p=terms', '', []], ['Accessibility', 'policy.php?p=accessibility', '', []],
        ],
        'footer_legal' => [
            ['Privacy', 'policy.php?p=privacy', '', []], ['Cookies', 'policy.php?p=cookies', '', []], ['Image & content permissions', 'policy.php?p=permissions', '', []], ['Sitemap', 'sitemap.php', '', []],
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
            } elseif ($c['url'] === 'auto:programmes') {
                $q = $conn->query("SELECT slug, title FROM programmes WHERE is_active=1 ORDER BY sort_order, id");
                if ($q) foreach ($q->fetch_all(MYSQLI_ASSOC) as $p) $exp[] = ['label' => $p['title'], 'url' => 'programme.php?slug=' . $p['slug'], 'new_tab' => 0];
            } else $exp[] = $c;
        }
        $it['children'] = $exp;
    }
    return $items;
}

function nufa_menu_is_active($item, $activeNav) {
    return $activeNav !== '' && in_array($activeNav, array_filter(array_map('trim', explode(',', (string)($item['nav_key'] ?? '')))), true);
}

function nufa_link_attrs($item) { return !empty($item['new_tab']) ? ' target="_blank" rel="noopener"' : ''; }

/** Escaped href for a menu/button URL: absolute links and anchors pass through, site paths are used as-is (the site lives at its web root). */
function nufa_href($url) {
    return htmlspecialchars(trim((string)$url), ENT_QUOTES, 'UTF-8');
}
