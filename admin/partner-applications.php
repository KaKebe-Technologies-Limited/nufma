<?php
$adminTitle = "Partnership applications";
$adminNav = "partnerapps";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/partner-apps.php";
pa_install($conn);

$e = fn($v) => htmlspecialchars((string)$v);
$msg = ''; $err = '';
$tab = ($_GET['tab'] ?? '') === 'settings' ? 'settings' : 'apps';
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$cats = nufa_list(nufa_site_settings($conn), 'list_partner_categories');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; $r = $conn->query("SELECT logo, partner_id FROM partner_applications WHERE id=$id")->fetch_assoc();
    if ($r && $r['logo']) @unlink(pa_storage() . basename($r['logo']));
    $conn->query("DELETE FROM partner_applications WHERE id=$id"); header("Location: partner-applications.php?deleted=1"); exit;
}
if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT ref_no, status, applicant_type, org_name, contact_name, role_title, email, phone, website, location, activity, partnership_type, contribution, contribution_value, currency, timing, benefits, commitment_text, commitment_due, commitment_met_at, partner_id, created_at FROM partner_applications ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="partnership-applications.csv"');
    $o = fopen('php://output', 'w'); if ($rows) fputcsv($o, array_keys($rows[0])); foreach ($rows as $r) fputcsv($o, $r); fclose($o); exit;
}

