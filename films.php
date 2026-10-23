<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/films.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
film_install($conn);
$S = film_settings($conn);
$pageTitle = "Film Showcase — Watch Films by Northern Ugandan Filmmakers | NUFA";
$pageDesc  = "Watch short films and productions from filmmakers in Northern Uganda and beyond, reviewed by NUFA — and support the filmmakers you love.";
$activeNav = "films"; $canonicalPath = "films.php";
include __DIR__ . "/includes/header.php";
$films = $conn->query("SELECT * FROM films WHERE status='approved' ORDER BY is_featured DESC, sort_order, approved_at DESC, id DESC")->fetch_all(MYSQLI_ASSOC);
$genres = []; foreach ($films as $f) if ($f['genre'] !== '') $genres[$f['genre']] = ($genres[$f['genre']] ?? 0) + 1;
$feat = null; foreach ($films as $f) if ($f['is_featured']) { $feat = $f; break; }
echo ui_page_hero('Resources', 'Film Showcase', $S['film_intro'], 'gallery/2026/nufa26-31.jpg', [['Resources', 'resources.php'], ['Film Showcase']],
    '<a href="#films" class="btn btn-gold">Watch films</a><a href="film-submit.php" class="btn btn-outline">Submit your film</a>');
$card = function ($f) { ob_start(); ?>
  <a class="fm-card" href="film.php?slug=<?php echo ui_e($f['slug']); ?>" data-genre="<?php echo ui_e($f['genre']); ?>" data-hay="<?php echo ui_e(strtolower($f['title'] . ' ' . $f['fm_name'] . ' ' . $f['genre'] . ' ' . $f['language'] . ' ' . $f['synopsis'])); ?>">
    <div class="fm-poster"><?php if (film_poster_ok($f)): ?><img src="assets/images/<?php echo ui_e($f['poster']); ?>" alt="" loading="lazy"><?php else: ?><span><?php echo ui_e($f['title']); ?></span><?php endif; ?>
      <?php if ($f['runtime_min']): ?><em class="fm-run"><?php echo ui_e(film_runtime($f)); ?></em><?php endif; ?><i class="fm-play"><svg viewBox="0 0 24 24" width="20" height="20"><path d="M8 5v14l11-7z" fill="currentColor"/></svg></i></div>
    <div class="fm-body"><h3><?php echo ui_e($f['title']); ?></h3><p class="fm-by">by <?php echo ui_e($f['fm_name']); ?></p><p><?php echo ui_e(trim($f['genre'] . ($f['year'] ? ' · ' . $f['year'] : '') . ($f['language'] !== '' ? ' · ' . $f['language'] : ''), ' ·')); ?></p></div>
  </a><?php return ob_get_clean(); };
?>
<section class="section" id="films"><div class="container">
  <?php if (!$films): ?>
    <div class="empty-state"><h3>The first films are coming soon</h3><p>Filmmakers are submitting their work and every film is reviewed by NUFA before it appears here. Are you a filmmaker?</p><a class="btn btn-gold" href="film-submit.php">Submit your film</a></div>
  <?php else: ?>
    <?php if ($feat): ?><a class="fs-feature" href="film.php?slug=<?php echo ui_e($feat['slug']); ?>"><div class="fs-fimg"><?php if (film_poster_ok($feat)): ?><img src="assets/images/<?php echo ui_e($feat['poster']); ?>" alt=""><?php endif; ?></div><div class="fs-ftxt"><span class="eyebrow">Featured film</span><h2><?php echo ui_e($feat['title']); ?></h2><p><?php echo ui_e(ui_excerpt($feat['synopsis'], 220)); ?></p><p class="fm-by">by <?php echo ui_e($feat['fm_name']); ?> · <?php echo ui_e(film_runtime($feat)); ?></p><span class="btn btn-gold">▶ Watch now</span></div></a><?php endif; ?>
    <div class="op-tools"><div class="filter-bar"><button class="op-tab is-active" type="button" data-g="">All <small><?php echo count($films); ?></small></button><?php foreach ($genres as $g => $n): ?><button class="op-tab" type="button" data-g="<?php echo ui_e($g); ?>"><?php echo ui_e($g); ?> <small><?php echo $n; ?></small></button><?php endforeach; ?></div>
      <div class="op-search"><input type="search" id="fm-q" placeholder="Search films or filmmakers" aria-label="Search films"></div></div>
    <div class="fm-grid" id="fm-grid"><?php foreach ($films as $f) echo $card($f); ?></div>
    <p class="empty-state" id="fm-none" hidden>No films match. Try another filter.</p>
    <div class="op-cta" data-reveal><div><h3>Made a film?</h3><p>Submit it for review. Approved films are shown here with the NUFA logo, and viewers can support you directly.</p></div><a class="btn btn-gold" href="film-submit.php">Submit your film</a></div>
  <?php endif; ?>
</div></section>
<script>
(function(){var g='',cards=[].slice.call(document.querySelectorAll('.fm-card')),q=document.getElementById('fm-q'),none=document.getElementById('fm-none');if(!q)return;
function run(){var t=(q.value||'').toLowerCase().trim(),n=0;cards.forEach(function(c){var ok=(!g||c.dataset.genre===g)&&(!t||c.dataset.hay.indexOf(t)>-1);c.hidden=!ok;if(ok)n++;});none.hidden=n>0;}
document.querySelectorAll('.op-tab').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.op-tab').forEach(function(x){x.classList.remove('is-active')});b.classList.add('is-active');g=b.dataset.g;run();});});q.addEventListener('input',run);})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
