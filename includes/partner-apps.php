<?php
/**
 * Partnership applications: individuals and organisations apply to co-host or partner on an activity / outreach,
 * stating their details and their contribution or sponsorship. After approval, and once the commitment is confirmed as met,
 * the partner (logo + details) is added to the Partners section of the website automatically.
 */
require_once __DIR__ . "/membership.php";

const PA_STORAGE_DIR = __DIR__ . '/../storage/partnerships/';
const PA_PUBLIC_DIR = __DIR__ . '/../assets/images/uploads/partners/';

function pa_storage() {
    if (!is_dir(PA_STORAGE_DIR)) @mkdir(PA_STORAGE_DIR, 0775, true);
    if (!is_file(PA_STORAGE_DIR . '.htaccess')) @file_put_contents(PA_STORAGE_DIR . '.htaccess', "Require all denied\nDeny from all\n");
    if (!is_file(PA_STORAGE_DIR . 'index.html')) @file_put_contents(PA_STORAGE_DIR . 'index.html', '');
    return PA_STORAGE_DIR;
}

function pa_types() {
    return ['Co-host an activity or event', 'Sponsor (cash)', 'In-kind support (venue, equipment, transport, supplies)', 'Outreach or community partner', 'Media partner', 'Technical or training partner', 'Other'];
}
/** Website partner category suggested for each partnership type. */
function pa_category_for($type) {
    return [0 => 'Industry bodies', 1 => 'Corporate sponsors', 2 => 'Corporate sponsors', 3 => 'Community & culture', 4 => 'Media', 5 => 'Technology', 6 => 'Industry bodies'][array_search($type, pa_types(), true)] ?? 'Industry bodies';
}
function pa_tier_for($type) {
    return [0 => 'Co-host', 1 => 'Sponsor', 2 => 'In-kind partner', 3 => 'Outreach partner', 4 => 'Media partner', 5 => 'Technical partner', 6 => 'Partner'][array_search($type, pa_types(), true)] ?? 'Partner';
}

function pa_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS partner_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_no VARCHAR(30) NOT NULL DEFAULT '',
        token CHAR(32) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        applicant_type VARCHAR(20) NOT NULL DEFAULT 'Organisation',
        org_name VARCHAR(190) NOT NULL DEFAULT '',
        contact_name VARCHAR(150) NOT NULL,
        role_title VARCHAR(120) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL DEFAULT '',
        website VARCHAR(255) NOT NULL DEFAULT '',
        location VARCHAR(150) NOT NULL DEFAULT '',
        about TEXT,
        logo VARCHAR(120) NOT NULL DEFAULT '',
        activity VARCHAR(255) NOT NULL DEFAULT '',
        partnership_type VARCHAR(120) NOT NULL DEFAULT '',
        contribution TEXT,
        contribution_value VARCHAR(40) NOT NULL DEFAULT '',
        currency VARCHAR(8) NOT NULL DEFAULT 'UGX',
        timing VARCHAR(190) NOT NULL DEFAULT '',
        benefits VARCHAR(500) NOT NULL DEFAULT '',
        message TEXT,
        commitment_text TEXT,
        commitment_due DATE NULL,
        commitment_met_at DATETIME NULL,
        auto_publish TINYINT(1) NOT NULL DEFAULT 1,
        publish_category VARCHAR(80) NOT NULL DEFAULT '',
        publish_tier VARCHAR(100) NOT NULL DEFAULT '',
        publish_blurb VARCHAR(500) NOT NULL DEFAULT '',
        partner_id INT NOT NULL DEFAULT 0,
        reject_reason VARCHAR(500) NOT NULL DEFAULT '',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        approved_at DATETIME NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_token (token), KEY idx_status (status), KEY idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (['description' => 'TEXT', 'activity' => "VARCHAR(255) NOT NULL DEFAULT ''", 'partner_app_id' => 'INT NOT NULL DEFAULT 0', 'published_at' => 'DATETIME NULL'] as $c => $ddl)
        if ($conn->query("SHOW COLUMNS FROM partners LIKE '$c'")->num_rows === 0) $conn->query("ALTER TABLE partners ADD COLUMN `$c` $ddl");
}

function pa_defaults() {
    return [
        'paapp_notify_email' => '',
        'paapp_intro' => "Would you like to co-host an activity, take part in an outreach or support NUFA with a contribution or sponsorship? Tell us who you are and what you can offer. Once your application is approved and your commitment is met, your logo and details appear in the Partners section of the website.",
        'paapp_auto_publish' => '1',
        'paapp_email_received_subject' => 'Partnership application received — {ref}',
        'paapp_email_received_body' => "Hello {name},\n\nThank you for offering to partner with NUFA. We have received your application ({ref}) to {type} for \"{activity}\".\n\nThe NUFA team will review it and get back to you. You will be listed under Partners on our website once the application is approved and your commitment has been met.\n\n— NUFA Partnerships",
        'paapp_email_approved_subject' => 'Your partnership application is approved — {ref}',
        'paapp_email_approved_body' => "Hello {name},\n\nGood news — NUFA has approved your partnership application ({ref}) for \"{activity}\".\n\nAgreed commitment:\n{commitment}\n{due_line}\nWhen the commitment has been met and confirmed by our team, your logo and details will be added to the Partners section of our website.\n\n{instructions}\n\nThank you for standing with Northern Uganda's storytellers.\n\n— NUFA Partnerships",
        'paapp_approved_instructions' => 'Our team will contact you with the details of how to deliver your commitment.',
        'paapp_email_published_subject' => 'You are now listed as a NUFA partner — {ref}',
        'paapp_email_published_body' => "Hello {name},\n\nThank you — your commitment has been confirmed and {display} is now listed in the Partners section of the NUFA website:\n{site_url}/partners.php\n\nWe are grateful for your support of \"{activity}\".\n\n— NUFA Partnerships",
        'paapp_email_rejected_subject' => 'Your partnership application — {ref}',
        'paapp_email_rejected_body' => "Hello {name},\n\nThank you for offering to partner with NUFA. We are not able to take your application ({ref}) forward at this time.\n\nReason: {reason}\n\nYou are welcome to contact us or apply again in future.\n\n— NUFA Partnerships",
    ];
}

