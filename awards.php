<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/awards.php";
awd_install($conn);
require_once __DIR__ . "/includes/award-events.php";
aev_install($conn);
require_once __DIR__ . "/includes/outreach.php";
out_install($conn);
$AW = awd_settings($conn);

// ticket booking request
$tixErrors = []; $tixSent = isset($_GET['tix']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'tickets' && $AW['awd_tix_booking_form'] === '1') {
    if ($g = form_guard($conn, 'tickets')) $tixErrors[] = $g;
    $tn = form_clean($_POST['name'] ?? '', 150); $te = strtolower(form_clean($_POST['email'] ?? '', 190)); $tp = form_clean($_POST['phone'] ?? '', 40);
    $tt = form_clean($_POST['ticket_type'] ?? '', 120); $tq = max(1, min(50, (int)($_POST['qty'] ?? 1))); $tm = form_clean($_POST['message'] ?? '', 1000);
    if ($tn === '') $tixErrors[] = "Please enter your name.";
    if (!filter_var($te, FILTER_VALIDATE_EMAIL)) $tixErrors[] = "Please enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $tp)) < 9) $tixErrors[] = "Please enter a phone number we can reach you on.";
    if (empty($_POST['consent'])) $tixErrors[] = "Please consent to being contacted about your booking.";
    if (!$tixErrors) {
        $id = form_save($conn, 'tickets', $tt ?: 'Ticket request', $tn, '', $te, $tp, $tm, ['Ticket type' => $tt, 'Quantity' => (string)$tq], true);
        if ($id) {
            if (filter_var($AW['awd_tix_notify'], FILTER_VALIDATE_EMAIL)) mem_send($conn, null, 'tickets', $AW['awd_tix_notify'], "Ticket request — $tn", "Ticket request from $tn <$te>, $tp\nType: $tt\nQuantity: $tq\n\n$tm", mem_site_url() . '/admin/enquiry-view.php?id=' . $id, $te);
            header("Location: awards.php?tix=1#tickets"); exit;
        }
        $tixErrors[] = "We couldn't save your request just now. Please try again.";
    }
}
$pageTitle = cms_value('seo', 'awards_title');
$pageDesc  = cms_value('seo', 'awards_desc');
$activeNav = "awards";
$canonicalPath = "awards.php";
$ogImage = "assets/images/og/og-awards.jpg";
include __DIR__ . "/includes/header.php";

