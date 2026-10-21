<?php
$adminTitle = "Academy live classes";
$adminNav = "academy";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/academy.php";
acad_install($conn);
$e = fn($v) => htmlspecialchars((string)$v); $error = '';
$modes = ['physical' => 'In person', 'virtual' => 'Online', 'hybrid' => 'In person + online'];
if (isset($_GET['delete'])) { $k = (int)$_GET['delete']; $conn->query("DELETE FROM academy_class_signups WHERE class_id=$k"); $conn->query("DELETE FROM academy_classes WHERE id=$k"); header("Location: academy-classes.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE academy_classes SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: academy-classes.php"); exit; }
if (isset($_GET['toggle_reg'])) { $conn->query("UPDATE academy_classes SET reg_open = 1 - reg_open WHERE id=" . (int)$_GET['toggle_reg']); header("Location: academy-classes.php"); exit; }
if (isset($_GET['rm_signup'])) { $conn->query("DELETE FROM academy_class_signups WHERE id=" . (int)$_GET['rm_signup']); header("Location: academy-classes.php?view=" . (int)($_GET['view'] ?? 0)); exit; }
$id = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0); $t = trim($_POST['title'] ?? ''); $mode = array_key_exists($_POST['mode'] ?? '', $modes) ? $_POST['mode'] : 'virtual'; $course = (int)($_POST['course_id'] ?? 0);
    $sd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['start_date'] ?? '') ? $_POST['start_date'] : null; $ed = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['end_date'] ?? '') ? $_POST['end_date'] : null;
    $tt = trim($_POST['time_text'] ?? ''); $venue = trim($_POST['venue'] ?? ''); $link = trim($_POST['meeting_link'] ?? ''); $fac = trim($_POST['facilitator'] ?? ''); $seats = max(0, (int)($_POST['seats'] ?? 0)); $desc = trim($_POST['description'] ?? ''); $fee = trim($_POST['fee_note'] ?? '');
    $reg = isset($_POST['reg_open']) ? 1 : 0; $act = isset($_POST['is_active']) ? 1 : 0;
    if ($t === '') $error = "Enter the class title."; elseif ($link !== '' && !preg_match('#^https?://#i', $link)) $error = "The meeting link must start with https://"; elseif ($sd && $ed && $ed < $sd) $error = "The end date must not be before the start date.";
    if (!$error) {
        if ($id) { $s = $conn->prepare("UPDATE academy_classes SET course_id=?,title=?,mode=?,start_date=?,end_date=?,time_text=?,venue=?,meeting_link=?,facilitator=?,seats=?,description=?,fee_note=?,reg_open=?,is_active=? WHERE id=?"); $s->bind_param("issssssssissiii", $course, $t, $mode, $sd, $ed, $tt, $venue, $link, $fac, $seats, $desc, $fee, $reg, $act, $id); }
        else { $s = $conn->prepare("INSERT INTO academy_classes (course_id,title,mode,start_date,end_date,time_text,venue,meeting_link,facilitator,seats,description,fee_note,reg_open,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("issssssssissii", $course, $t, $mode, $sd, $ed, $tt, $venue, $link, $fac, $seats, $desc, $fee, $reg, $act); }
        $s->execute(); header("Location: academy-classes.php?saved=1"); exit;
    }
}
$cur = $id ? $conn->query("SELECT * FROM academy_classes WHERE id=$id")->fetch_assoc() : null; if ($_SERVER['REQUEST_METHOD'] === 'POST') $cur = array_merge($cur ?: [], $_POST);
$f = fn($k, $d = '') => $e($cur[$k] ?? $d);
$courses = $conn->query("SELECT id, title FROM academy_courses ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$rows = $conn->query("SELECT k.*, (SELECT COUNT(*) FROM academy_class_signups s WHERE s.class_id=k.id) signups FROM academy_classes k ORDER BY k.start_date IS NULL, k.start_date DESC, k.id DESC")->fetch_all(MYSQLI_ASSOC);
$view = (int)($_GET['view'] ?? 0); $vclass = $view ? $conn->query("SELECT * FROM academy_classes WHERE id=$view")->fetch_assoc() : null;
$signups = $vclass ? $conn->query("SELECT s.id, s.created_at, l.name, l.email, l.phone FROM academy_class_signups s, academy_learners l WHERE l.id=s.learner_id AND s.class_id=$view ORDER BY s.created_at")->fetch_all(MYSQLI_ASSOC) : [];
include __DIR__ . "/includes/admin-header.php";
echo acad_admin_tabs('academy-classes.php');
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Class removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>
<?php if ($vclass): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Registered: <?php echo $e($vclass['title']); ?> (<?php echo count($signups); ?><?php echo $vclass['seats'] ? ' of ' . (int)$vclass['seats'] : ''; ?>)</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-classes.php">← All classes</a></div>
  <?php if (!$signups): ?><p class="admin-help">Nobody has registered yet.</p><?php else: ?><table class="admin-table" data-export="Class registrations"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th></th></tr></thead><tbody><?php foreach ($signups as $s): ?><tr><td><?php echo $e($s['name']); ?></td><td><?php echo $e($s['email']); ?></td><td><?php echo $e($s['phone']); ?></td><td><?php echo $e(date('j M Y H:i', strtotime($s['created_at']))); ?></td><td><a class="admin-btn admin-btn-danger admin-btn-sm" href="academy-classes.php?rm_signup=<?php echo $s['id']; ?>&view=<?php echo $view; ?>" data-confirm="Remove this registration?">Remove</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<?php endif; ?>
<div class="admin-card" id="class-form"><div class="admin-card-head"><h2><?php echo $id ? 'Edit class' : 'Schedule a live class'; ?></h2><span class="admin-help">The meeting link and venue are shown only to learners who have registered.</span></div>
  <form method="post" class="admin-form"><input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Class title</label><input name="title" required value="<?php echo $f('title'); ?>"></div>
      <div class="field"><label>Format</label><select name="mode"><?php foreach ($modes as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($cur['mode'] ?? 'virtual') === $k ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Linked course (optional)</label><select name="course_id"><option value="0">— none —</option><?php foreach ($courses as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo (int)($cur['course_id'] ?? 0) === (int)$c['id'] ? 'selected' : ''; ?>><?php echo $e($c['title']); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Start date</label><input type="date" name="start_date" value="<?php echo $f('start_date'); ?>"></div>
      <div class="field"><label>End date (if several days)</label><input type="date" name="end_date" value="<?php echo $f('end_date'); ?>"></div>
      <div class="field"><label>Time</label><input name="time_text" value="<?php echo $f('time_text'); ?>" placeholder="e.g. 10:00 AM – 1:00 PM EAT"></div>
      <div class="field"><label>Venue (in person)</label><input name="venue" value="<?php echo $f('venue'); ?>"></div>
      <div class="field"><label>Online meeting link (Zoom, Google Meet…)</label><input type="url" name="meeting_link" value="<?php echo $f('meeting_link'); ?>" placeholder="https://"></div>
      <div class="field"><label>Facilitator</label><input name="facilitator" value="<?php echo $f('facilitator'); ?>"></div>
      <div class="field"><label>Seats (0 = unlimited)</label><input type="number" name="seats" min="0" value="<?php echo $f('seats', '0'); ?>"></div>
      <div class="field"><label>Fee / note</label><input name="fee_note" value="<?php echo $f('fee_note'); ?>" placeholder="e.g. Free for enrolled learners"></div>
    </div>
    <div class="field"><label>Description</label><textarea name="description" rows="3"><?php echo $f('description'); ?></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin:6px 0 14px"><label><input type="checkbox" name="reg_open" <?php echo !$cur || !empty($cur['reg_open']) ? 'checked' : ''; ?>> Registration open</label><label><input type="checkbox" name="is_active" <?php echo !$cur || !empty($cur['is_active']) ? 'checked' : ''; ?>> Visible on the website</label></div>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $id ? 'Save class' : 'Add class'; ?></button><?php if ($id): ?><a class="admin-btn admin-btn-line" href="academy-classes.php">Cancel</a><?php endif; ?></div></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>All classes (<?php echo count($rows); ?>)</h2></div>
  <?php if (!$rows): ?><p class="admin-help">No classes scheduled. Nothing is shown on the website until you add one.</p><?php else: ?><table class="admin-table" data-export="Academy classes"><thead><tr><th>Class</th><th>Format</th><th>When</th><th>Registered</th><th>Registration</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $k): ?><tr><td><b><?php echo $e($k['title']); ?></b><br><small><?php echo $e($k['facilitator']); ?></small></td><td><?php echo $e($modes[$k['mode']] ?? ''); ?></td><td><?php echo $k['start_date'] ? $e(date('j M Y', strtotime($k['start_date']))) : 'TBA'; ?></td><td><a href="academy-classes.php?view=<?php echo $k['id']; ?>"><?php echo (int)$k['signups']; ?><?php echo $k['seats'] ? ' / ' . (int)$k['seats'] : ''; ?></a></td>
    <td><a href="academy-classes.php?toggle_reg=<?php echo $k['id']; ?>" class="admin-badge <?php echo $k['reg_open'] ? '' : 'gray'; ?>"><?php echo $k['reg_open'] ? 'Open' : 'Closed'; ?></a></td><td><a href="academy-classes.php?toggle=<?php echo $k['id']; ?>" class="admin-badge <?php echo $k['is_active'] ? '' : 'gray'; ?>"><?php echo $k['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
    <td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-classes.php?view=<?php echo $k['id']; ?>">Registered</a> <a class="admin-btn admin-btn-line admin-btn-sm" href="academy-classes.php?edit=<?php echo $k['id']; ?>#class-form">Edit</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="academy-classes.php?delete=<?php echo $k['id']; ?>" data-confirm="Delete this class and its registrations?">Delete</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
