<?php
$adminTitle = "Feedback";
$adminNav = "feedback";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/feedback.php";
fb_install($conn);

$e = fn($v) => htmlspecialchars((string)$v);
$msg = ''; $err = '';
$tab = in_array($_GET['tab'] ?? '', ['insights', 'settings'], true) ? $_GET['tab'] : 'list';
$cats = fb_categories(); $types = fb_types(); $statuses = fb_statuses();
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };

if (isset($_GET['delete'])) { $conn->query("DELETE FROM feedback WHERE id=" . (int)$_GET['delete']); header("Location: feedback.php?deleted=1"); exit; }
if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT ref_no, created_at, category, about, feedback_type, rating, participated, recommend, comment, suggestion, identity, name, email, phone, status, is_public FROM feedback ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="feedback.csv"');
    $o = fopen('php://output', 'w'); if ($rows) fputcsv($o, array_keys($rows[0])); foreach ($rows as $r) fputcsv($o, $r); fclose($o); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0); $f = $id ? $conn->query("SELECT * FROM feedback WHERE id=$id")->fetch_assoc() : null;
    if ($f && $act === 'save') {
        $st = array_key_exists($_POST['status'] ?? '', $statuses) ? $_POST['status'] : 'new'; $note = mb_substr(trim($_POST['admin_note'] ?? ''), 0, 500);
        $pub = isset($_POST['is_public']) ? 1 : 0; $pn = mb_substr(trim($_POST['public_name'] ?? ''), 0, 150);
        $u = $conn->prepare("UPDATE feedback SET status=?, admin_note=?, is_public=?, public_name=? WHERE id=?"); $u->bind_param("ssisi", $st, $note, $pub, $pn, $id); $u->execute(); $msg = "Saved."; $_GET['view'] = $id;
    } elseif ($f && $act === 'reply') {
        $body = trim($_POST['reply'] ?? '');
        if ($f['email'] === '') $err = "This person did not leave an email address.";
        elseif ($body === '') $err = "Write a reply first.";
        else {
            $S0 = fb_settings($conn); $text = "Hello " . ($f['name'] ?: 'there') . ",\n\n" . $body . "\n\n(Your feedback {$f['ref_no']}: " . mb_strimwidth($f['comment'], 0, 200, '…') . ")\n\n" . $S0['fb_reply_footer'];
            $ok = mem_send($conn, null, 'feedback-reply', $f['email'], strtr($S0['fb_reply_subject'], ['{ref}' => $f['ref_no']]), $text);
            $conn->query("UPDATE feedback SET replied_at=NOW(), status=IF(status='new','reviewed',status) WHERE id=$id"); $msg = $ok ? "Reply sent." : "Reply saved, but the mail system refused it (normal on a local XAMPP install).";
        }
        $_GET['view'] = $id;
    } elseif ($act === 'settings') {
        $tab = 'settings'; foreach (['fb_intro', 'fb_notify_email', 'fb_reply_subject', 'fb_reply_footer'] as $k) $save($k, mb_substr(trim($_POST[$k] ?? ''), 0, 2000));
        $save('fb_enabled', isset($_POST['fb_enabled']) ? '1' : '0'); $save('fb_show_public', isset($_POST['fb_show_public']) ? '1' : '0');
        if (trim($_POST['fb_notify_email'] ?? '') !== '' && !filter_var(trim($_POST['fb_notify_email']), FILTER_VALIDATE_EMAIL)) $err = "The notification email is not valid."; else $msg = "Settings saved.";
    } elseif ($act === 'bulk' && !empty($_POST['ids'])) {
        $ids = implode(',', array_map('intval', (array)$_POST['ids'])); $st = array_key_exists($_POST['bulk_status'] ?? '', $statuses) ? $_POST['bulk_status'] : '';
        if ($st) { $conn->query("UPDATE feedback SET status='$st' WHERE id IN ($ids)"); $msg = "Updated."; }
    }
}

