<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$S = acad_settings($conn);
$me = acad_require($conn); $lid = (int)$me['id'];
$flash = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if (!mem_csrf_ok()) $err = "Your session expired. Please try again.";
    elseif ($act === 'class' || $act === 'unclass') {
        $kid = (int)($_POST['class_id'] ?? 0);
        $k = $conn->query("SELECT * FROM academy_classes WHERE id=$kid AND is_active=1")->fetch_assoc();
        if (!$k) $err = "That class is not available.";
        elseif ($act === 'unclass') { $conn->query("DELETE FROM academy_class_signups WHERE class_id=$kid AND learner_id=$lid"); $flash = "Your registration was cancelled."; }
        elseif (!$k['reg_open']) $err = "Registration for this class is closed.";
        else {
            $taken = (int)$conn->query("SELECT COUNT(*) FROM academy_class_signups WHERE class_id=$kid")->fetch_row()[0];
            if ($k['seats'] > 0 && $taken >= $k['seats']) $err = "Sorry, this class is full.";
            else { $conn->query("INSERT IGNORE INTO academy_class_signups (class_id, learner_id) VALUES ($kid, $lid)"); $flash = "You are registered for “" . $k['title'] . "”.";
                $to = acad_notify_to($conn, $S); if ($to) mem_send($conn, null, 'academy-class', $to, "Academy class registration: " . $k['title'], $me['name'] . " (" . $me['email'] . ") registered for " . $k['title'] . ".", mem_site_url() . '/admin/academy-classes.php'); }
        }
    } elseif ($act === 'profile') {
        $n = form_clean($_POST['name'] ?? '', 150); $p = form_clean($_POST['phone'] ?? '', 40); $c = form_clean($_POST['country'] ?? '', 80);
        if (mb_strlen($n) < 2) $err = "Enter your name."; else { $s = $conn->prepare("UPDATE academy_learners SET name=?, phone=?, country=? WHERE id=?"); $s->bind_param("sssi", $n, $p, $c, $lid); $s->execute(); $flash = "Profile saved."; $me['name'] = $n; $me['phone'] = $p; $me['country'] = $c; }
    } elseif ($act === 'password') {
        $old = (string)($_POST['old'] ?? ''); $new = (string)($_POST['new'] ?? '');
        if (!password_verify($old, $me['password_hash'])) $err = "Your current password is not correct.";
        elseif (strlen($new) < 8) $err = "The new password must be at least 8 characters.";
        elseif ($new !== (string)($_POST['new2'] ?? '')) $err = "The new passwords do not match.";
        else { $h = password_hash($new, PASSWORD_DEFAULT); $s = $conn->prepare("UPDATE academy_learners SET password_hash=? WHERE id=?"); $s->bind_param("si", $h, $lid); $s->execute(); $flash = "Password changed."; }
    }
}
$enrols = $conn->query("SELECT * FROM academy_enrolments WHERE learner_id=$lid ORDER BY enrolled_at DESC")->fetch_all(MYSQLI_ASSOC);
$cids = array_map('intval', array_column($enrols, 'course_id')); $cmap = [];
if ($cids) foreach ($conn->query("SELECT * FROM academy_courses WHERE id IN (" . implode(',', $cids) . ")")->fetch_all(MYSQLI_ASSOC) as $c) $cmap[$c['id']] = $c;
$signed = array_map('intval', array_column($conn->query("SELECT class_id FROM academy_class_signups WHERE learner_id=$lid")->fetch_all(MYSQLI_ASSOC), 'class_id'));
$classes = $conn->query("SELECT * FROM academy_classes WHERE is_active=1 AND (COALESCE(end_date,start_date) IS NULL OR COALESCE(end_date,start_date) >= CURDATE()) ORDER BY start_date IS NULL, start_date")->fetch_all(MYSQLI_ASSOC);
$want = (int)($_GET['class'] ?? 0);
$pageTitle = "My learning — NUFA Academy"; $pageDesc = "Your NUFA Academy learner portal."; $activeNav = "academy"; $canonicalPath = "academy-portal.php"; $noindex = true;
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Learner portal', 'Welcome, ' . explode(' ', $me['name'])[0], 'Your courses, live classes and certificates.', cms_img_raw('gallery', 'hero_image'), [['NUFA Academy', 'academy.php'], ['My learning']],
    '<a href="academy.php#courses" class="btn btn-gold">Find a course</a><a href="academy-logout.php?t=' . urlencode(mem_csrf()) . '" class="btn btn-outline">Sign out</a>');