$load = fn($id) => $conn->query("SELECT * FROM partner_applications WHERE id=" . (int)$id)->fetch_assoc();
$mail = function ($kind, $a, $extra = []) use ($conn) {
    $S = pa_settings($conn); $v = pa_vars($a, $extra);
    return mem_send($conn, null, "partner-$kind", $a['email'], strtr($S["paapp_email_{$kind}_subject"], $v), strtr($S["paapp_email_{$kind}_body"], $v));
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0); $a = $id ? $load($id) : null; $S0 = pa_settings($conn);
    if ($a && $act === 'approve') {
        $due = trim($_POST['commitment_due'] ?? ''); $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) ? $due : null;
        $ct = mb_substr(trim($_POST['commitment_text'] ?? ''), 0, 2500); $cat = mb_substr(trim($_POST['publish_category'] ?? ''), 0, 80); $tier = mb_substr(trim($_POST['publish_tier'] ?? ''), 0, 100);
        $blurb = mb_substr(trim($_POST['publish_blurb'] ?? ''), 0, 500); $auto = isset($_POST['auto_publish']) ? 1 : 0; $already = isset($_POST['already_met']);
        if ($ct === '') $err = "State the commitment that has been agreed (what they will contribute) so it can be tracked.";
        else {
            $u = $conn->prepare("UPDATE partner_applications SET status='approved', approved_at=COALESCE(approved_at, NOW()), reject_reason='', commitment_text=?, commitment_due=?, publish_category=?, publish_tier=?, publish_blurb=?, auto_publish=? WHERE id=?");
            $u->bind_param("sssssii", $ct, $due, $cat, $tier, $blurb, $auto, $id); $u->execute(); $a = $load($id);
            $ok = $mail('approved', $a, ['{instructions}' => $S0['paapp_approved_instructions']]);
            $msg = "Application approved" . ($ok ? " and the applicant was emailed." : " (the email could not be sent from this server — it is saved in the email log).");
            if ($already) { $act = 'met'; }
        }
        $_GET['view'] = $id;
    } elseif ($a && $act === 'reject') {
        $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 500);
        if ($reason === '') $err = "Enter a reason for rejecting the application.";
        else { $u = $conn->prepare("UPDATE partner_applications SET status='rejected', reject_reason=? WHERE id=?"); $u->bind_param("si", $reason, $id); $u->execute(); pa_unpublish($conn, $a); $a = $load($id); $ok = $mail('rejected', $a); $msg = "Application rejected" . ($ok ? " and the applicant was emailed." : " (email not sent from this server)."); }
        $_GET['view'] = $id;
    }
    if ($a && $act === 'met' && $a['status'] === 'approved') {
        $conn->query("UPDATE partner_applications SET commitment_met_at=COALESCE(commitment_met_at, NOW()) WHERE id=$id"); $a = $load($id);
        if ((int)$a['auto_publish'] === 1) {
            $pid = pa_publish($conn, $a); $a = $load($id); $ok = $mail('published', $a);
            $msg = ($msg ? $msg . ' ' : '') . "Commitment recorded as met — " . pa_display($a) . " is now live in the Partners section of the website" . ($ok ? " and the partner was thanked by email." : ".");
        } else $msg = ($msg ? $msg . ' ' : '') . "Commitment recorded as met. Auto-publish is off for this partner — use “Publish on the website” when ready.";
        $_GET['view'] = $id;
    } elseif ($a && $act === 'publish' && $a['status'] === 'approved') {
        $logoNew = null;
        if (!empty($_FILES['logo']['name'])) { [$fn, $er] = mem_store_upload($_FILES['logo'], MEM_IMG_TYPES, 3 * 1048576, 'pa-logo', pa_storage()); if ($er) $err = "Logo: $er"; else { $logoNew = $fn; $conn->query("UPDATE partner_applications SET logo='" . $conn->real_escape_string($fn) . "' WHERE id=$id"); } }
        if (!$err) { $a = $load($id); if (!$a['commitment_met_at']) $conn->query("UPDATE partner_applications SET commitment_met_at=NOW() WHERE id=$id"); $a = $load($id); pa_publish($conn, $a, $logoNew); $a = $load($id); $msg = pa_display($a) . " is published in the Partners section."; }
        $_GET['view'] = $id;
    } elseif ($a && $act === 'unpublish') { pa_unpublish($conn, $a); $msg = "Removed from the Partners section (the application is kept)."; $_GET['view'] = $id; }
    elseif ($a && $act === 'note') { $u = $conn->prepare("UPDATE partner_applications SET admin_note=? WHERE id=?"); $n = mb_substr(trim($_POST['admin_note'] ?? ''), 0, 500); $u->bind_param("si", $n, $id); $u->execute(); $msg = "Saved."; $_GET['view'] = $id; }
    elseif ($act === 'settings') {
        $tab = 'settings';
        foreach (['paapp_intro', 'paapp_notify_email', 'paapp_approved_instructions', 'paapp_email_received_subject', 'paapp_email_received_body', 'paapp_email_approved_subject', 'paapp_email_approved_body', 'paapp_email_published_subject', 'paapp_email_published_body', 'paapp_email_rejected_subject', 'paapp_email_rejected_body'] as $k) $save($k, mb_substr(trim($_POST[$k] ?? ''), 0, 4000));
        $save('paapp_auto_publish', isset($_POST['paapp_auto_publish']) ? '1' : '0');
        if (trim($_POST['paapp_notify_email'] ?? '') !== '' && !filter_var(trim($_POST['paapp_notify_email']), FILTER_VALIDATE_EMAIL)) $err = "The notification email is not valid."; else $msg = "Settings saved.";
    }
}

