<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/films.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
film_install($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$S = film_settings($conn);
$slug = preg_replace('/[^a-z0-9-]/', '', $_GET['slug'] ?? '');
$st = $conn->prepare("SELECT * FROM films WHERE slug=? AND status='approved'"); $st->bind_param("s", $slug); $st->execute();
$film = $st->get_result()->fetch_assoc();
if (!$film) { http_response_code(404); include __DIR__ . "/404.php"; exit; }
$more = $conn->query("SELECT * FROM films WHERE status='approved' AND id<>" . (int)$film['id'] . " ORDER BY (genre='" . $conn->real_escape_string($film['genre']) . "') DESC, approved_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
$pageTitle = $film['title'] . " — Film Showcase | NUFA"; $pageDesc = ui_excerpt($film['synopsis'], 155); $activeNav = "films"; $canonicalPath = "film.php?slug=" . $film['slug'];
if (film_poster_ok($film)) $ogImage = "assets/images/" . $film['poster'];
$ogType = "video.movie";
$__keep = get_defined_vars();
include __DIR__ . "/includes/header.php";
foreach (["film", "S", "more", "slug"] as $__k) $$__k = $__keep[$__k];
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$credits = film_lines($film['credits']); $hasSupport = film_has_support($film);
$links = array_filter(['Website' => $film['website'], 'Instagram' => $film['instagram'], 'Facebook' => $film['facebook'], 'X' => $film['x_url'], 'YouTube' => $film['youtube']]);
$share = urlencode($canonical); $shareText = urlencode($film['title'] . ' — watch on NUFA Film Showcase');
$tab = ($_GET['tab'] ?? '') === 'support' ? 'support' : 'about';
?>
<section class="section section--dark" style="padding-block:clamp(26px,4vw,44px)"><div class="container fs-watch<?php echo $more ? "" : " fs-watch--solo"; ?>">
  <div>
    <nav class="breadcrumb" aria-label="Breadcrumb" style="margin-bottom:14px"><a href="index.php">Home</a><span aria-hidden="true">/</span><a href="films.php">Film Showcase</a><span aria-hidden="true">/</span><?php echo $h($film['title']); ?></nav>
    <?php echo film_player_html($conn, $film, $S); ?>
    <div class="fs-title"><div><h1 style="color:#fff"><?php echo $h($film['title']); ?></h1><p style="margin:4px 0 0;color:#cfcfcf">by <?php echo $h($film['fm_name']); ?></p></div>
      <?php if ($hasSupport): ?><button type="button" class="btn btn-gold" id="support-cta">♥ Support this filmmaker</button><?php endif; ?></div>
  </div>
  <aside class="fs-side" style="align-content:start"><?php if ($more): ?><h4 style="margin:0;color:#fff;font-size:.8rem;letter-spacing:.12em;text-transform:uppercase">More films</h4><?php foreach ($more as $m): ?>
    <a class="fs-card" href="film.php?slug=<?php echo $h($m['slug']); ?>"><?php if (film_poster_ok($m)): ?><img src="assets/images/<?php echo $h($m['poster']); ?>" alt="" loading="lazy"><?php else: ?><div class="fs-nop"></div><?php endif; ?><div><b><?php echo $h($m['title']); ?></b><span>by <?php echo $h($m['fm_name']); ?> · <?php echo $h(film_runtime($m)); ?></span></div></a><?php endforeach; endif; ?></aside>
</div></section>

<section class="section"><div class="container" style="max-width:900px">
  <div class="fs-meta"><?php foreach ([$film['genre'], $film['year'] ?: '', film_runtime($film), $film['language'], $film['rating']] as $m) if ($m !== '' && $m !== null) echo '<span>' . $h($m) . '</span>'; ?></div>
  <div class="fs-tabs" role="tablist"><button type="button" data-tab="about" class="<?php echo $tab === 'about' ? 'on' : ''; ?>">About</button><button type="button" data-tab="maker">The filmmaker</button><?php if ($credits): ?><button type="button" data-tab="credits">Credits</button><?php endif; ?><button type="button" data-tab="support" class="fs-support <?php echo $tab === 'support' ? 'on' : ''; ?>">♥ Support this filmmaker</button></div>

  <div class="fs-panel" data-panel="about" <?php echo $tab === 'about' ? '' : 'hidden'; ?>>
    <?php if ($film['tagline'] !== ''): ?><p style="font-size:1.15rem;font-weight:600"><?php echo $h($film['tagline']); ?></p><?php endif; ?>
    <p style="white-space:pre-line"><?php echo $h($film['synopsis']); ?></p>
    <div class="fs-facts"><?php foreach (['Genre' => $film['genre'], 'Year' => $film['year'] ?: '', 'Running time' => film_runtime($film), 'Language' => $film['language'], 'Subtitles' => $film['subtitles'], 'Made in' => $film['country'], 'Suitable for' => $film['rating']] as $k => $v) if ($v !== '' && $v !== null): ?><div><b><?php echo $h($k); ?></b><?php echo $h($v); ?></div><?php endif; ?></div>
    <?php if ($film['content_notes'] !== ''): ?><p class="muted"><b>Content notes:</b> <?php echo $h($film['content_notes']); ?></p><?php endif; ?>
    <p class="muted" style="font-size:.85rem">Reviewed and approved by NUFA<?php echo $film['approved_at'] ? ' on ' . $h(date('j F Y', strtotime($film['approved_at']))) : ''; ?>.</p>
    <div class="share-row" aria-label="Share this film"><span>Share:</span><a href="https://wa.me/?text=<?php echo $shareText . '%20' . $share; ?>" target="_blank" rel="noopener">WhatsApp</a><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share; ?>" target="_blank" rel="noopener">Facebook</a><a href="https://twitter.com/intent/tweet?url=<?php echo $share; ?>&amp;text=<?php echo $shareText; ?>" target="_blank" rel="noopener">X</a><a href="mailto:?subject=<?php echo $shareText; ?>&amp;body=<?php echo $share; ?>">Email</a></div>
  </div>

  <div class="fs-panel" data-panel="maker" hidden>
    <div class="fs-maker"><?php if (film_photo_ok($film)): ?><img src="assets/images/<?php echo $h($film['fm_photo']); ?>" alt="<?php echo $h($film['fm_name']); ?>"><?php else: ?><div class="fs-ph"><?php echo $h(mb_strtoupper(mb_substr($film['fm_name'], 0, 1))); ?></div><?php endif; ?>
      <div><h3 style="margin:0"><?php echo $h($film['fm_name']); ?></h3><?php if ($film['fm_location'] !== ''): ?><p class="muted" style="margin:2px 0 10px"><?php echo $h($film['fm_location']); ?></p><?php endif; ?><p style="white-space:pre-line"><?php echo $h($film['fm_bio']); ?></p>
        <?php if ($links): ?><div class="fs-links"><?php foreach ($links as $l => $u): ?><a href="<?php echo $h($u); ?>" target="_blank" rel="noopener nofollow"><?php echo $h($l); ?> ↗</a><?php endforeach; ?></div><?php endif; ?></div></div>
  </div>

  <?php if ($credits): ?><div class="fs-panel" data-panel="credits" hidden><ul class="ac-checks" style="list-style:none;padding:0"><?php foreach ($credits as $c) echo '<li>' . $h($c) . '</li>'; ?></ul></div><?php endif; ?>

  <div class="fs-panel" data-panel="support" <?php echo $tab === 'support' ? '' : 'hidden'; ?>>
    <div class="fs-support-box">
      <h3>♥ Loved “<?php echo $h($film['title']); ?>”? Support <?php echo $h($film['fm_name']); ?></h3>
      <?php if ($film['donate_note'] !== ''): ?><p style="font-size:1.05rem">“<?php echo $h($film['donate_note']); ?>”</p><?php endif; ?>
      <?php if ($hasSupport): ?>
      <div class="fs-pay">
        <?php foreach (['MTN Mobile Money' => $film['donate_mtn'], 'Airtel Money' => $film['donate_airtel']] as $l => $num) if ($num !== ''): ?><div><small><?php echo $h($l); ?></small><b><?php echo $h($num); ?></b><?php if ($film['donate_name'] !== ''): ?><span style="color:#cfcfcf;font-size:.85rem">Registered name: <?php echo $h($film['donate_name']); ?></span><br><?php endif; ?><button type="button" class="btn btn-outline btn-sm" data-copy="<?php echo $h(preg_replace('/[^0-9+]/', '', $num)); ?>">Copy number</button></div><?php endif; ?>
        <?php if ($film['donate_link'] !== ''): ?><div><small>Online</small><b style="font-size:1.05rem">Give online</b><a class="btn btn-gold btn-sm" href="<?php echo $h($film['donate_link']); ?>" target="_blank" rel="noopener nofollow"><?php echo $h($film['donate_link_label'] ?: 'Support this film'); ?> ↗</a></div><?php endif; ?>
        <?php if ($film['donate_bank'] !== ''): ?><div><small>Bank</small><b style="font-size:1rem;word-break:normal"><?php echo $h($film['donate_bank']); ?></b></div><?php endif; ?>
      </div>
      <?php else: ?><p>This filmmaker has not added support details yet. You can still help by sharing the film with friends and family.</p><?php endif; ?>
      <p class="fs-safe"><?php echo $h($S['film_donate_note']); ?></p>
    </div>
  </div>
</div></section>
<script src="assets/js/film-player.js" defer></script>
<script>
(function(){
  var tabs=document.querySelectorAll('.fs-tabs button'),panels=document.querySelectorAll('.fs-panel'),csrf='<?php echo $h(mem_csrf()); ?>',fid=<?php echo (int)$film['id']; ?>;
  function ping(k){try{var b=new URLSearchParams();b.set('id',fid);b.set('k',k);b.set('csrf',csrf);fetch('film-count.php',{method:'POST',body:b,credentials:'same-origin',keepalive:true});}catch(e){}}
  function show(n){tabs.forEach(function(t){t.classList.toggle('on',t.dataset.tab===n)});panels.forEach(function(p){p.hidden=p.dataset.panel!==n});if(n==='support')ping('support');}
  tabs.forEach(function(t){t.addEventListener('click',function(){show(t.dataset.tab);});});
  var cta=document.getElementById('support-cta');if(cta)cta.addEventListener('click',function(){show('support');document.querySelector('.fs-tabs').scrollIntoView({behavior:'smooth',block:'start'});});
  document.querySelectorAll('[data-copy]').forEach(function(b){b.addEventListener('click',function(){var v=b.dataset.copy;(navigator.clipboard?navigator.clipboard.writeText(v):Promise.reject()).then(function(){b.textContent='Copied ✓';}).catch(function(){window.prompt('Copy this number',v);});setTimeout(function(){b.textContent='Copy number';},2000);});});
  ping('view');<?php if ($tab === 'support'): ?>ping('support');<?php endif; ?>
})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
