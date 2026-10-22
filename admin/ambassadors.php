<?php
$adminTitle = "NUFA Ambassadors";
$adminNav = "ambassadors";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/outreach.php";
require_once __DIR__ . "/../includes/export.php";
out_install($conn);
$S = out_settings($conn); $e = fn($v) => htmlspecialchars((string)$v); $error = '';
$tabs = ['apps' => 'Applications', 'meetings' => 'Monthly meetings', 'settings' => 'Settings'];
$tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'apps';
$view = (int)($_GET['view'] ?? 0);

if (isset($_GET['delete'])) { $conn->query("DELETE FROM ambassadors WHERE id=" . (int)$_GET['delete']); header("Location: ambassadors.php?deleted=1"); exit; }
if (isset($_GET['del_meeting'])) { $conn->query("DELETE FROM ambassador_meetings WHERE id=" . (int)$_GET['del_meeting']); header("Location: ambassadors.php?tab=meetings&deleted=1"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';
    if ($form === 'settings') {
        $f = ['amb_open' => isset($_POST['amb_open']) ? '1' : '0', 'amb_show_public' => isset($_POST['amb_show_public']) ? '1' : '0'];
        foreach (['amb_intro', 'amb_proposal_url', 'amb_proposal_note', 'amb_notify_email', 'amb_interests', 'amb_ways'] as $k) $f[$k] = trim($_POST[$k] ?? '');
        if ($f['amb_notify_email'] !== '' && !filter_var($f['amb_notify_email'], FILTER_VALIDATE_EMAIL)) { $error = "Enter a valid notification email."; $tab = 'settings'; }
        elseif ($f['amb_proposal_url'] !== '' && !preg_match('#^https?://#i', $f['amb_proposal_url'])) { $error = "The proposal link must start with https://"; $tab = 'settings'; }
        else { foreach ($f as $k => $v) out_save_setting($conn, $k, $v); header("Location: ambassadors.php?tab=settings&saved=1"); exit; }
    } elseif ($form === 'app') {
        $id = (int)($_POST['id'] ?? 0); $a = $conn->query("SELECT * FROM ambassadors WHERE id=$id")->fetch_assoc();
        if ($a) {
            $act = $_POST['act'] ?? 'save'; $notes = trim($_POST['notes'] ?? ''); $pub = isset($_POST['is_public']) ? 1 : 0;
            $status = $a['status']; if ($act === 'approve') $status = 'approved'; elseif ($act === 'decline') $status = 'declined'; elseif ($act === 'pending') $status = 'pending';
            $appr = $status === 'approved' ? ($a['approved_at'] ?: date('Y-m-d H:i:s')) : null;
            $s = $conn->prepare("UPDATE ambassadors SET status=?, notes=?, is_public=?, approved_at=? WHERE id=?"); $s->bind_param("ssisi", $status, $notes, $pub, $appr, $id); $s->execute();
            if ($status !== $a['status'] && !empty($_POST['send_email'])) {
                $msg = trim($_POST['message'] ?? ''); $nm = out_next_meeting($conn);
                if ($status === 'approved') {
                    $body = "Hello " . $a['name'] . ",\n\nWelcome to the NUFA Ambassador team! Ambassadors lobby for NUFA, share our proposal with possible donors, hold fundraising and meet the NUFA Board every month."
                        . ($S['amb_proposal_url'] !== '' ? "\n\nThe NUFA proposal and donor pack: " . $S['amb_proposal_url'] : "\n\n" . $S['amb_proposal_note'])
                        . ($nm ? "\n\nNext meeting with the Board: " . date('l, j F Y', strtotime($nm['meet_date'])) . ($nm['meet_time'] !== '' ? ' · ' . $nm['meet_time'] : '') . ($nm['link'] !== '' ? "\nJoin: " . $nm['link'] : '') : "\n\nWe will send you the date of the next monthly meeting soon.")
                        . ($msg !== '' ? "\n\n" . $msg : '');
                    mem_send($conn, null, 'ambassador-approved', $a['email'], "Welcome, NUFA Ambassador", $body);
                } elseif ($status === 'declined') mem_send($conn, null, 'ambassador-declined', $a['email'], "Your NUFA Ambassador application", "Hello " . $a['name'] . ",\n\nThank you for offering to be a NUFA Ambassador. We are not able to take your application forward at this time, but we are grateful for your interest." . ($msg !== '' ? "\n\n" . $msg : ''));
            }
            header("Location: ambassadors.php?view=$id&saved=1"); exit;
        }
    } elseif ($form === 'meeting') {
        $t = trim($_POST['title'] ?? ''); $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['meet_date'] ?? '') ? $_POST['meet_date'] : null; $tm = trim($_POST['meet_time'] ?? ''); $l = trim($_POST['link'] ?? ''); $ag = trim($_POST['agenda'] ?? '');
        if ($t === '' || !$d) $error = "Enter the meeting title and date."; elseif ($l !== '' && !preg_match('#^https?://#i', $l)) $error = "The meeting link must start with https://";
        if (!$error) { $s = $conn->prepare("INSERT INTO ambassador_meetings (title, meet_date, meet_time, link, agenda) VALUES (?,?,?,?,?)"); $s->bind_param("sssss", $t, $d, $tm, $l, $ag); $s->execute(); header("Location: ambassadors.php?tab=meetings&saved=1"); exit; }
        $tab = 'meetings';
    } elseif ($form === 'invite') {
        $mid = (int)($_POST['meeting'] ?? 0); $m = $conn->query("SELECT * FROM ambassador_meetings WHERE id=$mid")->fetch_assoc(); $n = 0;
        if ($m) {
            foreach ($conn->query("SELECT name, email FROM ambassadors WHERE status='approved'")->fetch_all(MYSQLI_ASSOC) as $r) {
                mem_send($conn, null, 'ambassador-meeting', $r['email'], "Ambassadors’ meeting with the NUFA Board: " . date('j F Y', strtotime($m['meet_date'])), "Hello " . $r['name'] . ",\n\nYou are invited to the monthly NUFA Ambassadors’ meeting with the Board.\n\n" . $m['title'] . "\nDate: " . date('l, j F Y', strtotime($m['meet_date'])) . ($m['meet_time'] !== '' ? "\nTime: " . $m['meet_time'] : '') . ($m['link'] !== '' ? "\nJoin: " . $m['link'] : '') . ($m['agenda'] !== '' ? "\n\nAgenda:\n" . $m['agenda'] : ''), $m['link'] ?: null); $n++;
            }
            $conn->query("UPDATE ambassador_meetings SET invited_at=NOW(), invited_count=$n WHERE id=$mid");
        }
        header("Location: ambassadors.php?tab=meetings&invited=$n"); exit;
    }
}
$counts = ['pending' => 0, 'approved' => 0, 'declined' => 0]; foreach ($conn->query("SELECT status, COUNT(*) c FROM ambassadors GROUP BY status")->fetch_all(MYSQLI_ASSOC) as $c) $counts[$c['status']] = (int)$c['c'];
$st = in_array($_GET['status'] ?? '', ['pending', 'approved', 'declined'], true) ? $_GET['status'] : '';
$A = $view ? $conn->query("SELECT * FROM ambassadors WHERE id=$view")->fetch_assoc() : null;
$rows = $tab === 'apps' ? $conn->query("SELECT * FROM ambassadors" . ($st ? " WHERE status='$st'" : '') . " ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC) : [];
if (!$A && $tab === 'apps' && nufa_export_format() !== '') {
    $d = array_map(fn($r) => [$r['ref'], $r['name'], $r['email'], $r['phone'], $r['country'], $r['city'], $r['organisation'], $r['interests'], $r['support_ways'], $r['availability'], $r['status'], date('d M Y', strtotime($r['created_at']))], $rows);
    nufa_export_send(nufa_export_format(), 'NUFA ambassadors', ['Ref', 'Name', 'Email', 'Phone', 'Country', 'City', 'Organisation', 'Interests', 'Ways to support', 'Availability', 'Status', 'Applied'], $d, 'nufa-ambassadors');
}
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><?php foreach ($tabs as $k => $l): ?><a href="ambassadors.php?tab=<?php echo $k; ?>" class="<?php echo ($tab === $k && !$A) ? 'active' : ''; ?>"><?php echo $l; ?><?php echo $k === 'apps' && $counts['pending'] ? ' (' . $counts['pending'] . ' new)' : ''; ?></a><?php endforeach; ?><a href="../ambassadors.php" target="_blank">View the page ↗</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Removed.</div><?php endif; ?><?php if (isset($_GET['invited'])): ?><div class="admin-alert ok">Invitation emailed to <?php echo (int)$_GET['invited']; ?> ambassador(s).</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>

<?php if ($A): ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($A['name']); ?> <span class="admin-badge <?php echo $A['status'] === 'approved' ? '' : 'gray'; ?>"><?php echo $e(ucfirst($A['status'])); ?></span> <small><?php echo $e($A['ref']); ?></small></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="ambassadors.php">← All applications</a></div>
  <table class="admin-table" data-noexport><tbody>
    <tr><th>Contact</th><td><?php echo $e($A['email']); ?> · <?php echo $e($A['phone']); ?> · <a href="https://wa.me/<?php echo $e(preg_replace('/\D/', '', $A['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a></td></tr>
    <tr><th>Lives in</th><td><?php echo $e($A['country']); ?><?php echo $A['city'] ? ', ' . $e($A['city']) : ''; ?></td></tr>
    <tr><th>Occupation</th><td><?php echo $e(trim($A['occupation'] . ' ' . ($A['organisation'] ? '· ' . $A['organisation'] : ''))) ?: '—'; ?><?php echo $A['profile_url'] ? ' · <a href="' . $e($A['profile_url']) . '" target="_blank" rel="noopener">Profile ↗</a>' : ''; ?></td></tr>
    <tr><th>Interests</th><td><?php echo $e(implode(', ', out_split($A['interests']))); ?></td></tr>
    <tr><th>Can support by</th><td><?php echo $e(implode('; ', out_split($A['support_ways']))); ?></td></tr>
    <tr><th>Why</th><td style="white-space:pre-line"><?php echo $e($A['motivation']); ?></td></tr>
    <tr><th>Network</th><td style="white-space:pre-line"><?php echo $e($A['network'] ?: '—'); ?></td></tr>
    <tr><th>Fundraising idea</th><td style="white-space:pre-line"><?php echo $e($A['fundraising_idea'] ?: '—'); ?></td></tr>
    <tr><th>Availability</th><td><?php echo $e($A['availability']); ?> · <?php echo $e($A['timezone'] ?: 'time zone not given'); ?> · best: <?php echo $e($A['best_time'] ?: '—'); ?></td></tr>
    <tr><th>Applied</th><td><?php echo $e(date('j M Y H:i', strtotime($A['created_at']))); ?></td></tr></tbody></table>
  <form method="post" class="admin-form" style="margin-top:14px"><input type="hidden" name="form" value="app"><input type="hidden" name="id" value="<?php echo $A['id']; ?>">
    <div class="field"><label>Internal notes</label><textarea name="notes" rows="3"><?php echo $e($A['notes']); ?></textarea></div>
    <div class="field"><label>Message to include in the email (optional)</label><textarea name="message" rows="2"></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px"><label><input type="checkbox" name="send_email" value="1" checked> Email the applicant (approval includes the proposal link and next meeting)</label><label><input type="checkbox" name="is_public" <?php echo $A['is_public'] ? 'checked' : ''; ?>> Show name and country on the Ambassadors page</label></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand" name="act" value="approve">Approve as ambassador</button><button class="admin-btn admin-btn-danger" name="act" value="decline">Decline</button><button class="admin-btn admin-btn-line" name="act" value="save">Save notes</button><a class="admin-btn admin-btn-danger" href="ambassadors.php?delete=<?php echo $A['id']; ?>" data-confirm="Delete this application?">Delete</a></div></form></div>

<?php elseif ($tab === 'apps'): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Ambassador applications (<?php echo count($rows); ?>)</h2><div class="ax-seg"><?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'] as $k => $l): ?><a href="ambassadors.php<?php echo $k ? '?status=' . $k : ''; ?>" class="<?php echo $st === $k ? 'on' : ''; ?>"><?php echo $l; ?><?php echo $k && $counts[$k] ? ' (' . $counts[$k] . ')' : ''; ?></a><?php endforeach; ?></div></div>
  <?php echo nufa_export_menu('ambassadors.php?' . http_build_query(array_filter(['status' => $st]))); ?>
  <?php if (!$rows): ?><p class="admin-help">No applications yet.</p><?php else: ?><table class="admin-table" data-noexport><thead><tr><th>Ref</th><th>Name</th><th>Country</th><th>Interests</th><th>Ways to support</th><th>Status</th><th>Applied</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?php echo $e($r['ref']); ?></td><td><b><?php echo $e($r['name']); ?></b><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e($r['country']); ?></td><td><?php echo $e(implode(', ', array_slice(out_split($r['interests']), 0, 3))); ?></td><td><?php echo $e(implode(', ', array_slice(out_split($r['support_ways']), 0, 2))); ?></td><td><span class="admin-badge <?php echo $r['status'] === 'approved' ? '' : 'gray'; ?>"><?php echo $e(ucfirst($r['status'])); ?></span></td><td><?php echo $e(date('j M Y', strtotime($r['created_at']))); ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="ambassadors.php?view=<?php echo $r['id']; ?>">Review</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>

<?php elseif ($tab === 'meetings'): $ms = $conn->query("SELECT * FROM ambassador_meetings ORDER BY meet_date DESC")->fetch_all(MYSQLI_ASSOC); ?>
<div class="admin-card"><div class="admin-card-head"><h2>Schedule a meeting with the Board</h2><span class="admin-help">Approved ambassadors: <?php echo $counts['approved']; ?>. After saving you can email the invitation to all of them.</span></div>
  <form method="post" class="admin-form"><input type="hidden" name="form" value="meeting"><div class="admin-form-grid"><div class="field"><label>Title</label><input name="title" required placeholder="e.g. Monthly Ambassadors’ meeting"></div><div class="field"><label>Date</label><input type="date" name="meet_date" required></div><div class="field"><label>Time (include time zone)</label><input name="meet_time" placeholder="e.g. 7:00 PM EAT"></div><div class="field"><label>Meeting link (Zoom, Google Meet…)</label><input type="url" name="link" placeholder="https://"></div></div>
    <div class="field"><label>Agenda</label><textarea name="agenda" rows="3"></textarea></div><button class="admin-btn admin-btn-brand">Add meeting</button></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Meetings (<?php echo count($ms); ?>)</h2></div>
  <?php if (!$ms): ?><p class="admin-help">No meetings scheduled.</p><?php else: ?><table class="admin-table" data-noexport><thead><tr><th>Meeting</th><th>Date</th><th>Invitation</th><th></th></tr></thead><tbody><?php foreach ($ms as $m): ?><tr><td><b><?php echo $e($m['title']); ?></b><br><small><?php echo $e($m['meet_time']); ?></small></td><td><?php echo $e(date('j M Y', strtotime($m['meet_date']))); ?></td><td><?php echo $m['invited_at'] ? 'Sent to ' . (int)$m['invited_count'] . ' on ' . $e(date('j M', strtotime($m['invited_at']))) : '—'; ?></td>
    <td style="white-space:nowrap"><form method="post" style="display:inline"><input type="hidden" name="form" value="invite"><input type="hidden" name="meeting" value="<?php echo $m['id']; ?>"><button class="admin-btn admin-btn-brand admin-btn-sm" data-confirm="Email this invitation to all approved ambassadors?"><?php echo $m['invited_at'] ? 'Send again' : 'Email invitation'; ?></button></form> <a class="admin-btn admin-btn-danger admin-btn-sm" href="ambassadors.php?del_meeting=<?php echo $m['id']; ?>" data-confirm="Delete this meeting?">Delete</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>

<?php else: ?>
<form method="post" class="admin-card admin-form"><input type="hidden" name="form" value="settings"><div class="admin-card-head"><h2>Ambassador settings</h2></div>
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px"><label><input type="checkbox" name="amb_open" <?php echo $S['amb_open'] === '1' ? 'checked' : ''; ?>> Applications are open</label><label><input type="checkbox" name="amb_show_public" <?php echo $S['amb_show_public'] === '1' ? 'checked' : ''; ?>> Show ambassadors you mark as public on the page</label></div>
  <div class="field"><label>Introduction on the Ambassadors page</label><textarea name="amb_intro" rows="4"><?php echo $e($S['amb_intro']); ?></textarea></div>
  <div class="field"><label>Link to the NUFA proposal / donor pack (included in the approval email)</label><input type="url" name="amb_proposal_url" value="<?php echo $e($S['amb_proposal_url']); ?>" placeholder="https://… (Google Drive, Dropbox or a PDF on your site)"><small class="admin-help">Use a link you control. Leave empty to send the note below instead.</small></div>
  <div class="field"><label>Note used when there is no proposal link</label><input name="amb_proposal_note" value="<?php echo $e($S['amb_proposal_note']); ?>"></div>
  <div class="field"><label>Notification email for new applications</label><input type="email" name="amb_notify_email" value="<?php echo $e($S['amb_notify_email']); ?>" placeholder="Leave empty to use the membership notification address"></div>
  <div class="admin-form-grid"><div class="field"><label>Areas of interest (one per line)</label><textarea name="amb_interests" rows="9"><?php echo $e($S['amb_interests']); ?></textarea></div><div class="field"><label>Ways to support (one per line)</label><textarea name="amb_ways" rows="9"><?php echo $e($S['amb_ways']); ?></textarea></div></div>
  <button class="admin-btn admin-btn-brand">Save settings</button></form>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
