<?php
/**
 * Membership fees and renewals.
 *   New member  = joining fee + annual subscription (default UGX 10,000 + 10,000 = 20,000)
 *   Renewal     = annual subscription only (default UGX 10,000)
 * Existing members renew from the membership page; an admin approves the renewal, which extends the validity and emails the member.
 */
require_once __DIR__ . "/membership.php";

function mem_fee_num($s) { $d = preg_replace('/[^0-9]/', '', (string)$s); return $d === '' ? 0 : (int)$d; }
function mem_money($n, $cur = '') { return trim($cur . ' ' . number_format((int)$n)); }

/** ['joining','subscription','new','renew','cur'] — amounts as integers. */
function mem_fees($S) {
    $join = mem_fee_num($S['fee_joining'] ?? '10,000'); $sub = mem_fee_num($S['fee_subscription'] ?? '10,000');
    return ['joining' => $join, 'subscription' => $sub, 'new' => $join + $sub, 'renew' => $sub, 'cur' => $S['membership_currency'] ?? 'UGX'];
}

/** One-time: make the live fee UGX 20,000 for new members (10,000 joining + 10,000 subscription) and 10,000 for renewals. */
function mem_fees_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='mem_fees_v1'");
    if (!$f || $f->num_rows) return;
    mem_save_setting($conn, 'fee_joining', '10,000'); mem_save_setting($conn, 'fee_subscription', '10,000'); mem_save_setting($conn, 'membership_fee', '20,000');
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('mem_fees_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
    mem_settings($conn, true);
}

