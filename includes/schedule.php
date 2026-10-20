<?php
/**
 * Programme schedule & calendar, managed in admin → Schedule & reminders.
 *
 * `events` holds programme sessions, public holidays and important days (one-off or repeating
 * weekly / monthly / yearly). `cal_subscribers` are people who asked for reminders (or were
 * added by an admin). Reminders go out by e-mail (PHP mail via mem_send) and by SMS through a
 * generic HTTP gateway configured in admin; every attempt is logged in `cal_reminders`.
 */
require_once __DIR__ . "/membership.php";

function sch_kinds() {
    return ['programme' => 'Programme session', 'important' => 'Important day', 'holiday' => 'Public holiday'];
}

function sch_defaults() {
    return [
        'sch_country_code' => '256',
        'sch_sms_url' => '', 'sch_sms_method' => 'POST', 'sch_sms_body' => 'to={to}&message={message}&from={sender}', 'sch_sms_header' => '', 'sch_sms_sender' => 'NUFA',
        'sch_email_subject' => 'Reminder: {event} — {date}',
        'sch_email_body' => "Hello {name},\n\nThis is a reminder about {event}{programme_line}.\n\nDate: {date}\nTime: {time}\nVenue: {venue}\n{link_line}\n{details}\n\nTo stop receiving reminders, use this link: {unsubscribe}\n\n— NUFA",
        'sch_sms_text' => 'NUFA reminder: {event}, {date} {time}, {venue}. {link}',
        'sch_cron_key' => '',
    ];
}

