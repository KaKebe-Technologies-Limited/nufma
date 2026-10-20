<?php
/**
 * Public feedback on NUFA activities, initiatives and programmes. People can give their name or stay anonymous;
 * anonymous feedback keeps no name, email or phone. Managed in admin → Feedback.
 */
require_once __DIR__ . "/membership.php";

function fb_categories() {
    return ['Programme' => 'A NUFA programme', 'Activity' => 'An activity, event or outreach', 'Initiative' => 'An initiative (NUFA Awards, SACCO, membership, partnerships)', 'Website' => 'This website or our communication', 'General' => 'NUFA in general'];
}
function fb_types() { return ['Praise' => 'Something that went well', 'Suggestion' => 'A suggestion or idea', 'Concern' => 'A concern or complaint', 'Question' => 'A question']; }
function fb_statuses() { return ['new' => 'New', 'reviewed' => 'Reviewed', 'actioned' => 'Actioned', 'archived' => 'Archived']; }

function fb_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_no VARCHAR(30) NOT NULL DEFAULT '',
        category VARCHAR(30) NOT NULL DEFAULT 'General',
        about VARCHAR(255) NOT NULL DEFAULT '',
        feedback_type VARCHAR(20) NOT NULL DEFAULT 'Suggestion',
        rating TINYINT NOT NULL DEFAULT 0,
        participated VARCHAR(10) NOT NULL DEFAULT '',
        recommend VARCHAR(10) NOT NULL DEFAULT '',
        comment TEXT,
        suggestion TEXT,
        identity VARCHAR(10) NOT NULL DEFAULT 'anonymous',
        name VARCHAR(150) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(40) NOT NULL DEFAULT '',
        allow_contact TINYINT(1) NOT NULL DEFAULT 0,
        allow_publish TINYINT(1) NOT NULL DEFAULT 0,
        status VARCHAR(10) NOT NULL DEFAULT 'new',
        is_public TINYINT(1) NOT NULL DEFAULT 0,
        public_name VARCHAR(150) NOT NULL DEFAULT '',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        replied_at DATETIME NULL,
        ip_hash CHAR(40) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status), KEY idx_pub (is_public, created_at), KEY idx_ip (ip_hash, created_at), KEY idx_cat (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fb_defaults() {
    return [
        'fb_enabled' => '1',
        'fb_intro' => "Tell us what you think about NUFA's programmes, activities and initiatives. Your honest feedback helps us improve. You can share your name or stay completely anonymous — anonymous feedback keeps no name, email or phone number.",
        'fb_notify_email' => '',
        'fb_show_public' => '1',
        'fb_reply_subject' => 'Thank you for your feedback — {ref}',
        'fb_reply_footer' => "— The NUFA Team",
    ];
}
function fb_settings($conn, $refresh = false) {
    static $c = null;
    if ($c !== null && !$refresh) return $c;
    $c = fb_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'fb\\_%'");
    while ($r && ($x = $r->fetch_assoc())) if (array_key_exists($x['setting_key'], $c) && ($x['setting_value'] !== '' || in_array($x['setting_key'], ['fb_enabled', 'fb_show_public', 'fb_notify_email'], true))) $c[$x['setting_key']] = $x['setting_value'];
    return $c;
}

/** Keyed hash of the visitor's IP, used only to limit spam (never stored in readable form). */
function fb_ip_hash() { return substr(hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', 'nufa-feedback-' . (function_exists('mem_csrf') ? 'v1' : 'v1')), 0, 40); }

function fb_stars($n, $size = 16) {
    $n = max(0, min(5, (int)$n)); $s = '<span class="stars" aria-label="' . $n . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) $s .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" class="' . ($i <= $n ? 'on' : 'off') . '"><path d="M12 2l3 6.500 7 .9-5.200 4.800 1.400 7L12 17.800 5.800 21.200l1.400-7L2 9.400l7-.9L12 2Z"/></svg>';
    return $s . '</span>';
}

/** Things people can give feedback on: programmes, then recent/upcoming activities, then award editions. */
function fb_subjects($conn) {
    $out = ['Programmes' => array_column($conn->query("SELECT title FROM programmes WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC), 'title')];
    try {
        require_once __DIR__ . "/schedule.php";
        $ev = [];
        foreach (sch_occurrences($conn, date('Y-m-d', strtotime('-180 day')), date('Y-m-d', strtotime('+90 day'))) as $o) if ($o['kind'] === 'programme') $ev[$o['title'] . ' — ' . date('M Y', strtotime($o['date']))] = 1;
        $out['Recent & upcoming activities'] = array_slice(array_keys($ev), 0, 25);
    } catch (Throwable $e) { }
    $out['NUFA Awards'] = array_map(fn($r) => 'NUFA Awards ' . $r['year'], $conn->query("SELECT year FROM award_editions WHERE is_active=1 ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC));
    $out['Initiatives'] = ['NUFA SACCO', 'Membership', 'Industry Network', 'Newsletter', 'Partnerships'];
    return array_filter($out);
}
