<?php
$pageTitle = "NUFA — Northern Uganda Filmmakers Association | Home of Northern Storytellers";
$pageDesc  = "NUFA unites, trains and celebrates filmmakers across Acholi, Lango, West Nile and Karamoja. Discover the NUFA Awards, our community, and how to partner with us.";
$activeNav = "home";
$base = "";
require __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";
?>
<a id="top"></a>

<?php
$heroImages = [];
$res = $conn->query("SELECT image, alt_text FROM hero_slides WHERE is_active=1 ORDER BY sort_order ASC");
while ($row = $res->fetch_assoc()) $heroImages[] = $row;
if (empty($heroImages)) {
  $heroImages = [["image"=>"gallery/2026/nufa26-07.jpg","alt_text"=>"NUFA Awards moment"]];
}
// Slice sequentially (not modulo) so every image appears at most once across the whole collage.
$hcCols = array_chunk($heroImages, max(1, (int)ceil(count($heroImages) / 6)));
function hc_col($imgs){
  foreach(array_merge($imgs,$imgs) as $slide){
    echo '<img src="assets/images/'.htmlspecialchars($slide['image']).'" alt="'.htmlspecialchars($slide['alt_text'] ?: 'NUFA Awards moment').'" loading="lazy">';
  }
}
?>
<section class="hero">
  <div class="hero-visual">
    <div class="hero-collage" aria-hidden="true">
      <?php foreach ($hcCols as $i => $col): ?>
      <div class="hc-col <?php echo $i % 2 === 0 ? 'hc-col-up' : 'hc-col-down'; ?>"><?php hc_col($col); ?></div>
      <?php endforeach; ?>
    </div>
    <div class="hero-scrim"></div>
  </div>

  <div class="container-wide hero-grid hero-grid-center">
    <div class="hero-copy hero-copy-center">
      <div class="eyebrow hero-eyebrow">Northern Uganda Filmmakers Association</div>
      <h1>Celebrating the <em>spirit</em> of Northern Cinema</h1>
      <div class="hero-stats">
        <div class="hero-avatars">
          <img src="assets/images/team/team-president.webp" alt="">
          <img src="assets/images/team/team-media-lead.webp" alt="">
          <img src="assets/images/team/team-guild-leader.webp" alt="">
          <img src="assets/images/team/team-publicity.webp" alt="">
        </div>
        <div class="stat"><b>1,000+</b><span>NUFA26 gala attendees</span></div>
      </div>
    </div>
  </div>
</section>

<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    <span>Acholi</span><span>Lango</span><span>West Nile</span><span>Karamoja</span><span>NUFA Awards 2027</span>
    <span>Acholi</span><span>Lango</span><span>West Nile</span><span>Karamoja</span><span>NUFA Awards 2027</span>
  </div>
</div>

<section class="section bg-paper">
  <div class="container assoc">
    <div class="assoc-visual" data-reveal>
      <div class="frame-main"><img src="assets/images/gallery/2026/nufa26-12.jpg" alt="A filmmaker in cultural dress with an elephant-motif pendant at the NUFA26 Awards"></div>
      <div class="chip"><b>02 May</b><span>NUFA26 Awards Day, Acholi Inn, Gulu</span></div>
    </div>
    <div class="assoc-body" data-reveal>
      <div class="eyebrow">The Association</div>
      <h2 style="margin-block:18px 20px">A regional home for the people telling <span class="serif">Northern Uganda's</span> stories</h2>
      <p class="lede">NUFA is a registered collective of filmmakers — camera operators, editors, actors, writers, costume and set designers — building a film industry where local stories are made, owned and celebrated by the people who live them.</p>
      <ul class="assoc-list">
        <li>
          <span class="ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 3l7 4v5c0 5-3.4 8.4-7 9-3.6-.6-7-4-7-9V7l7-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
          <div><b>Training &amp; capacity building</b><span>Screenwriting, directing and technical masterclasses reaching thousands of creatives.</span></div>
        </li>
        <li>
          <span class="ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
          <div><b>Recognition &amp; platform</b><span>The NUFA Awards give technical and creative achievement a regional stage.</span></div>
        </li>
        <li>
          <span class="ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M4 20v-1a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v1M15 4.2a3.5 3.5 0 0 1 0 6.6M17 20v-1a5 5 0 0 0-3-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/></svg></span>
          <div><b>Advocacy &amp; policy</b><span>Championing the policies and market access that make film a viable career.</span></div>
        </li>
      </ul>
      <div style="margin-top:32px"><a href="about.php" class="btn-ghost">Read our full story <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
    </div>
  </div>
