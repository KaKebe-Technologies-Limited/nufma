<?php
$adminTitle = "Award invitation";
$adminNav = "invitations";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/invitations.php";
inv_install($conn);

$id = (int)($_GET['id'] ?? 0);
$camp = $conn->query("SELECT * FROM invite_campaigns WHERE id=$id")->fetch_assoc();
if (!$camp) { header("Location: invitations.php"); exit; }
$tab = in_array($_GET['tab'] ?? '', ['setup', 'guests', 'send'], true) ? $_GET['tab'] : (isset($_GET['new']) ? 'setup' : 'guests');
if (isset($_GET['new']) && !isset($_GET['tab'])) $tab = 'setup';
$msg = ''; $err = ''; $e = fn($v) => htmlspecialchars((string)$v);
$editions = $conn->query("SELECT id, year FROM award_editions ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT title_text, name, org, email, status, sent_at, rsvp, rsvp_at FROM invite_guests WHERE campaign_id=$id ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="invitation-guests.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Title', 'Name', 'Organisation / position', 'Email', 'Invitation', 'Sent at', 'RSVP', 'RSVP at']);
    foreach ($rows as $r) fputcsv($o, [$r['title_text'], $r['name'], $r['org'], $r['email'], $r['status'], $r['sent_at'], $r['rsvp'] ?: 'no reply', $r['rsvp_at']]); fclose($o); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'setup') {
        $tab = 'setup';
        $f = fn($k, $m = 255) => mb_substr(trim($_POST[$k] ?? ''), 0, $m);
        $lay = inv_layout($camp); $lay['page'] = max(1, (int)($_POST['page'] ?? 1));
        foreach (['name', 'sub'] as $k) {
            $lay[$k]['x'] = (float)($_POST["{$k}_x"] ?? $lay[$k]['x']); $lay[$k]['y'] = (float)($_POST["{$k}_y"] ?? $lay[$k]['y']); $lay[$k]['w'] = max(10, (float)($_POST["{$k}_w"] ?? $lay[$k]['w']));
            $lay[$k]['size'] = max(6, min(120, (float)($_POST["{$k}_size"] ?? $lay[$k]['size']))); $lay[$k]['align'] = in_array($_POST["{$k}_align"] ?? 'C', ['L', 'C', 'R'], true) ? $_POST["{$k}_align"] : 'C';
            $lay[$k]['color'] = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST["{$k}_color"] ?? '') ? $_POST["{$k}_color"] : '#000000'; $lay[$k]['bold'] = isset($_POST["{$k}_bold"]) ? 1 : 0;
        }
        $lay['sub']['on'] = isset($_POST['sub_on']) ? 1 : 0;
        $tpl = $camp['template_file'];
        if (!empty($_FILES['template']['name'])) {
            [$fn, $er] = mem_store_upload($_FILES['template'], ['application/pdf' => 'pdf'], 15 * 1048576, 'invite', inv_storage());
            if ($er) $err = "PDF template: $er"; else { if ($tpl) @unlink(inv_storage() . basename($tpl)); $tpl = $fn; }
        }
        $reply = $f('reply_to', 190);
        if (!$err && $reply !== '' && !filter_var($reply, FILTER_VALIDATE_EMAIL)) $err = "The reply-to address is not a valid email.";
        if (!$err && $f('title', 190) === '') $err = "Give the invitation a name.";
        if (!$err) {
            $mode = in_array($_POST['attach_mode'] ?? '', ['personalised', 'plain', 'none'], true) ? $_POST['attach_mode'] : 'personalised';
            $json = json_encode($lay); $t = $f('title', 190); $subj = $f('subject'); $body = mb_substr(trim($_POST['body'] ?? ''), 0, 5000); $ed = (int)($_POST['edition_id'] ?? 0);
            $dt = $f('event_date_text', 190); $ven = $f('venue_text'); $rs = isset($_POST['rsvp_on']) ? 1 : 0; $rd = $f('rsvp_deadline', 120);
            $s = $conn->prepare("UPDATE invite_campaigns SET title=?, edition_id=?, subject=?, body=?, template_file=?, attach_mode=?, layout=?, event_date_text=?, venue_text=?, rsvp_on=?, rsvp_deadline=?, reply_to=? WHERE id=?");
            $s->bind_param("sisssssssissi", $t, $ed, $subj, $body, $tpl, $mode, $json, $dt, $ven, $rs, $rd, $reply, $id); $s->execute();
            $msg = "Saved."; $camp = $conn->query("SELECT * FROM invite_campaigns WHERE id=$id")->fetch_assoc();
        }
    } elseif ($act === 'add_guests') {
        $tab = 'guests'; $text = (string)($_POST['guests'] ?? '');
        if (!empty($_FILES['csv']['name']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK && $_FILES['csv']['size'] < 2097152) $text .= "\n" . file_get_contents($_FILES['csv']['tmp_name']);
        [$rows, $skipped] = inv_parse_guests($text); $added = inv_add_guests($conn, $id, $rows);
        $msg = "$added guest" . ($added === 1 ? '' : 's') . " added" . (count($rows) - $added > 0 ? ", " . (count($rows) - $added) . " already on the list" : '') . ($skipped ? ", $skipped line(s) skipped (no valid email or name)" : '') . ".";
    } elseif ($act === 'add_members') {
        $tab = 'guests'; $rows = [];
        $q = $conn->query("SELECT full_name, email FROM members WHERE status='approved' AND email<>''");
        while ($q && ($m = $q->fetch_assoc())) if (filter_var($m['email'], FILTER_VALIDATE_EMAIL)) $rows[] = ['title' => '', 'name' => $m['full_name'], 'org' => 'NUFA member', 'email' => strtolower($m['email'])];
        $added = inv_add_guests($conn, $id, $rows); $msg = "$added approved member(s) added to the guest list.";
    } elseif ($act === 'edit_guest') {
        $tab = 'guests'; $gid = (int)($_POST['gid'] ?? 0); $em = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($em, FILTER_VALIDATE_EMAIL) || trim($_POST['name'] ?? '') === '') $err = "A guest needs a name and a valid email.";
        else { $s = $conn->prepare("UPDATE invite_guests SET title_text=?, name=?, org=?, email=?, status=IF(email<>?, 'pending', status) WHERE id=? AND campaign_id=?"); $t = mb_substr(trim($_POST['title_text'] ?? ''), 0, 80); $n = mb_substr(trim($_POST['name']), 0, 190); $o = mb_substr(trim($_POST['org'] ?? ''), 0, 190);
            $s->bind_param("sssssii", $t, $n, $o, $em, $em, $gid, $id); $s->execute(); $msg = "Guest updated."; }
    } elseif ($act === 'del_guest') { $tab = 'guests'; $conn->query("DELETE FROM invite_guests WHERE id=" . (int)($_POST['gid'] ?? 0) . " AND campaign_id=$id"); $msg = "Guest removed."; }
    elseif ($act === 'clear_unsent') { $tab = 'guests'; $conn->query("DELETE FROM invite_guests WHERE campaign_id=$id AND status<>'sent'"); $msg = "Guests who haven't been sent an invitation were removed."; }
    elseif ($act === 'test') {
        $tab = 'send'; $to = trim($_POST['test_to'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) $err = "Enter a valid email address for the test.";
        else { $g = ['title_text' => 'Hon.', 'name' => 'Test Guest', 'org' => 'Sample organisation', 'email' => $to, 'token' => 'test']; [$ok, $note] = inv_mail($conn, $camp, $g, $to); $msg = $ok ? "Test invitation handed to the mail system for $to. $note" : "The mail system refused the test (normal on a local XAMPP install). $note"; }
    } elseif ($act === 'send') {
        $tab = 'send'; [$sent, $failed, $rem] = inv_send_batch($conn, $camp, 25);
        $msg = "Batch done — $sent sent" . ($failed ? ", $failed refused by the mail system" : '') . ". " . ($rem ? "$rem guest(s) still waiting: press “Send next batch”." : "No one is waiting.");
    } elseif ($act === 'resend') {
        $tab = 'guests'; $gid = (int)($_POST['gid'] ?? 0); [$sent, $failed] = inv_send_batch($conn, $camp, 1, [$gid]); $msg = $sent ? "Invitation sent." : "The mail system refused it.";
    } elseif ($act === 'retry_failed') { $tab = 'send'; $conn->query("UPDATE invite_guests SET status='pending' WHERE campaign_id=$id AND status='failed'"); $msg = "Failed guests are queued again."; }
}

