<?php
$adminTitle = "Appearance & Branding";
$adminNav = "appearance";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
require_once __DIR__ . "/../includes/settings.php";

$dir = __DIR__ . "/../assets/images/uploads/brand/";
$msg = ''; $err = '';
$save = function ($k, $v) use ($conn) {
    $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $s->bind_param("ss", $k, $v); $s->execute();
};
$cfg = nufa_site_settings($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'reset_colours') {
        $save('brand_gold', '#D4AF63'); $save('brand_ink', '#111111'); $msg = "Colours restored to the NUFA defaults.";
    } else {
        foreach (['brand_gold', 'brand_ink'] as $k) {
            $v = trim($_POST[$k] ?? '');
            if (!preg_match('/^#[0-9a-f]{6}$/i', $v)) { $err = "Colours must be a 6-digit hex value such as #D4AF63."; break; }
            $save($k, strtoupper($v));
        }
        $save('show_topbar', isset($_POST['show_topbar']) ? '1' : '0');
        $save('show_clock', isset($_POST['show_clock']) ? '1' : '0');
        $save('whatsapp_enabled', isset($_POST['whatsapp_enabled']) ? '1' : '0');
        $save('whatsapp_number', preg_replace('/[^0-9+ ]/', '', mb_substr(trim($_POST['whatsapp_number'] ?? ''), 0, 30)));
        $save('whatsapp_message', mb_substr(trim($_POST['whatsapp_message'] ?? ''), 0, 300));
        $save('pwa_enabled', isset($_POST['pwa_enabled']) ? '1' : '0');
        $tzIn = trim($_POST['clock_timezone'] ?? ''); $save('clock_timezone', in_array($tzIn, DateTimeZone::listIdentifiers(), true) ? $tzIn : 'Africa/Kampala');
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        foreach (['site_logo' => ['logo', $types + ['image/svg+xml' => 'svg']], 'site_favicon' => ['favicon', $types + ['image/svg+xml' => 'svg', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico']], 'og_default_image' => ['share', $types]] as $key => [$pfx, $allowed]) {
            if (!empty($_POST['remove_' . $key])) { $save($key, ''); continue; }
            if (!empty($_FILES[$key]['name'])) {
                [$fn, $e] = mem_store_upload($_FILES[$key], $allowed, 4 * 1048576, $pfx, $dir);
                if ($e) $err = ucfirst($pfx) . ": " . $e; else $save($key, 'uploads/brand/' . $fn);
            }
        }
        if (!$err) $msg = "Appearance saved — it applies to every public page immediately.";
    }
    $GLOBALS['__reload'] = true;
    $cfgRows = $conn->query("SELECT setting_key, setting_value FROM settings"); $cfg2 = [];
    while ($r = $cfgRows->fetch_assoc()) $cfg2[$r['setting_key']] = $r['setting_value'];
    $cfg = array_merge($cfg, array_filter($cfg2, fn($v, $k) => in_array($k, ['brand_gold', 'brand_ink', 'site_logo', 'site_favicon', 'og_default_image', 'show_topbar', 'show_clock', 'clock_timezone', 'pwa_enabled', 'whatsapp_enabled', 'whatsapp_number', 'whatsapp_message']), ARRAY_FILTER_USE_BOTH));
}
$gold = $cfg['brand_gold']; $ink = $cfg['brand_ink'];
$img = fn($k) => $cfg[$k] !== '' && is_file(__DIR__ . '/../assets/images/' . $cfg[$k]);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="admin-form">
  <div class="admin-card">
    <div class="admin-card-head"><h2>Logo &amp; icons</h2><span class="admin-help">Shown in the header, footer, mobile menu and search/social previews</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Logo (PNG, SVG, WebP or JPG)</label>
        <div class="brand-preview" style="background:#111"><img src="../assets/images/<?php echo $img('site_logo') ? htmlspecialchars($cfg['site_logo']) : 'logo/nufa-logo.png'; ?>" alt="Current logo"></div>
        <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
        <?php if ($img('site_logo')): ?><label class="mem-check"><input type="checkbox" name="remove_site_logo" value="1"> Use the original NUFA logo again</label><?php endif; ?></div>
      <div class="field"><label>Browser-tab icon (favicon)</label>
        <?php if ($img('site_favicon')): ?><div class="brand-preview"><img src="../assets/images/<?php echo htmlspecialchars($cfg['site_favicon']); ?>" alt="Favicon" style="height:32px"></div><?php endif; ?>
        <input type="file" name="site_favicon" accept="image/png,image/svg+xml,image/x-icon,image/webp">
        <?php if ($img('site_favicon')): ?><label class="mem-check"><input type="checkbox" name="remove_site_favicon" value="1"> Use the default icon again</label><?php endif; ?></div>
      <div class="field"><label>Default social-share image (1200×630 works best)</label>
        <?php if ($img('og_default_image')): ?><div class="brand-preview"><img src="../assets/images/<?php echo htmlspecialchars($cfg['og_default_image']); ?>" alt="Share image" style="height:90px"></div><?php endif; ?>
        <input type="file" name="og_default_image" accept="image/png,image/jpeg,image/webp">
        <?php if ($img('og_default_image')): ?><label class="mem-check"><input type="checkbox" name="remove_og_default_image" value="1"> Use the built-in image again</label><?php endif; ?></div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Brand colours</h2><span class="admin-help">Lighter and darker shades are generated automatically. Keep good contrast between text and buttons.</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Accent colour (buttons, highlights)</label><div style="display:flex;gap:10px"><input type="color" name="brand_gold" value="<?php echo htmlspecialchars($gold); ?>" style="max-width:70px;padding:3px"><input value="<?php echo htmlspecialchars($gold); ?>" disabled style="max-width:110px"></div></div>
      <div class="field"><label>Dark colour (header, footer, dark sections)</label><div style="display:flex;gap:10px"><input type="color" name="brand_ink" value="<?php echo htmlspecialchars($ink); ?>" style="max-width:70px;padding:3px"><input value="<?php echo htmlspecialchars($ink); ?>" disabled style="max-width:110px"></div></div>
    </div>
    <div class="palette-preview"><span style="background:<?php echo htmlspecialchars($ink); ?>;color:<?php echo htmlspecialchars($gold); ?>">Dark section</span><span style="background:<?php echo htmlspecialchars($gold); ?>;color:<?php echo htmlspecialchars($ink); ?>">Button</span><span style="background:<?php echo nufa_shade($gold, 88); ?>;color:<?php echo nufa_shade($gold, -38); ?>">Soft highlight</span></div>
    <label class="mem-check" style="margin-top:18px"><input type="checkbox" name="show_topbar" value="1" <?php echo $cfg['show_topbar'] === '1' ? 'checked' : ''; ?>> Show the contact bar above the header (phone, email, address, social icons)</label>
    <label class="mem-check" style="margin-top:10px"><input type="checkbox" name="show_clock" value="1" <?php echo ($cfg['show_clock'] ?? '1') === '1' ? 'checked' : ''; ?>> Show a live clock (date and time) in the contact bar</label>
    <div class="field" style="max-width:340px;margin-top:8px"><label>Clock time zone</label><select name="clock_timezone"><?php $tzNow = $cfg['clock_timezone'] ?? 'Africa/Kampala'; foreach (['Africa/Kampala' => 'Uganda (EAT)', 'Africa/Nairobi' => 'Kenya (EAT)', 'Africa/Dar_es_Salaam' => 'Tanzania (EAT)', 'Africa/Kigali' => 'Rwanda (CAT)', 'Africa/Johannesburg' => 'South Africa (SAST)', 'Africa/Lagos' => 'Nigeria (WAT)', 'Europe/London' => 'London', 'UTC' => 'UTC'] as $tzk => $tzl) echo '<option value="' . $tzk . '"' . ($tzNow === $tzk ? ' selected' : '') . '>' . $tzl . '</option>'; ?></select></div>
    <label class="mem-check" style="margin-top:10px"><input type="checkbox" name="whatsapp_enabled" value="1" <?php echo ($cfg['whatsapp_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>> Show the WhatsApp chat button (floating button, contact bar and Contact page)</label>
    <div class="admin-form-grid"><div class="field"><label>WhatsApp number</label><input name="whatsapp_number" value="<?php echo htmlspecialchars($cfg['whatsapp_number'] ?? ''); ?>" placeholder="Leave blank to use the main telephone, e.g. +256 774 757 587"></div><div class="field"><label>Message pre-typed for visitors</label><input name="whatsapp_message" value="<?php echo htmlspecialchars($cfg['whatsapp_message'] ?? 'Hello NUFA, I would like to make an enquiry.'); ?>"></div></div>
    <label class="mem-check" style="margin-top:10px"><input type="checkbox" name="pwa_enabled" value="1" <?php echo ($cfg['pwa_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>> Let visitors install the website as an app (phones and computers) and keep working offline</label>
  </div>
  <button class="admin-btn admin-btn-brand">Save appearance</button>
</form>
<form method="post" style="margin-top:14px"><input type="hidden" name="action" value="reset_colours"><button class="admin-btn admin-btn-line admin-btn-sm">Restore default colours</button></form>
<p class="admin-help" style="margin-top:18px">Fonts are chosen under <a href="settings.php">Site Settings</a>. Hero photographs are managed under <a href="sliders.php">Hero Images</a>.</p>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
