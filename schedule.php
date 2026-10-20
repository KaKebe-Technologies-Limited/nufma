<?php
require_once __DIR__ . "/includes/schedule.php";   // also loads membership, db
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
sch_install($conn);

// unsubscribe link from a reminder
$unsubDone = false;
if (preg_match('/^[a-f0-9]{32}$/', $_GET['unsub'] ?? '')) {
    $s = $conn->prepare("UPDATE event_subscribers SET unsubscribed=1 WHERE token=?"); $s->bind_param("s", $_GET['unsub']); $s->execute(); $unsubDone = $s->affected_rows > 0;
}

$progs = $conn->query("SELECT id, title, slug FROM programmes WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$fc = nufa_form_cfg('schedule');
if (!$fc['enabled'] && $_SERVER['REQUEST_METHOD'] === 'POST') $_SERVER['REQUEST_METHOD'] = 'GET';
$errors = []; $sent = isset($_GET['sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($g = form_guard($conn, 'schedule', 'event_subscribers')) $errors[] = $g;
    $name = form_clean($_POST['name'] ?? '', 150); $email = strtolower(form_clean($_POST['email'] ?? '', 190)); $phone = form_clean($_POST['phone'] ?? '', 40);
    $pid = (int)($_POST['programme_id'] ?? 0); $wantE = !empty($_POST['want_email']) ? 1 : 0; $wantS = !empty($_POST['want_sms']) ? 1 : 0;
    if ($pid && !in_array($pid, array_column($progs, 'id'))) $pid = 0;
    if ($name === '') $errors[] = "Please enter your name.";
    if (!$wantE && !$wantS) $errors[] = "Choose how you would like to be reminded: email, SMS or both.";
    if ($wantE && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if ($wantS && strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Please enter a valid phone number for SMS reminders.";
    if (empty($_POST['consent'])) $errors[] = "Please consent to receiving reminders.";
    if (!$errors) {
        $tok = bin2hex(random_bytes(16)); $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
        $s = $conn->prepare("INSERT INTO event_subscribers (programme_id, name, email, phone, want_email, want_sms, token, ip) VALUES (?,?,?,?,?,?,?,?)");
        $s->bind_param("isssiiss", $pid, $name, $email, $phone, $wantE, $wantS, $tok, $ip);
        if ($s->execute()) { header("Location: schedule.php?sent=1#remind"); exit; }
        $errors[] = "We couldn't save your request just now. Please try again.";
    }
}

// month shown
$m = preg_match('/^\d{4}-\d{2}$/', $_GET['m'] ?? '') ? $_GET['m'] : date('Y-m');
$first = new DateTime("$m-01"); if ($first->format('Y') < date('Y') - 2 || $first->format('Y') > date('Y') + 10) $first = new DateTime(date('Y-m-01'));
$m = $first->format('Y-m'); $last = (clone $first)->modify('last day of this month');
$pf = 0; $pfSlug = $_GET['p'] ?? '';
foreach ($progs as $p) if ($p['slug'] === $pfSlug) $pf = (int)$p['id'];
$occ = sch_occurrences($conn, $first->format('Y-m-d'), $last->format('Y-m-d'), $pf);
$byDay = []; foreach ($occ as $o) $byDay[$o['date']][] = $o;
$upcoming = array_values(array_filter(sch_occurrences($conn, date('Y-m-d'), date('Y-m-d', strtotime('+180 day')), $pf), fn($o) => $o['kind'] !== 'holiday'));
$upcoming = array_slice($upcoming, 0, 3);
$qs = fn($mm) => 'schedule.php?m=' . $mm . ($pf ? '&p=' . urlencode($pfSlug) : '') . '#calendar';
$prev = (clone $first)->modify('-1 month')->format('Y-m'); $next = (clone $first)->modify('+1 month')->format('Y-m');
$today = date('Y-m-d');
$kindCls = ['programme' => 'cal-ev--prog', 'important' => 'cal-ev--imp', 'holiday' => 'cal-ev--hol'];

$pageTitle = cms_value('seo', 'schedule_title');
$pageDesc  = cms_value('seo', 'schedule_desc');
$activeNav = "schedule";
$canonicalPath = "schedule.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Schedule & Calendar', cms_value('schedule', 'hero_title'), cms_value('schedule', 'hero_lede'), cms_img_raw('schedule', 'hero_image'), [['Programmes', 'programmes.php'], ['Schedule']],
    '<a href="#calendar" class="btn btn-gold">View the calendar</a><a href="#remind" class="btn btn-outline">Get reminders</a>');
?>
<?php if ($unsubDone): ?><section class="section--tight"><div class="container"><div class="alert ok" role="status">You have been unsubscribed from NUFA reminders.</div></div></section><?php endif; ?>

<?php if (nufa_show('schedule', 'next') && $upcoming): ?><section class="section--tight"><div class="container">
  <div class="section-head" data-reveal><div class="eyebrow">Coming up</div><h2><?php echo cms_t('schedule', 'next_heading'); ?></h2></div>
  <div class="grid grid-3" data-reveal-stagger>
  <?php foreach ($upcoming as $o): $d = strtotime($o['date']); ?>
    <article class="next-ev"><div class="next-ev__date"><b><?php echo date('j', $d); ?></b><span><?php echo date('M Y', $d); ?></span></div>
      <div><h3><?php echo ui_e($o['title']); ?></h3>
        <p class="muted"><?php echo ui_e(sch_time($o)); ?><?php if ($o['venue'] !== ''): ?> · <?php echo ui_e($o['venue']); ?><?php endif; ?></p>
        <?php if (!empty($o['programme_title'])): ?><span class="chip"><?php echo ui_e($o['programme_title']); ?></span><?php endif; ?>
        <?php if ($o['meeting_link'] !== '' && (int)$o['show_link'] === 1): ?> <a class="link-arrow" href="<?php echo ui_e($o['meeting_link']); ?>" target="_blank" rel="noopener">Join online</a><?php endif; ?></div></article>
  <?php endforeach; ?></div>
</div></section><?php endif; ?>

<?php if (nufa_show('schedule', 'calendar')): ?><section class="section" id="calendar">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Calendar</div><h2><?php echo cms_t('schedule', 'calendar_heading'); ?></h2><p><?php echo cms_t('schedule', 'calendar_intro'); ?></p></div>
    <div class="cal-bar">
      <a class="btn btn-outline btn-sm" href="<?php echo $qs($prev); ?>" aria-label="Previous month">‹ Prev</a>
      <h3><?php echo $first->format('F Y'); ?></h3>
      <a class="btn btn-outline btn-sm" href="<?php echo $qs($next); ?>" aria-label="Next month">Next ›</a>
    </div>
    <form method="get" action="schedule.php#calendar" class="cal-filter"><input type="hidden" name="m" value="<?php echo $m; ?>">
      <label for="cal-p">Show</label><select id="cal-p" name="p" onchange="this.form.submit()"><option value="">All programmes</option><?php foreach ($progs as $p): ?><option value="<?php echo ui_e($p['slug']); ?>"<?php echo $pf == $p['id'] ? ' selected' : ''; ?>><?php echo ui_e($p['title']); ?></option><?php endforeach; ?></select>
      <noscript><button class="btn btn-sm">Filter</button></noscript>
      <span class="cal-legend"><i class="cal-dot cal-dot--prog"></i>Programme <i class="cal-dot cal-dot--imp"></i>Important day <i class="cal-dot cal-dot--hol"></i>Public holiday</span>
    </form>
    <div class="cal" role="table" aria-label="Calendar for <?php echo $first->format('F Y'); ?>">
      <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dn): ?><div class="cal-h" role="columnheader"><?php echo $dn; ?></div><?php endforeach; ?>
      <?php $lead = (int)$first->format('N') - 1; for ($i = 0; $i < $lead; $i++) echo '<div class="cal-d cal-d--empty" aria-hidden="true"></div>';
      for ($day = 1, $n = (int)$last->format('j'); $day <= $n; $day++):
        $ds = sprintf('%s-%02d', $m, $day); $evs = $byDay[$ds] ?? []; $isHol = false; foreach ($evs as $e) if ($e['kind'] === 'holiday') $isHol = true; ?>
        <div class="cal-d<?php echo $ds === $today ? ' cal-d--today' : ''; ?><?php echo $isHol ? ' cal-d--hol' : ''; ?><?php echo ((int)date('N', strtotime($ds)) >= 6) ? ' cal-d--we' : ''; ?>" role="cell">
          <span class="cal-n"><?php echo $day; ?></span>
          <?php foreach (array_slice($evs, 0, 3) as $e): ?><a class="cal-ev <?php echo $kindCls[$e['kind']] ?? ''; ?>" href="#d-<?php echo $ds; ?>" title="<?php echo ui_e($e['title']); ?>"><?php echo ui_e($e['title']); ?></a><?php endforeach; ?>
          <?php if (count($evs) > 3): ?><a class="cal-more" href="#d-<?php echo $ds; ?>">+<?php echo count($evs) - 3; ?> more</a><?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>

    <h3 style="margin:44px 0 18px">Agenda for <?php echo $first->format('F Y'); ?></h3>
    <?php if (!$occ): ?><p class="muted">Nothing is scheduled for this month yet. Check another month, or <a href="#remind">sign up for reminders</a>.</p><?php endif; ?>
    <div class="agenda">
    <?php foreach ($occ as $o): $d = strtotime($o['date']); ?>
      <article class="agenda-item <?php echo $kindCls[$o['kind']] ?? ''; ?>" id="d-<?php echo $o['date']; ?>">
        <div class="agenda-date"><b><?php echo date('j', $d); ?></b><span><?php echo date('D', $d); ?></span></div>
        <div class="agenda-body">
          <h4><?php echo ui_e($o['title']); ?> <span class="chip"><?php echo ui_e(sch_kinds()[$o['kind']] ?? ''); ?></span></h4>
          <p class="muted"><?php echo ui_e(sch_time($o)); ?><?php if ($o['venue'] !== ''): ?> · <?php echo ui_e($o['venue']); ?><?php endif; ?><?php if (!empty($o['programme_title'])): ?> · <a href="programme.php?slug=<?php echo urlencode($o['programme_slug']); ?>"><?php echo ui_e($o['programme_title']); ?></a><?php endif; ?></p>
          <?php if (trim((string)$o['description']) !== ''): ?><p><?php echo nl2br(ui_e($o['description'])); ?></p><?php endif; ?>
          <?php if ($o['meeting_link'] !== '' && (int)$o['show_link'] === 1): ?><a class="btn btn-gold btn-sm" href="<?php echo ui_e($o['meeting_link']); ?>" target="_blank" rel="noopener">Join the meeting</a><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?></div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('schedule', 'reminders')): ?><section class="section section--soft" id="remind">
  <div class="container member-grid" style="grid-template-columns:.8fr 1.3fr">
    <div data-reveal><div class="eyebrow">Reminders</div><h2><?php echo cms_t('schedule', 'remind_heading'); ?></h2><p class="lede"><?php echo cms_t('schedule', 'remind_intro'); ?></p></div>
    <div class="form-card" data-reveal>
      <?php if ($fc['intro'] !== ''): ?><p class="muted" style="margin-bottom:18px"><?php echo ui_e($fc['intro']); ?></p><?php endif; ?>
      <?php echo form_banner($sent, $errors, $fc['success']); ?>
      <?php if (!$fc['enabled']): ?><div class="alert err" role="status"><?php echo ui_e($fc['closed']); ?></div><?php elseif (!$sent): ?>
      <form method="post" action="schedule.php#remind">
        <?php echo form_open_fields(); ?>
        <div class="form-grid">
          <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name']); ?>
          <?php echo f_input('email', 'Email address', ['type' => 'email', 'autocomplete' => 'email']); ?>
          <?php echo f_input('phone', 'Phone (for SMS)', ['type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '+256…']); ?>
          <div class="field"><label for="f-programme_id">Remind me about</label><select id="f-programme_id" name="programme_id"><option value="0">All programmes</option><?php foreach ($progs as $p): ?><option value="<?php echo (int)$p['id']; ?>"<?php echo (int)($_POST['programme_id'] ?? 0) === (int)$p['id'] ? ' selected' : ''; ?>><?php echo ui_e($p['title']); ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="check"><label><input type="checkbox" name="want_email" value="1" <?php echo !$errors || !empty($_POST['want_email']) ? 'checked' : ''; ?>> Email me</label></div>
        <div class="check"><label><input type="checkbox" name="want_sms" value="1" <?php echo !empty($_POST['want_sms']) ? 'checked' : ''; ?>> Send me SMS</label></div>
        <?php echo f_check('consent', 'I agree that NUFA may send me reminders about the programmes I selected. I can unsubscribe at any time.'); ?>
        <button class="btn btn-gold btn-block" type="submit"><?php echo ui_e($fc['button']); ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section><?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
