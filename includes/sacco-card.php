<?php
/**
 * NUFA SACCO membership card: corporate two-sided ID card. Front: photo, name, member number, sub-region, district, shares, validity.
 * Back: QR code to the verification page, bio data, next of kin, terms, and the Chairperson's signature.
 * Needs assets/css/member-card.css on the page.
 */
require_once __DIR__ . "/sacco-members.php";
require_once __DIR__ . "/settings.php";
require_once __DIR__ . "/qr.php";
require_once __DIR__ . "/membership.php";

/** ID numbers are only partly shown on the card. */
function sac_mask($s) { $s = trim((string)$s); $n = mb_strlen($s); return $n <= 4 ? $s : str_repeat('•', $n - 4) . mb_substr($s, -4); }

function sac_card_photo($a) {
    if (empty($a['photo']) || !is_file(sac_storage() . $a['photo'])) return '';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file(sac_storage() . $a['photo']);
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(sac_storage() . $a['photo']));
}

/** $o: side ('both'|'front'|'back'), prefix ('' on public pages, '../' in the admin). */
function sac_card_html($conn, array $a, array $o = []) {
    $S = sac_settings($conn); $C = nufa_site_settings($conn);
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $pre = $o['prefix'] ?? ''; $side = $o['side'] ?? 'both';
    $fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';
    $expired = !empty($a['card_valid_until']) && strtotime($a['card_valid_until'] . ' 23:59:59') < time();
    $shares = (int)($a['shares_confirmed'] ?: $a['shares_applied']);
    $since = $a['card_issued_at'] ?: $a['approved_at'];
    $sub = trim((string)($a['subregion'] ?? '')); if ($sub === '') $sub = mem_subregion_of($a['district'] ?? '', mem_subregion_map($conn));
    $sub = $sub === 'Other' ? 'Other' : $sub;
    $sig = $S['sacapp_chair_signature'] ?? ''; $sigOk = $sig !== '' && is_file(__DIR__ . '/../assets/images/' . $sig);
    $signer = trim($S['sacapp_signer_name']); $title = $S['sacapp_signer_title'] ?: 'Chairperson, NUFA SACCO';
    $photo = sac_card_photo($a); $initial = mb_strtoupper(mb_substr($a['full_name'], 0, 1));
    $site = preg_replace('#^https?://#', '', rtrim($C['site_website'] ?? '', '/'));
    $link = sac_card_url($a);
    $role = trim($a['creative_role'] ?: $a['occupation']) ?: 'Savings & credit member';
    $out = '<div class="idc-set">';
    if ($side !== 'back') {
        $out .= '<div class="idc idc--sacco idc-front' . ($expired ? ' is-expired' : '') . '"><div class="idc-head"><span class="idc-logo"><img src="' . $e($pre) . 'assets/images/logo/nufa-logo.png" alt=""></span><span class="idc-org"><b>NUFA SACCO</b><i>Savings &amp; Credit Cooperative</i></span><span class="idc-tag">Member</span></div>'
            . '<div class="idc-photo">' . ($photo ? '<img src="' . $photo . '" alt="">' : '<span>' . $e($initial) . '</span>') . '</div>'
            . '<div class="idc-info"><h3>' . $e($a['full_name']) . '</h3><p class="idc-role">' . $e($role) . '</p>'
            . '<dl><div><dt>Sub-region</dt><dd>' . $e($sub ?: '—') . '</dd></div><div><dt>District</dt><dd>' . $e($a['district'] ?: '—') . '</dd></div><div><dt>Shares held</dt><dd>' . $shares . '</dd></div><div><dt>Member since</dt><dd>' . $fmt($since) . '</dd></div><div><dt>Gender</dt><dd>' . $e($a['gender'] ?: '—') . '</dd></div><div><dt>Valid until</dt><dd>' . ($a['card_valid_until'] ? $fmt($a['card_valid_until']) : 'In good standing') . '</dd></div></dl></div>'
            . '<div class="idc-band"><span><small>SACCO Member No.</small><b>' . $e($a['reg_no']) . '</b></span><em class="' . ($expired ? 'bad' : 'good') . '">' . ($expired ? 'Expired' : 'Active') . '</em></div>'
            . ($expired ? '<div class="idc-stamp">EXPIRED</div>' : '') . '</div>';
    }
    if ($side !== 'front') {
        $contact = array_filter([$C['site_email'] ?? '', $C['site_phone'] ?? '']);
        $kin = trim($a['kin_name'] . ($a['kin_phone'] ? ' · ' . $a['kin_phone'] : ''));
        $out .= '<div class="idc idc--sacco idc-back' . ($expired ? ' is-expired' : '') . '"><div class="idc-qr idc-qr--sm">' . qr_svg($link, 200, '#111111', '#ffffff') . '<small>Scan to verify</small></div>'
            . '<div class="idc-det"><h4>Member details</h4><dl>'
            . '<div><dt>Date of birth</dt><dd>' . $fmt($a['dob']) . '</dd></div><div><dt>Nationality</dt><dd>' . $e($a['nationality'] ?: '—') . '</dd></div>'
            . '<div><dt>' . $e($a['id_type'] ?: 'ID') . '</dt><dd>' . $e(sac_mask($a['id_number']) ?: '—') . '</dd></div><div><dt>Phone</dt><dd>' . $e($a['phone'] ?: '—') . '</dd></div>'
            . '<div><dt>Village / parish</dt><dd>' . $e($a['village'] ?: '—') . '</dd></div><div><dt>NUFA member no.</dt><dd>' . $e(($a['nufa_member'] === 'Yes' && $a['nufa_reg_no']) ? $a['nufa_reg_no'] : '—') . '</dd></div>'
            . '<div class="wide"><dt>Next of kin (emergency)</dt><dd>' . $e($kin ?: '—') . '</dd></div></dl></div>'
            . '<div class="idc-sign idc-sign--sacco">' . ($sigOk ? '<img src="' . $e($pre) . 'assets/images/' . $e($sig) . '" alt="Signature of the Chairperson">' : '<span class="idc-nosig"></span>') . '<i></i><b>' . $e($signer) . '</b><small>' . $e($title) . '</small></div>'
            . '<p class="idc-fine">' . $e($S['sacapp_card_note']) . '</p>'
            . '<div class="idc-band idc-band--back"><span>' . $e($site) . ($contact ? ' · ' . $e(implode(' · ', $contact)) : '') . '</span><span>' . $e($a['reg_no']) . '</span></div></div>';
    }
    return $out . '</div>';
}