</section>

<section class="section-tight bg-cream2">
  <div class="container stat-grid" data-reveal-stagger>
    <div class="stat-cell">
      <span class="stat-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M17 20v-1a5 5 0 0 0-5-5H8a5 5 0 0 0-5 5v1M15.5 4.2a3.5 3.5 0 0 1 0 6.6M19 20v-1a5 5 0 0 0-3-4.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="9.5" cy="8" r="3.3" stroke="currentColor" stroke-width="1.7"/></svg></span>
      <b>6,700+</b><span>People reached through NUFA programmes</span>
    </div>
    <div class="stat-cell">
      <span class="stat-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
      <b>92</b><span>Film submissions to NUFA26 — up 31% on 2025</span>
    </div>
    <div class="stat-cell">
      <span class="stat-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18" stroke="currentColor" stroke-width="1.4"/></svg></span>
      <b>4</b><span>Regions united — Acholi, Lango, West Nile, Karamoja</span>
    </div>
    <div class="stat-cell">
      <span class="stat-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.6 6.6L21 10l-5 4.3L17.4 21 12 17.3 6.6 21 8 14.3 3 10l6.4-1.4L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
      <b>2027</b><span>Next edition of the NUFA Awards</span>
    </div>
  </div>
</section>

<section class="section bg-paper">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">The NUFA Awards</div>
      <h2>Two editions strong. <span class="serif">One big stage ahead.</span></h2>
      <p class="lede">2025 and 2026 are history — 2027 is where we're headed. Every edition brings Northern Uganda's finest storytellers to Acholi Inn, Gulu.</p>
    </div>

    <div class="timeline" data-reveal-stagger>
      <article class="ed-card" id="prev-2025">
        <div class="ed-media">
          <span class="ed-status">Past Edition</span>
          <img src="assets/images/gallery/2026/poster.jpg" alt="NUFA Awards branding" style="filter:grayscale(40%)">
        </div>
        <div class="ed-body">
          <span class="ed-year">2025</span>
          <p>The inaugural NUFA Awards — the region's first-ever film awards platform, launched to recognise Northern Uganda's storytellers.</p>
          <ul class="ed-meta">
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 3v4M17 3v4M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"/></svg> 25 April 2025</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/></svg> Acholi Inn, Gulu</li>
          </ul>
        </div>
      </article>

      <article class="ed-card" id="nufa26">
        <div class="ed-media">
          <span class="ed-status">Past Edition</span>
          <img src="assets/images/gallery/2026/nufa26-07.jpg" alt="Guests arriving at the NUFA26 Awards red carpet">
        </div>
        <div class="ed-body">
          <span class="ed-year">2026</span>
          <p>NUFA26 grew the celebration — a full house at Acholi Inn, more categories, and a red carpet befitting the region's finest.</p>
          <ul class="ed-meta">
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 3v4M17 3v4M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"/></svg> 2 May 2026</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/></svg> Acholi Inn, Gulu</li>
          </ul>
        </div>
      </article>

      <article class="ed-card upcoming" id="nufa27">
        <div class="ed-media">
          <span class="ed-status">Upcoming</span>
          <img src="assets/images/gallery/2025/nufa25-09.webp" alt="A trophy is presented on stage — looking ahead to NUFA27">
        </div>
        <div class="ed-body">
          <span class="ed-year">2027</span>
          <p>The next chapter of the NUFA Awards is being written. Save the date — nominations and partner packages open soon.</p>
          <ul class="ed-meta">
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 3v4M17 3v4M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"/></svg> Date to be announced</li>
            <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.5"/></svg> Gulu, Northern Uganda</li>
          </ul>
          <a href="awards.php#nufa27" class="btn btn-brand btn-sm" style="margin-top:16px;align-self:flex-start">Get Involved</a>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="section bg-cream2">
  <div class="container" style="max-width:1440px">
    <div class="section-head" data-reveal style="flex-direction:row;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:20px">
      <div>
        <div class="eyebrow">Pictorials</div>
        <h2 style="margin-top:16px">Moments from <span class="serif">the Gala</span></h2>
      </div>
      <a href="gallery.php" class="btn-ghost">View full gallery <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
    </div>

    <div class="hscroll-wrap">
      <div class="hscroll" data-reveal-stagger>
        <div class="h-item"><img src="assets/images/gallery/2026/nufa26-08.jpg" alt="Guests mingling at NUFA26" loading="lazy"></div>
        <div class="h-item"><img src="assets/images/gallery/2026/nufa26-06.jpg" alt="NUFA26 guest arrival" loading="lazy"></div>
        <div class="h-item"><img src="assets/images/gallery/2025/nufa25-01.webp" alt="NUFA25 Awards moment" loading="lazy"></div>
        <div class="h-item"><img src="assets/images/gallery/2025/nufa25-02.webp" alt="NUFA25 Awards moment" loading="lazy"></div>
        <div class="h-item"><img src="assets/images/gallery/2025/nufa25-04.webp" alt="NUFA25 Awards moment" loading="lazy"></div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-paper">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">Why NUFA</div>
      <h2>Built for filmmakers, <span class="serif">by filmmakers</span></h2>
    </div>
    <div class="grid-3" data-reveal-stagger>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></div>
        <h3>Mentorship that shows up</h3>
        <p>Guilds and workshops led by working professionals, in camera, editing, sound, writing and design.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.6 6.6L21 10l-5 4.3L17.4 21 12 17.3 6.6 21 8 14.3 3 10l6.4-1.4L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></div>
        <h3>A stage that recognises you</h3>
        <p>The NUFA Awards put technical craft — not just star power — in the spotlight every edition.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18" stroke="currentColor" stroke-width="1.4"/></svg></div>
        <h3>A network across the North</h3>
        <p>One collective spanning Acholi, Lango, West Nile and Karamoja — production support included.</p>
      </div>
    </div>
  </div>
