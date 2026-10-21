<?php
$adminTitle = "NUFA Academy";
$adminNav = "academy";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/academy.php";
acad_install($conn);
$tab = ($_GET['tab'] ?? '') === 'settings' ? 'settings' : 'courses';
$S = acad_settings($conn); $e = fn($v) => htmlspecialchars((string)$v); $error = '';

if (isset($_GET['toggle'])) { $conn->query("UPDATE academy_courses SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: academy.php"); exit; }
if (isset($_GET['toggle_enrol'])) { $conn->query("UPDATE academy_courses SET enrol_open = 1 - enrol_open WHERE id=" . (int)$_GET['toggle_enrol']); header("Location: academy.php"); exit; }
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; $r = $conn->query("SELECT cover FROM academy_courses WHERE id=$id")->fetch_assoc();
    if ($r && strpos($r['cover'], 'uploads/academy/') === 0) @unlink(__DIR__ . "/../assets/images/" . $r['cover']);
    $conn->query("DELETE FROM academy_progress WHERE enrolment_id IN (SELECT id FROM academy_enrolments WHERE course_id=$id)");
    $conn->query("DELETE FROM academy_attempts WHERE enrolment_id IN (SELECT id FROM academy_enrolments WHERE course_id=$id)");
    foreach (['academy_enrolments', 'academy_lessons', 'academy_questions'] as $t) $conn->query("DELETE FROM $t WHERE course_id=$id");
    $conn->query("DELETE FROM academy_courses WHERE id=$id"); header("Location: academy.php?deleted=1"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'settings') {
    $int = fn($k, $min, $max) => (string)max($min, min($max, (int)($_POST[$k] ?? 0)));
    $f = ['acad_pass_mark' => $int('acad_pass_mark', 1, 100), 'acad_exam_minutes' => $int('acad_exam_minutes', 1, 300), 'acad_exam_questions' => $int('acad_exam_questions', 1, 200), 'acad_max_attempts' => $int('acad_max_attempts', 0, 99), 'acad_retake_hours' => $int('acad_retake_hours', 0, 720),
          'acad_registration_open' => isset($_POST['acad_registration_open']) ? '1' : '0', 'acad_require_lessons' => isset($_POST['acad_require_lessons']) ? '1' : '0', 'acad_show_answers' => isset($_POST['acad_show_answers']) ? '1' : '0', 'acad_trainers_open' => isset($_POST['acad_trainers_open']) ? '1' : '0'];
    foreach (['acad_intro', 'acad_cert_title', 'acad_cert_org', 'acad_cert_sub', 'acad_sign1_name', 'acad_sign1_title', 'acad_sign2_name', 'acad_sign2_title', 'acad_accreditation', 'acad_payment_info', 'acad_notify_email', 'acad_trainer_intro'] as $k) $f[$k] = trim($_POST[$k] ?? '');
    if ($f['acad_notify_email'] !== '' && !filter_var($f['acad_notify_email'], FILTER_VALIDATE_EMAIL)) { $error = "Enter a valid notification email."; $tab = 'settings'; }
    else { foreach ($f as $k => $v) acad_save_setting($conn, $k, $v); header("Location: academy.php?tab=settings&saved=1"); exit; }
}
$id = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'course') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? ''); $level = array_key_exists($_POST['level'] ?? '', acad_levels()) ? $_POST['level'] : 'Beginner'; $cat = trim($_POST['category'] ?? '');
    $sum = trim($_POST['summary'] ?? ''); $desc = trim($_POST['description'] ?? ''); $out = trim($_POST['outcomes'] ?? ''); $dur = trim($_POST['duration'] ?? '');
    $fee = max(0, (int)preg_replace('/\D/', '', $_POST['fee'] ?? '0')); $cur = in_array($_POST['currency'] ?? '', ['UGX', 'USD', 'EUR', 'GBP'], true) ? $_POST['currency'] : 'UGX';
    $pm = max(0, min(100, (int)($_POST['pass_mark'] ?? 0))); $em = max(0, (int)($_POST['exam_minutes'] ?? 0)); $eq = max(0, (int)($_POST['exam_questions'] ?? 0));
    $act = isset($_POST['is_active']) ? 1 : 0; $eo = isset($_POST['enrol_open']) ? 1 : 0; $ft = isset($_POST['is_featured']) ? 1 : 0;
    $old = $id ? ($conn->query("SELECT cover FROM academy_courses WHERE id=$id")->fetch_assoc()['cover'] ?? '') : ''; $cover = $old;
    if ($title === '') $error = "Enter the course title.";
    if (!$error && !empty($_FILES['cover']['name'])) { [$fn, $er] = mem_store_upload($_FILES['cover'], MEM_IMG_TYPES, 6 * 1048576, 'course', __DIR__ . "/../assets/images/uploads/academy/"); if ($er) $error = "Cover: $er"; else { $cover = 'uploads/academy/' . $fn; if (strpos($old, 'uploads/academy/') === 0) @unlink(__DIR__ . "/../assets/images/" . $old); } }
    if (!$error) {
        if ($id) { $s = $conn->prepare("UPDATE academy_courses SET title=?,level=?,category=?,summary=?,description=?,outcomes=?,duration=?,fee=?,currency=?,pass_mark=?,exam_minutes=?,exam_questions=?,cover=?,is_active=?,enrol_open=?,is_featured=? WHERE id=?"); $s->bind_param("sssssssisiiisiiii", $title, $level, $cat, $sum, $desc, $out, $dur, $fee, $cur, $pm, $em, $eq, $cover, $act, $eo, $ft, $id); $s->execute(); $go = $id; }
        else { $slug = acad_slug($title, $conn); $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM academy_courses")->fetch_row()[0];
            $s = $conn->prepare("INSERT INTO academy_courses (slug,title,level,category,summary,description,outcomes,duration,fee,currency,pass_mark,exam_minutes,exam_questions,cover,is_active,enrol_open,is_featured,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("ssssssssisiiisiiii", $slug, $title, $level, $cat, $sum, $desc, $out, $dur, $fee, $cur, $pm, $em, $eq, $cover, $act, $eo, $ft, $o); $s->execute(); $go = $conn->insert_id; }
        header("Location: academy-course.php?id=$go&saved=1"); exit;
    }
}
$cur = $id ? $conn->query("SELECT * FROM academy_courses WHERE id=$id")->fetch_assoc() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'course') $cur = array_merge($cur ?: [], $_POST);
$f = fn($k, $d = '') => $e($cur[$k] ?? $d);
$courses = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM academy_lessons l WHERE l.course_id=c.id) lessons, (SELECT COUNT(*) FROM academy_questions q WHERE q.course_id=c.id) qs, (SELECT COUNT(*) FROM academy_enrolments n WHERE n.course_id=c.id) enrolled, (SELECT COUNT(*) FROM academy_enrolments n WHERE n.course_id=c.id AND n.status='completed') passed FROM academy_courses c ORDER BY c.sort_order, c.id")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
echo acad_admin_tabs($tab === 'settings' ? 'academy.php?tab=settings' : 'academy.php');
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Course deleted.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>