?>
<section class="section"><div class="container">
  <?php if ($flash): ?><div style="padding:12px 16px;border-radius:12px;background:#e8f5ea;color:#1f6b2b;margin-bottom:12px"><?php echo ui_e($flash); ?></div><?php endif; ?>
  <?php if ($err): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:12px"><?php echo ui_e($err); ?></div><?php endif; ?>
  <?php if (!empty($_GET['pending'])): ?><div style="padding:12px 16px;border-radius:12px;background:#fff8e6;margin-bottom:12px">Your enrolment was received. <?php echo ui_e($S['acad_payment_info']); ?></div><?php endif; ?>

  <h2 style="margin-bottom:16px">My courses</h2>
  <?php if (!$enrols): ?><div class="empty-state"><h3>You have not enrolled yet</h3><p>Browse the courses and start learning.</p><a class="btn btn-gold" href="academy.php#courses">Browse courses</a></div><?php else: ?>
  <div class="ac-grid">
    <?php foreach ($enrols as $en): $c = $cmap[$en['course_id']] ?? null; if (!$c) continue; [$done, $tot, $pct] = acad_progress($conn, $en['id'], $c['id']); ?>
    <div class="ac-card" style="cursor:default">
      <div class="ac-body"><div class="ac-meta"><span class="ac-lvl ac-lvl--<?php echo strtolower($c['level']); ?>"><?php echo ui_e($c['level']); ?></span><span><?php echo ui_e($c['category']); ?></span></div>
        <h3><?php echo ui_e($c['title']); ?></h3>
        <?php if ($en['status'] === 'pending'): ?><p class="muted">Waiting for payment confirmation by the Academy team.</p>
        <?php else: ?><div class="ac-bar"><i style="width:<?php echo $pct; ?>%"></i></div><p class="muted" style="font-size:.88rem"><?php echo $done; ?> of <?php echo $tot; ?> lessons<?php echo $en['best_score'] ? ' · best exam score ' . (int)$en['best_score'] . '%' : ''; ?></p><?php endif; ?>
        <div class="ac-foot" style="gap:8px;flex-wrap:wrap">
          <?php if ($en['status'] === 'completed'): ?><a class="btn btn-gold btn-sm" href="academy-certificate.php?no=<?php echo ui_e($en['cert_no']); ?>">Certificate</a><a class="btn btn-outline btn-sm" href="academy-learn.php?course=<?php echo ui_e($c['slug']); ?>">Review</a>
          <?php elseif ($en['status'] === 'active'): ?><a class="btn btn-gold btn-sm" href="academy-learn.php?course=<?php echo ui_e($c['slug']); ?>"><?php echo $pct >= 100 ? 'Take the exam' : ($done ? 'Continue' : 'Start learning'); ?></a><?php endif; ?></div></div>
    </div>
    <?php endforeach; ?>
  </div><?php endif; ?>

  <h2 id="classes" style="margin:44px 0 16px">Live classes</h2>
  <?php if (!$classes): ?><p class="muted">No live classes are scheduled right now. Check back soon.</p><?php else: ?>
  <div class="ac-classes">
    <?php foreach ($classes as $k): $in = in_array((int)$k['id'], $signed, true); $taken = (int)$conn->query("SELECT COUNT(*) FROM academy_class_signups WHERE class_id=" . (int)$k['id'])->fetch_row()[0]; $full = $k['seats'] > 0 && $taken >= $k['seats']; ?>
    <div class="ac-class<?php echo $want === (int)$k['id'] ? ' is-hl' : ''; ?>"><span class="ac-mode ac-mode--<?php echo $k['mode']; ?>"><?php echo ['physical' => 'In person', 'virtual' => 'Online', 'hybrid' => 'In person + online'][$k['mode']] ?? ''; ?></span><h3><?php echo ui_e($k['title']); ?></h3>
      <p><?php echo $k['start_date'] ? ui_e(date('j M Y', strtotime($k['start_date']))) . ($k['end_date'] && $k['end_date'] !== $k['start_date'] ? ' – ' . ui_e(date('j M Y', strtotime($k['end_date']))) : '') : 'Date to be announced'; ?><?php echo $k['time_text'] !== '' ? ' · ' . ui_e($k['time_text']) : ''; ?></p>
      <?php if ($k['description']): ?><p class="muted"><?php echo ui_e(ui_excerpt($k['description'], 160)); ?></p><?php endif; ?>
      <?php if ($k['facilitator'] !== ''): ?><p class="muted">Facilitator: <?php echo ui_e($k['facilitator']); ?></p><?php endif; ?>
      <?php if ($k['fee_note'] !== ''): ?><p class="muted"><?php echo ui_e($k['fee_note']); ?></p><?php endif; ?>
      <?php if ($in): ?>
        <p><b style="color:#1f7a3d">You are registered.</b></p>
        <?php if ($k['venue'] !== '' && $k['mode'] !== 'virtual'): ?><p><b>Venue:</b> <?php echo ui_e($k['venue']); ?></p><?php endif; ?>
        <?php if ($k['meeting_link'] !== '' && $k['mode'] !== 'physical'): ?><p><a class="btn btn-gold btn-sm" href="<?php echo ui_e($k['meeting_link']); ?>" target="_blank" rel="noopener noreferrer">Join online class ↗</a></p><?php endif; ?>
        <form method="post"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="unclass"><input type="hidden" name="class_id" value="<?php echo (int)$k['id']; ?>"><button class="btn btn-ghost btn-sm">Cancel registration</button></form>
      <?php elseif (!$k['reg_open'] || $full): ?><p class="muted"><?php echo $full ? 'This class is full.' : 'Registration closed.'; ?></p>
      <?php else: ?><form method="post"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="class"><input type="hidden" name="class_id" value="<?php echo (int)$k['id']; ?>"><button class="btn btn-outline btn-sm">Register<?php echo $k['seats'] > 0 ? ' (' . max(0, $k['seats'] - $taken) . ' seats left)' : ''; ?></button></form><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div><?php endif; ?>

  <div class="ac-two" style="margin-top:44px">
    <form method="post" class="ac-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="profile"><h3>My details</h3>
      <div class="field"><label>Email (used to sign in)</label><input value="<?php echo ui_e($me['email']); ?>" disabled></div>
      <div class="field"><label>Full name (shown on certificates)</label><input name="name" value="<?php echo ui_e($me['name']); ?>" required></div>
      <div class="field"><label>Phone</label><input name="phone" value="<?php echo ui_e($me['phone']); ?>"></div>
      <div class="field"><label>Country</label><input name="country" value="<?php echo ui_e($me['country']); ?>"></div>
      <button class="btn btn-outline">Save details</button></form>
    <form method="post" class="ac-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="act" value="password"><h3>Change password</h3>
      <div class="field"><label>Current password</label><input type="password" name="old" required autocomplete="current-password"></div>
      <div class="field"><label>New password</label><input type="password" name="new" required minlength="8" autocomplete="new-password"></div>
      <div class="field"><label>Confirm new password</label><input type="password" name="new2" required autocomplete="new-password"></div>
      <button class="btn btn-outline">Change password</button></form>
  </div>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
