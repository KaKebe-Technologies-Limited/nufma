<?php
require __DIR__ . "/includes/db.php";

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$stmt = $conn->prepare("SELECT * FROM posts WHERE slug=? AND is_published=1 LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: news.php");
    exit;
}

$conn->query("UPDATE posts SET views = views + 1 WHERE id = " . (int)$post['id']);

$pageTitle = htmlspecialchars($post['title']) . " | NUFA News";
$pageDesc  = htmlspecialchars($post['excerpt']);
$activeNav = "news";
$base = "";
$ogImage = "assets/images/" . $post['image'];
$canonicalPath = "post.php?slug=" . $post['slug'];
include __DIR__ . "/includes/header.php";
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "NewsArticle",
  "headline": <?php echo json_encode($post['title']); ?>,
  "description": <?php echo json_encode($post['excerpt']); ?>,
  "image": [<?php echo json_encode($siteUrl . "/assets/images/" . $post['image']); ?>],
  "datePublished": "<?php echo date('c', strtotime($post['published_at'])); ?>",
  "author": {"@type": "Organization", "name": <?php echo json_encode($post['author']); ?>},
  "publisher": {
    "@type": "Organization",
    "name": "NUFA — Northern Uganda Filmmakers Association",
    "logo": {"@type": "ImageObject", "url": "<?php echo $siteUrl; ?>/assets/images/logo/nufa-logo.png"}
  }
}
</script>
<?php

$related = [];
$stmt2 = $conn->prepare("SELECT * FROM posts WHERE is_published=1 AND id != ? ORDER BY published_at DESC LIMIT 3");
$stmt2->bind_param("i", $post['id']);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($r = $res2->fetch_assoc()) $related[] = $r;
?>
<a id="top"></a>

<section class="page-hero" style="min-height:200px">
  <div class="page-hero-bg"><img src="assets/images/<?php echo htmlspecialchars($post['image']); ?>" alt="" aria-hidden="true"></div>
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><a href="news.php">News</a><span>/</span><span><?php echo htmlspecialchars($post['category']); ?></span></div>
  </div>
</section>

<article class="section bg-paper">
  <div class="container" style="max-width:900px">
    <div class="article-meta-row" data-reveal>
      <span class="news-tag"><?php echo htmlspecialchars($post['category']); ?></span>
      <span class="news-date"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 3v4M17 3v4M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"/></svg> <?php echo date("d F Y", strtotime($post['published_at'])); ?></span>
      <span class="news-date">By <?php echo htmlspecialchars($post['author']); ?></span>
    </div>
    <h1 style="max-width:22ch;margin-bottom:28px" data-reveal><?php echo htmlspecialchars($post['title']); ?></h1>

    <div class="article-hero-img" data-reveal>
      <img src="assets/images/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
    </div>

    <div class="article-body" data-reveal>
      <?php echo $post['body']; ?>
    </div>

    <div style="max-width:74ch;margin:40px auto 0;display:flex;gap:12px;flex-wrap:wrap">
      <a href="news.php" class="btn-ghost">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="transform:rotate(180deg)"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Back to Newsroom
      </a>
    </div>
  </div>
</article>

<?php if (count($related)): ?>
<section class="section-tight bg-cream2">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">Keep Reading</div>
      <h2 style="margin-top:12px">More from NUFA</h2>
    </div>
    <div class="grid-3" data-reveal-stagger>
      <?php foreach ($related as $r): ?>
      <article class="news-card">
        <a href="post.php?slug=<?php echo urlencode($r['slug']); ?>">
          <div class="nc-media"><img src="assets/images/<?php echo htmlspecialchars($r['image']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" loading="lazy"></div>
          <div class="nc-body">
            <span class="news-tag"><?php echo htmlspecialchars($r['category']); ?></span>
            <h3><?php echo htmlspecialchars($r['title']); ?></h3>
            <p><?php echo htmlspecialchars($r['excerpt']); ?></p>
          </div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . "/includes/footer.php"; ?>
