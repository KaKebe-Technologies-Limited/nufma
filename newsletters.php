<?php
require_once __DIR__ . "/includes/newsletter.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
nl_install($conn);

// unsubscribe link from an e-mail
$unsub = null;
if (preg_match('/^[a-f0-9]{32}$/', $_GET['unsub'] ?? '')) {
    $s = $conn->prepare("UPDATE subscribers SET unsubscribed=1 WHERE token=?"); $s->bind_param("s", $_GET['unsub']); $s->execute(); $unsub = $s->affected_rows > 0;
}

$subErrors = []; $subSent = isset($_GET['subscribed']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'subscribe') {
    if ($g = form_guard($conn, 'subscribe', 'subscribers')) $subErrors[] = $g;
    if (empty($_POST['consent'])) $subErrors[] = "Please tick the box to confirm you would like to receive the newsletter.";
    if (!$subErrors) {
        [$ok, $m] = nl_subscribe($conn, $_POST['email'] ?? '', $_POST['name'] ?? '', 'newsletter page');
        if ($ok) { header("Location: newsletters.php?subscribed=1#subscribe"); exit; }
        $subErrors[] = $m;
    }
}

$pageTitle = cms_value('seo', 'newsletters_title');
$pageDesc  = cms_value('seo', 'newsletters_desc');
$activeNav = "news";
$canonicalPath = "newsletters.php";
$ogImage = "assets/images/og/og-news.jpg";
include __DIR__ . "/includes/header.php";

$issues = $conn->query("SELECT * FROM newsletters WHERE status='published' AND (published_at IS NULL OR published_at <= NOW()) ORDER BY published_at DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
$latest = $issues[0] ?? null; $older = array_slice($issues, 1);
$coverOf = fn($n) => $n['cover_image'] !== '' && is_file(__DIR__ . '/assets/images/' . $n['cover_image']) ? $n['cover_image'] : '';
$pdfOf = fn($n) => $n['pdf_file'] !== '' && is_file(__DIR__ . '/assets/newsletters/' . $n['pdf_file']) ? 'assets/newsletters/' . rawurlencode($n['pdf_file']) : '';
echo ui_page_hero('Newsletter', cms_value('newsletters', 'hero_title'), cms_value('newsletters', 'hero_lede'), cms_img_raw('newsletters', 'hero_image'), [['News & Stories', 'news.php'], ['Newsletter']],
    '<a href="#subscribe" class="btn btn-gold">Subscribe</a><a href="news.php" class="btn btn-outline">News &amp; Stories</a>');
?>
<?php if ($unsub !== null): ?><section class="section--tight"><div class="container"><div class="alert <?php echo $unsub ? 'ok' : 'err'; ?>" role="status"><?php echo $unsub ? 'You have been unsubscribed from the NUFA newsletter. You are welcome back any time.' : 'We could not find that subscription — it may already be removed.'; ?></div></div></section><?php endif; ?>

<?php if (nufa_show('newsletters', 'latest')): ?><section class="section">
  <div class="container">
    <?php if (!$latest): ?>
      <div class="placeholder-card"><span class="ph-icon"><?php echo nufa_icon('mail', 22); ?></span><p><?php echo ui_e(cms_value('newsletters', 'empty_text')); ?></p></div>
    <?php else: $lc = $coverOf($latest); $lp = $pdfOf($latest); ?>
    <article class="nl-feature" data-reveal>
      <div class="nl-feature__cover"><?php if ($lc): ?><img src="assets/images/<?php echo ui_e($lc); ?>" alt="Cover of <?php echo ui_e($latest['title']); ?>"><?php else: ?><div class="nl-cover-ph"><?php echo nufa_icon('mail', 54); ?><b>NUFA</b><span>Newsletter</span></div><?php endif; ?></div>
      <div class="nl-feature__body">
        <span class="chip chip--dark">Latest issue<?php echo $latest['issue_label'] !== '' ? ' · ' . ui_e($latest['issue_label']) : ''; ?></span>
        <h2><?php echo ui_e($latest['title']); ?></h2>
        <p class="muted"><?php echo ui_date($latest['published_at']); ?></p>
        <?php if ($latest['summary'] !== ''): ?><p class="lede"><?php echo ui_e($latest['summary']); ?></p><?php endif; ?>
        <div class="actions"><a class="btn btn-gold" href="newsletter.php?slug=<?php echo urlencode($latest['slug']); ?>"><?php echo $latest['kind'] === 'pdf' ? 'Open the newsletter' : 'Read online'; ?></a><?php if ($lp): ?><a class="btn btn-outline-dark" href="<?php echo ui_e($lp); ?>" target="_blank" rel="noopener">Download PDF</a><?php endif; ?></div>
      </div>
    </article>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('newsletters', 'archive') && $older): ?><section class="section section--soft" id="archive">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Archive</div><h2><?php echo cms_t('newsletters', 'archive_heading'); ?></h2></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($older as $n): $c = $coverOf($n); $p = $pdfOf($n); ?>
      <article class="nl-card"><a class="nl-card__cover" href="newsletter.php?slug=<?php echo urlencode($n['slug']); ?>"><?php if ($c): ?><img src="assets/images/<?php echo ui_e($c); ?>" alt="" loading="lazy"><?php else: ?><div class="nl-cover-ph"><?php echo nufa_icon('mail', 40); ?><b>NUFA</b></div><?php endif; ?></a>
        <div class="nl-card__body"><span class="nl-card__issue"><?php echo ui_e($n['issue_label'] ?: ui_date($n['published_at'], 'F Y')); ?></span><h3><a href="newsletter.php?slug=<?php echo urlencode($n['slug']); ?>"><?php echo ui_e($n['title']); ?></a></h3>
          <?php if ($n['summary'] !== ''): ?><p class="muted"><?php echo ui_e(ui_excerpt($n['summary'], 130)); ?></p><?php endif; ?>
          <div class="nl-card__links"><a class="link-arrow" href="newsletter.php?slug=<?php echo urlencode($n['slug']); ?>">Read</a><?php if ($p): ?><a class="link-arrow" href="<?php echo ui_e($p); ?>" target="_blank" rel="noopener">PDF</a><?php endif; ?></div></div></article>
      <?php endforeach; ?>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('newsletters', 'subscribe')): ?><section class="section<?php echo $older ? '' : ' section--soft'; ?>" id="subscribe">
  <div class="container narrow"><?php echo nl_subscribe_box($subSent, $subErrors); ?></div>
</section><?php endif; ?>
<?php include __DIR__ . "/includes/footer.php"; ?>
