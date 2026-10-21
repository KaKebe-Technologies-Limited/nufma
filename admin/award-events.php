<?php
$adminTitle = "Awards pre-events";
$adminNav = "awardevents";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/award-events.php";
require_once __DIR__ . "/../includes/export.php";
aev_install($conn);

$e = fn($v) => htmlspecialchars((string)$v);
$msg = ''; $err = '';
$tab = in_array($_GET['tab'] ?? '', ['sponsors', 'apps', 'settings'], true) ? $_GET['tab'] : 'events';
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$editions = $conn->query("SELECT id, year, status FROM award_editions ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
$upId = 0; foreach ($editions as $ed) if ($ed['status'] === 'upcoming') $upId = (int)$ed['id'];

if (nufa_export_format() !== '' && $tab === 'apps') {
    $rows = $conn->query("SELECT ref_no, status, event_ids, applicant_type, org_name, contact_name, email, phone, website, partnership_role, contribution, contribution_value, currency, benefits, created_at FROM award_event_apps ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) $r['event_ids'] = aev_names($conn, $r['event_ids']); unset($r);
    nufa_export_send(nufa_export_format(), 'Awards event applications', $rows ? array_keys($rows[0]) : ['No data'], $rows, 'awards-event-applications');
}
if (isset($_GET['delete_app'])) { $id = (int)$_GET['delete_app']; $r = $conn->query("SELECT logo FROM award_event_apps WHERE id=$id")->fetch_assoc(); if ($r && $r['logo']) @unlink(aev_storage() . basename($r['logo'])); $conn->query("DELETE FROM award_event_apps WHERE id=$id"); header("Location: award-events.php?tab=apps&deleted=1"); exit; }
if (isset($_GET['delete_event'])) { $id = (int)$_GET['delete_event']; $conn->query("DELETE FROM award_event_sponsors WHERE event_id=$id"); $conn->query("DELETE FROM award_events WHERE id=$id"); header("Location: award-events.php?deleted=1"); exit; }
if (isset($_GET['delete_sponsor'])) { $id = (int)$_GET['delete_sponsor']; $conn->query("DELETE FROM award_event_sponsors WHERE id=$id"); header("Location: award-events.php?tab=sponsors&event=" . (int)($_GET['event'] ?? 0) . "&deleted=1"); exit; }

$upload = function ($key, $prefix) use (&$err) {
    if (empty($_FILES[$key]['name'])) return '';
    [$fn, $er] = mem_store_upload($_FILES[$key], MEM_IMG_TYPES, 4 * 1048576, $prefix, AEV_PUBLIC_DIR); if ($er) { $err = ucfirst($prefix) . ": $er"; return ''; }
    return 'uploads/awards/' . $fn;
};
$mail = function ($kind, $a, $extra = []) use ($conn) { $S = aev_settings($conn); $v = aev_vars($conn, $a, $extra); return mem_send($conn, null, "award-event-$kind", $a['email'], strtr($S["aev_email_{$kind}_subject"], $v), strtr($S["aev_email_{$kind}_body"], $v)); };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($act === 'event_save' || $act === 'event_add') {
        $tab = 'events'; $title = mb_substr(trim($_POST['title'] ?? ''), 0, 190); $date = trim($_POST['event_date'] ?? ''); $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
        $conf = isset($_POST['date_confirmed']) ? 1 : 0; $st = trim($_POST['start_time'] ?? '') ?: null; $et = trim($_POST['end_time'] ?? '') ?: null;
        if ($title === '') $err = "Give the event a name.";
        elseif ($conf && !$date) $err = "A confirmed date needs a date — pick one, or untick “Date confirmed”.";
        elseif ($st && $et && $et < $st) $err = "The end time must be after the start time.";
        elseif (trim($_POST['map_url'] ?? '') !== '' && !preg_match('#^https?://#i', trim($_POST['map_url']))) $err = "The map link must start with https://";
        else {
            $cur = $id ? $conn->query("SELECT * FROM award_events WHERE id=$id")->fetch_assoc() : null; $img = $cur['image'] ?? '';
            $new = $upload('image', 'event'); if ($new !== '') $img = $new;
            if (!$err) {
                $slug = $cur['slug'] ?? ''; if ($slug === '') { $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'event'; $slug = $base; $n = 1; while ($conn->query("SELECT id FROM award_events WHERE slug='" . $conn->real_escape_string($slug) . "'")->num_rows) $slug = $base . '-' . (++$n); }
                $v = [(int)($_POST['edition_id'] ?? $upId), $slug, $title, mb_substr(trim($_POST['tagline'] ?? ''), 0, 255), mb_substr(trim($_POST['description'] ?? ''), 0, 3000), $img, $conf, $date, $st, $et, mb_substr(trim($_POST['date_note'] ?? ''), 0, 190), mb_substr(trim($_POST['venue'] ?? ''), 0, 255), mb_substr(trim($_POST['venue_note'] ?? ''), 0, 255), trim($_POST['map_url'] ?? ''), isset($_POST['countdown_on']) ? 1 : 0, isset($_POST['cohost_open']) ? 1 : 0, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
                if ($act === 'event_add') { $s = $conn->prepare("INSERT INTO award_events (edition_id, slug, title, tagline, description, image, date_confirmed, event_date, start_time, end_time, date_note, venue, venue_note, map_url, countdown_on, cohost_open, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("isssssisssssssiiii", ...$v); }
                else { $s = $conn->prepare("UPDATE award_events SET edition_id=?, slug=?, title=?, tagline=?, description=?, image=?, date_confirmed=?, event_date=?, start_time=?, end_time=?, date_note=?, venue=?, venue_note=?, map_url=?, countdown_on=?, cohost_open=?, sort_order=?, is_active=? WHERE id=?"); $v[] = $id; $s->bind_param("isssssisssssssiiiii", ...$v); }
                $s->execute(); $msg = "Event saved — the NUFA Awards page is updated."; $_GET['edit'] = 0;
            }
        }
        if ($err) $_GET['edit'] = $act === 'event_save' ? $id : 0;
    } elseif ($act === 'toggle_cd') {
        $tab = 'events'; $ev = $conn->query("SELECT * FROM award_events WHERE id=$id")->fetch_assoc();
        if ($ev && !$ev['countdown_on'] && (!$ev['date_confirmed'] || !$ev['event_date'])) $err = "Confirm the date first (edit the event, tick “Date confirmed” and pick the date) — then the countdown can start.";
        elseif ($ev) { $conn->query("UPDATE award_events SET countdown_on = 1 - countdown_on WHERE id=$id"); $msg = $ev['countdown_on'] ? "Countdown stopped for “{$ev['title']}”." : "Countdown started for “{$ev['title']}” — visitors now see it counting down."; }
    } elseif ($act === 'sponsor_save' || $act === 'sponsor_add') {
        $tab = 'sponsors'; $eid = (int)($_POST['event_id'] ?? 0); $name = mb_substr(trim($_POST['name'] ?? ''), 0, 190); $site = trim($_POST['website'] ?? '');
        if ($name === '' || !$eid) $err = "Choose the event and give the sponsor or co-host a name.";
        elseif ($site !== '' && !preg_match('#^https?://#i', $site)) $err = "The website must start with https://";
        else {
            $cur = $id ? $conn->query("SELECT logo FROM award_event_sponsors WHERE id=$id")->fetch_assoc() : null; $logo = $cur['logo'] ?? ''; $new = $upload('logo', 'sponsor'); if ($new !== '') $logo = $new;
            if (!$err) {
                $tier = in_array($_POST['tier'] ?? '', aev_tiers(), true) ? $_POST['tier'] : 'Sponsor'; $bl = mb_substr(trim($_POST['blurb'] ?? ''), 0, 300); $so = (int)($_POST['sort_order'] ?? 0); $ac = isset($_POST['is_active']) ? 1 : 0;
                if ($act === 'sponsor_add') { $s = $conn->prepare("INSERT INTO award_event_sponsors (event_id, name, tier, logo, website, blurb, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)"); $s->bind_param("isssssii", $eid, $name, $tier, $logo, $site, $bl, $so, $ac); }
                else { $s = $conn->prepare("UPDATE award_event_sponsors SET event_id=?, name=?, tier=?, logo=?, website=?, blurb=?, sort_order=?, is_active=? WHERE id=?"); $s->bind_param("isssssiii", $eid, $name, $tier, $logo, $site, $bl, $so, $ac, $id); }
                $s->execute(); $msg = "Saved."; $_GET['event'] = $eid;
            }
        }
    } elseif ($act === 'settings') {
        $tab = 'settings'; foreach (['aev_intro', 'aev_notify_email', 'aev_email_received_subject', 'aev_email_received_body', 'aev_email_approved_subject', 'aev_email_approved_body', 'aev_email_rejected_subject', 'aev_email_rejected_body'] as $k) $save($k, mb_substr(trim($_POST[$k] ?? ''), 0, 4000));
        if (trim($_POST['aev_notify_email'] ?? '') !== '' && !filter_var(trim($_POST['aev_notify_email']), FILTER_VALIDATE_EMAIL)) $err = "The notification email is not valid."; else $msg = "Settings saved.";
    } elseif (in_array($act, ['approve', 'reject', 'note'], true)) {
        $tab = 'apps'; $a = $conn->query("SELECT * FROM award_event_apps WHERE id=$id")->fetch_assoc(); $_GET['view'] = $id;
        if ($a && $act === 'approve') {
            $tier = in_array($_POST['tier'] ?? '', aev_tiers(), true) ? $_POST['tier'] : 'Sponsor'; $pub = isset($_POST['publish_now']);
            $conn->query("UPDATE award_event_apps SET status='approved', approved_at=COALESCE(approved_at, NOW()), reject_reason='' WHERE id=$id"); $a = $conn->query("SELECT * FROM award_event_apps WHERE id=$id")->fetch_assoc();
            $line = ''; if ($pub) { $n = aev_publish_app($conn, $a, $tier, mb_substr(trim($_POST['blurb'] ?? $a['about']), 0, 300)); $line = "You are now shown on the NUFA Awards website as a " . strtolower($tier) . ".\n\n"; $msg = "Approved and published on $n event page(s). "; } else $msg = "Approved (not yet shown on the website). ";
            $ok = $mail('approved', $a, ['{published_line}' => $line]); $msg .= $ok ? "The applicant was emailed." : "The email could not be sent from this server (saved in the email log).";
        } elseif ($a && $act === 'reject') {
            $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 500); if ($reason === '') $err = "Enter a reason for rejecting the application.";
            else { $u = $conn->prepare("UPDATE award_event_apps SET status='rejected', reject_reason=? WHERE id=?"); $u->bind_param("si", $reason, $id); $u->execute(); $conn->query("UPDATE award_event_sponsors SET is_active=0 WHERE app_id=$id"); $a = $conn->query("SELECT * FROM award_event_apps WHERE id=$id")->fetch_assoc(); $ok = $mail('rejected', $a); $msg = "Rejected" . ($ok ? " and the applicant was emailed." : " (email not sent from this server)."); }
        } elseif ($a) { $u = $conn->prepare("UPDATE award_event_apps SET admin_note=? WHERE id=?"); $n = mb_substr(trim($_POST['admin_note'] ?? ''), 0, 500); $u->bind_param("si", $n, $id); $u->execute(); $msg = "Saved."; }
    }
}

$S = aev_settings($conn, true);
$events = $conn->query("SELECT * FROM award_events ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$pending = (int)$conn->query("SELECT COUNT(*) FROM award_event_apps WHERE status='pending'")->fetch_row()[0];
include __DIR__ . "/includes/admin-header.php";
$tabs = ['events' => 'Events & countdowns', 'sponsors' => 'Co-hosts & sponsors', 'apps' => 'Applications' . ($pending ? " ($pending)" : ''), 'settings' => 'Form & emails'];
?>
<div class="mem-tabs"><?php foreach ($tabs as $t => $l): ?><a href="award-events.php?tab=<?php echo $t; ?>" class="<?php echo $tab === $t ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?><a href="awards-setup.php">Awards setup</a><a href="../awards.php#events" target="_blank">View on the website ↗</a><a href="../awards-cohost.php" target="_blank">View the application form ↗</a></div>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Deleted.</div><?php endif; ?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'events'):
  $edit = (int)($_GET['edit'] ?? 0); $cur = $edit ? $conn->query("SELECT * FROM award_events WHERE id=$edit")->fetch_assoc() : null;
  $f = ($_SERVER['REQUEST_METHOD'] === 'POST' && $err) ? $_POST : ($cur ?: []); $v = fn($k, $d = '') => $e($f[$k] ?? $d); ?>
  <div class="ev-admin-list">
  <?php foreach ($events as $ev): $iso = aev_countdown_iso($ev); $diff = $iso !== '' ? max(0, (int)floor((strtotime($iso) - time()) / 86400)) : null; ?>
    <div class="admin-card" style="padding:18px 20px"><div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;justify-content:space-between">
      <div><b style="font-size:1.05rem"><?php echo $e($ev['title']); ?></b> <?php echo $ev['is_active'] ? '' : '<span class="admin-badge gray">Hidden</span>'; ?><br>
        <span class="admin-help"><?php echo $e(aev_when($ev)); ?> · <?php echo $ev['venue'] !== '' ? $e($ev['venue']) : 'venue not set'; ?></span><br>
        <?php echo $ev['date_confirmed'] ? '<span class="admin-badge">Date confirmed</span>' : '<span class="admin-badge gray">Date not confirmed</span>'; ?>
        <?php echo $ev['countdown_on'] ? '<span class="admin-badge" style="background:#eef6ee;color:#1f6b2b">⏱ Countdown running' . ($diff !== null ? " · $diff days left" : '') . '</span>' : '<span class="admin-badge gray">No countdown</span>'; ?>
        <?php echo $ev['cohost_open'] ? '<span class="admin-badge">Co-host applications open</span>' : '<span class="admin-badge gray">Applications closed</span>'; ?></div>
      <div class="admin-row-actions" style="flex-wrap:wrap"><form method="post" style="display:inline"><input type="hidden" name="action" value="toggle_cd"><input type="hidden" name="id" value="<?php echo $ev['id']; ?>"><button class="admin-btn <?php echo $ev['countdown_on'] ? 'admin-btn-line' : 'admin-btn-brand'; ?> admin-btn-sm"><?php echo $ev['countdown_on'] ? '⏹ Stop countdown' : '▶ Start countdown'; ?></button></form>
        <a class="admin-btn admin-btn-line admin-btn-sm" href="award-events.php?edit=<?php echo $ev['id']; ?>#event-form">Edit details</a><a class="admin-btn admin-btn-line admin-btn-sm" href="award-events.php?tab=sponsors&event=<?php echo $ev['id']; ?>">Sponsors</a>
        <a class="admin-btn admin-btn-danger admin-btn-sm" href="award-events.php?delete_event=<?php echo $ev['id']; ?>" data-confirm="Delete this event and its sponsors?">Delete</a></div></div></div>
  <?php endforeach; if (!$events) echo '<div class="admin-card"><p class="admin-help">No events yet — add one below.</p></div>'; ?></div>
  <form method="post" enctype="multipart/form-data" class="admin-card admin-form" id="event-form"><input type="hidden" name="action" value="<?php echo $cur ? 'event_save' : 'event_add'; ?>"><input type="hidden" name="id" value="<?php echo (int)($cur['id'] ?? 0); ?>">
    <div class="admin-card-head"><h2><?php echo $cur ? 'Edit “' . $e($cur['title']) . '”' : 'Add an event'; ?></h2><span class="admin-help">Everything here appears on the NUFA Awards page.</span></div>
    <div class="admin-form-grid"><div class="field"><label>Event name</label><input name="title" value="<?php echo $v('title'); ?>" required></div><div class="field"><label>Short line under the name</label><input name="tagline" value="<?php echo $v('tagline'); ?>"></div></div>
    <div class="field"><label>About this event</label><textarea name="description" style="min-height:90px"><?php echo $v('description'); ?></textarea></div>
    <div class="admin-card" style="background:#faf8f3;box-shadow:none"><div class="admin-card-head" style="margin-bottom:10px"><h2 style="font-size:.95rem">Date, time &amp; venue</h2></div>
      <label class="mem-check"><input type="checkbox" name="date_confirmed" value="1" <?php echo !empty($f['date_confirmed']) ? 'checked' : ''; ?>> The date is <b>confirmed</b> (show it publicly and allow a countdown)</label>
      <div class="admin-form-grid" style="margin-top:8px"><div class="field"><label>Date</label><input type="date" name="event_date" value="<?php echo $v('event_date'); ?>"></div><div class="field"><label>Starts</label><input type="time" name="start_time" value="<?php echo $e(substr((string)($f['start_time'] ?? ''), 0, 5)); ?>"></div><div class="field"><label>Ends</label><input type="time" name="end_time" value="<?php echo $e(substr((string)($f['end_time'] ?? ''), 0, 5)); ?>"></div>
        <div class="field"><label>Shown while the date is not confirmed</label><input name="date_note" value="<?php echo $v('date_note', 'Date to be announced'); ?>" placeholder="e.g. Date to be announced · Expected March 2027"></div></div>
      <div class="admin-form-grid"><div class="field"><label>Venue</label><input name="venue" value="<?php echo $v('venue'); ?>" placeholder="e.g. Acholi Inn, Gulu City"></div><div class="field"><label>Venue note (address, entrance…)</label><input name="venue_note" value="<?php echo $v('venue_note'); ?>"></div><div class="field"><label>Map link (optional)</label><input type="url" name="map_url" value="<?php echo $v('map_url'); ?>" placeholder="https://maps.google.com/…"></div></div></div>
    <div class="admin-form-grid"><div class="field"><label>Event picture (optional)</label><input type="file" name="image" accept="image/*"><?php if (!empty($f['image'])): ?><span class="admin-help">Current: <?php echo $e($f['image']); ?></span><?php endif; ?></div><div class="field"><label>Order</label><input type="number" name="sort_order" value="<?php echo (int)($f['sort_order'] ?? count($events) + 1); ?>"></div>
      <div class="field"><label>Awards edition</label><select name="edition_id"><?php foreach ($editions as $ed) echo '<option value="' . $ed['id'] . '"' . ((int)($f['edition_id'] ?? $upId) === (int)$ed['id'] ? ' selected' : '') . '>NUFA Awards ' . (int)$ed['year'] . '</option>'; ?></select></div></div>
    <label class="mem-check"><input type="checkbox" name="countdown_on" value="1" <?php echo !empty($f['countdown_on']) ? 'checked' : ''; ?>> Show a countdown on the website <span class="admin-help">(works once the date is confirmed)</span></label>
    <label class="mem-check"><input type="checkbox" name="cohost_open" value="1" <?php echo (!$cur && !$_POST) || !empty($f['cohost_open']) ? 'checked' : ''; ?>> Accept co-host and sponsor applications for this event</label>
    <label class="mem-check"><input type="checkbox" name="is_active" value="1" <?php echo (!$cur && !$_POST) || !empty($f['is_active']) ? 'checked' : ''; ?>> Show this event on the website</label>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $cur ? 'Save event' : 'Add event'; ?></button><?php if ($cur): ?><a class="admin-btn admin-btn-line" href="award-events.php">Cancel</a><?php endif; ?></div>
  </form>

<?php elseif ($tab === 'sponsors'):
  $eid = (int)($_GET['event'] ?? ($events[0]['id'] ?? 0)); $list = $eid ? $conn->query("SELECT * FROM award_event_sponsors WHERE event_id=$eid ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC) : [];
  $sForm = function ($s, $add = false) use ($events, $eid, $e) { ob_start(); ?>
    <form method="post" enctype="multipart/form-data" class="admin-card admin-form" style="padding:16px 18px"><input type="hidden" name="action" value="<?php echo $add ? 'sponsor_add' : 'sponsor_save'; ?>"><input type="hidden" name="id" value="<?php echo (int)($s['id'] ?? 0); ?>"><input type="hidden" name="event_id" value="<?php echo $eid; ?>">
      <div class="admin-form-grid"><div class="field"><label>Name</label><input name="name" value="<?php echo $e($s['name'] ?? ''); ?>" required></div><div class="field"><label>Role</label><select name="tier"><?php foreach (aev_tiers() as $t) echo '<option' . (($s['tier'] ?? 'Sponsor') === $t ? ' selected' : '') . '>' . $t . '</option>'; ?></select></div>
        <div class="field"><label>Logo</label><input type="file" name="logo" accept="image/*"><?php if (!empty($s['logo'])): ?><img src="../assets/images/<?php echo $e($s['logo']); ?>" alt="" style="height:34px;margin-top:6px;background:#fff;border:1px solid #e4e0d6;border-radius:6px;padding:3px"><?php endif; ?></div><div class="field"><label>Website</label><input type="url" name="website" value="<?php echo $e($s['website'] ?? ''); ?>" placeholder="https://"></div>
        <div class="field"><label>Order</label><input type="number" name="sort_order" value="<?php echo (int)($s['sort_order'] ?? 0); ?>"></div></div>
      <div style="display:flex;gap:12px;align-items:center"><label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo $add || !empty($s['is_active']) ? 'checked' : ''; ?>> Show</label><button class="admin-btn admin-btn-brand admin-btn-sm"><?php echo $add ? 'Add' : 'Save'; ?></button><?php if (!$add): ?><a class="admin-btn admin-btn-danger admin-btn-sm" href="award-events.php?delete_sponsor=<?php echo (int)$s['id']; ?>&event=<?php echo $eid; ?>" data-confirm="Remove this sponsor?">Remove</a><?php endif; ?></div></form><?php return ob_get_clean(); }; ?>
  <div class="ax-toolbar"><div class="ax-seg"><?php foreach ($events as $ev): ?><a href="award-events.php?tab=sponsors&event=<?php echo $ev['id']; ?>" class="<?php echo $eid === (int)$ev['id'] ? 'on' : ''; ?>"><?php echo $e($ev['title']); ?></a><?php endforeach; ?></div></div>
  <p class="admin-help" style="margin-bottom:12px">Add the co-hosts and sponsors shown on the event card. Approved applicants can be published here automatically from the Applications tab.</p>
  <h3 class="mem-h3" style="margin-top:0">Add a sponsor or co-host</h3><?php echo $sForm([], true); ?>
  <?php if ($list): ?><h3 class="mem-h3">Currently on this event (<?php echo count($list); ?>)</h3><?php foreach ($list as $s) echo $sForm($s); endif; ?>

<?php elseif ($tab === 'apps'):
  $view = (int)($_GET['view'] ?? 0); $a = $view ? $conn->query("SELECT * FROM award_event_apps WHERE id=$view")->fetch_assoc() : null;
  if ($a): $disp = aev_display($a); ?>
  <div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($disp); ?> <?php echo ['pending' => '<span class="admin-badge gray">Pending</span>', 'approved' => '<span class="admin-badge">Approved</span>', 'rejected' => '<span class="admin-badge red">Rejected</span>'][$a['status']]; ?></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="award-events.php?tab=apps">← All applications</a></div>
    <p><span class="reg-badge-sm"><?php echo $e($a['ref_no']); ?></span> · <?php echo date('d M Y H:i', strtotime($a['created_at'])); ?> · wants to <b><?php echo $e(strtolower($a['partnership_role'])); ?></b>: <b><?php echo $e(aev_names($conn, $a['event_ids'])); ?></b></p>
    <?php require_once __DIR__ . "/../includes/whatsapp.php"; if (($wa = nufa_wa_reply_url($a['phone'], 'Hello ' . $a['contact_name'] . ', this is NUFA about your awards event application ' . $a['ref_no'] . '. '))): ?><p><a class="admin-btn admin-btn-line admin-btn-sm" href="<?php echo $e($wa); ?>" target="_blank" rel="noopener" style="border-color:#25D366;color:#128c4a">Message on WhatsApp</a></p><?php endif; ?>
    <?php if ($a['logo']): ?><div class="app-files"><a class="app-file" href="award-event-file.php?id=<?php echo $a['id']; ?>" target="_blank"><img src="award-event-file.php?id=<?php echo $a['id']; ?>" alt=""><b>Logo</b></a></div><?php endif; ?>
    <dl class="app-dl"><?php foreach ([['Organisation', 'org_name'], ['Contact', 'contact_name'], ['Position', 'role_title'], ['Email', 'email'], ['Phone', 'phone'], ['Website', 'website'], ['About', 'about'], ['Contribution', 'contribution'], ['Recognition wanted', 'benefits'], ['Message', 'message']] as [$l, $c]) if (trim((string)$a[$c]) !== '') echo '<dt>' . $e($l) . '</dt><dd>' . nl2br($e($a[$c])) . '</dd>'; if ($a['contribution_value'] !== '') echo '<dt>Estimated value</dt><dd>' . $e($a['currency'] . ' ' . $a['contribution_value']) . '</dd>'; ?></dl></div>
  <div class="edit-grid"><div class="admin-card"><div class="admin-card-head"><h2>Decision</h2></div>
    <?php if ($a['reject_reason']): ?><div class="admin-alert err">Rejected: <?php echo $e($a['reject_reason']); ?></div><?php endif; ?>
    <form method="post" class="admin-form"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?php echo $a['id']; ?>">
      <div class="field"><label>Show on the event page as</label><select name="tier"><?php foreach (aev_tiers() as $t) echo '<option' . ($a['partnership_role'] === $t ? ' selected' : '') . '>' . $t . '</option>'; ?></select></div>
      <div class="field"><label>Short line shown with them (optional)</label><input name="blurb" maxlength="300" value="<?php echo $e(mb_substr((string)$a['about'], 0, 140)); ?>"></div>
      <label class="mem-check"><input type="checkbox" name="publish_now" value="1" checked> Publish them on the event page(s) now (with their logo)</label>
      <button class="admin-btn admin-btn-brand" onclick="return confirm('Approve this application and email the applicant?')"><?php echo $a['status'] === 'approved' ? 'Update & re-send approval' : 'Approve & email'; ?></button></form>
    <form method="post" class="admin-form" style="margin-top:14px"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><div class="field"><label>Reason for rejecting</label><input name="reason" value="<?php echo $e($a['reject_reason']); ?>"></div><button class="admin-btn admin-btn-danger" onclick="return confirm('Reject and email the applicant? Any listing is hidden.')">Reject &amp; email</button></form></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Notes</h2></div><form method="post" class="admin-form"><input type="hidden" name="action" value="note"><input type="hidden" name="id" value="<?php echo $a['id']; ?>"><div class="field"><textarea name="admin_note" style="min-height:90px"><?php echo $e($a['admin_note']); ?></textarea></div><button class="admin-btn admin-btn-line">Save note</button></form>
    <p style="margin-top:14px"><a class="admin-btn admin-btn-danger admin-btn-sm" href="award-events.php?delete_app=<?php echo $a['id']; ?>" data-confirm="Delete this application permanently?">Delete</a></p></div></div>
  <?php else:
    $filter = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : ''; $rows = $conn->query("SELECT * FROM award_event_apps" . ($filter ? " WHERE status='$filter'" : '') . " ORDER BY id DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC); ?>
  <div class="admin-card"><div class="admin-card-head"><h2>Applications</h2><span><form method="get" style="display:inline-flex;gap:8px"><input type="hidden" name="tab" value="apps"><select name="status" onchange="this.form.submit()" style="padding:8px 10px;border:1px solid #e4e0d6;border-radius:8px"><option value="">All</option><?php foreach (['pending', 'approved', 'rejected'] as $s) echo '<option value="' . $s . '"' . ($filter === $s ? ' selected' : '') . '>' . ucfirst($s) . '</option>'; ?></select></form> <?php echo nufa_export_menu('award-events.php?tab=apps'); ?></span></div>
    <table class="admin-table"><thead><tr><th>Ref</th><th>Applicant</th><th>Events</th><th>Role</th><th>Value</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><b><?php echo $e($r['ref_no']); ?></b><br><small><?php echo date('d M Y', strtotime($r['created_at'])); ?></small></td><td><?php echo $e(aev_display($r)); ?><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e(aev_names($conn, $r['event_ids'])); ?></td><td><?php echo $e($r['partnership_role']); ?></td><td><?php echo $r['contribution_value'] !== '' ? $e($r['currency'] . ' ' . $r['contribution_value']) : '—'; ?></td><td><?php echo ['pending' => '<span class="admin-badge gray">Pending</span>', 'approved' => '<span class="admin-badge">Approved</span>', 'rejected' => '<span class="admin-badge red">Rejected</span>'][$r['status']]; ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="award-events.php?tab=apps&view=<?php echo $r['id']; ?>">Open</a></td></tr><?php endforeach; if (!$rows) echo '<tr><td colspan="7" style="color:var(--text-muted)">No applications yet.</td></tr>'; ?></tbody></table></div>
  <?php endif; ?>

<?php else: ?>
  <form method="post" class="admin-form admin-card"><input type="hidden" name="action" value="settings"><div class="admin-card-head"><h2>Form &amp; emails</h2><span class="admin-help">Placeholders: {name} {ref} {role} {events} {reason}</span></div>
    <div class="field"><label>Introduction on the Awards events section and form</label><textarea name="aev_intro" style="min-height:80px"><?php echo $e($S['aev_intro']); ?></textarea></div>
    <div class="field"><label>Send new-application alerts to</label><input type="email" name="aev_notify_email" value="<?php echo $e($S['aev_notify_email']); ?>" placeholder="Defaults to the membership alert address"></div>
    <?php foreach (['received' => 'Application received', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?><h3 class="mem-h3"><?php echo $l; ?></h3><div class="field"><label>Subject</label><input name="aev_email_<?php echo $k; ?>_subject" value="<?php echo $e($S["aev_email_{$k}_subject"]); ?>"></div><div class="field"><label>Message</label><textarea name="aev_email_<?php echo $k; ?>_body" style="min-height:110px"><?php echo $e($S["aev_email_{$k}_body"]); ?></textarea></div><?php endforeach; ?>
    <button class="admin-btn admin-btn-brand">Save settings</button></form>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