function sch_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    // first version used generic table names; carry any data over
    foreach (['events' => ['cal_events', 'repeat_rule'], 'event_subscribers' => ['cal_subscribers', 'unsubscribed'], 'event_reminders' => ['cal_reminders', 'occ_date']] as $old => [$new, $marker]) {
        if ($conn->query("SHOW TABLES LIKE '$new'")->num_rows === 0 && $conn->query("SHOW TABLES LIKE '$old'")->num_rows > 0 && $conn->query("SHOW COLUMNS FROM $old LIKE '$marker'")->num_rows > 0) $conn->query("RENAME TABLE $old TO $new");
    }
    $conn->query("CREATE TABLE IF NOT EXISTS cal_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(190) NOT NULL,
        kind VARCHAR(20) NOT NULL DEFAULT 'programme',
        programme_id INT NULL,
        start_date DATE NOT NULL,
        end_date DATE NULL,
        start_time TIME NULL,
        end_time TIME NULL,
        venue VARCHAR(255) NOT NULL DEFAULT '',
        meeting_link VARCHAR(500) NOT NULL DEFAULT '',
        show_link TINYINT(1) NOT NULL DEFAULT 1,
        description TEXT,
        repeat_rule VARCHAR(10) NOT NULL DEFAULT 'none',
        repeat_until DATE NULL,
        auto_remind_days INT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_start (start_date), KEY idx_prog (programme_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS cal_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        programme_id INT NOT NULL DEFAULT 0,
        event_id INT NOT NULL DEFAULT 0,
        name VARCHAR(150) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        phone VARCHAR(40) NOT NULL DEFAULT '',
        want_email TINYINT(1) NOT NULL DEFAULT 1,
        want_sms TINYINT(1) NOT NULL DEFAULT 0,
        unsubscribed TINYINT(1) NOT NULL DEFAULT 0,
        source VARCHAR(20) NOT NULL DEFAULT 'website',
        token CHAR(32) NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_token (token), KEY idx_scope (programme_id, event_id), KEY idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS cal_reminders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        occ_date DATE NOT NULL,
        channel VARCHAR(10) NOT NULL,
        recipient VARCHAR(190) NOT NULL,
        status VARCHAR(12) NOT NULL,
        detail VARCHAR(255) NOT NULL DEFAULT '',
        mode VARCHAR(10) NOT NULL DEFAULT 'manual',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ev (event_id, occ_date, channel, recipient)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // make sure every column exists even if a table was created by an older version
    $need = ['cal_events' => ['kind' => "VARCHAR(20) NOT NULL DEFAULT 'programme'", 'programme_id' => 'INT NULL', 'end_date' => 'DATE NULL', 'start_time' => 'TIME NULL', 'end_time' => 'TIME NULL', 'venue' => "VARCHAR(255) NOT NULL DEFAULT ''", 'meeting_link' => "VARCHAR(500) NOT NULL DEFAULT ''", 'show_link' => 'TINYINT(1) NOT NULL DEFAULT 1', 'description' => 'TEXT', 'repeat_rule' => "VARCHAR(10) NOT NULL DEFAULT 'none'", 'repeat_until' => 'DATE NULL', 'auto_remind_days' => 'INT NULL', 'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1']];
    foreach ($need as $t => $cols) foreach ($cols as $col => $def) if ($conn->query("SHOW COLUMNS FROM $t LIKE '$col'")->num_rows === 0) $conn->query("ALTER TABLE $t ADD COLUMN $col $def");
    sch_seed($conn);
}

/** Seeds Uganda's fixed-date public holidays (yearly) and Good Friday / Easter Monday once; admins edit freely. */
/** Easter Sunday for a year (Gregorian computus). */
function sch_easter($y) {
    $a = $y % 19; $b = intdiv($y, 100); $c = $y % 100; $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $n = $h + $l - 7 * $m + 114;
    return new DateTime(sprintf('%04d-%02d-%02d', $y, intdiv($n, 31), ($n % 31) + 1));
}

function sch_seed($conn) {
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key='sch_seeded'");
    if ($r && $r->num_rows) return;
    $fixed = [['New Year’s Day', '01-01'], ['NRM Liberation Day', '01-26'], ['Archbishop Janani Luwum Day', '02-16'], ['International Women’s Day', '03-08'], ['Labour Day', '05-01'],
              ['Uganda Martyrs’ Day', '06-03'], ['National Heroes’ Day', '06-09'], ['Independence Day', '10-09'], ['Christmas Day', '12-25'], ['Boxing Day', '12-26']];
    $s = $conn->prepare("INSERT INTO cal_events (title, kind, start_date, repeat_rule, description) VALUES (?, 'holiday', ?, 'yearly', 'Public holiday in Uganda.')");
    foreach ($fixed as [$t, $md]) { $d = "2024-$md"; $s->bind_param("ss", $t, $d); $s->execute(); }
    $s2 = $conn->prepare("INSERT INTO cal_events (title, kind, start_date, description) VALUES (?, 'holiday', ?, 'Public holiday in Uganda. Date moves each year.')");
    for ($y = 2026; $y <= 2032; $y++) {   // Easter (Gregorian computus)
        $a = $y % 19; $b = intdiv($y, 100); $c = $y % 100; $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mo = intdiv($h + $l - 7 * $m + 114, 31); $da = (($h + $l - 7 * $m + 114) % 31) + 1;
        $easter = new DateTime(sprintf('%04d-%02d-%02d', $y, $mo, $da));
        foreach ([['Good Friday', '-2 days'], ['Easter Monday', '+1 day']] as [$t, $off]) { $dt = (clone $easter)->modify($off)->format('Y-m-d'); $s2->bind_param("ss", $t, $dt); $s2->execute(); }
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('sch_seeded','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

function sch_settings($conn, $refresh = false) {
    static $c = null;
    if ($c !== null && !$refresh) return $c;
    $c = sch_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sch\\_%'");
    while ($r && ($x = $r->fetch_assoc())) if (array_key_exists($x['setting_key'], $c) && $x['setting_value'] !== '') $c[$x['setting_key']] = $x['setting_value'];
    if ($c['sch_cron_key'] === '') {
        $c['sch_cron_key'] = bin2hex(random_bytes(12));
        $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('sch_cron_key', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $s->bind_param("s", $c['sch_cron_key']); $s->execute();
    }
    return $c;
}

/** All occurrences between two dates (inclusive, Y-m-d) as event rows plus 'date'. Optional programme filter. */
function sch_occurrences($conn, $from, $to, $programmeId = 0, $onlyEvent = 0) {
    $where = "e.is_active=1 AND e.start_date <= '" . $conn->real_escape_string($to) . "'";
    if ($onlyEvent) $where .= " AND e.id=" . (int)$onlyEvent;
    if ($programmeId) $where .= " AND (e.programme_id=" . (int)$programmeId . " OR e.programme_id IS NULL)";
    $rows = $conn->query("SELECT e.*, p.title AS programme_title, p.slug AS programme_slug FROM cal_events e LEFT JOIN programmes p ON p.id=e.programme_id WHERE $where")->fetch_all(MYSQLI_ASSOC);
    $out = [];
    foreach ($rows as $e) {
        $rule = $e['repeat_rule']; $start = new DateTime($e['start_date']);
        $lim = new DateTime($to); if ($e['repeat_until'] && new DateTime($e['repeat_until']) < $lim) $lim = new DateTime($e['repeat_until']);
        $fromD = new DateTime($from);
        if ($rule === 'none') {
            $end = $e['end_date'] ? new DateTime($e['end_date']) : clone $start;
            for ($d = max(clone $start, clone $fromD), $n = 0; $d <= $end && $d <= new DateTime($to) && $n < 400; $d->modify('+1 day'), $n++) $out[] = $e + ['date' => $d->format('Y-m-d')];
            continue;
        }
        $step = ['weekly' => '+1 week', 'monthly' => '+1 month', 'yearly' => '+1 year'][$rule] ?? null; if (!$step) continue;
        $d = clone $start; $i = 0;
        while ($d <= $lim && $i < 1500) {
            if ($d >= $fromD) $out[] = $e + ['date' => $d->format('Y-m-d')];
            $i++; $d = (clone $start)->modify($i . ' ' . ['weekly' => 'week', 'monthly' => 'month', 'yearly' => 'year'][$rule]);
        }
    }
    usort($out, fn($a, $b) => [$a['date'], $a['start_time'] ?? '', $a['title']] <=> [$b['date'], $b['start_time'] ?? '', $b['title']]);
    return $out;
}

function sch_time($o) {
    if (empty($o['start_time'])) return 'All day';
    $t = substr($o['start_time'], 0, 5); if (!empty($o['end_time'])) $t .= '–' . substr($o['end_time'], 0, 5);
    return $t;
}

function sch_phone($raw, $cc = '256') {
    $d = preg_replace('/\D+/', '', (string)$raw);
    if ($d === '') return '';
    if (strpos((string)$raw, '+') === 0) return $d;
    if ($d[0] === '0') return $cc . substr($d, 1);
    return strlen($d) <= 9 ? $cc . $d : $d;
}

function sch_vars($conn, $o, $sub) {
    $S = sch_settings($conn);
    $link = (string)$o['meeting_link'];
    return [
        '{name}' => $sub['name'] !== '' ? $sub['name'] : 'there', '{event}' => $o['title'], '{programme}' => (string)($o['programme_title'] ?? ''),
        '{programme_line}' => !empty($o['programme_title']) ? ' (' . $o['programme_title'] . ')' : '',
        '{date}' => date('l, j F Y', strtotime($o['date'])), '{time}' => sch_time($o), '{venue}' => $o['venue'] !== '' ? $o['venue'] : 'To be confirmed',
        '{link}' => $link, '{link_line}' => $link !== '' ? "Meeting link: $link\n" : '', '{details}' => trim((string)$o['description']),
        '{unsubscribe}' => mem_site_url() . '/schedule.php?unsub=' . ($sub['token'] ?? ''),
    ];
}

function sch_send_sms($conn, $to, $message) {
    $S = sch_settings($conn);
    if (trim($S['sch_sms_url']) === '') return [false, 'No SMS gateway configured'];
    $rep = fn($t, $enc) => strtr($t, ['{to}' => $enc ? rawurlencode($to) : $to, '{message}' => $enc ? rawurlencode($message) : $message, '{sender}' => $enc ? rawurlencode($S['sch_sms_sender']) : $S['sch_sms_sender']]);
    $headers = array_values(array_filter(array_map('trim', preg_split('/\R/', $S['sch_sms_header']))));
    $post = $S['sch_sms_method'] === 'POST';
    $url = $rep($S['sch_sms_url'], true);
    if ($post) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $ctx = stream_context_create(['http' => ['method' => $post ? 'POST' : 'GET', 'header' => implode("\r\n", $headers), 'content' => $post ? $rep($S['sch_sms_body'], true) : '', 'timeout' => 15, 'ignore_errors' => true]]);
    $res = @file_get_contents($url, false, $ctx);
    $code = 0; foreach (($http_response_header ?? []) as $h) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int)$m[1];
    if ($res === false && !$code) return [false, 'Gateway unreachable'];
    return [$code >= 200 && $code < 300, 'HTTP ' . $code];
}

/** Subscribers who should hear about this event (all-programmes, this programme, or this event only). */
function sch_audience($conn, $o) {
    $pid = (int)($o['programme_id'] ?? 0); $eid = (int)$o['id'];
    $r = $conn->query("SELECT * FROM cal_subscribers WHERE unsubscribed=0 AND (event_id=$eid OR (event_id=0 AND programme_id=0)" . ($pid ? " OR (event_id=0 AND programme_id=$pid)" : '') . ")");
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

function sch_log($conn, $eid, $date, $channel, $to, $ok, $detail, $mode) {
    $st = $ok ? 'sent' : 'failed';
    $s = $conn->prepare("INSERT INTO cal_reminders (event_id, occ_date, channel, recipient, status, detail, mode) VALUES (?,?,?,?,?,?,?)");
    $d = mb_substr($detail, 0, 250); $s->bind_param("issssss", $eid, $date, $channel, $to, $st, $d, $mode); $s->execute();
}

/**
 * Sends a reminder for one occurrence. $people: list of ['name','email','phone','token','want_email','want_sms'].
 * Returns ['email' => [sent, failed], 'sms' => [sent, failed]]. In auto mode, anyone already reminded for this occurrence is skipped.
 */
function sch_send($conn, $o, array $people, $viaEmail, $viaSms, $mode = 'manual') {
    $S = sch_settings($conn); $res = ['email' => [0, 0], 'sms' => [0, 0]];
    $already = function ($ch, $to) use ($conn, $o) {
        $s = $conn->prepare("SELECT 1 FROM cal_reminders WHERE event_id=? AND occ_date=? AND channel=? AND recipient=? AND status='sent' LIMIT 1");
        $s->bind_param("isss", $o['id'], $o['date'], $ch, $to); $s->execute(); return $s->get_result()->num_rows > 0;
    };
    $seen = [];
    foreach ($people as $p) {
        $v = sch_vars($conn, $o, $p + ['name' => '', 'token' => '']);
        if ($viaEmail && !empty($p['want_email']) && filter_var($p['email'] ?? '', FILTER_VALIDATE_EMAIL) && empty($seen['e' . strtolower($p['email'])])) {
            $seen['e' . strtolower($p['email'])] = 1;
            if ($mode === 'auto' && $already('email', $p['email'])) continue;
            $ok = mem_send($conn, null, 'reminder', $p['email'], strtr($S['sch_email_subject'], $v), strtr($S['sch_email_body'], $v), $o['meeting_link'] ?: null);
            sch_log($conn, $o['id'], $o['date'], 'email', $p['email'], $ok, $ok ? 'Handed to mail system' : 'Mail system refused', $mode);
            $res['email'][$ok ? 0 : 1]++;
        }
        $ph = sch_phone($p['phone'] ?? '', $S['sch_country_code']);
        if ($viaSms && !empty($p['want_sms']) && $ph !== '' && empty($seen['s' . $ph])) {
            $seen['s' . $ph] = 1;
            if ($mode === 'auto' && $already('sms', $ph)) continue;
            [$ok, $d] = sch_send_sms($conn, $ph, trim(preg_replace('/\s+/', ' ', strtr($S['sch_sms_text'], $v))));
            sch_log($conn, $o['id'], $o['date'], 'sms', $ph, $ok, $d, $mode);
            $res['sms'][$ok ? 0 : 1]++;
        }
    }
    return $res;
}

/** Cron: sends reminders for events whose auto-reminder lead time (days before) is today. */
function sch_run_auto($conn) {
    $tot = ['events' => 0, 'email' => 0, 'sms' => 0];
    $evs = $conn->query("SELECT DISTINCT auto_remind_days d FROM cal_events WHERE is_active=1 AND auto_remind_days IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
    foreach ($evs as $x) {
        $day = date('Y-m-d', strtotime('+' . (int)$x['d'] . ' day'));
        foreach (sch_occurrences($conn, $day, $day) as $o) {
            if ($o['auto_remind_days'] === null || (int)$o['auto_remind_days'] !== (int)$x['d'] || $o['kind'] === 'holiday') continue;
            $r = sch_send($conn, $o, sch_audience($conn, $o), true, true, 'auto');
            $tot['events']++; $tot['email'] += $r['email'][0]; $tot['sms'] += $r['sms'][0];
        }
    }
    return $tot;
}