</section>

<section class="section bg-cream2">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">In Their Words</div>
      <h2>Voices from <span class="serif">NUFA26</span></h2>
    </div>
    <div class="grid-3" data-reveal-stagger>
      <div class="testi-card">
        <span class="quote-mark">"</span>
        <p>"We'd love to see this event last forever" — on what our partners make possible each edition.</p>
        <div class="testi-who">
          <img src="assets/images/team/team-president.webp" alt="Ojok Odong, NUFA President">
          <div><b>Ojok Odong</b><span>NUFA President</span></div>
        </div>
      </div>
      <div class="testi-card">
        <span class="quote-mark">"</span>
        <p>"Your work is not just entertainment — it is a mirror to the community," a partner told our filmmakers at NUFA26.</p>
        <div class="testi-who">
          <img src="assets/images/gallery/2026/nufa26-02.jpg" alt="Guest at NUFA26">
          <div><b>Leonard Amanya</b><span>Uganda Communications Commission</span></div>
        </div>
      </div>
      <div class="testi-card">
        <span class="quote-mark">"</span>
        <p>Young creatives "showing the world that we can do it" — the kind of moment NUFA Awards was built to create.</p>
        <div class="testi-who">
          <img src="assets/images/gallery/2026/nufa26-03.jpg" alt="Guest at NUFA26">
          <div><b>Hon. Betty Amongi</b><span>Minister, Gender, Labour &amp; Social Development</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-tight bg-paper">
  <div class="container">
    <div class="section-head" style="margin-bottom:32px">
      <div class="eyebrow">Partners</div>
      <h2 style="margin-top:16px">Backed by organisations who believe in Northern storytelling</h2>
    </div>
    <div class="partner-wall" data-reveal-stagger>
      <?php
      $homePartners = $conn->query("SELECT * FROM partners WHERE is_active=1 ORDER BY sort_order ASC");
      while ($p = $homePartners->fetch_assoc()):
        $hasLogo = file_exists(__DIR__ . "/assets/images/" . $p['logo']);
      ?>
      <div class="partner-tile">
        <?php if ($hasLogo): ?>
          <img src="assets/images/<?php echo htmlspecialchars($p['logo']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?> — NUFA <?php echo htmlspecialchars($p['tier']); ?>" loading="lazy">
        <?php else: ?>
          <div><b><?php echo htmlspecialchars($p['name']); ?></b><span><?php echo htmlspecialchars($p['tier']); ?></span></div>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<section class="section bg-cream2">
  <div class="container">
    <div class="cta-band" data-reveal>
      <div>
        <div class="eyebrow">Accelerate Our Impact</div>
        <h2 style="margin-top:16px">Become a sponsor of the <span class="serif">NUFA Awards 2027</span></h2>
        <p class="lede" style="margin-top:14px">Put your brand behind the region's biggest night in film — and the training that happens all year round.</p>
      </div>
      <a href="partners.php#become" class="btn btn-brand">Partner With Us</a>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
