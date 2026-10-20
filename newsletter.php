<?php
require_once __DIR__ . "/includes/newsletter.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
nl_install($conn);

$slug = preg_replace('/[^a-z0-9-]/i', '', $_GET['slug'] ?? '');
$st = $conn->prepare("SELECT * FROM newsletters WHERE slug=? AND status='published' AND (published_at IS NULL OR published_at <= NOW()) LIMIT 1");
$st->bind_param("s", $slug); $st->execute();
$n = $st->get_result()->fetch_assoc();
if (!$n) { http_response_code(404); include __DIR__ . "/404.php"; exit; }

$cover = $n['cover_image'] !== '' && is_file(__DIR__ . '/assets/images/' . $n['cover_image']) ? $n['cover_image'] : '';
$pdf = $n['pdf_file'] !== '' && is_file(__DIR__ . '/assets/newsletters/' . $n['pdf_file']) ? 'assets/newsletters/' . rawurlencode($n['pdf_file']) : '';
$posts = nl_posts($conn, $n['post_ids']);
$others = $conn->query("SELECT slug, title, issue_label, published_at FROM newsletters WHERE status='published' AND id<>" . (int)$n['id'] . " AND (published_at IS NULL OR published_at <= NOW()) ORDER BY published_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);

$pageTitle = $n['title'] . " | NUFA Newsletter";
$pageDesc  = ui_excerpt($n['summary'] ?: $n['body'], 155);
$activeNav = "news";
$canonicalPath = "newsletter.php?slug=" . $n['slug'];
if ($cover) $ogImage = "assets/images/" . $cover;
include __DIR__ . "/includes/header.php";
?>
<section class="page-hero" style="padding-block:clamp(44px,6vw,76px)">
  <div class="page-hero-bg"><img src="assets/images/<?php echo ui_e($cover ?: 'slider/hero/hero-slide-3.webp'); ?>" alt="" fetchpriority="high"></div>
  <div class="container article-head">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="index.php">Home</a><span aria-hidden="true">/</span><a href="news.php">News &amp; Stories</a><span aria-hidden="true">/</span><a href="newsletters.php">Newsletter</a></nav>
    <div class="article-meta"><span class="chip chip--dark" style="border:1px solid var(--gold)"><?php echo ui_e($n['issue_label'] ?: 'Newsletter'); ?></span><span><?php echo ui_date($n['published_at']); ?></span></div>
    <h1 style="max-width:22ch"><?php echo ui_e($n['title']); ?></h1>
    <?php if ($n['summary'] !== ''): ?><p><?php echo ui_e($n['summary']); ?></p><?php endif; ?>
    <?php if ($pdf): ?><div class="actions" style="margin-top:18px"><a class="btn btn-gold" href="<?php echo ui_e($pdf); ?>" target="_blank" rel="noopener" download>Download the PDF</a></div><?php endif; ?>
  </div>
</section>

<article class="section">
  <div class="container">
    <?php if (trim(strip_tags((string)$n['body'], '<img>')) !== ''): ?><div class="article-body"><?php echo $n['body']; ?></div><?php endif; ?>
    <?php if ($pdf): ?>
      <div class="pdf-viewer" data-reveal>
        <object data="<?php echo ui_e($pdf); ?>#toolbar=1&navpanes=0" type="application/pdf" width="100%" height="780"><div class="info-card"><p>Your browser cannot show the PDF here.</p><a class="btn btn-gold" href="<?php echo ui_e($pdf); ?>" target="_blank" rel="noopener">Open the PDF</a></div></object>
      </div>
    <?php endif; ?>
    <?php if ($posts): ?>
      <div class="section-head" style="margin-top:56px"><div class="eyebrow">In this issue</div><h2>Stories in this newsletter</h2></div>
      <div class="grid grid-3" data-reveal-stagger>
        <?php foreach ($posts as $r): ?>
        <article class="card"><a href="post.php?slug=<?php echo urlencode($r['slug']); ?>" style="display:flex;flex-direction:column;height:100%"><div class="card-media"><?php echo ui_img($r['image'], ''); ?></div>
          <div class="card-body"><div class="card-meta"><b><?php echo ui_e($r['category']); ?></b><span><?php echo ui_date($r['published_at']); ?></span></div><h3><?php echo ui_e($r['title']); ?></h3><span class="link-arrow">Read story</span></div></a></article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p style="max-width:70ch;margin:30px auto 0"><a href="newsletters.php" class="link-arrow" style="transform:none">All newsletters</a></p>
  </div>
</article>

<section class="section section--soft" id="subscribe"><div class="container narrow"><?php echo nl_subscribe_box(false, []); ?></div></section>
<?php if ($others): ?><section class="section--tight"><div class="container"><p class="center muted">More issues: <?php foreach ($others as $i => $o): ?><a class="hl" style="font-weight:700" href="newsletter.php?slug=<?php echo urlencode($o['slug']); ?>"><?php echo ui_e($o['issue_label'] ?: $o['title']); ?></a><?php echo $i < count($others) - 1 ? ' · ' : ''; ?><?php endforeach; ?></p></div></section><?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
