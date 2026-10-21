<?php
$adminTitle = "Course lessons & exam";
$adminNav = "academy";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/academy.php";
acad_install($conn);
$cid = (int)($_GET['id'] ?? 0);
$c = $conn->query("SELECT * FROM academy_courses WHERE id=$cid")->fetch_assoc();
if (!$c) { header("Location: academy.php"); exit; }
$e = fn($v) => htmlspecialchars((string)$v); $error = ''; $notice = '';

if (isset($_GET['del_lesson'])) { $l = (int)$_GET['del_lesson']; $conn->query("DELETE FROM academy_progress WHERE lesson_id=$l"); $conn->query("DELETE FROM academy_lessons WHERE id=$l AND course_id=$cid"); header("Location: academy-course.php?id=$cid&tab=lessons&deleted=1"); exit; }
if (isset($_GET['del_q'])) { $conn->query("DELETE FROM academy_questions WHERE id=" . (int)$_GET['del_q'] . " AND course_id=$cid"); header("Location: academy-course.php?id=$cid&tab=exam&deleted=1"); exit; }
if (isset($_GET['move'])) { [$lid, $dir] = array_pad(explode(':', $_GET['move']), 2, ''); $lid = (int)$lid; $rows = $conn->query("SELECT id FROM academy_lessons WHERE course_id=$cid ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC); $ids = array_map('intval', array_column($rows, 'id')); $i = array_search($lid, $ids, true);
    if ($i !== false) { $j = $dir === 'up' ? $i - 1 : $i + 1; if (isset($ids[$j])) { [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]]; } foreach ($ids as $n => $x) $conn->query("UPDATE academy_lessons SET sort_order=" . ($n + 1) . " WHERE id=$x"); }
    header("Location: academy-course.php?id=$cid&tab=lessons"); exit; }