$guests = $conn->query("SELECT * FROM invite_guests WHERE campaign_id=$id ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$cnt = ['pending' => 0, 'sent' => 0, 'failed' => 0, 'yes' => 0, 'no' => 0, 'none' => 0];
foreach ($guests as $g) { $cnt[$g['status']] = ($cnt[$g['status']] ?? 0) + 1; if ($g['rsvp'] === 'yes') $cnt['yes']++; elseif ($g['rsvp'] === 'no') $cnt['no']++; elseif ($g['status'] === 'sent') $cnt['none']++; }
$lay = inv_layout($camp);
// is the uploaded template one we can personalise?
$tplInfo = null; $tplOk = null;
if ($camp['template_file'] !== '' && is_file(inv_storage() . basename($camp['template_file']))) {
    try { $ps = new PdfStamper(file_get_contents(inv_storage() . basename($camp['template_file']))); $tplInfo = $ps->pageSize(0); $tplOk = true; }
    catch (Throwable $ex) { $tplOk = $ex->getMessage(); }
}
include __DIR__ . "/includes/admin-header.php";
$tabs = ['setup' => '1 · Template & message', 'guests' => '2 · Guests (' . count($guests) . ')', 'send' => '3 · Send & responses'];
?>
<div class="mem-tabs"><a href="invitations.php">← All invitations</a><?php foreach ($tabs as $t => $l): ?><a href="invitation.php?id=<?php echo $id; ?>&tab=<?php echo $t; ?>" class="<?php echo $tab === $t ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?></div>
<h2 style="margin:0 0 14px"><?php echo $e($camp['title']); ?></h2>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'setup'): ?>
<form method="post" enctype="multipart/form-data" class="admin-form"><input type="hidden" name="action" value="setup">
  <div class="edit-grid">
    <div class="edit-main">
      <div class="admin-card"><div class="admin-card-head"><h2>The email</h2><span class="admin-help">Placeholders: {full_name} {title} {name} {org} {event} {date} {venue} {rsvp_deadline}</span></div>
        <div class="admin-form-grid"><div class="field"><label>Invitation name</label><input name="title" value="<?php echo $e($camp['title']); ?>" required></div>
          <div class="field"><label>Awards edition</label><select name="edition_id"><option value="0">—</option><?php foreach ($editions as $ed) echo '<option value="' . $ed['id'] . '"' . ((int)$camp['edition_id'] === (int)$ed['id'] ? ' selected' : '') . '>NUFA Awards ' . (int)$ed['year'] . '</option>'; ?></select></div>
          <div class="field"><label>Date of the event (shown in the email)</label><input name="event_date_text" value="<?php echo $e($camp['event_date_text']); ?>" placeholder="e.g. Saturday, 18 September 2027, 6:00 pm"></div>
          <div class="field"><label>Venue</label><input name="venue_text" value="<?php echo $e($camp['venue_text']); ?>" placeholder="e.g. Acholi Inn, Gulu City"></div></div>
        <div class="field"><label>Email subject</label><input name="subject" value="<?php echo $e($camp['subject']); ?>"></div>
        <div class="field"><label>Message</label><textarea name="body" style="min-height:190px"><?php echo $e($camp['body']); ?></textarea></div>
        <div class="admin-form-grid"><div class="field"><label>Replies go to (optional)</label><input type="email" name="reply_to" value="<?php echo $e($camp['reply_to']); ?>"></div>
          <div class="field"><label>Please reply by (optional)</label><input name="rsvp_deadline" value="<?php echo $e($camp['rsvp_deadline']); ?>" placeholder="e.g. 1 September 2027"></div></div>
        <label class="mem-check"><input type="checkbox" name="rsvp_on" value="1" <?php echo $camp['rsvp_on'] ? 'checked' : ''; ?>> Include “Yes, I will attend / Sorry, I cannot” buttons so guests can reply with one click</label>
      </div>
      <div class="admin-card"><div class="admin-card-head"><h2>Where the name goes on your PDF</h2><span class="admin-help">Distances are in millimetres from the top-left corner of the page. Save, then check the live preview.</span></div>
        <div class="admin-form-grid"><div class="field"><label>Page of the PDF</label><input type="number" min="1" name="page" value="<?php echo (int)$lay['page']; ?>"></div></div>
        <?php foreach (['name' => 'Line 1 — title and name (e.g. “Hon. Dr. Jane Akello”)', 'sub' => 'Line 2 — organisation / position (optional)'] as $k => $lab): $L = $lay[$k]; ?>
        <h3 class="mem-h3" style="margin-top:14px"><?php echo $lab; ?><?php if ($k === 'sub'): ?> <label class="mem-check" style="display:inline-flex;margin-left:12px"><input type="checkbox" name="sub_on" value="1" <?php echo !empty($L['on']) ? 'checked' : ''; ?>> Show it</label><?php endif; ?></h3>
        <div class="admin-form-grid" style="grid-template-columns:repeat(auto-fit,minmax(110px,1fr))">
          <div class="field"><label>From left (mm)</label><input type="number" step="0.5" name="<?php echo $k; ?>_x" value="<?php echo $e($L['x']); ?>"></div>
          <div class="field"><label>From top (mm)</label><input type="number" step="0.5" name="<?php echo $k; ?>_y" value="<?php echo $e($L['y']); ?>"></div>
          <div class="field"><label>Box width (mm)</label><input type="number" step="0.5" name="<?php echo $k; ?>_w" value="<?php echo $e($L['w']); ?>"></div>
          <div class="field"><label>Text size (pt)</label><input type="number" step="0.5" name="<?php echo $k; ?>_size" value="<?php echo $e($L['size']); ?>"></div>
          <div class="field"><label>Align</label><select name="<?php echo $k; ?>_align"><?php foreach (['L' => 'Left', 'C' => 'Centre', 'R' => 'Right'] as $a => $al) echo '<option value="' . $a . '"' . ($L['align'] === $a ? ' selected' : '') . '>' . $al . '</option>'; ?></select></div>
          <div class="field"><label>Colour</label><input type="color" name="<?php echo $k; ?>_color" value="<?php echo $e($L['color']); ?>" style="height:42px;padding:3px"></div>
          <div class="field"><label>&nbsp;</label><label class="mem-check"><input type="checkbox" name="<?php echo $k; ?>_bold" value="1" <?php echo !empty($L['bold']) ? 'checked' : ''; ?>> Bold</label></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <aside class="edit-side">
      <div class="admin-card"><div class="admin-card-head"><h2>PDF template</h2></div>
        <?php if ($tplOk === true): ?><div class="admin-alert ok" style="margin-bottom:10px">Template uploaded — page size <?php echo round($tplInfo[0] / 72 * 25.4); ?> × <?php echo round($tplInfo[1] / 72 * 25.4); ?> mm. Names can be added to it.</div>
        <?php elseif ($tplOk !== null): ?><div class="admin-alert err" style="margin-bottom:10px">Template uploaded, but names can't be added to it: <?php echo $e($tplOk); ?> It will be attached unchanged. Tip: re-save it from your design tool as a standard PDF, or print it to a new PDF.</div><?php endif; ?>
        <div class="field"><label><?php echo $camp['template_file'] ? 'Replace the PDF' : 'Upload the invitation PDF'; ?> (max 15 MB)</label><input type="file" name="template" accept="application/pdf"></div>
        <div class="field"><label>What guests receive</label>
          <label class="mem-check"><input type="radio" name="attach_mode" value="personalised" <?php echo $camp['attach_mode'] === 'personalised' ? 'checked' : ''; ?>> The PDF with their title &amp; name added</label>
          <label class="mem-check"><input type="radio" name="attach_mode" value="plain" <?php echo $camp['attach_mode'] === 'plain' ? 'checked' : ''; ?>> The same PDF for everyone (unchanged)</label>
          <label class="mem-check"><input type="radio" name="attach_mode" value="none" <?php echo $camp['attach_mode'] === 'none' ? 'checked' : ''; ?>> No attachment (message only)</label></div>
        <button class="admin-btn admin-btn-brand">Save</button>
      </div>
      <?php if ($camp['template_file'] !== ''): ?>
      <div class="admin-card"><div class="admin-card-head"><h2>Preview</h2></div>
        <form method="get" action="invitation-file.php" target="_blank" class="admin-form"><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="mode" value="preview">
          <div class="field"><label>Sample title</label><input name="t" value="Hon. Dr."></div><div class="field"><label>Sample name</label><input name="n" value="Jane Akello Odongo"></div><div class="field"><label>Sample organisation</label><input name="o" value="Ministry of Gender, Labour and Social Development"></div>
          <button class="admin-btn admin-btn-line">Open the personalised preview</button> <a class="admin-btn admin-btn-line" target="_blank" href="invitation-file.php?id=<?php echo $id; ?>&mode=template">View original</a></form>
        <p class="admin-help" style="margin-top:8px">Adjust the numbers on the left and press Save, then open the preview again until the name sits where you want it.</p>
      </div><?php endif; ?>
    </aside>
  </div>
