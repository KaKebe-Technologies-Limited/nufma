<?php
/** WhatsApp helpers: the organisation's chat link and quick-reply links for people who left a phone number. */

/** Digits-only international number for wa.me ('' when none). Uses the WhatsApp number setting, falling back to the main telephone. */
function nufa_wa_digits($phone, $cc = '256') {
    $raw = (string)$phone; $d = preg_replace('/\D+/', '', $raw);
    if ($d === '') return '';
    if (strpos(ltrim($raw), '+') === 0 || strpos($raw, '00') === 0) return preg_replace('/^00/', '', $d);
    if ($d[0] === '0') return $cc . substr($d, 1);
    return strlen($d) <= 9 ? $cc . $d : $d;
}

function nufa_wa_number($cfg) {
    $n = nufa_wa_digits(($cfg['whatsapp_number'] ?? '') !== '' ? $cfg['whatsapp_number'] : ($cfg['site_phone'] ?? ''));
    return strlen($n) >= 10 ? $n : '';
}

function nufa_wa_enabled($cfg) { return (($cfg['whatsapp_enabled'] ?? '1') === '1') && nufa_wa_number($cfg) !== ''; }

/** wa.me link to the organisation, with a ready-typed message. */
function nufa_wa_url($cfg, $text = '') {
    $n = nufa_wa_number($cfg); if ($n === '') return '';
    $t = $text !== '' ? $text : ($cfg['whatsapp_message'] ?? '');
    return 'https://wa.me/' . $n . ($t !== '' ? '?text=' . rawurlencode($t) : '');
}

/** wa.me link to a person's number (for admins replying from the dashboard). */
function nufa_wa_reply_url($phone, $text = '') {
    $n = nufa_wa_digits($phone); if (strlen($n) < 10) return '';
    return 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}
