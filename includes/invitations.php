<?php
/**
 * NUFA Awards invitations, managed in admin → Award invitations.
 * A campaign holds a PDF invitation template (kept in private storage), a message, and a guest list.
 * Each guest gets an email with the template personalised with their title and name (see PdfStamper),
 * and a link to confirm whether they will attend.
 */
require_once __DIR__ . "/membership.php";
require_once __DIR__ . "/pdfstamp.php";

const INV_STORAGE_DIR = __DIR__ . '/../storage/invitations/';

function inv_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS invite_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(190) NOT NULL,
        edition_id INT NOT NULL DEFAULT 0,
        subject VARCHAR(255) NOT NULL DEFAULT '',
        body TEXT,
        template_file VARCHAR(120) NOT NULL DEFAULT '',
        attach_mode VARCHAR(14) NOT NULL DEFAULT 'personalised',
        layout TEXT,
        event_date_text VARCHAR(190) NOT NULL DEFAULT '',
        venue_text VARCHAR(255) NOT NULL DEFAULT '',
        rsvp_on TINYINT(1) NOT NULL DEFAULT 1,
        rsvp_deadline VARCHAR(120) NOT NULL DEFAULT '',
        reply_to VARCHAR(190) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS invite_guests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        title_text VARCHAR(80) NOT NULL DEFAULT '',
        name VARCHAR(190) NOT NULL,
        org VARCHAR(190) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        note VARCHAR(255) NOT NULL DEFAULT '',
        sent_at DATETIME NULL,
        token CHAR(32) NOT NULL,
        rsvp VARCHAR(5) NOT NULL DEFAULT '',
        rsvp_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_guest (campaign_id, email), UNIQUE KEY uq_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function inv_storage() {
    if (!is_dir(INV_STORAGE_DIR)) @mkdir(INV_STORAGE_DIR, 0775, true);
    if (!is_file(INV_STORAGE_DIR . '.htaccess')) @file_put_contents(INV_STORAGE_DIR . '.htaccess', "Require all denied\nDeny from all\n");
    if (!is_file(INV_STORAGE_DIR . 'index.html')) @file_put_contents(INV_STORAGE_DIR . 'index.html', '');
    return INV_STORAGE_DIR;
}

function inv_default_layout() {
    return ['page' => 1,
        'name' => ['x' => 20, 'y' => 140, 'w' => 170, 'size' => 26, 'align' => 'C', 'color' => '#000000', 'bold' => 1],
        'sub'  => ['on' => 1, 'x' => 20, 'y' => 156, 'w' => 170, 'size' => 15, 'align' => 'C', 'color' => '#444444', 'bold' => 0]];
}
function inv_layout($camp) {
    $d = inv_default_layout(); $l = json_decode((string)($camp['layout'] ?? ''), true);
    if (!is_array($l)) return $d;
    foreach (['name', 'sub'] as $k) $d[$k] = array_merge($d[$k], (array)($l[$k] ?? []));
    $d['page'] = max(1, (int)($l['page'] ?? 1));
    return $d;
}

function inv_vars($camp, $g) {
    $full = trim(($g['title_text'] !== '' ? $g['title_text'] . ' ' : '') . $g['name']);
    return ['{name}' => $g['name'], '{title}' => $g['title_text'], '{full_name}' => $full, '{org}' => $g['org'],
            '{event}' => $camp['title'], '{date}' => $camp['event_date_text'], '{venue}' => $camp['venue_text'], '{rsvp_deadline}' => $camp['rsvp_deadline'],
            '{rsvp_link}' => inv_rsvp_url($g)];
}
function inv_rsvp_url($g) { return mem_site_url() . '/rsvp.php?t=' . ($g['token'] ?? ''); }

/** Personalised PDF bytes for one guest. Returns [bytes|null, note]. */
function inv_pdf($camp, $g) {
    if ($camp['template_file'] === '' || !is_file(inv_storage() . basename($camp['template_file']))) return [null, 'No template uploaded.'];
    $raw = file_get_contents(inv_storage() . basename($camp['template_file']));
    if ($camp['attach_mode'] !== 'personalised') return [$raw, $camp['attach_mode'] === 'plain' ? 'Template attached unchanged.' : ''];
    $L = inv_layout($camp);
    $full = trim(($g['title_text'] !== '' ? $g['title_text'] . ' ' : '') . $g['name']);
    $items = [array_merge($L['name'], ['text' => $full])];
    if (!empty($L['sub']['on']) && trim($g['org']) !== '') $items[] = array_merge($L['sub'], ['text' => $g['org']]);
    try { $s = new PdfStamper($raw); return [$s->stamp($items, $L['page'] - 1), '']; }
    catch (Throwable $e) { return [$raw, 'Could not personalise the PDF (' . $e->getMessage() . ') — the template was attached unchanged.']; }
}

