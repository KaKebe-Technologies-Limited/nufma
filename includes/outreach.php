<?php
/**
 * NUFA Ambassadors (call for ambassadors, applications, monthly board meetings) and the Awards build-up:
 * performers who want to showcase at the NUFA Awards events, and hosts of pre-event workshops.
 * Managed in admin → Ambassadors and admin → Awards build-up.
 */
require_once __DIR__ . "/membership.php";   // mem_send, mem_store_upload, MEM_IMG_TYPES

const OUT_IMG_DIR = __DIR__ . '/../assets/images/uploads/outreach/';

function out_defaults() {
    return [
        'amb_open' => '1',
        'amb_intro' => 'NUFA Ambassadors are people who believe in Northern Uganda’s stories and use their voice, networks and time to help NUFA grow. Ambassadors lobby for NUFA, share our proposal with possible donors and partners, hold fundraising activities and stay closely connected to the NUFA Board through a monthly meeting.',
        'amb_proposal_url' => '', 'amb_proposal_note' => 'Approved ambassadors receive the NUFA proposal and donor pack by email.',
        'amb_notify_email' => '', 'amb_show_public' => '1',
        'amb_interests' => "NUFA Awards\nCapacity building and training\nNUFA SACCO\nFilm production and funding\nYouth and emerging filmmakers\nCultural preservation\nNUFA Academy\nFilm Showcase and distribution\nPartnerships and sponsorship\nOther",
        'amb_ways' => "Lobby and advocate for NUFA\nShare NUFA’s proposal with possible donors and partners\nHold fundraising events or campaigns\nIntroduce NUFA to sponsors and institutions\nHost or promote NUFA events where I live\nMedia and social media promotion\nProfessional or in-kind support\nOther",
        'bld_acts_open' => '1', 'bld_workshops_open' => '1',
        'bld_intro' => 'The road to the NUFA Awards is a celebration. Perform on the Launch, Nomination Night or Grand Finale stage, host a pre-event workshop for filmmakers, or become an ambassador — and help us build momentum for the night.',
        'bld_acts_intro' => 'Musicians, poets, spoken-word artists, dancers, comedians and cultural performers: tell us about your act and which NUFA Awards event you would like to showcase at.',
        'bld_workshop_intro' => 'Host a pre-event workshop for filmmakers. Tell us the venue, how many filmmakers you want to reach and what you will contribute, and the NUFA team will work with you.',
        'bld_notify_email' => '',
        'bld_act_types' => "Musician or band\nPoet\nSpoken-word artist\nDancer or dance group\nTraditional or cultural performer\nComedian\nDJ\nOther",
        'bld_contributions' => "Venue\nTrainers or facilitators\nEquipment\nRefreshments or meals\nTransport\nFunding\nPromotion and mobilisation\nOther",
    ];
}
function out_settings($conn) {
    $d = out_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'amb\\_%' OR setting_key LIKE 'bld\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d)) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}
function out_save_setting($conn, $k, $v) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); }
function out_lines($t) { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$t)))); }
function out_csv($a) { return implode(' | ', $a); }
function out_split($t) { return array_values(array_filter(array_map('trim', explode(' | ', (string)$t)))); }
function out_notify_to($conn, $S, $key) { return filter_var($S[$key], FILTER_VALIDATE_EMAIL) ? $S[$key] : (mem_settings($conn)['admin_notify_email'] ?? ''); }
function out_countries() {
    return ['Uganda', 'Kenya', 'Tanzania', 'Rwanda', 'South Sudan', 'Democratic Republic of the Congo', 'Burundi', 'Ethiopia', 'Nigeria', 'Ghana', 'South Africa', 'Egypt', 'United Kingdom', 'United States', 'Canada', 'Germany', 'France', 'Netherlands', 'Sweden', 'Norway', 'Denmark', 'Belgium', 'Ireland', 'Italy', 'Australia', 'United Arab Emirates', 'Qatar', 'China', 'India', 'Other'];
}

