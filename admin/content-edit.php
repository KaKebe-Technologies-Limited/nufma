<?php
$adminNav = "content";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/content.php";
require __DIR__ . "/../includes/content-schemas.php";

$page = $_GET['page'] ?? '';
if (!isset($CMS_SCHEMAS[$page])) { header("Location: content.php"); exit; }
$schema = $CMS_SCHEMAS[$page]['fields'];
$adminTitle = $CMS_SCHEMAS[$page]['label'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_save($conn, $page, $schema, $_POST, $_FILES);
    header("Location: content-edit.php?page=" . urlencode($page) . "&saved=1");
    exit;
}

$values = cms_load($conn, $page, $schema);
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved — the page reflects your changes immediately.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2><?php echo htmlspecialchars($CMS_SCHEMAS[$page]['label']); ?></h2>
    <a href="../<?php echo htmlspecialchars($page === 'home' ? 'index' : $page); ?>.php" target="_blank" class="admin-btn admin-btn-line admin-btn-sm">View page</a>
  </div>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <?php foreach ($schema as $key => $def):
      $type = $def['type'] ?? 'text';
      $val = $values[$key] ?? '';
    ?>
    <div class="field">
      <label><?php echo htmlspecialchars($def['label']); ?></label>
      <?php if ($type === 'text'): ?>
        <input type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($val); ?>">
      <?php elseif ($type === 'textarea' || $type === 'html'): ?>
        <textarea name="<?php echo htmlspecialchars($key); ?>" style="min-height:<?php echo $type === 'html' && strlen($val) > 200 ? '160px' : '80px'; ?>"><?php echo htmlspecialchars($val); ?></textarea>
      <?php elseif ($type === 'image'):
        $exists = $val && file_exists(__DIR__ . "/../assets/images/" . $val);
      ?>
        <input type="file" name="<?php echo htmlspecialchars($key); ?>" accept=".jpg,.jpeg,.png,.webp,image/*">
        <div class="admin-help" style="margin-top:10px">
          <?php if ($exists): ?>
            <img src="../assets/images/<?php echo htmlspecialchars($val); ?>" alt="" style="height:70px;max-width:200px;object-fit:cover;border-radius:6px;vertical-align:middle">
            <span style="margin-left:8px">Current: <?php echo htmlspecialchars($val); ?> — upload a new file to replace it.</span>
          <?php else: ?>
            <span>Current: <?php echo htmlspecialchars($val); ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <button type="submit" class="admin-btn admin-btn-brand">Save Changes</button>
    <a href="content.php" class="admin-btn admin-btn-line">Back to Page Content</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