$tab = ($_GET['tab'] ?? 'lessons') === 'exam' ? 'exam' : 'lessons';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';
    if ($form === 'lesson') {
        $lid = (int)($_POST['lid'] ?? 0); $t = trim($_POST['title'] ?? ''); $m = max(1, (int)($_POST['minutes'] ?? 10)); $b = $_POST['body'] ?? ''; $v = trim($_POST['video_url'] ?? ''); $ru = trim($_POST['resource_url'] ?? ''); $rl = trim($_POST['resource_label'] ?? '');
        if ($t === '') $error = "Enter the lesson title."; elseif ($v !== '' && !ui_video_embed($v)) $error = "Only YouTube and Vimeo links can be embedded."; elseif ($ru !== '' && !preg_match('#^https?://#i', $ru)) $error = "The further-reading link must start with https://";
        if (!$error) {
            if ($lid) { $s = $conn->prepare("UPDATE academy_lessons SET title=?, minutes=?, body=?, video_url=?, resource_url=?, resource_label=? WHERE id=? AND course_id=?"); $s->bind_param("sissssii", $t, $m, $b, $v, $ru, $rl, $lid, $cid); }
            else { $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM academy_lessons WHERE course_id=$cid")->fetch_row()[0]; $s = $conn->prepare("INSERT INTO academy_lessons (course_id, sort_order, title, minutes, body, video_url, resource_url, resource_label) VALUES (?,?,?,?,?,?,?,?)"); $s->bind_param("iisissss", $cid, $o, $t, $m, $b, $v, $ru, $rl); }
            $s->execute(); header("Location: academy-course.php?id=$cid&tab=lessons&saved=1"); exit;
        }
    } elseif ($form === 'question') {
        $qid = (int)($_POST['qid'] ?? 0); $q = trim($_POST['question'] ?? ''); $o = [trim($_POST['opt_a'] ?? ''), trim($_POST['opt_b'] ?? ''), trim($_POST['opt_c'] ?? ''), trim($_POST['opt_d'] ?? '')]; $ok = max(0, min(3, (int)($_POST['correct'] ?? 0))); $ex = trim($_POST['explanation'] ?? '');
        if ($q === '' || $o[0] === '' || $o[1] === '') $error = "Enter the question and at least two answers."; elseif ($o[$ok] === '') $error = "The correct answer cannot be empty.";
        if (!$error) {
            if ($qid) { $s = $conn->prepare("UPDATE academy_questions SET question=?, opt_a=?, opt_b=?, opt_c=?, opt_d=?, correct=?, explanation=? WHERE id=? AND course_id=?"); $s->bind_param("sssssisii", $q, $o[0], $o[1], $o[2], $o[3], $ok, $ex, $qid, $cid); }
            else { $s = $conn->prepare("INSERT INTO academy_questions (course_id, question, opt_a, opt_b, opt_c, opt_d, correct, explanation) VALUES (?,?,?,?,?,?,?,?)"); $s->bind_param("isssssis", $cid, $q, $o[0], $o[1], $o[2], $o[3], $ok, $ex); }
            $s->execute(); header("Location: academy-course.php?id=$cid&tab=exam&saved=1"); exit;
        }
        $tab = 'exam';
    } elseif ($form === 'bulk') {
        $tab = 'exam'; $n = 0; $skipped = 0;
        $s = $conn->prepare("INSERT INTO academy_questions (course_id, question, opt_a, opt_b, opt_c, opt_d, correct, explanation) VALUES (?,?,?,?,?,?,?,?)");
        foreach (preg_split('/\R/', (string)($_POST['bulk'] ?? '')) as $line) {
            $line = trim($line); if ($line === '') continue; $p = array_map('trim', explode('|', $line));
            if (count($p) < 6 || !preg_match('/^[A-D]$/i', $p[5]) || $p[0] === '' || $p[1] === '' || $p[2] === '') { $skipped++; continue; }
            $ok = ord(strtoupper($p[5])) - 65; $ex = $p[6] ?? ''; $cc = $p[3] ?? ''; $dd = $p[4] ?? '';
            $s->bind_param("isssssis", $cid, $p[0], $p[1], $p[2], $cc, $dd, $ok, $ex); $s->execute(); $n++;
        }
        $notice = "$n question(s) added" . ($skipped ? ", $skipped line(s) skipped (check the format)." : ".");
    }
}
$lessons = $conn->query("SELECT * FROM academy_lessons WHERE course_id=$cid ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$qs = $conn->query("SELECT * FROM academy_questions WHERE course_id=$cid ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$el = !empty($_GET['edit_lesson']) ? $conn->query("SELECT * FROM academy_lessons WHERE id=" . (int)$_GET['edit_lesson'] . " AND course_id=$cid")->fetch_assoc() : null;
$eq = !empty($_GET['edit_q']) ? $conn->query("SELECT * FROM academy_questions WHERE id=" . (int)$_GET['edit_q'] . " AND course_id=$cid")->fetch_assoc() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) { if (($_POST['form'] ?? '') === 'lesson') $el = array_merge($el ?: [], $_POST, ['id' => (int)($_POST['lid'] ?? 0)]); if (($_POST['form'] ?? '') === 'question') $eq = array_merge($eq ?: [], $_POST, ['id' => (int)($_POST['qid'] ?? 0)]); }
$S = acad_settings($conn);
include __DIR__ . "/includes/admin-header.php";
echo acad_admin_tabs('academy.php');
?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($c['title']); ?></h2><span><a class="admin-btn admin-btn-line admin-btn-sm" href="academy.php?edit=<?php echo $cid; ?>#course-form">Course details</a> <a class="admin-btn admin-btn-line admin-btn-sm" href="../academy-course.php?slug=<?php echo $e($c['slug']); ?>" target="_blank">View ↗</a></span></div>
  <div class="mem-tabs" style="margin:0"><a href="academy-course.php?id=<?php echo $cid; ?>&tab=lessons" class="<?php echo $tab === 'lessons' ? 'active' : ''; ?>">Lessons (<?php echo count($lessons); ?>)</a><a href="academy-course.php?id=<?php echo $cid; ?>&tab=exam" class="<?php echo $tab === 'exam' ? 'active' : ''; ?>">Exam questions (<?php echo count($qs); ?>)</a></div></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Removed.</div><?php endif; ?>
<?php if ($notice): ?><div class="admin-alert ok"><?php echo $e($notice); ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo $e($error); ?></div><?php endif; ?>

<?php if ($tab === 'lessons'): ?>
<div class="admin-card" id="lesson-form"><div class="admin-card-head"><h2><?php echo $el ? 'Edit lesson' : 'Add a lesson'; ?></h2></div>
  <form method="post" class="admin-form"><input type="hidden" name="form" value="lesson"><input type="hidden" name="lid" value="<?php echo (int)($el['id'] ?? 0); ?>">
    <div class="admin-form-grid"><div class="field"><label>Lesson title</label><input name="title" required value="<?php echo $e($el['title'] ?? ''); ?>"></div><div class="field"><label>Minutes</label><input type="number" name="minutes" min="1" value="<?php echo (int)($el['minutes'] ?? 10); ?>"></div>
      <div class="field"><label>Video link (YouTube or Vimeo, optional)</label><input type="url" name="video_url" value="<?php echo $e($el['video_url'] ?? ''); ?>"></div><div class="field"><label>Further-reading link (optional)</label><input type="url" name="resource_url" value="<?php echo $e($el['resource_url'] ?? ''); ?>"></div>
      <div class="field"><label>Further-reading button label</label><input name="resource_label" value="<?php echo $e($el['resource_label'] ?? ''); ?>" placeholder="e.g. Read the free guide"></div></div>
    <div class="field"><label>Lesson content</label><textarea name="body" data-rich data-upload="upload-image.php"><?php echo $e($el['body'] ?? ''); ?></textarea></div>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $el ? 'Save lesson' : 'Add lesson'; ?></button><?php if ($el): ?><a class="admin-btn admin-btn-line" href="academy-course.php?id=<?php echo $cid; ?>&tab=lessons">Cancel</a><?php endif; ?></div></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Lessons</h2></div>
  <?php if (!$lessons): ?><p class="admin-help">No lessons yet.</p><?php else: ?><table class="admin-table" data-noexport><thead><tr><th>#</th><th>Title</th><th>Minutes</th><th></th></tr></thead><tbody>
  <?php foreach ($lessons as $i => $l): ?><tr><td><?php echo $i + 1; ?></td><td><b><?php echo $e($l['title']); ?></b></td><td><?php echo (int)$l['minutes']; ?></td><td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-course.php?id=<?php echo $cid; ?>&move=<?php echo $l['id']; ?>:up" title="Move up">↑</a> <a class="admin-btn admin-btn-line admin-btn-sm" href="academy-course.php?id=<?php echo $cid; ?>&move=<?php echo $l['id']; ?>:down" title="Move down">↓</a> <a class="admin-btn admin-btn-line admin-btn-sm" href="academy-course.php?id=<?php echo $cid; ?>&tab=lessons&edit_lesson=<?php echo $l['id']; ?>#lesson-form">Edit</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="academy-course.php?id=<?php echo $cid; ?>&del_lesson=<?php echo $l['id']; ?>" data-confirm="Delete this lesson?">Delete</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>

