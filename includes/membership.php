<?php
/**
 * Membership module — shared helpers.
 *
 * - Creates its own tables on first use (safe to deploy by just uploading files).
 * - Every behaviour (fee, reg-number format, validity, e-mail wording, president
 *   signature, guilds) is stored in the `settings` / `guilds` tables and edited
 *   from admin/membership-settings.php and admin/guilds.php.
 */
require_once __DIR__ . "/db.php";

const MEM_STORAGE_DIR = __DIR__ . "/../storage/members/";

function mem_defaults() {
    return [
        'membership_open'      => '1',
        'membership_fee'       => '50,000',
        'membership_currency'  => 'UGX',
        'payment_instructions' => "Pay the membership fee by Mobile Money or bank deposit, then upload your receipt or a screenshot of the transaction.\n\nMobile Money: 0700 000 000 (NUFA)\nBank: Account name — NUFA, Account number — 0000000000",
        'reg_prefix'           => 'NUFA',
        'reg_format'           => '{PREFIX}/{YEAR}/{SEQ}',
        'reg_pad'              => '4',
        'reg_next'             => '1',
        'validity_months'      => '12',
        'president_name'       => '',
        'president_title'      => 'President, NUFA',
        'president_signature'  => '',
        'card_note'            => 'This card remains the property of NUFA. Valid only for the period shown.',
        'email_from'           => 'info@nufa.media',
        'email_from_name'      => 'NUFA',
        'admin_notify_email'   => 'nuf.association@gmail.com',
        'send_receipt_email'   => '1',
        'notify_admin'         => '1',
        'email_received_subject' => 'We received your NUFA membership application',
        'email_received_body'  => "Hello {name},\n\nThank you for applying to join the Northern Uganda Filmmakers Association ({guild}).\n\nWe have received your application and proof of payment. Our team will review it and you will get another email once it has been approved.\n\n— NUFA Membership Team",
        'email_approved_subject' => 'Welcome to NUFA — your membership is approved',
        'email_approved_body'  => "Hello {name},\n\nCongratulations! Your NUFA membership has been approved.\n\nNUFA registration number: {reg_no}\nGuild: {guild}\nValid from {valid_from} to {valid_until}\n\nYour membership card is ready. Open the link below to view, print or save it:\n{card_link}\n\nWelcome to the association.\n\n— NUFA Membership Team",
        'email_rejected_subject' => 'Your NUFA membership application',
        'email_rejected_body'  => "Hello {name},\n\nThank you for your interest in NUFA. Unfortunately we could not approve your application at this time.\n\nReason: {reason}\n\nYou are welcome to apply again with the corrected details at {site_url}/membership.php.\n\n— NUFA Membership Team",
    ];
}