function out_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $T = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query("CREATE TABLE IF NOT EXISTS ambassadors (
        id INT AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(20) NOT NULL DEFAULT '', name VARCHAR(150) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '', country VARCHAR(80) NOT NULL DEFAULT '', city VARCHAR(120) NOT NULL DEFAULT '',
        occupation VARCHAR(190) NOT NULL DEFAULT '', organisation VARCHAR(190) NOT NULL DEFAULT '', profile_url VARCHAR(255) NOT NULL DEFAULT '', interests TEXT, support_ways TEXT, motivation TEXT, network TEXT, fundraising_idea TEXT,
        availability VARCHAR(60) NOT NULL DEFAULT '', timezone VARCHAR(60) NOT NULL DEFAULT '', best_time VARCHAR(120) NOT NULL DEFAULT '', status VARCHAR(10) NOT NULL DEFAULT 'pending', is_public TINYINT(1) NOT NULL DEFAULT 0, notes TEXT,
        ip VARCHAR(45) NOT NULL DEFAULT '', approved_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_status (status)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS ambassador_meetings (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, meet_date DATE NULL, meet_time VARCHAR(60) NOT NULL DEFAULT '', link VARCHAR(500) NOT NULL DEFAULT '', agenda TEXT, notes TEXT, invited_at DATETIME NULL, invited_count INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS award_acts (
        id INT AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(20) NOT NULL DEFAULT '', name VARCHAR(190) NOT NULL, act_type VARCHAR(60) NOT NULL DEFAULT '', event_ids VARCHAR(120) NOT NULL DEFAULT '', performers INT NOT NULL DEFAULT 1, duration_min INT NOT NULL DEFAULT 0,
        description TEXT, samples_url VARCHAR(500) NOT NULL DEFAULT '', tech_needs VARCHAR(600) NOT NULL DEFAULT '', fee_option VARCHAR(12) NOT NULL DEFAULT 'free', fee_amount VARCHAR(80) NOT NULL DEFAULT '', bio TEXT, photo VARCHAR(255) NOT NULL DEFAULT '',
        contact_name VARCHAR(150) NOT NULL DEFAULT '', email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '', social_url VARCHAR(255) NOT NULL DEFAULT '', status VARCHAR(10) NOT NULL DEFAULT 'pending', assigned_event_id INT NOT NULL DEFAULT 0,
        is_public TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, notes TEXT, ip VARCHAR(45) NOT NULL DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_status (status)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS award_workshops (
        id INT AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(20) NOT NULL DEFAULT '', host_name VARCHAR(190) NOT NULL, host_type VARCHAR(14) NOT NULL DEFAULT 'Organisation', contact_name VARCHAR(150) NOT NULL DEFAULT '', email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '',
        title VARCHAR(190) NOT NULL, description TEXT, audience VARCHAR(255) NOT NULL DEFAULT '', proposed_date DATE NULL, proposed_end DATE NULL, duration_text VARCHAR(80) NOT NULL DEFAULT '', venue VARCHAR(255) NOT NULL DEFAULT '', town VARCHAR(120) NOT NULL DEFAULT '',
        venue_capacity INT NOT NULL DEFAULT 0, filmmakers_needed INT NOT NULL DEFAULT 0, contribution_types VARCHAR(300) NOT NULL DEFAULT '', contribution TEXT, needs_from_nufa TEXT, status VARCHAR(10) NOT NULL DEFAULT 'pending', confirmed_date DATE NULL,
        is_public TINYINT(1) NOT NULL DEFAULT 0, notes TEXT, ip VARCHAR(45) NOT NULL DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY idx_status (status)) $T");

    $m = $conn->query("SELECT setting_value FROM settings WHERE setting_key='outreach_menu_added'");
    if ($m && !$m->num_rows) {
        $add = function ($parentUrl, $label, $url) use ($conn) {
            $p = $conn->query("SELECT id FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='" . $conn->real_escape_string($parentUrl) . "' LIMIT 1")->fetch_assoc();
            if (!$p || (int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='" . $conn->real_escape_string($url) . "' AND location='header'")->fetch_row()[0]) return;
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$p['id'])->fetch_row()[0];
            $conn->query("INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', " . (int)$p['id'] . ", '" . $conn->real_escape_string($label) . "', '" . $conn->real_escape_string($url) . "', $o)");
        };
        $add('awards.php', 'Perform at the Awards', 'awards-perform.php'); $add('awards.php', 'Host a pre-event workshop', 'awards-workshop.php'); $add('partners.php', 'Become an Ambassador', 'ambassadors.php');
        if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='ambassadors.php' AND location='footer2'")->fetch_row()[0]) {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer2'")->fetch_row()[0];
            $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer2', 'Become an Ambassador', 'ambassadors.php', $o)");
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('outreach_menu_added','1')");
    }
}

function out_next_meeting($conn) { return $conn->query("SELECT * FROM ambassador_meetings WHERE meet_date >= CURDATE() ORDER BY meet_date LIMIT 1")->fetch_assoc() ?: null; }
function out_ref($prefix, $id) { return $prefix . '-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); }