$S = fb_settings($conn, true);
$view = (int)($_GET['view'] ?? 0); $f = $view ? $conn->query("SELECT * FROM feedback WHERE id=$view")->fetch_assoc() : null;
if ($f && $f['status'] === 'new' && $_SERVER['REQUEST_METHOD'] !== 'POST') { $conn->query("UPDATE feedback SET status='reviewed' WHERE id=$view"); $f['status'] = 'reviewed'; }
$newCount = (int)$conn->query("SELECT COUNT(*) FROM feedback WHERE status='new'")->fetch_row()[0];
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="feedback.php" class="<?php echo $tab === 'list' && !$f ? 'active' : ''; ?>">All feedback<?php echo $newCount ? " ($newCount new)" : ''; ?></a><a href="feedback.php?tab=insights" class="<?php echo $tab === 'insights' ? 'active' : ''; ?>">Insights</a><a href="feedback.php?tab=settings" class="<?php echo $tab === 'settings' ? 'active' : ''; ?>">Settings</a><a href="../feedback.php" target="_blank">View the form ↗</a></div>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Feedback deleted.</div><?php endif; ?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'settings'): ?>
<form method="post" class="admin-form admin-card"><input type="hidden" name="action" value="settings">
  <div class="admin-card-head"><h2>Feedback form</h2></div>
  <label class="mem-check"><input type="checkbox" name="fb_enabled" value="1" <?php echo $S['fb_enabled'] === '1' ? 'checked' : ''; ?>> The feedback form is open</label>
  <label class="mem-check"><input type="checkbox" name="fb_show_public" value="1" <?php echo $S['fb_show_public'] === '1' ? 'checked' : ''; ?>> Show approved comments on the Feedback page (“What people say”)</label>
  <div class="field" style="margin-top:10px"><label>Introduction above the form</label><textarea name="fb_intro" style="min-height:90px"><?php echo $e($S['fb_intro']); ?></textarea></div>
  <div class="field"><label>Send new-feedback alerts to</label><input type="email" name="fb_notify_email" value="<?php echo $e($S['fb_notify_email']); ?>" placeholder="Defaults to the membership alert address"></div>
  <div class="admin-form-grid"><div class="field"><label>Reply email subject</label><input name="fb_reply_subject" value="<?php echo $e($S['fb_reply_subject']); ?>"></div><div class="field"><label>Reply sign-off</label><input name="fb_reply_footer" value="<?php echo $e($S['fb_reply_footer']); ?>"></div></div>
  <p class="admin-help">The hero text, section headings and SEO are edited in <a href="page-editor.php?page=feedback">Page Editor → Feedback</a>.</p>
  <button class="admin-btn admin-btn-brand">Save settings</button></form>

