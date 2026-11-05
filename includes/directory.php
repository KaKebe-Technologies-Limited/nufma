<?php
/**
 * Filmmakers directory: approved, active members who opted in (members.in_directory = 1) can be listed publicly by guild and
 * sub-region. Only name, guild, region, district, craft, experience and portfolio link are shown — never phone, email or ID numbers.
 */
require_once __DIR__ . "/membership.php";

function dir_settings($conn) {
    $d = ['dir_enabled' => '1', 'dir_intro' => 'Find NUFA filmmakers by craft (guild) and by region. Every person listed is an approved NUFA member who chose to be shown here. To work with someone, contact NUFA and we will connect you.'];
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('dir_enabled','dir_intro')");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}

/**
 * Members matching the filters. $o: guild (id), region (label), q (text), listed (true = opted-in only), active (true = not expired),
 * order ('guild'|'region'|'name'), limit, offset. Each row gets 'region' (sub-region label or 'Other / not stated') and guild_name.
 */
function dir_members($conn, array $o = []) {
    $where = ["m.status='approved'"]; $types = ''; $params = [];
    if (!empty($o['listed'])) $where[] = "m.in_directory=1";
    if (!empty($o['active'])) $where[] = "(m.valid_until IS NULL OR m.valid_until >= CURDATE())";
    if (!empty($o['guild'])) { $where[] = "m.guild_id=?"; $types .= 'i'; $params[] = (int)$o['guild']; }
    if (!empty($o['q'])) { $where[] = "(m.full_name LIKE ? OR m.district LIKE ? OR m.occupation LIKE ?)"; $types .= 'sss'; $l = '%' . $o['q'] . '%'; array_push($params, $l, $l, $l); }
    $sql = "SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE " . implode(' AND ', $where) . " ORDER BY m.full_name";
    $st = $conn->prepare($sql); if ($params) $st->bind_param($types, ...$params); $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $map = mem_subregion_map($conn);
    foreach ($rows as &$r) { $s = mem_subregion_for($r, $map); $r['region'] = ($s === '' || $s === 'Other') ? 'Other / not stated' : $s; $r['guild_name'] = $r['guild_name'] ?: 'Other / not stated'; }
    unset($r);
    if (!empty($o['region'])) $rows = array_values(array_filter($rows, fn($r) => $r['region'] === $o['region']));
    $order = $o['order'] ?? 'guild';
    usort($rows, function ($a, $b) use ($order) {
        $k = $order === 'region' ? [$a['region'] <=> $b['region'], $a['guild_name'] <=> $b['guild_name']] : ($order === 'name' ? [] : [$a['guild_name'] <=> $b['guild_name'], $a['region'] <=> $b['region']]);
        foreach ($k as $c) if ($c) return $c;
        return strcasecmp($a['full_name'], $b['full_name']);
    });
    return $rows;
}

function dir_regions() { return array_merge(array_keys(mem_subregions()), ['Other / not stated']); }

/** Rows for a public export (no private contact details). */
function dir_public_export(array $rows) {
    $h = ['Name', 'Guild', 'Region', 'District', 'Craft / role', 'Experience', 'Portfolio'];
    $o = []; foreach ($rows as $r) $o[] = [$r['full_name'], $r['guild_name'], $r['region'], $r['district'], $r['occupation'], $r['experience'], $r['portfolio_url']];
    return [$h, $o];
}

/** Adds "Filmmakers Directory" to the menus once (admins can remove it under Menus). */
function dir_menu_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='dir_menu_added'");
    if (!$f || $f->num_rows) return;
    $p = $conn->query("SELECT id, nav_key FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='about.php' LIMIT 1")->fetch_assoc();
    if ($p && !(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='directory.php' AND location='header'")->fetch_row()[0]) {
        if (strpos((string)$p['nav_key'], 'directory') === false) { $k = trim($p['nav_key'] . ',directory', ','); $conn->query("UPDATE nav_items SET nav_key='" . $conn->real_escape_string($k) . "' WHERE id=" . (int)$p['id']); }
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$p['id'])->fetch_row()[0];
        $conn->query("INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', " . (int)$p['id'] . ", 'Filmmakers Directory', 'directory.php', $o)");
    }
    if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='directory.php' AND location='footer1'")->fetch_row()[0]) {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer1'")->fetch_row()[0];
        $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer1', 'Filmmakers Directory', 'directory.php', $o)");
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('dir_menu_added','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}