$S = pa_settings($conn, true);
$view = (int)($_GET['view'] ?? 0); $a = $view ? $load($view) : null;
$filter = in_array($_GET['status'] ?? '', ['pending', 'awaiting', 'live', 'rejected'], true) ? $_GET['status'] : ''; $q = trim($_GET['q'] ?? '');
$cnt = $conn->query("SELECT SUM(status='pending') p, SUM(status='approved' AND partner_id=0) w, SUM(status='approved' AND partner_id>0) l, SUM(status='rejected') r FROM partner_applications")->fetch_assoc();
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="partners.php">← Partners</a><a href="partner-applications.php" class="<?php echo $tab === 'apps' ? 'active' : ''; ?>">Applications</a><a href="partner-applications.php?tab=settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">Form &amp; emails</a><a href="../partner-apply.php" target="_blank">View the form ↗</a><a href="../partners.php" target="_blank">View partners page ↗</a></div>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Application deleted. (A partner already published from it stays on the website — remove it in Partners.)</div><?php endif; ?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'settings'): ?>
<form method="post" class="admin-form"><input type="hidden" name="action" value="settings">
  <div class="admin-card"><div class="admin-card-head"><h2>The form</h2></div>
    <div class="field"><label>Introduction above the form</label><textarea name="paapp_intro" style="min-height:90px"><?php echo $e($S['paapp_intro']); ?></textarea></div>
    <label class="mem-check"><input type="checkbox" name="paapp_auto_publish" value="1" <?php echo $S['paapp_auto_publish'] === '1' ? 'checked' : ''; ?>> By default, publish a partner on the website automatically as soon as their commitment is marked as met</label>
    <div class="field" style="margin-top:10px"><label>Send new-application alerts to</label><input type="email" name="paapp_notify_email" value="<?php echo $e($S['paapp_notify_email']); ?>" placeholder="Defaults to the membership alert address"></div></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Emails</h2><span class="admin-help">Placeholders: {name} {ref} {type} {activity} {display} {commitment} {due_line} {reason} {site_url} {instructions}</span></div>
    <?php foreach (['received' => 'Application received', 'approved' => 'Approved (includes the agreed commitment)', 'published' => 'Commitment met — now listed on the website', 'rejected' => 'Rejected'] as $k => $l): ?>
    <h3 class="mem-h3"><?php echo $l; ?></h3><div class="field"><label>Subject</label><input name="paapp_email_<?php echo $k; ?>_subject" value="<?php echo $e($S["paapp_email_{$k}_subject"]); ?>"></div><div class="field"><label>Message</label><textarea name="paapp_email_<?php echo $k; ?>_body" style="min-height:120px"><?php echo $e($S["paapp_email_{$k}_body"]); ?></textarea></div>
    <?php endforeach; ?>
    <div class="field"><label>Instructions inserted into the approval email</label><textarea name="paapp_approved_instructions" style="min-height:70px"><?php echo $e($S['paapp_approved_instructions']); ?></textarea></div></div>
  <button class="admin-btn admin-btn-brand">Save settings</button>
</form>

<?php elseif ($a): $disp = pa_display($a); ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($disp); ?> <?php echo pa_badge($a); ?></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="partner-applications.php">← All applications</a></div>
  <p><span class="reg-badge-sm"><?php echo $e($a['ref_no']); ?></span> · <?php echo $e($a['applicant_type']); ?> · submitted <?php echo date('d M Y H:i', strtotime($a['created_at'])); ?></p>
  <?php if ($a['logo']): ?><div class="app-files"><a class="app-file" href="partner-file.php?id=<?php echo $a['id']; ?>" target="_blank"><img src="partner-file.php?id=<?php echo $a['id']; ?>" alt=""><b>Logo / photo</b></a></div><?php endif; ?>
  <h3 class="mem-h3">Applicant</h3><dl class="app-dl"><?php foreach ([['Organisation', 'org_name'], ['Contact person', 'contact_name'], ['Position', 'role_title'], ['Email', 'email'], ['Phone', 'phone'], ['Website', 'website'], ['Based in', 'location'], ['About', 'about']] as [$l, $c]) if (trim((string)$a[$c]) !== '') echo '<dt>' . $e($l) . '</dt><dd>' . nl2br($e($a[$c])) . '</dd>'; ?></dl>
  <h3 class="mem-h3">Proposed partnership</h3><dl class="app-dl"><?php foreach ([['Activity / outreach', 'activity'], ['How they want to partner', 'partnership_type'], ['Contribution', 'contribution'], ['Estimated value', null], ['Timing', 'timing'], ['Recognition requested', 'benefits'], ['Message', 'message']] as [$l, $c]) { $val = $c === null ? ($a['contribution_value'] !== '' ? $a['currency'] . ' ' . $a['contribution_value'] : '') : $a[$c]; if (trim((string)$val) !== '') echo '<dt>' . $e($l) . '</dt><dd>' . nl2br($e($val)) . '</dd>'; } ?></dl>
</div>

