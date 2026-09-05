<?php
$pageTitle = "NUFA Awards — 2026 Winners & 2027 Tickets";
$pageDesc  = "The NUFA Awards honour the best of Northern Uganda film. See the NUFA26 winners list, relive the 2025 and 2026 editions, and get tickets for NUFA Awards 2027.";
$activeNav = "awards";
$base = "";
$ogImage = "assets/images/og/og-awards.jpg";
$ogImageAlt = "On stage at the NUFA Awards 2025 Golden Night";
$canonicalPath = "awards.php";
include __DIR__ . "/includes/header.php";
require __DIR__ . "/includes/content.php";
require __DIR__ . "/includes/content-schemas.php";
$awardsSchema = $CMS_SCHEMAS['awards']['fields'];
$awardsVals = cms_load($conn, 'awards', $awardsSchema);
function awards_c($key) { global $awardsSchema, $awardsVals; return cms_out($awardsSchema, $awardsVals, $key); }

$editions = [];
$er = $conn->query("SELECT * FROM award_editions WHERE is_active=1 ORDER BY sort_order ASC");
while ($row = $er->fetch_assoc()) $editions[] = $row;

$winnersByEdition = [];
$wr = $conn->query("SELECT * FROM award_winners ORDER BY sort_order ASC");
while ($row = $wr->fetch_assoc()) $winnersByEdition[$row['edition_id']][] = $row;
?>
<a id="top"></a>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "<?php echo $siteUrl; ?>/index.php"},
    {"@type": "ListItem", "position": 2, "name": "Awards", "item": "<?php echo $siteUrl; ?>/awards.php"}
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "NUFA Awards 2026 — Stories That Redefine Us",
  "startDate": "2026-05-02",
  "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
  "eventStatus": "https://schema.org/EventScheduled",
  "location": {
    "@type": "Place",
    "name": "Acholi Inn Hotel",
    "address": {"@type": "PostalAddress", "addressLocality": "Gulu City", "addressRegion": "Northern Region", "addressCountry": "UG"}
  },
  "image": ["<?php echo $siteUrl; ?>/assets/images/gallery/2026/nufa26-15.jpg"],
  "description": "The second edition of the Northern Uganda Film Awards — 92 film submissions, 64 nominations across 29 films, and 1,000+ guests at Acholi Inn, Gulu City.",
  "organizer": {"@type": "Organization", "name": "Northern Uganda Filmmakers Association", "url": "<?php echo $siteUrl; ?>/"}
}
</script>

<section class="page-hero">
  <div class="page-hero-bg"><img src="assets/images/<?php echo htmlspecialchars($awardsVals['hero_image']); ?>" alt="" aria-hidden="true"></div>
  <div class="hero-glow g1"></div>
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>Awards</span></div>
    <div class="eyebrow"><?php echo awards_c('hero_eyebrow'); ?></div>
    <h1 style="margin-top:18px"><?php echo awards_c('hero_heading'); ?></h1>
    <p><?php echo awards_c('hero_lede'); ?></p>
  </div>
</section>

<?php foreach ($editions as $ed):
  $bg = $ed['status'] === 'upcoming' ? 'bg-paper' : (($ed['sort_order'] % 2 === 0) ? 'bg-cream2' : 'bg-paper');
  $winners = $winnersByEdition[$ed['id']] ?? [];
