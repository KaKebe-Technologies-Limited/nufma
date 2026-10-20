<?php
/**
 * NUFA Awards extras, managed in admin → Awards setup:
 *  - award_items: guests, key moments, impact stories, highlight films and roadmap steps (one table, `kind` tells them apart)
 *  - awd_* settings: submission portal, tickets & booking
 */

const AWD_TEAM_CATEGORY = 'Awards Technical Team';

function awd_kinds() {
    return [
        'roadmap' => ['Roadmap step', 'Road map to the next edition', 'Phase / milestone', 'Short description', 'Timing (e.g. "March 2027" or "To be announced")'],
        'guest'   => ['Guest', 'Guests & dignitaries', 'Name', 'Title / organisation', ''],
        'moment'  => ['Key moment', 'Key moments', 'Moment title', 'Subtitle (optional)', ''],
        'impact'  => ['Impact story', 'Impact stories', 'Story title', 'Who / where (optional)', ''],
        'film'    => ['Film', 'Highlight films', 'Film title', 'Director · category (optional)', ''],
    ];
}

function awd_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS award_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        edition_id INT NOT NULL DEFAULT 0,
        kind VARCHAR(20) NOT NULL,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255) NOT NULL DEFAULT '',
        body TEXT,
        image VARCHAR(255) NOT NULL DEFAULT '',
        link_url VARCHAR(500) NOT NULL DEFAULT '',
        date_label VARCHAR(120) NOT NULL DEFAULT '',
        status VARCHAR(12) NOT NULL DEFAULT 'upcoming',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        KEY idx_kind (kind, edition_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (['detail' => 'TEXT', 'image' => "VARCHAR(255) NOT NULL DEFAULT ''"] as $c => $ddl)
        if ($conn->query("SHOW COLUMNS FROM award_winners LIKE '$c'")->num_rows === 0) $conn->query("ALTER TABLE award_winners ADD COLUMN `$c` $ddl");
    $r = $conn->query("SELECT 1 FROM settings WHERE setting_key='awd_seeded'");
    if ($r && !$r->num_rows) {
        // generic roadmap phases (no dates are asserted); the team sets real timing in admin
        $up = $conn->query("SELECT id FROM award_editions WHERE status='upcoming' AND is_active=1 ORDER BY year LIMIT 1")->fetch_assoc();
        $eid = (int)($up['id'] ?? 0);
        $phases = [['Call for entries opens', 'Filmmakers are invited to submit their work for the new edition.'], ['Submission deadline', 'Last date to submit films and entries.'],
                   ['Screening & judging', 'Entries are reviewed and judged against the approved criteria.'], ['Nominees announced', 'Nominees are published for every category.'],
                   ['Tickets on sale', 'Booking opens for the awards night.'], ['Awards night', 'The ceremony celebrating the year’s best work.']];
        $s = $conn->prepare("INSERT INTO award_items (edition_id, kind, title, body, date_label, status, sort_order) VALUES (?, 'roadmap', ?, ?, 'To be announced', 'upcoming', ?)");
        foreach ($phases as $i => [$t, $b]) { $o = $i + 1; $s->bind_param("issi", $eid, $t, $b, $o); $s->execute(); }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('awd_seeded','1')");
    }
}

function awd_defaults() {
    return [
        'awd_sub_status' => 'soon',        // soon | open | closed
        'awd_sub_url' => '', 'awd_sub_opens' => '', 'awd_sub_deadline' => '', 'awd_sub_fee' => '',
        'awd_sub_eligibility' => '', 'awd_sub_guidelines' => '', 'awd_sub_email' => '',
        'awd_tix_status' => 'soon',        // soon | on_sale | sold_out | closed
        'awd_tix_tiers' => '',             // one per line: Name | Price | Notes
        'awd_tix_url' => '', 'awd_tix_howto' => '', 'awd_tix_venue' => '', 'awd_tix_booking_form' => '1', 'awd_tix_notify' => '',
    ];
}

function awd_settings($conn, $refresh = false) {
    static $c = null;
    if ($c !== null && !$refresh) return $c;
    $c = awd_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'awd\\_%'");
    while ($r && ($x = $r->fetch_assoc())) if (array_key_exists($x['setting_key'], $c)) $c[$x['setting_key']] = $x['setting_value'];
    return $c;
}

/** Active items of one kind; $editionIds = [] for all editions. */
function awd_items($conn, $kind, array $editionIds = []) {
    $k = $conn->real_escape_string($kind);
    $w = "kind='$k' AND is_active=1";
    if ($editionIds) $w .= " AND edition_id IN (" . implode(',', array_map('intval', $editionIds)) . ")";
    return $conn->query("SELECT * FROM award_items WHERE $w ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
}

/** Ticket tiers: "Name | Price | Notes" per line. */
function awd_tiers($text) {
    $out = [];
    foreach (preg_split('/\R/', (string)$text) as $line) {
        $p = array_map('trim', explode('|', $line)); if ($p[0] === '') continue;
        $out[] = ['name' => $p[0], 'price' => $p[1] ?? '', 'note' => $p[2] ?? ''];
    }
    return $out;
}
