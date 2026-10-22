<?php
$adminTitle = "Film Showcase";
$adminNav = "films";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/films.php";
film_install($conn);
$S = film_settings($conn); $e = fn($v) => htmlspecialchars((string)$v); $error = '';
$tabs = ['pending' => 'Waiting for review', 'approved' => 'Approved', 'changes' => 'Changes requested', 'rejected' => 'Rejected', 'all' => 'All', 'settings' => 'Settings'];
$tab = array_key_exists($_GET['tab'] ?? '', $tabs) ? $_GET['tab'] : 'pending';
$view = (int)($_GET['view'] ?? 0);

function film_rm_files($conn, $id) {
    $r = $conn->query("SELECT video_file, poster, fm_photo FROM films WHERE id=" . (int)$id)->fetch_assoc(); if (!$r) return;
    if ($r['video_file'] !== '') @unlink(film_storage() . basename($r['video_file']));
    foreach (['poster', 'fm_photo'] as $k) if (strpos($r[$k], 'uploads/films/') === 0) @unlink(__DIR__ . "/../assets/images/" . $r[$k]);
}
if (isset($_GET['delete'])) { $id = (int)$_GET['delete']; film_rm_files($conn, $id); $conn->query("DELETE FROM films WHERE id=$id"); header("Location: films.php?deleted=1"); exit; }
if (isset($_GET['star'])) { $conn->query("UPDATE films SET is_featured = 1 - is_featured WHERE id=" . (int)$_GET['star']); header("Location: films.php?tab=approved"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'settings') {
    $f = ['film_open' => isset($_POST['film_open']) ? '1' : '0', 'film_max_mb' => (string)max(10, min(5000, (int)($_POST['film_max_mb'] ?? 300))), 'film_max_minutes' => (string)max(0, (int)($_POST['film_max_minutes'] ?? 60)),
          'film_logo_pos' => in_array($_POST['film_logo_pos'] ?? '', ['tl', 'tr', 'bl', 'br'], true) ? $_POST['film_logo_pos'] : 'tr', 'film_logo_opacity' => (string)max(20, min(100, (int)($_POST['film_logo_opacity'] ?? 75)))];
    foreach (['film_notify_email', 'film_intro', 'film_submit_intro', 'film_guidelines', 'film_checklist', 'film_donate_note'] as $k) $f[$k] = trim($_POST[$k] ?? '');
    if ($f['film_notify_email'] !== '' && !filter_var($f['film_notify_email'], FILTER_VALIDATE_EMAIL)) { $error = "Enter a valid notification email."; $tab = 'settings'; }
    else { foreach ($f as $k => $v) film_save_setting($conn, $k, $v); header("Location: films.php?tab=settings&saved=1"); exit; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'film') {
    $id = (int)($_POST['id'] ?? 0); $a = $conn->query("SELECT * FROM films WHERE id=$id")->fetch_assoc();
    if ($a) {
        $act = $_POST['act'] ?? 'save'; $p = fn($k, $m = 190) => mb_substr(trim($_POST[$k] ?? ''), 0, $m);
        $title = $p('title') ?: $a['title']; $tag = $p('tagline', 250); $syn = mb_substr(trim($_POST['synopsis'] ?? ''), 0, 3000); $genre = in_array($_POST['genre'] ?? '', film_genres(), true) ? $_POST['genre'] : $a['genre'];
        $lang = $p('language', 80); $year = (int)($_POST['year'] ?? $a['year']); $mins = (int)($_POST['runtime_min'] ?? $a['runtime_min']); $rating = in_array($_POST['rating'] ?? '', film_ratings(), true) ? $_POST['rating'] : $a['rating'];
        $credits = mb_substr(trim($_POST['credits'] ?? ''), 0, 3000); $bio = mb_substr(trim($_POST['fm_bio'] ?? ''), 0, 3000); $notes = trim($_POST['admin_notes'] ?? '');
        $don = []; foreach (['donate_mtn' => 40, 'donate_airtel' => 40, 'donate_name' => 150, 'donate_link' => 490, 'donate_link_label' => 60, 'donate_bank' => 390, 'donate_note' => 390] as $k => $m) $don[$k] = $p($k, $m);
        if ($don['donate_link'] !== '' && !preg_match('#^https://#i', $don['donate_link'])) $don['donate_link'] = '';
        $url = $p('video_url', 490); $src = $a['source_type']; if ($src === 'link' && $url !== '' && film_link_ok($url)) { /* keep */ } else $url = $a['video_url'];
        $chk = implode(',', array_map('intval', (array)($_POST['chk'] ?? []))); $sort = (int)($_POST['sort_order'] ?? 0); $feat = isset($_POST['is_featured']) ? 1 : 0; $reason = trim($_POST['reason'] ?? '');
        $poster = $a['poster'];
        if (!empty($_FILES['poster']['name'])) { [$fn, $er] = mem_store_upload($_FILES['poster'], MEM_IMG_TYPES, 5 * 1048576, 'poster', FILM_IMG_DIR); if ($er) $error = "Poster: $er"; else { if (strpos($poster, 'uploads/films/') === 0) @unlink(__DIR__ . "/../assets/images/" . $poster); $poster = 'uploads/films/' . $fn; } }
        $status = $a['status']; if ($act === 'approve') $status = 'approved'; elseif ($act === 'reject') $status = 'rejected'; elseif ($act === 'changes') $status = 'changes'; elseif ($act === 'unpublish') $status = 'pending';
        if (!$error && $act === 'approve' && $poster === '' ) { /* poster optional: the site shows the title card */ }
        if (!$error && in_array($act, ['reject', 'changes'], true) && $reason === '') $error = "Write a message to the filmmaker explaining what is needed.";
        if (!$error) {
            $appr = $status === 'approved' ? ($a['approved_at'] ?: date('Y-m-d H:i:s')) : null; if ($status !== 'approved') $feat = 0;
            $s = $conn->prepare("UPDATE films SET title=?,tagline=?,synopsis=?,genre=?,language=?,year=?,runtime_min=?,rating=?,credits=?,fm_bio=?,poster=?,video_url=?,donate_mtn=?,donate_airtel=?,donate_name=?,donate_link=?,donate_link_label=?,donate_bank=?,donate_note=?,status=?,reject_reason=?,admin_notes=?,checklist=?,is_featured=?,sort_order=?,approved_at=? WHERE id=?");
            $rr = in_array($act, ['reject', 'changes'], true) ? $reason : ($status === 'approved' ? '' : $a['reject_reason']);
            $s->bind_param("sssssiissssssssssssssssiisi", $title, $tag, $syn, $genre, $lang, $year, $mins, $rating, $credits, $bio, $poster, $url, $don['donate_mtn'], $don['donate_airtel'], $don['donate_name'], $don['donate_link'], $don['donate_link_label'], $don['donate_bank'], $don['donate_note'], $status, $rr, $notes, $chk, $feat, $sort, $appr, $id);
            $s->execute();
            if ($status !== $a['status'] && !empty($_POST['send_email'])) {
                $statusUrl = '';
                if ($status === 'approved') mem_send($conn, null, 'film-approved', $a['email'], "Your film is live: $title", "Hello " . $a['fm_name'] . ",\n\nGreat news — “$title” has been approved and is now on the NUFA Film Showcase. Share the link with your audience.", mem_site_url() . '/film.php?slug=' . $a['slug']);
                elseif (in_array($status, ['rejected', 'changes'], true)) mem_send($conn, null, 'film-' . $status, $a['email'], ($status === 'changes' ? "Changes needed for your film: " : "Update on your film: ") . $title, "Hello " . $a['fm_name'] . ",\n\n" . ($status === 'changes' ? "Thank you for submitting “$title”. Before we can approve it, we need the following:" : "Thank you for submitting “$title”. We are not able to approve it at this time.") . "\n\n" . $reason . "\n\nYou are welcome to submit a new version.", mem_site_url() . '/film-submit.php');
            }
            header("Location: films.php?view=$id&saved=1"); exit;
        }
        $view = $id;
    }
}
$counts = ['pending' => 0, 'approved' => 0, 'changes' => 0, 'rejected' => 0]; foreach ($conn->query("SELECT status, COUNT(*) c FROM films GROUP BY status")->fetch_all(MYSQLI_ASSOC) as $c) $counts[$c['status']] = (int)$c['c'];
$A = $view ? $conn->query("SELECT * FROM films WHERE id=$view")->fetch_assoc() : null;
$rows = (!$A && $tab !== 'settings') ? $conn->query("SELECT * FROM films" . ($tab === 'all' ? '' : " WHERE status='$tab'") . " ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC) : [];
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><?php foreach ($tabs as $k => $l): ?><a href="films.php?tab=<?php echo $k; ?>" class="<?php echo ($tab === $k && !$A) ? 'active' : ''; ?>"><?php echo $l; ?><?php echo isset($counts[$k]) && $counts[$k] ? ' (' . $counts[$k] . ')' : ''; ?></a><?php endforeach; ?><a href="../films.php" target="_blank">View the Film Showcase ↗</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Film deleted.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>

<?php if ($A): $chkSel = array_map('intval', array_filter(explode(',', $A['checklist']), 'strlen')); $items = film_lines($S['film_checklist']); $src = film_source($A, true); $badge = ['pending' => 'gray', 'approved' => '', 'changes' => 'gray', 'rejected' => 'red'][$A['status']]; ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($A['title']); ?> <span class="admin-badge <?php echo $badge; ?>"><?php echo $e(['pending' => 'Waiting for review', 'approved' => 'Approved', 'changes' => 'Changes requested', 'rejected' => 'Rejected'][$A['status']]); ?></span> <small><?php echo $e($A['ref']); ?></small></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="films.php?tab=<?php echo $A['status']; ?>">← Back</a></div>
  <div style="max-width:760px;margin-bottom:14px"><?php echo film_player_html($conn, $A, $S, true); ?></div>
  <p class="admin-help">Source: <?php echo $A['source_type'] === 'mp4' ? 'MP4 upload (' . round($A['file_size'] / 1048576, 1) . ' MB)' : '<a href="' . $e($A['video_url']) . '" target="_blank" rel="noopener">' . $e($A['video_url']) . '</a>'; ?> · Submitted <?php echo $e(date('j M Y H:i', strtotime($A['created_at']))); ?> · <?php echo (int)$A['views']; ?> views · <?php echo (int)$A['support_clicks']; ?> “support” taps</p>
  <p class="admin-help"><b>Filmmaker:</b> <?php echo $e($A['fm_name']); ?> · <?php echo $e($A['email']); ?> · <?php echo $e($A['phone']); ?> · <a href="https://wa.me/<?php echo $e(preg_replace('/\D/', '', $A['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a></p>
  <script src="../assets/js/film-player.js" defer></script>
</div>
<form method="post" enctype="multipart/form-data" class="admin-card admin-form"><input type="hidden" name="form" value="film"><input type="hidden" name="id" value="<?php echo $A['id']; ?>">
  <div class="admin-card-head"><h2>Review checklist</h2></div>
  <?php foreach ($items as $i => $it): ?><label style="display:block;margin:6px 0"><input type="checkbox" name="chk[]" value="<?php echo $i; ?>" <?php echo in_array($i, $chkSel, true) ? 'checked' : ''; ?>> <?php echo $e($it); ?></label><?php endforeach; ?>
  <div class="admin-card-head" style="margin-top:18px"><h2>Film details (edit before publishing if needed)</h2></div>
  <div class="admin-form-grid">
    <div class="field"><label>Title</label><input name="title" value="<?php echo $e($A['title']); ?>"></div><div class="field"><label>Tagline</label><input name="tagline" value="<?php echo $e($A['tagline']); ?>"></div>
    <div class="field"><label>Genre</label><select name="genre"><?php foreach (film_genres() as $g): ?><option <?php echo $A['genre'] === $g ? 'selected' : ''; ?>><?php echo $e($g); ?></option><?php endforeach; ?></select></div><div class="field"><label>Language</label><input name="language" value="<?php echo $e($A['language']); ?>"></div>
    <div class="field"><label>Year</label><input type="number" name="year" value="<?php echo (int)$A['year']; ?>"></div><div class="field"><label>Minutes</label><input type="number" name="runtime_min" value="<?php echo (int)$A['runtime_min']; ?>"></div>
    <div class="field"><label>Suitable for</label><select name="rating"><?php foreach (film_ratings() as $r): ?><option <?php echo $A['rating'] === $r ? 'selected' : ''; ?>><?php echo $e($r); ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Poster (replace)</label><input type="file" name="poster" accept="image/jpeg,image/png,image/webp"><?php if (film_poster_ok($A)): ?><img src="../assets/images/<?php echo $e($A['poster']); ?>" alt="" style="width:120px;border-radius:8px;margin-top:6px"><?php endif; ?></div>
    <?php if ($A['source_type'] === 'link'): ?><div class="field"><label>Video link</label><input type="url" name="video_url" value="<?php echo $e($A['video_url']); ?>"></div><?php endif; ?>
    <div class="field"><label>Display order (lower first)</label><input type="number" name="sort_order" value="<?php echo (int)$A['sort_order']; ?>"></div>
  </div>
  <div class="field"><label>Synopsis</label><textarea name="synopsis" rows="4"><?php echo $e($A['synopsis']); ?></textarea></div>
  <div class="field"><label>Credits (one per line)</label><textarea name="credits" rows="3"><?php echo $e($A['credits']); ?></textarea></div>
  <div class="field"><label>Filmmaker biography</label><textarea name="fm_bio" rows="3"><?php echo $e($A['fm_bio']); ?></textarea></div>
  <div class="admin-card-head" style="margin-top:12px"><h2>Support details shown to viewers</h2><span class="admin-help">Check they look genuine. NUFA does not handle these gifts.</span></div>
  <div class="admin-form-grid"><div class="field"><label>MTN number</label><input name="donate_mtn" value="<?php echo $e($A['donate_mtn']); ?>"></div><div class="field"><label>Airtel number</label><input name="donate_airtel" value="<?php echo $e($A['donate_airtel']); ?>"></div><div class="field"><label>Registered name</label><input name="donate_name" value="<?php echo $e($A['donate_name']); ?>"></div>
    <div class="field"><label>Online link</label><input name="donate_link" value="<?php echo $e($A['donate_link']); ?>"></div><div class="field"><label>Link label</label><input name="donate_link_label" value="<?php echo $e($A['donate_link_label']); ?>"></div><div class="field"><label>Bank details</label><input name="donate_bank" value="<?php echo $e($A['donate_bank']); ?>"></div><div class="field"><label>Message to supporters</label><input name="donate_note" value="<?php echo $e($A['donate_note']); ?>"></div></div>
  <div class="field"><label>Internal notes</label><textarea name="admin_notes" rows="2"><?php echo $e($A['admin_notes']); ?></textarea></div>
  <div class="field"><label>Message to the filmmaker (required for “Request changes” or “Reject”)</label><textarea name="reason" rows="3"><?php echo $e($A['reject_reason']); ?></textarea></div>
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin:8px 0 14px"><label><input type="checkbox" name="send_email" value="1" checked> Email the filmmaker about the decision</label><label><input type="checkbox" name="is_featured" <?php echo $A['is_featured'] ? 'checked' : ''; ?>> Feature on the Film Showcase</label></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand" name="act" value="approve"><?php echo $A['status'] === 'approved' ? 'Save (stay approved)' : 'Approve and publish'; ?></button><button class="admin-btn admin-btn-line" name="act" value="changes">Request changes</button><button class="admin-btn admin-btn-danger" name="act" value="reject">Reject</button><?php if ($A['status'] === 'approved'): ?><button class="admin-btn admin-btn-line" name="act" value="unpublish">Unpublish</button><?php endif; ?><button class="admin-btn admin-btn-line" name="act" value="save">Save without deciding</button><a class="admin-btn admin-btn-danger" href="films.php?delete=<?php echo $A['id']; ?>" data-confirm="Delete this film and its files permanently?">Delete</a></div>
</form>

<?php elseif ($tab === 'settings'): $limit = film_server_limit_mb(); ?>
<form method="post" class="admin-card admin-form"><input type="hidden" name="form" value="settings"><div class="admin-card-head"><h2>Film Showcase settings</h2></div>
  <div class="admin-form-grid">
    <div class="field"><label>Maximum MP4 size (MB)</label><input type="number" name="film_max_mb" min="10" value="<?php echo $e($S['film_max_mb']); ?>"><small class="admin-help">This server currently accepts uploads up to about <b><?php echo $limit; ?> MB</b>; larger files need the hosting PHP limits raised (upload_max_filesize and post_max_size). Filmmakers can always send a link instead.</small></div>
    <div class="field"><label>Maximum running time (minutes, 0 = no limit)</label><input type="number" name="film_max_minutes" min="0" value="<?php echo $e($S['film_max_minutes']); ?>"></div>
    <div class="field"><label>NUFA logo on the player — position</label><select name="film_logo_pos"><?php foreach (['tr' => 'Top right', 'tl' => 'Top left', 'br' => 'Bottom right', 'bl' => 'Bottom left'] as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo $S['film_logo_pos'] === $k ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Logo opacity (%)</label><input type="number" name="film_logo_opacity" min="20" max="100" value="<?php echo $e($S['film_logo_opacity']); ?>"></div>
    <div class="field"><label>Notification email for new submissions</label><input type="email" name="film_notify_email" value="<?php echo $e($S['film_notify_email']); ?>" placeholder="Leave empty to use the membership notification address"></div></div>
  <label style="display:block;margin:10px 0"><input type="checkbox" name="film_open" <?php echo $S['film_open'] === '1' ? 'checked' : ''; ?>> Film submissions are open</label>
  <div class="field"><label>Showcase introduction</label><textarea name="film_intro" rows="2"><?php echo $e($S['film_intro']); ?></textarea></div>
  <div class="field"><label>Submission page introduction</label><textarea name="film_submit_intro" rows="3"><?php echo $e($S['film_submit_intro']); ?></textarea></div>
  <div class="field"><label>Submission guidelines (one per line — shown to filmmakers)</label><textarea name="film_guidelines" rows="6"><?php echo $e($S['film_guidelines']); ?></textarea></div>
  <div class="field"><label>Review checklist (one per line — used when you approve)</label><textarea name="film_checklist" rows="6"><?php echo $e($S['film_checklist']); ?></textarea></div>
  <div class="field"><label>Support notice (shown with every film’s support details)</label><textarea name="film_donate_note" rows="3"><?php echo $e($S['film_donate_note']); ?></textarea></div>
  <button class="admin-btn admin-btn-brand">Save settings</button></form>

<?php else: ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($tabs[$tab]); ?> (<?php echo count($rows); ?>)</h2></div>
  <?php if (!$rows): ?><p class="admin-help">Nothing here yet.</p><?php else: ?><table class="admin-table" data-export="Film submissions"><thead><tr><th>Film</th><th>Filmmaker</th><th>Genre</th><th>Source</th><th>Status</th><th>Views</th><th>Submitted</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><div class="ax-title-cell"><?php if (film_poster_ok($r)): ?><img class="admin-thumb" src="../assets/images/<?php echo $e($r['poster']); ?>" alt=""><?php endif; ?><div><b><?php echo $e($r['title']); ?></b><?php echo $r['is_featured'] ? ' <span class="admin-badge">★</span>' : ''; ?><br><small><?php echo $e($r['ref']); ?> · <?php echo (int)$r['runtime_min']; ?> min</small></div></div></td><td><?php echo $e($r['fm_name']); ?><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e($r['genre']); ?></td><td><?php echo $r['source_type'] === 'mp4' ? 'MP4' : 'Link'; ?></td>
    <td><span class="admin-badge <?php echo ['pending' => 'gray', 'approved' => '', 'changes' => 'gray', 'rejected' => 'red'][$r['status']]; ?>"><?php echo $e(['pending' => 'Waiting', 'approved' => 'Approved', 'changes' => 'Changes', 'rejected' => 'Rejected'][$r['status']]); ?></span></td><td><?php echo (int)$r['views']; ?></td><td><?php echo $e(date('j M Y', strtotime($r['created_at']))); ?></td><td style="white-space:nowrap"><a class="admin-btn admin-btn-brand admin-btn-sm" href="films.php?view=<?php echo $r['id']; ?>"><?php echo $r['status'] === 'pending' ? 'Review' : 'Open'; ?></a><?php if ($r['status'] === 'approved'): ?> <a class="admin-btn admin-btn-line admin-btn-sm" href="films.php?star=<?php echo $r['id']; ?>"><?php echo $r['is_featured'] ? 'Unfeature' : 'Feature'; ?></a> <a class="admin-btn admin-btn-line admin-btn-sm" href="../film.php?slug=<?php echo $e($r['slug']); ?>" target="_blank">View</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
