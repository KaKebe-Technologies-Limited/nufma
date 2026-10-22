<?php
$adminTitle = "Holiday greetings";
$adminNav = "schedule";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/schedule.php";
sch_install($conn);

$e = fn($v) => htmlspecialchars((string)$v);
$msg = ''; $err = '';
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'settings') {
        $aud = in_array($_POST['sch_greet_audience'] ?? '', ['members', 'subscribers', 'both'], true) ? $_POST['sch_greet_audience'] : 'members';
        $subj = mb_substr(trim($_POST['sch_greet_subject'] ?? ''), 0, 255); $body = mb_substr(trim($_POST['sch_greet_body'] ?? ''), 0, 4000);
        if ($subj === '' || $body === '') $err = "The default greeting needs a subject and a message.";
        else { $save('sch_greet_holidays', isset($_POST['sch_greet_holidays']) ? '1' : '0'); $save('sch_greet_audience', $aud); $save('sch_greet_subject', $subj); $save('sch_greet_body', $body); $msg = "Saved."; }
    } elseif (in_array($act, ['event', 'send', 'test'], true)) {
        $id = (int)($_POST['id'] ?? 0); $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'] ?? '') ? $_POST['date'] : date('Y-m-d');
        $ev = $id ? $conn->query("SELECT * FROM cal_events WHERE id=$id")->fetch_assoc() : null;
        if (!$ev) $err = "Event not found.";
        elseif ($act === 'event') {
            $mode = max(0, min(2, (int)($_POST['greet_mode'] ?? 0))); $subj = mb_substr(trim($_POST['greet_subject'] ?? ''), 0, 255); $body = mb_substr(trim($_POST['greet_body'] ?? ''), 0, 4000);
            if ($mode === 1 && ($subj === '' && $body === '') ) { /* uses the default message */ }
            $u = $conn->prepare("UPDATE cal_events SET greet_mode=?, greet_subject=?, greet_body=? WHERE id=?"); $u->bind_param("issi", $mode, $subj, $body, $id); $u->execute(); $msg = "Saved the greeting for “" . $ev['title'] . "”.";
            $_GET['edit'] = $id;
        } else {
            $occ = array_values(array_filter(sch_occurrences($conn, $date, $date, 0, $id), fn($o) => $o['date'] === $date)); $o = $occ[0] ?? null;
            if (!$o) $err = "That date is not an occurrence of this event.";
            elseif ($act === 'test') {
                $to = trim($_POST['test_to'] ?? '');
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $err = "Enter a valid email address for the test.";
                else { [$sj, $bd] = sch_greet_message($conn, $o); $v = ['{name}' => 'Test Member', '{holiday}' => $o['title'], '{event}' => $o['title'], '{date}' => date('l, j F Y', strtotime($date))]; $ok = mem_send($conn, null, 'greeting', $to, '[TEST] ' . strtr($sj, $v), strtr($bd, $v)); $msg = $ok ? "Test greeting handed to the mail system for $to." : "The mail system refused the test (normal on a local XAMPP install)."; }
                $_GET['edit'] = $id;
            } else {
                [$s, $f, $r] = sch_greet_send($conn, $o, 150);
                $msg = "Greeting for “{$o['title']}”: $s sent" . ($f ? ", $f refused by the mail system" : '') . ($r ? ", $r still waiting — press Send again to continue" : '') . ($s + $f + $r === 0 ? ' — everyone has already been greeted for this day' : '') . ".";
            }
        }
    }
}

$S = sch_settings($conn, true);
$members = (int)$conn->query("SELECT COUNT(*) FROM members WHERE status='approved' AND email<>''")->fetch_row()[0];
$subs = 0; try { $subs = (int)$conn->query("SELECT COUNT(*) FROM subscribers WHERE unsubscribed=0")->fetch_row()[0]; } catch (Throwable $x) { }
$audCount = count(sch_greet_audience($conn));
$edit = (int)($_GET['edit'] ?? 0); $cur = $edit ? $conn->query("SELECT * FROM cal_events WHERE id=$edit")->fetch_assoc() : null;
$upcoming = array_values(array_filter(sch_occurrences($conn, date('Y-m-d'), date('Y-m-d', strtotime('+150 day'))), fn($o) => in_array($o['kind'], ['holiday', 'important'], true)));
$sentBy = []; foreach ($conn->query("SELECT event_id, occ_date, COUNT(*) c FROM cal_reminders WHERE mode='greeting' AND status='sent' GROUP BY event_id, occ_date")->fetch_all(MYSQLI_ASSOC) as $x) $sentBy[$x['event_id'] . '|' . $x['occ_date']] = (int)$x['c'];
$cronUrl = mem_site_url() . '/cron-reminders.php?key=' . $S['sch_cron_key'];
$last = $conn->query("SELECT setting_value FROM settings WHERE setting_key='sch_greet_last_run'")->fetch_row()[0] ?? '';
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="schedule.php">← Schedule</a><a href="greetings.php" class="active">Holiday greetings</a><a href="schedule.php?tab=remind">Send a reminder</a><a href="schedule.php?tab=settings">Email &amp; SMS settings</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<div class="edit-grid">
<form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="settings">
  <div class="admin-card-head"><h2>Default greeting</h2><span class="admin-help">Placeholders: {name} {holiday} {date}</span></div>
  <label class="mem-check"><input type="checkbox" name="sch_greet_holidays" value="1" <?php echo $S['sch_greet_holidays'] === '1' ? 'checked' : ''; ?>> Greet automatically on every public holiday in the calendar</label>
  <div class="field" style="max-width:340px;margin-top:8px"><label>Who receives greetings</label><select name="sch_greet_audience"><?php foreach (['members' => "Registered members ($members)", 'subscribers' => "Newsletter subscribers ($subs)", 'both' => 'Members and subscribers'] as $k => $l) echo '<option value="' . $k . '"' . ($S['sch_greet_audience'] === $k ? ' selected' : '') . '>' . $e($l) . '</option>'; ?></select></div>
  <div class="field"><label>Subject</label><input name="sch_greet_subject" value="<?php echo $e($S['sch_greet_subject']); ?>"></div>
  <div class="field"><label>Message</label><textarea name="sch_greet_body" style="min-height:170px"><?php echo $e($S['sch_greet_body']); ?></textarea></div>
  <button class="admin-btn admin-btn-brand">Save default greeting</button>