function pa_settings($conn, $refresh = false) {
    static $c = null;
    if ($c !== null && !$refresh) return $c;
    $c = pa_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'paapp\\_%'");
    while ($r && ($x = $r->fetch_assoc())) if (array_key_exists($x['setting_key'], $c) && ($x['setting_value'] !== '' || in_array($x['setting_key'], ['paapp_notify_email', 'paapp_auto_publish'], true))) $c[$x['setting_key']] = $x['setting_value'];
    return $c;
}

function pa_display($a) { return trim($a['org_name']) !== '' ? $a['org_name'] : $a['contact_name']; }

function pa_vars($a, array $extra = []) {
    $due = $a['commitment_due'] ? 'Due by: ' . date('j F Y', strtotime($a['commitment_due'])) . "\n" : '';
    return ['{name}' => $a['contact_name'], '{ref}' => $a['ref_no'], '{type}' => strtolower($a['partnership_type']), '{activity}' => $a['activity'], '{display}' => pa_display($a),
            '{commitment}' => trim((string)$a['commitment_text']) !== '' ? trim($a['commitment_text']) : trim((string)$a['contribution']), '{due_line}' => $due, '{reason}' => $a['reject_reason'] !== '' ? $a['reject_reason'] : 'Not stated',
            '{site_url}' => mem_site_url(), '{instructions}' => ''] + $extra;
}

function pa_badge($a) {
    $s = $a['status'];
    if ($s === 'approved') {
        if ((int)$a['partner_id'] > 0) return '<span class="admin-badge">Published on website</span>';
        if ($a['commitment_met_at']) return '<span class="admin-badge">Commitment met</span>';
        return '<span class="admin-badge" style="background:#fff8e6;color:#6b4e0a">Awaiting commitment</span>';
    }
    return ['pending' => '<span class="admin-badge gray">Pending</span>', 'rejected' => '<span class="admin-badge red">Rejected</span>'][$s] ?? $s;
}

/** Adds (or updates) the partner on the website from an application. Returns the partners.id. */
function pa_publish($conn, $a, $logoOverride = null) {
    $name = pa_display($a);
    $logo = '';
    $src = $logoOverride !== null ? $logoOverride : $a['logo'];
    if ($src !== '' && is_file(pa_storage() . basename($src))) {
        if (!is_dir(PA_PUBLIC_DIR)) @mkdir(PA_PUBLIC_DIR, 0775, true);
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION)); $dest = 'pa-' . (int)$a['id'] . '-' . substr(md5($src), 0, 8) . '.' . $ext;
        if (@copy(pa_storage() . basename($src), PA_PUBLIC_DIR . $dest)) $logo = 'uploads/partners/' . $dest;
    }
    $cat = $a['publish_category'] !== '' ? $a['publish_category'] : pa_category_for($a['partnership_type']);
    $tier = $a['publish_tier'] !== '' ? $a['publish_tier'] : pa_tier_for($a['partnership_type']);
    $blurb = $a['publish_blurb'] !== '' ? $a['publish_blurb'] : mb_substr(trim((string)$a['about']), 0, 300);
    $site = $a['website']; $act = $a['activity'];
    $pid = (int)$a['partner_id'];
    if ($pid && $conn->query("SELECT id FROM partners WHERE id=$pid")->num_rows) {
        $s = $conn->prepare("UPDATE partners SET name=?, website=?, tier=?, category=?, description=?, activity=?, is_active=1, relationship='current', published_at=COALESCE(published_at, NOW()), logo=IF(?<>'', ?, logo) WHERE id=?");
        $s->bind_param("ssssssssi", $name, $site, $tier, $cat, $blurb, $act, $logo, $logo, $pid); $s->execute();
    } else {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM partners")->fetch_row()[0]; $aid = (int)$a['id'];
        $s = $conn->prepare("INSERT INTO partners (name, logo, website, tier, sort_order, is_active, relationship, category, description, activity, partner_app_id, published_at) VALUES (?,?,?,?,?,1,'current',?,?,?,?,NOW())");
        $s->bind_param("ssssisssi", $name, $logo, $site, $tier, $o, $cat, $blurb, $act, $aid); $s->execute(); $pid = $conn->insert_id;
    }
    $conn->query("UPDATE partner_applications SET partner_id=$pid WHERE id=" . (int)$a['id']);
    return $pid;
}

function pa_unpublish($conn, $a) {
    if ((int)$a['partner_id'] > 0) $conn->query("UPDATE partners SET is_active=0 WHERE id=" . (int)$a['partner_id']);
}