<?php if ($tab === 'settings'): ?>
<form method="post" class="admin-card admin-form"><input type="hidden" name="form" value="settings">
  <div class="admin-card-head"><h2>Academy settings</h2></div>
  <h3>Exams</h3>
  <div class="admin-form-grid">
    <div class="field"><label>Pass mark (%) — learners need this or more</label><input type="number" name="acad_pass_mark" min="1" max="100" value="<?php echo $e($S['acad_pass_mark']); ?>"><small class="admin-help">75 means 75% or higher passes. Set 76 if you want strictly “over 75%”. A course can override this.</small></div>
    <div class="field"><label>Time limit (minutes)</label><input type="number" name="acad_exam_minutes" min="1" value="<?php echo $e($S['acad_exam_minutes']); ?>"></div>
    <div class="field"><label>Questions per exam</label><input type="number" name="acad_exam_questions" min="1" value="<?php echo $e($S['acad_exam_questions']); ?>"><small class="admin-help">Drawn at random from the course’s question bank.</small></div>
    <div class="field"><label>Maximum attempts (0 = unlimited)</label><input type="number" name="acad_max_attempts" min="0" value="<?php echo $e($S['acad_max_attempts']); ?>"></div>
    <div class="field"><label>Wait between attempts (hours)</label><input type="number" name="acad_retake_hours" min="0" value="<?php echo $e($S['acad_retake_hours']); ?>"></div>
  </div>
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin:10px 0"><label><input type="checkbox" name="acad_require_lessons" <?php echo $S['acad_require_lessons'] === '1' ? 'checked' : ''; ?>> Learners must finish all lessons before the exam</label><label><input type="checkbox" name="acad_show_answers" <?php echo $S['acad_show_answers'] === '1' ? 'checked' : ''; ?>> Show correct answers and explanations after the exam</label></div>
  <h3>Learners and trainers</h3>
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin:10px 0"><label><input type="checkbox" name="acad_registration_open" <?php echo $S['acad_registration_open'] === '1' ? 'checked' : ''; ?>> New learner registration is open</label><label><input type="checkbox" name="acad_trainers_open" <?php echo $S['acad_trainers_open'] === '1' ? 'checked' : ''; ?>> Facilitator applications are open</label></div>
  <div class="field"><label>Notification email (new trainer applications, class sign-ups, payments)</label><input type="email" name="acad_notify_email" value="<?php echo $e($S['acad_notify_email']); ?>" placeholder="Leave empty to use the membership notification address"></div>
  <div class="field"><label>Academy introduction (landing page)</label><textarea name="acad_intro" rows="3"><?php echo $e($S['acad_intro']); ?></textarea></div>
  <div class="field"><label>Facilitator invitation text</label><textarea name="acad_trainer_intro" rows="2"><?php echo $e($S['acad_trainer_intro']); ?></textarea></div>
  <div class="field"><label>Payment instructions (shown for paid courses)</label><textarea name="acad_payment_info" rows="3"><?php echo $e($S['acad_payment_info']); ?></textarea></div>
  <h3>Certificate</h3>
  <div class="admin-form-grid">
    <div class="field"><label>Certificate heading</label><input name="acad_cert_title" value="<?php echo $e($S['acad_cert_title']); ?>"></div>
    <div class="field"><label>Issuing body</label><input name="acad_cert_org" value="<?php echo $e($S['acad_cert_org']); ?>"></div>
    <div class="field"><label>Sub-line</label><input name="acad_cert_sub" value="<?php echo $e($S['acad_cert_sub']); ?>"></div>
    <div class="field"><label>First signatory — name</label><input name="acad_sign1_name" value="<?php echo $e($S['acad_sign1_name']); ?>"></div>
    <div class="field"><label>First signatory — title</label><input name="acad_sign1_title" value="<?php echo $e($S['acad_sign1_title']); ?>"></div>
    <div class="field"><label>Second signatory — name</label><input name="acad_sign2_name" value="<?php echo $e($S['acad_sign2_name']); ?>"></div>
    <div class="field"><label>Second signatory — title</label><input name="acad_sign2_title" value="<?php echo $e($S['acad_sign2_title']); ?>"></div>
  </div>
  <div class="field"><label>Accreditation statement (printed at the foot of certificates)</label><input name="acad_accreditation" value="<?php echo $e($S['acad_accreditation']); ?>" placeholder="Leave empty unless an accrediting body has approved the Academy"><small class="admin-help">Only enter an accreditation or approval that has actually been granted — for example the name of the body and the approval reference.</small></div>
  <button class="admin-btn admin-btn-brand">Save settings</button>
