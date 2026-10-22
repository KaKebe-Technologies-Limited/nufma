<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$S = acad_settings($conn);
$me = acad_require($conn); $lid = (int)$me['id'];
$slug = preg_replace('/[^a-z0-9-]/', '', $_GET['course'] ?? $_POST['course'] ?? '');
$st = $conn->prepare("SELECT * FROM academy_courses WHERE slug=? AND is_active=1"); $st->bind_param("s", $slug); $st->execute(); $c = $st->get_result()->fetch_assoc();
if (!$c) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$cid = (int)$c['id']; $en = acad_enrolment($conn, $lid, $cid);
if (!$en || $en['status'] === 'pending') { header("Location: academy-course.php?slug=$slug"); exit; }
$eid = (int)$en['id'];
[$done, $tot, $pct] = acad_progress($conn, $eid, $cid);
$pass = acad_pass_mark($c, $S); $mins = acad_exam_minutes($c, $S);
$maxAtt = (int)$S['acad_max_attempts'] > 0 ? (int)$S['acad_max_attempts'] + (int)$en['extra_attempts'] : 0;
$msg = '';

function acad_grade($conn, $att, $post, $pass, $course, $enrol, $learner, $S, $expired = false) {
    $qids = json_decode($att['qids'], true) ?: []; $ans = []; $correct = 0;
    $qs = []; if ($qids) foreach ($conn->query("SELECT * FROM academy_questions WHERE id IN (" . implode(',', array_map('intval', $qids)) . ")")->fetch_all(MYSQLI_ASSOC) as $q) $qs[$q['id']] = $q;
    foreach ($qids as $qid) { $a = isset($post['q'][$qid]) ? (int)$post['q'][$qid] : -1; $ans[$qid] = $a; if (isset($qs[$qid]) && $a === (int)$qs[$qid]['correct']) $correct++; }
    $total = count($qids); $score = ($total && !$expired) ? (int)floor($correct * 100 / $total) : 0; $ok = !$expired && $score >= $pass ? 1 : 0;
    $status = $expired ? 'expired' : 'done'; $aj = json_encode($ans);
    $s = $conn->prepare("UPDATE academy_attempts SET submitted_at=NOW(), total=?, correct=?, score=?, passed=?, status=?, answers=? WHERE id=?");
    $s->bind_param("iiiissi", $total, $correct, $score, $ok, $status, $aj, $att['id']); $s->execute();
    $eid = (int)$enrol['id'];
    if ($score > (int)$enrol['best_score']) $conn->query("UPDATE academy_enrolments SET best_score=$score WHERE id=$eid");
    if ($ok && $enrol['status'] !== 'completed') {
        $no = acad_cert_no($eid);
        $conn->query("UPDATE academy_enrolments SET status='completed', completed_at=NOW(), cert_no='" . $conn->real_escape_string($no) . "' WHERE id=$eid");
        mem_send($conn, null, 'academy-certificate', $learner['email'], "Congratulations — you passed " . $course['title'], "Hello " . $learner['name'] . ",\n\nYou passed the " . $course['title'] . " exam with " . $score . "%. Your NUFA Academy certificate (" . $no . ") is ready to view and print.", mem_site_url() . '/academy-certificate.php?no=' . $no);
    }
    return (int)$att['id'];
}

