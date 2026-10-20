<?php
$adminTitle = "Page Editor";
$adminNav = "pages";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/sections.php";
require_once __DIR__ . "/../includes/content.php";

$registry = nufa_registry();
$cfg = nufa_site_settings($conn);
$edited = [];
$r = $conn->query("SELECT page, COUNT(*) c FROM page_content GROUP BY page");
if ($r) while ($x = $r->fetch_assoc()) $edited[$x['page']] = (int)$x['c'];
include __DIR__ . "/includes/admin-header.php";
?>
<p class="admin-help" style="margin-bottom:18px">Pick a page and change everything on it, one area at a time: its <b>text and images</b>, which <b>sections</b> are shown, how its <b>forms and functions</b> behave, and how it appears in <b>search and social sharing</b>.</p>
<div class="pe-grid">
<?php foreach ($registry as $k => $p):
    $hidden = 0; foreach ($p['sections'] as $s) if (($s[2] ?? true) !== false && ($cfg["show_{$k}_{$s[0]}"] ?? '1') === '0') $hidden++;
    $fields = count($CMS_SCHEMAS[$p['schema']]['fields'] ?? []);
    $formOn = !empty($p['form']) ? (($cfg["form_{$k}_enabled"] ?? '1') !== '0') : null;
    $noindex = ($cfg["noindex_$k"] ?? '0') === '1'; ?>
  <div class="admin-card pe-card">
    <div class="admin-card-head"><h2><?php echo htmlspecialchars($p['label']); ?></h2><?php if (empty($p['virtual'])): ?><a href="../<?php echo htmlspecialchars($k === 'policies' ? 'policy.php?p=privacy' : $p['file']); ?>" target="_blank" class="admin-help">View ↗</a><?php endif; ?></div>
    <ul class="pe-facts">
      <li><b><?php echo $fields; ?></b> editable text/image items<?php echo !empty($edited[$p['schema']]) ? ' · <span class="admin-badge">' . $edited[$p['schema']] . ' customised</span>' : ''; ?></li>
      <?php if ($p['sections']): ?><li><b><?php echo count($p['sections']); ?></b> sections<?php echo $hidden ? ' · <span class="admin-badge red">' . $hidden . ' hidden</span>' : ''; ?></li><?php endif; ?>
      <?php if ($formOn !== null): ?><li>Form: <span class="admin-badge <?php echo $formOn ? '' : 'red'; ?>"><?php echo $formOn ? 'accepting submissions' : 'switched off'; ?></span></li><?php endif; ?>
      <?php if ($p['seo']): ?><li>Search: <?php echo $noindex ? '<span class="admin-badge red">hidden from search</span>' : '<span class="admin-badge gray">listed</span>'; ?></li><?php endif; ?>
    </ul>
    <a href="page-editor.php?page=<?php echo $k; ?>" class="admin-btn admin-btn-brand">Edit this page</a>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