<?php if ($a['status'] === 'approved'): $live = (int)$a['partner_id'] > 0; ?>
<div class="admin-card"><div class="admin-card-head"><h2>Commitment &amp; website listing</h2></div>
  <div class="admin-alert" style="background:#fff8e6;border:1px solid #ecd9a3;color:#6b4e0a"><b>Commitment:</b> <?php echo nl2br($e($a['commitment_text'])); ?><?php echo $a['commitment_due'] ? '<br><b>Due:</b> ' . date('j F Y', strtotime($a['commitment_due'])) : ''; ?><br><b>Status:</b> <?php echo $a['commitment_met_at'] ? 'Met on ' . date('j M Y', strtotime($a['commitment_met_at'])) : 'Not yet met'; ?><?php echo $live ? ' · <b>Listed on the website</b> (<a href="../partners.php" target="_blank">view</a>)' : ''; ?></div>
  <form method="post" enctype="multipart/form-data" class="admin-form"><input type="hidden" name="id" value="<?php echo $a['id']; ?>">
    <div class="admin-form-grid"><div class="field"><label>Website category</label><select name="publish_category"><?php $cur = $a['publish_category'] ?: pa_category_for($a['partnership_type']); foreach (array_unique(array_merge($cats, [$cur])) as $c) echo '<option' . ($cur === $c ? ' selected' : '') . '>' . $e($c) . '</option>'; ?></select></div>
      <div class="field"><label>Label shown under the name</label><input name="publish_tier" value="<?php echo $e($a['publish_tier'] ?: pa_tier_for($a['partnership_type'])); ?>"></div></div>
    <div class="field"><label>Short description shown on the website</label><textarea name="publish_blurb" style="min-height:70px" maxlength="500"><?php echo $e($a['publish_blurb'] ?: mb_substr((string)$a['about'], 0, 300)); ?></textarea></div>
    <div class="field"><label>Logo for the website (<?php echo $a['logo'] ? 'replace the applicant\'s logo' : 'upload one'; ?> — optional)</label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><?php if (!$a['commitment_met_at']): ?><button name="action" value="met" class="admin-btn admin-btn-brand" onclick="return confirm('Confirm the commitment has been met? <?php echo (int)$a['auto_publish'] ? 'The partner will be published on the website and thanked by email.' : 'Auto-publish is off for this partner.'; ?>')">Commitment met<?php echo (int)$a['auto_publish'] ? ' → publish automatically' : ''; ?></button><?php endif; ?>
      <button name="action" value="publish" class="admin-btn admin-btn-line"><?php echo $live ? 'Update the website listing' : 'Publish on the website now'; ?></button>
      <?php if ($live): ?><button name="action" value="unpublish" class="admin-btn admin-btn-danger" onclick="return confirm('Remove this partner from the website?')">Remove from website</button><?php endif; ?></div>
  </form></div>
<?php endif; ?>

<div class="edit-grid"><div class="admin-card"><div class="admin-card-head"><h2>Decision</h2></div>
  <?php if ($a['reject_reason']): ?><div class="admin-alert err">Rejected: <?php echo $e($a['reject_reason']); ?></div><?php endif; ?>
  <form method="post" class="admin-form"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?php echo $a['id']; ?>">
    <h3 class="mem-h3" style="margin-top:0"><?php echo $a['status'] === 'approved' ? 'Update the approval' : 'Approve'; ?></h3>
    <div class="field"><label>Agreed commitment (what they will contribute — tracked until met, and sent to them)</label><textarea name="commitment_text" style="min-height:90px"><?php echo $e($a['commitment_text'] ?: $a['contribution']); ?></textarea></div>
    <div class="admin-form-grid"><div class="field"><label>Due by</label><input type="date" name="commitment_due" value="<?php echo $e($a['commitment_due']); ?>"></div>
      <div class="field"><label>Website category</label><select name="publish_category"><?php $cur2 = $a['publish_category'] ?: pa_category_for($a['partnership_type']); foreach (array_unique(array_merge($cats, [$cur2])) as $c) echo '<option' . ($cur2 === $c ? ' selected' : '') . '>' . $e($c) . '</option>'; ?></select></div></div>
    <input type="hidden" name="publish_tier" value="<?php echo $e($a['publish_tier']); ?>"><input type="hidden" name="publish_blurb" value="<?php echo $e($a['publish_blurb']); ?>">
    <label class="mem-check"><input type="checkbox" name="auto_publish" value="1" <?php echo (int)$a['auto_publish'] ? 'checked' : ''; ?>> Publish on the website automatically when the commitment is met</label>
    <?php if ($a['status'] !== 'approved'): ?><label class="mem-check"><input type="checkbox" name="already_met" value="1"> The commitment has already been met (publish straight away)</label><?php endif; ?>
    <button class="admin-btn admin-btn-brand" onclick="return confirm('Approve this application and email the applicant?')"><?php echo $a['status'] === 'approved' ? 'Save & re-send approval' : 'Approve & email'; ?></button></form>
  <form method="post" class="admin-form" style="margin-top:16px"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><div class="field"><label>Reason for rejecting (sent to the applicant)</label><input name="reason" value="<?php echo $e($a['reject_reason']); ?>"></div><button class="admin-btn admin-btn-danger" onclick="return confirm('Reject this application and email the applicant? A live listing will be removed.')">Reject &amp; email</button></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Internal notes</h2></div><form method="post" class="admin-form"><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><div class="field"><textarea name="admin_note" style="min-height:90px"><?php echo $e($a['admin_note']); ?></textarea></div><button class="admin-btn admin-btn-line">Save note</button></form>
  <p style="margin-top:14px"><a class="admin-btn admin-btn-danger admin-btn-sm" href="partner-applications.php?delete=<?php echo $a['id']; ?>" data-confirm="Delete this application permanently?">Delete application</a></p></div></div>

