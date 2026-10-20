<?php
$adminTitle = "Site Settings";
$adminNav = "settings";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/fonts.php";
require_once __DIR__ . "/../includes/schema.php";

$textKeys = ['site_email', 'site_phone', 'site_phone2', 'site_address', 'site_website', 'facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'youtube_url', 'ticket_price',
             'impact1_value', 'impact1_label', 'impact2_value', 'impact2_label', 'impact3_value', 'impact3_label', 'impact4_value', 'impact4_label'];
$switchKeys = ['tickets_open', 'vm_approved', 'policies_approved'];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $font = $_POST['font_preset'] ?? 'cinematic';
    if (!isset($FONT_PRESETS[$font])) $font = 'cinematic';
    $fields = ['font_preset' => $font];
    foreach ($textKeys as $k) $fields[$k] = trim($_POST[$k] ?? '');
    foreach ($switchKeys as $k) $fields[$k] = isset($_POST[$k]) ? '1' : '0';

    if ($fields['site_email'] !== '' && !filter_var($fields['site_email'], FILTER_VALIDATE_EMAIL)) $err = "Enter a valid contact email address.";
    foreach (['facebook_url', 'instagram_url', 'x_url', 'linkedin_url', 'youtube_url', 'site_website'] as $k)
        if ($fields[$k] !== '' && !preg_match('#^https?://#i', $fields[$k])) $err = "Links must start with https:// — check " . str_replace('_', ' ', $k) . ".";

    if (!$err) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        foreach ($fields as $k => $v) { $stmt->bind_param("ss", $k, $v); $stmt->execute(); }
        header("Location: settings.php?saved=1");
        exit;
    }
}

$current = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $res->fetch_assoc()) $current[$row['setting_key']] = $row['setting_value'];
if ($err) $current = array_merge($current, $_POST);
require_once __DIR__ . "/../includes/settings.php";
$cfg = nufa_site_settings($conn);           // verified defaults shown as placeholders
$activeFontKey = $current['font_preset'] ?? 'cinematic';
$v = fn($k) => htmlspecialchars($current[$k] ?? '');
$ph = fn($k) => htmlspecialchars($cfg[$k] ?? '');
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Settings saved — changes apply across the website immediately.</div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<form method="post" class="admin-form">
  <div class="admin-card">
    <div class="admin-card-head"><h2>Organisation &amp; contact details</h2><span class="admin-help">Used in the header, footer, Contact page and emails. Blank fields fall back to the verified details shown.</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Contact email</label><input type="email" name="site_email" value="<?php echo $v('site_email'); ?>" placeholder="<?php echo $ph('site_email'); ?>"></div>
      <div class="field"><label>Telephone</label><input name="site_phone" value="<?php echo $v('site_phone'); ?>" placeholder="<?php echo $ph('site_phone'); ?>"></div>
      <div class="field"><label>Alternative telephone</label><input name="site_phone2" value="<?php echo $v('site_phone2'); ?>" placeholder="<?php echo $ph('site_phone2'); ?>"></div>
      <div class="field"><label>Website</label><input type="url" name="site_website" value="<?php echo $v('site_website'); ?>" placeholder="<?php echo $ph('site_website'); ?>"></div>
    </div>
    <div class="field"><label>Address</label><input name="site_address" value="<?php echo $v('site_address'); ?>" placeholder="<?php echo $ph('site_address'); ?>"></div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Social media</h2><span class="admin-help">Full links only (https://…). Leave blank to hide an icon.</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Facebook</label><input type="url" name="facebook_url" value="<?php echo $v('facebook_url'); ?>" placeholder="<?php echo $ph('facebook_url'); ?>"></div>
      <div class="field"><label>Instagram</label><input type="url" name="instagram_url" value="<?php echo $v('instagram_url'); ?>" placeholder="<?php echo $ph('instagram_url'); ?>"></div>
      <div class="field"><label>X (Twitter)</label><input type="url" name="x_url" value="<?php echo $v('x_url'); ?>" placeholder="<?php echo $ph('x_url'); ?>"></div>
      <div class="field"><label>LinkedIn</label><input type="url" name="linkedin_url" value="<?php echo $v('linkedin_url'); ?>" placeholder="<?php echo $ph('linkedin_url'); ?>"></div>
      <div class="field"><label>YouTube</label><input type="url" name="youtube_url" value="<?php echo $v('youtube_url'); ?>" placeholder="https://www.youtube.com/@…"></div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Approvals &amp; publication switches</h2><span class="admin-help">Keep these off until NUFA has confirmed the matter.</span></div>
    <label class="mem-check"><input type="checkbox" name="vm_approved" value="1" <?php echo ($current['vm_approved'] ?? '0') === '1' ? 'checked' : ''; ?>> Vision and mission statements are approved by NUFA leadership (removes the “Draft” label on the About page)</label>
    <label class="mem-check"><input type="checkbox" name="policies_approved" value="1" <?php echo ($current['policies_approved'] ?? '0') === '1' ? 'checked' : ''; ?>> Policy pages have been reviewed by a qualified professional (removes the “Draft for review” notice)</label>
    <label class="mem-check"><input type="checkbox" name="tickets_open" value="1" <?php echo ($current['tickets_open'] ?? '0') === '1' ? 'checked' : ''; ?>> Ticket information has been officially confirmed — show it on the Awards page</label>
    <div class="field" style="max-width:320px;margin-top:10px"><label>Ticket price (UGX, optional)</label><input name="ticket_price" value="<?php echo $v('ticket_price'); ?>" placeholder="e.g. 30,000"></div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Verified impact figures</h2><span class="admin-help">The homepage Impact section stays qualitative until you add figures NUFA can verify. Fill a value and a label to show an indicator.</span></div>
    <div class="admin-form-grid">
      <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="field"><label>Indicator <?php echo $i; ?></label>
        <div style="display:flex;gap:8px"><input name="impact<?php echo $i; ?>_value" value="<?php echo $v("impact{$i}_value"); ?>" placeholder="Value" style="max-width:120px"><input name="impact<?php echo $i; ?>_label" value="<?php echo $v("impact{$i}_label"); ?>" placeholder="e.g. Filmmakers trained"></div></div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Site fonts</h2><span class="admin-help">Applies to the whole public site</span></div>
    <div class="font-picker">
      <?php foreach ($FONT_PRESETS as $key => $p): ?>
      <label class="font-option <?php echo $activeFontKey === $key ? 'is-selected' : ''; ?>">
        <input type="radio" name="font_preset" value="<?php echo $key; ?>" <?php echo $activeFontKey === $key ? 'checked' : ''; ?>>
        <span class="font-option-name" style="font-family:<?php echo $p['heading']; ?>"><?php echo htmlspecialchars($p['label']); ?></span>
        <span class="font-option-sample" style="font-family:<?php echo $p['body']; ?>">The quick brown fox — Aa Bb Cc</span>
        <span class="font-option-meta"><?php echo htmlspecialchars($p['sample']); ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <button type="submit" class="admin-btn admin-btn-brand">Save settings</button>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
