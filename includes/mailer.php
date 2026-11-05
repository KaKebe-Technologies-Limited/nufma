<?php
/**
 * Outgoing email. Uses the mailbox you set under Admin → Email delivery (SMTP) when it is configured — the reliable way to make
 * sure messages are delivered — and falls back to PHP's mail() otherwise. Drop-in replacement for mail().
 */
function nufa_mail_cfg() {
    global $conn;
    $c = ['smtp_enabled' => '0', 'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => '465', 'smtp_secure' => 'ssl', 'smtp_user' => '', 'smtp_pass' => '', 'smtp_from' => '', 'smtp_from_name' => ''];
    if (isset($conn) && $conn) { $r = @$conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp\\_%'"); if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $c)) $c[$x['setting_key']] = (string)$x['setting_value']; }
    return $c;
}

/** Last delivery error (for the "Send test email" page and logs). */
function nufa_mail_error() { return $GLOBALS['nufa_mail_error'] ?? ''; }

function nufa_smtp_read($fp) { $out = ''; while (($l = fgets($fp, 515)) !== false) { $out .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $out; }
function nufa_smtp_cmd($fp, $cmd, $expect) { if ($cmd !== null) fwrite($fp, $cmd . "\r\n"); $r = nufa_smtp_read($fp); if (strpos($expect, substr($r, 0, 3)) === false) throw new RuntimeException('SMTP said: ' . trim($r) . ($cmd !== null && stripos($cmd, 'AUTH') !== 0 && strlen($cmd) < 80 ? ' (after ' . preg_replace('/^(RCPT TO|MAIL FROM):.*$/i', '$1', $cmd) . ')' : '')); return $r; }

/** Sends through SMTP. Returns true or throws. */
function nufa_smtp_send(array $c, $to, $subject, $message, $headers) {
    $host = $c['smtp_host']; $port = (int)$c['smtp_port'] ?: 465; $sec = $c['smtp_secure'];
    $fp = @stream_socket_client(($sec === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $en, $es, 20, STREAM_CLIENT_CONNECT);
    if (!$fp) throw new RuntimeException("Could not connect to $host:$port ($es). Check the host and port, and that your hosting allows outgoing SMTP.");
    stream_set_timeout($fp, 25);
    try {
        nufa_smtp_cmd($fp, null, '220');
        $me = $_SERVER['SERVER_NAME'] ?? 'localhost';
        nufa_smtp_cmd($fp, "EHLO $me", '250');
        if ($sec === 'tls') { nufa_smtp_cmd($fp, 'STARTTLS', '220'); if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('Could not start TLS.'); nufa_smtp_cmd($fp, "EHLO $me", '250'); }
        nufa_smtp_cmd($fp, 'AUTH LOGIN', '334'); nufa_smtp_cmd($fp, base64_encode($c['smtp_user']), '334');
        try { nufa_smtp_cmd($fp, base64_encode($c['smtp_pass']), '235'); } catch (RuntimeException $e) { throw new RuntimeException('Login failed — check the email address and password of the mailbox. (' . $e->getMessage() . ')'); }
        $from = $c['smtp_from'] !== '' ? $c['smtp_from'] : $c['smtp_user'];
        nufa_smtp_cmd($fp, "MAIL FROM:<$from>", '250');
        nufa_smtp_cmd($fp, "RCPT TO:<" . trim($to) . ">", '250 251');
        nufa_smtp_cmd($fp, 'DATA', '354');
        $msg = "To: $to\r\nSubject: $subject\r\nDate: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(10)) . "@" . preg_replace('/^.*@/', '', $from) . ">\r\n" . $headers . "\r\n\r\n" . $message;
        $msg = preg_replace("/\r\n|\r|\n/", "\r\n", $msg); $msg = preg_replace('/^\./m', '..', $msg);
        fwrite($fp, $msg . "\r\n.\r\n"); nufa_smtp_cmd($fp, null, '250');
        @nufa_smtp_cmd($fp, 'QUIT', '221');
    } finally { @fclose($fp); }
    return true;
}

/** Drop-in for mail(): $to, already-encoded $subject, $message (body incl. MIME parts), $headers (From, Reply-To, MIME…). */
function nufa_mail($to, $subject, $message, $headers = '') {
    $GLOBALS['nufa_mail_error'] = '';
    $c = nufa_mail_cfg();
    if ($c['smtp_enabled'] === '1' && $c['smtp_user'] !== '' && $c['smtp_pass'] !== '') {
        // the From address must be the authenticated mailbox; keep the display name and send replies to the original sender
        $from = $c['smtp_from'] !== '' ? $c['smtp_from'] : $c['smtp_user'];
        $orig = ''; $name = $c['smtp_from_name'];
        if (preg_match('/^From:\s*(.*)$/mi', $headers, $m)) { $orig = $m[1]; if ($name === '' && preg_match('/^(.*?)\s*<[^>]+>/', trim($orig), $n)) $name = trim($n[1]); if (preg_match('/<([^>]+)>/', $orig, $a)) $orig = $a[1]; else $orig = trim($orig); }
        $headers = preg_replace('/^From:.*\R?/mi', '', $headers);
        $fromHdr = ($name !== '' ? (preg_match('/^[\x20-\x7e]*$/', $name) ? '"' . str_replace('"', '', $name) . '"' : mb_encode_mimeheader($name)) . ' ' : '') . "<$from>";
        $headers = "From: $fromHdr\r\n" . (stripos($headers, 'Reply-To:') === false ? "Reply-To: " . ($orig && $orig !== $from ? $orig : $from) . "\r\n" : '') . ltrim($headers);
        try { return nufa_smtp_send($c, $to, $subject, $message, rtrim($headers)); }
        catch (Throwable $e) { $GLOBALS['nufa_mail_error'] = $e->getMessage(); return false; }
    }
    $ok = @mail($to, $subject, $message, $headers);
    if (!$ok) $GLOBALS['nufa_mail_error'] = 'PHP mail() refused the message. Set up SMTP under Admin → Email delivery for reliable sending.';
    return $ok;
}