<?php else:
    $where = []; $map = ['pending' => "status='pending'", 'awaiting' => "status='approved' AND partner_id=0", 'live' => "status='approved' AND partner_id>0", 'rejected' => "status='rejected'"];
    if ($filter) $where[] = $map[$filter]; if ($q !== '') { $qq = $conn->real_escape_string($q); $where[] = "(org_name LIKE '%$qq%' OR contact_name LIKE '%$qq%' OR ref_no LIKE '%$qq%' OR email LIKE '%$qq%' OR activity LIKE '%$qq%')"; }
    $rows = $conn->query("SELECT * FROM partner_applications" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY id DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC); ?>
<div class="stat-row"><div><b><?php echo (int)$cnt['p']; ?></b><span>Pending review</span></div><div><b><?php echo (int)$cnt['w']; ?></b><span>Approved, awaiting commitment</span></div><div><b><?php echo (int)$cnt['l']; ?></b><span>Live on the website</span></div><div><b><?php echo (int)$cnt['r']; ?></b><span>Rejected</span></div></div>
<div class="admin-card"><div class="admin-card-head"><h2>Applications</h2><span><form method="get" style="display:inline-flex;gap:8px"><select name="status" onchange="this.form.submit()" style="padding:8px 10px;border:1px solid #e4e0d6;border-radius:8px"><option value="">All</option><?php foreach (['pending' => 'Pending review', 'awaiting' => 'Awaiting commitment', 'live' => 'Live on website', 'rejected' => 'Rejected'] as $k => $l) echo '<option value="' . $k . '"' . ($filter === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select><input name="q" value="<?php echo $e($q); ?>" placeholder="Search…" style="padding:8px 12px;border:1px solid #e4e0d6;border-radius:8px"><button class="admin-btn admin-btn-line admin-btn-sm">Search</button></form> <a class="admin-btn admin-btn-line admin-btn-sm" href="partner-applications.php?export=1">Export CSV</a></span></div>
  <table class="admin-table"><thead><tr><th>Ref</th><th>Applicant</th><th>Activity</th><th>Partnership</th><th>Value</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="7" style="color:var(--text-muted)">No applications yet.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?><tr><td><b><?php echo $e($r['ref_no']); ?></b><br><small><?php echo date('d M Y', strtotime($r['created_at'])); ?></small></td><td><?php echo $e(pa_display($r)); ?><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e($r['activity']); ?></td><td><?php echo $e($r['partnership_type']); ?></td><td><?php echo $r['contribution_value'] !== '' ? $e($r['currency'] . ' ' . $r['contribution_value']) : '—'; ?></td><td><?php echo pa_badge($r); ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="partner-applications.php?view=<?php echo $r['id']; ?>">Open</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