</form>
<div class="admin-card"><div class="admin-card-head"><h2>How it works</h2></div>
  <ol class="admin-steps"><li><b>The calendar decides</b> — every morning the system checks today's date against your calendar (public holidays and important days).</li><li><b>Your message goes out</b> — registered members get your default greeting, or the special message you wrote for that day.</li><li><b>No repeats</b> — a person is greeted once per day, even if the task runs twice.</li></ol>
  <p class="admin-help">Needs the daily task from <a href="schedule.php?tab=settings">Email &amp; SMS settings</a>:</p><p><code style="word-break:break-all;font-size:.75rem"><?php echo $e($cronUrl); ?></code></p>
  <p class="admin-help">Last checked: <b><?php echo $last ? $e(date('d M Y', strtotime($last))) : 'never'; ?></b>. If no daily task is set up, the check also runs the first time an admin opens the dashboard each day.</p></div>
</div>

<?php if ($cur): $o0 = ['id' => $cur['id'], 'kind' => $cur['kind'], 'title' => $cur['title'], 'greet_subject' => $cur['greet_subject'] ?? '', 'greet_body' => $cur['greet_body'] ?? '']; $nextDate = ''; foreach ($upcoming as $u) if ((int)$u['id'] === (int)$cur['id']) { $nextDate = $u['date']; break; } $nextDate = $nextDate ?: date('Y-m-d'); ?>
<form method="post" class="admin-card admin-form" id="edit"><input type="hidden" name="id" value="<?php echo $cur['id']; ?>"><input type="hidden" name="date" value="<?php echo $e($nextDate); ?>">
  <div class="admin-card-head"><h2>Greeting for “<?php echo $e($cur['title']); ?>”</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="greetings.php">Close</a></div>
  <div class="field" style="max-width:420px"><label>What should happen on this day?</label><select name="greet_mode"><?php foreach ([0 => 'Follow the default (holidays: greet; other days: no greeting)', 1 => 'Send a greeting', 2 => 'Do not send a greeting'] as $k => $l) echo '<option value="' . $k . '"' . ((int)$cur['greet_mode'] === $k ? ' selected' : '') . '>' . $e($l) . '</option>'; ?></select></div>
  <div class="field"><label>Subject <span class="admin-help">(blank = default subject)</span></label><input name="greet_subject" value="<?php echo $e($cur['greet_subject'] ?? ''); ?>" placeholder="<?php echo $e($S['sch_greet_subject']); ?>"></div>
  <div class="field"><label>Your message for this day <span class="admin-help">(blank = default message)</span></label><textarea name="greet_body" style="min-height:150px" placeholder="<?php echo $e(mb_substr($S['sch_greet_body'], 0, 120)); ?>…"><?php echo $e($cur['greet_body'] ?? ''); ?></textarea></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end"><button name="action" value="event" class="admin-btn admin-btn-brand">Save</button>
    <input type="email" name="test_to" placeholder="Send a test to…" style="padding:10px 12px;border:1px solid #e4e0d6;border-radius:10px;min-width:220px"><button name="action" value="test" class="admin-btn admin-btn-line">Send test</button>
    <button name="action" value="send" class="admin-btn admin-btn-line" onclick="return confirm('Send this greeting now to <?php echo $audCount; ?> recipient(s)?')">Send now to <?php echo $audCount; ?> recipient(s)</button></div>
</form>
<?php endif; ?>

<div class="admin-card"><div class="admin-card-head"><h2>Upcoming holidays &amp; special days (next 5 months)</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="schedule.php">Edit the calendar</a></div>
  <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Day</th><th>Type</th><th>Greeting</th><th>Sent</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($upcoming as $o): $on = sch_greet_enabled($conn, $o); $custom = trim((string)($o['greet_body'] ?? '')) !== ''; $n = $sentBy[$o['id'] . '|' . $o['date']] ?? 0; ?>
    <tr<?php echo $o['date'] === date('Y-m-d') ? ' style="background:#FFF4EA"' : ''; ?>><td class="nowrap"><?php echo $e(date('D j M Y', strtotime($o['date']))); ?><?php echo $o['date'] === date('Y-m-d') ? ' <span class="admin-badge">Today</span>' : ''; ?></td><td><b><?php echo $e($o['title']); ?></b></td><td><?php echo $e(sch_kinds()[$o['kind']] ?? ''); ?></td>
      <td><?php echo !$on ? '<span class="admin-badge gray">Off</span>' : ($custom ? '<span class="admin-badge">Custom message</span>' : '<span class="admin-badge" style="background:#eef6ee;color:#1f6b2b">Default message</span>'); ?></td><td><?php echo $n ?: '—'; ?></td>
      <td><a class="admin-btn admin-btn-line admin-btn-sm" href="greetings.php?edit=<?php echo $o['id']; ?>#edit">Write / change message</a></td></tr>
  <?php endforeach; if (!$upcoming) echo '<tr><td colspan="6" style="color:var(--text-muted)">No holidays or special days in the next 5 months. Add them in the calendar.</td></tr>'; ?></tbody></table></div>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
