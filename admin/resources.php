<?php
$adminTitle = "Resource Library";
$adminNav = "resources";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload()
require_once __DIR__ . "/../includes/ui.php";           // category list

$dir = __DIR__ . "/../assets/resources/";
$docTypes = ['application/pdf' => 'pdf', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
             'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
             'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx', 'application/zip' => 'zip'];

if (isset($_GET['delete'])) {
    $row = $conn->query("SELECT file_path FROM resources WHERE id=" . (int)$_GET['delete'])->fetch_assoc();
    if ($row && $row['file_path']) @unlink($dir . basename($row['file_path']));
    $conn->query("DELETE FROM resources WHERE id=" . (int)$_GET['delete']);
    header("Location: resources.php?deleted=1"); exit;
}
if (isset($_GET['toggle'])) { $conn->query("UPDATE resources SET is_active = 1 - is_active WHERE id=" . (int)$_GET['toggle']); header("Location: resources.php"); exit; }

$error = ''; $edit = null;
if (isset($_GET['id'])) $edit = $conn->query("SELECT * FROM resources WHERE id=" . (int)$_GET['id'])->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? ''); $cat = trim($_POST['category'] ?? ''); $desc = trim($_POST['description'] ?? '');
    $url = trim($_POST['external_url'] ?? ''); $date = trim($_POST['published_on'] ?? '') ?: null;
    $existing = $rid ? $conn->query("SELECT * FROM resources WHERE id=$rid")->fetch_assoc() : null;
    $file = $existing['file_path'] ?? '';
    if ($title === '') $error = "Enter a title.";
    elseif (!in_array($cat, NUFA_RESOURCE_CATEGORIES, true)) $error = "Choose a category.";
    elseif ($url !== '' && !preg_match('#^https?://#i', $url)) $error = "The external link must start with https://";
    if (!$error && !empty($_FILES['file']['name'])) {
        [$fn, $e] = mem_store_upload($_FILES['file'], $docTypes, 20 * 1048576, 'resource', $dir);
        if ($e) $error = "File: " . $e;
        else { if ($file) @unlink($dir . basename($file)); $file = $fn; }
    }
    if (!$error && $file === '' && $url === '') $error = "Upload a file or provide an external link — a resource must point to something real.";
    if (!$error) {
        if ($rid) {
            $s = $conn->prepare("UPDATE resources SET title=?, category=?, description=?, file_path=?, external_url=?, published_on=? WHERE id=?");
            $s->bind_param("ssssssi", $title, $cat, $desc, $file, $url, $date, $rid);
        } else {
            $s = $conn->prepare("INSERT INTO resources (title, category, description, file_path, external_url, published_on) VALUES (?,?,?,?,?,?)");
            $s->bind_param("ssssss", $title, $cat, $desc, $file, $url, $date);
        }
        $s->execute();
        header("Location: resources.php?saved=1"); exit;
    }
    $edit = ['id' => $rid, 'title' => $title, 'category' => $cat, 'description' => $desc, 'external_url' => $url, 'published_on' => $date, 'file_path' => $file];
}
$rows = $conn->query("SELECT * FROM resources ORDER BY COALESCE(published_on, DATE(created_at)) DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
$ev = fn($k) => htmlspecialchars((string)($edit[$k] ?? ''));
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Resource saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Resource deleted.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo !empty($edit['id']) ? 'Edit resource' : 'Add a resource'; ?></h2><span class="admin-help">Only add documents that have been supplied and approved — never list a file that does not exist.</span></div>
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="id" value="<?php echo (int)($edit['id'] ?? 0); ?>">
    <div class="admin-form-grid">
      <div class="field"><label>Title</label><input name="title" value="<?php echo $ev('title'); ?>" required></div>
      <div class="field"><label>Category</label><select name="category" required><option value="">Select…</option><?php foreach (NUFA_RESOURCE_CATEGORIES as $c): ?><option <?php echo ($edit['category'] ?? '') === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>File (PDF, Word, Excel, PowerPoint or ZIP — max 20 MB)</label><input type="file" name="file"><?php if (!empty($edit['file_path'])): ?><span class="admin-help">Current: <?php echo $ev('file_path'); ?> (upload a new file to replace)</span><?php endif; ?></div>
      <div class="field"><label>…or external link</label><input type="url" name="external_url" value="<?php echo $ev('external_url'); ?>" placeholder="https://"></div>
      <div class="field"><label>Publication date</label><input type="date" name="published_on" value="<?php echo $ev('published_on'); ?>"></div>
    </div>
    <div class="field"><label>Description</label><textarea name="description" style="min-height:80px"><?php echo $ev('description'); ?></textarea></div>
    <button class="admin-btn admin-btn-brand"><?php echo !empty($edit['id']) ? 'Save changes' : 'Add resource'; ?></button>
    <?php if (!empty($edit['id'])): ?><a href="resources.php" class="admin-btn admin-btn-line">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Library (<?php echo count($rows); ?>)</h2></div>
  <?php if (!$rows): ?><p class="admin-help">No resources yet. The public Resources page shows a “being prepared” message until the first one is added.</p><?php else: ?>
  <table class="admin-table"><thead><tr><th>Title</th><th>Category</th><th>Date</th><th>Type</th><th>Visible</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td><b><?php echo htmlspecialchars($r['title']); ?></b></td><td><?php echo htmlspecialchars($r['category']); ?></td>
      <td><?php echo ui_date($r['published_on'] ?: substr($r['created_at'], 0, 10), 'd M Y'); ?></td><td><?php echo $r['file_path'] ? strtoupper(pathinfo($r['file_path'], PATHINFO_EXTENSION)) : 'Link'; ?></td>
      <td><a href="resources.php?toggle=<?php echo $r['id']; ?>" class="admin-badge <?php echo $r['is_active'] ? '' : 'gray'; ?>"><?php echo $r['is_active'] ? 'Yes' : 'Hidden'; ?></a></td>
      <td style="white-space:nowrap"><a href="resources.php?id=<?php echo $r['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a> <a href="resources.php?delete=<?php echo $r['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this resource and its file?">Delete</a></td></tr>
  <?php endforeach; ?></tbody></table>
  <?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