// finish an expired open attempt
$open = $conn->query("SELECT * FROM academy_attempts WHERE enrolment_id=$eid AND status='open' ORDER BY id DESC LIMIT 1")->fetch_assoc();
if ($open && time() > strtotime($open['started_at']) + $open['minutes'] * 60 + 90) { acad_grade($conn, $open, [], $pass, $c, $en, $me, $S, true); $open = null; $msg = "Your previous exam attempt ran out of time and was marked as unsuccessful."; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && mem_csrf_ok()) {
    $act = $_POST['act'] ?? '';
    if ($act === 'start' && !$open) {
        $used = acad_attempts_used($conn, $eid); $last = $conn->query("SELECT submitted_at FROM academy_attempts WHERE enrolment_id=$eid AND status<>'open' ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $wait = (int)$S['acad_retake_hours'] > 0 && $last && $last['submitted_at'] && strtotime($last['submitted_at']) + (int)$S['acad_retake_hours'] * 3600 > time();
        if ($S['acad_require_lessons'] === '1' && $done < $tot) $msg = "Finish all the lessons before the exam.";
        elseif ($maxAtt && $used >= $maxAtt) $msg = "You have used all your exam attempts. Please contact the Academy team.";
        elseif ($wait) $msg = "Please wait " . (int)$S['acad_retake_hours'] . " hour(s) between attempts.";
        else {
            $ids = array_map('intval', array_column($conn->query("SELECT id FROM academy_questions WHERE course_id=$cid")->fetch_all(MYSQLI_ASSOC), 'id')); shuffle($ids);
            $ids = array_slice($ids, 0, acad_exam_count($c, $S));
            if (!$ids) $msg = "This exam has no questions yet.";
            else { $j = json_encode($ids); $now = date('Y-m-d H:i:s'); $s = $conn->prepare("INSERT INTO academy_attempts (enrolment_id, started_at, total, qids, minutes) VALUES (?,?,?,?,?)"); $n = count($ids); $s->bind_param("isisi", $eid, $now, $n, $j, $mins); $s->execute(); header("Location: academy-exam.php?course=$slug"); exit; }
        }
    } elseif ($act === 'submit' && $open) {
        $late = time() > strtotime($open['started_at']) + $open['minutes'] * 60 + 90;
        $aid = acad_grade($conn, $open, $_POST, $pass, $c, $en, $me, $S, $late);
        header("Location: academy-exam.php?course=$slug&result=$aid"); exit;
    }
    $open = $conn->query("SELECT * FROM academy_attempts WHERE enrolment_id=$eid AND status='open' ORDER BY id DESC LIMIT 1")->fetch_assoc();
}
$en = acad_enrolment($conn, $lid, $cid);
$resultId = (int)($_GET['result'] ?? 0);
$res = $resultId ? $conn->query("SELECT * FROM academy_attempts WHERE id=$resultId AND enrolment_id=$eid AND status<>'open'")->fetch_assoc() : null;
$used = acad_attempts_used($conn, $eid);
$pageTitle = "Exam — " . $c['title'] . " — NUFA Academy"; $pageDesc = $c['summary']; $activeNav = "academy"; $canonicalPath = "academy-exam.php?course=$slug"; $noindex = true;
$__keep = get_defined_vars();
include __DIR__ . "/includes/header.php";
foreach (["c", "cid", "en", "eid", "S", "me", "lid", "slug", "msg", "lessons", "qn", "classes", "pass", "mins", "cnt", "totalMin", "doneIds", "cur", "want", "idx", "prev", "next", "done", "tot", "pct", "allDone", "maxAtt", "open", "resultId", "res", "used"] as $__k) if (array_key_exists($__k, $__keep)) $$__k = $__keep[$__k];
echo ui_page_hero('NUFA Academy · Final exam', $c['title'], '', cms_img_raw('gallery', 'hero_image'), [['My learning', 'academy-portal.php'], [$c['title'], 'academy-learn.php?course=' . $slug], ['Exam']]);
?>
<section class="section"><div class="container" style="max-width:820px">
<?php if ($msg): ?><div style="padding:12px 16px;border-radius:12px;background:#FFF4EA;margin-bottom:14px"><?php echo ui_e($msg); ?></div><?php endif; ?>

<?php if ($res): $qids = json_decode($res['qids'], true) ?: []; $ans = json_decode($res['answers'], true) ?: []; ?>
  <div class="ac-result <?php echo $res['passed'] ? 'is-pass' : 'is-fail'; ?>">
    <div class="ac-score"><?php echo (int)$res['score']; ?>%</div>
    <h2><?php echo $res['status'] === 'expired' ? 'Time ran out' : ($res['passed'] ? 'You passed!' : 'Not passed this time'); ?></h2>
    <p><?php echo (int)$res['correct']; ?> of <?php echo (int)$res['total']; ?> correct · pass mark <?php echo $pass; ?>%</p>
    <?php if ($res['passed'] && $en['cert_no']): ?><a class="btn btn-gold" href="academy-certificate.php?no=<?php echo ui_e($en['cert_no']); ?>">View and print your certificate</a>
    <?php elseif (!$res['passed']): ?><p>Review the lessons and try again<?php echo $maxAtt ? ' — you have used ' . $used . ' of ' . $maxAtt . ' attempts' : ''; ?>.</p><a class="btn btn-outline" href="academy-learn.php?course=<?php echo ui_e($slug); ?>">Back to the lessons</a><?php endif; ?>
  </div>
  <?php if ($S['acad_show_answers'] === '1' && $res['status'] !== 'expired' && $qids):
    $qs = []; foreach ($conn->query("SELECT * FROM academy_questions WHERE id IN (" . implode(',', array_map('intval', $qids)) . ")")->fetch_all(MYSQLI_ASSOC) as $q) $qs[$q['id']] = $q; ?>
    <h3 style="margin:30px 0 12px">Review</h3>
    <?php foreach ($qids as $i => $qid): $q = $qs[$qid] ?? null; if (!$q) continue; $a = $ans[$qid] ?? -1; $ok = $a === (int)$q['correct']; $opts = [$q['opt_a'], $q['opt_b'], $q['opt_c'], $q['opt_d']]; ?>
    <div class="ac-q <?php echo $ok ? 'ok' : 'bad'; ?>"><p><b><?php echo $i + 1; ?>. <?php echo ui_e($q['question']); ?></b></p>
      <p style="margin:4px 0">Your answer: <?php echo $a >= 0 && ($opts[$a] ?? '') !== '' ? ui_e($opts[$a]) : '<em>no answer</em>'; ?> <?php echo $ok ? '✓' : '✗'; ?></p>
      <?php if (!$ok): ?><p style="margin:4px 0">Correct answer: <b><?php echo ui_e($opts[$q['correct']] ?? ''); ?></b></p><?php endif; ?>
      <?php if ($q['explanation'] !== ''): ?><p class="muted" style="margin:4px 0"><?php echo ui_e($q['explanation']); ?></p><?php endif; ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

<?php elseif ($open): $qids = json_decode($open['qids'], true) ?: []; $qs = []; if ($qids) foreach ($conn->query("SELECT * FROM academy_questions WHERE id IN (" . implode(',', array_map('intval', $qids)) . ")")->fetch_all(MYSQLI_ASSOC) as $q) $qs[$q['id']] = $q;
    $endTs = strtotime($open['started_at']) + $open['minutes'] * 60; ?>
  <div class="ac-timer" id="ac-timer" data-end="<?php echo date('c', $endTs); ?>"><span>Time left</span><b>--:--</b></div>
  <form method="post" id="examform"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="submit"><input type="hidden" name="course" value="<?php echo ui_e($slug); ?>">
    <?php foreach ($qids as $i => $qid): $q = $qs[$qid] ?? null; if (!$q) continue; ?>
    <fieldset class="ac-q"><legend><?php echo $i + 1; ?>. <?php echo ui_e($q['question']); ?></legend>
      <?php foreach ([$q['opt_a'], $q['opt_b'], $q['opt_c'], $q['opt_d']] as $k => $t) if ($t !== ''): ?><label class="ac-opt"><input type="radio" name="q[<?php echo (int)$qid; ?>]" value="<?php echo $k; ?>"> <span><?php echo ui_e($t); ?></span></label><?php endif; ?>
    </fieldset>
    <?php endforeach; ?>
    <button class="btn btn-gold" data-confirm-submit>Submit my exam</button>
  </form>
  <script>
  (function(){var t=document.getElementById('ac-timer'),end=Date.parse(t.dataset.end),f=document.getElementById('examform'),b=t.querySelector('b');function p(n){return n<10?'0'+n:n}
  function tick(){var d=Math.floor((end-Date.now())/1000);if(d<=0){b.textContent='00:00';f.submit();return;}b.textContent=p(Math.floor(d/60))+':'+p(d%60);if(d<60)t.classList.add('low');}
  tick();setInterval(tick,1000);
  f.addEventListener('submit',function(e){var un=f.querySelectorAll('fieldset').length-f.querySelectorAll('input:checked').length;if(un>0&&Date.parse(t.dataset.end)>Date.now()&&!confirm('You have '+un+' unanswered question(s). Submit anyway?'))e.preventDefault();});
  })();
  </script>

<?php elseif ($en['status'] === 'completed'): ?>
  <div class="ac-result is-pass"><h2>You have already passed this course</h2><p>Your best score was <?php echo (int)$en['best_score']; ?>%.</p><a class="btn btn-gold" href="academy-certificate.php?no=<?php echo ui_e($en['cert_no']); ?>">View your certificate</a></div>

<?php else: $cnt = min((int)$conn->query("SELECT COUNT(*) FROM academy_questions WHERE course_id=$cid")->fetch_row()[0], acad_exam_count($c, $S)); ?>
  <div class="ac-box" style="max-width:none">
    <h2 style="margin-top:0">Ready for the exam?</h2>
    <ul class="ac-facts"><li><b>Questions</b><?php echo $cnt; ?> multiple choice</li><li><b>Time limit</b><?php echo $mins; ?> minutes — the timer starts when you press Start</li><li><b>Pass mark</b><?php echo $pass; ?>% or more</li><li><b>Attempts</b><?php echo $maxAtt ? $used . ' of ' . $maxAtt . ' used' : 'Unlimited'; ?></li></ul>
    <?php if ($S['acad_require_lessons'] === '1' && $done < $tot): ?><p>Finish all lessons first (<?php echo $done; ?> of <?php echo $tot; ?> done).</p><a class="btn btn-gold" href="academy-learn.php?course=<?php echo ui_e($slug); ?>">Back to the lessons</a>
    <?php elseif ($maxAtt && $used >= $maxAtt): ?><p>You have used all your attempts. Please contact the Academy team for another chance.</p>
    <?php else: ?><form method="post"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="start"><input type="hidden" name="course" value="<?php echo ui_e($slug); ?>"><button class="btn btn-gold">Start the exam</button></form><?php endif; ?>
  </div>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
