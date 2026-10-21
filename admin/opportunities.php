<?php
$adminTitle = "Opportunities";
$adminNav = "opps";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/opportunities.php";
opp_install($conn);
$cats = opp_categories(); $sts = opp_statuses();

if (isset($_GET['delete'])) { $conn->query("DELETE FROM opportunities WHERE id=" . (int)$_GET['delete']); header("Location: opportunities.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE opportunities SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: opportunities.php"); exit; }
if (isset($_GET['star'])) { $conn->query("UPDATE opportunities SET is_featured = 1 - is_featured WHERE id=" . (int)$_GET['star']); header("Location: opportunities.php"); exit; }
if (isset($_GET['verify'])) { $conn->query("UPDATE opportunities SET verified_on=CURDATE() WHERE id=" . (int)$_GET['verify']); header("Location: opportunities.php?verified=1"); exit; }

$error = ''; $id = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $cat = array_key_exists($_POST['category'] ?? '', $cats) ? $_POST['category'] : 'other';
    $st = array_key_exists($_POST['status'] ?? '', $sts) ? $_POST['status'] : 'check';
    $title = trim($_POST['title'] ?? ''); $org = trim($_POST['organisation'] ?? ''); $reg = trim($_POST['region'] ?? '');
    $sum = trim($_POST['summary'] ?? ''); $eli = trim($_POST['eligibility'] ?? ''); $fund = trim($_POST['funding'] ?? '');
    $dl = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['deadline'] ?? '') ? $_POST['deadline'] : null; $note = trim($_POST['deadline_note'] ?? '');
    $url = trim($_POST['apply_url'] ?? ''); $src = trim($_POST['source_url'] ?? '');
    $ver = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['verified_on'] ?? '') ? $_POST['verified_on'] : null;
    $feat = isset($_POST['is_featured']) ? 1 : 0; $act = isset($_POST['is_active']) ? 1 : 0;
    if ($title === '') $error = "Enter a title.";
    elseif ($url !== '' && !preg_match('#^https?://#i', $url)) $error = "The apply link must start with https://";
    elseif ($src !== '' && !preg_match('#^https?://#i', $src)) $error = "The source link must start with https://";
    else {
        if ($id) {
            $s = $conn->prepare("UPDATE opportunities SET category=?,title=?,organisation=?,region=?,summary=?,eligibility=?,funding=?,status=?,deadline=?,deadline_note=?,apply_url=?,source_url=?,verified_on=?,is_featured=?,is_active=? WHERE id=?");
            $s->bind_param("sssssssssssssiii", $cat, $title, $org, $reg, $sum, $eli, $fund, $st, $dl, $note, $url, $src, $ver, $feat, $act, $id);
        } else {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 n FROM opportunities")->fetch_assoc()['n'];
            $s = $conn->prepare("INSERT INTO opportunities (category,title,organisation,region,summary,eligibility,funding,status,deadline,deadline_note,apply_url,source_url,verified_on,is_featured,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $s->bind_param("sssssssssssssiii", $cat, $title, $org, $reg, $sum, $eli, $fund, $st, $dl, $note, $url, $src, $ver, $feat, $act, $o);
        }
        $s->execute(); header("Location: opportunities.php?saved=1"); exit;
    }
}
$cur = $id ? $conn->query("SELECT * FROM opportunities WHERE id=$id")->fetch_assoc() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') $cur = array_merge($cur ?: [], $_POST);
$f = fn($k, $d = '') => htmlspecialchars((string)($cur[$k] ?? $d));
$rows = $conn->query("SELECT * FROM opportunities ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="opportunities.php" class="active">Opportunities</a><a href="../opportunities.php" target="_blank">View on the website ↗</a><a href="menus.php">Menus</a></div>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Removed.</div><?php endif; ?>
<?php if (isset($_GET['verified'])): ?><div class="admin-alert ok">Marked as checked today.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card" id="opp-form">
  <div class="admin-card-head"><h2><?php echo $cur && $id ? 'Edit opportunity' : 'Add an opportunity'; ?></h2><span class="admin-help">Only publish what you have confirmed on the organiser's own page. Always add the official link and re-check dates before each call closes.</span></div>
  <form method="post" class="admin-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Title</label><input name="title" required value="<?php echo $f('title'); ?>"></div>
      <div class="field"><label>Organiser / funder</label><input name="organisation" value="<?php echo $f('organisation'); ?>"></div>
      <div class="field"><label>Category</label><select name="category"><?php foreach ($cats as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($cur['category'] ?? 'fund_ug') === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Who it is for (country / region)</label><input name="region" value="<?php echo $f('region'); ?>" placeholder="e.g. Uganda, East Africa, Global"></div>
      <div class="field"><label>Status</label><select name="status"><?php foreach ($sts as $k => $l): ?><option value="<?php echo $k; ?>" <?php echo ($cur['status'] ?? 'check') === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option><?php endforeach; ?></select><small class="admin-help">A call with a past deadline shows as closed automatically.</small></div>
      <div class="field"><label>Deadline (if dated)</label><input type="date" name="deadline" value="<?php echo $f('deadline'); ?>"></div>
      <div class="field"><label>Deadline / timing note</label><input name="deadline_note" value="<?php echo $f('deadline_note'); ?>" placeholder="e.g. Four cycles a year"></div>
      <div class="field"><label>Funding / benefit</label><input name="funding" value="<?php echo $f('funding'); ?>" placeholder="e.g. Up to $10,000"></div>
      <div class="field"><label>Apply link (official page)</label><input type="url" name="apply_url" value="<?php echo $f('apply_url'); ?>" placeholder="https://…"></div>
      <div class="field"><label>Source link (where you confirmed it)</label><input type="url" name="source_url" value="<?php echo $f('source_url'); ?>" placeholder="https://…"></div>
      <div class="field"><label>Last checked on</label><input type="date" name="verified_on" value="<?php echo $f('verified_on', date('Y-m-d')); ?>"></div>
    </div>
    <div class="field"><label>Summary</label><textarea name="summary" rows="3"><?php echo $f('summary'); ?></textarea></div>
    <div class="field"><label>Eligibility &amp; requirements — one point per line</label><textarea name="eligibility" rows="7"><?php echo $f('eligibility'); ?></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin:6px 0 14px"><label><input type="checkbox" name="is_featured" <?php echo !empty($cur['is_featured']) ? 'checked' : ''; ?>> Featured</label><label><input type="checkbox" name="is_active" <?php echo !$cur || !empty($cur['is_active']) ? 'checked' : ''; ?>> Show on the website</label></div>
    <div style="display:flex;gap:10px"><button class="admin-btn admin-btn-brand"><?php echo $id ? 'Save changes' : 'Add opportunity'; ?></button><?php if ($id): ?><a class="admin-btn admin-btn-line" href="opportunities.php">Cancel</a><?php endif; ?></div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>All opportunities (<?php echo count($rows); ?>)</h2></div>
  <table class="admin-table" data-export="Opportunities"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Deadline</th><th>Checked</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $st = opp_status($r); ?>
  <tr><td><b><?php echo htmlspecialchars($r['title']); ?></b><br><small><?php echo htmlspecialchars($r['organisation']); ?></small></td>
    <td><?php echo htmlspecialchars($cats[$r['category']] ?? 'Other'); ?></td>
    <td><span class="admin-badge <?php echo $st === 'closed' ? 'gray' : ''; ?>"><?php echo htmlspecialchars($sts[$st]); ?></span></td>
    <td><?php echo $r['deadline'] ? htmlspecialchars(date('j M Y', strtotime($r['deadline']))) : '—'; ?></td>
    <td><?php echo $r['verified_on'] ? htmlspecialchars(date('j M Y', strtotime($r['verified_on']))) : '—'; ?> <a href="opportunities.php?verify=<?php echo $r['id']; ?>" title="Mark as checked today">✓</a></td>
    <td><a href="opportunities.php?toggle=<?php echo $r['id']; ?>" class="admin-badge <?php echo $r['is_active'] ? '' : 'gray'; ?>"><?php echo $r['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
    <td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="opportunities.php?edit=<?php echo $r['id']; ?>#opp-form">Edit</a> <a class="admin-btn admin-btn-danger admin-btn-sm" href="opportunities.php?delete=<?php echo $r['id']; ?>" data-confirm="Remove this opportunity?">Delete</a></td></tr>
  <?php endforeach; ?></tbody></table>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
