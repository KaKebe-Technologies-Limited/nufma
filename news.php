<?php
$pageTitle = "NUFA News — Awards Recaps, Training & Updates";
$pageDesc  = "The latest from NUFA: awards recaps, training programmes, new partnerships and the road to the NUFA Awards 2027 across Northern Uganda's film community.";
$activeNav = "news";
$base = "";
$canonicalPath = "news.php";
$ogImage = "assets/images/og/og-news.jpg";
$ogImageAlt = "A NUFA film crew recording on location with a boom mic";
require __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";
?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Home","item":"<?php echo $siteUrl; ?>/index.php"},
  {"@type":"ListItem","position":2,"name":"News","item":"<?php echo $siteUrl; ?>/news.php"}
]}
</script>
<?php
$all = [];
$res = $conn->query("SELECT * FROM posts WHERE is_published=1 ORDER BY published_at DESC");
while ($row = $res->fetch_assoc()) $all[] = $row;

$featured = null;
foreach ($all as $p) { if ($p['is_featured']) { $featured = $p; break; } }
if (!$featured && count($all)) $featured = $all[0];

$rest = array_values(array_filter($all, function($p) use ($featured) { return $p['id'] != $featured['id']; }));
$miniFour = array_slice($rest, 0, 4);
$popular  = array_slice($all, 0, 6);
$listMain = array_slice($rest, 4, 6);
$sidebarPicks = array_slice($rest, 10, 4);
if (count($sidebarPicks) < 3) $sidebarPicks = array_slice($rest, 0, 4);

function newsDate($d){ return date("d M Y", strtotime($d)); }
?>
<a id="top"></a>

<section class="page-hero" style="min-height:220px">
  <div class="page-hero-bg"><img src="assets/images/gallery/2025/nufa25-16.webp" alt="" aria-hidden="true"></div>
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>News</span></div>
    <div class="eyebrow">Newsroom</div>
    <h1 style="margin-top:12px">What's happening at NUFA</h1>
  </div>
</section>

<section class="section-tight bg-paper">
  <div class="container">

    <?php if ($featured): ?>
    <div class="news-featured-grid" data-reveal style="margin-bottom:56px">
      <a class="nf-feat" href="post.php?slug=<?php echo urlencode($featured['slug']); ?>">
        <img src="assets/images/<?php echo htmlspecialchars($featured['image']); ?>" alt="<?php echo htmlspecialchars($featured['title']); ?>">
        <div class="nf-feat-body">
          <span class="news-tag"><?php echo htmlspecialchars($featured['category']); ?></span>
          <h2><?php echo htmlspecialchars($featured['title']); ?></h2>
          <p><?php echo htmlspecialchars($featured['excerpt']); ?></p>
        </div>
      </a>
      <div class="nf-mini-list">
        <?php foreach ($miniFour as $p): ?>
        <a class="nf-mini" href="post.php?slug=<?php echo urlencode($p['slug']); ?>">
          <div class="nf-thumb"><img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy"></div>
          <div class="nf-mini-body">
            <span class="news-tag"><?php echo htmlspecialchars($p['category']); ?></span>
            <h4><?php echo htmlspecialchars($p['title']); ?></h4>
            <span class="news-date"><?php echo newsDate($p['published_at']); ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="section-head" style="margin-bottom:24px">
      <div class="eyebrow">Popular</div>
    </div>
    <div class="popular-row" data-reveal style="margin-bottom:56px">
      <?php foreach ($popular as $p): ?>
      <a class="pop-card" href="post.php?slug=<?php echo urlencode($p['slug']); ?>">
        <div class="pop-thumb"><img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy"></div>
        <div class="pop-body">
          <span><?php echo newsDate($p['published_at']); ?></span>
          <h5><?php echo htmlspecialchars($p['title']); ?></h5>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="news-split">
      <div>
        <div class="section-head" style="margin-bottom:8px">
          <div class="eyebrow">More News</div>
        </div>
        <?php foreach ($listMain as $p): ?>
        <a class="news-list-item" href="post.php?slug=<?php echo urlencode($p['slug']); ?>">
          <div class="nl-thumb"><img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy"></div>
          <div>
            <span class="news-tag"><?php echo htmlspecialchars($p['category']); ?></span>
            <h4><?php echo htmlspecialchars($p['title']); ?></h4>
            <p><?php echo htmlspecialchars($p['excerpt']); ?></p>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <aside>
        <div class="sidebar-card">
          <h4>Editor's Picks</h4>
          <div class="sidebar-list">
            <?php foreach ($sidebarPicks as $p): ?>
            <a class="sidebar-item" href="post.php?slug=<?php echo urlencode($p['slug']); ?>">
              <div class="si-thumb"><img src="assets/images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy"></div>
              <h5><?php echo htmlspecialchars($p['title']); ?></h5>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="sidebar-newsletter">
          <h4>Stay Updated</h4>
          <p>Get NUFA news, award updates and training dates straight to your inbox.</p>
          <form action="subscribe.php" method="post">
            <input type="email" name="email" placeholder="you@email.com" required>
            <button type="submit" class="btn btn-brand" style="width:100%">Subscribe</button>
          </form>
        </div>
      </aside>
    </div>

  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
