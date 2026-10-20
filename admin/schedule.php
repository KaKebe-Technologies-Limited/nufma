<?php
$adminTitle = "Schedule & reminders";
$adminNav = "schedule";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/schedule.php";
require_once __DIR__ . "/../includes/ui.php";
sch_install($conn);

$tab = in_array($_GET['tab'] ?? '', ['events', 'remind', 'subscribers', 'settings'], true) ? $_GET['tab'] : 'events';
$msg = ''; $err = '';
$e = fn($v) => htmlspecialchars((string)$v);
$progs = $conn->query("SELECT id, title FROM programmes ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$kinds = sch_kinds();
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$validDate = fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$d) && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    $p = fn($k, $m = 255) => mb_substr(trim($_POST[$k] ?? ''), 0, $m);
    if ($act === 'event_save' || $act === 'event_add') {
        $tab = 'events';
        $title = $p('title', 190); $kind = isset($kinds[$_POST['kind'] ?? '']) ? $_POST['kind'] : 'programme';
        $pid = (int)($_POST['programme_id'] ?? 0) ?: null; $sd = $p('start_date', 10); $ed = $p('end_date', 10) ?: null;
        $st = $p('start_time', 5) ?: null; $et = $p('end_time', 5) ?: null; $rule = in_array($_POST['repeat_rule'] ?? '', ['none', 'weekly', 'monthly', 'yearly'], true) ? $_POST['repeat_rule'] : 'none';
        $until = $p('repeat_until', 10) ?: null; $link = $p('meeting_link', 500); $remind = ($_POST['auto_remind_days'] ?? '') === '' ? null : max(0, min(60, (int)$_POST['auto_remind_days']));
        if ($title === '') $err = "Give the event a title.";
        elseif (!$validDate($sd)) $err = "Choose a valid start date.";
        elseif ($ed && (!$validDate($ed) || $ed < $sd)) $err = "The end date must be on or after the start date.";
        elseif ($until && !$validDate($until)) $err = "Choose a valid 'repeat until' date.";
        elseif ($link !== '' && !preg_match('#^https?://#i', $link)) $err = "The meeting link must start with https://";
        elseif ($st && $et && $et < $st) $err = "The end time must be after the start time.";
        else {
            $v = [$title, $kind, $pid, $sd, $ed, $st, $et, $p('venue'), $link, isset($_POST['show_link']) ? 1 : 0, mb_substr(trim($_POST['description'] ?? ''), 0, 3000), $rule, $until, $remind, isset($_POST['is_active']) ? 1 : 0];
            if ($act === 'event_add') { $s = $conn->prepare("INSERT INTO cal_events (title, kind, programme_id, start_date, end_date, start_time, end_time, venue, meeting_link, show_link, description, repeat_rule, repeat_until, auto_remind_days, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("ssissssssisssii", ...$v); }
            else { $s = $conn->prepare("UPDATE cal_events SET title=?, kind=?, programme_id=?, start_date=?, end_date=?, start_time=?, end_time=?, venue=?, meeting_link=?, show_link=?, description=?, repeat_rule=?, repeat_until=?, auto_remind_days=?, is_active=? WHERE id=?"); $v[] = $id; $s->bind_param("ssissssssisssiii", ...$v); }
            $s->execute(); $msg = $act === 'event_add' ? "Event added." : "Event saved."; $_GET['edit'] = 0;
        }
        if ($err) $_GET['edit'] = $act === 'event_save' ? $id : 0;
    } elseif ($act === 'holidays_year') {
        $tab = 'events'; $y = (int)($_POST['year'] ?? 0); $added = 0;
        if ($y < 2024 || $y > 2060) $err = "Choose a year between 2024 and 2060.";
        else {
            $easter = sch_easter($y);
            foreach ([['Good Friday', '-2 days'], ['Easter Monday', '+1 day']] as [$t, $off]) {
                $dt = (clone $easter)->modify($off)->format('Y-m-d');
                $ex = $conn->prepare("SELECT 1 FROM cal_events WHERE title=? AND start_date=?"); $ex->bind_param('ss', $t, $dt); $ex->execute();
                if (!$ex->get_result()->num_rows) { $s = $conn->prepare("INSERT INTO cal_events (title, kind, start_date, description) VALUES (?, 'holiday', ?, 'Public holiday in Uganda. Date moves each year.')"); $s->bind_param('ss', $t, $dt); $s->execute(); $added++; }
            }
            $msg = "Good Friday and Easter Monday for $y: $added added. Add the Eid holidays as the dates are announced.";
        }
    } elseif ($act === 'event_delete') { $tab = 'events'; $conn->query("DELETE FROM cal_events WHERE id=$id"); $conn->query("DELETE FROM cal_reminders WHERE event_id=$id"); $msg = "Event deleted."; }
    elseif ($act === 'sub_add') {
        $tab = 'subscribers'; $name = $p('name', 150); $email = strtolower($p('email', 190)); $phone = $p('phone', 40); $pid = (int)($_POST['programme_id'] ?? 0); $wE = isset($_POST['want_email']) ? 1 : 0; $wS = isset($_POST['want_sms']) ? 1 : 0;
        if ($name === '' || (!$wE && !$wS)) $err = "Enter a name and choose email and/or SMS.";
        elseif ($wE && !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = "Enter a valid email address.";
        elseif ($wS && strlen(preg_replace('/\D/', '', $phone)) < 9) $err = "Enter a valid phone number for SMS.";
        else { $tok = bin2hex(random_bytes(16)); $s = $conn->prepare("INSERT INTO cal_subscribers (programme_id, name, email, phone, want_email, want_sms, source, token) VALUES (?,?,?,?,?,?,'admin',?)"); $s->bind_param("isssiis", $pid, $name, $email, $phone, $wE, $wS, $tok); $s->execute(); $msg = "Recipient added."; }
    } elseif ($act === 'sub_delete') { $tab = 'subscribers'; $conn->query("DELETE FROM cal_subscribers WHERE id=$id"); $msg = "Recipient removed."; }
    elseif ($act === 'sub_toggle') { $tab = 'subscribers'; $conn->query("UPDATE cal_subscribers SET unsubscribed = 1 - unsubscribed WHERE id=$id"); }
    elseif ($act === 'settings') {
        $tab = 'settings';
        foreach (['sch_country_code', 'sch_sms_url', 'sch_sms_body', 'sch_sms_header', 'sch_sms_sender', 'sch_email_subject', 'sch_email_body', 'sch_sms_text'] as $k) $save($k, mb_substr(trim($_POST[$k] ?? ''), 0, 3000));
        $save('sch_sms_method', ($_POST['sch_sms_method'] ?? '') === 'GET' ? 'GET' : 'POST');
        if (!empty($_POST['sch_cron_new'])) $save('sch_cron_key', bin2hex(random_bytes(12)));
        $msg = "Settings saved.";
        if (!empty($_POST['test_phone'])) { [$ok, $d] = sch_send_sms($conn, sch_phone($_POST['test_phone'], trim($_POST['sch_country_code'] ?? '256')), 'NUFA test message'); $msg .= $ok ? " Test SMS accepted by the gateway ($d)." : " Test SMS failed: $d."; }
    } elseif ($act === 'send') {
        $tab = 'remind'; $eid = (int)($_POST['event_id'] ?? 0); $date = $p('occ_date', 10);
        $occ = array_values(array_filter(sch_occurrences($conn, $date ?: '1970-01-01', $date ?: '1970-01-01', 0, $eid), fn($o) => $o['date'] === $date));
        if (!$occ) $err = "Choose an event and one of its dates.";
        else {
            $o = $occ[0]; $people = [];
            if (!empty($_POST['aud_subs'])) $people = sch_audience($conn, $o);
            foreach (preg_split('/\R/', (string)($_POST['extra'] ?? '')) as $line) {   // name, email, phone
                $c = array_map('trim', explode(',', $line)); if (count($c) < 2 || ($c[0] === '' && $c[1] === '')) continue;
                $em = filter_var($c[1] ?? '', FILTER_VALIDATE_EMAIL) ? $c[1] : ''; $ph = $c[2] ?? ($em === '' ? $c[1] : '');
                $people[] = ['name' => $c[0], 'email' => $em, 'phone' => $ph, 'want_email' => $em !== '' ? 1 : 0, 'want_sms' => $ph !== '' ? 1 : 0, 'token' => ''];
            }
            if (!empty($_POST['aud_members']) && $_POST['aud_members'] === 'all') {
                $r = $conn->query("SELECT full_name, email, phone FROM members WHERE status='approved'");
                while ($m = $r->fetch_assoc()) $people[] = ['name' => $m['full_name'], 'email' => $m['email'], 'phone' => $m['phone'], 'want_email' => 1, 'want_sms' => 1, 'token' => ''];
            }
            $vE = !empty($_POST['via_email']); $vS = !empty($_POST['via_sms']);
            if (!$people) $err = "No recipients: tick the subscribers box, approved members, or add people below.";
            elseif (!$vE && !$vS) $err = "Choose email, SMS or both.";
            else { $r = sch_send($conn, $o, $people, $vE, $vS); $msg = "Reminder processed — email: {$r['email'][0]} handed to the mail system, {$r['email'][1]} failed; SMS: {$r['sms'][0]} accepted, {$r['sms'][1]} failed."; }
        }
    }
}
$S = sch_settings($conn, true);
include __DIR__ . "/includes/admin-header.php";
$tabs = ['events' => 'Events & holidays', 'remind' => 'Send a reminder', 'subscribers' => 'Reminder recipients', 'settings' => 'Email & SMS settings'];
?>
<div class="mem-tabs"><?php foreach ($tabs as $t => $l): ?><a href="schedule.php?tab=<?php echo $t; ?>" class="<?php echo $tab === $t ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?>
  <a href="page-editor.php?page=schedule">Page text &amp; sections ↗</a><a href="greetings.php">Holiday greetings</a><a href="../schedule.php" target="_blank">View calendar ↗</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'events'):
    $edit = (int)($_GET['edit'] ?? 0); $cur = $edit ? $conn->query("SELECT * FROM cal_events WHERE id=$edit")->fetch_assoc() : null;
    $f = $_SERVER['REQUEST_METHOD'] === 'POST' && $err ? $_POST : ($cur ?: (preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? ['start_date' => $_GET['date']] : []));
    $v = fn($k, $d = '') => $e($f[$k] ?? $d);
    $filter = in_array($_GET['kind'] ?? '', array_keys($kinds), true) ? $_GET['kind'] : '';
    $yr = (int)($_GET['year'] ?? date('Y'));
    $occ = sch_occurrences($conn, "$yr-01-01", "$yr-12-31");
    $all = $conn->query("SELECT e.*, p.title ptitle FROM cal_events e LEFT JOIN programmes p ON p.id=e.programme_id " . ($filter ? "WHERE e.kind='$filter'" : '') . " ORDER BY e.start_date DESC, e.id DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC);
?>
<?php
    $am = preg_match('/^\d{4}-\d{2}$/', $_GET['am'] ?? '') ? $_GET['am'] : date('Y-m');
    $af = new DateTime("$am-01"); $al = (clone $af)->modify('last day of this month'); $am = $af->format('Y-m');
    $aby = []; foreach (sch_occurrences($conn, $af->format('Y-m-d'), $al->format('Y-m-d')) as $o) $aby[$o['date']][] = $o;
    $acls = ['programme' => 'cal-ev--prog', 'important' => 'cal-ev--imp', 'holiday' => 'cal-ev--hol'];
?>
  <div class="admin-card">
    <div class="admin-card-head"><h2><?php echo $af->format('F Y'); ?></h2><span>
      <a class="admin-btn admin-btn-sm" href="schedule.php?am=<?php echo (clone $af)->modify('-1 month')->format('Y-m'); ?>">‹ Prev</a>
      <a class="admin-btn admin-btn-sm" href="schedule.php">Today</a>
      <a class="admin-btn admin-btn-sm" href="schedule.php?am=<?php echo (clone $af)->modify('+1 month')->format('Y-m'); ?>">Next ›</a></span></div>
    <p class="admin-help" style="margin-bottom:10px">Click a day to add an activity on it, or click an item to edit it. This is the same calendar visitors see on the website.</p>
    <div class="cal admin-cal">
      <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dn) echo '<div class="cal-h">' . $dn . '</div>';
      for ($i = 0, $lead = (int)$af->format('N') - 1; $i < $lead; $i++) echo '<div class="cal-d cal-d--empty"></div>';
      for ($d = 1, $n = (int)$al->format('j'); $d <= $n; $d++): $ds = sprintf('%s-%02d', $am, $d); ?>
        <div class="cal-d<?php echo $ds === date('Y-m-d') ? ' cal-d--today' : ''; ?>">
          <a class="cal-n" href="schedule.php?am=<?php echo $am; ?>&date=<?php echo $ds; ?>#event-form" title="Add an activity on <?php echo $ds; ?>"><?php echo $d; ?></a>
          <?php foreach (($aby[$ds] ?? []) as $o): ?><a class="cal-ev <?php echo $acls[$o['kind']] ?? ''; ?>" href="schedule.php?edit=<?php echo $o['id']; ?>&am=<?php echo $am; ?>#event-form" title="<?php echo $e($o['title']); ?>"><?php echo $e($o['title']); ?></a><?php endforeach; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
  <form method="post" class="admin-card admin-form" id="event-form">
    <div class="admin-card-head"><h2><?php echo $cur ? 'Edit event' : 'Add an event'; ?></h2><span class="admin-help">Programme sessions, important days and public holidays — one-off or repeating for the coming years.</span></div>
    <input type="hidden" name="action" value="<?php echo $cur ? 'event_save' : 'event_add'; ?>"><input type="hidden" name="id" value="<?php echo (int)($cur['id'] ?? 0); ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Title</label><input name="title" value="<?php echo $v('title'); ?>" required></div>
      <div class="field"><label>Type</label><select name="kind"><?php foreach ($kinds as $k => $l) echo '<option value="' . $k . '"' . (($f['kind'] ?? 'programme') === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
      <div class="field"><label>Programme (optional)</label><select name="programme_id"><option value="0">— None / general —</option><?php foreach ($progs as $pr) echo '<option value="' . $pr['id'] . '"' . ((int)($f['programme_id'] ?? 0) === (int)$pr['id'] ? ' selected' : '') . '>' . $e($pr['title']) . '</option>'; ?></select></div>
      <div class="field"><label>Venue</label><input name="venue" value="<?php echo $v('venue'); ?>" placeholder="e.g. NUFA hall, Gulu"></div>
      <div class="field"><label>Start date</label><input type="date" name="start_date" value="<?php echo $v('start_date'); ?>" required></div>
      <div class="field"><label>End date (multi-day, optional)</label><input type="date" name="end_date" value="<?php echo $v('end_date'); ?>"></div>
      <div class="field"><label>Start time</label><input type="time" name="start_time" value="<?php echo $e(substr((string)($f['start_time'] ?? ''), 0, 5)); ?>"></div>
      <div class="field"><label>End time</label><input type="time" name="end_time" value="<?php echo $e(substr((string)($f['end_time'] ?? ''), 0, 5)); ?>"></div>
      <div class="field"><label>Meeting link (Zoom, Meet, Teams…)</label><input type="url" name="meeting_link" value="<?php echo $v('meeting_link'); ?>" placeholder="https://"></div>
    </div>
    <details style="margin:6px 0 14px"><summary style="cursor:pointer;font-weight:700">Repeat &amp; automatic reminder options</summary><div class="admin-form-grid" style="margin-top:10px">
      <div class="field"><label>Repeats</label><select name="repeat_rule"><?php foreach (['none' => 'Does not repeat', 'weekly' => 'Every week', 'monthly' => 'Every month', 'yearly' => 'Every year'] as $k => $l) echo '<option value="' . $k . '"' . (($f['repeat_rule'] ?? 'none') === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
      <div class="field"><label>Repeat until (blank = keeps going)</label><input type="date" name="repeat_until" value="<?php echo $v('repeat_until'); ?>"></div>
      <div class="field"><label>Auto-reminder (days before; blank = none)</label><input type="number" min="0" max="60" name="auto_remind_days" value="<?php echo $v('auto_remind_days'); ?>"><span class="admin-help">Sent daily by the scheduled task (see Email &amp; SMS settings).</span></div>
    </div></details>
    <div class="field"><label>Details (shown on the calendar and in reminders)</label><textarea name="description" style="min-height:80px"><?php echo $v('description'); ?></textarea></div>
    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
      <label class="mem-check" style="margin:0"><input type="checkbox" name="show_link" value="1" <?php echo (!isset($f['show_link']) || !empty($f['show_link'])) ? 'checked' : ''; ?>> Show the meeting link on the public calendar</label>
      <label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo (!$cur && !$_POST) || !empty($f['is_active']) ? 'checked' : ''; ?>> Visible</label>
      <button class="admin-btn admin-btn-brand"><?php echo $cur ? 'Save event' : 'Add event'; ?></button>
      <?php if ($cur): ?><a class="admin-btn" href="schedule.php">Cancel</a><button name="action" value="event_delete" class="admin-btn admin-btn-danger" onclick="return confirm('Delete this event?')">Delete</button><?php endif; ?>
    </div>
  </form>

  <div class="admin-card">
    <div class="admin-card-head"><h2>All events</h2><span><?php foreach (['' => 'All'] + $kinds as $k => $l): ?><a class="admin-btn admin-btn-sm <?php echo $filter === $k ? 'admin-btn-brand' : ''; ?>" href="schedule.php?kind=<?php echo $k; ?>"><?php echo $l; ?></a> <?php endforeach; ?></span></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Event</th><th>Type</th><th>Programme</th><th>Repeats</th><th>Link</th><th></th></tr></thead><tbody>
    <?php foreach ($all as $x): ?><tr<?php echo $x['is_active'] ? '' : ' style="opacity:.5"'; ?>>
      <td><?php echo $e(date('d M Y', strtotime($x['start_date']))); ?><?php echo $x['end_date'] ? ' → ' . $e(date('d M', strtotime($x['end_date']))) : ''; ?><br><small><?php echo $e(sch_time($x)); ?></small></td>
      <td><b><?php echo $e($x['title']); ?></b><br><small><?php echo $e($x['venue']); ?></small></td>
      <td><?php echo $e($kinds[$x['kind']] ?? $x['kind']); ?></td><td><?php echo $e($x['ptitle'] ?? '—'); ?></td>
      <td><?php echo $e($x['repeat_rule'] === 'none' ? '—' : ucfirst($x['repeat_rule']) . ($x['repeat_until'] ? ' until ' . $x['repeat_until'] : '')); ?></td>
      <td><?php echo $x['meeting_link'] ? '✔' : ''; ?></td>
      <td><a class="admin-btn admin-btn-sm" href="schedule.php?edit=<?php echo $x['id']; ?>#event-form">Edit</a></td></tr>
    <?php endforeach; if (!$all): ?><tr><td colspan="7">No events yet.</td></tr><?php endif; ?></tbody></table></div>
    <form method="post" style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap"><input type="hidden" name="action" value="holidays_year"><b>Add Easter holidays for a year:</b><input type="number" name="year" min="2024" max="2060" value="<?php echo date('Y') + 8; ?>" style="width:100px"><button class="admin-btn admin-btn-sm">Add Good Friday &amp; Easter Monday</button></form>
    <p class="admin-help" style="margin-top:12px">Fixed-date Ugandan public holidays repeat yearly and Good Friday / Easter Monday are pre-filled for 2026–2032. Add the Eid holidays and any holiday the government announces or moves, and edit or delete anything that is wrong.</p>
  </div>

<?php elseif ($tab === 'remind'):
    $evs = sch_occurrences($conn, date('Y-m-d'), date('Y-m-d', strtotime('+365 day')));
    $evs = array_values(array_filter($evs, fn($o) => $o['kind'] !== 'holiday'));
    $log = $conn->query("SELECT r.*, e.title FROM cal_reminders r LEFT JOIN cal_events e ON e.id=r.event_id ORDER BY r.id DESC LIMIT 40")->fetch_all(MYSQLI_ASSOC);
    $smsOn = trim($S['sch_sms_url']) !== '';
?>
  <form method="post" class="admin-card admin-form">
    <input type="hidden" name="action" value="send">
    <div class="admin-card-head"><h2>Send a reminder now</h2><span class="admin-help">Choose the session, who should get it and how. Wording comes from Email &amp; SMS settings.</span></div>
    <div class="field" style="max-width:560px"><label>Event and date</label>
      <select name="event_id_date" onchange="var p=this.value.split('|');this.form.event_id.value=p[0];this.form.occ_date.value=p[1]" required>
        <option value="">Choose an upcoming session…</option>
        <?php foreach ($evs as $o): ?><option value="<?php echo $o['id'] . '|' . $o['date']; ?>"><?php echo $e(date('D j M Y', strtotime($o['date'])) . ' — ' . $o['title'] . ($o['programme_title'] ? ' (' . $o['programme_title'] . ')' : '')); ?></option><?php endforeach; ?>
      </select><input type="hidden" name="event_id"><input type="hidden" name="occ_date"></div>
    <div class="field"><label>Who should receive it</label>
      <label class="mem-check"><input type="checkbox" name="aud_subs" value="1" checked> Everyone who asked for reminders about this programme/event (and “all programmes”)</label>
      <label class="mem-check"><input type="checkbox" name="aud_members" value="all"> All approved NUFA members</label>
      <label style="margin-top:8px">Specific individuals (one per line: <code>Name, email, phone</code> — email or phone may be left out)</label>
      <textarea name="extra" style="min-height:70px" placeholder="Jane Akello, jane@example.com, 0772123456"></textarea></div>
    <div class="field"><label>Send by</label>
      <label class="mem-check"><input type="checkbox" name="via_email" value="1" checked> Email</label>
      <label class="mem-check"><input type="checkbox" name="via_sms" value="1" <?php echo $smsOn ? 'checked' : ''; ?>> SMS <?php echo $smsOn ? '' : '(no gateway configured yet — see Email &amp; SMS settings)'; ?></label></div>
    <button class="admin-btn admin-btn-brand" onclick="return this.form.event_id.value ? confirm('Send this reminder now?') : (alert('Choose a session first.'), false)">Send reminder</button>
  </form>
  <div class="admin-card"><div class="admin-card-head"><h2>Recent reminders</h2></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>When</th><th>Event</th><th>Date</th><th>Channel</th><th>To</th><th>Result</th></tr></thead><tbody>
    <?php foreach ($log as $l): ?><tr><td><?php echo $e($l['created_at']); ?></td><td><?php echo $e($l['title'] ?? '—'); ?></td><td><?php echo $e($l['occ_date']); ?></td><td><?php echo $e($l['channel']) . ($l['mode'] === 'auto' ? ' (auto)' : ''); ?></td><td><?php echo $e($l['recipient']); ?></td><td><span class="admin-badge <?php echo $l['status'] === 'sent' ? '' : 'red'; ?>"><?php echo $e($l['status']); ?></span> <small><?php echo $e($l['detail']); ?></small></td></tr>
    <?php endforeach; if (!$log) echo '<tr><td colspan="6">Nothing sent yet.</td></tr>'; ?></tbody></table></div>
    <p class="admin-help" style="margin-top:10px">“Sent” for email means it was handed to the server’s mail system — delivery cannot be confirmed. On a local XAMPP install mail is not delivered.</p></div>

<?php elseif ($tab === 'subscribers'):
    $subs = $conn->query("SELECT s.*, p.title ptitle FROM cal_subscribers s LEFT JOIN programmes p ON p.id=s.programme_id ORDER BY s.id DESC LIMIT 500")->fetch_all(MYSQLI_ASSOC); ?>
  <form method="post" class="admin-card admin-form">
    <input type="hidden" name="action" value="sub_add">
    <div class="admin-card-head"><h2>Add a recipient</h2><span class="admin-help">People who sign up on the website appear here automatically. Add anyone you have consent to remind.</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Name</label><input name="name" required></div><div class="field"><label>Email</label><input type="email" name="email"></div>
      <div class="field"><label>Phone</label><input name="phone" placeholder="+256… or 07…"></div>
      <div class="field"><label>Programme</label><select name="programme_id"><option value="0">All programmes</option><?php foreach ($progs as $pr) echo '<option value="' . $pr['id'] . '">' . $e($pr['title']) . '</option>'; ?></select></div>
    </div>
    <label class="mem-check"><input type="checkbox" name="want_email" value="1" checked> Email</label> <label class="mem-check"><input type="checkbox" name="want_sms" value="1"> SMS</label>
    <button class="admin-btn admin-btn-brand">Add recipient</button>
  </form>
  <div class="admin-card"><div class="admin-card-head"><h2>Recipients (<?php echo count($subs); ?>)</h2></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Programme</th><th>Via</th><th>Source</th><th></th></tr></thead><tbody>
    <?php foreach ($subs as $s): ?><tr<?php echo $s['unsubscribed'] ? ' style="opacity:.5"' : ''; ?>><td><?php echo $e($s['name']); ?></td><td><?php echo $e($s['email']); ?></td><td><?php echo $e($s['phone']); ?></td><td><?php echo $e($s['ptitle'] ?? 'All'); ?></td>
      <td><?php echo ($s['want_email'] ? 'Email ' : '') . ($s['want_sms'] ? 'SMS' : ''); ?></td><td><?php echo $e($s['source']) . ($s['unsubscribed'] ? ' · unsubscribed' : ''); ?></td>
      <td><form method="post" style="display:inline"><input type="hidden" name="id" value="<?php echo $s['id']; ?>"><button name="action" value="sub_toggle" class="admin-btn admin-btn-sm"><?php echo $s['unsubscribed'] ? 'Resubscribe' : 'Pause'; ?></button> <button name="action" value="sub_delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Remove this recipient?')">Remove</button></form></td></tr>
    <?php endforeach; if (!$subs) echo '<tr><td colspan="7">No recipients yet.</td></tr>'; ?></tbody></table></div></div>

<?php else: $cron = mem_site_url() . '/cron-reminders.php?key=' . $S['sch_cron_key']; ?>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="settings">
    <div class="admin-card"><div class="admin-card-head"><h2>Reminder wording</h2><span class="admin-help">Placeholders: {name} {event} {programme} {date} {time} {venue} {link} {details} {unsubscribe}</span></div>
      <div class="field"><label>Email subject</label><input name="sch_email_subject" value="<?php echo $e($S['sch_email_subject']); ?>"></div>
      <div class="field"><label>Email body</label><textarea name="sch_email_body" style="min-height:170px"><?php echo $e($S['sch_email_body']); ?></textarea></div>
      <div class="field"><label>SMS text (keep it short)</label><textarea name="sch_sms_text" style="min-height:60px"><?php echo $e($S['sch_sms_text']); ?></textarea></div></div>
    <div class="admin-card"><div class="admin-card-head"><h2>SMS gateway</h2><span class="admin-help">NUFA sends SMS through your provider’s HTTP API (Africa’s Talking, Twilio, Yo!, etc.). Enter the endpoint from the provider’s documentation. Until it is filled in, SMS reminders are logged as failed and nothing is sent.</span></div>
      <div class="admin-form-grid">
        <div class="field"><label>Gateway URL</label><input name="sch_sms_url" value="<?php echo $e($S['sch_sms_url']); ?>" placeholder="https://api.provider.com/sms/send"><span class="admin-help">May contain {to}, {message}, {sender}.</span></div>
        <div class="field"><label>Method</label><select name="sch_sms_method"><option <?php echo $S['sch_sms_method'] === 'POST' ? 'selected' : ''; ?>>POST</option><option <?php echo $S['sch_sms_method'] === 'GET' ? 'selected' : ''; ?>>GET</option></select></div>
        <div class="field"><label>POST body (form-encoded)</label><input name="sch_sms_body" value="<?php echo $e($S['sch_sms_body']); ?>"></div>
        <div class="field"><label>Extra headers (one per line, e.g. Authorization: Bearer …)</label><textarea name="sch_sms_header" style="min-height:60px"><?php echo $e($S['sch_sms_header']); ?></textarea></div>
        <div class="field"><label>Sender name</label><input name="sch_sms_sender" value="<?php echo $e($S['sch_sms_sender']); ?>"></div>
        <div class="field"><label>Default country code</label><input name="sch_country_code" value="<?php echo $e($S['sch_country_code']); ?>"><span class="admin-help">Used to turn 0772… into +256772…</span></div>
        <div class="field"><label>Send a test SMS to</label><input name="test_phone" placeholder="Your phone number (optional)"></div>
      </div></div>
    <div class="admin-card"><div class="admin-card-head"><h2>Automatic reminders</h2></div>
      <p class="admin-help">Events with an “Auto-reminder (days before)” value are reminded automatically. Create a daily scheduled task (cron job) in hPanel → Advanced → Cron Jobs that opens this address once a day:</p>
      <p><code style="word-break:break-all"><?php echo $e($cron); ?></code></p>
      <label class="mem-check"><input type="checkbox" name="sch_cron_new" value="1"> Generate a new secret key</label></div>
    <button class="admin-btn admin-btn-brand">Save settings</button>
  </form>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