function mem_install($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    $r = $conn->query("SHOW TABLES LIKE 'members'");
    $exists = $r && $r->num_rows > 0;

    if (!$exists) {
        $conn->query("CREATE TABLE IF NOT EXISTS guilds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $conn->query("CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reg_no VARCHAR(60) NULL UNIQUE,
            card_token CHAR(32) NOT NULL UNIQUE,
            full_name VARCHAR(150) NOT NULL,
            gender VARCHAR(20) NOT NULL DEFAULT '',
            dob DATE NULL,
            phone VARCHAR(40) NOT NULL DEFAULT '',
            email VARCHAR(190) NOT NULL,
            id_number VARCHAR(60) NOT NULL DEFAULT '',
            district VARCHAR(100) NOT NULL DEFAULT '',
            address VARCHAR(255) NOT NULL DEFAULT '',
            occupation VARCHAR(150) NOT NULL DEFAULT '',
            experience VARCHAR(60) NOT NULL DEFAULT '',
            portfolio_url VARCHAR(255) NOT NULL DEFAULT '',
            bio TEXT,
            guild_id INT NULL,
            photo VARCHAR(255) NOT NULL DEFAULT '',
            payment_method VARCHAR(60) NOT NULL DEFAULT '',
            payment_ref VARCHAR(120) NOT NULL DEFAULT '',
            payment_date DATE NULL,
            amount_paid VARCHAR(40) NOT NULL DEFAULT '',
            payment_proof VARCHAR(255) NOT NULL DEFAULT '',
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            reject_reason VARCHAR(500) NOT NULL DEFAULT '',
            valid_from DATE NULL,
            valid_until DATE NULL,
            approved_at DATETIME NULL,
            admin_note VARCHAR(500) NOT NULL DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_email (email),
            KEY idx_guild (guild_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $conn->query("CREATE TABLE IF NOT EXISTS member_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NULL,
            kind VARCHAR(30) NOT NULL,
            to_email VARCHAR(190) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            body MEDIUMTEXT,
            sent TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_member (member_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!$exists) {
        $seed = [
            ['Directors & Producers', 'Film directors, producers and production managers'],
            ['Cinematography & Camera', 'Cinematographers, camera operators and drone pilots'],
            ['Editors & Post-production', 'Editors, colourists and VFX artists'],
            ['Actors & Performers', 'Screen actors, voice artists and presenters'],
            ['Writers & Scriptwriters', 'Screenwriters and story developers'],
            ['Sound & Music', 'Sound engineers, composers and music supervisors'],
            ['Costume, Set & Make-up', 'Costume, set, props and make-up artists'],
            ['Other / Supporting Crew', 'Any other role in film production'],
        ];
        $s = $conn->prepare("INSERT INTO guilds (name, description, sort_order) VALUES (?,?,?)");
        foreach ($seed as $i => $row) { $o = $i + 1; $s->bind_param("ssi", $row[0], $row[1], $o); $s->execute(); }
    }

    $seeded = $conn->query("SELECT 1 FROM settings WHERE setting_key='email_rejected_body'")->num_rows;
    if (!$seeded) {
        $ins = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?,?)");
        foreach (mem_defaults() as $k => $v) { $ins->bind_param("ss", $k, $v); $ins->execute(); }
    }
}

function mem_settings($conn, $refresh = false) {
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = mem_defaults();
        $res = $conn->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $res->fetch_assoc()) {
            if (array_key_exists($row['setting_key'], $cache)) $cache[$row['setting_key']] = (string)$row['setting_value'];
        }
    }
    return $cache;
}

function mem_save_setting($conn, $key, $value) {
    $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $s->bind_param("ss", $key, $value);
    $s->execute();
}

function mem_site_url() {
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    // pages inside /admin report the admin folder; strip it so links point at the site root
    $dir = preg_replace('#/admin$#', '', $dir);
    return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}

function mem_card_link($member) {
    return mem_site_url() . '/card.php?t=' . $member['card_token'];
}

/* ---------- CSRF ---------- */
function mem_csrf() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['mem_csrf'])) $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['mem_csrf'];
}
function mem_csrf_field() { return '<input type="hidden" name="csrf" value="' . mem_csrf() . '">'; }
function mem_csrf_ok() {
    return isset($_POST['csrf'], $_SESSION['mem_csrf']) && hash_equals($_SESSION['mem_csrf'], (string)$_POST['csrf']);
}

/* ---------- uploads ---------- */
function mem_storage_ready() {
    if (!is_dir(MEM_STORAGE_DIR)) @mkdir(MEM_STORAGE_DIR, 0775, true);
    $ht = MEM_STORAGE_DIR . ".htaccess";
    if (!is_file($ht)) @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    $idx = MEM_STORAGE_DIR . "index.html";
    if (!is_file($idx)) @file_put_contents($idx, "");
}

/** Validates and stores an uploaded file. Returns [filename, error]. */
function mem_store_upload($file, array $allowed, $maxBytes, $prefix, $dir = null) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return ['', 'No file selected.'];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['', 'The upload failed (error ' . (int)$file['error'] . '). Try a smaller file.'];
    if ($file['size'] > $maxBytes) return ['', 'File is too large (max ' . round($maxBytes / 1048576) . ' MB).'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) return ['', 'Unsupported file type. Allowed: ' . implode(', ', array_unique(array_values($allowed))) . '.'];
    $name = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if ($dir === null) { mem_storage_ready(); $dir = MEM_STORAGE_DIR; }
    elseif (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return ['', 'Could not save the file on the server.'];
    return [$name, ''];
}
const MEM_IMG_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const MEM_PROOF_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

/* ---------- registration numbers ---------- */
function mem_format_reg($settings, $seq, $year = null) {
    $year = $year ?: date('Y');
    $pad = max(1, (int)$settings['reg_pad']);
    return strtr($settings['reg_format'], [
        '{PREFIX}' => $settings['reg_prefix'],
        '{YEAR}'   => $year,
        '{SEQ}'    => str_pad((string)$seq, $pad, '0', STR_PAD_LEFT),
    ]);
}