</form>

<?php else: ?>
<div class="admin-card" id="course-form">
  <div class="admin-card-head"><h2><?php echo $id ? 'Edit course details' : 'Add a course'; ?></h2><span class="admin-help">After saving you can add lessons and exam questions.</span></div>
  <form method="post" enctype="multipart/form-data" class="admin-form"><input type="hidden" name="form" value="course"><input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Course title</label><input name="title" required value="<?php echo $f('title'); ?>"></div>
      <div class="field"><label>Level</label><select name="level"><?php foreach (acad_levels() as $l): ?><option <?php echo ($cur['level'] ?? 'Beginner') === $l ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Subject</label><input name="category" list="acad-cats" value="<?php echo $f('category'); ?>"><datalist id="acad-cats"><?php foreach (acad_categories() as $c) echo '<option value="' . $e($c) . '">'; ?></datalist></div>
      <div class="field"><label>Duration</label><input name="duration" value="<?php echo $f('duration'); ?>" placeholder="e.g. 3 hours"></div>
      <div class="field"><label>Fee (0 = free)</label><input name="fee" value="<?php echo $f('fee', '0'); ?>"></div>
      <div class="field"><label>Currency</label><select name="currency"><?php foreach (['UGX', 'USD', 'EUR', 'GBP'] as $c): ?><option <?php echo ($cur['currency'] ?? 'UGX') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Pass mark % (0 = use the default <?php echo (int)$S['acad_pass_mark']; ?>)</label><input type="number" name="pass_mark" min="0" max="100" value="<?php echo $f('pass_mark', '0'); ?>"></div>
      <div class="field"><label>Exam minutes (0 = default)</label><input type="number" name="exam_minutes" min="0" value="<?php echo $f('exam_minutes', '0'); ?>"></div>
      <div class="field"><label>Exam questions (0 = default)</label><input type="number" name="exam_questions" min="0" value="<?php echo $f('exam_questions', '0'); ?>"></div>
      <div class="field"><label>Cover image (optional)</label><input type="file" name="cover" accept="image/jpeg,image/png,image/webp"></div>
    </div>
    <div class="field"><label>Short summary</label><input name="summary" maxlength="500" value="<?php echo $f('summary'); ?>"></div>
    <div class="field"><label>Description</label><textarea name="description" rows="4"><?php echo $f('description'); ?></textarea></div>
    <div class="field"><label>What learners will learn — one point per line</label><textarea name="outcomes" rows="5"><?php echo $f('outcomes'); ?></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin:6px 0 14px"><label><input type="checkbox" name="is_active" <?php echo !$cur || !empty($cur['is_active']) ? 'checked' : ''; ?>> Visible on the website</label><label><input type="checkbox" name="enrol_open" <?php echo !$cur || !empty($cur['enrol_open']) ? 'checked' : ''; ?>> Enrolment open</label><label><input type="checkbox" name="is_featured" <?php echo !empty($cur['is_featured']) ? 'checked' : ''; ?>> Featured (shown first)</label></div>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $id ? 'Save and manage lessons' : 'Create course'; ?></button><?php if ($id): ?><a class="admin-btn admin-btn-line" href="academy.php">Cancel</a><?php endif; ?></div>
  </form>
