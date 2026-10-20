<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
$cat = trim($_GET['cat'] ?? '');
if (!in_array($cat, nufa_resource_categories($conn), true)) $cat = '';
$q = trim(mb_substr($_GET['q'] ?? '', 0, 80));

$pageTitle = cms_value('seo', 'resources_title');
$pageDesc  = cms_value('seo', 'resources_desc');
$activeNav = "resources";
$canonicalPath = "resources.php";
if ($cat || $q) $noindex = true;
include __DIR__ . "/includes/header.php";

$where = ["is_active=1"]; $types = ''; $params = [];
if ($cat) { $where[] = "category=?"; $types .= 's'; $params[] = $cat; }
if ($q !== '') { $where[] = "(title LIKE ? OR description LIKE ?)"; $types .= 'ss'; $l = "%$q%"; array_push($params, $l, $l); }
$st = $conn->prepare("SELECT * FROM resources WHERE " . implode(' AND ', $where) . " ORDER BY COALESCE(published_on, DATE(created_at)) DESC, id DESC");
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$total = (int)$conn->query("SELECT COUNT(*) FROM resources WHERE is_active=1")->fetch_row()[0];

echo ui_page_hero('Resources', cms_value('resources', 'hero_title'), cms_value('resources', 'hero_lede'), cms_img_raw('resources', 'hero_image'), [['Resources']]);
?>
<section class="section">
  <div class="container">
    <?php if ($total === 0): ?>
      <div class="empty-state" data-reveal><h3>The library is being prepared</h3><p>Approved guides, reports, calls for submissions and funding opportunities will be added here as NUFA supplies them. No documents are listed until they exist.</p>
        <p style="margin-top:18px"><a href="news.php?cat=Opportunities+and+Calls" class="btn btn-outline-dark">See opportunities in the news</a></p></div>
    <?php else: ?>
    <div class="filter-bar">
      <a href="resources.php" class="<?php echo !$cat ? 'active' : ''; ?>">All</a>
      <?php foreach (nufa_resource_categories($conn) as $c): ?><a href="resources.php?cat=<?php echo urlencode($c); ?>" class="<?php echo $cat === $c ? 'active' : ''; ?>"><?php echo ui_e($c); ?></a><?php endforeach; ?>
    </div>
    <form class="search-form" method="get" action="resources.php" role="search" style="margin-bottom:30px">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?php echo ui_e($cat); ?>"><?php endif; ?>
      <label class="sr-only" for="res-q">Search resources</label><input id="res-q" type="search" name="q" value="<?php echo ui_e($q); ?>" placeholder="Search resources"><button type="submit">Search</button>
    </form>
    <?php if (!$items): ?><div class="empty-state"><h3>No resources match</h3><p>Try a different category or search term.</p></div>
    <?php else: foreach ($items as $r):
        $href = $r['file_path'] ? 'assets/resources/' . rawurlencode($r['file_path']) : $r['external_url'];
        $ext = $r['file_path'] ? strtoupper(pathinfo($r['file_path'], PATHINFO_EXTENSION)) : 'LINK'; ?>
      <div class="resource-item">
        <span class="icon-circle"><?php echo nufa_icon($r['file_path'] ? 'download' : 'globe', 22); ?></span>
        <div><div class="card-meta"><b><?php echo ui_e($r['category']); ?></b><span><?php echo ui_date($r['published_on'] ?: substr($r['created_at'], 0, 10)); ?></span></div><h3><?php echo ui_e($r['title']); ?></h3><?php if ($r['description']): ?><p><?php echo ui_e($r['description']); ?></p><?php endif; ?></div>
        <?php if ($href): ?><a href="<?php echo ui_e($href); ?>" class="btn btn-outline-dark btn-sm" <?php echo $r['file_path'] ? 'download' : 'target="_blank" rel="noopener"'; ?>><?php echo $r['file_path'] ? 'Download ' . ui_e($ext) : 'Open link'; ?></a><?php endif; ?>
      </div>
    <?php endforeach; endif; endif; ?>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
