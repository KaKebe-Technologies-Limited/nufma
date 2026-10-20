<?php
/**
 * Shared handling for the public enquiry forms (contact, partnership, SACCO
 * interest, industry network). Every form gets the same protections:
 *   - CSRF token tied to the visitor's session
 *   - hidden honeypot field + minimum fill time
 *   - per-IP rate limit
 *   - server-side validation, consent capture
 * Submissions are stored in the `enquiries` table (admin → Enquiries) and the
 * NUFA inbox is notified by email where the host can send mail.
 */
require_once __DIR__ . "/membership.php";
require_once __DIR__ . "/sections.php";
require_once __DIR__ . "/settings.php";   // mem_csrf(), mem_send(), mem_settings()
require_once __DIR__ . "/schema.php";

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function form_open_fields() {
    $t = time();
    $sig = hash_hmac('sha256', (string)$t, mem_csrf());
    return mem_csrf_field()
        . '<input type="text" name="url_confirm" value="" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">'
        . '<input type="hidden" name="_t" value="' . $t . '"><input type="hidden" name="_s" value="' . $sig . '">';
}

/** Returns an error message, or '' when the submission looks legitimate. */
function form_guard($conn, $type, $table = 'enquiries') {
    if (!mem_csrf_ok()) return "Your session expired. Please reload the page and try again.";
    if (trim($_POST['url_confirm'] ?? '') !== '') return "Submission rejected.";
    $t = (int)($_POST['_t'] ?? 0);
    $sig = (string)($_POST['_s'] ?? '');
    if (!$t || !hash_equals(hash_hmac('sha256', (string)$t, mem_csrf()), $sig)) return "Your session expired. Please reload the page and try again.";
    if (time() - $t < 3) return "That was very quick — please check your details and submit again.";
    if (time() - $t > 7200) return "This form has expired. Please reload the page and try again.";
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $table = in_array($table, ['enquiries', 'engagements'], true) ? $table : 'enquiries';
    $s = $conn->prepare("SELECT COUNT(*) c FROM $table WHERE ip=? AND created_at > (NOW() - INTERVAL 1 HOUR)");
    $s->bind_param("s", $ip);
    $s->execute();
    if ((int)$s->get_result()->fetch_assoc()['c'] >= 5) return "Too many submissions from your connection. Please try again later.";
    return '';
}

function form_clean($v, $max = 500) {
    $v = trim((string)$v);
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v);
    return mb_substr($v, 0, $max);
}