</form>

<?php elseif ($tab === 'guests'): ?>
<div class="edit-grid">
  <div class="edit-main">
    <div class="admin-card"><div class="admin-card-head"><h2>Guests (<?php echo count($guests); ?>)</h2><span><a class="admin-btn admin-btn-line admin-btn-sm" href="invitation.php?id=<?php echo $id; ?>&export=1">Export CSV</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Remove every guest who has not been sent an invitation?')"><input type="hidden" name="action" value="clear_unsent"><button class="admin-btn admin-btn-danger admin-btn-sm">Clear unsent</button></form></span></div>
      <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Title</th><th>Name</th><th>Organisation / position</th><th>Email</th><th>Invitation</th><th>RSVP</th><th></th></tr></thead><tbody>
        <?php if (!$guests): ?><tr><td colspan="7" style="color:var(--text-muted)">No guests yet — add them on the right.</td></tr><?php endif; ?>
        <?php foreach ($guests as $g): ?>
        <?php $fid = 'gf' . $g['id']; ?><tr>
          <td><form id="<?php echo $fid; ?>" method="post"><input type="hidden" name="gid" value="<?php echo $g['id']; ?>"></form><input form="<?php echo $fid; ?>" name="title_text" value="<?php echo $e($g['title_text']); ?>" style="width:70px;padding:6px 8px"></td>
          <td><input form="<?php echo $fid; ?>" name="name" value="<?php echo $e($g['name']); ?>" style="padding:6px 8px;min-width:150px"></td>
          <td><input form="<?php echo $fid; ?>" name="org" value="<?php echo $e($g['org']); ?>" style="padding:6px 8px;min-width:150px"></td>
          <td><input form="<?php echo $fid; ?>" name="email" value="<?php echo $e($g['email']); ?>" style="padding:6px 8px;min-width:180px"></td>
          <td><?php echo ['sent' => '<span class="admin-badge">Sent</span>', 'failed' => '<span class="admin-badge red">Refused</span>', 'pending' => '<span class="admin-badge gray">Waiting</span>'][$g['status']] ?? ''; ?><?php if ($g['sent_at']): ?><br><small><?php echo date('d M H:i', strtotime($g['sent_at'])); ?></small><?php endif; ?></td>
          <td><?php echo $g['rsvp'] === 'yes' ? '<span class="admin-badge">Attending</span>' : ($g['rsvp'] === 'no' ? '<span class="admin-badge red">Declined</span>' : '<small class="muted">—</small>'); ?></td>
          <td><div class="admin-row-actions"><button form="<?php echo $fid; ?>" name="action" value="edit_guest" class="admin-btn admin-btn-line admin-btn-sm">Save</button>
            <?php if ($camp['template_file']): ?><a class="admin-btn admin-btn-line admin-btn-sm" target="_blank" href="invitation-file.php?id=<?php echo $id; ?>&mode=guest&guest=<?php echo $g['id']; ?>">PDF</a><?php endif; ?>
            <button form="<?php echo $fid; ?>" name="action" value="resend" class="admin-btn admin-btn-line admin-btn-sm" onclick="return confirm('Send this invitation to <?php echo $e(addslashes($g['email'])); ?> now?')"><?php echo $g['status'] === 'sent' ? 'Resend' : 'Send'; ?></button>
            <button form="<?php echo $fid; ?>" name="action" value="del_guest" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Remove this guest?')">✕</button></div></td></tr>
        <?php endforeach; ?></tbody></table></div>
    </div>
  </div>
  <aside class="edit-side">
    <form method="post" enctype="multipart/form-data" class="admin-card admin-form"><input type="hidden" name="action" value="add_guests">
      <div class="admin-card-head"><h2>Add guests</h2></div>
      <div class="field"><label>Paste a list — one guest per line: <code>Title | Name | Email | Organisation</code></label>
        <textarea name="guests" style="min-height:150px" placeholder="Hon. | Akello Grace | grace@example.com | Ministry of Gender&#10;Dr. | Okello Peter | peter@example.com&#10;Odong Sam | sam@example.com"></textarea>
        <span class="admin-help">Title and organisation are optional. Commas, semicolons or tabs also work, so you can paste straight from Excel.</span></div>
      <div class="field"><label>…or upload a CSV file</label><input type="file" name="csv" accept=".csv,.txt,text/csv,text/plain"></div>
      <button class="admin-btn admin-btn-brand">Add to the list</button>
    </form>
    <form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="add_members"><div class="admin-card-head"><h2>NUFA members</h2></div>
      <p class="admin-help">Add every approved NUFA member who has an email address to this guest list.</p><button class="admin-btn admin-btn-line" onclick="return confirm('Add all approved members to this guest list?')">Add all approved members</button></form>
  </aside>
