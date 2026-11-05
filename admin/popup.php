<?php
$adminTitle = "Welcome popup";
$adminNav = "popup";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/membership.php";
require_once __DIR__ . "/../includes/popup.php";
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$flash = ''; $flashType = 'ok';
$P = popup_settings($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $save('popup_enabled', isset($_POST['popup_enabled']) ? '1' : '0');
        foreach (['popup_title', 'popup_lead', 'popup_tagline', 'popup_tags', 'popup_btn_label', 'popup_btn_url'] as $k) $save($k, trim($_POST[$k] ?? ''));
        $save('popup_body', trim(str_replace("\r\n", "\n", $_POST['popup_body'] ?? '')));
        $save('popup_delay', (string)max(0, min(30, (int)($_POST['popup_delay'] ?? 1))));
        if (!empty($_POST['popup_reshow'])) $save('popup_version', (string)((int)$P['popup_version'] + 1));
        if (!empty($_POST['remove_image'])) $save('popup_image', '__none__');
        if (!empty($_FILES['popup_image']['name'])) {
            [$fn, $err] = mem_store_upload($_FILES['popup_image'], MEM_IMG_TYPES, 6 * 1048576, 'popup', __DIR__ . "/../assets/images/uploads/content/");
            if ($err) { $flash = 'Picture: ' . $err; $flashType = 'err'; } else $save('popup_image', 'uploads/content/' . $fn);
        }
        if ($flashType !== 'err') $flash = 'Popup saved.' . (!empty($_POST['popup_reshow']) ? ' It will show again to every visitor.' : '');
        $P = popup_settings($conn);
    }
}
$imgShown = ($P['popup_image'] !== '' && $P['popup_image'] !== '__none__' && is_file(__DIR__ . '/../assets/images/' . $P['popup_image'])) ? $P['popup_image'] : '';
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $e($flash); ?></div><?php endif; ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Welcome popup</h2><span><span class="admin-badge <?php echo $P['popup_enabled'] === '1' ? '' : 'gray'; ?>"><?php echo $P['popup_enabled'] === '1' ? 'On' : 'Off'; ?></span> <a class="admin-btn admin-btn-line admin-btn-sm" href="../index.php?popup=1" target="_blank">Preview it on the website ↗</a></span></div>
  <p class="admin-help">A message that opens on top of the website the <b>first time</b> someone visits from a phone or computer. After they close it, it does not appear again on that device — until you tick “Show it again to everyone” below.</p>
  <form method="post" enctype="multipart/form-data" class="admin-form"><?php echo mem_csrf_field(); ?>
    <label style="display:flex;gap:8px;align-items:center;margin:8px 0 14px"><input type="checkbox" name="popup_enabled" value="1" <?php echo $P['popup_enabled'] === '1' ? 'checked' : ''; ?>> Show the popup to new visitors</label>
    <div class="field"><label>Picture (portrait poster works best, e.g. 640 × 960)</label><input type="file" name="popup_image" accept="image/jpeg,image/png,image/webp">
      <?php if ($imgShown): ?><div style="margin-top:8px;display:flex;gap:12px;align-items:center"><img src="../assets/images/<?php echo $e($imgShown); ?>" alt="" style="height:110px;border-radius:10px;border:1px solid #ddd"><label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="remove_image" value="1"> Show the popup without a picture</label></div><?php endif; ?></div>
    <div class="admin-form-grid">
      <div class="field"><label>Heading</label><input name="popup_title" value="<?php echo $e($P['popup_title']); ?>"></div>
      <div class="field"><label>Highlighted line under the heading</label><input name="popup_lead" value="<?php echo $e($P['popup_lead']); ?>"></div>
    </div>
    <div class="field"><label>Message (leave a blank line between paragraphs)</label><textarea name="popup_body" style="min-height:230px"><?php echo $e($P['popup_body']); ?></textarea></div>
    <div class="admin-form-grid">
      <div class="field"><label>Closing line (shown with an orange bar)</label><input name="popup_tagline" value="<?php echo $e($P['popup_tagline']); ?>"></div>
      <div class="field"><label>Hashtags</label><input name="popup_tags" value="<?php echo $e($P['popup_tags']); ?>"></div>
      <div class="field"><label>Button text (leave empty for no button)</label><input name="popup_btn_label" value="<?php echo $e($P['popup_btn_label']); ?>"></div>
      <div class="field"><label>Button link</label><input name="popup_btn_url" value="<?php echo $e($P['popup_btn_url']); ?>" placeholder="awards.php or https://…"></div>
      <div class="field"><label>Open after (seconds)</label><input type="number" min="0" max="30" name="popup_delay" value="<?php echo $e($P['popup_delay']); ?>"></div>
    </div>
    <label style="display:flex;gap:8px;align-items:center;margin:10px 0 14px"><input type="checkbox" name="popup_reshow" value="1"> Show it again to everyone, even people who already closed it (use this when you publish a new announcement)</label>
    <button class="admin-btn admin-btn-brand">Save popup</button>
  </form>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
