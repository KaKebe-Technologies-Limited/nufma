<?php
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/schema.php";
require_once __DIR__ . "/includes/guests.php";
guests_install($conn);

$slug = preg_replace('/[^a-z0-9-]/i', '', $_GET['slug'] ?? '');
$st = $conn->prepare("SELECT * FROM posts WHERE slug=? AND is_published=1 LIMIT 1");
$st->bind_param("s", $slug);
$st->execute();
$post = $st->get_result()->fetch_assoc();
if (!$post) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$conn->query("UPDATE posts SET views = views + 1 WHERE id = " . (int)$post['id']);
$gallery = $conn->query("SELECT image, caption FROM post_images WHERE post_id=" . (int)$post['id'] . " ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$gallery = array_values(array_filter($gallery, fn($g) => is_file(__DIR__ . '/assets/images/' . $g['image'])));
$evWhen = story_when($post); $evTime = trim($post['event_time'] ?? ''); $evVenue = trim($post['event_venue'] ?? ''); $evMap = trim($post['event_map_url'] ?? '');

$rel = $conn->prepare("SELECT title, slug, category, image, published_at FROM posts WHERE is_published=1 AND id<>? ORDER BY (category=?) DESC, published_at DESC LIMIT 3");
$rel->bind_param("is", $post['id'], $post['category']);
$rel->execute();
$related = $rel->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = $post['title'] . " | NUFA News & Stories";
$pageDesc  = ui_excerpt($post['excerpt'] ?: $post['body'], 155);
$activeNav = "news";
$canonicalPath = "post.php?slug=" . $post['slug'];
$ogImage = "assets/images/" . $post['image'];
$ogType = "article";
$siteForLd = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$schemaExtra = ['@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => $post['title'], 'datePublished' => date('c', strtotime($post['published_at'])),
    'image' => [$siteForLd . '/assets/images/' . $post['image']], 'author' => ['@type' => 'Organization', 'name' => $post['author'] ?: 'NUFA'],
    'publisher' => ['@type' => 'Organization', 'name' => 'Northern Uganda Filmmakers Association']];
include __DIR__ . "/includes/header.php";

$shareUrl = urlencode($canonical);
$shareText = urlencode($post['title']);
?>
<section class="page-hero" style="padding-block:clamp(48px,7vw,84px)">
  <div class="page-hero-bg"><img src="assets/images/<?php echo ui_e($post['image']); ?>" alt="" fetchpriority="high"></div>
  <div class="container article-head">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a><span aria-hidden="true">/</span><a href="news.php">News &amp; Stories</a><span aria-hidden="true">/</span><a href="news.php?cat=<?php echo urlencode($post['category']); ?>"><?php echo ui_e($post['category']); ?></a></nav>
    <div class="article-meta"><span class="chip chip--dark" style="border:1px solid var(--gold)"><?php echo ui_e($post['category']); ?></span><span><?php echo ui_date($post['published_at']); ?></span><span>By <?php echo ui_e($post['author'] ?: 'NUFA Team'); ?></span></div>
    <h1 style="max-width:22ch"><?php echo ui_e($post['title']); ?></h1>
    <?php if ($post['excerpt']): ?><p><?php echo ui_e($post['excerpt']); ?></p><?php endif; ?>
  </div>
</section>

<article class="section">
  <div class="container">
    <figure class="article-figure" data-reveal>
      <img src="assets/images/<?php echo ui_e($post['image']); ?>" alt="<?php echo ui_e($post['image_caption'] ?: $post['title']); ?>" width="1600" height="900">
      <?php if (!empty($post['image_caption']) || !empty($post['image_credit'])): ?><figcaption><?php echo ui_e($post['image_caption'] ?? ''); ?><?php echo !empty($post['image_credit']) ? ' <em>Photo: ' . ui_e($post['image_credit']) . '</em>' : ''; ?></figcaption><?php endif; ?>
    </figure>
    <?php if ($evWhen !== '' || $evTime !== '' || $evVenue !== ''): ?>
    <div class="ev-panel" data-reveal>
      <?php if ($evWhen !== ''): ?><div><b>Date</b><span><?php echo ui_e($evWhen); ?></span></div><?php endif; ?>
      <?php if ($evTime !== ''): ?><div><b>Time</b><span><?php echo ui_e($evTime); ?></span></div><?php endif; ?>
      <?php if ($evVenue !== ''): ?><div><b>Venue</b><span><?php echo ui_e($evVenue); ?><?php if ($evMap !== ''): ?> · <a href="<?php echo ui_e($evMap); ?>" target="_blank" rel="noopener">Map</a><?php endif; ?></span></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="article-body"><?php echo $post['body']; ?></div>
    <?php if ($gallery): ?>
    <div class="story-gallery" data-reveal><h2>Photo gallery <small><?php echo count($gallery); ?> photos</small></h2>
      <div class="sg-grid"><?php foreach ($gallery as $gi): $cap = $gi['caption'] !== '' ? $gi['caption'] : $post['title']; ?><div class="sg-item"><img src="assets/images/<?php echo ui_e($gi['image']); ?>" data-lightbox="assets/images/<?php echo ui_e($gi['image']); ?>" data-caption="<?php echo ui_e($cap); ?>" alt="<?php echo ui_e($cap); ?>" loading="lazy" decoding="async"></div><?php endforeach; ?></div>
    </div>
    <?php endif; ?>
<?php
$pv = ui_video_embed($post['video_url'] ?? ''); $pa = trim($post['attachment'] ?? '');
$paOk = $pa !== '' && is_file(__DIR__ . '/assets/downloads/' . $pa);
if ($pv || $paOk): ?>
    <div class="story-extras">
      <?php if ($pv): ?><div class="video-card"><div class="frame"><iframe src="<?php echo ui_e($pv); ?>" title="Video: <?php echo ui_e($post['title']); ?>" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div><?php endif; ?>
      <?php if ($paOk): ?><div class="attach-card"><div><b><?php echo ui_e($post['attachment_title'] ?: 'Download the PDF'); ?></b><span>PDF document</span></div><a class="btn btn-gold btn-sm" href="assets/downloads/<?php echo ui_e(rawurlencode($pa)); ?>" target="_blank" rel="noopener" download>Download</a></div><?php endif; ?>
    </div>
<?php endif; ?>

    <div class="share-row" aria-label="Share this article">
      <span>Share:</span>
      <a href="https://wa.me/?text=<?php echo $shareText . '%20' . $shareUrl; ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" target="_blank" rel="noopener">Facebook</a>
      <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&amp;text=<?php echo $shareText; ?>" target="_blank" rel="noopener">X</a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>" target="_blank" rel="noopener">LinkedIn</a>
      <a href="mailto:?subject=<?php echo $shareText; ?>&amp;body=<?php echo $shareUrl; ?>">Email</a>
    </div>
    <p style="max-width:70ch;margin:30px auto 0"><a href="news.php" class="link-arrow" style="transform:none">Back to News &amp; Stories</a></p>
  </div>
</article>

<?php if ($related): ?>
<section class="section section--soft">
  <div class="container">
    <div class="section-head center"><div class="eyebrow">Keep reading</div><h2>Related stories</h2></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($related as $r): ?>
      <article class="card"><a href="post.php?slug=<?php echo urlencode($r['slug']); ?>" style="display:flex;flex-direction:column;height:100%"><div class="card-media"><?php echo ui_img($r['image'], ''); ?></div>
        <div class="card-body"><div class="card-meta"><b><?php echo ui_e($r['category']); ?></b><span><?php echo ui_date($r['published_at']); ?></span></div><h3><?php echo ui_e($r['title']); ?></h3><span class="link-arrow">Read story</span></div></a></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<section class="section--tight"><div class="container"><div class="status-banner" role="note" style="align-items:center"><div style="flex:1"><b>Enjoyed this story?</b> Get NUFA news in your inbox.</div><a class="btn btn-gold btn-sm" href="newsletters.php#subscribe">Subscribe to the newsletter</a></div></div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
