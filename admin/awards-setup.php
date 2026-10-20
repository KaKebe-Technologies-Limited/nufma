<?php
$adminTitle = "Awards setup";
$adminNav = "awardsetup";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
require_once __DIR__ . "/../includes/awards.php";
awd_install($conn);

$kinds = awd_kinds();
$tabs = ['submissions' => 'Submission portal', 'tickets' => 'Tickets & booking', 'roadmap' => 'Road map', 'guest' => 'Guests', 'moment' => 'Key moments', 'impact' => 'Impact stories', 'film' => 'Highlight films'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'submissions';
$msg = ''; $err = '';
$e = fn($v) => htmlspecialchars((string)$v);
$save = function ($k, $v) use ($conn) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); };
$editions = $conn->query("SELECT id, slug, year, status FROM award_editions ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
$imgDir = __DIR__ . "/../assets/images/uploads/awards/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($act === 'settings') {
        $tab = $_POST['tab'] ?? 'submissions';
        $keys = $tab === 'tickets' ? ['awd_tix_status', 'awd_tix_tiers', 'awd_tix_url', 'awd_tix_howto', 'awd_tix_venue', 'awd_tix_notify'] : ['awd_sub_status', 'awd_sub_url', 'awd_sub_opens', 'awd_sub_deadline', 'awd_sub_fee', 'awd_sub_eligibility', 'awd_sub_guidelines', 'awd_sub_email'];
        $url = trim($_POST[$tab === 'tickets' ? 'awd_tix_url' : 'awd_sub_url'] ?? '');
        if ($url !== '' && !preg_match('#^https?://#i', $url)) $err = "The link must start with https://";
        else {
            foreach ($keys as $k) $save($k, mb_substr(trim($_POST[$k] ?? ''), 0, 4000));
            if ($tab === 'tickets') $save('awd_tix_booking_form', isset($_POST['awd_tix_booking_form']) ? '1' : '0');
            $msg = "Saved — the NUFA Awards page updates immediately.";
        }
    } elseif ($act === 'item_save' || $act === 'item_add') {
        $kind = isset($kinds[$_POST['kind'] ?? '']) ? $_POST['kind'] : 'guest'; $tab = $kind === 'roadmap' ? 'roadmap' : $kind;
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 255);
        if ($title === '') $err = "Give it a title.";
        else {
            $img = trim($_POST['image'] ?? '');
            if (!empty($_FILES['upload']['name'])) { [$fn, $er] = mem_store_upload($_FILES['upload'], MEM_IMG_TYPES, 6 * 1048576, 'award', $imgDir); if ($er) $err = "Image: $er"; else $img = 'uploads/awards/' . $fn; }
            $link = trim($_POST['link_url'] ?? '');
            if (!$err && $link !== '' && !preg_match('#^https?://#i', $link)) $err = "The link must start with https://";
            if (!$err) {
                $v = [(int)($_POST['edition_id'] ?? 0), $kind, $title, mb_substr(trim($_POST['subtitle'] ?? ''), 0, 255), mb_substr(trim($_POST['body'] ?? ''), 0, 5000), mb_substr($img, 0, 255), $link,
                      mb_substr(trim($_POST['date_label'] ?? ''), 0, 120), in_array($_POST['status'] ?? '', ['done', 'current', 'upcoming'], true) ? $_POST['status'] : 'upcoming', (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
                if ($act === 'item_add') { $s = $conn->prepare("INSERT INTO award_items (edition_id, kind, title, subtitle, body, image, link_url, date_label, status, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)"); $s->bind_param("issssssssii", ...$v); }
                else { $s = $conn->prepare("UPDATE award_items SET edition_id=?, kind=?, title=?, subtitle=?, body=?, image=?, link_url=?, date_label=?, status=?, sort_order=?, is_active=? WHERE id=?"); $v[] = $id; $s->bind_param("issssssssiii", ...$v); }
                $s->execute(); $msg = "Saved.";
            }
        }
    } elseif ($act === 'item_delete') { $r = $conn->query("SELECT kind FROM award_items WHERE id=$id")->fetch_assoc(); if ($r) $tab = $r['kind']; $conn->query("DELETE FROM award_items WHERE id=$id"); $msg = "Deleted."; }
}
$S = awd_settings($conn, true);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><?php foreach ($tabs as $t => $l): ?><a href="awards-setup.php?tab=<?php echo $t; ?>" class="<?php echo $tab === $t ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?>
  <a href="award-editions.php">Editions ↗</a><a href="award-winners.php">Winners ↗</a><a href="team.php">Technical team ↗</a><a href="page-editor.php?page=awards">Page text &amp; sections ↗</a><a href="../awards.php" target="_blank">View page ↗</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'submissions'): ?>
  <form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="settings"><input type="hidden" name="tab" value="submissions">
    <div class="admin-card-head"><h2>Submission portal</h2><span class="admin-help">Controls the “Submit your film” section on the NUFA Awards page. Only publish what has been officially confirmed.</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Status</label><select name="awd_sub_status"><?php foreach (['soon' => 'Opening soon (details to come)', 'open' => 'Open for submissions', 'closed' => 'Closed'] as $k => $l) echo '<option value="' . $k . '"' . ($S['awd_sub_status'] === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
      <div class="field"><label>Portal link (shown as the Submit button when Open)</label><input type="url" name="awd_sub_url" value="<?php echo $e($S['awd_sub_url']); ?>" placeholder="https://"></div>
      <div class="field"><label>Submissions open on</label><input name="awd_sub_opens" value="<?php echo $e($S['awd_sub_opens']); ?>" placeholder="e.g. 1 March 2027"></div>
      <div class="field"><label>Deadline</label><input name="awd_sub_deadline" value="<?php echo $e($S['awd_sub_deadline']); ?>" placeholder="e.g. 30 June 2027"></div>
      <div class="field"><label>Entry fee</label><input name="awd_sub_fee" value="<?php echo $e($S['awd_sub_fee']); ?>" placeholder="e.g. Free, or UGX 20,000"></div>
      <div class="field"><label>Help email for submitters</label><input type="email" name="awd_sub_email" value="<?php echo $e($S['awd_sub_email']); ?>"></div>
    </div>
    <div class="field"><label>Who can submit (eligibility)</label><textarea name="awd_sub_eligibility" style="min-height:90px"><?php echo $e($S['awd_sub_eligibility']); ?></textarea></div>
    <div class="field"><label>Submission guidelines (format, length, language, what to upload — one point per line)</label><textarea name="awd_sub_guidelines" style="min-height:110px"><?php echo $e($S['awd_sub_guidelines']); ?></textarea></div>
    <button class="admin-btn admin-btn-brand">Save</button>
  </form>

<?php elseif ($tab === 'tickets'): ?>
  <form method="post" class="admin-card admin-form"><input type="hidden" name="action" value="settings"><input type="hidden" name="tab" value="tickets">
    <div class="admin-card-head"><h2>Tickets &amp; booking</h2><span class="admin-help">Controls the “Tickets and booking” section on the NUFA Awards page.</span></div>
    <div class="admin-form-grid">
      <div class="field"><label>Sales status</label><select name="awd_tix_status"><?php foreach (['soon' => 'Tickets coming soon', 'on_sale' => 'On sale', 'sold_out' => 'Sold out', 'closed' => 'Sales closed'] as $k => $l) echo '<option value="' . $k . '"' . ($S['awd_tix_status'] === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
      <div class="field"><label>Online purchase link (optional)</label><input type="url" name="awd_tix_url" value="<?php echo $e($S['awd_tix_url']); ?>" placeholder="https://"></div>
      <div class="field"><label>Venue &amp; time for the ticket section</label><input name="awd_tix_venue" value="<?php echo $e($S['awd_tix_venue']); ?>" placeholder="Defaults to the upcoming edition venue"></div>
      <div class="field"><label>Send booking requests to (email)</label><input type="email" name="awd_tix_notify" value="<?php echo $e($S['awd_tix_notify']); ?>"></div>
    </div>
    <div class="field"><label>Ticket types — one per line: <code>Name | Price | Notes</code></label><textarea name="awd_tix_tiers" style="min-height:100px" placeholder="General admission | UGX 30,000 | Includes entry to the ceremony"><?php echo $e($S['awd_tix_tiers']); ?></textarea></div>
    <div class="field"><label>How to book and pay (shown to visitors)</label><textarea name="awd_tix_howto" style="min-height:100px"><?php echo $e($S['awd_tix_howto']); ?></textarea></div>
    <label class="mem-check"><input type="checkbox" name="awd_tix_booking_form" value="1" <?php echo $S['awd_tix_booking_form'] === '1' ? 'checked' : ''; ?>> Show the “Request tickets” form (requests arrive under Enquiries → Tickets)</label>
    <button class="admin-btn admin-btn-brand">Save</button>
  </form>

<?php else:
    $K = $kinds[$tab]; $isRoad = $tab === 'roadmap';
    $items = $conn->query("SELECT i.*, e.year FROM award_items i LEFT JOIN award_editions e ON e.id=i.edition_id WHERE i.kind='" . $conn->real_escape_string($tab) . "' ORDER BY i.edition_id DESC, i.sort_order, i.id")->fetch_all(MYSQLI_ASSOC);
    $upId = 0; foreach ($editions as $ed) if ($ed['status'] === 'upcoming') $upId = $ed['id'];
    $form = function ($it, $add = false) use ($K, $tab, $editions, $isRoad, $upId, $e) { ob_start(); $it += ['edition_id' => $isRoad ? $upId : ($editions[1]['id'] ?? ($editions[0]['id'] ?? 0)), 'status' => 'upcoming', 'is_active' => 1, 'sort_order' => 0, 'id' => 0]; ?>
    <form method="post" enctype="multipart/form-data" class="admin-card admin-form">
      <input type="hidden" name="action" value="<?php echo $add ? 'item_add' : 'item_save'; ?>"><input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>"><input type="hidden" name="kind" value="<?php echo $tab; ?>">
      <div class="admin-form-grid">
        <div class="field"><label><?php echo $e($K[2]); ?></label><input name="title" value="<?php echo $e($it['title'] ?? ''); ?>" required></div>
        <div class="field"><label>Edition</label><select name="edition_id"><?php if ($isRoad) echo '<option value="0">Next edition (general)</option>'; foreach ($editions as $ed) echo '<option value="' . $ed['id'] . '"' . ((int)$it['edition_id'] === (int)$ed['id'] ? ' selected' : '') . '>NUFA Awards ' . (int)$ed['year'] . '</option>'; ?></select></div>
        <div class="field"><label><?php echo $e($K[3]); ?></label><input name="subtitle" value="<?php echo $e($it['subtitle'] ?? ''); ?>"></div>
        <?php if ($isRoad): ?><div class="field"><label>Timing</label><input name="date_label" value="<?php echo $e($it['date_label'] ?? ''); ?>" placeholder="e.g. March 2027, or To be announced"></div>
        <div class="field"><label>Progress</label><select name="status"><?php foreach (['done' => 'Done', 'current' => 'Happening now', 'upcoming' => 'Coming up'] as $k => $l) echo '<option value="' . $k . '"' . ($it['status'] === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
        <?php elseif ($tab === 'moment'): ?><div class="field"><label>When (optional)</label><input name="date_label" value="<?php echo $e($it['date_label'] ?? ''); ?>"></div><?php endif; ?>
        <?php if ($tab === 'film' || $tab === 'impact' || $isRoad): ?><div class="field"><label><?php echo $tab === 'film' ? 'Trailer / watch link' : 'Read more link'; ?> (optional)</label><input type="url" name="link_url" value="<?php echo $e($it['link_url'] ?? ''); ?>" placeholder="https://"></div><?php endif; ?>
        <div class="field"><label>Order</label><input type="number" name="sort_order" value="<?php echo (int)$it['sort_order']; ?>"></div>
      </div>
      <div class="field"><label><?php echo $tab === 'guest' ? 'About / role at the awards' : ($tab === 'film' ? 'Synopsis' : 'Details'); ?></label><textarea name="body" style="min-height:80px"><?php echo $e($it['body'] ?? ''); ?></textarea></div>
      <?php if (!$isRoad): ?><div class="admin-form-grid"><div class="field"><label>Picture (upload)</label><input type="file" name="upload" accept="image/*"></div><div class="field"><label>…or existing image path</label><input name="image" value="<?php echo $e($it['image'] ?? ''); ?>" placeholder="e.g. gallery/2026/nufa26-04.jpg"></div></div><?php endif; ?>
      <div style="display:flex;gap:14px;align-items:center"><label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo $it['is_active'] ? 'checked' : ''; ?>> Show on the page</label>
        <button class="admin-btn admin-btn-brand admin-btn-sm"><?php echo $add ? 'Add' : 'Save'; ?></button>
        <?php if (!$add): ?><button name="action" value="item_delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this?')">Delete</button><?php endif; ?></div>
    </form><?php return ob_get_clean(); }; ?>
  <p class="admin-help" style="margin-bottom:12px"><?php
    echo ['roadmap' => 'The timeline shown under “Road map to the next edition”. Placeholder steps were added for you — set the real timing and edit or delete them as needed.',
          'guest' => 'Guests and dignitaries who joined an edition. Pick the edition each belongs to.', 'moment' => 'Key moments from an edition, with a picture where possible.',
          'impact' => 'Verified stories of what the awards have meant for filmmakers and communities.', 'film' => 'Films to highlight from an edition, with synopsis and trailer link.'][$tab]; ?></p>
  <h3 class="mem-h3" style="margin-top:0">Add <?php echo strtolower($K[0]); ?></h3><?php echo $form([], true); ?>
  <?php if ($items): ?><h3 class="mem-h3"><?php echo $e($K[1]); ?> (<?php echo count($items); ?>)</h3><?php endif; foreach ($items as $it) echo $form($it); ?>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
