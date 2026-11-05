<?php
$adminTitle = "Invitation letters";
$adminNav = "letters";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/letters.php";
ltr_install($conn);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$S = ltr_settings($conn);
$flash = ''; $flashType = 'ok';
$tabs = ['list' => 'All letters', 'new' => 'New letter', 'bulk' => 'Several at once', 'settings' => 'Letterhead & template'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'list';
$id = (int)($_GET['id'] ?? 0);

$create = function (array $d) use ($conn, $S) {
    $d['body'] = ltr_generate($conn, $d);
    $tok = bin2hex(random_bytes(16)); $ref = ltr_next_ref($conn, $S); $by = $_SESSION['admin_name'] ?? 'admin';
    $date = !empty($d['event_date']) && strtotime($d['event_date']) ? date('Y-m-d', strtotime($d['event_date'])) : null;
    $ld = date('Y-m-d');
    $st = $conn->prepare("INSERT INTO invitation_letters (token, ref, honorific, recipient_name, position, organisation, email, phone, event_title, event_date, event_time, venue, purpose, body, letter_date, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $vals = [$tok, $ref, trim($d['honorific'] ?? ''), trim($d['recipient_name']), trim($d['position'] ?? ''), trim($d['organisation'] ?? ''), trim($d['email'] ?? ''), trim($d['phone'] ?? ''), trim($d['event_title']), $date, trim($d['event_time'] ?? ''), trim($d['venue'] ?? ''), trim($d['purpose'] ?? ''), $d['body'], $ld, $by];
    $st->bind_param(str_repeat('s', 16), ...$vals); $st->execute();
    return (int)$conn->insert_id;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $act = $_POST['action'] ?? '';
        if ($act === 'create') {
            if (trim($_POST['recipient_name'] ?? '') === '' || trim($_POST['event_title'] ?? '') === '') { $flash = 'Enter the name and the event.'; $flashType = 'err'; $tab = 'new'; }
            else { $nid = $create($_POST); header("Location: letters.php?id=$nid&created=1"); exit; }
        } elseif ($act === 'bulk') {
            $n = 0; $shared = $_POST;
            foreach (preg_split('/\R/', (string)($_POST['people'] ?? '')) as $ln) {
                $p = array_map('trim', explode('|', $ln)); if (($p[0] ?? '') === '') continue;
                $d = $shared; $d['recipient_name'] = $p[0]; $d['honorific'] = $p[1] ?? ''; $d['position'] = $p[2] ?? ''; $d['email'] = $p[3] ?? ''; $d['phone'] = $p[4] ?? '';
                if (trim($d['event_title'] ?? '') === '') break; $create($d); $n++;
            }
            if ($n) { header("Location: letters.php?bulk=$n"); exit; } $flash = 'Enter the event and at least one person.'; $flashType = 'err'; $tab = 'bulk';
        } elseif (in_array($act, ['save', 'email', 'mark_wa', 'delete', 'regenerate'], true)) {
            $lid = (int)($_POST['id'] ?? 0); $l = $conn->query("SELECT * FROM invitation_letters WHERE id=$lid")->fetch_assoc();
            if ($l) {
                if ($act === 'save') {
                    $u = $conn->prepare("UPDATE invitation_letters SET honorific=?, recipient_name=?, position=?, organisation=?, email=?, phone=?, event_title=?, event_date=?, event_time=?, venue=?, body=? WHERE id=?");
                    $ed = !empty($_POST['event_date']) && strtotime($_POST['event_date']) ? date('Y-m-d', strtotime($_POST['event_date'])) : null;
                    $vals = [trim($_POST['honorific'] ?? ''), trim($_POST['recipient_name'] ?? ''), trim($_POST['position'] ?? ''), trim($_POST['organisation'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['event_title'] ?? ''), $ed, trim($_POST['event_time'] ?? ''), trim($_POST['venue'] ?? ''), trim($_POST['body'] ?? '')];
                    $u->bind_param(str_repeat('s', 11) . 'i', ...array_merge($vals, [$lid])); $u->execute(); $flash = 'Letter saved.';
                } elseif ($act === 'regenerate') {
                    $l['purpose'] = $l['purpose'] ?? ''; $b = ltr_generate($conn, $l); $u = $conn->prepare("UPDATE invitation_letters SET body=? WHERE id=?"); $u->bind_param("si", $b, $lid); $u->execute(); $flash = 'The letter text was rewritten from the template.';
                } elseif ($act === 'email') {
                    $ok = ltr_send_email($conn, $l); $flash = $ok ? 'Email sent to ' . $l['email'] . '.' : 'The email could not be sent (no valid email address, or mail is not available on this server). It is saved in the email log — you can still send the link by WhatsApp.'; if (!$ok) $flashType = 'warn';
                } elseif ($act === 'mark_wa') { $conn->query("UPDATE invitation_letters SET wa_sent_at=NOW(), status='sent' WHERE id=$lid"); if (!empty($_POST['ajax'])) { echo 'ok'; exit; } }
                elseif ($act === 'delete') { $conn->query("DELETE FROM invitation_letters WHERE id=$lid"); header("Location: letters.php?deleted=1"); exit; }
                $id = $lid;
            }
        } elseif ($act === 'settings') {
            $save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
            foreach (['lt_ref_prefix', 'lt_signer_name', 'lt_signer_title', 'lt_rsvp', 'lt_closing', 'lt_template'] as $k) $save($k, trim($_POST[$k] ?? ''));
            $lhDir = __DIR__ . "/../assets/images/uploads/letterhead/";
            if (!empty($_POST['remove_letterhead']) && $S['lt_letterhead']) { @unlink(__DIR__ . "/../assets/images/" . $S['lt_letterhead']); $save('lt_letterhead', ''); }
            if (!empty($_FILES['lt_letterhead']['name'])) {
                [$lf, $le] = mem_store_upload($_FILES['lt_letterhead'], MEM_IMG_TYPES, 4 * 1048576, 'letterhead', $lhDir);
                if ($le) { $flash = 'Letterhead: ' . $le; $flashType = 'err'; } else { if ($S['lt_letterhead']) @unlink(__DIR__ . "/../assets/images/" . $S['lt_letterhead']); $save('lt_letterhead', 'uploads/letterhead/' . $lf); }
            }
            $sigDir = __DIR__ . "/../assets/images/uploads/signature/";
            if (!empty($_POST['remove_signature']) && $S['lt_signature']) { @unlink(__DIR__ . "/../assets/images/" . $S['lt_signature']); $save('lt_signature', ''); }
            if (!empty($_FILES['lt_signature']['name'])) {
                [$fn, $se] = mem_store_upload($_FILES['lt_signature'], MEM_IMG_TYPES, 2 * 1048576, 'letter-signature', $sigDir);
                if ($se) { $flash = 'Signature: ' . $se; $flashType = 'err'; } else { if ($S['lt_signature']) @unlink(__DIR__ . "/../assets/images/" . $S['lt_signature']); $save('lt_signature', 'uploads/signature/' . $fn); }
            }
            if ($flashType !== 'err') $flash = 'Letterhead settings saved.'; $S = ltr_settings($conn); $tab = 'settings';
        }
    }
}
if (isset($_GET['bulk'])) $flash = (int)$_GET['bulk'] . ' letter(s) created. Send each one by email or WhatsApp from the list below.';
if (isset($_GET['deleted'])) $flash = 'Letter deleted.';

$L = $id ? $conn->query("SELECT * FROM invitation_letters WHERE id=$id")->fetch_assoc() : null;
// quick-fill sources: leadership team and distinguished guests
$people = [];
foreach ($conn->query("SELECT name, role FROM team_members WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC) as $t) $people[$t['name']] = ['position' => $t['role'], 'organisation' => 'Northern Uganda Filmmakers Association'];
$gq = $conn->query("SELECT name, title, organisation FROM vip_guests WHERE is_active=1 ORDER BY name"); if ($gq) foreach ($gq->fetch_all(MYSQLI_ASSOC) as $g) $people[$g['name']] = ['position' => $g['title'], 'organisation' => $g['organisation']];
include __DIR__ . "/includes/admin-header.php";
$eventFields = function ($v = []) use ($e) { ?>
  <div class="admin-form-grid">
    <div class="field"><label>Event / occasion *</label><input name="event_title" required value="<?php echo $e($v['event_title'] ?? ''); ?>" placeholder="e.g. the NUFA Awards Nomination Night"></div>
    <div class="field"><label>Date of the event</label><input type="date" name="event_date" value="<?php echo $e($v['event_date'] ?? ''); ?>"></div>
    <div class="field"><label>Time</label><input name="event_time" value="<?php echo $e($v['event_time'] ?? ''); ?>" placeholder="e.g. 6:00 PM"></div>
    <div class="field"><label>Venue</label><input name="venue" value="<?php echo $e($v['venue'] ?? ''); ?>" placeholder="e.g. Pece Stadium, Gulu City"></div>
  </div>
  <div class="field"><label>What the event is about (optional — becomes a paragraph in the letter)</label><textarea name="purpose" style="min-height:70px" placeholder="e.g. The evening recognises outstanding filmmakers from Northern Uganda and launches the next call for entries."><?php echo $e($v['purpose'] ?? ''); ?></textarea></div>
<?php };
?>
<div class="mem-tabs"><a href="invitations.php">Award invitations</a><?php foreach ($tabs as $k => $l): ?><a href="letters.php?tab=<?php echo $k; ?>" class="<?php echo (!$L && $tab === $k) ? 'active' : ''; ?>"><?php echo $e($l); ?></a><?php endforeach; ?></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $e($flash); ?></div><?php endif; ?>
<?php if (isset($_GET['created'])): ?><div class="admin-alert ok">Letter created on NUFA headed paper. Check it below, then send it by email or WhatsApp.</div><?php endif; ?>

<?php if ($L): $wa = ltr_wa_url($L); ?>
<link rel="stylesheet" href="../assets/css/admin-ui.css">
<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo $e($L['ref']); ?> — <?php echo $e(trim($L['honorific'] . ' ' . $L['recipient_name'])); ?></h2>
    <span><?php echo $L['email_sent_at'] ? '<span class="admin-badge">Emailed ' . date('d M Y', strtotime($L['email_sent_at'])) . '</span> ' : ''; echo $L['wa_sent_at'] ? '<span class="admin-badge">WhatsApp ' . date('d M Y', strtotime($L['wa_sent_at'])) . '</span>' : ''; echo (!$L['email_sent_at'] && !$L['wa_sent_at']) ? '<span class="admin-badge gray">Not sent yet</span>' : ''; ?></span></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="post"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo $L['id']; ?>"><button class="admin-btn admin-btn-brand" name="action" value="email" <?php echo filter_var($L['email'], FILTER_VALIDATE_EMAIL) ? '' : 'disabled title="Add an email address below"'; ?> data-confirm="Send this letter to <?php echo $e($L['email']); ?>?">&#9993; Send by email</button></form>
    <?php if ($wa): ?><a class="admin-btn admin-btn-brand" style="background:#128C4A;border-color:#128C4A" href="<?php echo $e($wa); ?>" target="_blank" rel="noopener" id="waBtn">&#9742; Send on WhatsApp</a><?php else: ?><button class="admin-btn admin-btn-line" disabled title="Add a phone number below">Send on WhatsApp</button><?php endif; ?>
    <a class="admin-btn admin-btn-line" href="../invitation-letter.php?t=<?php echo $e($L['token']); ?>" target="_blank">Print / save as PDF</a>
    <button type="button" class="admin-btn admin-btn-line" onclick="navigator.clipboard.writeText('<?php echo $e(ltr_link($L)); ?>');this.textContent='Link copied'">Copy letter link</button>
    <a class="admin-btn admin-btn-line" href="letters.php">&larr; All letters</a>
  </div>
  <p class="admin-help" style="margin-top:10px">WhatsApp opens with a ready message and the link to the letter on headed paper; the person taps the link to read or download it.</p>
</div>
<div class="edit-grid" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr);gap:16px;align-items:start">
  <div class="admin-card"><div class="admin-card-head"><h2>Edit details &amp; text</h2></div>
    <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo $L['id']; ?>"><input type="hidden" name="action" value="save">
      <div class="admin-form-grid">
        <div class="field"><label>Title (Hon., Dr., Mr., Ms.…)</label><input name="honorific" value="<?php echo $e($L['honorific']); ?>"></div>
        <div class="field"><label>Full name *</label><input name="recipient_name" required value="<?php echo $e($L['recipient_name']); ?>"></div>
        <div class="field"><label>Position</label><input name="position" value="<?php echo $e($L['position']); ?>"></div>
        <div class="field"><label>Organisation</label><input name="organisation" value="<?php echo $e($L['organisation']); ?>"></div>
        <div class="field"><label>Email</label><input type="email" name="email" value="<?php echo $e($L['email']); ?>"></div>
        <div class="field"><label>WhatsApp / phone</label><input name="phone" value="<?php echo $e($L['phone']); ?>"></div>
        <div class="field"><label>Event *</label><input name="event_title" required value="<?php echo $e($L['event_title']); ?>"></div>
        <div class="field"><label>Date</label><input type="date" name="event_date" value="<?php echo $e($L['event_date']); ?>"></div>
        <div class="field"><label>Time</label><input name="event_time" value="<?php echo $e($L['event_time']); ?>"></div>
        <div class="field"><label>Venue</label><input name="venue" value="<?php echo $e($L['venue']); ?>"></div>
      </div>
      <div class="field"><label>Letter text (separate paragraphs with a blank line)</label><textarea name="body" style="min-height:300px"><?php echo $e($L['body']); ?></textarea></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand">Save changes</button></div>
    </form>
    <form method="post" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo $L['id']; ?>">
      <button class="admin-btn admin-btn-line admin-btn-sm" name="action" value="regenerate" data-confirm="Rewrite the letter text from the template? Your edits to the text will be replaced.">Rewrite from template</button>
      <button class="admin-btn admin-btn-danger admin-btn-sm" name="action" value="delete" data-confirm="Delete this letter?">Delete</button></form>
  </div>
  <div style="background:#e9e9e9;border-radius:14px;padding:14px;overflow:auto"><div style="width:210mm;max-width:none;transform-origin:top left;zoom:.62"><?php echo ltr_html($conn, $L, ['prefix' => '../']); ?></div></div>
</div>
<script>(function(){var b=document.getElementById('waBtn');if(!b)return;b.addEventListener('click',function(){var f=new FormData();f.append('csrf','<?php echo mem_csrf(); ?>');f.append('action','mark_wa');f.append('id','<?php echo (int)$L['id']; ?>');f.append('ajax','1');fetch('letters.php',{method:'POST',body:f,credentials:'same-origin'});});})();</script>

<?php elseif ($tab === 'new'): ?>
<div class="admin-card"><div class="admin-card-head"><h2>New invitation letter</h2><span class="admin-help">Fill in who and what — the letter is written for you on NUFA headed paper.</span></div>
  <form method="post" class="admin-form" id="newForm"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="create">
    <h3 style="margin:4px 0 8px">Who is invited</h3>
    <div class="admin-form-grid">
      <div class="field"><label>Title (Hon., Dr., Mr., Ms., Eng.…)</label><input name="honorific" placeholder="Hon."></div>
      <div class="field"><label>Full name *</label><input name="recipient_name" id="rName" list="peopleList" required autocomplete="off"><datalist id="peopleList"><?php foreach ($people as $n => $p) echo '<option value="' . $e($n) . '">'; ?></datalist><span class="admin-help">Start typing to pick someone from the leadership team or distinguished guests.</span></div>
      <div class="field"><label>Position</label><input name="position" id="rPos" placeholder="e.g. Chairperson, Gulu District Local Government"></div>
      <div class="field"><label>Organisation</label><input name="organisation" id="rOrg"></div>
      <div class="field"><label>Email (to send by email)</label><input type="email" name="email"></div>
      <div class="field"><label>WhatsApp / phone (to send on WhatsApp)</label><input name="phone" placeholder="+256…"></div>
    </div>
    <h3 style="margin:14px 0 8px">The invitation</h3>
    <?php $eventFields(); ?>
    <button class="admin-btn admin-btn-brand">Generate the letter</button>
  </form>
  <script>(function(){var P=<?php echo json_encode($people, JSON_UNESCAPED_UNICODE); ?>,n=document.getElementById('rName');n.addEventListener('change',function(){var p=P[n.value];if(!p)return;var a=document.getElementById('rPos'),o=document.getElementById('rOrg');if(!a.value)a.value=p.position||'';if(!o.value)o.value=p.organisation||'';});})();</script>
</div>

<?php elseif ($tab === 'bulk'): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Several letters at once</h2><span class="admin-help">Same event, many people — one line each.</span></div>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="bulk">
    <div class="field"><label>People — one per line: Name | Title | Position | Email | WhatsApp</label><textarea name="people" style="min-height:160px;font-family:monospace;font-size:.85rem" placeholder="Jane Akello | Hon. | Chairperson | jane@example.org | 0772000000&#10;Peter Okello | Dr. | Executive Director, Example Foundation | | +256700111222"></textarea></div>
    <?php $eventFields(); ?>
    <button class="admin-btn admin-btn-brand">Create the letters</button>
  </form></div>

<?php elseif ($tab === 'settings'): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Letterhead &amp; template</h2><span class="admin-help">Letters use the official NUFA letterhead picture below.</span></div>
  <form method="post" enctype="multipart/form-data" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="settings">
    <div class="field"><label>Letterhead picture (shown at the top of every letter and email — about 4.5 × wider than tall)</label><input data-no-picker type="file" name="lt_letterhead" accept="image/png,image/jpeg,image/webp"><div style="margin-top:8px;background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px;display:inline-block"><img src="../assets/images/<?php echo $e(ltr_letterhead($conn)); ?>" alt="" style="max-width:100%;height:70px"></div><?php if ($S['lt_letterhead']): ?> <label style="display:inline-flex;gap:6px;align-items:center;margin-left:10px"><input type="checkbox" name="remove_letterhead" value="1"> Go back to the NUFA letterhead</label><?php else: ?><span class="admin-help"> The official NUFA letterhead is in use.</span><?php endif; ?></div>
    <div class="admin-form-grid">
      <div class="field"><label>Signed by (name)</label><input name="lt_signer_name" value="<?php echo $e($S['lt_signer_name']); ?>" placeholder="Blank = the president's name from Membership settings"></div>
      <div class="field"><label>Signer's title</label><input name="lt_signer_title" value="<?php echo $e($S['lt_signer_title']); ?>"></div>
      <div class="field"><label>Reference prefix</label><input name="lt_ref_prefix" value="<?php echo $e($S['lt_ref_prefix']); ?>"><span class="admin-help">Letters are numbered like <?php echo $e($S['lt_ref_prefix']); ?>/<?php echo date('Y'); ?>/0001</span></div>
      <div class="field"><label>Signature picture (transparent PNG is best)</label><input data-no-picker type="file" name="lt_signature" accept="image/png,image/jpeg,image/webp"><?php $sg = ltr_signer($conn); if ($sg['sig']): ?><div style="margin-top:8px;background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px;display:inline-block"><img src="../assets/images/<?php echo $e($sg['sig']); ?>" alt="" style="height:50px"></div><?php if ($S['lt_signature']): ?> <label style="display:inline-flex;gap:6px;align-items:center;margin-left:10px"><input type="checkbox" name="remove_signature" value="1"> Remove</label><?php else: ?><span class="admin-help"> (using the president's signature from Membership settings)</span><?php endif; ?><?php endif; ?></div>
    </div>
    <div class="field"><label>Letter template</label><textarea name="lt_template" style="min-height:260px;font-family:monospace;font-size:.84rem"><?php echo $e($S['lt_template']); ?></textarea>
      <span class="admin-help">Placeholders: {salutation} {name} {event} {EVENT} {date} {position_phrase} {time_phrase} {venue_phrase} {purpose} {rsvp} {closing}. Empty parts are left out automatically.</span></div>
    <div class="admin-form-grid"><div class="field"><label>RSVP sentence ({phone} and {email} come from Site settings)</label><textarea name="lt_rsvp" style="min-height:70px"><?php echo $e($S['lt_rsvp']); ?></textarea></div>
      <div class="field"><label>Closing line</label><textarea name="lt_closing" style="min-height:70px"><?php echo $e($S['lt_closing']); ?></textarea></div></div>
    <button class="admin-btn admin-btn-brand">Save settings</button>
  </form></div>

<?php else:
    $rows = $conn->query("SELECT * FROM invitation_letters ORDER BY created_at DESC, id DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC); ?>
<div class="admin-card"><div class="admin-card-head"><h2>Invitation letters (<?php echo count($rows); ?>)</h2><a class="admin-btn admin-btn-brand admin-btn-sm" href="letters.php?tab=new">+ New letter</a></div>
  <?php if (!$rows): ?><p style="color:var(--text-muted);padding:14px 0">No letters yet. Create the first one with <b>New letter</b>.</p><?php else: ?>
  <div style="overflow-x:auto"><table class="admin-table" data-noexport><thead><tr><th>Ref</th><th>Invitee</th><th>Event</th><th>Date</th><th>Sent</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $wa = ltr_wa_url($r); ?>
    <tr><td><?php echo $e($r['ref']); ?></td>
      <td><b><?php echo $e(trim($r['honorific'] . ' ' . $r['recipient_name'])); ?></b><br><span style="color:#777;font-size:.82rem"><?php echo $e($r['position']); ?></span></td>
      <td><?php echo $e($r['event_title']); ?></td><td><?php echo $r['event_date'] ? date('d M Y', strtotime($r['event_date'])) : '—'; ?></td>
      <td><?php echo $r['email_sent_at'] ? '<span class="admin-badge">Email</span> ' : ''; echo $r['wa_sent_at'] ? '<span class="admin-badge">WhatsApp</span>' : ''; echo (!$r['email_sent_at'] && !$r['wa_sent_at']) ? '<span class="admin-badge gray">Not sent</span>' : ''; ?></td>
      <td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="letters.php?id=<?php echo $r['id']; ?>">Open</a>
        <form method="post" style="display:inline"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button class="admin-btn admin-btn-line admin-btn-sm" name="action" value="email" <?php echo filter_var($r['email'], FILTER_VALIDATE_EMAIL) ? '' : 'disabled'; ?> data-confirm="Send this letter to <?php echo $e($r['email']); ?>?">Email</button></form>
        <?php if ($wa): ?><a class="admin-btn admin-btn-sm" style="background:#128C4A;color:#fff;border-color:#128C4A" href="<?php echo $e($wa); ?>" target="_blank" rel="noopener" data-wa="<?php echo $r['id']; ?>">WhatsApp</a><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
  <script>document.querySelectorAll('[data-wa]').forEach(function(a){a.addEventListener('click',function(){var f=new FormData();f.append('csrf','<?php echo mem_csrf(); ?>');f.append('action','mark_wa');f.append('id',a.dataset.wa);f.append('ajax','1');fetch('letters.php',{method:'POST',body:f,credentials:'same-origin'});});});</script>
  <?php endif; ?></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
