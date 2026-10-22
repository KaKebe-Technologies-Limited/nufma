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
    <?php if (nufa_fn_on('resources', 'show_categories')): ?><div class="filter-bar">
      <a href="resources.php" class="<?php echo !$cat ? 'active' : ''; ?>">All</a>
      <?php foreach (nufa_resource_categories($conn) as $c): ?><a href="resources.php?cat=<?php echo urlencode($c); ?>" class="<?php echo $cat === $c ? 'active' : ''; ?>"><?php echo ui_e($c); ?></a><?php endforeach; ?>
    </div><?php endif; ?>
    <?php if (nufa_fn_on('resources', 'show_search')): ?><form class="search-form" method="get" action="resources.php" role="search" style="margin-bottom:30px">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?php echo ui_e($cat); ?>"><?php endif; ?>
      <label class="sr-only" for="res-q">Search resources</label><input id="res-q" type="search" name="q" value="<?php echo ui_e($q); ?>" placeholder="Search resources"><button type="submit">Search</button>
    </form><?php endif; ?>
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
<?php
require_once __DIR__ . "/includes/films.php"; film_install($conn);
$__films = $conn->query("SELECT * FROM films WHERE status='approved' ORDER BY is_featured DESC, approved_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
?>
<section class="section section--soft" id="film-showcase"><div class="container">
  <div class="section-head center"><div class="eyebrow">Film Showcase</div><h2>Watch films by our filmmakers</h2><p>Short films and productions reviewed by NUFA — watch them free and support the filmmakers you love.</p></div>
  <?php if ($__films): ?><div class="fm-grid" style="margin-bottom:26px"><?php foreach ($__films as $f): ?>
    <a class="fm-card" href="film.php?slug=<?php echo ui_e($f['slug']); ?>"><div class="fm-poster"><?php if (film_poster_ok($f)): ?><img src="assets/images/<?php echo ui_e($f['poster']); ?>" alt="" loading="lazy"><?php else: ?><span><?php echo ui_e($f['title']); ?></span><?php endif; ?><?php if ($f['runtime_min']): ?><em class="fm-run"><?php echo ui_e(film_runtime($f)); ?></em><?php endif; ?><i class="fm-play"><svg viewBox="0 0 24 24" width="20" height="20"><path d="M8 5v14l11-7z" fill="currentColor"/></svg></i></div><div class="fm-body"><h3><?php echo ui_e($f['title']); ?></h3><p class="fm-by">by <?php echo ui_e($f['fm_name']); ?></p></div></a>
  <?php endforeach; ?></div><?php endif; ?>
  <div class="actions" style="justify-content:center"><a class="btn btn-gold" href="films.php"><?php echo $__films ? 'Watch all films' : 'Open the Film Showcase'; ?></a><a class="btn btn-outline-dark" href="film-submit.php">Submit your film</a><a class="btn btn-outline-dark" href="academy.php">NUFA Academy</a><a class="btn btn-outline-dark" href="opportunities.php">Opportunities</a></div>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
