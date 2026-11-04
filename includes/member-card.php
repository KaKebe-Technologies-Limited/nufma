<?php
/**
 * NUFA membership card: corporate two-sided ID card (front: photo, name, guild, membership ID, validity; back: QR code to the
 * verification page, terms, signature and contacts). Used by the public card page and the admin card pages.
 */
require_once __DIR__ . "/membership.php";
require_once __DIR__ . "/settings.php";
require_once __DIR__ . "/qr.php";

/** Adds members.card_issued_at once (existing approved members with an ID count as already issued). */
function mem_card_ensure($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='mem_card_col'");
    if (!$f || $f->num_rows) return;
    $c = $conn->query("SHOW COLUMNS FROM members LIKE 'card_issued_at'");
    if ($c && !$c->num_rows) {
        $conn->query("ALTER TABLE members ADD COLUMN card_issued_at DATETIME NULL");
        $conn->query("UPDATE members SET card_issued_at=COALESCE(approved_at, NOW()) WHERE status='approved' AND reg_no IS NOT NULL AND reg_no<>''");
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('mem_card_col','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

/** One-time: use the supplied signature image on the card if none has been set (an admin can replace or remove it in Membership settings). */
function mem_card_sig_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='sig_seed_v1'");
    if (!$f || $f->num_rows) return;
    $rel = 'signature/president-signature.png';
    if (!is_file(__DIR__ . '/../assets/images/' . $rel)) return;
    $S = mem_settings($conn, true);
    if (($S['president_signature'] ?? '') === '') mem_save_setting($conn, 'president_signature', $rel);
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('sig_seed_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

/** Issues the card: approves the member, gives them a membership ID if they have none, and records the issue date. Returns the ID. */
function mem_card_issue($conn, $id, $notify = false) {
    mem_card_ensure($conn);
    require_once __DIR__ . "/member-admin.php";
    $reg = mem_issue_id($conn, (int)$id);
    $conn->query("UPDATE members SET card_issued_at=COALESCE(card_issued_at, NOW()) WHERE id=" . (int)$id);
    if ($notify) mem_admin_notify($conn, $id);
    return $reg;
}

function mem_card_photo($m) {
    if (empty($m['photo']) || !defined('MEM_STORAGE_DIR') || !is_file(MEM_STORAGE_DIR . $m['photo'])) return '';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file(MEM_STORAGE_DIR . $m['photo']);
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(MEM_STORAGE_DIR . $m['photo']));
}

/**
 * HTML for the card. $m must include guild_name. $o: 'side' => 'both'|'front'|'back', 'prefix' => path to the site root from the page ('' or '../').
 * Needs assets/css/member-card.css on the page.
 */
function mem_card_html($conn, array $m, array $o = []) {
    mem_card_sig_seed($conn);
    $S = mem_settings($conn); $C = nufa_site_settings($conn);
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $pre = $o['prefix'] ?? ''; $side = $o['side'] ?? 'both';
    $fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';
    $expired = $m['valid_until'] && strtotime($m['valid_until'] . ' 23:59:59') < time();
    $photo = mem_card_photo($m);
    $sig = $S['president_signature'] ?? ''; $sigOk = $sig !== '' && is_file(__DIR__ . '/../assets/images/' . $sig);
    $link = mem_card_link($m);
    $site = preg_replace('#^https?://#', '', rtrim($C['site_website'] ?? '', '/'));
    $since = !empty($m['approved_at']) ? date('Y', strtotime($m['approved_at'])) : ($m['valid_from'] ? date('Y', strtotime($m['valid_from'])) : '—');
    $initial = mb_strtoupper(mb_substr($m['full_name'], 0, 1));
    $out = '<div class="idc-set">';
    if ($side !== 'back') {
        $out .= '<div class="idc idc-front' . ($expired ? ' is-expired' : '') . '"><div class="idc-head"><span class="idc-logo"><img src="' . $e($pre) . 'assets/images/logo/nufa-logo.png" alt=""></span><span class="idc-org"><b>NUFA</b><i>Northern Uganda Filmmakers Association</i></span><span class="idc-tag">Member</span></div>'
            . '<div class="idc-photo">' . ($photo ? '<img src="' . $photo . '" alt="">' : '<span>' . $e($initial) . '</span>') . '</div>'
            . '<div class="idc-info"><h3>' . $e($m['full_name']) . '</h3><p class="idc-guild">' . $e($m['guild_name'] ?: 'Member') . '</p>'
            . '<dl><div><dt>Valid from</dt><dd>' . $fmt($m['valid_from']) . '</dd></div><div><dt>Valid until</dt><dd>' . $fmt($m['valid_until']) . '</dd></div><div><dt>District</dt><dd>' . $e($m['district'] ?: '—') . '</dd></div><div><dt>Member since</dt><dd>' . $e($since) . '</dd></div></dl></div>'
            . '<div class="idc-band"><span><small>Membership ID</small><b>' . $e($m['reg_no'] ?: 'NOT YET ISSUED') . '</b></span><em class="' . ($expired ? 'bad' : 'good') . '">' . ($expired ? 'Expired' : ($m['status'] === 'approved' ? 'Active' : ucfirst($m['status']))) . '</em></div>'
            . ($expired ? '<div class="idc-stamp">EXPIRED</div>' : '') . '</div>';
    }
    if ($side !== 'front') {
        $contact = array_filter([$C['site_email'] ?? '', $C['site_phone'] ?? '', $C['site_address'] ?? '']);
        $out .= '<div class="idc idc-back' . ($expired ? ' is-expired' : '') . '"><div class="idc-qr">' . qr_svg($link, 240, '#111111', '#ffffff') . '<small>Scan to verify</small></div>'
            . '<div class="idc-terms"><h4>Terms of use</h4><p>' . $e($S['card_note']) . '</p><p>If found, please return to NUFA or report it at ' . $e($site) . '.</p></div>'
            . '<div class="idc-sign">' . ($sigOk ? '<img src="' . $e($pre) . 'assets/images/' . $e($sig) . '" alt="Signature">' : '<span class="idc-nosig"></span>') . '<i></i><b>' . $e($S['president_name']) . '</b><small>' . $e($S['president_title']) . '</small></div>'
            . '<div class="idc-contact">' . implode('<span>•</span>', array_map($e, $contact)) . '</div>'
            . '<div class="idc-band idc-band--back"><span>' . $e($site) . '</span><span>' . $e($m['reg_no'] ?: '') . '</span></div></div>';
    }
    return $out . '</div>';
}