function inv_email_html($camp, $g) {
    $e = fn($v) => htmlspecialchars((string)$v); $site = mem_site_url();
    $body = strtr((string)$camp['body'], inv_vars($camp, $g));
    $body = nl2br($e($body));
    $btn = fn($url, $label, $bg, $fg) => '<a href="' . $e($url) . '" style="display:inline-block;background:' . $bg . ';color:' . $fg . ';text-decoration:none;padding:12px 24px;border-radius:30px;font-weight:bold;font-size:14px;margin:4px 8px 4px 0">' . $e($label) . '</a>';
    $rsvp = $camp['rsvp_on'] ? '<p style="margin:22px 0 4px"><b>Will you attend?</b>' . ($camp['rsvp_deadline'] !== '' ? ' Please reply by ' . $e($camp['rsvp_deadline']) . '.' : '') . '</p><p style="margin:6px 0 0">' . $btn(inv_rsvp_url($g) . '&a=yes', 'Yes, I will attend', '#C8A03C', '#161616') . $btn(inv_rsvp_url($g) . '&a=no', 'Sorry, I cannot', '#161616', '#ffffff') . '</p>' : '';
    $facts = '';
    foreach (['Date' => $camp['event_date_text'], 'Venue' => $camp['venue_text']] as $k => $v) if (trim($v) !== '') $facts .= '<tr><td style="padding:4px 14px 4px 0;color:#6B6459">' . $k . '</td><td style="padding:4px 0"><b>' . $e($v) . '</b></td></tr>';
    return '<!doctype html><html><body style="margin:0;background:#f2f1ec;font-family:Arial,Helvetica,sans-serif;color:#211d17"><div style="max-width:620px;margin:0 auto;padding:22px 12px">'
        . '<div style="background:#161616;color:#fff;padding:22px 26px;border-bottom:4px solid #C8A03C"><div style="font-size:12px;color:#C8A03C;letter-spacing:.14em;text-transform:uppercase">Northern Uganda Filmmakers Association</div><div style="font-size:22px;font-weight:bold;margin-top:4px">' . $e($camp['title']) . '</div></div>'
        . '<div style="background:#fff;padding:28px 26px;line-height:1.65;font-size:15px">' . $body . ($facts !== '' ? '<table style="margin:18px 0 0;font-size:15px">' . $facts . '</table>' : '') . $rsvp . '</div>'
        . '<div style="text-align:center;font-size:12px;color:#6B6459;padding:16px">Your personal invitation is attached to this email.<br>&copy; ' . date('Y') . ' NUFA &middot; Gulu, Northern Uganda &middot; <a href="' . $e($site) . '" style="color:#6B6459">' . $e(preg_replace('#^https?://#', '', $site)) . '</a></div></div></body></html>';
}
function inv_email_text($camp, $g) {
    $t = strtr((string)$camp['body'], inv_vars($camp, $g));
    if (trim($camp['event_date_text']) !== '') $t .= "\n\nDate: " . $camp['event_date_text'];
    if (trim($camp['venue_text']) !== '') $t .= "\nVenue: " . $camp['venue_text'];
    if ($camp['rsvp_on']) $t .= "\n\nPlease tell us if you will attend: " . inv_rsvp_url($g);
    return $t . "\n\nYour invitation is attached.";
}

/** Sends the invitation email (with the PDF attached) and logs it. Returns [ok, note]. */
function inv_mail($conn, $camp, $g, $toOverride = null) {
    $s = mem_settings($conn);
    $from = $s['email_from'] ?: 'info@nufa.media'; $fromName = $s['email_from_name'] ?: 'NUFA';
    $to = $toOverride ?: $g['email'];
    $subject = strtr($camp['subject'] !== '' ? $camp['subject'] : 'You are invited: {event}', inv_vars($camp, $g));
    [$pdf, $note] = inv_pdf($camp, $g);
    $b1 = 'nufa_m_' . bin2hex(random_bytes(6)); $b2 = 'nufa_a_' . bin2hex(random_bytes(6));
    $fname = 'NUFA-Awards-Invitation-' . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $g['name']), '-') . '.pdf';
    $headers = "From: " . mb_encode_mimeheader($fromName) . " <{$from}>\r\nReply-To: " . ($camp['reply_to'] !== '' ? $camp['reply_to'] : $from) . "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"{$b1}\"\r\n";
    $msg = "--{$b1}\r\nContent-Type: multipart/alternative; boundary=\"{$b2}\"\r\n\r\n"
         . "--{$b2}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . inv_email_text($camp, $g) . "\r\n\r\n"
         . "--{$b2}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . inv_email_html($camp, $g) . "\r\n\r\n--{$b2}--\r\n";
    if ($pdf !== null && $camp['attach_mode'] !== 'none') $msg .= "--{$b1}\r\nContent-Type: application/pdf; name=\"{$fname}\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$fname}\"\r\n\r\n" . chunk_split(base64_encode($pdf)) . "\r\n";
    $msg .= "--{$b1}--";
    $ok = false; try { $ok = @mail($to, mb_encode_mimeheader($subject), $msg, $headers); } catch (Throwable $e) { $ok = false; }
    $l = $conn->prepare("INSERT INTO member_emails (member_id, kind, to_email, subject, body, sent) VALUES (NULL, 'invitation', ?, ?, ?, ?)"); $sent = $ok ? 1 : 0; $short = mb_substr(inv_email_text($camp, $g), 0, 500);
    $l->bind_param("sssi", $to, $subject, $short, $sent); $l->execute();
    return [$ok, $ok ? $note : trim('Mail system refused. ' . $note)];
}

