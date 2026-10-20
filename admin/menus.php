<?php
$adminTitle = "Menus & Footer";
$adminNav = "menus";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/menu.php";

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $loc = array_key_exists($_POST['location'] ?? '', NUFA_MENU_LOCATIONS) ? $_POST['location'] : 'header';
    $label = trim($_POST['label'] ?? ''); $url = trim($_POST['url'] ?? ''); $key = trim($_POST['nav_key'] ?? '');
    $sort = (int)($_POST['sort_order'] ?? 0); $active = isset($_POST['is_active']) ? 1 : 0; $tab = isset($_POST['new_tab']) ? 1 : 0;
    $urlOk = fn($u) => $u !== '' && (preg_match('#^(https?://|mailto:|tel:|\#)#i', $u) || preg_match('#^[A-Za-z0-9_./?=&%\#:+-]+$#', $u) || in_array($u, ['auto:editions', 'auto:programmes'], true));

    if ($act === 'add' || $act === 'save') {
        if ($label === '') $err = "Enter a label.";
        elseif (!$urlOk($url)) $err = "Enter a valid link — a page such as about.php, an anchor such as about.php#story, or a full https:// address.";
        elseif ($act === 'add') {
            $parent = (int)($_POST['parent_id'] ?? 0) ?: null;
            if (!$sort) $sort = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='" . $conn->real_escape_string($loc) . "'")->fetch_row()[0];
            $s = $conn->prepare("INSERT INTO nav_items (location, parent_id, label, url, nav_key, new_tab, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)");
            $s->bind_param("sisssiii", $loc, $parent, $label, $url, $key, $tab, $sort, $active);
            $s->execute(); $msg = "Menu item added.";
        } else {
            $s = $conn->prepare("UPDATE nav_items SET label=?, url=?, nav_key=?, new_tab=?, sort_order=?, is_active=? WHERE id=?");
            $s->bind_param("sssiiii", $label, $url, $key, $tab, $sort, $active, $id);
            $s->execute(); $msg = "Menu item saved.";
        }
    } elseif ($act === 'delete') {
        $conn->query("DELETE FROM nav_items WHERE id=$id OR parent_id=$id"); $msg = "Menu item deleted.";
    } elseif ($act === 'reset') {
        $conn->query("DELETE FROM nav_items"); nufa_menu_seed($conn); $msg = "Menus restored to the original structure.";
    }
}
$rows = $conn->query("SELECT * FROM nav_items ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$by = [];
foreach ($rows as $r) $by[$r['location']][$r['parent_id'] ?: 0][] = $r;
$parents = $by['header'][0] ?? [];
include __DIR__ . "/includes/admin-header.php";

$rowForm = function ($r, $isChild = false) {
    ob_start(); ?>
    <form method="post" class="menu-row <?php echo $isChild ? 'is-child' : ''; ?>">
      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
      <input type="hidden" name="location" value="<?php echo htmlspecialchars($r['location']); ?>">
      <input name="label" value="<?php echo htmlspecialchars($r['label']); ?>" placeholder="Label" required aria-label="Label">
      <input name="url" value="<?php echo htmlspecialchars($r['url']); ?>" placeholder="about.php" required aria-label="Link">
      <?php if ($r['location'] === 'header' && !$isChild): ?><input name="nav_key" value="<?php echo htmlspecialchars($r['nav_key']); ?>" placeholder="highlight key" title="Page keys that highlight this item, comma-separated (e.g. about,membership)" aria-label="Highlight key" style="max-width:130px"><?php else: ?><input type="hidden" name="nav_key" value="<?php echo htmlspecialchars($r['nav_key']); ?>"><?php endif; ?>
      <input name="sort_order" type="number" value="<?php echo (int)$r['sort_order']; ?>" title="Order" aria-label="Order" style="max-width:64px">
      <label class="mem-check" style="margin:0"><input type="checkbox" name="is_active" value="1" <?php echo $r['is_active'] ? 'checked' : ''; ?>> Show</label>
      <label class="mem-check" style="margin:0"><input type="checkbox" name="new_tab" value="1" <?php echo $r['new_tab'] ? 'checked' : ''; ?>> New tab</label>
      <button name="action" value="save" class="admin-btn admin-btn-line admin-btn-sm">Save</button>
      <button name="action" value="delete" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Delete this menu item<?php echo !$isChild ? ' and its sub-items' : ''; ?>?')">Delete</button>
    </form>
    <?php return ob_get_clean();
};
?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<p class="admin-help" style="margin-bottom:16px">These control the links visitors see in the header and footer. Use a site page (<code>about.php</code>), an anchor (<code>about.php#story</code>) or a full <code>https://</code> address. A sub-item with the link <code>auto:editions</code> or <code>auto:programmes</code> automatically lists every award edition / programme. Footer headings, the header button and tagline are edited under <a href="content-edit.php?page=site">Page Content → Site-wide</a>.</p>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo NUFA_MENU_LOCATIONS['header']; ?></h2></div>
  <?php foreach ($by['header'][0] ?? [] as $p): ?>
    <?php echo $rowForm($p); ?>
    <?php foreach ($by['header'][$p['id']] ?? [] as $c) echo $rowForm($c, true); ?>
  <?php endforeach; ?>
  <?php if (!$parents): ?><p class="admin-help">No items yet — the site falls back to its original menu.</p><?php endif; ?>
</div>

<?php foreach (['footer1', 'footer2', 'footer_legal'] as $loc): ?>
<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo NUFA_MENU_LOCATIONS[$loc]; ?></h2></div>
  <?php foreach ($by[$loc][0] ?? [] as $r) echo $rowForm($r); ?>
</div>
<?php endforeach; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a menu item</h2></div>
  <form method="post" class="admin-form">
    <input type="hidden" name="action" value="add"><input type="hidden" name="is_active" value="1">
    <div class="admin-form-grid">
      <div class="field"><label>Where</label><select name="location"><?php foreach (NUFA_MENU_LOCATIONS as $k => $l): ?><option value="<?php echo $k; ?>"><?php echo htmlspecialchars($l); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Under (main menu only)</label><select name="parent_id"><option value="0">— top level —</option><?php foreach ($parents as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['label']); ?> (dropdown)</option><?php endforeach; ?></select></div>
      <div class="field"><label>Label</label><input name="label" required></div>
      <div class="field"><label>Link</label><input name="url" placeholder="about.php or https://…" required></div>
    </div>
    <label class="mem-check"><input type="checkbox" name="new_tab" value="1"> Open in a new tab</label>
    <button class="admin-btn admin-btn-brand">Add item</button>
  </form>
</div>

<form method="post" onsubmit="return confirm('Restore the original menus? Your changes to menu items will be lost.')">
  <input type="hidden" name="action" value="reset"><button class="admin-btn admin-btn-danger admin-btn-sm">Restore original menus</button>
</form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
