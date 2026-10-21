<?php
/**
 * Story extras (event date / time / venue and a photo gallery per story) and the Distinguished Guests directory.
 * Managed in admin → News & stories (story editor) and admin → Distinguished guests.
 */
require_once __DIR__ . "/membership.php";   // mem_store_upload, MEM_IMG_TYPES

const GUEST_DIR = __DIR__ . '/../assets/images/uploads/guests/';
const STORY_DIR = __DIR__ . '/../assets/images/uploads/posts/';

function guest_categories() { return ['Chief guest', 'Guest of honour', 'VIP', 'Distinguished guest', 'Special guest']; }

function guests_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    require_once __DIR__ . "/schema.php";
    nufa_add_col($conn, 'posts', 'event_date', "DATE NULL");
    nufa_add_col($conn, 'posts', 'event_end_date', "DATE NULL");
    nufa_add_col($conn, 'posts', 'event_time', "VARCHAR(80) NOT NULL DEFAULT ''");
    nufa_add_col($conn, 'posts', 'event_venue', "VARCHAR(255) NOT NULL DEFAULT ''");
    nufa_add_col($conn, 'posts', 'event_map_url', "VARCHAR(500) NOT NULL DEFAULT ''");
    $conn->query("CREATE TABLE IF NOT EXISTS post_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        image VARCHAR(255) NOT NULL,
        caption VARCHAR(255) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_post (post_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS vip_guests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL,
        category VARCHAR(40) NOT NULL DEFAULT 'Distinguished guest',
        title VARCHAR(190) NOT NULL DEFAULT '',
        organisation VARCHAR(190) NOT NULL DEFAULT '',
        photo VARCHAR(255) NOT NULL DEFAULT '',
        editions VARCHAR(255) NOT NULL DEFAULT '',
        profile TEXT,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_active (is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // menu: About NUFA → Distinguished Guests (once, so the admin can remove it later)
    $m = $conn->query("SELECT setting_value FROM settings WHERE setting_key='guests_menu_added'");
    if ($m && !$m->num_rows) {
        $p = $conn->query("SELECT id FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='about.php' LIMIT 1")->fetch_assoc();
        if ($p && !(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='guests.php' AND location='header'")->fetch_row()[0]) {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$p['id'])->fetch_row()[0];
            $conn->query("INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', " . (int)$p['id'] . ", 'Distinguished Guests', 'guests.php', $o)");
            $conn->query("UPDATE nav_items SET nav_key=CONCAT(nav_key, ',guests') WHERE id=" . (int)$p['id'] . " AND nav_key NOT LIKE '%guests%'");
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('guests_menu_added','1')");
    }
}

/** Edition labels the admin can tick, e.g. "NUFA Awards 2026". */
function guest_edition_options($conn) {
    $out = [];
    $r = $conn->query("SELECT year FROM award_editions ORDER BY year DESC");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) $out[] = 'NUFA Awards ' . (int)$x['year'];
    return $out;
}
function guest_editions($g) { return array_values(array_filter(array_map('trim', explode(',', (string)($g['editions'] ?? ''))))); }
function guest_photo_ok($g) { return $g['photo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $g['photo']); }
function guest_initials($n) { $p = preg_split('/\s+/', trim($n)); return strtoupper(mb_substr($p[0] ?? '', 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : '')); }

/** Dates of a story's event as readable text, '' when none. */
function story_when($p) {
    if (empty($p['event_date'])) return '';
    $a = strtotime($p['event_date']); $b = !empty($p['event_end_date']) ? strtotime($p['event_end_date']) : 0;
    if ($b && $b > $a) return date('j', $a) . (date('M Y', $a) === date('M Y', $b) ? '' : ' ' . date('M', $a)) . ' – ' . date('j F Y', $b);
    return date('l, j F Y', $a);
}
