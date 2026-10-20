<?php
$adminTitle = "NUFA SACCO manager";
$adminNav = "saccoadm";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
require_once __DIR__ . "/../includes/ui.php";

$tab = in_array($_GET['tab'] ?? '', ['status', 'products', 'documents', 'announcements', 'stats'], true) ? $_GET['tab'] : 'status';
$msg = ''; $err = '';
$save = function ($k, $v) use ($conn) {
    $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $s->bind_param("ss", $k, $v); $s->execute();
};
$docDir = __DIR__ . "/../assets/sacco-docs/";
$docTypesOk = ['application/pdf' => 'pdf', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
               'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx', 'application/zip' => 'zip'];
$prodCats = ['savings' => 'Savings', 'development' => 'Development / production loan', 'equipment' => 'Equipment financing', 'working_capital' => 'Working-capital financing', 'credit' => 'Other credit'];
$docTypes = ['Bylaws', 'Membership rules', 'Loan policy', 'Savings policy', 'Privacy notice', 'Complaints procedure', 'Application form', 'Registration certificate', 'Other'];
$annKinds = ['Update', 'Registration update', 'Member meeting', 'Product launch', 'Official notice'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $f = fn($k, $m = 255) => mb_substr(trim($_POST[$k] ?? ''), 0, $m);
    if ($act === 'status') {
        $st = in_array($_POST['sacco_status'] ?? '', ['proposed', 'in_development', 'operational'], true) ? $_POST['sacco_status'] : 'proposed';
        $save('sacco_status', $st);
        $save('sacco_applications_open', isset($_POST['sacco_applications_open']) ? '1' : '0');
        $save('sacco_vm_approved', isset($_POST['sacco_vm_approved']) ? '1' : '0');
        foreach (['sacco_regulator', 'sacco_reg_number', 'sacco_licence_status', 'sacco_reg_date'] as $k) $save($k, $f($k, 190));
        $msg = "Status and regulatory details saved — the SACCO page updates immediately.";
    } elseif ($act === 'product_save' || $act === 'product_add') {
        $name = $f('name', 190); $cat = isset($prodCats[$_POST['category'] ?? '']) ? $_POST['category'] : 'savings';
        $pst = ($_POST['status'] ?? '') === 'approved' ? 'approved' : 'proposed';
        if ($name === '') $err = "Give the product a name.";
        else {
            $v = [$cat, $name, mb_substr(trim($_POST['description'] ?? ''), 0, 3000), mb_substr(trim($_POST['eligibility'] ?? ''), 0, 3000), mb_substr(trim($_POST['terms'] ?? ''), 0, 3000), $pst, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
            if ($act === 'product_add') { $s = $conn->prepare("INSERT INTO sacco_products (category, name, description, eligibility, terms, status, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)"); $s->bind_param("ssssssii", ...$v); }
            else { $s = $conn->prepare("UPDATE sacco_products SET category=?, name=?, description=?, eligibility=?, terms=?, status=?, sort_order=?, is_active=? WHERE id=?"); $v[] = $id; $s->bind_param("ssssssiii", ...$v); }
            $s->execute(); $msg = "Product saved.";
        }
    } elseif ($act === 'product_delete') { $conn->query("DELETE FROM sacco_products WHERE id=$id"); $msg = "Product deleted."; }
    elseif ($act === 'doc_save' || $act === 'doc_add') {
        $title = $f('title'); $type = in_array($_POST['doc_type'] ?? '', $docTypes, true) ? $_POST['doc_type'] : 'Other'; $url = $f('external_url', 500);
        $date = $f('published_on', 10) ?: null; $desc = $f('description', 500);
        $existing = $id ? $conn->query("SELECT * FROM sacco_documents WHERE id=$id")->fetch_assoc() : null; $file = $existing['file_path'] ?? '';
        if ($title === '') $err = "Give the document a title.";
        elseif ($url !== '' && !preg_match('#^https?://#i', $url)) $err = "The link must start with https://";
        else {
            if (!empty($_FILES['file']['name'])) { [$fn, $e] = mem_store_upload($_FILES['file'], $docTypesOk, 20 * 1048576, 'sacco', $docDir); if ($e) $err = "File: " . $e; else { if ($file) @unlink($docDir . basename($file)); $file = $fn; } }
            if (!$err && $file === '' && $url === '') $err = "Upload a file or give a link — a document must point to something real.";
            if (!$err) {
                if ($act === 'doc_add') { $s = $conn->prepare("INSERT INTO sacco_documents (title, doc_type, description, file_path, external_url, published_on) VALUES (?,?,?,?,?,?)"); $s->bind_param("ssssss", $title, $type, $desc, $file, $url, $date); }
                else { $s = $conn->prepare("UPDATE sacco_documents SET title=?, doc_type=?, description=?, file_path=?, external_url=?, published_on=?, is_active=? WHERE id=?"); $a = isset($_POST['is_active']) ? 1 : 0; $s->bind_param("ssssssii", $title, $type, $desc, $file, $url, $date, $a, $id); }
                $s->execute(); $msg = "Document saved.";
            }
        }
    } elseif ($act === 'doc_delete') { $row = $conn->query("SELECT file_path FROM sacco_documents WHERE id=$id")->fetch_assoc(); if ($row && $row['file_path']) @unlink($docDir . basename($row['file_path'])); $conn->query("DELETE FROM sacco_documents WHERE id=$id"); $msg = "Document deleted."; }
    elseif ($act === 'ann_save' || $act === 'ann_add') {
        $title = $f('title'); $body = mb_substr(trim($_POST['body'] ?? ''), 0, 5000); $kind = in_array($_POST['kind'] ?? '', $annKinds, true) ? $_POST['kind'] : 'Update'; $date = $f('published_on', 10) ?: date('Y-m-d');
        if ($title === '') $err = "Give the announcement a title.";
        else {
            if ($act === 'ann_add') { $s = $conn->prepare("INSERT INTO sacco_announcements (title, body, kind, published_on) VALUES (?,?,?,?)"); $s->bind_param("ssss", $title, $body, $kind, $date); }
            else { $a = isset($_POST['is_active']) ? 1 : 0; $s = $conn->prepare("UPDATE sacco_announcements SET title=?, body=?, kind=?, published_on=?, is_active=? WHERE id=?"); $s->bind_param("ssssii", $title, $body, $kind, $date, $a, $id); }
            $s->execute(); $msg = "Announcement saved.";
        }
    } elseif ($act === 'ann_delete') { $conn->query("DELETE FROM sacco_announcements WHERE id=$id"); $msg = "Announcement deleted."; }
}
$cfg = nufa_site_settings($conn);
$cur = []; $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sacco\\_%'"); while ($x = $r->fetch_assoc()) $cur[$x['setting_key']] = $x['setting_value'];
$g = fn($k, $d = '') => $cur[$k] ?? $d;
include __DIR__ . "/includes/admin-header.php";
$tabs = ['status' => 'Status & regulatory', 'products' => 'Products', 'documents' => 'Documents', 'announcements' => 'Announcements', 'stats' => 'Visits & enquiries'];
$e = fn($v) => htmlspecialchars((string)$v);
?>
<div class="mem-tabs"><?php foreach ($tabs as $t => $l): ?><a href="sacco-manager.php?tab=<?php echo $t; ?>" class="<?php echo $tab === $t ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?>
  <a href="page-editor.php?page=sacco">Page text &amp; sections ↗</a><a href="../sacco.php" target="_blank">View page ↗</a></div>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<?php if ($tab === 'status'): $st = $g('sacco_status', 'proposed'); ?>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="status">
    <div class="admin-card">
      <div class="admin-card-head"><h2>SACCO status</h2><span class="admin-help">Must reflect verified reality — it appears on the public page.</span></div>
      <div class="field" style="max-width:420px"><label>Current status</label>
        <select name="sacco_status"><option value="proposed" <?php echo $st === 'proposed' ? 'selected' : ''; ?>>Proposed</option><option value="in_development" <?php echo $st === 'in_development' ? 'selected' : ''; ?>>In Development</option><option value="operational" <?php echo $st === 'operational' ? 'selected' : ''; ?>>Operational</option></select></div>
      <?php if ($st === 'operational' && trim($g('sacco_reg_number')) === ''): ?><div class="admin-alert err">The status is Operational but no registration number is recorded. Only mark the SACCO Operational once its registration, licensing and readiness are verified, and publish those details below.</div><?php endif; ?>
      <label class="mem-check"><input type="checkbox" name="sacco_applications_open" value="1" <?php echo $g('sacco_applications_open') === '1' ? 'checked' : ''; ?>> Accept membership applications on the page (only when the process is approved and ready)</label>
      <?php if ($g('sacco_applications_open') === '1' && $st !== 'operational'): ?><div class="admin-alert err">Applications are open while the SACCO is not Operational. Check that this is intended.</div><?php endif; ?>
      <label class="mem-check"><input type="checkbox" name="sacco_vm_approved" value="1" <?php echo $g('sacco_vm_approved') === '1' ? 'checked' : ''; ?>> SACCO vision &amp; mission are approved (removes the “Draft” labels)</label>
    </div>
    <div class="admin-card">
      <div class="admin-card-head"><h2>Regulatory information (publish only when verified)</h2></div>
      <div class="admin-form-grid">
        <div class="field"><label>Regulator / registering authority</label><input name="sacco_regulator" value="<?php echo $e($g('sacco_regulator')); ?>" placeholder="e.g. as named on the certificate"></div>
        <div class="field"><label>Registration number</label><input name="sacco_reg_number" value="<?php echo $e($g('sacco_reg_number')); ?>"></div>
        <div class="field"><label>Licence / authorisation status</label><input name="sacco_licence_status" value="<?php echo $e($g('sacco_licence_status')); ?>"></div>
        <div class="field"><label>Date</label><input name="sacco_reg_date" value="<?php echo $e($g('sacco_reg_date')); ?>" placeholder="e.g. 12 March 2027"></div>
      </div>
      <p class="admin-help">The explanatory text for this section is edited in <a href="page-editor.php?page=sacco">Page Editor → SACCO</a>.</p>
    </div>
    <button class="admin-btn admin-btn-brand">Save</button>
  </form>

<?php elseif ($tab === 'products'):
    $prods = $conn->query("SELECT * FROM sacco_products ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
    $pForm = function ($p, $add = false) use ($prodCats, $e) { ob_start(); ?>
    <form method="post" class="admin-card admin-form">
      <input type="hidden" name="action" value="<?php echo $add ? 'product_add' : 'product_save'; ?>"><input type="hidden" name="id" value="<?php echo (int)($p['id'] ?? 0); ?>">
      <div class="admin-form-grid">
        <div class="field"><label>Product name</label><input name="name" value="<?php echo $e($p['name'] ?? ''); ?>" required></div>
        <div class="field"><label>Category</label><select name="category"><?php foreach ($prodCats as $k => $l) echo '<option value="' . $k . '"' . (($p['category'] ?? 'savings') === $k ? ' selected' : '') . '>' . $l . '</option>'; ?></select></div>
        <div class="field"><label>Status</label><select name="status"><option value="proposed" <?php echo ($p['status'] ?? 'proposed') === 'proposed' ? 'selected' : ''; ?>>Proposed (idea only)</option><option value="approved" <?php echo ($p['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved (terms are published)</option></select></div>
        <div class="field"><label>Order</label><input type="number" name="sort_order" value="<?php echo (int)($p['sort_order'] ?? 0); ?>"></div>
      </div>
      <div class="field"><label>Description</label><textarea name="description" style="min-height:70px"><?php echo $e($p['description'] ?? ''); ?></textarea></div>
      <div class="admin-form-grid">
        <div class="field"><label>Eligibility (shown only when Approved)</label><textarea name="eligibility" style="min-height:80px"><?php echo $e($p['eligibility'] ?? ''); ?></textarea></div>
        <div class="field"><label>Repayment terms, interest, fees &amp; security (shown only when Approved)</label><textarea name="terms" style="min-height:80px"><?php echo $e($p['terms'] ?? ''); ?></textarea></div>
      </div>
      <div style="display:flex;gap:14px;align-items:center"><label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo ($add || !empty($p['is_active'])) ? 'checked' : ''; ?>> Show on the page</label>
        <button class="admin-btn admin-btn-brand admin-btn-sm"><?php echo $add ? 'Add product' : 'Save'; ?></button>
        <?php if (!$add): ?><button name="action" value="product_delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this product?')">Delete</button><?php endif; ?></div>
    </form><?php return ob_get_clean(); }; ?>
  <p class="admin-help" style="margin-bottom:14px">Label every product <b>Proposed</b> until it is formally approved. Terms, interest, fees and eligibility stay hidden from the public until a product is marked Approved.</p>
  <?php foreach ($prods as $p) echo $pForm($p); ?>
  <h3 class="mem-h3">Add a product</h3><?php echo $pForm([], true); ?>

<?php elseif ($tab === 'documents'):
    $docs = $conn->query("SELECT * FROM sacco_documents ORDER BY COALESCE(published_on, DATE(created_at)) DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
    $dForm = function ($d, $add = false) use ($docTypes, $e) { ob_start(); ?>
    <form method="post" enctype="multipart/form-data" class="admin-card admin-form">
      <input type="hidden" name="action" value="<?php echo $add ? 'doc_add' : 'doc_save'; ?>"><input type="hidden" name="id" value="<?php echo (int)($d['id'] ?? 0); ?>">
      <div class="admin-form-grid">
        <div class="field"><label>Title</label><input name="title" value="<?php echo $e($d['title'] ?? ''); ?>" required></div>
        <div class="field"><label>Type</label><select name="doc_type"><?php foreach ($docTypes as $t) echo '<option' . (($d['doc_type'] ?? '') === $t ? ' selected' : '') . '>' . $t . '</option>'; ?></select></div>
        <div class="field"><label>File (PDF, Word, Excel, ZIP — max 20 MB)</label><input type="file" name="file"><?php if (!empty($d['file_path'])): ?><span class="admin-help">Current: <?php echo $e($d['file_path']); ?></span><?php endif; ?></div>
        <div class="field"><label>…or link</label><input type="url" name="external_url" value="<?php echo $e($d['external_url'] ?? ''); ?>" placeholder="https://"></div>
        <div class="field"><label>Date</label><input type="date" name="published_on" value="<?php echo $e($d['published_on'] ?? ''); ?>"></div>
        <div class="field"><label>Short description</label><input name="description" value="<?php echo $e($d['description'] ?? ''); ?>"></div>
      </div>
      <div style="display:flex;gap:14px;align-items:center"><?php if (!$add): ?><label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo !empty($d['is_active']) ? 'checked' : ''; ?>> Show</label><?php endif; ?>
        <button class="admin-btn admin-btn-brand admin-btn-sm"><?php echo $add ? 'Add document' : 'Save'; ?></button>
        <?php if (!$add): ?><button name="action" value="doc_delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this document and its file?')">Delete</button><?php endif; ?></div>
    </form><?php return ob_get_clean(); }; ?>
  <p class="admin-help" style="margin-bottom:14px">Publish only <b>approved</b> bylaws, forms and policies. Nothing is listed on the public page unless it exists here.</p>
  <?php foreach ($docs as $d) echo $dForm($d); ?>
  <h3 class="mem-h3">Add a document</h3><?php echo $dForm([], true); ?>

<?php elseif ($tab === 'announcements'):
    $anns = $conn->query("SELECT * FROM sacco_announcements ORDER BY published_on DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
    $aForm = function ($a, $add = false) use ($annKinds, $e) { ob_start(); ?>
    <form method="post" class="admin-card admin-form">
      <input type="hidden" name="action" value="<?php echo $add ? 'ann_add' : 'ann_save'; ?>"><input type="hidden" name="id" value="<?php echo (int)($a['id'] ?? 0); ?>">
      <div class="admin-form-grid">
        <div class="field"><label>Title</label><input name="title" value="<?php echo $e($a['title'] ?? ''); ?>" required></div>
        <div class="field"><label>Type</label><select name="kind"><?php foreach ($annKinds as $k) echo '<option' . (($a['kind'] ?? '') === $k ? ' selected' : '') . '>' . $k . '</option>'; ?></select></div>
        <div class="field"><label>Date</label><input type="date" name="published_on" value="<?php echo $e($a['published_on'] ?? date('Y-m-d')); ?>"></div>
      </div>
      <div class="field"><label>Message</label><textarea name="body" style="min-height:90px"><?php echo $e($a['body'] ?? ''); ?></textarea></div>
      <div style="display:flex;gap:14px;align-items:center"><?php if (!$add): ?><label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo !empty($a['is_active']) ? 'checked' : ''; ?>> Show</label><?php endif; ?>
        <button class="admin-btn admin-btn-brand admin-btn-sm"><?php echo $add ? 'Publish announcement' : 'Save'; ?></button>
        <?php if (!$add): ?><button name="action" value="ann_delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this announcement?')">Delete</button><?php endif; ?></div>
    </form><?php return ob_get_clean(); }; ?>
  <h3 class="mem-h3" style="margin-top:0">Post an announcement</h3><?php echo $aForm([], true); ?>
  <?php foreach ($anns as $a) echo $aForm($a); ?>

<?php else:
    $hits = $conn->query("SELECT day, hits FROM page_hits WHERE page='sacco' AND day >= CURDATE() - INTERVAL 29 DAY ORDER BY day")->fetch_all(MYSQLI_ASSOC);
    $byDay = array_column($hits, 'hits', 'day'); $series = []; $max = 1;
    for ($i = 29; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i day")); $series[$d] = (int)($byDay[$d] ?? 0); $max = max($max, $series[$d]); }
    $enq = $conn->query("SELECT topic, COUNT(*) c FROM enquiries WHERE type='sacco' AND created_at >= (NOW() - INTERVAL 30 DAY) GROUP BY topic")->fetch_all(MYSQLI_ASSOC);
    $enqAll = (int)$conn->query("SELECT COUNT(*) FROM enquiries WHERE type='sacco'")->fetch_row()[0];
?>
  <div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="admin-stat"><b><?php echo number_format(array_sum($series)); ?></b><span>Page visits — last 30 days</span></div>
    <div class="admin-stat"><b><?php echo array_sum(array_column($enq, 'c')); ?></b><span>Enquiries/applications — last 30 days</span></div>
    <a class="admin-stat" href="enquiries.php?type=sacco"><b><?php echo $enqAll; ?></b><span>All SACCO submissions (open)</span></a>
  </div>
  <div class="admin-card"><div class="admin-card-head"><h2>Daily visits</h2><span class="admin-help">Anonymous counts only — no cookies, IP addresses or personal data are stored.</span></div>
    <div class="bars" style="height:150px;gap:4px"><?php foreach ($series as $d => $n): ?><div class="bar" title="<?php echo date('d M', strtotime($d)); ?>: <?php echo $n; ?>"><i style="height:<?php echo max(2, round($n / $max * 110)); ?>px"></i></div><?php endforeach; ?></div></div>
  <div class="admin-card"><div class="admin-card-head"><h2>Submissions by type (last 30 days)</h2></div>
    <?php if (!$enq): ?><p class="admin-help">None yet.</p><?php endif; foreach ($enq as $q): ?><div class="mem-row"><span style="width:auto;flex:1"><?php echo $e($q['topic']); ?></span><b style="flex:none"><?php echo (int)$q['c']; ?></b></div><?php endforeach; ?></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