$editions = $conn->query("SELECT * FROM award_editions WHERE is_active=1 ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
$upcoming = array_values(array_filter($editions, fn($x) => $x['status'] === 'upcoming'));
$past     = array_values(array_filter($editions, fn($x) => $x['status'] !== 'upcoming'));
$awardNews = $conn->query("SELECT title, slug, category, excerpt, image, published_at FROM posts WHERE is_published=1 AND category='NUFA Awards' ORDER BY published_at DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$submission = trim(cms_value('awards', 'submission_url'));

echo ui_page_hero('NUFA Awards', cms_value('awards', 'hero_heading'), cms_value('awards', 'hero_lede'), cms_img_raw('awards', 'hero_image'), [['NUFA Awards']],
    '<a href="awards-cohost.php" class="btn btn-gold">Co-host or sponsor an event</a><a href="#archive" class="btn btn-outline">Explore the Archive</a><a href="contact.php?topic=Awards" class="btn btn-ghost">Awards enquiries</a>');
?>
<nav class="aw-subnav" aria-label="On this page"><div class="container"><?php foreach ([['events', 'Events'], ['buildup', 'Get involved'], ['roadmap', 'Road map'], ['winners', 'Winners'], ['highlights', 'Highlights'], ['moments', 'Key moments'], ['impact', 'Impact'], ['films', 'Films'], ['archive', 'Archive'], ['takepart', 'Take part'], ['submissions', 'Submit a film'], ['tickets', 'Tickets'], ['team', 'Technical team'], ['faq', 'FAQ']] as [$a, $l]): ?><a href="#<?php echo $a; ?>"><?php echo $l; ?></a><?php endforeach; ?></div></nav>

<?php if (nufa_show('awards', 'purpose')): ?><section class="section">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow">Purpose &amp; significance</div>
      <h2><?php echo cms_t('awards', 'purpose_heading'); ?></h2>
      <div class="lede stack"><?php echo ui_paragraphs(cms_value('awards', 'purpose_body')); ?></div>
    </div>
    <div data-reveal>
      <h3>On this platform</h3>
      <?php echo ui_list(cms_value('awards', 'platform_list')); ?>
    </div>
  </div>
</section><?php endif; ?>

<?php foreach ($upcoming as $u): ?>
<?php if (nufa_show('awards', 'upcoming')): ?><section class="section feature-band section--black" id="upcoming">
  <div class="bg"><img src="assets/images/<?php echo ui_e($u['image'] ?: 'slider/hero/hero-slide-3.webp'); ?>" alt="" loading="lazy"></div>
  <div class="container">
    <div style="max-width:720px" data-reveal>
      <span class="chip chip--dark" style="border:1px solid var(--gold)"><?php echo ui_e($u['badge_text'] ?: 'Upcoming Edition'); ?></span>
      <h2 style="margin-top:18px">NUFA Awards <span class="hl"><?php echo (int)$u['year']; ?></span></h2>
      <p class="lede" style="color:var(--muted-on-dark)"><?php echo ui_e($u['description']); ?></p>
      <ul class="meta-list" style="color:var(--muted-on-dark);margin:20px 0">
        <?php if ($u['event_date_label']): ?><li><?php echo nufa_icon('calendar', 18); ?><span><?php echo ui_e($u['event_date_label']); ?></span></li><?php endif; ?>
        <?php if ($u['location_label']): ?><li><?php echo nufa_icon('pin', 18); ?><span><?php echo ui_e($u['location_label']); ?></span></li><?php endif; ?>
      </ul>
      <?php if ($cfg['tickets_open'] === '1'): ?>
        <div class="notice notice--dark" style="margin-bottom:22px"><span><?php echo nufa_icon('star', 22); ?></span><div><b>Tickets</b><br>Ticket information is available from the NUFA team<?php echo $cfg['ticket_price'] !== '' ? ' — general admission UGX ' . ui_e($cfg['ticket_price']) : ''; ?>. Please enquire through the contact form.</div></div>
      <?php endif; ?>
      <div class="actions"><a href="award.php?e=<?php echo ui_e($u['slug']); ?>" class="btn btn-gold">Edition details</a><a href="partners.php#enquiry" class="btn btn-outline">Sponsorship opportunities</a></div>
    </div>
  </div>
</section><?php endif; ?>
<?php endforeach; ?>


<?php
// data for the detailed sections
$last2 = array_slice($past, 0, 2);
$last2Ids = array_column($last2, 'id');
$nextEd = $upcoming[0] ?? null;
$img = fn($rel) => $rel !== '' && is_file(__DIR__ . '/assets/images/' . $rel) ? $rel : '';
$ph = fn($text) => '<div class="placeholder-card"><span class="ph-icon">' . nufa_icon('star', 22) . '</span><p>' . ui_e($text) . '</p></div>';
$edLabel = function ($id) use ($editions) { foreach ($editions as $x) if ((int)$x['id'] === (int)$id) return 'NUFA Awards ' . (int)$x['year']; return ''; };
$winsBy = [];
if ($last2Ids) { $r = $conn->query("SELECT * FROM award_winners WHERE edition_id IN (" . implode(',', array_map('intval', $last2Ids)) . ") ORDER BY sort_order, id"); foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) $winsBy[$x['edition_id']][] = $x; }
$guests = awd_items($conn, 'guest', $last2Ids); $moments = awd_items($conn, 'moment', $last2Ids); $impacts = awd_items($conn, 'impact', $last2Ids);
$films = awd_items($conn, 'film', $last2Ids); $roadmap = awd_items($conn, 'roadmap', array_merge([0], $nextEd ? [(int)$nextEd['id']] : []));
?>

<?php if (nufa_show('awards', 'events')):
  $aev = $conn->query("SELECT * FROM award_events WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
  $aevSp = []; foreach ($conn->query("SELECT * FROM award_event_sponsors WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC) as $__s) $aevSp[$__s['event_id']][] = $__s;
  $AE = aev_settings($conn);
  $aevActs = []; foreach ($conn->query("SELECT name, act_type, assigned_event_id FROM award_acts WHERE status='approved' AND is_public=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC) as $__a) $aevActs[$__a['assigned_event_id']][] = $__a;
  if ($aev): ?>
<section class="section section--black" id="events">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Road to the awards</div><h2>Launch · Nomination Night · Grand Finale</h2><p><?php echo ui_e($AE['aev_intro']); ?></p>
      <div class="actions" style="justify-content:center;margin-top:18px"><a href="awards-cohost.php" class="btn btn-gold">Apply to co-host or sponsor</a></div></div>
    <div class="ev-grid" data-reveal-stagger>
      <?php foreach ($aev as $ev): $iso = aev_countdown_iso($ev); $confirmed = !empty($ev['date_confirmed']) && !empty($ev['event_date']); $past = $confirmed && strtotime($ev['event_date'] . ' 23:59:59') < time(); $evImg = $ev['image'] !== '' && is_file(__DIR__ . '/assets/images/' . $ev['image']) ? $ev['image'] : '';
        $sp = $aevSp[$ev['id']] ?? []; ?>
      <article class="ev-card" id="ev-<?php echo ui_e($ev['slug']); ?>">
        <?php if ($evImg): ?><div class="ev-img"><img src="assets/images/<?php echo ui_e($evImg); ?>" alt="" loading="lazy"></div><?php endif; ?>
        <div class="ev-body">
          <span class="ev-chip <?php echo $past ? 'is-past' : ($confirmed ? 'is-ok' : ''); ?>"><?php echo $past ? 'Completed' : ($confirmed ? 'Date confirmed' : 'Date to be announced'); ?></span>
          <h3><?php echo ui_e($ev['title']); ?></h3>
          <?php if ($ev['tagline'] !== ''): ?><p class="ev-tag"><?php echo ui_e($ev['tagline']); ?></p><?php endif; ?>
          <?php if ($iso !== '' && !$past): ?><div class="ev-countdown" data-countdown="<?php echo ui_e($iso); ?>" aria-live="off"><div><b data-u="d">--</b><span>days</span></div><div><b data-u="h">--</b><span>hours</span></div><div><b data-u="m">--</b><span>mins</span></div><div><b data-u="s">--</b><span>secs</span></div></div><?php endif; ?>
          <ul class="ev-facts">
            <li><?php echo nufa_icon('calendar', 17); ?><span><b>When</b><?php echo ui_e(aev_when($ev)); ?></span></li>
            <li><?php echo nufa_icon('pin', 17); ?><span><b>Venue</b><?php echo $ev['venue'] !== '' ? ui_e($ev['venue']) . ($ev['venue_note'] !== '' ? '<small>' . ui_e($ev['venue_note']) . '</small>' : '') : 'Venue to be announced'; ?><?php if ($ev['map_url'] !== ''): ?> · <a href="<?php echo ui_e($ev['map_url']); ?>" target="_blank" rel="noopener">Map</a><?php endif; ?></span></li>
          </ul>
          <?php if (trim((string)$ev['description']) !== ''): ?><p class="ev-desc"><?php echo ui_e($ev['description']); ?></p><?php endif; ?>
          <?php if ($sp): ?><div class="ev-sponsors"><h4>Co-hosts &amp; sponsors</h4><div class="ev-sp-row"><?php foreach ($sp as $s): $has = $s['logo'] !== '' && is_file(__DIR__ . '/assets/images/' . $s['logo']);
            $in = $has ? '<img src="assets/images/' . ui_e($s['logo']) . '" alt="' . ui_e($s['name']) . '" loading="lazy">' : '<b>' . ui_e($s['name']) . '</b>'; ?>
            <div class="ev-sp" title="<?php echo ui_e($s['name'] . ' — ' . $s['tier']); ?>"><?php echo $s['website'] !== '' ? '<a href="' . ui_e($s['website']) . '" target="_blank" rel="noopener">' . $in . '</a>' : $in; ?><span><?php echo ui_e($s['tier']); ?></span></div><?php endforeach; ?></div></div>
          <?php else: ?><p class="ev-nosp">Co-host and sponsor spots are open.</p><?php endif; ?>
          <?php $__ac = $aevActs[$ev['id']] ?? []; if ($__ac): ?><div class="ev-sponsors"><h4>On the line-up</h4><div class="ev-sp-row"><?php foreach ($__ac as $__a): ?><div class="ev-sp" title="<?php echo ui_e($__a['act_type']); ?>"><b><?php echo ui_e($__a['name']); ?></b><span><?php echo ui_e($__a['act_type']); ?></span></div><?php endforeach; ?></div></div><?php endif; ?>
          <?php if (!$past): ?><a class="btn btn-ghost btn-sm" href="awards-perform.php?event=<?php echo ui_e($ev['slug']); ?>">Perform at this event</a><?php endif; ?>
          <?php if (!$past && !empty($ev['cohost_open'])): ?><a class="btn btn-outline btn-sm" href="awards-cohost.php?event=<?php echo ui_e($ev['slug']); ?>">Co-host or sponsor this event</a><?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<script>
(function(){var els=document.querySelectorAll('[data-countdown]');if(!els.length)return;
function pad(n){return n<10?'0'+n:''+n}
function tick(){els.forEach(function(el){var t=Date.parse(el.getAttribute('data-countdown'));var d=t-Date.now();
 if(isNaN(t))return; if(d<=0){el.innerHTML='<div class="ev-live">It\'s happening — see you there!</div>';return;}
 var s=Math.floor(d/1000),dd=Math.floor(s/86400),hh=Math.floor(s%86400/3600),mm=Math.floor(s%3600/60),ss=s%60;
 var v={d:dd,h:pad(hh),m:pad(mm),s:pad(ss)};['d','h','m','s'].forEach(function(u){var b=el.querySelector('[data-u="'+u+'"]');if(b)b.textContent=v[u];});});}
tick();setInterval(tick,1000);})();
</script>
<?php endif; endif; ?>
<?php if (nufa_show('awards', 'buildup')):
  $OS = out_settings($conn);
  $bwork = $conn->query("SELECT * FROM award_workshops WHERE status='approved' AND is_public=1 ORDER BY COALESCE(confirmed_date, proposed_date) IS NULL, COALESCE(confirmed_date, proposed_date)")->fetch_all(MYSQLI_ASSOC);
  $bacts = $conn->query("SELECT name, act_type, assigned_event_id, photo FROM award_acts WHERE status='approved' AND is_public=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
  $evNames = []; foreach ($aev ?? [] as $__e) $evNames[$__e['id']] = $__e['title']; ?>
<section class="section" id="buildup">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Build the moment with us</div><h2>Be part of the road to the NUFA Awards</h2><p><?php echo ui_e($OS['bld_intro']); ?></p></div>
    <div class="bu-grid" data-reveal-stagger>
      <?php if ($OS['bld_acts_open'] === '1'): ?><div class="bu-card"><span class="bu-ic">♪</span><h3>Perform on the night</h3><p>Musicians, poets, spoken-word artists, dancers and cultural performers — showcase your act at the Launch, Nomination Night or Grand Finale.</p><a class="btn btn-gold btn-sm" href="awards-perform.php">Apply to perform</a></div><?php endif; ?>
      <?php if ($OS['bld_workshops_open'] === '1'): ?><div class="bu-card"><span class="bu-ic">✦</span><h3>Host a pre-event workshop</h3><p>Bring filmmakers together in your town. Tell us your venue, how many filmmakers you need and what you will contribute.</p><a class="btn btn-gold btn-sm" href="awards-workshop.php">Host a workshop</a></div><?php endif; ?>
      <div class="bu-card"><span class="bu-ic">★</span><h3>Become an ambassador</h3><p>Lobby for NUFA, share our proposal with donors, hold fundraising and meet the Board every month.</p><a class="btn btn-gold btn-sm" href="ambassadors.php">Become an ambassador</a></div>
      <div class="bu-card"><span class="bu-ic">♥</span><h3>Co-host or sponsor</h3><p>Put your name behind the Launch, Nomination Night or Grand Finale.</p><a class="btn btn-gold btn-sm" href="awards-cohost.php">Co-host or sponsor</a></div>
    </div>
    <?php if ($bacts): ?><h3 style="margin:44px 0 14px">Announced line-up</h3><div class="gs-grid"><?php foreach ($bacts as $a): ?><div class="gs-card"><div class="gs-photo" style="aspect-ratio:16/10"><?php if ($a['photo'] !== '' && is_file(__DIR__ . '/assets/images/' . $a['photo'])): ?><img src="assets/images/<?php echo ui_e($a['photo']); ?>" alt="<?php echo ui_e($a['name']); ?>" loading="lazy"><?php else: ?><span style="font-size:2rem">♪</span><?php endif; ?></div><div class="gs-body"><span class="gs-cat"><?php echo ui_e($evNames[$a['assigned_event_id']] ?? 'NUFA Awards'); ?></span><h3><?php echo ui_e($a['name']); ?></h3><p class="gs-title"><?php echo ui_e($a['act_type']); ?></p></div></div><?php endforeach; ?></div><?php endif; ?>
    <?php if ($bwork): ?><h3 style="margin:44px 0 14px">Pre-event workshops</h3><div class="ac-classes"><?php foreach ($bwork as $w): $d = $w['confirmed_date'] ?: $w['proposed_date']; ?>
      <div class="ac-class"><span class="ac-mode">Workshop</span><h3><?php echo ui_e($w['title']); ?></h3><p><?php echo $d ? ui_e(date('j F Y', strtotime($d))) . ($w['confirmed_date'] ? '' : ' (proposed)') : 'Date to be confirmed'; ?></p><p class="muted"><?php echo ui_e($w['venue']); ?>, <?php echo ui_e($w['town']); ?></p><p class="muted">Hosted by <?php echo ui_e($w['host_name']); ?> · looking for <b><?php echo (int)$w['filmmakers_needed']; ?></b> filmmakers</p><a class="btn btn-outline btn-sm" href="contact.php?topic=Awards&amp;message=<?php echo urlencode('I would like to join the workshop: ' . $w['title'] . ' (' . $w['town'] . ')'); ?>">I want to take part</a></div><?php endforeach; ?></div><?php endif; ?>
  </div>
</section>
<?php endif; ?>
<?php if (nufa_show('awards', 'roadmap')): ?><section class="section" id="roadmap">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Road map</div><h2><?php echo cms_t('awards', 'roadmap_heading'); ?><?php echo $nextEd ? ' — ' . (int)$nextEd['year'] : ''; ?></h2><p><?php echo cms_t('awards', 'roadmap_intro'); ?></p></div>
    <?php if (!$roadmap): echo $ph('The road map for the next edition will be published here.'); else: ?>
    <ol class="roadmap" data-reveal-stagger>
      <?php foreach ($roadmap as $i => $s): ?>
      <li class="roadmap-step roadmap-step--<?php echo ui_e($s['status']); ?>">
        <span class="roadmap-dot"><?php echo $s['status'] === 'done' ? '✓' : $i + 1; ?></span>
        <div class="roadmap-body"><span class="roadmap-when"><?php echo ui_e($s['date_label'] ?: 'To be announced'); ?></span><h3><?php echo ui_e($s['title']); ?></h3>
          <?php if ($s['body'] !== ''): ?><p><?php echo ui_e($s['body']); ?></p><?php endif; ?>
          <?php if ($s['link_url'] !== ''): ?><a class="link-arrow" href="<?php echo ui_e($s['link_url']); ?>" target="_blank" rel="noopener">More</a><?php endif; ?></div>
      </li>
      <?php endforeach; ?>
    </ol>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'teaser')):
  $tv = ui_video_embed(cms_value('awards', 'teaser_video_url')); $ti = cms_img_raw('awards', 'teaser_image'); ?>
<section class="section section--black teaser" id="teaser">
  <div class="container split">
    <div data-reveal><div class="eyebrow">Teaser</div><h2><?php echo cms_t('awards', 'teaser_heading'); ?></h2><p class="lede" style="color:var(--muted-on-dark)"><?php echo cms_t('awards', 'teaser_text'); ?></p>
      <div class="actions"><a href="#tickets" class="btn btn-gold">Get tickets</a><a href="#submissions" class="btn btn-outline">Submit your film</a></div></div>
    <div data-reveal>
      <?php if ($tv): ?><div class="video-card"><div class="frame"><iframe src="<?php echo ui_e($tv); ?>" title="NUFA Awards teaser" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div></div>
      <?php elseif ($img($ti)): ?><div class="media-frame"><img src="assets/images/<?php echo ui_e($ti); ?>" alt="A first look at the next NUFA Awards" loading="lazy"></div><?php endif; ?>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'winners')): ?><section class="section section--soft" id="winners">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Winners</div><h2><?php echo cms_t('awards', 'winners_heading'); ?></h2><p><?php echo cms_t('awards', 'winners_intro'); ?></p></div>
    <?php if (!$last2): echo $ph(cms_value('awards', 'winners_empty')); endif; ?>
    <?php foreach ($last2 as $ed): $ws = $winsBy[$ed['id']] ?? []; ?>
    <div class="ed-block" id="winners-<?php echo (int)$ed['year']; ?>">
      <div class="ed-block__head" data-reveal>
        <div><span class="chip chip--dark">NUFA Awards <?php echo (int)$ed['year']; ?></span><?php if ($ed['theme_quote']): ?><h3><?php echo ui_e($ed['theme_quote']); ?></h3><?php endif; ?>
          <p class="muted"><?php echo ui_e(trim($ed['event_date_label'] . ($ed['event_date_label'] && $ed['location_label'] ? ' · ' : '') . $ed['location_label'])); ?></p></div>
        <a class="link-arrow" href="award.php?e=<?php echo ui_e($ed['slug']); ?>">Full edition details</a>
      </div>
      <?php if (!$ws): echo $ph(cms_value('awards', 'winners_empty')); else: ?>
      <div class="win-grid" data-reveal-stagger>
        <?php foreach ($ws as $w): $wi = $img($w['image'] ?? ''); ?>
        <article class="win-card<?php echo $w['is_honorary'] ? ' win-card--hon' : ''; ?>">
          <?php if ($wi): ?><div class="win-img"><img src="assets/images/<?php echo ui_e($wi); ?>" alt="<?php echo ui_e($w['winner_title']); ?>" loading="lazy"></div><?php endif; ?>
          <div class="win-body"><span class="win-cat"><?php echo ui_e($w['category']); ?><?php echo $w['is_honorary'] ? ' · Honorary' : ''; ?></span>
            <h4><?php echo ui_e($w['winner_title']); ?></h4>
            <?php if ($w['person_name']): ?><p class="win-person"><?php echo ui_e($w['person_name']); ?></p><?php endif; ?>
            <?php if (!empty($w['detail'])): ?><p class="win-detail"><?php echo ui_e($w['detail']); ?></p><?php endif; ?></div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'highlights')): ?><section class="section" id="highlights">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Highlights &amp; guests</div><h2><?php echo cms_t('awards', 'highlights_heading'); ?></h2></div>
    <?php $anyHl = false; foreach ($last2 as $ed): if (trim(strip_tags($ed['highlights_html'] ?? '')) === '') continue; $anyHl = true; ?>
      <div class="hl-card" data-reveal><span class="chip chip--dark">NUFA Awards <?php echo (int)$ed['year']; ?> highlights</span><div class="stack"><?php echo $ed['highlights_html']; ?></div></div>
    <?php endforeach; ?>
    <?php if ($guests): ?>
      <h3 class="sub-title" data-reveal>Guests &amp; dignitaries</h3>
      <div class="guest-grid" data-reveal-stagger>
        <?php foreach ($guests as $g): $gi = $img($g['image']); ?>
        <article class="guest"><div class="guest-photo"><?php if ($gi): ?><img src="assets/images/<?php echo ui_e($gi); ?>" alt="<?php echo ui_e($g['title']); ?>" loading="lazy"><?php else: echo nufa_icon('users', 34); endif; ?></div>
          <h4><?php echo ui_e($g['title']); ?></h4><?php if ($g['subtitle']): ?><span><?php echo ui_e($g['subtitle']); ?></span><?php endif; ?>
          <?php if ($g['body']): ?><p><?php echo ui_e($g['body']); ?></p><?php endif; ?><small><?php echo ui_e($edLabel($g['edition_id'])); ?></small></article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!$anyHl && !$guests) echo $ph(cms_value('awards', 'highlights_empty')); ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'moments')): ?><section class="section section--soft" id="moments">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Key moments</div><h2><?php echo cms_t('awards', 'moments_heading'); ?></h2></div>
    <?php if (!$moments): echo $ph(cms_value('awards', 'moments_empty')); else: ?>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($moments as $m): $mi = $img($m['image']); ?>
      <article class="card"><?php if ($mi): ?><div class="card-media"><img src="assets/images/<?php echo ui_e($mi); ?>" alt="<?php echo ui_e($m['title']); ?>" loading="lazy"></div><?php endif; ?>
        <div class="card-body"><div class="card-meta"><b><?php echo ui_e($edLabel($m['edition_id'])); ?></b><span><?php echo ui_e($m['date_label']); ?></span></div><h3><?php echo ui_e($m['title']); ?></h3>
          <?php if ($m['subtitle']): ?><p class="muted"><b><?php echo ui_e($m['subtitle']); ?></b></p><?php endif; ?><?php if ($m['body']): ?><p class="muted"><?php echo ui_e($m['body']); ?></p><?php endif; ?></div></article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php
    $strip = [];
    foreach ($last2 as $ed) {
        $got = 0;
        $q = $conn->prepare("SELECT image, caption FROM gallery_images WHERE edition IN (?, ?) ORDER BY created_at DESC LIMIT 6"); $yy = (string)$ed['year']; $q->bind_param("ss", $ed['slug'], $yy); $q->execute();
        foreach ($q->get_result()->fetch_all(MYSQLI_ASSOC) as $row) if ($img($row['image'])) { $strip[] = [$row['image'], $row['caption'] ?: 'NUFA Awards ' . $yy, $yy]; $got++; }
        if (!$got) foreach (array_slice(glob(__DIR__ . '/assets/images/gallery/' . $yy . '/*.{jpg,jpeg,webp}', GLOB_BRACE) ?: [], 0, 6) as $p) if (stripos(basename($p), 'poster') === false) $strip[] = ['gallery/' . $yy . '/' . basename($p), 'NUFA Awards ' . $yy, $yy];
    }
    if ($strip): ?>
    <h3 class="sub-title" data-reveal>From the ceremonies</h3>
    <div class="photo-strip" data-reveal-stagger>
      <?php foreach (array_slice($strip, 0, 12) as [$p, $cap, $yy]): ?><figure><img src="assets/images/<?php echo ui_e($p); ?>" data-lightbox="assets/images/<?php echo ui_e($p); ?>" data-caption="<?php echo ui_e($cap); ?>" alt="<?php echo ui_e($cap); ?>" loading="lazy"><figcaption><?php echo ui_e($yy); ?></figcaption></figure><?php endforeach; ?>
    </div>
    <p class="center mt-2"><a href="gallery.php" class="link-arrow">Open the full gallery</a></p>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'impact')): ?><section class="section" id="impact">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Impact</div><h2><?php echo cms_t('awards', 'impact_heading'); ?></h2><p><?php echo cms_t('awards', 'impact_intro'); ?></p></div>
    <?php if (!$impacts): echo $ph(cms_value('awards', 'impact_empty')); else: ?>
    <div class="impact-stories" data-reveal-stagger>
      <?php foreach ($impacts as $s): $si = $img($s['image']); ?>
      <article class="story"><?php if ($si): ?><div class="story-img"><img src="assets/images/<?php echo ui_e($si); ?>" alt="<?php echo ui_e($s['title']); ?>" loading="lazy"></div><?php endif; ?>
        <div class="story-body"><span class="story-q" aria-hidden="true">“</span><h3><?php echo ui_e($s['title']); ?></h3><?php if ($s['subtitle']): ?><span class="story-who"><?php echo ui_e($s['subtitle']); ?></span><?php endif; ?>
          <p><?php echo ui_e($s['body']); ?></p><?php if ($s['link_url']): ?><a class="link-arrow" href="<?php echo ui_e($s['link_url']); ?>" target="_blank" rel="noopener">Read more</a><?php endif; ?></div></article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'films')): ?><section class="section section--black" id="films">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Screen</div><h2><?php echo cms_t('awards', 'films_heading'); ?></h2></div>
    <?php if (!$films): echo $ph(cms_value('awards', 'films_empty')); else: ?>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($films as $f): $fi = $img($f['image']); ?>
      <article class="film-card"><div class="film-poster"><?php if ($fi): ?><img src="assets/images/<?php echo ui_e($fi); ?>" alt="<?php echo ui_e($f['title']); ?> poster" loading="lazy"><?php else: echo nufa_icon('film', 40); endif; ?></div>
        <div class="film-body"><h3><?php echo ui_e($f['title']); ?></h3><?php if ($f['subtitle']): ?><span class="film-meta"><?php echo ui_e($f['subtitle']); ?></span><?php endif; ?>
          <?php if ($f['body']): ?><p><?php echo ui_e(ui_excerpt($f['body'], 220)); ?></p><?php endif; ?>
          <?php if ($f['link_url']): ?><a class="btn btn-gold btn-sm" href="<?php echo ui_e($f['link_url']); ?>" target="_blank" rel="noopener">Watch trailer</a><?php endif; ?></div></article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'archive')): ?><section class="section section--soft" id="archive">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Awards archive</div><h2>Previous editions</h2><p>Verified records of each edition. Information that has not been confirmed is not published.</p></div>
    <?php if (!$past): ?>
      <div class="empty-state"><h3>No past editions published yet</h3><p>Archive pages will appear here as editions are documented.</p></div>
    <?php else: ?>
    <div class="grid" style="gap:28px" data-reveal-stagger>
      <?php foreach ($past as $p): ?>
      <article class="edition-card">
        <div class="media"><img src="assets/images/<?php echo ui_e($p['image'] ?: 'slider/hero/hero-slide-1.webp'); ?>" alt="NUFA Awards <?php echo (int)$p['year']; ?> ceremony" loading="lazy"></div>
        <div class="body">
          <span class="chip chip--dark" style="width:max-content"><?php echo ui_e($p['badge_text'] ?: 'Past Edition'); ?></span>
          <div class="year"><?php echo (int)$p['year']; ?></div>
          <?php if ($p['theme_quote']): ?><p style="font-weight:700;margin:0"><?php echo ui_e($p['theme_quote']); ?></p><?php endif; ?>
          <p class="muted"><?php echo ui_e(ui_excerpt($p['description'], 230)); ?></p>
          <ul class="meta-list">
            <?php if ($p['event_date_label']): ?><li><?php echo nufa_icon('calendar', 17); ?><span><?php echo ui_e($p['event_date_label']); ?></span></li><?php endif; ?>
            <?php if ($p['location_label']): ?><li><?php echo nufa_icon('pin', 17); ?><span><?php echo ui_e($p['location_label']); ?></span></li><?php endif; ?>
          </ul>
          <a href="award.php?e=<?php echo ui_e($p['slug']); ?>" class="link-arrow">View edition</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'takepart')):
  $ways = [];
  foreach (preg_split('/\R/', cms_value('awards', 'takepart_list')) as $ln) { $p = array_map('trim', explode('|', $ln)); if ($p[0] === '') continue; $ways[] = ['t' => $p[0], 'd' => $p[1] ?? '', 'b' => $p[2] ?? '', 'u' => $p[3] ?? '']; }
  $wayIcons = ['film', 'users', 'heart', 'handshake', 'star', 'camera', 'compass']; ?>