</div>

<?php else: $total = count($guests); ?>
<div class="stat-row"><div><b><?php echo $total; ?></b><span>Guests</span></div><div><b><?php echo $cnt['sent']; ?></b><span>Invitations sent</span></div><div><b><?php echo $cnt['pending']; ?></b><span>Waiting to send</span></div><div><b><?php echo $cnt['yes']; ?></b><span>Attending</span></div><div><b><?php echo $cnt['no']; ?></b><span>Declined</span></div><div><b><?php echo $cnt['none']; ?></b><span>No reply yet</span></div></div>
<div class="edit-grid"><div class="admin-card"><div class="admin-card-head"><h2>Send the invitations</h2></div>
  <?php if ($camp['template_file'] === '' && $camp['attach_mode'] !== 'none'): ?><div class="admin-alert err">No PDF template has been uploaded yet — guests would only get the message. <a href="invitation.php?id=<?php echo $id; ?>&tab=setup">Upload it first.</a></div><?php endif; ?>
  <div class="progress"><i style="width:<?php echo $total ? round($cnt['sent'] / $total * 100) : 0; ?>%"></i></div><p class="admin-help"><?php echo $cnt['sent']; ?> of <?php echo $total; ?> sent · <?php echo $cnt['pending']; ?> waiting<?php echo $cnt['failed'] ? ' · ' . $cnt['failed'] . ' refused' : ''; ?></p>
  <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:14px"><input type="hidden" name="action" value="test"><div class="field" style="margin:0;flex:1;min-width:220px"><label>Send a test to yourself first</label><input type="email" name="test_to" placeholder="your@email.com"></div><button class="admin-btn admin-btn-line">Send test</button></form>
  <form method="post" style="display:inline" onsubmit="return confirm('Send the invitation to the next batch of waiting guests now?')"><input type="hidden" name="action" value="send"><button class="admin-btn admin-btn-brand" <?php echo $cnt['pending'] ? '' : 'disabled'; ?>><?php echo $cnt['sent'] ? 'Send next batch' : 'Send invitations'; ?> (up to 25 at a time)</button></form>
  <?php if ($cnt['failed']): ?><form method="post" style="display:inline"><input type="hidden" name="action" value="retry_failed"><button class="admin-btn admin-btn-line">Retry the <?php echo $cnt['failed']; ?> refused</button></form><?php endif; ?>
  <p class="admin-help" style="margin-top:12px">Each guest gets their own email with a personalised PDF. “Sent” means the server accepted it — delivery cannot be confirmed. Ask guests to check their spam folder if they don't see it. On a local XAMPP install no mail is delivered.</p>
</div><div></div></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
