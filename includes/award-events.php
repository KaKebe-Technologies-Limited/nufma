<?php
/**
 * NUFA Awards pre-events (Launch, Nomination Night, Grand Finale …): dates, venues, countdowns, sponsors and co-host applications.
 * Managed in admin → Awards pre-events.
 */
require_once __DIR__ . "/membership.php";

const AEV_STORAGE_DIR = __DIR__ . '/../storage/award-events/';
const AEV_PUBLIC_DIR = __DIR__ . '/../assets/images/uploads/awards/';

function aev_storage() {
    if (!is_dir(AEV_STORAGE_DIR)) @mkdir(AEV_STORAGE_DIR, 0775, true);
    if (!is_file(AEV_STORAGE_DIR . '.htaccess')) @file_put_contents(AEV_STORAGE_DIR . '.htaccess', "Require all denied\nDeny from all\n");
    if (!is_file(AEV_STORAGE_DIR . 'index.html')) @file_put_contents(AEV_STORAGE_DIR . 'index.html', '');
    return AEV_STORAGE_DIR;
}

function aev_roles() { return ['Co-host', 'Headline sponsor', 'Sponsor', 'In-kind partner', 'Media partner']; }
function aev_tiers() { return ['Co-host', 'Headline sponsor', 'Sponsor', 'In-kind partner', 'Media partner', 'Supporter']; }

function aev_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS award_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        edition_id INT NOT NULL DEFAULT 0,
        slug VARCHAR(60) NOT NULL,
        title VARCHAR(190) NOT NULL,
        tagline VARCHAR(255) NOT NULL DEFAULT '',
        description TEXT,
        image VARCHAR(255) NOT NULL DEFAULT '',
        date_confirmed TINYINT(1) NOT NULL DEFAULT 0,
        event_date DATE NULL,
        start_time TIME NULL,
        end_time TIME NULL,
        date_note VARCHAR(190) NOT NULL DEFAULT '',
        venue VARCHAR(255) NOT NULL DEFAULT '',
        venue_note VARCHAR(255) NOT NULL DEFAULT '',
        map_url VARCHAR(500) NOT NULL DEFAULT '',
        countdown_on TINYINT(1) NOT NULL DEFAULT 0,
        cohost_open TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS award_event_sponsors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        name VARCHAR(190) NOT NULL,
        tier VARCHAR(40) NOT NULL DEFAULT 'Sponsor',
        logo VARCHAR(255) NOT NULL DEFAULT '',
        website VARCHAR(500) NOT NULL DEFAULT '',
        blurb VARCHAR(300) NOT NULL DEFAULT '',
        app_id INT NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        KEY idx_event (event_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS award_event_apps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_no VARCHAR(30) NOT NULL DEFAULT '',
        token CHAR(32) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        event_ids VARCHAR(120) NOT NULL DEFAULT '',
        applicant_type VARCHAR(20) NOT NULL DEFAULT 'Organisation',
        org_name VARCHAR(190) NOT NULL DEFAULT '',
        contact_name VARCHAR(150) NOT NULL,
        role_title VARCHAR(120) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL DEFAULT '',
        website VARCHAR(255) NOT NULL DEFAULT '',
        about TEXT,
        logo VARCHAR(120) NOT NULL DEFAULT '',
        partnership_role VARCHAR(40) NOT NULL DEFAULT '',
        contribution TEXT,
        contribution_value VARCHAR(40) NOT NULL DEFAULT '',
        currency VARCHAR(8) NOT NULL DEFAULT 'UGX',
        benefits VARCHAR(500) NOT NULL DEFAULT '',
        message TEXT,
        reject_reason VARCHAR(500) NOT NULL DEFAULT '',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        approved_at DATETIME NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_token (token), KEY idx_status (status), KEY idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // seed the three pre-events once (no dates or venues are invented — the team confirms them in admin)
    $r = $conn->query("SELECT 1 FROM settings WHERE setting_key='aev_seeded'");
    if ($r && !$r->num_rows) {
        $ed = $conn->query("SELECT id FROM award_editions WHERE status='upcoming' AND is_active=1 ORDER BY year LIMIT 1")->fetch_assoc(); $eid = (int)($ed['id'] ?? 0);
        $rows = [['launch', 'Awards Launch', 'The official start of the new awards season.', 'The launch opens the new NUFA Awards season — announcing the call for entries, the categories and how filmmakers, sponsors and partners can take part.'],
                 ['nomination-night', 'Nomination Night', 'Celebrating the films and people in the running.', 'Nomination Night announces the nominees in every category and brings the community together to celebrate the work that made the shortlist.'],
                 ['grand-finale', 'Grand Finale — Awards Night', 'The ceremony that crowns the winners.', 'The Grand Finale is the awards night itself: the winners are announced and Northern Uganda\'s film community celebrates together.']];
        $s = $conn->prepare("INSERT INTO award_events (edition_id, slug, title, tagline, description, date_note, sort_order) VALUES (?,?,?,?,?, 'Date to be announced', ?)");
        foreach ($rows as $i => [$slug, $t, $tg, $d]) { $o = $i + 1; $s->bind_param("issssi", $eid, $slug, $t, $tg, $d, $o); $s->execute(); }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('aev_seeded','1')");
    }
}

