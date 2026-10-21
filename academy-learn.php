<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$S = acad_settings($conn);
$me = acad_require($conn); $lid = (int)$me['id'];
$slug = preg_replace('/[^a-z0-9-]/', '', $_GET['course'] ?? '');
$st = $conn->prepare("SELECT * FROM academy_courses WHERE slug=? AND is_active=1"); $st->bind_param("s", $slug); $st->execute(); $c = $st->get_result()->fetch_assoc();
if (!$c) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$cid = (int)$c['id']; $en = acad_enrolment($conn, $lid, $cid);
if (!$en) { header("Location: academy-course.php?slug=$slug"); exit; }
if ($en['status'] === 'pending') { header("Location: academy-portal.php?pending=1"); exit; }
$eid = (int)$en['id'];
$lessons = $conn->query("SELECT * FROM academy_lessons WHERE course_id=$cid ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$doneIds = array_map('intval', array_column($conn->query("SELECT lesson_id FROM academy_progress WHERE enrolment_id=$eid")->fetch_all(MYSQLI_ASSOC), 'lesson_id'));
$cur = null; $want = (int)($_GET['lesson'] ?? 0);
foreach ($lessons as $l) if ((int)$l['id'] === $want) $cur = $l;
if (!$cur) foreach ($lessons as $l) if (!in_array((int)$l['id'], $doneIds, true)) { $cur = $l; break; }
if (!$cur && $lessons) $cur = $lessons[0];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cur && mem_csrf_ok()) {
    $conn->query("INSERT IGNORE INTO academy_progress (enrolment_id, lesson_id) VALUES ($eid, " . (int)$cur['id'] . ")");
    $idx = array_search($cur['id'], array_column($lessons, 'id'));
    $nx = $lessons[$idx + 1] ?? null;
    header("Location: " . ($nx ? "academy-learn.php?course=$slug&lesson=" . (int)$nx['id'] : "academy-learn.php?course=$slug&finished=1")); exit;
}
[$done, $tot, $pct] = acad_progress($conn, $eid, $cid);
$idx = $cur ? array_search($cur['id'], array_column($lessons, 'id')) : 0; $prev = $lessons[$idx - 1] ?? null; $next = $lessons[$idx + 1] ?? null;
$allDone = $tot > 0 && $done >= $tot;
$pageTitle = ($cur ? $cur['title'] . ' — ' : '') . $c['title'] . " — NUFA Academy"; $pageDesc = $c['summary']; $activeNav = "academy"; $canonicalPath = "academy-learn.php?course=$slug"; $noindex = true;
$__keep = get_defined_vars();
include __DIR__ . "/includes/header.php";
foreach (["c", "cid", "en", "eid", "S", "me", "lid", "slug", "msg", "lessons", "qn", "classes", "pass", "mins", "cnt", "totalMin", "doneIds", "cur", "want", "idx", "prev", "next", "done", "tot", "pct", "allDone", "maxAtt", "open", "resultId", "res", "used"] as $__k) if (array_key_exists($__k, $__keep)) $$__k = $__keep[$__k];
echo ui_page_hero('NUFA Academy · ' . $c['level'], $c['title'], '', cms_img_raw('gallery', 'hero_image'), [['My learning', 'academy-portal.php'], [$c['title']]]);
?>
<section class="section"><div class="container ac-learn">
  <aside class="ac-nav">
    <div class="ac-bar"><i style="width:<?php echo $pct; ?>%"></i></div><p class="muted" style="font-size:.85rem"><?php echo $done; ?> of <?php echo $tot; ?> lessons · <?php echo $pct; ?>%</p>
    <ol><?php foreach ($lessons as $l): $d = in_array((int)$l['id'], $doneIds, true); ?><li class="<?php echo ($cur && $cur['id'] == $l['id']) ? 'on' : ''; ?><?php echo $d ? ' done' : ''; ?>"><a href="academy-learn.php?course=<?php echo ui_e($slug); ?>&lesson=<?php echo (int)$l['id']; ?>"><i><?php echo $d ? '✓' : ''; ?></i><?php echo ui_e($l['title']); ?></a></li><?php endforeach; ?>
      <li class="exam <?php echo $allDone ? '' : 'locked'; ?>"><?php if ($allDone || $S['acad_require_lessons'] !== '1'): ?><a href="academy-exam.php?course=<?php echo ui_e($slug); ?>"><i>★</i>Final exam</a><?php else: ?><a><i>★</i>Final exam (finish all lessons)</a><?php endif; ?></li></ol>
  </aside>
  <article class="ac-lesson">
    <?php if (!empty($_GET['finished']) || ($allDone && !$cur)): ?>
      <div class="ac-finish"><h2>You finished all the lessons!</h2><p>Now take the final exam. You need <?php echo acad_pass_mark($c, $S); ?>% or more to pass and earn your certificate.</p><a class="btn btn-gold" href="academy-exam.php?course=<?php echo ui_e($slug); ?>">Go to the exam</a></div>
    <?php elseif ($cur): ?>
      <div class="eyebrow">Lesson <?php echo $idx + 1; ?> of <?php echo $tot; ?> · <?php echo (int)$cur['minutes']; ?> min</div>
      <h2><?php echo ui_e($cur['title']); ?></h2>
      <?php $vid = ui_video_embed($cur['video_url']); if ($vid): ?><div class="video-card" style="margin:16px 0"><div class="frame"><iframe src="<?php echo ui_e($vid); ?>" title="<?php echo ui_e($cur['title']); ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div><?php endif; ?>
      <div class="article-body ac-text"><?php echo $cur['body']; ?></div>
      <?php if ($cur['resource_url'] !== ''): ?><p><a class="btn btn-outline btn-sm" href="<?php echo ui_e($cur['resource_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo ui_e($cur['resource_label'] ?: 'Further reading'); ?> ↗</a></p><?php endif; ?>
      <div class="ac-lnav">
        <?php if ($prev): ?><a class="btn btn-ghost" href="academy-learn.php?course=<?php echo ui_e($slug); ?>&lesson=<?php echo (int)$prev['id']; ?>">← Previous</a><?php else: ?><span></span><?php endif; ?>
        <form method="post"><?php echo mem_csrf_field(); ?><button class="btn btn-gold"><?php echo in_array((int)$cur['id'], $doneIds, true) ? ($next ? 'Next lesson →' : 'Continue →') : ($next ? 'Mark complete & next →' : 'Mark complete'); ?></button></form>
      </div>
    <?php else: ?><p>This course has no lessons yet.</p><?php endif; ?>
    <?php if ($en['status'] === 'completed'): ?><p class="ac-finish" style="margin-top:24px">You completed this course. <a href="academy-certificate.php?no=<?php echo ui_e($en['cert_no']); ?>">View your certificate</a></p><?php endif; ?>
  </article>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