</div>
<div class="admin-card">
  <div class="admin-card-head"><h2>Courses (<?php echo count($courses); ?>)</h2></div>
  <div class="admin-table-wrap"><table class="admin-table" data-export="Academy courses"><thead><tr><th>Course</th><th>Level</th><th>Lessons</th><th>Questions</th><th>Fee</th><th>Enrolled</th><th>Passed</th><th>Visible</th><th>Enrolment</th><th></th></tr></thead><tbody>
  <?php foreach ($courses as $c): ?>
  <tr><td><b><?php echo $e($c['title']); ?></b><br><small><?php echo $e($c['category']); ?></small></td><td><?php echo $e($c['level']); ?></td><td><?php echo (int)$c['lessons']; ?></td><td><?php echo (int)$c['qs']; ?></td><td><?php echo $e(acad_fee_text($c)); ?></td><td><?php echo (int)$c['enrolled']; ?></td><td><?php echo (int)$c['passed']; ?></td>
    <td><a href="academy.php?toggle=<?php echo $c['id']; ?>" class="admin-badge <?php echo $c['is_active'] ? '' : 'gray'; ?>"><?php echo $c['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
    <td><a href="academy.php?toggle_enrol=<?php echo $c['id']; ?>" class="admin-badge <?php echo $c['enrol_open'] ? '' : 'gray'; ?>"><?php echo $c['enrol_open'] ? 'Open' : 'Closed'; ?></a></td>
    <td style="white-space:nowrap"><a class="admin-btn admin-btn-brand admin-btn-sm" href="academy-course.php?id=<?php echo $c['id']; ?>">Lessons &amp; exam</a> <a class="admin-btn admin-btn-line admin-btn-sm" href="academy.php?edit=<?php echo $c['id']; ?>#course-form">Details</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="academy.php?delete=<?php echo $c['id']; ?>" data-confirm="Delete this course with its lessons, questions and all learner progress and results?">Delete</a></td></tr>
  <?php endforeach; ?></tbody></table></div>
</div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