function aev_defaults() {
    return [
        'aev_intro' => 'Join us on the road to the awards — from the launch to nomination night and the grand finale. Businesses, organisations and individuals can apply to co-host or sponsor any of these events.',
        'aev_notify_email' => '',
        'aev_email_received_subject' => 'Awards event application received — {ref}',
        'aev_email_received_body' => "Hello {name},\n\nThank you for offering to {role} NUFA Awards: {events}. We have received your application ({ref}) and the NUFA awards team will be in touch.\n\n— NUFA Awards team",
        'aev_email_approved_subject' => 'Your NUFA Awards application is approved — {ref}',
        'aev_email_approved_body' => "Hello {name},\n\nGood news — NUFA has approved your application to {role} NUFA Awards: {events} (reference {ref}).\n\n{published_line}The awards team will contact you with the next steps.\n\nThank you for standing with Northern Uganda's storytellers.\n\n— NUFA Awards team",
        'aev_email_rejected_subject' => 'Your NUFA Awards application — {ref}',
        'aev_email_rejected_body' => "Hello {name},\n\nThank you for offering to support NUFA Awards. We are not able to take your application ({ref}) forward at this time.\n\nReason: {reason}\n\n— NUFA Awards team",
    ];
}
function aev_settings($conn, $refresh = false) {
    static $c = null; if ($c !== null && !$refresh) return $c;
    $c = aev_defaults(); $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'aev\\_%'");
    while ($r && ($x = $r->fetch_assoc())) if (array_key_exists($x['setting_key'], $c) && ($x['setting_value'] !== '' || $x['setting_key'] === 'aev_notify_email')) $c[$x['setting_key']] = $x['setting_value'];
    return $c;
}

/** ISO date-time (Africa/Kampala, +03:00) a countdown should run to, or '' when the date is not confirmed. */
function aev_countdown_iso($ev) {
    if (empty($ev['countdown_on']) || empty($ev['date_confirmed']) || empty($ev['event_date'])) return '';
    return $ev['event_date'] . 'T' . (!empty($ev['start_time']) ? substr($ev['start_time'], 0, 8) : '00:00:00') . '+03:00';
}

function aev_when($ev) {
    if (!empty($ev['date_confirmed']) && !empty($ev['event_date'])) {
        $t = date('l, j F Y', strtotime($ev['event_date']));
        if (!empty($ev['start_time'])) $t .= ' · ' . substr($ev['start_time'], 0, 5) . (!empty($ev['end_time']) ? '–' . substr($ev['end_time'], 0, 5) : '');
        return $t;
    }
    return $ev['date_note'] !== '' ? $ev['date_note'] : 'Date to be announced';
}

function aev_names($conn, $csv) {
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)$csv)))); if (!$ids) return 'NUFA Awards';
    return implode(', ', array_column($conn->query("SELECT title FROM award_events WHERE id IN (" . implode(',', $ids) . ") ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC), 'title')) ?: 'NUFA Awards';
}

function aev_vars($conn, $a, array $extra = []) {
    return ['{name}' => $a['contact_name'], '{ref}' => $a['ref_no'], '{role}' => strtolower($a['partnership_role'] ?: 'support'), '{events}' => aev_names($conn, $a['event_ids']), '{reason}' => $a['reject_reason'] !== '' ? $a['reject_reason'] : 'Not stated', '{published_line}' => ''] + $extra;
}
function aev_display($a) { return trim($a['org_name']) !== '' ? $a['org_name'] : $a['contact_name']; }

/** Copies an applicant's logo to the public folder and adds them as a sponsor/co-host on each chosen event. */
function aev_publish_app($conn, $a, $tier, $blurb = '') {
    $logo = ''; $src = $a['logo'];
    if ($src !== '' && is_file(aev_storage() . basename($src))) { if (!is_dir(AEV_PUBLIC_DIR)) @mkdir(AEV_PUBLIC_DIR, 0775, true); $dest = 'ev-' . (int)$a['id'] . '-' . substr(md5($src), 0, 8) . '.' . strtolower(pathinfo($src, PATHINFO_EXTENSION)); if (@copy(aev_storage() . basename($src), AEV_PUBLIC_DIR . $dest)) $logo = 'uploads/awards/' . $dest; }
    $name = aev_display($a); $site = $a['website']; $aid = (int)$a['id']; $n = 0;
    foreach (array_filter(array_map('intval', explode(',', $a['event_ids']))) as $eid) {
        $ex = $conn->query("SELECT id FROM award_event_sponsors WHERE event_id=$eid AND app_id=$aid")->fetch_assoc();
        if ($ex) { $s = $conn->prepare("UPDATE award_event_sponsors SET name=?, tier=?, website=?, blurb=?, is_active=1, logo=IF(?<>'', ?, logo) WHERE id=?"); $s->bind_param("ssssssi", $name, $tier, $site, $blurb, $logo, $logo, $ex['id']); }
        else { $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM award_event_sponsors WHERE event_id=$eid")->fetch_row()[0]; $s = $conn->prepare("INSERT INTO award_event_sponsors (event_id, name, tier, logo, website, blurb, app_id, sort_order) VALUES (?,?,?,?,?,?,?,?)"); $s->bind_param("isssssii", $eid, $name, $tier, $logo, $site, $blurb, $aid, $o); }
        $s->execute(); $n++;
    }
    return $n;
}
