<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn);
$S = acad_settings($conn); $me = acad_learner($conn);
$slug = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
$st = $conn->prepare("SELECT * FROM academy_courses WHERE slug=? AND is_active=1"); $st->bind_param("s", $slug); $st->execute();
$c = $st->get_result()->fetch_assoc();
if (!$c) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$cid = (int)$c['id'];
$en = $me ? acad_enrolment($conn, $me['id'], $cid) : null;
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['act'] ?? '') === 'enrol') {
    if (!mem_csrf_ok()) $msg = "Your session expired. Please try again.";
    elseif (!$me) { header("Location: academy-login.php?next=" . urlencode("academy-course.php?slug=$slug")); exit; }
    elseif (!$c['enrol_open']) $msg = "Enrolment for this course is closed.";
    elseif (!$en) {
        $status = (int)$c['fee'] > 0 ? 'pending' : 'active';
        $s = $conn->prepare("INSERT IGNORE INTO academy_enrolments (learner_id, course_id, status) VALUES (?,?,?)"); $s->bind_param("iis", $me['id'], $cid, $status); $s->execute();
        $to = acad_notify_to($conn, $S);
        if ($status === 'pending' && $to) mem_send($conn, null, 'academy-pending', $to, "Academy enrolment awaiting payment: " . $c['title'], $me['name'] . " (" . $me['email'] . ") enrolled in " . $c['title'] . " (" . acad_fee_text($c) . "). Confirm payment in admin → NUFA Academy → Learners & results.", mem_site_url() . '/admin/academy-learners.php');
        header("Location: " . ($status === 'active' ? "academy-learn.php?course=$slug" : "academy-portal.php?pending=1")); exit;
    }
    else { header("Location: academy-learn.php?course=$slug"); exit; }
}
$lessons = $conn->query("SELECT id, title, minutes FROM academy_lessons WHERE course_id=$cid ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$qn = (int)$conn->query("SELECT COUNT(*) FROM academy_questions WHERE course_id=$cid")->fetch_row()[0];
$classes = $conn->query("SELECT * FROM academy_classes WHERE course_id=$cid AND is_active=1 AND (COALESCE(end_date,start_date) IS NULL OR COALESCE(end_date,start_date) >= CURDATE()) ORDER BY start_date")->fetch_all(MYSQLI_ASSOC);
$pageTitle = $c['title'] . " — NUFA Academy"; $pageDesc = ui_excerpt($c['summary'] ?: $c['description'], 155);
$activeNav = "academy"; $canonicalPath = "academy-course.php?slug=" . $c['slug'];
$__keep = get_defined_vars();
include __DIR__ . "/includes/header.php";
foreach (["c", "cid", "en", "eid", "S", "me", "lid", "slug", "msg", "lessons", "qn", "classes", "pass", "mins", "cnt", "totalMin", "doneIds", "cur", "want", "idx", "prev", "next", "done", "tot", "pct", "allDone", "maxAtt", "open", "resultId", "res", "used"] as $__k) if (array_key_exists($__k, $__keep)) $$__k = $__keep[$__k];
$pass = acad_pass_mark($c, $S); $mins = acad_exam_minutes($c, $S); $cnt = min($qn, acad_exam_count($c, $S));
$totalMin = array_sum(array_column($lessons, 'minutes'));
echo ui_page_hero($c['category'] . ' · ' . $c['level'], $c['title'], $c['summary'], cms_img_raw('gallery', 'hero_image'), [['NUFA Academy', 'academy.php'], [$c['title']]]);
?>
<section class="section">
  <div class="container ac-detail">
    <div class="ac-main">
      <?php if ($msg): ?><div class="admin-alert err" style="padding:12px 16px;border-radius:12px;background:#fde8e8;margin-bottom:16px"><?php echo ui_e($msg); ?></div><?php endif; ?>
      <?php if (trim((string)$c['description']) !== ''): ?><p class="ac-desc"><?php echo ui_e($c['description']); ?></p><?php endif; ?>
      <?php $out = acad_lines($c['outcomes']); if ($out): ?><h3>What you will learn</h3><ul class="ac-checks"><?php foreach ($out as $o) echo '<li>' . ui_e($o) . '</li>'; ?></ul><?php endif; ?>
      <h3>Course content</h3>
      <ol class="ac-syllabus"><?php foreach ($lessons as $l): ?><li><span><?php echo ui_e($l['title']); ?></span><small><?php echo (int)$l['minutes']; ?> min</small></li><?php endforeach; ?><li class="exam"><span>Final exam — <?php echo $cnt; ?> questions</span><small><?php echo $mins; ?> min · pass mark <?php echo $pass; ?>%</small></li></ol>
      <?php if ($classes): ?><h3>Live classes for this course</h3><?php foreach ($classes as $k): ?><div class="ac-class" style="margin-bottom:12px"><span class="ac-mode ac-mode--<?php echo $k['mode']; ?>"><?php echo ['physical' => 'In person', 'virtual' => 'Online', 'hybrid' => 'In person + online'][$k['mode']] ?? ''; ?></span><h3><?php echo ui_e($k['title']); ?></h3><p><?php echo $k['start_date'] ? ui_e(date('j M Y', strtotime($k['start_date']))) : 'Date to be announced'; ?><?php echo $k['time_text'] !== '' ? ' · ' . ui_e($k['time_text']) : ''; ?></p><a class="btn btn-outline btn-sm" href="academy-portal.php?class=<?php echo (int)$k['id']; ?>#classes">Register for this class</a></div><?php endforeach; ?><?php endif; ?>
    </div>
    <aside class="ac-side">
      <div class="ac-box">
        <div class="ac-price"><?php echo ui_e(acad_fee_text($c)); ?></div>
        <ul class="ac-facts"><li><b>Level</b><?php echo ui_e($c['level']); ?></li><li><b>Lessons</b><?php echo count($lessons); ?><?php echo $totalMin ? ' · about ' . round($totalMin / 60, 1) . ' hours' : ''; ?></li><li><b>Exam</b><?php echo $cnt; ?> questions · <?php echo $mins; ?> minutes</li><li><b>Pass mark</b><?php echo $pass; ?>% or more</li><li><b>Certificate</b>NUFA Academy certificate on passing</li></ul>
        <?php if ($en && $en['status'] === 'completed'): ?><a class="btn btn-gold" href="academy-learn.php?course=<?php echo ui_e($c['slug']); ?>">Review the course</a><a class="btn btn-outline" style="margin-top:8px" href="academy-certificate.php?no=<?php echo ui_e($en['cert_no']); ?>">View certificate</a>
        <?php elseif ($en && $en['status'] === 'active'): ?><a class="btn btn-gold" href="academy-learn.php?course=<?php echo ui_e($c['slug']); ?>">Continue learning</a>
        <?php elseif ($en): ?><p class="muted">Your enrolment is waiting for payment confirmation.</p><a class="btn btn-outline" href="academy-portal.php">Go to my learning</a>
        <?php elseif (!$c['enrol_open']): ?><p class="muted">Enrolment for this course is closed.</p>
        <?php elseif (!$me): ?><a class="btn btn-gold" href="academy-login.php?next=<?php echo urlencode("academy-course.php?slug=" . $c['slug']); ?>">Sign in to enrol</a><?php if ($S['acad_registration_open'] === '1'): ?><a class="btn btn-outline" style="margin-top:8px" href="academy-register.php?next=<?php echo urlencode("academy-course.php?slug=" . $c['slug']); ?>">Create a free account</a><?php endif; ?>
        <?php else: ?><form method="post"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="enrol"><button class="btn btn-gold" style="width:100%;justify-content:center"><?php echo (int)$c['fee'] > 0 ? 'Enrol — ' . ui_e(acad_fee_text($c)) : 'Enrol for free'; ?></button></form><?php if ((int)$c['fee'] > 0): ?><p class="muted" style="font-size:.85rem;margin-top:10px"><?php echo ui_e($S['acad_payment_info']); ?></p><?php endif; ?>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