?>
<!-- ================= <?php echo htmlspecialchars(strtoupper($ed['slug'])); ?> — <?php echo strtoupper($ed['status']); ?> ================= -->
<section class="section <?php echo $bg; ?>" id="<?php echo htmlspecialchars($ed['slug']); ?>">
  <div class="container">
    <div class="grid-2" data-reveal style="align-items:center">
      <div>
        <div class="tier-badge" <?php echo $ed['status']==='past' ? 'style="background:var(--cream);border:1px solid var(--line)"' : ''; ?>><?php echo htmlspecialchars($ed['badge_text']); ?></div>
        <h2><?php echo $ed['heading_html']; ?></h2>
        <?php if ($ed['theme_quote']): ?><p class="serif" style="font-size:1.05rem;margin-top:10px"><?php echo htmlspecialchars($ed['theme_quote']); ?></p><?php endif; ?>
        <p class="lede" style="margin-top:16px"><?php echo htmlspecialchars($ed['description']); ?></p>
        <?php if ($ed['status'] === 'upcoming'): ?>
        <ul class="ed-meta" style="margin-top:24px;border:none;gap:14px">
          <li style="font-size:.95rem"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 3v4M17 3v4M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"/></svg> <?php echo htmlspecialchars($ed['event_date_label']); ?></li>
          <li style="font-size:.95rem"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/></svg> <?php echo htmlspecialchars($ed['location_label']); ?></li>
        </ul>
        <div class="ticket-card">
          <div class="ticket-price"><span>UGX</span><b><?php echo htmlspecialchars($siteSettings['ticket_price']); ?></b></div>
          <div class="ticket-info">
            <b><?php echo awards_c('n27_ticket_title'); ?></b>
            <span><?php echo awards_c('n27_ticket_desc'); ?></span>
          </div>
          <a href="contact.php" class="btn btn-brand btn-sm">Get Tickets</a>
        </div>
        <div class="hero-actions" style="margin-top:20px">
          <a href="partners.php#become" class="btn btn-line">Sponsor <?php echo htmlspecialchars(strtoupper($ed['slug'])); ?></a>
        </div>
        <?php else: ?>
        <a href="gallery.php" class="btn-ghost" style="margin-top:24px">See the <?php echo htmlspecialchars(strtoupper($ed['slug'])); ?> gallery <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        <?php endif; ?>
      </div>
      <div class="assoc-visual">
        <div class="frame-main" style="aspect-ratio:1/1"><img src="assets/images/<?php echo htmlspecialchars($ed['image']); ?>" alt="<?php echo htmlspecialchars($ed['heading_html'] ? strip_tags($ed['heading_html']) : ''); ?>"></div>
        <?php if ($ed['chip_title']): ?><div class="chip"><b><?php echo htmlspecialchars($ed['chip_title']); ?></b><span><?php echo htmlspecialchars($ed['chip_sub']); ?></span></div><?php endif; ?>
      </div>
    </div>

    <?php if ($ed['stat1_value']): ?>
    <div class="stat-grid" style="margin-top:48px" data-reveal-stagger>
      <?php for ($i = 1; $i <= 4; $i++): if (empty($ed["stat{$i}_value"])) continue; ?>
      <div class="stat-cell"><b><?php echo htmlspecialchars($ed["stat{$i}_value"]); ?></b><span><?php echo htmlspecialchars($ed["stat{$i}_label"]); ?></span></div>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php if ($ed['masonry1']): ?>
    <div class="masonry" style="margin-top:56px" data-reveal>
      <?php for ($i = 1; $i <= 4; $i++): if (empty($ed["masonry{$i}"])) continue; ?>
      <div class="m-item"<?php echo $i === 1 ? ' style="grid-column:span 2"' : ''; ?>><img src="assets/images/<?php echo htmlspecialchars($ed["masonry{$i}"]); ?>" alt="<?php echo htmlspecialchars(strtoupper($ed['slug'])); ?> gala moment" loading="lazy"></div>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php if ($winners): ?>
    <div class="section-head center" style="margin-top:72px">
      <div class="eyebrow"><?php echo htmlspecialchars(strtoupper($ed['slug'])); ?> Champions</div>
      <h3 style="margin-top:14px;font-size:clamp(1.4rem,2.4vw,1.9rem)">Meet the Winners</h3>
    </div>
    <div class="winners-grid" data-reveal-stagger>
      <?php foreach ($winners as $w): ?>
      <div class="winner-cell<?php echo $w['is_honorary'] ? ' honorary' : ''; ?>">
        <span class="wc-cat"><?php echo htmlspecialchars($w['category']); ?></span>
        <b><?php echo htmlspecialchars($w['winner_title']); ?></b>
        <?php if ($w['person_name']): ?><span class="wc-who"><?php echo htmlspecialchars($w['person_name']); ?></span><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($ed['extra_html']): ?>
    <?php echo $ed['extra_html']; ?>
    <?php endif; ?>
  </div>
</section>

<?php if ($ed['status'] === 'upcoming'): ?>
<div class="marquee" aria-hidden="true"><div class="marquee-track">
  <span>NUFA Awards <?php echo (int)$ed['year']; ?></span><span>Save the date</span><span>Nominations opening soon</span><span>Become a partner</span>
  <span>NUFA Awards <?php echo (int)$ed['year']; ?></span><span>Save the date</span><span>Nominations opening soon</span><span>Become a partner</span>
</div></div>
<?php endif; ?>
<?php endforeach; ?>

<section class="section bg-cream2">
  <div class="container">
    <div class="cta-band" data-reveal>
      <div>
        <div class="eyebrow"><?php echo awards_c('cta_eyebrow'); ?></div>
        <h2 style="margin-top:16px"><?php echo awards_c('cta_heading'); ?></h2>
      </div>
      <a href="partners.php#become" class="btn btn-brand">Become a Partner</a>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
