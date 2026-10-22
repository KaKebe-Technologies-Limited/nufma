<?php
$adminTitle = "Awards build-up";
$adminNav = "awardacts";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/outreach.php";
require_once __DIR__ . "/../includes/award-events.php";
require_once __DIR__ . "/../includes/export.php";
out_install($conn); aev_install($conn);
$S = out_settings($conn); $e = fn($v) => htmlspecialchars((string)$v); $error = '';
$tabs = ['acts' => 'Performers', 'workshops' => 'Workshops', 'settings' => 'Settings'];
$tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'acts';
$view = (int)($_GET['view'] ?? 0);
$events = $conn->query("SELECT id, title, event_date, date_confirmed, date_note FROM award_events ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$evTitle = array_column($events, 'title', 'id');
$stLabel = ['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined', 'waitlist' => 'Waitlist'];

if (isset($_GET['del_act'])) { $id = (int)$_GET['del_act']; $r = $conn->query("SELECT photo FROM award_acts WHERE id=$id")->fetch_assoc(); if ($r && strpos($r['photo'], 'uploads/outreach/') === 0) @unlink(__DIR__ . "/../assets/images/" . $r['photo']); $conn->query("DELETE FROM award_acts WHERE id=$id"); header("Location: award-acts.php?deleted=1"); exit; }
if (isset($_GET['del_ws'])) { $conn->query("DELETE FROM award_workshops WHERE id=" . (int)$_GET['del_ws']); header("Location: award-acts.php?tab=workshops&deleted=1"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';
    if ($form === 'settings') {
        $f = ['bld_acts_open' => isset($_POST['bld_acts_open']) ? '1' : '0', 'bld_workshops_open' => isset($_POST['bld_workshops_open']) ? '1' : '0'];
        foreach (['bld_intro', 'bld_acts_intro', 'bld_workshop_intro', 'bld_notify_email', 'bld_act_types', 'bld_contributions'] as $k) $f[$k] = trim($_POST[$k] ?? '');
        if ($f['bld_notify_email'] !== '' && !filter_var($f['bld_notify_email'], FILTER_VALIDATE_EMAIL)) { $error = "Enter a valid notification email."; $tab = 'settings'; }
        else { foreach ($f as $k => $v) out_save_setting($conn, $k, $v); header("Location: award-acts.php?tab=settings&saved=1"); exit; }
    } elseif ($form === 'act') {
        $id = (int)($_POST['id'] ?? 0); $a = $conn->query("SELECT * FROM award_acts WHERE id=$id")->fetch_assoc();
        if ($a) {
            $act = $_POST['act'] ?? 'save'; $notes = trim($_POST['notes'] ?? ''); $pub = isset($_POST['is_public']) ? 1 : 0; $ev = (int)($_POST['assigned_event_id'] ?? 0); if (!isset($evTitle[$ev])) $ev = 0; $sort = (int)($_POST['sort_order'] ?? 0); $msg = trim($_POST['message'] ?? '');
            $status = $a['status']; if ($act === 'approve') $status = 'approved'; elseif ($act === 'decline') $status = 'declined'; elseif ($act === 'waitlist') $status = 'waitlist';
            if ($status === 'approved' && !$ev) $error = "Choose which event this act is performing at before approving.";
            if (!$error) {
                if ($status !== 'approved') $pub = 0;
                $s = $conn->prepare("UPDATE award_acts SET status=?, notes=?, is_public=?, assigned_event_id=?, sort_order=? WHERE id=?"); $s->bind_param("ssiiii", $status, $notes, $pub, $ev, $sort, $id); $s->execute();
                if ($status !== $a['status'] && !empty($_POST['send_email'])) {
                    if ($status === 'approved') mem_send($conn, null, 'act-approved', $a['email'], "You are on the NUFA Awards line-up: " . $a['name'], "Hello " . $a['contact_name'] . ",\n\nWe would love you to showcase at " . $evTitle[$ev] . ". Our team will be in touch about the date, time, sound check and logistics." . ($msg !== '' ? "\n\n" . $msg : ''));
                    elseif ($status === 'waitlist') mem_send($conn, null, 'act-waitlist', $a['email'], "Your NUFA Awards application: " . $a['name'], "Hello " . $a['contact_name'] . ",\n\nThank you for applying. We have placed your act on our waiting list and will contact you if a slot opens." . ($msg !== '' ? "\n\n" . $msg : ''));
                    else mem_send($conn, null, 'act-declined', $a['email'], "Your NUFA Awards application: " . $a['name'], "Hello " . $a['contact_name'] . ",\n\nThank you for applying to showcase at the NUFA Awards. We are not able to offer a slot this time, and we appreciate your interest." . ($msg !== '' ? "\n\n" . $msg : ''));
                }
                header("Location: award-acts.php?view=$id&saved=1"); exit;
            }
            $view = $id;
        }
    } elseif ($form === 'ws') {
        $id = (int)($_POST['id'] ?? 0); $a = $conn->query("SELECT * FROM award_workshops WHERE id=$id")->fetch_assoc();
        if ($a) {
            $act = $_POST['act'] ?? 'save'; $notes = trim($_POST['notes'] ?? ''); $pub = isset($_POST['is_public']) ? 1 : 0; $msg = trim($_POST['message'] ?? '');
            $cd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['confirmed_date'] ?? '') ? $_POST['confirmed_date'] : null;
            $status = $a['status']; if ($act === 'approve') $status = 'approved'; elseif ($act === 'decline') $status = 'declined'; elseif ($act === 'waitlist') $status = 'waitlist';
            if ($status !== 'approved') $pub = 0;
            $s = $conn->prepare("UPDATE award_workshops SET status=?, notes=?, is_public=?, confirmed_date=? WHERE id=?"); $s->bind_param("ssisi", $status, $notes, $pub, $cd, $id); $s->execute();
            if ($status !== $a['status'] && !empty($_POST['send_email'])) {
                if ($status === 'approved') mem_send($conn, null, 'ws-approved', $a['email'], "Your NUFA Awards workshop is approved: " . $a['title'], "Hello " . $a['contact_name'] . ",\n\nThank you — we would be glad to work with you on “" . $a['title'] . "” in " . $a['town'] . ". " . ($cd ? "The date we have confirmed is " . date('l, j F Y', strtotime($cd)) . "." : "We will confirm the date with you.") . " Our team will contact you about mobilising the " . (int)$a['filmmakers_needed'] . " filmmakers and the next steps." . ($msg !== '' ? "\n\n" . $msg : ''));
                else mem_send($conn, null, 'ws-' . $status, $a['email'], "Your NUFA Awards workshop proposal: " . $a['title'], "Hello " . $a['contact_name'] . ",\n\nThank you for proposing “" . $a['title'] . "”. " . ($status === 'waitlist' ? "We have placed it on hold and will come back to you." : "We are not able to go ahead with it at this time, and we appreciate your offer.") . ($msg !== '' ? "\n\n" . $msg : ''));
            }
            header("Location: award-acts.php?tab=workshops&view=$id&saved=1"); exit;
        }
    }
}
$cnt = []; foreach (['award_acts' => 'acts', 'award_workshops' => 'workshops'] as $t => $k) { $cnt[$k] = (int)$conn->query("SELECT COUNT(*) FROM $t WHERE status='pending'")->fetch_row()[0]; }
$st = array_key_exists($_GET['status'] ?? '', $stLabel) ? $_GET['status'] : '';
$A = null; $W = null;
if ($view && $tab === 'acts') $A = $conn->query("SELECT * FROM award_acts WHERE id=$view")->fetch_assoc();
if ($view && $tab === 'workshops') $W = $conn->query("SELECT * FROM award_workshops WHERE id=$view")->fetch_assoc();
$rows = [];
if (!$A && !$W && $tab === 'acts') $rows = $conn->query("SELECT * FROM award_acts" . ($st ? " WHERE status='$st'" : '') . " ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
if (!$A && !$W && $tab === 'workshops') $rows = $conn->query("SELECT * FROM award_workshops" . ($st ? " WHERE status='$st'" : '') . " ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
$badge = fn($s) => '<span class="admin-badge ' . ($s === 'approved' ? '' : 'gray') . '">' . $e($stLabel[$s] ?? $s) . '</span>';
?>
<div class="mem-tabs"><?php foreach ($tabs as $k => $l): ?><a href="award-acts.php?tab=<?php echo $k; ?>" class="<?php echo ($tab === $k && !$A && !$W) ? 'active' : ''; ?>"><?php echo $l; ?><?php echo isset($cnt[$k]) && $cnt[$k] ? ' (' . $cnt[$k] . ' new)' : ''; ?></a><?php endforeach; ?><a href="award-events.php">Awards pre-events</a><a href="../awards.php#buildup" target="_blank">View on the website ↗</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>

<?php if ($A): $want = array_filter(array_map('intval', explode(',', $A['event_ids']))); ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($A['name']); ?> <?php echo $badge($A['status']); ?> <small><?php echo $e($A['ref']); ?></small></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="award-acts.php">← All performers</a></div>
  <?php if ($A['photo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $A['photo'])): ?><img src="../assets/images/<?php echo $e($A['photo']); ?>" alt="" style="width:120px;border-radius:12px;float:right;margin-left:14px"><?php endif; ?>
  <table class="admin-table" data-noexport><tbody>
    <tr><th>Type</th><td><?php echo $e($A['act_type']); ?> · <?php echo (int)$A['performers']; ?> performer(s) · <?php echo (int)$A['duration_min']; ?> min</td></tr>
    <tr><th>Wants to perform at</th><td><?php echo $e(implode(', ', array_map(fn($i) => $evTitle[$i] ?? '?', $want))); ?></td></tr>
    <tr><th>Performance</th><td style="white-space:pre-line"><?php echo $e($A['description']); ?></td></tr>
    <tr><th>Sample</th><td><a href="<?php echo $e($A['samples_url']); ?>" target="_blank" rel="noopener nofollow"><?php echo $e($A['samples_url']); ?> ↗</a></td></tr>
    <tr><th>Tech needs</th><td><?php echo $e($A['tech_needs'] ?: '—'); ?></td></tr>
    <tr><th>Fee</th><td><?php echo $e(['free' => 'Free / exposure', 'negotiable' => 'Open to discussion', 'fee' => 'Charges a fee'][$A['fee_option']] ?? ''); ?> <?php echo $e($A['fee_amount']); ?></td></tr>
    <tr><th>About</th><td style="white-space:pre-line"><?php echo $e($A['bio']); ?></td></tr>
    <tr><th>Contact</th><td><?php echo $e($A['contact_name']); ?> · <?php echo $e($A['email']); ?> · <?php echo $e($A['phone']); ?> · <a href="https://wa.me/<?php echo $e(preg_replace('/\D/', '', $A['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a><?php echo $A['social_url'] ? ' · <a href="' . $e($A['social_url']) . '" target="_blank" rel="noopener">Social ↗</a>' : ''; ?></td></tr></tbody></table>
  <form method="post" class="admin-form" style="margin-top:14px"><input type="hidden" name="form" value="act"><input type="hidden" name="id" value="<?php echo $A['id']; ?>">
    <div class="admin-form-grid"><div class="field"><label>Performing at (required to approve)</label><select name="assigned_event_id"><option value="0">— choose —</option><?php foreach ($events as $ev): ?><option value="<?php echo $ev['id']; ?>" <?php echo (int)($A['assigned_event_id'] ?: ($want ? reset($want) : 0)) === (int)$ev['id'] ? 'selected' : ''; ?>><?php echo $e($ev['title']); ?></option><?php endforeach; ?></select></div><div class="field"><label>Order in the line-up</label><input type="number" name="sort_order" value="<?php echo (int)$A['sort_order']; ?>"></div></div>
    <div class="field"><label>Internal notes</label><textarea name="notes" rows="2"><?php echo $e($A['notes']); ?></textarea></div><div class="field"><label>Message to include in the email (optional)</label><textarea name="message" rows="2"></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px"><label><input type="checkbox" name="send_email" value="1" checked> Email the applicant about the decision</label><label><input type="checkbox" name="is_public" <?php echo $A['is_public'] ? 'checked' : ''; ?>> Announce on the NUFA Awards page line-up (when approved)</label></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand" name="act" value="approve">Approve for the line-up</button><button class="admin-btn admin-btn-line" name="act" value="waitlist">Waitlist</button><button class="admin-btn admin-btn-danger" name="act" value="decline">Decline</button><button class="admin-btn admin-btn-line" name="act" value="save">Save</button><a class="admin-btn admin-btn-danger" href="award-acts.php?del_act=<?php echo $A['id']; ?>" data-confirm="Delete this application?">Delete</a></div></form></div>

<?php elseif ($W): ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($W['title']); ?> <?php echo $badge($W['status']); ?> <small><?php echo $e($W['ref']); ?></small></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="award-acts.php?tab=workshops">← All workshops</a></div>
  <table class="admin-table" data-noexport><tbody>
    <tr><th>Host</th><td><?php echo $e($W['host_name']); ?> (<?php echo $e($W['host_type']); ?>) · <?php echo $e($W['contact_name']); ?> · <?php echo $e($W['email']); ?> · <?php echo $e($W['phone']); ?> · <a href="https://wa.me/<?php echo $e(preg_replace('/\D/', '', $W['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a></td></tr>
    <tr><th>Workshop</th><td style="white-space:pre-line"><?php echo $e($W['description']); ?><br><small>For: <?php echo $e($W['audience'] ?: '—'); ?></small></td></tr>
    <tr><th>When</th><td><?php echo $W['proposed_date'] ? $e(date('j M Y', strtotime($W['proposed_date']))) . ($W['proposed_end'] && $W['proposed_end'] !== $W['proposed_date'] ? ' – ' . $e(date('j M Y', strtotime($W['proposed_end']))) : '') : 'Not given'; ?> · <?php echo $e($W['duration_text']); ?></td></tr>
    <tr><th>Venue</th><td><?php echo $e($W['venue']); ?>, <?php echo $e($W['town']); ?> · capacity <?php echo (int)$W['venue_capacity'] ?: '—'; ?></td></tr>
    <tr><th>Filmmakers needed</th><td><b><?php echo (int)$W['filmmakers_needed']; ?></b></td></tr>
    <tr><th>Contribution</th><td><?php echo $e(implode(', ', out_split($W['contribution_types']))); ?><br><span style="white-space:pre-line"><?php echo $e($W['contribution']); ?></span></td></tr>
    <tr><th>Needs from NUFA</th><td style="white-space:pre-line"><?php echo $e($W['needs_from_nufa'] ?: '—'); ?></td></tr></tbody></table>
  <form method="post" class="admin-form" style="margin-top:14px"><input type="hidden" name="form" value="ws"><input type="hidden" name="id" value="<?php echo $W['id']; ?>">
    <div class="field"><label>Confirmed date (shown publicly when approved)</label><input type="date" name="confirmed_date" value="<?php echo $e($W['confirmed_date']); ?>"></div>
    <div class="field"><label>Internal notes</label><textarea name="notes" rows="2"><?php echo $e($W['notes']); ?></textarea></div><div class="field"><label>Message to include in the email (optional)</label><textarea name="message" rows="2"></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px"><label><input type="checkbox" name="send_email" value="1" checked> Email the host about the decision</label><label><input type="checkbox" name="is_public" <?php echo $W['is_public'] ? 'checked' : ''; ?>> Announce on the NUFA Awards page (when approved)</label></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand" name="act" value="approve">Approve</button><button class="admin-btn admin-btn-line" name="act" value="waitlist">Hold</button><button class="admin-btn admin-btn-danger" name="act" value="decline">Decline</button><button class="admin-btn admin-btn-line" name="act" value="save">Save</button><a class="admin-btn admin-btn-danger" href="award-acts.php?del_ws=<?php echo $W['id']; ?>" data-confirm="Delete this proposal?">Delete</a></div></form></div>

<?php elseif ($tab === 'settings'): ?>
<form method="post" class="admin-card admin-form"><input type="hidden" name="form" value="settings"><div class="admin-card-head"><h2>Awards build-up settings</h2></div>
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px"><label><input type="checkbox" name="bld_acts_open" <?php echo $S['bld_acts_open'] === '1' ? 'checked' : ''; ?>> Performer applications are open</label><label><input type="checkbox" name="bld_workshops_open" <?php echo $S['bld_workshops_open'] === '1' ? 'checked' : ''; ?>> Workshop proposals are open</label></div>
  <div class="field"><label>Build-up section introduction (NUFA Awards page)</label><textarea name="bld_intro" rows="3"><?php echo $e($S['bld_intro']); ?></textarea></div>
  <div class="field"><label>Performer page introduction</label><textarea name="bld_acts_intro" rows="2"><?php echo $e($S['bld_acts_intro']); ?></textarea></div>
  <div class="field"><label>Workshop page introduction</label><textarea name="bld_workshop_intro" rows="2"><?php echo $e($S['bld_workshop_intro']); ?></textarea></div>
  <div class="field"><label>Notification email</label><input type="email" name="bld_notify_email" value="<?php echo $e($S['bld_notify_email']); ?>" placeholder="Leave empty to use the membership notification address"></div>
  <div class="admin-form-grid"><div class="field"><label>Types of act (one per line)</label><textarea name="bld_act_types" rows="8"><?php echo $e($S['bld_act_types']); ?></textarea></div><div class="field"><label>Workshop contribution options (one per line)</label><textarea name="bld_contributions" rows="8"><?php echo $e($S['bld_contributions']); ?></textarea></div></div>
  <button class="admin-btn admin-btn-brand">Save settings</button></form>

<?php else: ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $tab === 'acts' ? 'Performer applications' : 'Workshop proposals'; ?> (<?php echo count($rows); ?>)</h2><div class="ax-seg"><?php foreach (['' => 'All'] + $stLabel as $k => $l): ?><a href="award-acts.php?tab=<?php echo $tab; ?><?php echo $k ? '&status=' . $k : ''; ?>" class="<?php echo $st === $k ? 'on' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?></div></div>
  <?php if (!$rows): ?><p class="admin-help">Nothing here yet.</p>
  <?php elseif ($tab === 'acts'): ?><table class="admin-table" data-export="Performer applications"><thead><tr><th>Ref</th><th>Act</th><th>Type</th><th>Wants</th><th>Min</th><th>Status</th><th>Applied</th><th></th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?php echo $e($r['ref']); ?></td><td><b><?php echo $e($r['name']); ?></b><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e($r['act_type']); ?></td><td><?php echo $e(implode(', ', array_map(fn($i) => $evTitle[(int)$i] ?? '?', array_filter(explode(',', $r['event_ids']))))); ?></td><td><?php echo (int)$r['duration_min']; ?></td><td><?php echo $badge($r['status']); ?><?php echo $r['is_public'] ? ' <span class="admin-badge">Public</span>' : ''; ?></td><td><?php echo $e(date('j M Y', strtotime($r['created_at']))); ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="award-acts.php?view=<?php echo $r['id']; ?>">Review</a></td></tr><?php endforeach; ?></tbody></table>
  <?php else: ?><table class="admin-table" data-export="Workshop proposals"><thead><tr><th>Ref</th><th>Workshop</th><th>Host</th><th>Venue</th><th>Filmmakers</th><th>Contribution</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?php echo $e($r['ref']); ?></td><td><b><?php echo $e($r['title']); ?></b><br><small><?php echo $r['proposed_date'] ? $e(date('j M Y', strtotime($r['proposed_date']))) : 'date TBC'; ?></small></td><td><?php echo $e($r['host_name']); ?></td><td><?php echo $e($r['venue']); ?>, <?php echo $e($r['town']); ?></td><td><?php echo (int)$r['filmmakers_needed']; ?></td><td><?php echo $e(implode(', ', array_slice(out_split($r['contribution_types']), 0, 3))); ?></td><td><?php echo $badge($r['status']); ?><?php echo $r['is_public'] ? ' <span class="admin-badge">Public</span>' : ''; ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="award-acts.php?tab=workshops&view=<?php echo $r['id']; ?>">Review</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
