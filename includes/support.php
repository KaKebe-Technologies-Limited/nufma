<?php
/**
 * Programme support: sponsor · donate · volunteer · partner.
 *
 * Supporters submit a request on the public site (support.php); NUFA reviews it
 * in admin → Support requests (approve / confirm payment / reject), and each
 * step can e-mail the supporter. How people pay is configured in admin →
 * Payment & Giving setup (payment methods, currencies, tiers, e-mail wording).
 */
require_once __DIR__ . "/membership.php";    // mem_send(), mem_store_upload(), mem_csrf(), MEM_STORAGE_DIR
require_once __DIR__ . "/schema.php";
require_once __DIR__ . "/settings.php";

const SUP_STORAGE_DIR = __DIR__ . "/../storage/support/";

function sup_kinds() {
    return [
        'donate'    => ['label' => 'Donate',    'noun' => 'donation',    'prefix' => 'DON', 'icon' => 'heart',     'blurb' => 'Give a one-off or recurring gift to help this programme reach more filmmakers.'],
        'sponsor'   => ['label' => 'Sponsor',   'noun' => 'sponsorship', 'prefix' => 'SPO', 'icon' => 'trophy',    'blurb' => 'Back a programme as an organisation or individual sponsor, with recognition where agreed.'],
        'volunteer' => ['label' => 'Volunteer', 'noun' => 'volunteer application', 'prefix' => 'VOL', 'icon' => 'users', 'blurb' => 'Give your time and skills — mentoring, production support, events or outreach.'],
        'partner'   => ['label' => 'Partner',   'noun' => 'partnership proposal', 'prefix' => 'PAR', 'icon' => 'handshake', 'blurb' => 'Collaborate with NUFA on training, equipment, screenings or other joint work.'],
    ];
}

function sup_defaults() {
    return [
        'sup_enable_donate' => '1', 'sup_enable_sponsor' => '1', 'sup_enable_volunteer' => '1', 'sup_enable_partner' => '1',
        'sup_currencies' => "UGX\nUSD\nEUR\nGBP", 'sup_default_currency' => 'UGX',
        'sup_suggested_amounts' => '', 'sup_min_amount' => '0',
        'sup_tiers' => '',
        'sup_volunteer_areas' => "Training and mentoring\nFilm production support\nEvents and screenings\nCommunity outreach\nFundraising\nAdministration and communications\nOther",
        'sup_availability' => "A few hours a month\nWeekly\nEvents only\nFull-time for a period",
        'sup_payment_intro' => "Make your payment using one of the methods below and quote your reference number. Your gift is confirmed once NUFA has verified the payment.",
        'sup_require_proof' => '0',
        'sup_notify_email' => '',
        'sup_terms' => "I confirm the information I have given is correct and I understand that NUFA reviews every request before it is accepted.",
        'sup_email_received_subject' => 'We received your {kind_noun} — {ref}',
        'sup_email_received_body' => "Hello {name},\n\nThank you for offering to support NUFA's {programme} programme. We have recorded your {kind_noun} with reference {ref}.\n\n{next_steps}\n\n— NUFA Support Team",
        'sup_email_approved_subject' => 'Your {kind_noun} has been accepted — {ref}',
        'sup_email_approved_body' => "Hello {name},\n\nGood news — NUFA has accepted your {kind_noun} for the {programme} programme (reference {ref}).\n\n{payment_block}\nWe will be in touch with the next steps. Thank you for standing with Northern Uganda's storytellers.\n\n— NUFA Support Team",
        'sup_email_completed_subject' => 'Thank you — your support is confirmed ({ref})',
        'sup_email_completed_body' => "Hello {name},\n\nWe have confirmed your {kind_noun} for the {programme} programme (reference {ref}){amount_line}. Thank you — it makes a real difference.\n\nKeep this email as your acknowledgement.\n\n— NUFA Support Team",
        'sup_email_rejected_subject' => 'About your {kind_noun} — {ref}',
        'sup_email_rejected_body' => "Hello {name},\n\nThank you for your interest in supporting NUFA. Unfortunately we are unable to proceed with your {kind_noun} at this time.\n\nReason: {reason}\n\nYou are welcome to contact us at {contact_email} if you would like to discuss it.\n\n— NUFA Support Team",
    ];
}

function sup_settings($conn, $refresh = false) {
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = sup_defaults();
        $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sup\\_%'");
        if ($r) while ($x = $r->fetch_assoc()) {
            $k = $x['setting_key']; if (!array_key_exists($k, $cache)) continue;
            if (strpos($k, 'sup_email_') === 0 && trim((string)$x['setting_value']) === '') continue;   // blank wording = use the default
            $cache[$k] = (string)$x['setting_value'];
        }
    }
    return $cache;
}

function sup_lines($text) { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$text)))); }

/** Sponsorship tiers: lines "Name | amount | description". */
function sup_tiers($S) {
    $out = [];
    foreach (sup_lines($S['sup_tiers']) as $line) {
        [$n, $a, $d] = array_pad(array_map('trim', explode('|', $line, 3)), 3, '');
        if ($n !== '') $out[] = ['name' => $n, 'amount' => $a, 'desc' => $d];
    }
    return $out;
}

function sup_amount($raw) {
    $v = preg_replace('/[^0-9.]/', '', (string)$raw);
    if ($v === '' || !is_numeric($v)) return null;
    $f = round((float)$v, 2);
    return $f > 0 && $f < 1e12 ? $f : null;
}
function sup_money($amount, $currency) {
    if ($amount === null || $amount === '') return '';
    return trim($currency . ' ' . number_format((float)$amount, ((float)$amount == floor((float)$amount)) ? 0 : 2));
}