<?php elseif ($tab === 'insights'):
    $tot = $conn->query("SELECT COUNT(*) n, AVG(rating) a, SUM(identity='anonymous') an, SUM(created_at >= (NOW() - INTERVAL 30 DAY)) m FROM feedback")->fetch_assoc();
    $dist = array_fill(1, 5, 0); foreach ($conn->query("SELECT rating, COUNT(*) c FROM feedback GROUP BY rating")->fetch_all(MYSQLI_ASSOC) as $r) if ($r['rating'] >= 1) $dist[(int)$r['rating']] = (int)$r['c'];
    $maxd = max(1, max($dist));
    $byCat = $conn->query("SELECT category, COUNT(*) n, AVG(rating) a FROM feedback GROUP BY category ORDER BY n DESC")->fetch_all(MYSQLI_ASSOC);
    $byAbout = $conn->query("SELECT about, COUNT(*) n, AVG(rating) a, SUM(feedback_type='Concern') c FROM feedback WHERE about<>'' GROUP BY about ORDER BY n DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);
    $byType = $conn->query("SELECT feedback_type, COUNT(*) n FROM feedback GROUP BY feedback_type")->fetch_all(MYSQLI_ASSOC); ?>
<div class="stat-row"><div><b><?php echo (int)$tot['n']; ?></b><span>Total feedback</span></div><div><b><?php echo $tot['n'] ? number_format((float)$tot['a'], 1) : '—'; ?></b><span>Average rating (of 5)</span></div><div><b><?php echo (int)$tot['m']; ?></b><span>Last 30 days</span></div><div><b><?php echo $tot['n'] ? round($tot['an'] / $tot['n'] * 100) : 0; ?>%</b><span>Anonymous</span></div></div>
<div class="edit-grid"><div class="admin-card"><div class="admin-card-head"><h2>Ratings</h2></div>
  <?php for ($i = 5; $i >= 1; $i--): ?><div class="bar-row"><span><?php echo $i; ?> ★</span><div class="bar-track"><i style="width:<?php echo round($dist[$i] / $maxd * 100); ?>%"></i></div><b><?php echo $dist[$i]; ?></b></div><?php endfor; ?>
  <h3 class="mem-h3">By type</h3><?php foreach ($byType as $r): ?><div class="mem-row"><span style="width:auto;flex:1"><?php echo $e($types[$r['feedback_type']] ?? $r['feedback_type']); ?></span><b style="flex:none"><?php echo (int)$r['n']; ?></b></div><?php endforeach; ?></div>
<div class="admin-card"><div class="admin-card-head"><h2>By area</h2></div>
  <?php foreach ($byCat as $r): ?><div class="mem-row"><span style="width:auto;flex:1"><?php echo $e($cats[$r['category']] ?? $r['category']); ?></span><span style="flex:none;color:#8a6a1c;font-weight:700"><?php echo number_format((float)$r['a'], 1); ?> ★</span><b style="flex:none;min-width:34px;text-align:right"><?php echo (int)$r['n']; ?></b></div><?php endforeach; if (!$byCat) echo '<p class="admin-help">No feedback yet.</p>'; ?></div></div>
<div class="admin-card"><div class="admin-card-head"><h2>Most-discussed programmes, activities &amp; initiatives</h2></div>
  <table class="admin-table"><thead><tr><th>Subject</th><th>Feedback</th><th>Average rating</th><th>Concerns</th></tr></thead><tbody>
  <?php foreach ($byAbout as $r): ?><tr><td><?php echo $e($r['about']); ?></td><td><?php echo (int)$r['n']; ?></td><td><?php echo fb_stars(round($r['a'])); ?> <?php echo number_format((float)$r['a'], 1); ?></td><td><?php echo (int)$r['c'] ?: '—'; ?></td></tr><?php endforeach; if (!$byAbout) echo '<tr><td colspan="4" style="color:var(--text-muted)">No feedback yet.</td></tr>'; ?></tbody></table></div>

<?php elseif ($f): ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo fb_stars($f['rating'], 20); ?> <?php echo $e($f['feedback_type']); ?> · <?php echo $e($f['about'] ?: ($cats[$f['category']] ?? $f['category'])); ?></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="feedback.php">← All feedback</a></div>
  <p><span class="reg-badge-sm"><?php echo $e($f['ref_no']); ?></span> · <?php echo date('d M Y H:i', strtotime($f['created_at'])); ?> · <?php echo $f['identity'] === 'anonymous' ? '<span class="admin-badge gray">Anonymous</span>' : '<span class="admin-badge">' . $e($f['name']) . '</span>'; ?></p>
  <dl class="app-dl"><dt>Area</dt><dd><?php echo $e($cats[$f['category']] ?? $f['category']); ?></dd><dt>Subject</dt><dd><?php echo $e($f['about'] ?: '—'); ?></dd><?php if ($f['participated']): ?><dt>Took part?</dt><dd><?php echo $e($f['participated']); ?></dd><?php endif; ?><?php if ($f['recommend']): ?><dt>Would recommend?</dt><dd><?php echo $e($f['recommend']); ?></dd><?php endif; ?>
    <dt>Feedback</dt><dd><?php echo nl2br($e($f['comment'])); ?></dd><?php if (trim((string)$f['suggestion']) !== ''): ?><dt>Could do better</dt><dd><?php echo nl2br($e($f['suggestion'])); ?></dd><?php endif; ?>
    <?php if ($f['identity'] === 'named'): ?><dt>Contact</dt><dd><?php echo $e(trim($f['email'] . ' ' . $f['phone'])) ?: '—'; ?><?php echo $f['allow_contact'] ? ' <span class="admin-badge">OK to contact</span>' : ''; ?></dd><?php endif; ?>
    <dt>Public use</dt><dd><?php echo $f['allow_publish'] ? 'Allowed to share the comments' . ($f['identity'] === 'named' ? ' with name' : ' without a name') : 'Not allowed'; ?></dd></dl></div>
<div class="edit-grid"><form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?php echo $f['id']; ?>"><div class="admin-card-head"><h2>Handle</h2></div>
  <div class="field"><label>Status</label><select name="status"><?php foreach ($statuses as $k => $l) echo '<option value="' . $k . '"' . ($f['status'] === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
  <div class="field"><label>Internal note</label><textarea name="admin_note" style="min-height:80px"><?php echo $e($f['admin_note']); ?></textarea></div>
  <label class="mem-check"><input type="checkbox" name="is_public" value="1" <?php echo $f['is_public'] ? 'checked' : ''; ?>> Show on the Feedback page</label>
  <?php if (!$f['allow_publish']): ?><p class="admin-help" style="color:#b3432f">The sender did not agree to public sharing — only publish if you have permission.</p><?php endif; ?>
  <div class="field"><label>Name shown publicly (blank = “Anonymous”<?php echo $f['identity'] === 'named' ? ' or their name if they agreed' : ''; ?>)</label><input name="public_name" value="<?php echo $e($f['public_name']); ?>"></div>
  <button class="admin-btn admin-btn-brand">Save</button> <a class="admin-btn admin-btn-danger admin-btn-sm" href="feedback.php?delete=<?php echo $f['id']; ?>" data-confirm="Delete this feedback permanently?">Delete</a></form>
<form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?php echo $f['id']; ?>"><div class="admin-card-head"><h2>Reply</h2></div>
  <?php require_once __DIR__ . "/../includes/whatsapp.php"; if ($f['phone'] !== '' && ($wa = nufa_wa_reply_url($f['phone'], 'Hello ' . ($f['name'] ?: '') . ', thank you for your feedback (' . $f['ref_no'] . ') to NUFA. '))): ?><p><a class="admin-btn admin-btn-line" href="<?php echo $e($wa); ?>" target="_blank" rel="noopener" style="border-color:#25D366;color:#128c4a">Reply on WhatsApp</a></p><?php endif; ?>
  <?php if ($f['email'] === ''): ?><p class="admin-help">No reply is possible: <?php echo $f['identity'] === 'anonymous' ? 'this feedback was sent anonymously.' : 'no email address was given.'; ?></p>
  <?php else: ?><?php echo $f['replied_at'] ? '<p class="admin-help">Last replied ' . date('d M Y H:i', strtotime($f['replied_at'])) . '.</p>' : ''; ?><div class="field"><label>Message to <?php echo $e($f['email']); ?></label><textarea name="reply" style="min-height:110px"></textarea></div><button class="admin-btn admin-btn-line">Send reply</button><?php endif; ?></form></div>

<?php else:
    $where = []; $filt = ['status' => $statuses, 'category' => $cats, 'feedback_type' => $types];
    foreach ($filt as $k => $opts) if (isset($_GET[$k]) && array_key_exists($_GET[$k], $opts)) $where[] = "$k='" . $conn->real_escape_string($_GET[$k]) . "'";
    if (in_array($_GET['identity'] ?? '', ['anonymous', 'named'], true)) $where[] = "identity='" . $_GET['identity'] . "'";
    if ((int)($_GET['rating'] ?? 0) >= 1 && (int)$_GET['rating'] <= 5) $where[] = "rating=" . (int)$_GET['rating'];
    $q = trim($_GET['q'] ?? ''); if ($q !== '') { $qq = $conn->real_escape_string($q); $where[] = "(comment LIKE '%$qq%' OR about LIKE '%$qq%' OR ref_no LIKE '%$qq%' OR name LIKE '%$qq%')"; }
    $rows = $conn->query("SELECT * FROM feedback" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY (status='new') DESC, id DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC);
    $sel = fn($n, $opts, $cur) => '<select name="' . $n . '" onchange="this.form.submit()" style="padding:8px 10px;border:1px solid #e4e0d6;border-radius:8px"><option value="">' . ucfirst(str_replace('_', ' ', $n)) . ': all</option>' . implode('', array_map(fn($k, $l) => '<option value="' . $k . '"' . ($cur === (string)$k ? ' selected' : '') . '>' . htmlspecialchars($l) . '</option>', array_keys($opts), $opts)) . '</select>'; ?>
<div class="admin-card"><div class="admin-card-head"><h2>Feedback</h2><span><form method="get" style="display:inline-flex;gap:6px;flex-wrap:wrap"><?php echo $sel('status', $statuses, $_GET['status'] ?? ''), $sel('category', $cats, $_GET['category'] ?? ''), $sel('feedback_type', $types, $_GET['feedback_type'] ?? ''), $sel('rating', [1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars'], $_GET['rating'] ?? ''), $sel('identity', ['anonymous' => 'Anonymous', 'named' => 'Named'], $_GET['identity'] ?? ''); ?><input name="q" value="<?php echo $e($q); ?>" placeholder="Search…" style="padding:8px 12px;border:1px solid #e4e0d6;border-radius:8px"><button class="admin-btn admin-btn-line admin-btn-sm">Go</button></form> <a class="admin-btn admin-btn-line admin-btn-sm" href="feedback.php?export=1">Export CSV</a></span></div>
  <form method="post"><input type="hidden" name="action" value="bulk"><table class="admin-table"><thead><tr><th></th><th>Ref</th><th>About</th><th>Type</th><th>Rating</th><th>From</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--text-muted)">No feedback yet.</td></tr><?php endif; ?>
  <?php foreach ($rows as $r): ?><tr<?php echo $r['status'] === 'new' ? ' style="background:#fffaf0"' : ''; ?>><td><input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>"></td><td><b><?php echo $e($r['ref_no']); ?></b><br><small><?php echo date('d M Y', strtotime($r['created_at'])); ?></small></td><td><?php echo $e($r['about'] ?: ($cats[$r['category']] ?? '')); ?><br><small class="muted"><?php echo $e(mb_strimwidth($r['comment'], 0, 70, '…')); ?></small></td><td><?php echo $e($r['feedback_type']); ?></td><td><?php echo fb_stars($r['rating'], 14); ?></td><td><?php echo $r['identity'] === 'anonymous' ? '<span class="admin-badge gray">Anonymous</span>' : $e($r['name']); ?></td><td><span class="admin-badge <?php echo $r['status'] === 'new' ? '' : 'gray'; ?>"><?php echo $e($statuses[$r['status']] ?? $r['status']); ?></span><?php echo $r['is_public'] ? ' <span class="admin-badge">Public</span>' : ''; ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="feedback.php?view=<?php echo $r['id']; ?>">Open</a></td></tr><?php endforeach; ?>
  </tbody></table>
  <?php if ($rows): ?><div style="display:flex;gap:8px;align-items:center;margin-top:12px"><span class="admin-help">With the selected:</span><select name="bulk_status" style="padding:8px 10px;border:1px solid #e4e0d6;border-radius:8px"><?php foreach ($statuses as $k => $l) echo '<option value="' . $k . '">Mark ' . strtolower($l) . '</option>'; ?></select><button class="admin-btn admin-btn-line admin-btn-sm">Apply</button></div><?php endif; ?></form></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
