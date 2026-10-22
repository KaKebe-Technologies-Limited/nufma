<?php
/**
 * One place for every payment the website collects (membership, SACCO, donations and sponsorship, Academy fees).
 * The bank and Mobile Money details are set once in admin → Payment details (the `payment_methods` table).
 * Forms let the person choose Bank or Mobile Money, show the details straight away, and show them again after they submit.
 */
function pay_label($key) { return ['bank' => 'Bank', 'momo' => 'Mobile Money', 'online' => 'Pay online'][$key] ?? $key; }
function pay_key_of($type) { return ['bank' => 'bank', 'mobile_money' => 'momo', 'online' => 'online'][$type] ?? ''; }

/** Active payment methods grouped as ['bank' => [...], 'momo' => [...], 'online' => [...]] (empty groups left out; online only when asked for). */
function pay_groups($conn, $online = false) {
    $out = [];
    $r = @$conn->query("SELECT * FROM payment_methods WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
    if (!$r) return $out;
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $m) { $k = pay_key_of($m['method_type']); if ($k === '' || ($k === 'online' && !$online)) continue; $out[$k][] = $m; }
    $ordered = []; foreach (['bank', 'momo', 'online'] as $k) if (isset($out[$k])) $ordered[$k] = $out[$k];
    return $ordered;
}
function pay_valid_choice($conn, $choice, $online = false) { return array_key_exists($choice, pay_groups($conn, $online)); }
function pay_proof_required($conn) {
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key='pay_require_proof'");
    return $r && ($x = $r->fetch_row()) && $x[0] === '1';
}
function pay_note($conn) { $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key='pay_note'"); return ($r && ($x = $r->fetch_row())) ? trim((string)$x[0]) : ''; }

function pay_card($m, $ctx = []) {
    $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $momo = $m['method_type'] === 'mobile_money';
    $row = function ($label, $val, $copy = false) use ($h) { return $val === '' ? '' : '<div><dt>' . $h($label) . '</dt><dd>' . $h($val) . ($copy ? ' <button type="button" class="pay-copy" data-copy="' . $h(preg_replace('/[^A-Za-z0-9+]/', '', $val)) . '">Copy</button>' : '') . '</dd></div>'; };
    $o = '<div class="pay-card"><span class="pay-type">' . $h($m['label']) . ($m['provider'] !== '' ? ' · ' . $h($m['provider']) : '') . '</span><dl>';
    $o .= $row($momo ? 'Mobile Money number' : 'Account number', $m['account_number'], true) . $row($momo ? 'Registered name' : 'Account name', $m['account_name']) . $row('More details', $m['extra']);
    if (!empty($ctx['amount'])) $o .= '<div><dt>Amount</dt><dd><b>' . $h($ctx['amount']) . '</b></dd></div>';
    if (!empty($ctx['ref'])) $o .= '<div><dt>Your payment reference</dt><dd><b>' . $h($ctx['ref']) . '</b>' . ($m['reference_hint'] !== '' ? ' <small>— ' . $h($m['reference_hint']) . '</small>' : '') . '</dd></div>';
    elseif ($m['reference_hint'] !== '') $o .= '<div><dt>Reference</dt><dd>' . $h($m['reference_hint']) . '</dd></div>';
    $o .= '</dl>';
    if ($m['instructions'] !== '') $o .= '<p class="pay-ins">' . nl2br($h($m['instructions'])) . '</p>';
    if ($m['link_url'] !== '') $o .= '<p><a class="btn btn-gold btn-sm" href="' . $h($m['link_url']) . '" target="_blank" rel="noopener">Pay online ↗</a></p>';
    return $o . '</div>';
}

/** The Bank / Mobile Money chooser; the details of the chosen option show immediately. */
function pay_picker($conn, $name, $selected = '', $ctx = [], $online = false, $required = true) {
    $g = pay_groups($conn, $online); $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    if (!$g) return '<div class="pay-none">NUFA will show you the payment details as soon as you submit.</div>';
    if (!isset($g[$selected])) $selected = '';
    $o = '<div class="pay-picker" data-pay><div class="pay-choices" role="radiogroup">';
    foreach ($g as $k => $_) $o .= '<label class="pay-choice"><input type="radio" name="' . $h($name) . '" value="' . $k . '"' . ($selected === $k ? ' checked' : '') . ($required ? ' required' : '') . '><span>' . $h(pay_label($k)) . '</span></label>';
    $o .= '</div>';
    foreach ($g as $k => $ms) { $o .= '<div class="pay-panel" data-group="' . $k . '"' . ($selected === $k ? '' : ' hidden') . '>'; foreach ($ms as $m) $o .= pay_card($m, $ctx); $o .= '</div>'; }
    $o .= '<p class="pay-hint">Pay using the details shown, then submit your application. ' . $h(pay_note($conn)) . '</p></div>';
    static $js = false;
    if (!$js) { $js = true; $o .= '<script>(function(){document.addEventListener("change",function(e){var r=e.target;if(!r.matches||!r.matches(".pay-choice input"))return;var box=r.closest("[data-pay]");box.querySelectorAll(".pay-panel").forEach(function(p){p.hidden=p.dataset.group!==r.value;});});document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest(".pay-copy");if(!b)return;var v=b.dataset.copy;(navigator.clipboard?navigator.clipboard.writeText(v):Promise.reject()).then(function(){b.textContent="Copied ✓";}).catch(function(){window.prompt("Copy",v);});setTimeout(function(){b.textContent="Copy";},1800);});})();</script>'; }
    return $o;
}

/** The details to show after someone submits: the option they chose (or every option when none was chosen). */
function pay_block($conn, $choice = '', $ctx = [], $online = false, $title = 'How to pay') {
    $g = pay_groups($conn, $online); $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    if (!$g) return '<div class="pay-block"><h3>' . $h($title) . '</h3><p>NUFA will send you the payment details by email shortly.</p></div>';
    $show = isset($g[$choice]) ? [$choice => $g[$choice]] : $g;
    $o = '<div class="pay-block"><h3>' . $h($title) . '</h3>';
    if (!empty($ctx['amount'])) $o .= '<p class="pay-amount">Amount to pay: <b>' . $h($ctx['amount']) . '</b></p>';
    foreach ($show as $k => $ms) { if (count($show) > 1) $o .= '<h4>' . $h(pay_label($k)) . '</h4>'; foreach ($ms as $m) $o .= pay_card($m, $ctx); }
    $n = pay_note($conn); if ($n !== '') $o .= '<p class="pay-hint">' . $h($n) . '</p>';
    static $js2 = false; if (!$js2) { $js2 = true; $o .= '<script>document.addEventListener("click",function(e){var b=e.target.closest&&e.target.closest(".pay-copy");if(!b)return;var v=b.dataset.copy;(navigator.clipboard?navigator.clipboard.writeText(v):Promise.reject()).then(function(){b.textContent="Copied ✓";}).catch(function(){window.prompt("Copy",v);});setTimeout(function(){b.textContent="Copy";},1800);});</script>'; }
    return $o . '</div>';
}

/** Plain-text version for emails. */
function pay_text($conn, $choice = '', $ctx = [], $online = false) {
    $g = pay_groups($conn, $online); if (!$g) return "We will send you the payment details shortly.";
    $show = isset($g[$choice]) ? [$choice => $g[$choice]] : $g; $t = "HOW TO PAY\n";
    if (!empty($ctx['amount'])) $t .= "Amount: " . $ctx['amount'] . "\n";
    foreach ($show as $k => $ms) foreach ($ms as $m) {
        $t .= "\n" . pay_label($k) . ": " . $m['label'] . ($m['provider'] !== '' ? ' (' . $m['provider'] . ')' : '') . "\n";
        if ($m['account_name'] !== '') $t .= ($k === 'momo' ? "Registered name: " : "Account name: ") . $m['account_name'] . "\n";
        if ($m['account_number'] !== '') $t .= ($k === 'momo' ? "Mobile Money number: " : "Account number: ") . $m['account_number'] . "\n";
        if ($m['extra'] !== '') $t .= $m['extra'] . "\n";
        if (!empty($ctx['ref'])) $t .= "Reference: " . $ctx['ref'] . ($m['reference_hint'] !== '' ? ' — ' . $m['reference_hint'] : '') . "\n";
        if ($m['link_url'] !== '') $t .= "Pay online: " . $m['link_url'] . "\n";
    }
    return $t;
}
