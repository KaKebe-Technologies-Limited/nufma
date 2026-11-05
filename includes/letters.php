<?php
/**
 * Invitation letters on NUFA headed paper. An admin enters a name, title, event, date (and venue/time); the letter is written
 * automatically, can be edited, printed / saved as PDF, and sent by email or WhatsApp with one click.
 */
require_once __DIR__ . "/membership.php";
require_once __DIR__ . "/settings.php";
require_once __DIR__ . "/whatsapp.php";

function ltr_install($conn) {
    static $done = false; if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS invitation_letters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token CHAR(32) NOT NULL,
        ref VARCHAR(40) NOT NULL DEFAULT '',
        honorific VARCHAR(40) NOT NULL DEFAULT '',
        recipient_name VARCHAR(160) NOT NULL,
        position VARCHAR(200) NOT NULL DEFAULT '',
        organisation VARCHAR(200) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(40) NOT NULL DEFAULT '',
        event_title VARCHAR(250) NOT NULL,
        event_date DATE NULL,
        event_time VARCHAR(60) NOT NULL DEFAULT '',
        venue VARCHAR(250) NOT NULL DEFAULT '',
        purpose TEXT,
        body MEDIUMTEXT,
        letter_date DATE NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'draft',
        email_sent_at DATETIME NULL,
        wa_sent_at DATETIME NULL,
        created_by VARCHAR(120) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_token (token), KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // one-time: drop the "presence and counsel / RSVP" paragraph and the closing line from the saved template and from existing letters
    $fl = $conn->query("SELECT 1 FROM settings WHERE setting_key='ltr_trim_v1'");
    if ($fl && !$fl->num_rows) {
        $conn->query("DELETE FROM settings WHERE setting_key='lt_template' AND setting_value LIKE '%Your presence and counsel%'");
        $rs = $conn->query("SELECT id, body FROM invitation_letters");
        if ($rs) foreach ($rs->fetch_all(MYSQLI_ASSOC) as $r) {
            $b = preg_replace('/\n*Your presence and counsel will be highly valued[^\n]*(\n|$)/u', "\n", (string)$r['body']);
            $b = preg_replace('/\n*Please accept the assurances of our highest regard\.?\s*$/u', '', $b);
            $b = trim(preg_replace("/\n{3,}/", "\n\n", $b));
            if ($b !== trim((string)$r['body'])) { $u = $conn->prepare("UPDATE invitation_letters SET body=? WHERE id=?"); $u->bind_param("si", $b, $r['id']); $u->execute(); }
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('ltr_trim_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
    }
    $c = $conn->query("SHOW COLUMNS FROM invitation_letters LIKE 'batch'");
    if ($c && !$c->num_rows) $conn->query("ALTER TABLE invitation_letters ADD COLUMN batch VARCHAR(30) NOT NULL DEFAULT '', ADD KEY idx_batch (batch)");
}

/** "Hon." / "Dr." / "Prof." … are salutations; anything longer (e.g. "Executive Director") is the person's position. Returns [honorific, position]. */
function ltr_split_title($t) {
    $t = trim((string)$t); if ($t === '') return ['', ''];
    if (preg_match('/^(hon\.?|dr\.?|mr\.?|mrs\.?|ms\.?|miss|prof\.?|professor|eng\.?|rev\.?|rt\.? rev\.?|amb\.?|ambassador|sir|dame|his excellency|her excellency|h\.?e\.?|rt\.? hon\.?|chief|pastor|bishop|sheikh|col\.?|gen\.?|maj\.?|capt\.?|lt\.?|hon\. dr\.?|dr\. hon\.?)$/i', $t)) return [$t, ''];
    return ['', $t];
}

function ltr_defaults() {
    return [
        'lt_ref_prefix' => 'NUFA/INV', 'lt_letterhead' => '', 'lt_show_signature' => '0',
        'lt_signer_name' => '', 'lt_signer_title' => 'President, Northern Uganda Filmmakers Association', 'lt_signature' => '',
        'lt_rsvp' => 'Kindly confirm your attendance by replying to this letter, calling {phone} or emailing {email}.',
        'lt_closing' => 'Please accept the assurances of our highest regard.',
        'lt_template' => "Dear {salutation},\n\nRE: INVITATION TO {EVENT}\n\nOn behalf of the Northern Uganda Filmmakers Association (NUFA), we are pleased to invite you{position_phrase} to {event}, to be held on {date}{time_phrase}{venue_phrase}.\n\n{purpose}",
    ];
}
function ltr_settings($conn) {
    $d = ltr_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'lt\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d) && $x['setting_value'] !== '') $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}

function ltr_next_ref($conn, $S) {
    $y = date('Y'); $prefix = $S['lt_ref_prefix'] . '/' . $y . '/';
    $n = (int)$conn->query("SELECT COUNT(*) FROM invitation_letters WHERE ref LIKE '" . $conn->real_escape_string($prefix) . "%'")->fetch_row()[0] + 1;
    do { $ref = $prefix . str_pad((string)$n, 4, '0', STR_PAD_LEFT); $ex = $conn->query("SELECT 1 FROM invitation_letters WHERE ref='" . $conn->real_escape_string($ref) . "'")->num_rows; $n++; } while ($ex);
    return $ref;
}

/** Writes the letter text from the template and the invitation details. */
function ltr_generate($conn, array $d) {
    $S = ltr_settings($conn); $C = nufa_site_settings($conn);
    $name = trim($d['recipient_name']); $hon = trim($d['honorific'] ?? '');
    $sal = trim($hon . ' ' . $name);
    $pos = trim(($d['position'] ?? '') . (!empty($d['position']) && !empty($d['organisation']) ? ', ' : '') . ($d['organisation'] ?? ''));
    $date = !empty($d['event_date']) ? date('l, j F Y', strtotime($d['event_date'])) : '[date to be confirmed]';
    $rsvp = strtr($S['lt_rsvp'], ['{phone}' => $C['site_phone'] ?? '', '{email}' => $C['site_email'] ?? '']);
    $map = [
        '{salutation}' => $sal, '{name}' => $name, '{event}' => $d['event_title'], '{EVENT}' => mb_strtoupper($d['event_title']), '{date}' => $date,
        '{position_phrase}' => $pos !== '' ? ", in your capacity as $pos," : '',
        '{time_phrase}' => !empty($d['event_time']) ? ' at ' . $d['event_time'] : '',
        '{venue_phrase}' => !empty($d['venue']) ? ' at ' . $d['venue'] : '',
        '{purpose}' => trim((string)($d['purpose'] ?? '')), '{rsvp}' => $rsvp, '{closing}' => $S['lt_closing'],
    ];
    $t = strtr($S['lt_template'], $map);
    return trim(preg_replace("/\n{3,}/", "\n\n", $t));
}

function ltr_link($l) { return mem_site_url() . '/invitation-letter.php?t=' . $l['token']; }

/** The NUFA letterhead picture (logo + name + address block). An admin can upload a replacement in Invitation letters → Letterhead. */
function ltr_letterhead($conn) {
    $S = ltr_settings($conn);
    if ($S['lt_letterhead'] !== '' && is_file(__DIR__ . '/../assets/images/' . $S['lt_letterhead'])) return $S['lt_letterhead'];
    return 'letterhead/nufa-letterhead.png';
}

/** Signature image path (relative to assets/images/) and signer details. */
function ltr_signer($conn) {
    $S = ltr_settings($conn); $M = mem_settings($conn);
    $name = $S['lt_signer_name'] !== '' ? $S['lt_signer_name'] : ($M['president_name'] ?? '');
    $sig = $S['lt_signature'] !== '' ? $S['lt_signature'] : ($M['president_signature'] ?? '');
    $sig = ($sig !== '' && is_file(__DIR__ . '/../assets/images/' . $sig)) ? $sig : '';
    return ['name' => $name, 'title' => $S['lt_signer_title'], 'sig' => $sig];
}

/** Printable A4 headed paper (HTML fragment with its own CSS). $o['prefix'] = path to the site root ('' or '../'). */
function ltr_html($conn, array $l, array $o = []) {
    $C = nufa_site_settings($conn); $sg = ltr_signer($conn); $S = ltr_settings($conn); $pre = $o['prefix'] ?? '';
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $paras = ''; foreach (preg_split('/\n{2,}/', trim((string)$l['body'])) as $p) { $p = trim($p); if ($p === '') continue; $cls = preg_match('/^RE:/i', $p) ? ' class="re"' : ''; $paras .= '<p' . $cls . '>' . nl2br($e($p)) . '</p>'; }
    $to = array_filter([trim(($l['honorific'] ? $l['honorific'] . ' ' : '') . $l['recipient_name']), $l['position'], $l['organisation']]);
    $site = preg_replace('#^https?://#', '', rtrim($C['site_website'] ?? '', '/'));
    ob_start(); ?>
<style>
.ltr-sheet{width:210mm;min-height:297mm;margin:0 auto;background:#fff;color:#1a1a1a;font-family:'Comic Sans MS','Comic Sans','Comic Neue',Chalkboard,cursive,sans-serif;position:relative;box-sizing:border-box;display:flex;flex-direction:column;box-shadow:0 8px 30px rgba(0,0,0,.18)}
.ltr-sheet *{box-sizing:border-box}
.ltr-head{padding:10mm 18mm 4mm;border-bottom:1.2mm solid #D50000;position:relative}.ltr-lh{display:block;width:100%;height:auto}
.ltr-head::after{content:"";position:absolute;left:0;bottom:-3mm;height:.9mm;width:34%;background:#FF7900}
.ltr-org{flex:1;font-family:Montserrat,Arial,sans-serif}.ltr-org b{display:block;white-space:nowrap;font-size:5.4mm;letter-spacing:.4mm;font-weight:900;color:#111;line-height:1.1}.ltr-org span{display:block;font-size:3.1mm;letter-spacing:.7mm;text-transform:uppercase;color:#D50000;font-weight:700;margin-top:1.4mm}.ltr-org i{display:block;font-style:normal;font-size:2.9mm;color:#555;margin-top:1.4mm}
.ltr-contact{flex:none;max-width:54mm;font-family:Arial,sans-serif;font-size:2.7mm;line-height:1.55;color:#333;text-align:right}
.ltr-body{padding:12mm 20mm 6mm;font-size:3.7mm;line-height:1.7;flex:1}
.ltr-meta{display:flex;justify-content:space-between;gap:8mm;font-size:3.4mm;margin-bottom:8mm}.ltr-meta b{font-weight:400}
.ltr-to{margin-bottom:7mm;line-height:1.5}.ltr-to b{font-weight:400}
.ltr-body p{margin:0 0 4.2mm;text-align:justify;text-justify:inter-word;hyphens:auto;-webkit-hyphens:auto}.ltr-body p.re{font-weight:400;margin-top:5mm;letter-spacing:.2mm}
.ltr-sign{margin-top:8mm;font-size:3.8mm}.ltr-sign img{height:17mm;width:auto;max-width:60mm;object-fit:contain;display:block;margin-bottom:-2mm}.ltr-sign .line{width:62mm;border-top:.3mm solid #222;margin:1mm 0 1.5mm}.ltr-sign b{display:block;font-weight:400}.ltr-sign span{color:#555;font-size:3.4mm}
.ltr-foot{background:#111;color:#fff;font-family:Arial,sans-serif;font-size:2.9mm;padding:4mm 18mm;display:flex;justify-content:space-between;gap:6mm;align-items:center;border-top:1.4mm solid #D50000}
.ltr-foot b{color:#FF7900;letter-spacing:.3mm}
@media print{@page{size:A4;margin:0}.ltr-sheet{box-shadow:none;width:210mm;min-height:297mm}body{background:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}
</style>
<div class="ltr-sheet">
  <div class="ltr-head"><img class="ltr-lh" src="<?php echo $e($pre); ?>assets/images/<?php echo $e(ltr_letterhead($conn)); ?>" alt="Northern Uganda Filmmakers Association (NUFA) — Plot 75, Jomo Kenyatta Road, Gulu City, Uganda"></div>
  <div class="ltr-body">
    <div class="ltr-meta"><span><b>Our ref:</b> <?php echo $e($l['ref']); ?></span><span><b>Date:</b> <?php echo $e(date('j F Y', strtotime($l['letter_date'] ?: date('Y-m-d')))); ?></span></div>
    <div class="ltr-to"><?php $first = true; foreach ($to as $line): echo $first ? '<b>' . $e($line) . '</b>' : '<br>' . $e($line); $first = false; endforeach; ?></div>
    <?php echo $paras; ?>
    <?php if ($S['lt_show_signature'] === '1'): ?><div class="ltr-sign"><?php if ($sg['sig']): ?><img src="<?php echo $e($pre); ?>assets/images/<?php echo $e($sg['sig']); ?>" alt="Signature"><?php else: ?><div style="height:15mm"></div><?php endif; ?><div class="line"></div><b><?php echo $e($sg['name']); ?></b><span><?php echo $e($sg['title']); ?></span></div><?php endif; ?>
  </div>
  <div class="ltr-foot"><span><b>NUFA</b> · Northern Uganda Filmmakers Association</span><span><?php echo $e($site); ?></span></div>
</div>
<?php return ob_get_clean();
}

/** Table-based HTML for email clients (headed paper look, inline styles). */
function ltr_email_html($conn, array $l) {
    $C = nufa_site_settings($conn); $sg = ltr_signer($conn); $base = mem_site_url();
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $paras = ''; foreach (preg_split('/\n{2,}/', trim((string)$l['body'])) as $p) { $p = trim($p); if ($p === '') continue; $b = ''; $paras .= '<p style="margin:0 0 14px;line-height:1.7;text-align:justify;' . $b . '">' . nl2br($e($p)) . '</p>'; }
    $to = array_filter([trim(($l['honorific'] ? $l['honorific'] . ' ' : '') . $l['recipient_name']), $l['position'], $l['organisation']]);
    $site = preg_replace('#^https?://#', '', rtrim($C['site_website'] ?? '', '/'));
    $h = '<div style="background:#f2f2f2;padding:20px 8px;font-family:\'Comic Sans MS\',\'Comic Sans\',cursive,sans-serif"><table role="presentation" width="640" align="center" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#fff;border-collapse:collapse;color:#1a1a1a">'
        . '<tr><td style="padding:18px 28px 10px;border-bottom:4px solid #D50000"><img src="' . $e($base) . '/assets/images/' . $e(ltr_letterhead($conn)) . '" width="584" alt="Northern Uganda Filmmakers Association (NUFA)" style="display:block;width:100%;max-width:584px;height:auto"></td></tr>'
        . '<tr><td style="padding:22px 28px 6px;font-size:15px"><table role="presentation" width="100%" style="font-family:Arial,sans-serif;font-size:12px;margin-bottom:18px"><tr><td>Our ref: ' . $e($l['ref']) . '</td><td align="right">Date: ' . $e(date('j F Y', strtotime($l['letter_date'] ?: date('Y-m-d')))) . '</td></tr></table>'
        . '<p style="margin:0 0 18px;line-height:1.5">' . implode('<br>', array_map(fn($x) => $e($x), array_values($to))) . '</p>' . $paras
        . (ltr_settings($conn)['lt_show_signature'] === '1' ? '<div style="margin-top:22px">' . ($sg['sig'] ? '<img src="' . $e($base) . '/assets/images/' . $e($sg['sig']) . '" height="56" alt="Signature" style="display:block">' : '<div style="height:44px"></div>') . '<div style="width:220px;border-top:1px solid #222;margin:3px 0 4px"></div>' . $e($sg['name']) . '<br><span style="color:#555;font-size:13px">' . $e($sg['title']) . '</span></div>' : '')
        . '<p style="text-align:center;margin:26px 0 8px"><a href="' . $e(ltr_link($l)) . '" style="display:inline-block;background:#D50000;color:#fff;text-decoration:none;font-family:Arial,sans-serif;font-weight:bold;font-size:14px;padding:12px 24px;border-radius:30px">View &amp; download the letter (PDF)</a></p></td></tr>'
        . '<tr><td style="background:#111;color:#fff;font-family:Arial,sans-serif;font-size:11px;padding:12px 28px;border-top:3px solid #D50000"><b style="color:#FF7900">NUFA</b> · Northern Uganda Filmmakers Association &nbsp;·&nbsp; ' . $e($site) . '</td></tr></table></div>';
    return $h;
}

/** Sends the letter by email (headed-paper HTML + plain-text copy). Returns true if handed to the mail system. */
function ltr_send_email($conn, array $l) {
    if (!filter_var($l['email'], FILTER_VALIDATE_EMAIL)) return false;
    $M = mem_settings($conn); $from = $M['email_from'] ?: 'info@nufawards.com'; $fromName = $M['email_from_name'] ?: 'NUFA';
    $subject = 'Invitation: ' . $l['event_title'] . ($l['event_date'] ? ' — ' . date('j F Y', strtotime($l['event_date'])) : '');
    $text = $l['body'] . "\n\nView and download the letter: " . ltr_link($l) . "\n\n— Northern Uganda Filmmakers Association";
    $html = ltr_email_html($conn, $l); $b = 'nufa_' . bin2hex(random_bytes(8));
    $hd = "From: " . mb_encode_mimeheader($fromName) . " <$from>\r\nReply-To: $from\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$b\"\r\n";
    $msg = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n$text\r\n\r\n--$b\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n$html\r\n\r\n--$b--";
    $ok = false; try { $ok = @mail($l['email'], mb_encode_mimeheader($subject), $msg, $hd); } catch (Throwable $e) { $ok = false; }
    $lg = $conn->prepare("INSERT INTO member_emails (member_id, kind, to_email, subject, body, sent) VALUES (NULL,'invitation-letter',?,?,?,?)"); $s = $ok ? 1 : 0; $lg->bind_param("sssi", $l['email'], $subject, $text, $s); $lg->execute();
    if ($ok) { $u = $conn->prepare("UPDATE invitation_letters SET email_sent_at=NOW(), status='sent' WHERE id=?"); $u->bind_param("i", $l['id']); $u->execute(); }
    return $ok;
}

/** wa.me link with a short message and the letter link (the recipient opens the headed letter from the link). */
function ltr_wa_url($l) {
    $when = $l['event_date'] ? ' on ' . date('j F Y', strtotime($l['event_date'])) : '';
    $msg = "Dear " . trim(($l['honorific'] ? $l['honorific'] . ' ' : '') . $l['recipient_name']) . ",\n\nThe Northern Uganda Filmmakers Association (NUFA) warmly invites you to " . $l['event_title'] . $when . ($l['venue'] ? ' at ' . $l['venue'] : '') . ".\n\nYour invitation letter is here:\n" . ltr_link($l) . "\n\nThank you.\n— NUFA";
    return nufa_wa_reply_url($l['phone'], $msg);
}