function mem_renew_install($conn) {
    static $done = false; if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS member_renewals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref VARCHAR(20) NOT NULL,
        member_id INT NOT NULL,
        name VARCHAR(150) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(40) NOT NULL DEFAULT '',
        amount VARCHAR(40) NOT NULL DEFAULT '',
        payment_method VARCHAR(60) NOT NULL DEFAULT '',
        payment_ref VARCHAR(120) NOT NULL DEFAULT '',
        payment_date DATE NULL,
        payment_proof VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        reject_reason VARCHAR(500) NOT NULL DEFAULT '',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        new_valid_until DATE NULL,
        approved_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ref (ref), KEY idx_member (member_id), KEY idx_status (status), KEY idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** An approved member matching email + (membership ID or phone), or null. */
function mem_renew_find($conn, $email, $regOrPhone) {
    $email = strtolower(trim($email)); $q = trim($regOrPhone); if ($email === '' || $q === '') return null;
    $s = $conn->prepare("SELECT * FROM members WHERE LOWER(email)=? AND status='approved'"); $s->bind_param("s", $email); $s->execute();
    $d = substr(preg_replace('/\D+/', '', $q), -9);
    foreach ($s->get_result()->fetch_all(MYSQLI_ASSOC) as $m) {
        if ($m['reg_no'] !== '' && strcasecmp($m['reg_no'], $q) === 0) return $m;
        if (strlen($d) >= 7 && substr(preg_replace('/\D+/', '', (string)$m['phone']), -9) === $d) return $m;
    }
    return null;
}

function mem_renew_ref() { return 'REN-' . strtoupper(bin2hex(random_bytes(4))); }

/** New validity if renewed today: continues from the current end date (or from today when it has already lapsed). */
function mem_renew_new_until($m, $months) {
    $cur = !empty($m['valid_until']) ? $m['valid_until'] : null;
    $base = ($cur && strtotime($cur) >= strtotime(date('Y-m-d'))) ? date('Y-m-d', strtotime($cur . ' +1 day')) : date('Y-m-d');
    return date('Y-m-d', strtotime("$base +" . max(1, (int)$months) . " months -1 day"));
}

/** Approves a renewal: extends the member's validity, marks the renewal approved and returns the new date. */
function mem_renew_approve($conn, $rid, $note = '') {
    $r = $conn->query("SELECT * FROM member_renewals WHERE id=" . (int)$rid)->fetch_assoc(); if (!$r || $r['status'] === 'approved') return null;
    $m = $conn->query("SELECT * FROM members WHERE id=" . (int)$r['member_id'])->fetch_assoc(); if (!$m) return null;
    $S = mem_settings($conn);
    $until = mem_renew_new_until($m, (int)$S['validity_months']);
    $from = (!empty($m['valid_until']) && strtotime($m['valid_until']) >= strtotime(date('Y-m-d'))) ? ($m['valid_from'] ?: date('Y-m-d')) : date('Y-m-d');
    $u = $conn->prepare("UPDATE members SET valid_from=?, valid_until=?, status='approved' WHERE id=?"); $u->bind_param("ssi", $from, $until, $m['id']); $u->execute();
    $a = $conn->prepare("UPDATE member_renewals SET status='approved', new_valid_until=?, approved_at=NOW(), admin_note=? WHERE id=?"); $n = mb_substr($note, 0, 500); $a->bind_param("ssi", $until, $n, $rid); $a->execute();
    return $until;
}

/** One-time: add "SACCO Governance" and "SACCO Management" to the saved list of leadership categories. */
function mem_team_cat_seed_v2($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='team_cat_sacco_v2'");
    if (!$f || $f->num_rows) return;
    $tc = $conn->query("SELECT setting_value FROM settings WHERE setting_key='list_team_categories'")->fetch_assoc();
    if ($tc && trim($tc['setting_value']) !== '') {
        $nv = rtrim($tc['setting_value']);
        foreach (['SACCO Governance', 'SACCO Management'] as $c) if (stripos("\n" . $nv . "\n", "\n" . $c . "\n") === false) $nv .= "\n" . $c;
        $u = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='list_team_categories'"); $u->bind_param('s', $nv); $u->execute();
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('team_cat_sacco_v2','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

/** One-time: the supplied signature is printed on every membership card — the NUFA membership card and the NUFA SACCO card (both earlier and new cards). Replace it any time in Membership settings / SACCO settings. */
function mem_card_signature_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='card_sig_v1'");
    if (!$f || $f->num_rows) return;
    $rel = 'signature/membership-signature.png';
    if (!is_file(__DIR__ . '/../assets/images/' . $rel)) return;   // picture not uploaded yet
    foreach (['president_signature', 'sacapp_chair_signature'] as $k) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param('ss', $k, $rel); $s->execute(); }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('card_sig_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
    mem_settings($conn, true);
}

/** One-time: add "SACCO Leadership" to the saved list of leadership categories (Admin → Leadership & team). */
function mem_team_cat_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='team_cat_sacco_v1'");
    if (!$f || $f->num_rows) return;
    $tc = $conn->query("SELECT setting_value FROM settings WHERE setting_key='list_team_categories'")->fetch_assoc();
    if ($tc && trim($tc['setting_value']) !== '' && stripos($tc['setting_value'], 'SACCO Leadership') === false) { $nv = rtrim($tc['setting_value']) . "
SACCO Leadership"; $u = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='list_team_categories'"); $u->bind_param('s', $nv); $u->execute(); }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('team_cat_sacco_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

/** One-time: the website is www.nufawards.com — replace any saved www.nufa.media / @nufa.media in settings, page text, menus and stories. */
function mem_domain_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='domain_nufawards_v1'");
    if (!$f || $f->num_rows) return;
    $targets = [['settings', 'setting_value'], ['page_content', 'field_value'], ['nav_items', 'url'], ['posts', 'content'], ['posts', 'excerpt'], ['programmes', 'summary'], ['resources', 'description']];
    foreach ($targets as [$t, $c]) {
        $x = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'"); if (!$x || !$x->num_rows) continue;
        $conn->query("UPDATE `$t` SET `$c`=REPLACE(REPLACE(`$c`,'www.nufa.media','www.nufawards.com'),'nufa.media','nufawards.com') WHERE `$c` LIKE '%nufa.media%'");
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('domain_nufawards_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
    mem_settings($conn, true);
}
