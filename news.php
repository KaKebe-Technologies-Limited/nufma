<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/sections.php";

$cat = trim($_GET['cat'] ?? '');
if (!in_array($cat, nufa_news_categories($conn), true)) $cat = '';
$q = trim(mb_substr($_GET['q'] ?? '', 0, 80));
$page = max(1, (int)($_GET['page'] ?? 1));
$per = max(3, min(30, (int)nufa_fn('news', 'per_page')));

$pageTitle = cms_value('seo', 'news_title');
$pageDesc  = cms_value('seo', 'news_desc');
$activeNav = "news";
$canonicalPath = "news.php";
$ogImage = "assets/images/og/og-news.jpg";
if ($cat || $q || $page > 1) $noindex = true;
include __DIR__ . "/includes/header.php";

$where = ["is_published=1"]; $types = ''; $params = [];
if ($cat) { $where[] = "category=?"; $types .= 's'; $params[] = $cat; }
if ($q !== '') { $where[] = "(title LIKE ? OR excerpt LIKE ? OR body LIKE ?)"; $types .= 'sss'; $like = "%$q%"; array_push($params, $like, $like, $like); }
$wsql = implode(' AND ', $where);

$cs = $conn->prepare("SELECT COUNT(*) FROM posts WHERE $wsql");
if ($params) $cs->bind_param($types, ...$params);
$cs->execute();
$total = (int)$cs->get_result()->fetch_row()[0];
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);

$featured = null;
if (!$cat && $q === '' && $page === 1 && nufa_fn_on('news', 'show_featured')) $featured = $conn->query("SELECT * FROM posts WHERE is_published=1 ORDER BY is_featured DESC, published_at DESC LIMIT 1")->fetch_assoc();

$sql = "SELECT id, title, slug, category, excerpt, image, published_at FROM posts WHERE $wsql" . ($featured ? " AND id<>" . (int)$featured['id'] : '') . " ORDER BY published_at DESC LIMIT $per OFFSET " . (($page - 1) * $per);
$ps = $conn->prepare($sql);
if ($params) $ps->bind_param($types, ...$params);
$ps->execute();
$posts = $ps->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = [];
$cr = $conn->query("SELECT category, COUNT(*) c FROM posts WHERE is_published=1 GROUP BY category");
while ($r = $cr->fetch_assoc()) $counts[$r['category']] = (int)$r['c'];
$link = fn($extra) => 'news.php?' . http_build_query(array_filter(array_merge(['cat' => $cat, 'q' => $q], $extra), fn($v) => $v !== '' && $v !== null && $v !== 1));

echo ui_page_hero('News & Stories', cms_value('news', 'hero_title'), cms_value('news', 'hero_lede'), cms_img_raw('news', 'hero_image'), [['News & Stories']]);
?>
<section class="section">
  <div class="container">
    <?php if ($featured): ?>
    <article class="news-feature" data-reveal style="margin-bottom:56px">
      <a href="post.php?slug=<?php echo urlencode($featured['slug']); ?>" aria-label="<?php echo ui_e($featured['title']); ?>"><img src="assets/images/<?php echo ui_e($featured['image']); ?>" alt="" width="1200" height="800"></a>
      <div class="body">
        <span class="chip chip--dark" style="width:max-content;border:1px solid var(--gold)">Featured · <?php echo ui_e($featured['category']); ?></span>
        <h2 style="margin:0"><a href="post.php?slug=<?php echo urlencode($featured['slug']); ?>"><?php echo ui_e($featured['title']); ?></a></h2>
        <p><?php echo ui_e(ui_excerpt($featured['excerpt'], 200)); ?></p>
        <div class="card-meta" style="color:var(--muted-on-dark)"><span><?php echo ui_date($featured['published_at']); ?></span></div>
        <a href="post.php?slug=<?php echo urlencode($featured['slug']); ?>" class="link-arrow">Read the story</a>
      </div>
    </article>
    <?php endif; ?>

    <?php if (nufa_fn_on('news', 'show_categories')): ?><div class="filter-bar" role="navigation" aria-label="News categories">
      <a href="news.php" class="<?php echo !$cat ? 'active' : ''; ?>">All</a>
      <?php foreach (nufa_news_categories($conn) as $c): ?><a href="news.php?cat=<?php echo urlencode($c); ?>" class="<?php echo $cat === $c ? 'active' : ''; ?>"><?php echo ui_e($c); ?><?php echo isset($counts[$c]) ? ' (' . $counts[$c] . ')' : ''; ?></a><?php endforeach; ?>
    </div><?php endif; ?>
    <?php if (nufa_fn_on('news', 'show_search')): ?><form class="search-form" method="get" action="news.php" role="search" style="margin-bottom:34px">
      <?php if ($cat): ?><input type="hidden" name="cat" value="<?php echo ui_e($cat); ?>"><?php endif; ?>
      <label class="sr-only" for="news-q">Search news</label><input id="news-q" type="search" name="q" value="<?php echo ui_e($q); ?>" placeholder="Search news and stories">
      <button type="submit">Search</button>
    </form><?php endif; ?>

    <?php if ($q !== '' || $cat): ?><p class="muted" style="margin-bottom:22px"><?php echo $total; ?> result<?php echo $total === 1 ? '' : 's'; ?><?php echo $cat ? ' in ' . ui_e($cat) : ''; ?><?php echo $q !== '' ? ' for “' . ui_e($q) . '”' : ''; ?> — <a class="hl" style="text-decoration:underline" href="news.php">clear</a></p><?php endif; ?>

    <?php if (!$posts && !$featured): ?>
      <div class="empty-state"><h3>No stories found</h3><p><?php echo ($q !== '' || $cat) ? 'Try a different search or category.' : 'News and stories will appear here once they are published.'; ?></p></div>
    <?php else: ?>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($posts as $p): ?>
      <article class="card"><a href="post.php?slug=<?php echo urlencode($p['slug']); ?>" style="display:flex;flex-direction:column;height:100%">
        <div class="card-media"><?php echo ui_img($p['image'], ''); ?></div>
        <div class="card-body"><div class="card-meta"><b><?php echo ui_e($p['category']); ?></b><span><?php echo ui_date($p['published_at']); ?></span></div><h3><?php echo ui_e($p['title']); ?></h3><p><?php echo ui_e(ui_excerpt($p['excerpt'], 130)); ?></p><span class="link-arrow">Read story</span></div></a></article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
      <?php if ($page > 1): ?><a href="<?php echo ui_e($link(['page' => $page - 1])); ?>" rel="prev">&larr;</a><?php endif; ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?><?php if ($i === $page): ?><span class="current" aria-current="page"><?php echo $i; ?></span><?php else: ?><a href="<?php echo ui_e($link(['page' => $i])); ?>"><?php echo $i; ?></a><?php endif; ?><?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?php echo ui_e($link(['page' => $page + 1])); ?>" rel="next">&rarr;</a><?php endif; ?>
    </nav>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
