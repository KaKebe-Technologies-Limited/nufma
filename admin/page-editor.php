<?php
$adminNav = "pages";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/sections.php";
require_once __DIR__ . "/../includes/content.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()

$registry = nufa_registry();
$key = $_GET['page'] ?? '';
if (!isset($registry[$key])) { header("Location: pages.php"); exit; }
$pg = $registry[$key];
$tab = in_array($_GET['tab'] ?? '', ['content', 'sections', 'functions', 'seo', 'data'], true) ? $_GET['tab'] : 'content';
$adminTitle = "Page Editor — " . $pg['label'];
$schemaKey = $pg['schema'];
$schema = $CMS_SCHEMAS[$schemaKey]['fields'] ?? [];
$errors = []; $saved = isset($_GET['saved']);

$save = function ($k, $v) use ($conn) {
    $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $s->bind_param("ss", $k, $v); $s->execute();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'content') {
        $errors = cms_save($conn, $schemaKey, $schema, $_POST, $_FILES);
        if (!$errors) { header("Location: page-editor.php?page=$key&tab=content&saved=1"); exit; }
    } elseif ($act === 'reset_content') {
        $d = $conn->prepare("DELETE FROM page_content WHERE page=?"); $d->bind_param("s", $schemaKey); $d->execute();
        header("Location: page-editor.php?page=$key&tab=content&saved=1"); exit;
    } elseif ($act === 'sections') {
        foreach ($pg['sections'] as $sec) { if (($sec[2] ?? true) === false) continue; $save("show_{$key}_{$sec[0]}", isset($_POST['show_' . $sec[0]]) ? '1' : '0'); }
        header("Location: page-editor.php?page=$key&tab=sections&saved=1"); exit;
    } elseif ($act === 'functions') {
        foreach ($pg['functions'] as [$fk, $flabel, $ftype, $fdef]) {
            if ($ftype === 'switch') $v = isset($_POST["fn_$fk"]) ? '1' : '0';
            elseif ($ftype === 'number') $v = (string)max(0, (int)($_POST["fn_$fk"] ?? $fdef));
            else $v = trim($_POST["fn_$fk"] ?? '');
            $save("fn_{$key}_{$fk}", $v);
        }
        if (!empty($pg['form'])) {
            $save("form_{$key}_enabled", isset($_POST['form_enabled']) ? '1' : '0');
            foreach (['intro', 'button', 'success', 'closed'] as $f) $save("form_{$key}_$f", mb_substr(trim($_POST["form_$f"] ?? ''), 0, 600));
            $n = trim($_POST['form_notify'] ?? '');
            if ($n !== '' && !filter_var($n, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid notification email address (or leave it blank to use the default).";
            else $save("form_{$key}_notify", $n);
        }
        if (!$errors) { header("Location: page-editor.php?page=$key&tab=functions&saved=1"); exit; }
    } elseif ($act === 'seo' && $pg['seo']) {
        $seoSchema = array_intersect_key($CMS_SCHEMAS['seo']['fields'], [$pg['seo'] . '_title' => 1, $pg['seo'] . '_desc' => 1]);
        $errors = cms_save($conn, 'seo', $seoSchema, $_POST, []);
        $save("noindex_$key", isset($_POST['noindex']) ? '1' : '0');
        if (!empty($_POST['remove_seo_image'])) $save("seo_image_$key", '');
        if (!empty($_FILES['seo_image']['name'])) {
            [$fn, $e] = mem_store_upload($_FILES['seo_image'], MEM_IMG_TYPES, 4 * 1048576, 'share', __DIR__ . "/../assets/images/uploads/seo/");
            if ($e) $errors[] = "Share image: " . $e; else $save("seo_image_$key", 'uploads/seo/' . $fn);
        }
        if (!$errors) { header("Location: page-editor.php?page=$key&tab=seo&saved=1"); exit; }
    }
}

$cfg = nufa_site_settings($conn);
$cur = [];
$r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'show\\_%' OR setting_key LIKE 'fn\\_%' OR setting_key LIKE 'form\\_%' OR setting_key LIKE 'noindex\\_%' OR setting_key LIKE 'seo\\_image\\_%'");
while ($x = $r->fetch_assoc()) $cur[$x['setting_key']] = $x['setting_value'];

include __DIR__ . "/includes/admin-header.php";
$tabs = ['content' => 'Text & images', 'sections' => 'Sections', 'functions' => 'Functions', 'seo' => 'SEO & sharing', 'data' => 'Data on this page'];
if (empty($pg['sections'])) unset($tabs['sections']);
if (empty($pg['functions']) && empty($pg['form'])) unset($tabs['functions']);
if (!$pg['seo']) unset($tabs['seo']);
$viewUrl = '../' . ($key === 'policies' ? 'policy.php?p=privacy' : $pg['file']);
?>
<div class="pe-head">
  <div><a href="pages.php" class="admin-help">&larr; All pages</a><h2><?php echo htmlspecialchars($pg['label']); ?></h2></div>
  <?php if (empty($pg['virtual'])): ?><a href="<?php echo htmlspecialchars($viewUrl); ?>" target="_blank" class="admin-btn admin-btn-line">View live page</a><?php endif; ?>
</div>
<div class="mem-tabs">
  <?php foreach ($tabs as $t => $label): ?><a href="page-editor.php?page=<?php echo $key; ?>&tab=<?php echo $t; ?>" class="<?php echo $tab === $t ? 'active' : ''; ?>"><?php echo $label; ?></a><?php endforeach; ?>
</div>
<?php if ($saved): ?><div class="admin-alert ok">Saved — the page updates immediately.</div><?php endif; ?>
<?php if ($errors): ?><div class="admin-alert err"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div><?php endif; ?>
<?php if (!empty($pg['note'])): ?><div class="notice-inline"><?php echo htmlspecialchars($pg['note']); ?></div><?php endif; ?>

<?php if ($tab === 'content'):
    $values = cms_load($conn, $schemaKey, $schema);
    $groups = [];
    foreach ($schema as $fk => $def) { $g = strpos($def['label'], ' — ') !== false ? explode(' — ', $def['label'], 2)[0] : 'General'; $groups[$g][$fk] = $def; }
?>
  <?php if (!$schema): ?><div class="admin-card"><p class="admin-help">This page has no editable text fields.</p></div><?php else: ?>
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="action" value="content">
    <p class="admin-help" style="margin-bottom:14px">Change any text or image below. Leave a field unchanged to keep what is live; clear it to restore the original wording. <?php echo count($schema); ?> editable items, grouped by area of the page.</p>
    <?php foreach ($groups as $gname => $fields): ?>
    <details class="pe-group" <?php echo array_key_first($groups) === $gname ? 'open' : ''; ?>>
      <summary><?php echo htmlspecialchars($gname); ?> <span class="admin-badge gray"><?php echo count($fields); ?></span></summary>
      <div class="pe-group-body">
      <?php foreach ($fields as $fk => $def): $type = $def['type'] ?? 'text'; $val = $values[$fk] ?? ''; $label = strpos($def['label'], ' — ') !== false ? explode(' — ', $def['label'], 2)[1] : $def['label']; ?>
        <div class="field">
          <label><?php echo htmlspecialchars($label); ?></label>
          <?php if ($type === 'text'): ?>
            <input type="text" name="<?php echo htmlspecialchars($fk); ?>" value="<?php echo htmlspecialchars($val); ?>">
          <?php elseif ($type === 'textarea'): ?>
            <textarea name="<?php echo htmlspecialchars($fk); ?>" style="min-height:90px"><?php echo htmlspecialchars($val); ?></textarea>
          <?php elseif ($type === 'html'): ?>
            <textarea name="<?php echo htmlspecialchars($fk); ?>" style="min-height:140px;font-family:monospace;font-size:.84rem"><?php echo htmlspecialchars($val); ?></textarea><span class="admin-help">HTML allowed (&lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;, &lt;a&gt; …)</span>
          <?php elseif ($type === 'image'): $exists = $val && is_file(__DIR__ . "/../assets/images/" . $val); ?>
            <div class="pe-image"><?php if ($exists): ?><img src="../assets/images/<?php echo htmlspecialchars($val); ?>" alt=""><?php endif; ?><div><input type="file" name="<?php echo htmlspecialchars($fk); ?>" accept="image/jpeg,image/png,image/webp"><span class="admin-help">Current: <?php echo htmlspecialchars($val); ?> — upload to replace.</span></div></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      </div>
    </details>
    <?php endforeach; ?>
    <button class="admin-btn admin-btn-brand">Save page content</button>
  </form>
  <form method="post" style="margin-top:14px" onsubmit="return confirm('Restore ALL text and images on this page to the original wording?')"><input type="hidden" name="action" value="reset_content"><button class="admin-btn admin-btn-line admin-btn-sm">Restore original wording for this page</button></form>
  <?php endif; ?>

<?php elseif ($tab === 'sections'): ?>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="sections">
    <div class="admin-card">
      <div class="admin-card-head"><h2>Sections on this page</h2><span class="admin-help">Untick a section to hide it from visitors. Listed in the order they appear on the page.</span></div>
      <?php foreach ($pg['sections'] as $i => $sec): $locked = ($sec[2] ?? true) === false; $on = ($cur["show_{$key}_{$sec[0]}"] ?? '1') !== '0'; ?>
        <label class="pe-section"><span class="pe-num"><?php echo $i + 1; ?></span>
          <input type="checkbox" name="show_<?php echo $sec[0]; ?>" value="1" <?php echo ($on || $locked) ? 'checked' : ''; ?> <?php echo $locked ? 'disabled' : ''; ?>>
          <span><b><?php echo htmlspecialchars($sec[1]); ?></b><?php echo $locked ? ' <span class="admin-help">(always shown)</span>' : ''; ?></span>
          <span class="admin-badge <?php echo ($on || $locked) ? '' : 'gray'; ?>"><?php echo ($on || $locked) ? 'Visible' : 'Hidden'; ?></span></label>
      <?php endforeach; ?>
    </div>
    <button class="admin-btn admin-btn-brand">Save sections</button>
  </form>

<?php elseif ($tab === 'functions'): $fc = !empty($pg['form']) ? nufa_form_cfg($key) : null; ?>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="functions">
    <?php if ($pg['functions']): ?>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Page behaviour</h2></div>
      <?php foreach ($pg['functions'] as [$fk, $flabel, $ftype, $fdef, $fhelp]): $fv = $cur["fn_{$key}_{$fk}"] ?? $fdef; ?>
        <?php if ($ftype === 'switch'): ?><label class="mem-check"><input type="checkbox" name="fn_<?php echo $fk; ?>" value="1" <?php echo $fv !== '0' ? 'checked' : ''; ?>> <?php echo htmlspecialchars($flabel); ?></label>
        <?php else: ?><div class="field" style="max-width:320px"><label><?php echo htmlspecialchars($flabel); ?></label><input type="<?php echo $ftype === 'number' ? 'number' : 'text'; ?>" name="fn_<?php echo $fk; ?>" value="<?php echo htmlspecialchars($fv); ?>"><?php if ($fhelp): ?><span class="admin-help"><?php echo htmlspecialchars($fhelp); ?></span><?php endif; ?></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($fc): ?>
    <div class="admin-card">
      <div class="admin-card-head"><h2><?php echo htmlspecialchars($pg['form_label'] ?? 'Form'); ?></h2><span class="admin-help">Spam protection (CSRF, honeypot, time check, rate limit) is always on.</span></div>
      <label class="mem-check"><input type="checkbox" name="form_enabled" value="1" <?php echo $fc['enabled'] ? 'checked' : ''; ?>> Accept submissions (untick to show a “closed” message instead of the form)</label>
      <div class="admin-form-grid">
        <div class="field"><label>Button label</label><input name="form_button" value="<?php echo htmlspecialchars($fc['button']); ?>"></div>
        <div class="field"><label>Send notifications to (blank = default inbox)</label><input type="email" name="form_notify" value="<?php echo htmlspecialchars($fc['notify']); ?>" placeholder="<?php echo htmlspecialchars($cfg['site_email']); ?>"></div>
      </div>
      <div class="field"><label>Short intro above the form (optional)</label><textarea name="form_intro" style="min-height:70px"><?php echo htmlspecialchars($fc['intro']); ?></textarea></div>
      <div class="field"><label>Message shown after a successful submission</label><textarea name="form_success" style="min-height:70px"><?php echo htmlspecialchars($fc['success']); ?></textarea></div>
      <div class="field"><label>Message shown when the form is switched off</label><textarea name="form_closed" style="min-height:70px"><?php echo htmlspecialchars($fc['closed']); ?></textarea></div>
      <p class="admin-help">Form field choices (topics, roles, organisation types …) are edited under <a href="lists.php">Form options</a>. Received submissions are under <a href="enquiries.php">Enquiries</a>.</p>
    </div>
    <?php endif; ?>
    <button class="admin-btn admin-btn-brand">Save functions</button>
  </form>

<?php elseif ($tab === 'seo'):
    $tk = $pg['seo'] . '_title'; $dk = $pg['seo'] . '_desc';
    $titleV = cms_value('seo', $tk); $descV = cms_value('seo', $dk);
    $img = $cur["seo_image_$key"] ?? ''; $imgOk = $img !== '' && is_file(__DIR__ . '/../assets/images/' . $img);
?>
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="action" value="seo">
    <div class="admin-card">
      <div class="admin-card-head"><h2>Search engines &amp; social sharing</h2><span class="admin-help">How this page appears in Google and when shared on WhatsApp, Facebook or X.</span></div>
      <div class="field"><label>Page title <span class="admin-help">(aim for under 60 characters)</span></label><input name="<?php echo $tk; ?>" value="<?php echo htmlspecialchars($titleV); ?>" maxlength="120" id="seoT"></div>
      <div class="field"><label>Description <span class="admin-help">(about 155 characters)</span></label><textarea name="<?php echo $dk; ?>" style="min-height:80px" maxlength="320" id="seoD"><?php echo htmlspecialchars($descV); ?></textarea></div>
      <div class="serp"><div class="serp-url"><?php echo htmlspecialchars(($pg['virtual'] ?? false) ? '' : 'yourdomain.com › ' . $pg['file']); ?></div><div class="serp-title" id="serpT"><?php echo htmlspecialchars($titleV); ?></div><div class="serp-desc" id="serpD"><?php echo htmlspecialchars($descV); ?></div></div>
      <div class="field" style="margin-top:20px"><label>Share image for this page (1200×630 works best)</label>
        <?php if ($imgOk): ?><div class="brand-preview"><img src="../assets/images/<?php echo htmlspecialchars($img); ?>" alt="" style="height:90px"></div><?php endif; ?>
        <input type="file" name="seo_image" accept="image/jpeg,image/png,image/webp"><span class="admin-help">Leave empty to use the site-wide image from Appearance &amp; Branding.</span>
        <?php if ($imgOk): ?><label class="mem-check"><input type="checkbox" name="remove_seo_image" value="1"> Remove this page's image</label><?php endif; ?></div>
      <label class="mem-check"><input type="checkbox" name="noindex" value="1" <?php echo ($cur["noindex_$key"] ?? '0') === '1' ? 'checked' : ''; ?>> Ask search engines <b>not</b> to list this page</label>
    </div>
    <button class="admin-btn admin-btn-brand">Save SEO</button>
  </form>
  <script>
  (function(){var t=document.getElementById('seoT'),d=document.getElementById('seoD'),st=document.getElementById('serpT'),sd=document.getElementById('serpD');
  if(t)t.addEventListener('input',function(){st.textContent=t.value}); if(d)d.addEventListener('input',function(){sd.textContent=d.value});})();
  </script>

<?php else: ?>
  <div class="admin-card">
    <div class="admin-card-head"><h2>Data that feeds this page</h2><span class="admin-help">These lists are edited in their own screens.</span></div>
    <table class="admin-table"><tbody>
      <?php foreach ($pg['related'] as [$label, $link]): ?><tr><td><?php echo htmlspecialchars($label); ?></td><td style="text-align:right"><a class="admin-btn admin-btn-brand admin-btn-sm" href="<?php echo htmlspecialchars($link); ?>">Open</a></td></tr><?php endforeach; ?>
      <?php if (!$pg['related']): ?><tr><td class="admin-help">Nothing else feeds this page.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