/** Stores an enquiry and notifies NUFA. $details is an assoc array saved as readable lines. */
function form_save($conn, $type, $topic, $name, $org, $email, $phone, $message, array $details, $consent) {
    $lines = [];
    foreach ($details as $k => $v) if ($v !== '' && $v !== null) $lines[] = $k . ': ' . $v;
    $detailText = implode("\n", $lines);
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
    $c = $consent ? 1 : 0;
    $s = $conn->prepare("INSERT INTO enquiries (type, topic, name, organisation, email, phone, message, details, consent, ip) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $s->bind_param("ssssssssis", $type, $topic, $name, $org, $email, $phone, $message, $detailText, $c, $ip);
    $ok = $s->execute();
    if (!$ok) return false;
    $id = $conn->insert_id;

    $S = mem_settings($conn);
    $pageOf = ['contact' => 'contact', 'partner' => 'partners', 'sacco' => 'sacco', 'network' => 'network'][$type] ?? $type;
    $fcfg = nufa_form_cfg($pageOf);
    $notifyTo = filter_var($fcfg['notify'], FILTER_VALIDATE_EMAIL) ? $fcfg['notify'] : $S['admin_notify_email'];
    if ($S['notify_admin'] === '1' && $notifyTo) {
        $label = ['contact' => 'Contact enquiry', 'partner' => 'Partnership enquiry', 'sacco' => 'SACCO expression of interest', 'network' => 'Industry network interest'][$type] ?? 'Enquiry';
        $url = mem_site_url() . '/admin/enquiry-view.php?id=' . $id;
        $body = "$label from $name <$email>" . ($org ? " ($org)" : '') . "\n\n" . ($topic ? "Topic: $topic\n" : '') . ($message ? "\n$message\n" : '') . ($detailText ? "\n$detailText\n" : '') . "\nView in the dashboard: $url";
        mem_send($conn, null, 'enquiry', $notifyTo, "$label — $name", $body, $url, $email);
    }
    return $id;
}

/** Renders the success / error banner shown above a form. */
function form_banner($sent, array $errors, $okText) {
    if ($sent) return '<div class="alert ok" role="status"><b>Thank you.</b> ' . htmlspecialchars($okText) . '</div>';
    if ($errors) return '<div class="alert err" role="alert"><b>Please fix the following:</b><ul>' . implode('', array_map(fn($e) => '<li>' . htmlspecialchars($e) . '</li>', $errors)) . '</ul></div>';
    return '';
}

/* ---------- field renderers (keep every public form visually and semantically consistent) ---------- */
function f_val($name, $default = '') { return htmlspecialchars((string)($_POST[$name] ?? $default), ENT_QUOTES, 'UTF-8'); }

function f_input($name, $label, array $o = []) {
    $type = $o['type'] ?? 'text'; $req = !empty($o['required']); $full = !empty($o['full']);
    $id = 'f-' . $name;
    $attrs = ($req ? ' required' : '') . (!empty($o['placeholder']) ? ' placeholder="' . htmlspecialchars($o['placeholder'], ENT_QUOTES) . '"' : '') . (!empty($o['autocomplete']) ? ' autocomplete="' . $o['autocomplete'] . '"' : '') . (!empty($o['maxlength']) ? ' maxlength="' . (int)$o['maxlength'] . '"' : '');
    return '<div class="field' . ($full ? ' full' : '') . '"><label for="' . $id . '">' . htmlspecialchars($label) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>'
        . '<input id="' . $id . '" name="' . $name . '" type="' . $type . '" value="' . f_val($name, $o['default'] ?? '') . '"' . $attrs . '>'
        . (!empty($o['hint']) ? '<span class="hint">' . htmlspecialchars($o['hint']) . '</span>' : '') . '</div>';
}
function f_select($name, $label, array $options, array $o = []) {
    $req = !empty($o['required']); $id = 'f-' . $name; $cur = $_POST[$name] ?? ($o['default'] ?? '');
    $html = '<div class="field' . (!empty($o['full']) ? ' full' : '') . '"><label for="' . $id . '">' . htmlspecialchars($label) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label><select id="' . $id . '" name="' . $name . '"' . ($req ? ' required' : '') . '><option value="">Select…</option>';
    foreach ($options as $opt) $html .= '<option' . ($cur === $opt ? ' selected' : '') . '>' . htmlspecialchars($opt) . '</option>';
    return $html . '</select></div>';
}
function f_textarea($name, $label, array $o = []) {
    $req = !empty($o['required']); $id = 'f-' . $name;
    return '<div class="field full"><label for="' . $id . '">' . htmlspecialchars($label) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label><textarea id="' . $id . '" name="' . $name . '"' . ($req ? ' required' : '') . (!empty($o['placeholder']) ? ' placeholder="' . htmlspecialchars($o['placeholder'], ENT_QUOTES) . '"' : '') . ' maxlength="' . (int)($o['maxlength'] ?? 3000) . '">' . f_val($name) . '</textarea></div>';
}
function f_check($name, $labelHtml, $required = true) {
    $checked = !empty($_POST[$name]) ? ' checked' : '';
    return '<label class="check"><input type="checkbox" name="' . $name . '" value="1"' . $checked . ($required ? ' required' : '') . '><span>' . $labelHtml . '</span></label>';
}
