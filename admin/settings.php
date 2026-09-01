<?php
$adminTitle = "Settings";
$adminNav = "settings";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/fonts.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $font = $_POST['font_preset'] ?? 'editorial';
    if (!isset($FONT_PRESETS[$font])) $font = 'editorial';

    $fields = [
        'font_preset'  => $font,
        'site_email'   => trim($_POST['site_email'] ?? ''),
        'site_phone'   => trim($_POST['site_phone'] ?? ''),
        'site_address' => trim($_POST['site_address'] ?? ''),
        'ticket_price' => trim($_POST['ticket_price'] ?? ''),
        'facebook_url'  => trim($_POST['facebook_url'] ?? ''),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'x_url'         => trim($_POST['x_url'] ?? ''),
        'linkedin_url'  => trim($_POST['linkedin_url'] ?? ''),
    ];
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($fields as $k => $v) {
        $stmt->bind_param("ss", $k, $v);
        $stmt->execute();
    }
    header("Location: settings.php?saved=1");
    exit;
}

$current = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $res->fetch_assoc()) $current[$row['setting_key']] = $row['setting_value'];
$activeFontKey = $current['font_preset'] ?? 'editorial';

include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Settings saved — changes apply site-wide immediately.</div><?php endif; ?>

<form method="post">
  <div class="admin-card">
    <div class="admin-card-head"><h2>Site Fonts</h2><span class="admin-help">Applies to the whole public site</span></div>
    <div class="font-picker">
      <?php foreach ($FONT_PRESETS as $key => $p): ?>
      <label class="font-option <?php echo $activeFontKey===$key?'is-selected':''; ?>">
        <input type="radio" name="font_preset" value="<?php echo $key; ?>" <?php echo $activeFontKey===$key?'checked':''; ?>>
        <span class="font-option-name" style="font-family:<?php echo $p['heading']; ?>"><?php echo htmlspecialchars($p['label']); ?></span>
        <span class="font-option-sample" style="font-family:<?php echo $p['body']; ?>">The quick brown fox — Aa Bb Cc</span>
        <span class="font-option-meta"><?php echo htmlspecialchars($p['sample']); ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Contact Details</h2><span class="admin-help">Shown in the footer and Contact page</span></div>
    <div class="admin-form">
      <div class="admin-form-grid">
        <div class="field">
          <label>Site email</label>
          <input type="email" name="site_email" value="<?php echo htmlspecialchars($current['site_email'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Site phone</label>
          <input type="text" name="site_phone" value="<?php echo htmlspecialchars($current['site_phone'] ?? ''); ?>">
        </div>
      </div>
      <div class="field">
        <label>Address</label>
        <input type="text" name="site_address" value="<?php echo htmlspecialchars($current['site_address'] ?? ''); ?>">
      </div>
      <div class="field">
        <label>NUFA27 ticket price (UGX)</label>
        <input type="text" name="ticket_price" value="<?php echo htmlspecialchars($current['ticket_price'] ?? ''); ?>">
      </div>
      <div class="admin-form-grid">
        <div class="field">
          <label>Facebook URL</label>
          <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($current['facebook_url'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>X (Twitter) URL</label>
          <input type="url" name="x_url" value="<?php echo htmlspecialchars($current['x_url'] ?? ''); ?>">
        </div>
      </div>
      <div class="admin-form-grid">
        <div class="field">
          <label>Instagram URL</label>
          <input type="url" name="instagram_url" value="<?php echo htmlspecialchars($current['instagram_url'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>LinkedIn URL</label>
          <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($current['linkedin_url'] ?? ''); ?>">
        </div>
      </div>
    </div>
  </div>

  <button type="submit" class="admin-btn admin-btn-brand">Save Settings</button>
</form>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