function sup_active_methods($conn) {
    $r = $conn->query("SELECT * FROM payment_methods WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/** One payment method as plain text (for e-mails) or HTML. */
function sup_method_text($m, $ref = '') {
    $lines = [$m['label'] . ($m['provider'] ? ' (' . $m['provider'] . ')' : '')];
    if ($m['account_name'])   $lines[] = 'Account name: ' . $m['account_name'];
    if ($m['account_number']) $lines[] = 'Account / number: ' . $m['account_number'];
    if ($m['extra'])          $lines[] = $m['extra'];
    if ($m['link_url'])       $lines[] = 'Pay online: ' . $m['link_url'];
    if ($m['instructions'])   $lines[] = $m['instructions'];
    $lines[] = 'Use this reference' . ($ref ? ': ' . $ref : ' (given after you submit)') . ($m['reference_hint'] ? ' — ' . $m['reference_hint'] : '');
    return implode("\n", $lines);
}
function sup_payment_block($conn, $ref, $methodLabel = '') {
    $S = sup_settings($conn);
    $methods = sup_active_methods($conn);
    if (!$methods) return "NUFA will share payment details with you directly.\n";
    $out = $S['sup_payment_intro'] . "\n\n";
    foreach ($methods as $m) {
        if ($methodLabel !== '' && $m['label'] !== $methodLabel && count($methods) > 1) continue;
        $out .= sup_method_text($m, $ref) . "\n\n";
    }
    if (trim($out) === trim($S['sup_payment_intro'])) foreach ($methods as $m) $out .= sup_method_text($m, $ref) . "\n\n";
    return $out;
}

function sup_programme_title($conn, $id) {
    if (!$id) return 'NUFA';
    $r = $conn->query("SELECT title FROM programmes WHERE id=" . (int)$id)->fetch_assoc();
    return $r['title'] ?? 'NUFA';
}

function sup_vars($conn, $e, $extra = []) {
    $kinds = sup_kinds(); $k = $kinds[$e['kind']] ?? ['label' => $e['kind'], 'noun' => $e['kind']];
    $cfg = nufa_site_settings($conn);
    $amount = $e['amount'] !== null && $e['amount'] !== '' ? sup_money($e['amount'], $e['currency']) : '';
    return array_merge([
        'name' => $e['name'], 'ref' => $e['ref_no'], 'kind' => $k['label'], 'kind_noun' => $k['noun'],
        'programme' => sup_programme_title($conn, $e['programme_id']),
        'amount' => $amount, 'currency' => $e['currency'],
        'amount_line' => $amount ? ' of ' . $amount : '',
        'reason' => $e['reject_reason'] ?: 'Not specified',
        'contact_email' => $cfg['site_email'], 'site_url' => mem_site_url(),
        'payment_block' => '', 'next_steps' => '',
    ], $extra);
}

/** Sends one of the four lifecycle e-mails for an engagement. Returns true when handed to the mail system. */
function sup_notify($conn, $e, $event) {
    $S = sup_settings($conn);
    $payable = in_array($e['kind'], ['donate', 'sponsor'], true);
    $extra = [];
    if ($event === 'approved' && $payable) {
        $extra['payment_block'] = !empty($e['payment_proof'])
            ? "We have received your proof of payment and will confirm once it has been verified.\n"
            : "How to pay:\n\n" . sup_payment_block($conn, $e['ref_no'], $e['method']) . "\n";
    }
    if ($event === 'received') {
        $extra['next_steps'] = $payable
            ? "Next steps: NUFA will review your request and confirm by email. If you have not paid yet, here is how:\n\n" . sup_payment_block($conn, $e['ref_no'], $e['method'])
            : "Next steps: our team will review your request and contact you shortly.";
    }
    $v = sup_vars($conn, $e, $extra);
    $fill = fn($t) => mem_fill($t, $v);
    $ok = mem_send($conn, null, 'support-' . $event, $e['email'], $fill($S["sup_email_{$event}_subject"]), $fill($S["sup_email_{$event}_body"]));
    return $ok;
}

function sup_notify_admin($conn, $e) {
    $S = sup_settings($conn); $m = mem_settings($conn);
    $to = filter_var($S['sup_notify_email'], FILTER_VALIDATE_EMAIL) ? $S['sup_notify_email'] : $m['admin_notify_email'];
    if (!$to || $m['notify_admin'] !== '1') return false;
    $kinds = sup_kinds(); $url = mem_site_url() . '/admin/support-view.php?id=' . $e['id'];
    $amount = $e['amount'] !== null && $e['amount'] !== '' ? "\nAmount: " . sup_money($e['amount'], $e['currency']) : '';
    return mem_send($conn, null, 'support-admin', $to, "New {$kinds[$e['kind']]['noun']}: {$e['name']} ({$e['ref_no']})",
        "A new {$kinds[$e['kind']]['noun']} is waiting for review.\n\nReference: {$e['ref_no']}\nFrom: {$e['name']}" . ($e['organisation'] ? " ({$e['organisation']})" : '') . "\nProgramme: " . sup_programme_title($conn, $e['programme_id']) . $amount . "\n\nReview it: $url", $url, $e['email']);
}

function sup_status_badge($s) {
    $map = ['pending' => ['Pending', 'gray'], 'approved' => ['Approved', ''], 'completed' => ['Completed', ''], 'rejected' => ['Rejected', 'red']];
    [$l, $c] = $map[$s] ?? [ucfirst($s), 'gray'];
    return '<span class="admin-badge ' . $c . '">' . $l . '</span>';
}

function sup_storage_ready() {
    if (!is_dir(SUP_STORAGE_DIR)) @mkdir(SUP_STORAGE_DIR, 0775, true);
    $ht = SUP_STORAGE_DIR . ".htaccess";
    if (!is_file($ht)) @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
}