/** Sends to up to $limit pending/failed guests. Returns [sent, failed, remaining]. */
function inv_send_batch($conn, $camp, $limit = 25, array $ids = []) {
    $cid = (int)$camp['id'];
    $where = $ids ? "id IN (" . implode(',', array_map('intval', $ids)) . ")" : "status IN ('pending')";
    $rows = $conn->query("SELECT * FROM invite_guests WHERE campaign_id=$cid AND $where ORDER BY id LIMIT " . (int)$limit)->fetch_all(MYSQLI_ASSOC);
    $sent = $failed = 0;
    foreach ($rows as $g) {
        [$ok, $note] = inv_mail($conn, $camp, $g);
        $st = $ok ? 'sent' : 'failed'; $n = mb_substr($note, 0, 250);
        $u = $conn->prepare("UPDATE invite_guests SET status=?, note=?, sent_at=IF(?='sent', NOW(), sent_at) WHERE id=?"); $u->bind_param("sssi", $st, $n, $st, $g['id']); $u->execute();
        $ok ? $sent++ : $failed++;
    }
    $rem = (int)$conn->query("SELECT COUNT(*) FROM invite_guests WHERE campaign_id=$cid AND status='pending'")->fetch_row()[0];
    return [$sent, $failed, $rem];
}

/** Parses pasted/CSV lines "Title | Name | Email | Organisation" (| , ; or tab). Returns [rows, skipped]. */
function inv_parse_guests($text) {
    $rows = []; $skipped = 0;
    foreach (preg_split('/\R/', (string)$text) as $line) {
        $line = trim($line); if ($line === '') continue;
        $p = array_map('trim', preg_split('/\s*[|;\t]\s*|\s*,\s*(?=(?:[^"]*"[^"]*")*[^"]*$)/', $line));
        $p = array_map(fn($v) => trim($v, "\" "), $p);
        $mail = -1; foreach ($p as $i => $v) if (filter_var($v, FILTER_VALIDATE_EMAIL)) { $mail = $i; break; }
        if ($mail < 0) { if (!preg_match('/^(title|name|email)/i', $line)) $skipped++; continue; }
        $email = strtolower($p[$mail]); $rest = array_values(array_filter(array_diff_key($p, [$mail => 1]), fn($v) => $v !== ''));
        // order: Title | Name | Org (title optional when it looks like an honorific)
        $title = ''; $name = ''; $org = '';
        if (count($rest) >= 3) { [$title, $name, $org] = $rest; }
        elseif (count($rest) === 2) { if (preg_match('/^(Hon\.?|Dr\.?|Prof\.?|Mr\.?|Mrs\.?|Ms\.?|Miss|Eng\.?|Amb\.?|H\.?E\.?|Sir|Dame|Rev\.?|Fr\.?|Sheikh|Chief|Hajji|Hajjat|Justice|Col\.?|Maj\.?|Gen\.?)$/i', $rest[0])) { [$title, $name] = $rest; } else { [$name, $org] = $rest; } }
        elseif (count($rest) === 1) $name = $rest[0];
        if ($name === '') { $skipped++; continue; }
        $rows[] = ['title' => mb_substr($title, 0, 80), 'name' => mb_substr($name, 0, 190), 'org' => mb_substr($org, 0, 190), 'email' => $email];
    }
    return [$rows, $skipped];
}

function inv_add_guests($conn, $cid, array $rows) {
    $s = $conn->prepare("INSERT IGNORE INTO invite_guests (campaign_id, title_text, name, org, email, token) VALUES (?,?,?,?,?,?)"); $added = 0;
    foreach ($rows as $r) { $tok = bin2hex(random_bytes(16)); $s->bind_param("isssss", $cid, $r['title'], $r['name'], $r['org'], $r['email'], $tok); $s->execute(); $added += $s->affected_rows > 0 ? 1 : 0; }
    return $added;
}
