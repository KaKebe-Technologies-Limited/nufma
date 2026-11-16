<?php
$adminTitle = "NUFA Film Fund page";
$adminNav = "filmfund";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/membership.php";
require_once __DIR__ . "/../includes/filmfund.php";
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$flash = ''; $flashType = 'ok';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        foreach (array_keys(ff_defaults()) as $k) {
            if ($k === 'ff_status_show') { $save($k, isset($_POST[$k]) ? '1' : '0'); continue; }
            $save($k, trim(str_replace("\r\n", "\n", (string)($_POST[$k] ?? ''))));
        }
        $flash = 'Film Fund page saved.';
    }
}
$F = ff_settings($conn);
$f = fn($k, $l, $big = false) => '<div class="field"><label>' . $l . '</label>' . ($big ? '<textarea name="' . $k . '" style="min-height:150px">' . $e($F[$k]) . '</textarea>' : '<input name="' . $k . '" value="' . $e($F[$k]) . '">') . '</div>';
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $e($flash); ?></div><?php endif; ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>NUFA Film Fund page</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="../film-fund.php" target="_blank">View it on the website ↗</a></div>
  <p class="admin-help">Everything on the Film Fund page is edited here. Only publish facts NUFA has confirmed (amounts, who can apply, dates). The hero picture is changed under <a href="site-images.php">All pictures on the site</a>.</p>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?>
    <h3>Top of the page</h3>
    <div class="admin-form-grid"><?php echo $f('ff_hero_title', 'Page heading') . $f('ff_hero_lede', 'Line under the heading'); ?></div>
    <h3>About the fund</h3>
    <?php echo $f('ff_intro_title', 'Section heading') . $f('ff_intro', 'Text (leave a blank line between paragraphs)', true); ?>
    <h3>Notice</h3>
    <label style="display:flex;gap:8px;align-items:center;margin:6px 0 10px"><input type="checkbox" name="ff_status_show" value="1" <?php echo $F['ff_status_show'] === '1' ? 'checked' : ''; ?>> Show the notice box</label>
    <?php echo $f('ff_status', 'Notice text (for example “Applications open on …”)', true); ?>
    <h3>Steps</h3>
    <?php echo $f('ff_steps_title', 'Section heading'); ?>
    <div class="admin-form-grid"><?php for ($n = 1; $n <= 3; $n++) echo $f("ff_step{$n}_t", "Step $n title") . $f("ff_step{$n}_d", "Step $n description"); ?></div>
    <h3>Buttons</h3>
    <div class="admin-form-grid"><?php echo $f('ff_btn1_label', 'Button 1 text') . $f('ff_btn1_url', 'Button 1 link') . $f('ff_btn2_label', 'Button 2 text') . $f('ff_btn2_url', 'Button 2 link'); ?></div>
    <button class="admin-btn admin-btn-brand">Save</button>
  </form>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