<?php else: ?>
<div class="admin-card" id="q-form"><div class="admin-card-head"><h2><?php echo $eq ? 'Edit question' : 'Add an exam question'; ?></h2><span class="admin-help">Each exam draws <?php echo acad_exam_count($c, $S); ?> random questions from this bank — add more than that for variety. Pass mark: <?php echo acad_pass_mark($c, $S); ?>%.</span></div>
  <form method="post" class="admin-form"><input type="hidden" name="form" value="question"><input type="hidden" name="qid" value="<?php echo (int)($eq['id'] ?? 0); ?>">
    <div class="field"><label>Question</label><textarea name="question" rows="2" required><?php echo $e($eq['question'] ?? ''); ?></textarea></div>
    <div class="admin-form-grid"><?php foreach (['a' => 'A', 'b' => 'B', 'c' => 'C (optional)', 'd' => 'D (optional)'] as $k => $l): ?><div class="field"><label>Answer <?php echo $l; ?></label><input name="opt_<?php echo $k; ?>" value="<?php echo $e($eq['opt_' . $k] ?? ''); ?>"></div><?php endforeach; ?>
      <div class="field"><label>Correct answer</label><select name="correct"><?php foreach (['A', 'B', 'C', 'D'] as $i => $l): ?><option value="<?php echo $i; ?>" <?php echo (int)($eq['correct'] ?? 0) === $i ? 'selected' : ''; ?>><?php echo $l; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Explanation (shown after the exam)</label><input name="explanation" value="<?php echo $e($eq['explanation'] ?? ''); ?>"></div></div>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $eq ? 'Save question' : 'Add question'; ?></button><?php if ($eq): ?><a class="admin-btn admin-btn-line" href="academy-course.php?id=<?php echo $cid; ?>&tab=exam">Cancel</a><?php endif; ?></div></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Add many questions at once</h2><span class="admin-help">One question per line: <code>Question | A | B | C | D | correct letter | explanation</code></span></div>
  <form method="post" class="admin-form"><input type="hidden" name="form" value="bulk"><div class="field"><textarea name="bulk" rows="5" placeholder="What does ISO control? | Sensor sensitivity | Lens size | Frame rate | Colour | A | ISO sets the sensor’s sensitivity to light."></textarea></div><button class="admin-btn admin-btn-line">Import questions</button></form></div>
<div class="admin-card"><div class="admin-card-head"><h2>Question bank (<?php echo count($qs); ?>)</h2></div>
  <?php if (!$qs): ?><p class="admin-help">No questions yet — learners cannot take the exam until you add some.</p><?php else: ?><table class="admin-table" data-noexport><thead><tr><th>#</th><th>Question</th><th>Correct</th><th></th></tr></thead><tbody>
  <?php foreach ($qs as $i => $q): $opts = [$q['opt_a'], $q['opt_b'], $q['opt_c'], $q['opt_d']]; ?><tr><td><?php echo $i + 1; ?></td><td><?php echo $e($q['question']); ?></td><td><b><?php echo ['A', 'B', 'C', 'D'][$q['correct']]; ?></b> · <?php echo $e($opts[$q['correct']] ?? ''); ?></td><td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-course.php?id=<?php echo $cid; ?>&tab=exam&edit_q=<?php echo $q['id']; ?>#q-form">Edit</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="academy-course.php?id=<?php echo $cid; ?>&del_q=<?php echo $q['id']; ?>" data-confirm="Delete this question?">Delete</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
