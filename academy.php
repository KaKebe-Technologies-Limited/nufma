<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn);
$S = acad_settings($conn); $me = acad_learner($conn);
$pageTitle = "NUFA Academy — Free Film Courses, Exams & Certificates";
$pageDesc  = "Learn filmmaking online with NUFA Academy: courses in directing, acting, cinematography, sound, editing, producing and more. Take exams and earn a certificate.";
$activeNav = "academy"; $canonicalPath = "academy.php";
include __DIR__ . "/includes/header.php";

$courses = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM academy_lessons l WHERE l.course_id=c.id) lessons FROM academy_courses c WHERE c.is_active=1 ORDER BY c.is_featured DESC, c.sort_order, c.id")->fetch_all(MYSQLI_ASSOC);
$learners = (int)$conn->query("SELECT COUNT(*) FROM academy_learners")->fetch_row()[0];
$certs = (int)$conn->query("SELECT COUNT(*) FROM academy_enrolments WHERE cert_no IS NOT NULL")->fetch_row()[0];
$classes = $conn->query("SELECT * FROM academy_classes WHERE is_active=1 AND (end_date IS NULL OR COALESCE(end_date, start_date) >= CURDATE()) ORDER BY start_date IS NULL, start_date LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$trainers = $conn->query("SELECT name, expertise, location, mode, bio FROM academy_trainers WHERE status='approved' AND is_public=1 ORDER BY name LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$cats = []; foreach ($courses as $c) if ($c['category'] !== '') $cats[$c['category']] = ($cats[$c['category']] ?? 0) + 1;

echo ui_page_hero('Resources', 'NUFA Academy', $S['acad_intro'], 'gallery/2026/nufa26-20.jpg', [['Resources', 'resources.php'], ['NUFA Academy']],
    ($me ? '<a href="academy-portal.php" class="btn btn-gold">My learning</a>' : ($S['acad_registration_open'] === '1' ? '<a href="academy-register.php" class="btn btn-gold">Create a learner account</a><a href="academy-login.php" class="btn btn-outline">Sign in</a>' : '<a href="academy-login.php" class="btn btn-gold">Sign in</a>')) . '<a href="#courses" class="btn btn-ghost">Browse courses</a>');
?>
<section class="section" id="how">
  <div class="container">
    <div class="ac-steps" data-reveal>
      <div><b>1</b><h3>Create an account</h3><p>Sign up with your email and a password to open your learner portal.</p></div>
      <div><b>2</b><h3>Enrol and learn</h3><p>Pick a course and work through the lessons at your own pace.</p></div>
      <div><b>3</b><h3>Take the exam</h3><p>Pass with <?php echo (int)$S['acad_pass_mark']; ?>% or more to complete the course.</p></div>
      <div><b>4</b><h3>Get your certificate</h3><p>Download a NUFA Academy certificate that anyone can verify online.</p></div>
    </div>
    <div class="ac-stats"><span><b><?php echo count($courses); ?></b> courses</span><span><b><?php echo (int)$conn->query("SELECT COUNT(*) FROM academy_lessons")->fetch_row()[0]; ?></b> lessons</span><?php if ($learners): ?><span><b><?php echo $learners; ?></b> learners</span><?php endif; ?><?php if ($certs): ?><span><b><?php echo $certs; ?></b> certificates issued</span><?php endif; ?></div>
  </div>
</section>

<section class="section section--soft" id="courses">
  <div class="container">
    <div class="section-head center"><div class="eyebrow">Courses</div><h2>Learn film, step by step</h2><p>From in-camera basics to distribution — choose your level and your craft.</p></div>
    <div class="op-tools">
      <div class="filter-bar" role="tablist" aria-label="Filter by level">
        <button class="op-tab is-active" type="button" data-lvl="all">All levels</button>
        <?php foreach (acad_levels() as $l): ?><button class="op-tab" type="button" data-lvl="<?php echo $l; ?>"><?php echo $l; ?></button><?php endforeach; ?>
      </div>
      <div class="op-search"><select id="ac-cat" aria-label="Filter by subject"><option value="">All subjects</option><?php foreach ($cats as $k => $n) echo '<option>' . ui_e($k) . '</option>'; ?></select><input type="search" id="ac-q" placeholder="Search courses" aria-label="Search courses"></div>
    </div>
    <?php if (!$courses): ?><div class="empty-state"><h3>Courses coming soon</h3></div><?php else: ?>
    <div class="ac-grid" id="ac-grid">
      <?php foreach ($courses as $c): ?>
      <a class="ac-card" href="academy-course.php?slug=<?php echo ui_e($c['slug']); ?>" data-lvl="<?php echo ui_e($c['level']); ?>" data-cat="<?php echo ui_e($c['category']); ?>" data-hay="<?php echo ui_e(strtolower($c['title'] . ' ' . $c['summary'] . ' ' . $c['category'])); ?>">
        <div class="ac-cover<?php echo acad_cover_ok($c) ? '' : ' ac-cover--' . (abs(crc32($c['category'])) % 6); ?>"><?php if (acad_cover_ok($c)): ?><img src="assets/images/<?php echo ui_e($c['cover']); ?>" alt="" loading="lazy"><?php else: ?><span><?php echo ui_e($c['category']); ?></span><?php endif; ?></div>
        <div class="ac-body"><div class="ac-meta"><span class="ac-lvl ac-lvl--<?php echo strtolower($c['level']); ?>"><?php echo ui_e($c['level']); ?></span><span><?php echo (int)$c['lessons']; ?> lessons<?php echo $c['duration'] !== '' ? ' · ' . ui_e($c['duration']) : ''; ?></span></div>
          <h3><?php echo ui_e($c['title']); ?></h3><p><?php echo ui_e($c['summary']); ?></p>
          <div class="ac-foot"><b><?php echo ui_e(acad_fee_text($c)); ?></b><span class="link-arrow">View course</span></div></div>
      </a>
      <?php endforeach; ?>
    </div>
    <p class="empty-state" id="ac-none" hidden>No courses match. Try another filter.</p>
    <?php endif; ?>
  </div>
</section>

<?php if ($classes): ?>
<section class="section" id="classes">
  <div class="container">
    <div class="section-head center"><div class="eyebrow">Live classes</div><h2>Upcoming classes — in person and online</h2></div>
    <div class="ac-classes">
      <?php foreach ($classes as $k): ?>
      <div class="ac-class"><span class="ac-mode ac-mode--<?php echo $k['mode']; ?>"><?php echo ['physical' => 'In person', 'virtual' => 'Online', 'hybrid' => 'In person + online'][$k['mode']] ?? ''; ?></span><h3><?php echo ui_e($k['title']); ?></h3>
        <p><?php echo $k['start_date'] ? ui_e(date('j M Y', strtotime($k['start_date']))) . ($k['end_date'] && $k['end_date'] !== $k['start_date'] ? ' – ' . ui_e(date('j M Y', strtotime($k['end_date']))) : '') : 'Date to be announced'; ?><?php echo $k['time_text'] !== '' ? ' · ' . ui_e($k['time_text']) : ''; ?></p>
        <?php if ($k['venue'] !== '' && $k['mode'] !== 'virtual'): ?><p class="muted"><?php echo ui_e($k['venue']); ?></p><?php endif; ?>
        <?php if ($k['facilitator'] !== ''): ?><p class="muted">Facilitator: <?php echo ui_e($k['facilitator']); ?></p><?php endif; ?>
        <a class="btn btn-outline btn-sm" href="academy-portal.php?class=<?php echo (int)$k['id']; ?>#classes">Register</a></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--black" id="library-cta"><div class="container">
  <div class="ac-split">
    <div><div class="eyebrow">Study library</div><h2>Hundreds of free learning resources</h2><p>Free courses, video channels, script libraries, software, templates and sound libraries from around the world, organised by subject and level.</p><a class="btn btn-gold" href="academy-library.php">Open the study library</a></div>
    <div><div class="eyebrow">Teach with us</div><h2>Become a facilitator</h2><p><?php echo ui_e($S['acad_trainer_intro']); ?></p><?php if ($S['acad_trainers_open'] === '1'): ?><a class="btn btn-outline" href="academy-teach.php">Apply to facilitate</a><?php endif; ?></div>
  </div>
</div></section>

<?php if ($trainers): ?>
<section class="section"><div class="container"><div class="section-head center"><div class="eyebrow">Our facilitators</div><h2>Learn from experienced practitioners</h2></div>
  <div class="gs-grid"><?php foreach ($trainers as $t): ?><div class="gs-card"><div class="gs-body"><span class="gs-cat"><?php echo ui_e(['physical' => 'In person', 'virtual' => 'Online', 'both' => 'In person & online'][$t['mode']] ?? ''); ?></span><h3><?php echo ui_e($t['name']); ?></h3><p class="gs-title"><?php echo ui_e($t['expertise']); ?><?php echo $t['location'] !== '' ? ' · ' . ui_e($t['location']) : ''; ?></p><?php if ($t['bio'] !== ''): ?><p class="gs-title"><?php echo ui_e(ui_excerpt($t['bio'], 140)); ?></p><?php endif; ?></div></div><?php endforeach; ?></div></div></section>
<?php endif; ?>

<script>
(function(){var lv='all',cards=[].slice.call(document.querySelectorAll('.ac-card')),q=document.getElementById('ac-q'),cat=document.getElementById('ac-cat'),none=document.getElementById('ac-none');
function run(){var t=(q.value||'').toLowerCase().trim(),n=0;cards.forEach(function(c){var ok=(lv==='all'||c.dataset.lvl===lv)&&(!cat.value||c.dataset.cat===cat.value)&&(!t||c.dataset.hay.indexOf(t)>-1);c.hidden=!ok;if(ok)n++;});if(none)none.hidden=n>0;}
document.querySelectorAll('[data-lvl].op-tab').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('[data-lvl].op-tab').forEach(function(x){x.classList.remove('is-active')});b.classList.add('is-active');lv=b.dataset.lvl;run();});});
q&&q.addEventListener('input',run);cat&&cat.addEventListener('change',run);})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
