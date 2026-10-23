<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/films.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
film_install($conn);
$ref = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['ref'] ?? ''); $t = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
$film = null;
if ($ref !== '' && $t !== '') { $s = $conn->prepare("SELECT * FROM films WHERE ref=? AND token_hash=?"); $h = hash('sha256', $t); $s->bind_param("ss", $ref, $h); $s->execute(); $film = $s->get_result()->fetch_assoc(); }
$pageTitle = "Film submission status — NUFA Film Showcase"; $pageDesc = "Check the review status of your film."; $activeNav = "films"; $canonicalPath = "film-status.php"; $noindex = true;
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Film Showcase', 'Submission status', '', 'gallery/2026/nufa26-31.jpg', [['Film Showcase', 'films.php'], ['Status']]);
$label = ['pending' => ['Waiting for review', 'Our team is reviewing your film. This can take a few days.'], 'approved' => ['Approved', 'Your film is live on the Film Showcase.'], 'rejected' => ['Not approved', ''], 'changes' => ['Changes needed', '']];
?>
<section class="section"><div class="container" style="max-width:720px">
<?php if (!$film): ?><div class="empty-state"><h3>We could not find that submission</h3><p>Please use the private link we emailed you when you submitted your film.</p><a class="btn btn-outline" href="films.php">Back to the Film Showcase</a></div>
<?php else: $st = $film['status']; ?>
  <div class="ac-result <?php echo $st === 'approved' ? 'is-pass' : ($st === 'rejected' ? 'is-fail' : ''); ?>"><p class="muted" style="margin:0"><?php echo ui_e($film['ref']); ?></p><h2 style="margin:6px 0"><?php echo ui_e($film['title']); ?></h2>
    <p><b><?php echo ui_e($label[$st][0] ?? ucfirst($st)); ?></b></p><p><?php echo ui_e($label[$st][1] ?? ''); ?></p>
    <?php if (in_array($st, ['rejected', 'changes'], true) && $film['reject_reason'] !== ''): ?><p style="white-space:pre-line;text-align:left;background:#fff;border-radius:12px;padding:14px"><b>Message from the NUFA team:</b><br><?php echo ui_e($film['reject_reason']); ?></p><a class="btn btn-gold" href="film-submit.php">Submit a new version</a><?php endif; ?>
    <?php if ($st === 'approved'): ?><a class="btn btn-gold" href="film.php?slug=<?php echo ui_e($film['slug']); ?>">Watch it on the website</a><?php endif; ?></div>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