/** Atomically takes the next registration number. */
function mem_next_reg_no($conn) {
    $conn->begin_transaction();
    try {
        $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key='reg_next' FOR UPDATE")->fetch_assoc();
        $seq = max(1, (int)($r['setting_value'] ?? 1));
        $settings = mem_settings($conn, true);
        do {
            $reg = mem_format_reg($settings, $seq);
            $exists = $conn->query("SELECT id FROM members WHERE reg_no='" . $conn->real_escape_string($reg) . "'")->num_rows;
            if ($exists) $seq++;
        } while ($exists);
        $next = $seq + 1;
        $u = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='reg_next'");
        $u->bind_param("s", $next);
        $u->execute();
        $conn->commit();
        return $reg;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/* ---------- e-mail ---------- */
function mem_fill($template, array $vars) {
    $map = [];
    foreach ($vars as $k => $v) $map['{' . $k . '}'] = $v;
    return strtr($template, $map);
}

function mem_member_vars($conn, $m, $extra = []) {
    $guild = '';
    if (!empty($m['guild_id'])) {
        $g = $conn->query("SELECT name FROM guilds WHERE id=" . (int)$m['guild_id'])->fetch_assoc();
        $guild = $g['name'] ?? '';
    }
    $fmt = function ($d) { return $d ? date('j F Y', strtotime($d)) : ''; };
    return array_merge([
        'name'        => $m['full_name'],
        'reg_no'      => $m['reg_no'] ?? '',
        'guild'       => $guild,
        'valid_from'  => $fmt($m['valid_from'] ?? null),
        'valid_until' => $fmt($m['valid_until'] ?? null),
        'card_link'   => mem_card_link($m),
        'site_url'    => mem_site_url(),
        'reason'      => $m['reject_reason'] ?? '',
    ], $extra);
}

function mem_email_html($subject, $text, $buttonUrl = null, $buttonLabel = 'View my membership card') {
    $body = nl2br(htmlspecialchars($text));
    $body = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" style="color:#D2650E">$1</a>', $body);
    $btn = $buttonUrl ? '<p style="margin:26px 0 4px"><a href="' . htmlspecialchars($buttonUrl) . '" style="background:#F5821F;color:#fff;text-decoration:none;padding:13px 26px;border-radius:30px;font-weight:bold;display:inline-block">' . htmlspecialchars($buttonLabel) . '</a></p>' : '';
    return '<!doctype html><html><body style="margin:0;background:#F2F1EC;font-family:Arial,Helvetica,sans-serif;color:#211D17">'
        . '<div style="max-width:600px;margin:0 auto;padding:24px 14px"><div style="background:#F5821F;color:#fff;padding:18px 26px;font-size:20px;font-weight:bold">NUFA <span style="font-weight:normal;font-size:13px;opacity:.9">Northern Uganda Filmmakers Association</span></div>'
        . '<div style="background:#fff;padding:28px 26px;line-height:1.65;font-size:15px">' . $body . $btn . '</div>'
        . '<div style="text-align:center;font-size:12px;color:#6B6459;padding:14px">&copy; ' . date('Y') . ' NUFA &middot; Gulu, Northern Uganda</div></div></body></html>';
}

/** Sends (and logs) an e-mail. Returns true when handed to the mail system. */
function mem_send($conn, $memberId, $kind, $to, $subject, $text, $buttonUrl = null, $replyTo = '') {
    $s = mem_settings($conn);
    $from = $s['email_from'] ?: 'info@nufa.media';
    $fromName = $s['email_from_name'] ?: 'NUFA';
    $boundary = 'nufa_' . bin2hex(random_bytes(8));
    $html = mem_email_html($subject, $text, $buttonUrl);
    $headers  = "From: " . mb_encode_mimeheader($fromName) . " <{$from}>\r\n";
    $headers .= "Reply-To: " . ($replyTo ?: $from) . "\r\n";
    $headers .= "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $msg  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . $text . "\r\n\r\n";
    $msg .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n\r\n--{$boundary}--";
    $ok = false;
    try { $ok = @mail($to, mb_encode_mimeheader($subject), $msg, $headers); } catch (Throwable $e) { $ok = false; }

    $log = $conn->prepare("INSERT INTO member_emails (member_id, kind, to_email, subject, body, sent) VALUES (?,?,?,?,?,?)");
    $sent = $ok ? 1 : 0;
    $log->bind_param("issssi", $memberId, $kind, $to, $subject, $text, $sent);
    $log->execute();
    return $ok;
}

function mem_status_badge($status) {
    $map = ['pending' => ['Pending', 'gray'], 'approved' => ['Approved', ''], 'rejected' => ['Rejected', 'red']];
    [$label, $cls] = $map[$status] ?? [ucfirst($status), 'gray'];
    return '<span class="admin-badge ' . $cls . '">' . $label . '</span>';
}

mem_install($conn);