<section class="section" id="takepart">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Take part</div><h2><?php echo cms_t('awards', 'takepart_heading'); ?></h2><p><?php echo cms_t('awards', 'takepart_intro'); ?></p></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($ways as $i => $w): ?>
      <div class="value"><span class="icon-circle"><?php echo nufa_icon($wayIcons[$i % count($wayIcons)], 24); ?></span><h3><?php echo ui_e($w['t']); ?></h3><p><?php echo ui_e($w['d']); ?></p>
        <?php if ($w['b'] !== '' && $w['u'] !== ''): ?><a class="link-arrow" href="<?php echo ui_e($w['u']); ?>"><?php echo ui_e($w['b']); ?></a><?php endif; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'judging')): ?><section class="section section--soft" id="judging">
  <div class="container"><div class="section-head" data-reveal><div class="eyebrow">Rules &amp; criteria</div><h2><?php echo cms_t('awards', 'judging_heading'); ?></h2></div>
    <div class="stack" data-reveal><?php echo cms_value('awards', 'judging_body'); ?></div></div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'submissions')):
  $st = $AW['awd_sub_status']; $subUrl = trim($AW['awd_sub_url']) !== '' ? $AW['awd_sub_url'] : trim(cms_value('awards', 'submission_url'));
  $stLabel = ['soon' => 'Opening soon', 'open' => 'Open for submissions', 'closed' => 'Submissions closed'][$st] ?? 'Opening soon'; ?>
