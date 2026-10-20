<?php
$adminNav = "programmes";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$blank = ['title' => '', 'slug' => '', 'status' => '', 'summary' => '', 'overview' => '', 'context' => '', 'objectives' => '', 'beneficiaries' => '', 'activities' => '', 'outputs' => '',
          'geography' => '', 'partners_text' => '', 'progress' => '', 'support_text' => '', 'allow_donate' => 1, 'allow_sponsor' => 1, 'allow_volunteer' => 1, 'allow_partner' => 1, 'image' => '', 'sort_order' => 0, 'is_active' => 1];
$p = $blank;
if ($id) {
    $r = $conn->query("SELECT * FROM programmes WHERE id=$id")->fetch_assoc();
    if (!$r) { header("Location: programmes.php"); exit; }
    $p = array_merge($blank, $r);
}
$adminTitle = $id ? "Edit Programme" : "New Programme";
$statuses = ['' => 'No status shown (not yet confirmed)', 'planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'under_development' => 'Under Development'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = fn($k, $max = 20000) => mb_substr(trim($_POST[$k] ?? ''), 0, $max);
    $title = $in('title', 255);
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $in('slug', 120) ?: $title), '-'));
    $status = isset($statuses[$_POST['status'] ?? '']) ? $_POST['status'] : '';
    $vals = ['summary' => $in('summary', 500), 'overview' => $in('overview'), 'context' => $in('context'), 'objectives' => $in('objectives'), 'beneficiaries' => $in('beneficiaries'),
             'activities' => $in('activities'), 'outputs' => $in('outputs'), 'geography' => $in('geography', 255), 'partners_text' => $in('partners_text'), 'progress' => $in('progress'), 'support_text' => $in('support_text')];
    $allow = []; foreach (['donate', 'sponsor', 'volunteer', 'partner'] as $k) $allow[$k] = isset($_POST["allow_$k"]) ? 1 : 0;
    $sort = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;
    $image = $p['image'];

    if (!empty($_FILES['image']['name'])) {
        [$fn, $e] = mem_store_upload($_FILES['image'], MEM_IMG_TYPES, 6 * 1048576, 'programme', __DIR__ . "/../assets/images/uploads/programmes/");
        if ($e) $error = "Image: " . $e; else $image = "uploads/programmes/" . $fn;
    }
    if (!$error && ($title === '' || $slug === '')) $error = "A title is required.";
    if (!$error) {
        $dup = $conn->prepare("SELECT id FROM programmes WHERE slug=? AND id<>?"); $dup->bind_param("si", $slug, $id); $dup->execute();
        if ($dup->get_result()->num_rows) $error = "Another programme already uses the address “$slug”.";
    }
    if (!$error) {
        if ($id) {
            $s = $conn->prepare("UPDATE programmes SET slug=?, title=?, status=?, summary=?, overview=?, context=?, objectives=?, beneficiaries=?, activities=?, outputs=?, geography=?, partners_text=?, progress=?, support_text=?, allow_donate=?, allow_sponsor=?, allow_volunteer=?, allow_partner=?, image=?, sort_order=?, is_active=? WHERE id=?");
            $s->bind_param("ssssssssssssssiiiisiii", $slug, $title, $status, $vals['summary'], $vals['overview'], $vals['context'], $vals['objectives'], $vals['beneficiaries'], $vals['activities'], $vals['outputs'], $vals['geography'], $vals['partners_text'], $vals['progress'], $vals['support_text'], $allow['donate'], $allow['sponsor'], $allow['volunteer'], $allow['partner'], $image, $sort, $active, $id);
        } else {
            $s = $conn->prepare("INSERT INTO programmes (slug, title, status, summary, overview, context, objectives, beneficiaries, activities, outputs, geography, partners_text, progress, support_text, allow_donate, allow_sponsor, allow_volunteer, allow_partner, image, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("ssssssssssssssiiiisii", $slug, $title, $status, $vals['summary'], $vals['overview'], $vals['context'], $vals['objectives'], $vals['beneficiaries'], $vals['activities'], $vals['outputs'], $vals['geography'], $vals['partners_text'], $vals['progress'], $vals['support_text'], $allow['donate'], $allow['sponsor'], $allow['volunteer'], $allow['partner'], $image, $sort, $active);
        }
        if ($s->execute()) { header("Location: programmes.php?saved=1"); exit; }
        $error = "Save failed: " . $conn->error;
    }
    $p = array_merge($p, $vals, ['allow_donate' => $allow['donate'], 'allow_sponsor' => $allow['sponsor'], 'allow_volunteer' => $allow['volunteer'], 'allow_partner' => $allow['partner'], 'title' => $title, 'slug' => $slug, 'status' => $status, 'sort_order' => $sort, 'is_active' => $active, 'image' => $image]);
}
$e = fn($k) => htmlspecialchars((string)$p[$k]);
include __DIR__ . "/includes/admin-header.php";
$ta = fn($k, $label, $help = '', $h = 90) => '<div class="field"><label>' . $label . '</label><textarea name="' . $k . '" style="min-height:' . $h . 'px">' . $e($k) . '</textarea>' . ($help ? '<span class="admin-help">' . $help . '</span>' : '') . '</div>';
?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="admin-form">
  <div class="admin-card">
    <div class="admin-card-head"><h2>Basics</h2></div>
    <div class="admin-form-grid">
      <div class="field"><label>Programme title</label><input name="title" value="<?php echo $e('title'); ?>" required></div>
      <div class="field"><label>Page address (slug)</label><input name="slug" value="<?php echo $e('slug'); ?>" placeholder="auto-generated from the title"><span class="admin-help">Used in the URL: programme.php?slug=…</span></div>
      <div class="field"><label>Status</label><select name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo $p['status'] === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option><?php endforeach; ?></select>
        <span class="admin-help">Only set a status that has been confirmed — never imply a planned programme is already running.</span></div>
      <div class="field"><label>Display order</label><input type="number" name="sort_order" value="<?php echo (int)$p['sort_order']; ?>"></div>
    </div>
    <div class="field"><label>Short summary (shown on cards)</label><textarea name="summary" style="min-height:70px" maxlength="500"><?php echo $e('summary'); ?></textarea></div>
    <div class="field"><label>Image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp">
      <?php if ($p['image']): ?><p class="admin-help">Current: <?php echo $e('image'); ?></p><?php endif; ?></div>
    <label class="mem-check"><input type="checkbox" name="is_active" value="1" <?php echo $p['is_active'] ? 'checked' : ''; ?>> Show on the website</label>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Programme detail page</h2><span class="admin-help">Empty sections are hidden. Use one line per item in list fields.</span></div>
    <?php echo $ta('overview', 'Overview'), $ta('context', 'Context and need'),
        $ta('objectives', 'Objectives', 'One per line.'), $ta('beneficiaries', 'Intended beneficiaries', 'One per line.'),
        $ta('activities', 'Key activities', 'One per line. Label as planned/ongoing/completed only when verified.', 140),
        $ta('outputs', 'Expected outputs and outcomes', 'One per line.'); ?>
    <div class="field"><label>Geographic focus</label><input name="geography" value="<?php echo $e('geography'); ?>" placeholder="e.g. Northern Uganda"></div>
    <?php echo $ta('partners_text', 'Partners and supporters', 'Only verified partners.'), $ta('progress', 'Progress updates or reports'); ?>
  </div>

  <div class="admin-card">
    <div class="admin-card-head"><h2>Supporting this programme</h2><span class="admin-help">Controls the “Get involved” section on the programme page and the support form.</span></div>
    <?php echo $ta('support_text', 'How support helps (what a gift or sponsorship makes possible)', 'Shown on the programme page and beside the support form. Do not promise outcomes or figures NUFA has not confirmed.', 110); ?>
    <p class="admin-help" style="margin-bottom:10px">Which ways of supporting are open for this programme:</p>
    <?php foreach (['donate' => 'Donate', 'sponsor' => 'Sponsor', 'volunteer' => 'Volunteer', 'partner' => 'Partner'] as $k => $l): ?><label class="mem-check"><input type="checkbox" name="allow_<?php echo $k; ?>" value="1" <?php echo !empty($p["allow_$k"]) ? 'checked' : ''; ?>> <?php echo $l; ?></label><?php endforeach; ?>
    <p class="admin-help">Payment methods, currencies, sponsorship levels and email wording are set under <a href="support-setup.php">Payment &amp; giving setup</a>. Requests arrive under <a href="support-requests.php">Support requests</a>.</p>
  </div>
  <button class="admin-btn admin-btn-brand">Save programme</button>
  <a href="programmes.php" class="admin-btn admin-btn-line">Cancel</a>
  <?php if ($id): ?><a href="../programme.php?slug=<?php echo urlencode($p['slug']); ?>" target="_blank" class="admin-btn admin-btn-line">View on site</a><?php endif; ?>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
