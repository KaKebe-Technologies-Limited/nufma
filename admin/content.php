<?php
$adminTitle = "Page Content";
$adminNav = "content";
require __DIR__ . "/includes/auth.php";
require __DIR__ . "/../includes/content-schemas.php";
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved — the page reflects your changes immediately.</div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Edit Page Text &amp; Images</h2><span class="admin-help">Headings, paragraphs and hero images for each public page</span></div>
  <table class="admin-table">
    <thead><tr><th>Page</th><th>Editable fields</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($CMS_SCHEMAS as $key => $schema): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($schema['label']); ?></b></td>
        <td><?php echo count($schema['fields']); ?> fields</td>
        <td><a href="content-edit.php?page=<?php echo urlencode($key); ?>" class="admin-btn admin-btn-line admin-btn-sm">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Managed elsewhere</h2></div>
  <p class="admin-help" style="padding:0 0 16px">
    Blog posts, the hero slider, gallery photos and partner logos already have their own dedicated pages —
    <a href="posts.php">Blog Posts</a>, <a href="sliders.php">Hero Slider</a>, <a href="gallery.php">Gallery</a> and <a href="partners.php">Partners</a>.
    Contact details and social links live under <a href="settings.php">Settings</a>.
  </p>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