<section class="section" id="submissions">
  <div class="container split">
    <div data-reveal>
      <div class="eyebrow">Submission portal</div><h2><?php echo cms_t('awards', 'submit_heading'); ?></h2>
      <span class="chip chip--<?php echo $st === 'open' ? 'operational' : 'dark'; ?>"><?php echo ui_e($stLabel); ?></span>
      <ul class="fact-list">
        <li><b>Opens</b><span><?php echo ui_e($AW['awd_sub_opens'] ?: 'To be announced'); ?></span></li>
        <li><b>Deadline</b><span><?php echo ui_e($AW['awd_sub_deadline'] ?: 'To be announced'); ?></span></li>
        <li><b>Entry fee</b><span><?php echo ui_e($AW['awd_sub_fee'] ?: 'To be announced'); ?></span></li>
      </ul>
      <div class="actions mt-2">
        <?php if ($st === 'open' && $subUrl !== ''): ?><a href="<?php echo ui_e($subUrl); ?>" class="btn btn-gold" target="_blank" rel="noopener">Submit your film</a><?php endif; ?>
        <a href="<?php echo $AW['awd_sub_email'] !== '' ? 'mailto:' . ui_e($AW['awd_sub_email']) : 'contact.php?topic=Awards'; ?>" class="btn btn-outline-dark">Ask about submissions</a>
      </div>
      <?php if ($st !== 'open'): ?><p class="muted" style="margin-top:14px"><?php echo $st === 'closed' ? 'Submissions are closed for now. Watch the road map for the next call for entries.' : 'The submission portal opens once the call for entries is announced. Become a member or join the mailing list to hear first.'; ?></p><?php endif; ?>
    </div>
    <div data-reveal>
      <div class="info-card"><h3>Who can submit</h3><?php echo trim($AW['awd_sub_eligibility']) !== '' ? '<div class="stack">' . ui_paragraphs($AW['awd_sub_eligibility']) . '</div>' : '<p class="muted">Eligibility rules will be published here once confirmed.</p>'; ?></div>
      <div class="info-card"><h3>Submission guidelines</h3><?php echo trim($AW['awd_sub_guidelines']) !== '' ? ui_list($AW['awd_sub_guidelines']) : '<p class="muted">Guidelines on format, length, language and what to upload will be published here.</p>'; ?></div>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'tickets')):
  $tst = $AW['awd_tix_status']; $tiers = awd_tiers($AW['awd_tix_tiers']);
  if (!$tiers && trim((string)($cfg['ticket_price'] ?? '')) !== '') $tiers[] = ['name' => 'General admission', 'price' => 'UGX ' . $cfg['ticket_price'], 'note' => ''];
  $tstLabel = ['soon' => 'Tickets coming soon', 'on_sale' => 'On sale now', 'sold_out' => 'Sold out', 'closed' => 'Sales closed'][$tst] ?? 'Tickets coming soon';
  $tixVenue = trim($AW['awd_tix_venue']) !== '' ? $AW['awd_tix_venue'] : ($nextEd ? trim($nextEd['event_date_label'] . ($nextEd['event_date_label'] && $nextEd['location_label'] ? ' · ' : '') . $nextEd['location_label']) : ''); ?>
