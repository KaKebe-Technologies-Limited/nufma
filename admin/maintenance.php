<?php
$adminTitle = "Maintenance mode";
$adminNav = "maintenance";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/maintenance.php";

$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$dt = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string)$v) ? $v : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? 'save';
    $fields = ['maint_title' => trim($_POST['maint_title'] ?? ''), 'maint_message' => trim($_POST['maint_message'] ?? ''), 'maint_start' => $dt($_POST['maint_start'] ?? ''), 'maint_end' => $dt($_POST['maint_end'] ?? ''),
               'maint_countdown' => isset($_POST['maint_countdown']) ? '1' : '0', 'maint_auto_end' => isset($_POST['maint_auto_end']) ? '1' : '0'];
    if ($fields['maint_title'] === '') $fields['maint_title'] = "We'll be right back";
    $s = maint_ts($fields['maint_start']); $e = maint_ts($fields['maint_end']);
    if ($s && $e && $e <= $s) $error = "The expected return time must be after the start time.";
    elseif ($act === 'on' && $e && $e <= time() && !$s) $error = "The expected return time has already passed — pick a later time, or clear it.";
    else {
        foreach ($fields as $k => $v) $save($k, $v);
        if ($act === 'on') $save('maint_on', '1'); elseif ($act === 'off') $save('maint_on', '0');
        header("Location: maintenance.php?done=" . urlencode($act)); exit;
    }
}
$m = maint_settings($conn); $st = maint_state($m); $cfg = nufa_site_settings($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST') $m = array_merge($m, array_intersect_key($_POST, $m));
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['done'])): ?><div class="admin-alert ok"><?php echo ['on' => ($st === 'scheduled' ? 'Maintenance mode is scheduled.' : 'The website is now OFF for visitors.'), 'off' => 'The website is back ONLINE.', 'save' => 'Saved.'][$_GET['done']] ?? 'Saved.'; ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card" style="border-left:6px solid <?php echo $st === 'down' ? '#c0392b' : ($st === 'scheduled' ? '#d4a017' : '#1f7a3d'); ?>">
  <div class="admin-card-head"><h2>Website status:
    <?php echo $st === 'down' ? '<span class="admin-badge red">OFF — visitors see the maintenance page</span>' : ($st === 'scheduled' ? '<span class="admin-badge gray">Scheduled to go off ' . htmlspecialchars(date('j M Y, H:i', maint_ts($m['maint_start']))) . '</span>' : '<span class="admin-badge">ONLINE</span>'); ?></h2>
    <a class="admin-btn admin-btn-line admin-btn-sm" href="../index.php" target="_blank">Open the site ↗</a></div>
  <p class="admin-help">While the website is off, visitors see your logo, the message below, your contact details, a countdown and the expected return date. You stay signed in to the admin and can still see the real site to check your update. Times are East Africa Time (Kampala).</p>
</div>

<form method="post" class="admin-card admin-form">
  <div class="admin-card-head"><h2>Maintenance page</h2></div>
  <div class="admin-form-grid">
    <div class="field"><label>Heading</label><input name="maint_title" value="<?php echo htmlspecialchars($m['maint_title']); ?>"></div>
    <div class="field"><label>Turn the site off at (optional)</label><input type="datetime-local" name="maint_start" value="<?php echo htmlspecialchars($m['maint_start']); ?>"><small class="admin-help">Leave empty to switch off immediately when you press the button.</small></div>
    <div class="field"><label>Expected back online (for the countdown)</label><input type="datetime-local" name="maint_end" value="<?php echo htmlspecialchars($m['maint_end']); ?>"></div>
  </div>
  <div class="field"><label>Message to visitors</label><textarea name="maint_message" rows="4"><?php echo htmlspecialchars($m['maint_message']); ?></textarea></div>
  <div style="display:flex;gap:18px;flex-wrap:wrap;margin:6px 0 16px"><label><input type="checkbox" name="maint_countdown" <?php echo $m['maint_countdown'] === '1' ? 'checked' : ''; ?>> Show the countdown</label><label><input type="checkbox" name="maint_auto_end" <?php echo $m['maint_auto_end'] === '1' ? 'checked' : ''; ?>> Bring the site back automatically at the expected time</label></div>
  <p class="admin-help">Contact details shown: <?php echo htmlspecialchars(implode(' · ', array_filter([$cfg['site_email'], $cfg['site_phone'], $cfg['site_phone2'], $cfg['site_address']]))); ?> — change them in <a href="settings.php">Site settings</a>. The logo comes from <a href="appearance.php">Appearance &amp; branding</a>.</p>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <?php if ($m['maint_on'] === '1'): ?>
      <button class="admin-btn admin-btn-brand" name="act" value="off">Bring the website back online</button>
      <button class="admin-btn admin-btn-line" name="act" value="save">Save changes</button>
    <?php else: ?>
      <button class="admin-btn admin-btn-danger" name="act" value="on" data-confirm="Turn the website off for visitors?">Turn the website off</button>
      <button class="admin-btn admin-btn-line" name="act" value="save">Save without switching</button>
    <?php endif; ?>
  </div>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