<section class="section section--soft" id="tickets">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Tickets</div><h2><?php echo cms_t('awards', 'tickets_heading'); ?></h2><span class="chip chip--<?php echo $tst === 'on_sale' ? 'operational' : 'dark'; ?>"><?php echo ui_e($tstLabel); ?></span><?php if ($tixVenue !== ''): ?><p><?php echo nufa_icon('pin', 16); ?> <?php echo ui_e($tixVenue); ?></p><?php endif; ?></div>
    <div class="split" style="align-items:start">
      <div data-reveal>
        <?php if ($tiers): ?><div class="tier-list"><?php foreach ($tiers as $t): ?><div class="tier"><div><b><?php echo ui_e($t['name']); ?></b><?php if ($t['note']): ?><span><?php echo ui_e($t['note']); ?></span><?php endif; ?></div><em><?php echo ui_e($t['price']); ?></em></div><?php endforeach; ?></div>
        <?php else: echo $ph('Ticket types and prices will be published here once sales are announced.'); endif; ?>
        <div class="info-card" style="margin-top:20px"><h3>How to book</h3><?php echo trim($AW['awd_tix_howto']) !== '' ? '<div class="stack">' . ui_paragraphs($AW['awd_tix_howto']) . '</div>' : '<p class="muted">Booking and payment instructions will be published here. You can already send a request using the form and the team will reply with details.</p>'; ?></div>
        <?php if ($tst === 'on_sale' && trim($AW['awd_tix_url']) !== ''): ?><div class="actions mt-2"><a class="btn btn-gold" href="<?php echo ui_e($AW['awd_tix_url']); ?>" target="_blank" rel="noopener">Buy tickets online</a></div><?php endif; ?>
      </div>
      <?php if ($AW['awd_tix_booking_form'] === '1'): ?>
      <div class="form-card" data-reveal>
        <h3 style="margin-top:0">Request tickets</h3>
        <?php echo form_banner($tixSent, $tixErrors, 'Your request has been received. The NUFA team will contact you with booking and payment details. No payment is taken on this page.'); ?>
        <?php if (!$tixSent): ?>
        <form method="post" action="awards.php#tickets"><input type="hidden" name="form" value="tickets"><?php echo form_open_fields(); ?>
          <div class="form-grid">
            <?php echo f_input('name', 'Full name', ['required' => true, 'autocomplete' => 'name']); ?>
            <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
            <?php echo f_input('phone', 'Phone / WhatsApp', ['type' => 'tel', 'required' => true, 'autocomplete' => 'tel']); ?>
            <?php echo $tiers ? f_select('ticket_type', 'Ticket type', array_column($tiers, 'name')) : f_input('ticket_type', 'Ticket type (optional)'); ?>
            <?php echo f_input('qty', 'Number of tickets', ['type' => 'number', 'default' => '1']); ?>
          </div>
          <?php echo f_textarea('message', 'Anything we should know?', ['maxlength' => 1000]); ?>
          <?php echo f_check('consent', 'I agree that NUFA may contact me about my ticket request.'); ?>
          <button class="btn btn-gold btn-block" type="submit">Send ticket request</button>
        </form><?php endif; ?>
      </div><?php endif; ?>
    </div>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'team')):
  $awTeam = $conn->query("SELECT * FROM team_members WHERE is_active=1 AND category='" . AWD_TEAM_CATEGORY . "' ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC); ?>
<section class="section" id="team">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Technical team</div><h2><?php echo cms_t('awards', 'tech_heading'); ?></h2><p><?php echo cms_t('awards', 'tech_intro'); ?></p></div>
    <?php if (!$awTeam): echo $ph(cms_value('awards', 'tech_empty')); else: ?>
    <div class="guest-grid team-grid" data-reveal-stagger>
      <?php foreach ($awTeam as $m): $mp = $img($m['photo'] ?? '') ?: ((!empty($m['photo']) && is_file(__DIR__ . '/assets/images/uploads/team/' . $m['photo'])) ? 'uploads/team/' . $m['photo'] : ''); ?>
      <article class="guest"><div class="guest-photo"><?php if ($mp): ?><img src="assets/images/<?php echo ui_e($mp); ?>" alt="<?php echo ui_e($m['name']); ?>" loading="lazy"><?php else: echo nufa_icon('users', 34); endif; ?></div>
        <h4><?php echo ui_e($m['name']); ?></h4><span><?php echo ui_e($m['role']); ?></span><?php if (!empty($m['bio'])): ?><p><?php echo ui_e($m['bio']); ?></p><?php endif; ?></article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section><?php endif; ?>

<?php if (nufa_show('awards', 'faq')): ?><section class="section" id="faq">
  <div class="container narrow"><div class="section-head center" data-reveal><div class="eyebrow">FAQ</div><h2><?php echo cms_t('awards', 'faq_heading'); ?></h2></div>
    <div class="faq" data-reveal><?php echo cms_value('awards', 'faq_html'); ?></div></div>
</section><?php endif; ?>

<?php if ($awardNews): ?>
<?php if (nufa_show('awards', 'news')): ?><section class="section section--soft">
  <div class="container">
    <div class="section-head" style="max-width:none;display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap"><div><div class="eyebrow">Awards news</div><h2 class="mb-0">News &amp; announcements</h2></div><a class="link-arrow" href="news.php?cat=NUFA+Awards">All awards news</a></div>
    <div class="grid grid-3" data-reveal-stagger>
      <?php foreach ($awardNews as $p): ?>
      <article class="card"><a href="post.php?slug=<?php echo urlencode($p['slug']); ?>" style="display:flex;flex-direction:column;height:100%">
        <div class="card-media"><?php echo ui_img($p['image'], ''); ?></div>
        <div class="card-body"><div class="card-meta"><b><?php echo ui_e($p['category']); ?></b><span><?php echo ui_date($p['published_at']); ?></span></div><h3><?php echo ui_e($p['title']); ?></h3><span class="link-arrow">Read story</span></div></a></article>
      <?php endforeach; ?>
    </div>
  </div>
</section><?php endif; ?>
<?php endif; ?>

<?php if (nufa_show('awards', 'cta')): ?><section class="section--tight"><div class="container"><?php echo ui_cta_band(cms_value('awards', 'cta_title'), cms_value('awards', 'cta_text'), cms_value('awards', 'cta_btn1_label'), cms_value('awards', 'cta_btn1_url'), cms_value('awards', 'cta_btn2_label'), cms_value('awards', 'cta_btn2_url')); ?></div></section><?php endif; ?>

<?php include __DIR__ . "/includes/footer.php"; ?>
