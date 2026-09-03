<?php
$pageTitle = "About NUFA — Our Story, Mission, Vision & Team";
$pageDesc  = "The story behind NUFA: our mission to unite and train Northern Uganda's filmmakers, the vision and values that guide us, and the executive team leading the association.";
$activeNav = "about";
$base = "";
$canonicalPath = "about.php";
$ogImage = "assets/images/og/og-about.jpg";
$ogImageAlt = "Two NUFA filmmakers reviewing a shot on a cinema camera";
include __DIR__ . "/includes/header.php";
?>
<a id="top"></a>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Home","item":"<?php echo $siteUrl; ?>/index.php"},
  {"@type":"ListItem","position":2,"name":"About","item":"<?php echo $siteUrl; ?>/about.php"}
]}
</script>

<section class="about-hero">
  <div class="about-hero-bg"><img src="assets/images/gallery/2025/nufa25-11.webp" alt="NUFA Awards guests at the 2025 gala in Gulu" width="1600" height="900"></div>
  <div class="container">
    <div class="breadcrumb" style="justify-content:center"><a href="index.php">Home</a><span>/</span><span>About</span></div>
    <div class="eyebrow" style="justify-content:center">Our Association</div>
    <h1 style="margin-top:14px">Empowering Storytellers.<br>Uniting Northern Uganda.</h1>
    <p style="max-width:60ch;margin-inline:auto;color:var(--text-on-ink-muted)">NUFA is the regional home for filmmakers across Acholi, Lango, West Nile and Karamoja — where stories are trained, produced, and honoured on our own stage: the NUFA Awards.</p>
  </div>
</section>

<section class="story-section bg-paper">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">Our Story</div>
      <h2 style="margin-top:16px">Where NUFA began</h2>
      <div class="divider-brand" style="margin-inline:auto"></div>
    </div>
    <p class="story-intro" data-reveal>At NUFA, we are passionate about training, platforming and celebrating the filmmakers of Northern Uganda — storytellers who elevate the region's culture and enrich the wider creative economy.</p>

    <div class="story-bento" data-reveal>
      <div class="sb-ph sb-1"><img src="assets/images/gallery/2025/nufa25-08.webp" alt="NUFA25 Awards moment" loading="lazy"></div>
      <div class="sb-ph sb-2"><img src="assets/images/production/karamoja-shoot-2.webp" alt="NUFA crew on a production shoot" loading="lazy"></div>
      <div class="sb-ph sb-3"><img src="assets/images/slider/slide2-2.webp" alt="Winners celebrate on stage at the Northern Uganda Film Awards" loading="lazy"></div>
      <div class="sb-ph sb-4"><img src="assets/images/gallery/2026/nufa26-13.jpg" alt="NUFA26 red carpet moment" loading="lazy"></div>
      <div class="sb-ph sb-5"><img src="assets/images/production/karamoja-shoot-1.webp" alt="NUFA crew filming on location in Karamoja" loading="lazy"></div>
    </div>

    <div class="story-narrative" data-reveal>
      <p>For years, the people making films in Acholi, Lango, West Nile and Karamoja worked the way most creatives in the region did — alone. A camera operator in Gulu had no easy way to find an editor in Lira. A costume designer in Arua had no guild to trade notes with. Talent was never the problem; the region has always had storytellers. What it lacked was a shared roof to work under, and a stage to be seen from.</p>
      <p>NUFA — the Northern Uganda Filmmakers Association — was formed to be that roof. It is a registered, filmmaker-led body that brings camera operators, editors, actors, writers, costume and set designers, sound engineers and directors into one regional community, with training, equipment access and professional guilds built around each craft.</p>

      <p class="story-pull">A career in film is something a young creative in Northern Uganda can actually build — not just dream about.</p>

      <p>That mission carries extra weight in a region that spent decades being written about by outsiders more often than it got to write its own story. Northern Uganda's recovery and resilience are real, lived history — and NUFA's members are increasingly the ones putting that history, and the region's culture, language and everyday life, on screen <strong>in their own words</strong>. Documentary work made in partnership with cultural institutions like Ker Kwaro Acholi sits alongside music videos, short films and community dramas — all evidence of a place actively authoring its own narrative.</p>
      <p>The NUFA Awards are where all of that comes together once a year — a red carpet at Acholi Inn that turns craft most people never see (editing, sound design, production management) into something the whole community can celebrate. Two editions in, with NUFA27 already taking shape, the goal hasn't changed: make Northern Uganda a place where a career in film is <strong>not the exception, but the expectation</strong>.</p>
    </div>
    <a href="awards.php" class="btn btn-brand">Discover the NUFA Awards</a>
  </div>
</section>

<section class="section bg-cream2">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">About Us</div>
      <h2 style="margin-top:16px">Our Identity, <span class="serif">Vision and Values</span></h2>
    </div>

    <div class="identity-bar" data-reveal>
      <div class="identity-item">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
        <span>Training</span>
      </div>
      <div class="identity-item">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 3l7 4v5c0 5-3.4 8.4-7 9-3.6-.6-7-4-7-9V7l7-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
        <span>Trust &amp; Advocacy</span>
      </div>
      <div class="identity-item">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M3 12h18" stroke="currentColor" stroke-width="1.3"/></svg></span>
        <span>Community</span>
      </div>
      <div class="identity-item">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.6 6.6L21 10l-5 4.3L17.4 21 12 17.3 6.6 21 8 14.3 3 10l6.4-1.4L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>
        <span>Innovation</span>
      </div>
    </div>

    <div class="vm-card" data-reveal>
      <div class="vm-col">
        <div class="vm-head"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg><h3>Vision</h3></div>
        <p>An independent platform that celebrates excellence, preserves culture, and positions Northern Uganda as a vibrant hub for film and creative innovation.</p>
      </div>
      <div class="vm-divider"></div>
      <div class="vm-col">
        <div class="vm-head"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="4.5" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/></svg><h3>Mission</h3></div>
        <p>To celebrate excellence in filmmaking, preserve Northern Uganda's cultural heritage, and inspire innovation that empowers storytellers and amplifies indigenous voices.</p>
      </div>
    </div>
  </div>
</section>

<section class="section bg-paper">
  <div class="container">
    <div class="section-head-split" data-reveal>
      <div>
        <div class="eyebrow">The Challenge</div>
        <h2 style="margin-top:14px">What We<br>Offer</h2>
      </div>
      <div>
        <p class="lede">We specialise in transforming raw talent into real careers. Explore the training, guilds and platforms NUFA has built to support every stage of a filmmaker's journey.</p>
      </div>
    </div>
    <div class="grid-3" data-reveal-stagger>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></div>
        <h3>Training &amp; workshops</h3>
        <p>Hands-on sessions led by experienced filmmakers, delivered across five districts and counting.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3.4" stroke="currentColor" stroke-width="1.6"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
        <h3>Professional guilds</h3>
        <p>Specialised communities for camera, editing, sound, costume, writing and set design.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg></div>
        <h3>Equipment &amp; production support</h3>
        <p>Access to gear and production know-how that would otherwise sit out of reach.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 5h13l3 3v11H4V5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 12l2.5 2.5L16 9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <h3>Community screenings</h3>
        <p>Public screenings that open dialogue between filmmakers and the communities they portray.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.6 6.6L21 10l-5 4.3L17.4 21 12 17.3 6.6 21 8 14.3 3 10l6.4-1.4L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></div>
        <h3>The NUFA Awards</h3>
        <p>An annual, growing platform that gives technical and creative excellence its due recognition.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 20v-1a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v1M15 4.2a3.5 3.5 0 0 1 0 6.6M17 20v-1a5 5 0 0 0-3-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/></svg></div>
        <h3>Advocacy &amp; policy</h3>
        <p>Pushing for the market access and policy support that make film a viable livelihood.</p>
      </div>
    </div>
  </div>
</section>

<section class="section-tight bg-cream2">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">Where We Work</div>
      <h2>Four regions. <span class="serif">One creative voice.</span></h2>
      <p class="lede">NUFA's reach spans the whole of Northern Uganda — bringing training, equipment access and recognition to creatives well beyond the capital.</p>
    </div>
    <div class="grid-4" data-reveal-stagger>
      <div class="stat-cell" style="align-items:center;text-align:center">
        <span class="stat-ico" style="background:var(--brand-100);color:var(--brand-700)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <b style="font-size:1.05rem">Acholi</b>
      </div>
      <div class="stat-cell" style="align-items:center;text-align:center">
        <span class="stat-ico" style="background:#FDEBEC;color:var(--red-600)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <b style="font-size:1.05rem">Lango</b>
      </div>
      <div class="stat-cell" style="align-items:center;text-align:center">
        <span class="stat-ico" style="background:var(--cream-2);color:var(--ink)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <b style="font-size:1.05rem">West Nile</b>
      </div>
      <div class="stat-cell" style="align-items:center;text-align:center">
        <span class="stat-ico" style="background:linear-gradient(135deg,var(--brand-300),var(--brand-600));color:var(--ink)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <b style="font-size:1.05rem">Karamoja</b>
      </div>
    </div>
  </div>
</section>

<?php
// icon = fb | x | ig | li
function social_icon($type){
  $icons = [
    'fb' => '<path d="M13.5 22v-8.4h2.8l.4-3.3h-3.2V8.1c0-.95.27-1.6 1.63-1.6H17V3.5c-.3-.04-1.3-.13-2.5-.13-2.47 0-4.16 1.5-4.16 4.27v2.6H7.5v3.3h2.84V22h3.16Z"/>',
    'x'  => '<path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8.2-9.3L1 2h7.2l5 6.6L18.9 2Zm-1.2 18h1.7L7.4 4h-1.8l12.1 16Z"/>',
    'ig' => '<path d="M12 8.4a3.6 3.6 0 1 0 0 7.2 3.6 3.6 0 0 0 0-7.2Zm0 5.9a2.3 2.3 0 1 1 0-4.6 2.3 2.3 0 0 1 0 4.6ZM16.9 6a.85.85 0 1 1 0 1.7.85.85 0 0 1 0-1.7Z"/><path d="M17 2H7a5 5 0 0 0-5 5v10a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V7a5 5 0 0 0-5-5Zm3.3 15a3.3 3.3 0 0 1-3.3 3.3H7A3.3 3.3 0 0 1 3.7 17V7A3.3 3.3 0 0 1 7 3.7h10A3.3 3.3 0 0 1 20.3 7v10Z"/>',
    'li' => '<path d="M6.94 8.5H3.56V20h3.38V8.5ZM5.25 3a1.96 1.96 0 1 0 0 3.92 1.96 1.96 0 0 0 0-3.92ZM20.44 20h-3.37v-5.9c0-1.4-.03-3.2-1.95-3.2-1.96 0-2.26 1.53-2.26 3.1V20H9.5V8.5h3.24v1.57h.05c.45-.86 1.56-1.77 3.21-1.77 3.44 0 4.07 2.26 4.07 5.2V20Z"/>',
  ];
  return '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">'.$icons[$type].'</svg>';
}
$ringColors = ['var(--brand-100)','#FDEBEC','var(--cream-2)','linear-gradient(135deg,var(--brand-300),var(--brand-600))','var(--brand-100)','#FDEBEC','var(--cream-2)','linear-gradient(135deg,var(--brand-300),var(--brand-600))'];
$ringIndex = 0;
function team_card($photo, $name, $role, $links){
  global $ringColors, $ringIndex;
  $bg = $ringColors[$ringIndex % count($ringColors)];
  $ringIndex++;
  echo '<div class="people-card">';
  echo '<div class="team-ring" style="background:'.$bg.'"><div class="ph"><img src="assets/images/team/'.$photo.'" alt="'.htmlspecialchars($name).' — NUFA '.htmlspecialchars($role).'" loading="lazy" width="200" height="200"></div></div>';
  echo '<b>'.htmlspecialchars($name).'</b><span>'.htmlspecialchars($role).'</span>';
  if(!empty($links)){
    echo '<div class="socials">';
    foreach($links as $type => $url){
      echo '<a href="'.htmlspecialchars($url).'" target="_blank" rel="noopener" aria-label="'.htmlspecialchars($name).' on '.strtoupper($type).'">'.social_icon($type).'</a>';
    }
    echo '</div>';
  }
  echo '</div>';
}
?>
<section class="section bg-paper">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">Meet Our Team</div>
      <h2 style="margin-top:16px">Our Team</h2>
    </div>
    <div class="grid-4" style="row-gap:44px" data-reveal-stagger>
      <?php
      team_card("team-president.webp", "Ojok Odong", "President", [
        "fb" => "https://www.facebook.com/ojok.francisodong",
        "x"  => "https://x.com/ojok_odong",
        "ig" => "https://www.instagram.com/ojokodong",
      ]);
      team_card("team-vice-president.webp", "Nimaro Precious", "Vice President", []);
      team_card("team-finance.webp", "Achiro Margereth Sharon", "Finance & Admin", []);
      team_card("team-publicity.webp", "Komakech Moses", "Publicity Secretary", [
        "x"  => "https://x.com/VandricajrMoze",
        "ig" => "https://www.instagram.com/bonomosesjr",
        "li" => "https://www.linkedin.com/in/moses-komakech-27a61120b/",
      ]);
      team_card("team-speaker.webp", "Ryekotoo Julius Peter", "Speaker", []);
      team_card("team-secretary.webp", "Okumu Robin", "Secretary", []);
      team_card("team-guild-leader.webp", "Akullu Susan", "Guild Leader", []);
      team_card("team-media-lead.webp", "Komakech Ravens Felix", "Media Lead", [
        "fb" => "https://www.facebook.com/ravens.frank",
        "x"  => "https://x.com/lucas_ravie",
        "ig" => "https://www.instagram.com/lucas_ravie",
      ]);
      ?>
    </div>
  </div>
</section>

<section class="section bg-cream2">
  <div class="container">
    <div class="cta-band" data-reveal>
      <div>
        <div class="eyebrow">Join The Movement</div>
        <h2 style="margin-top:16px">Are you a filmmaker in Northern Uganda?</h2>
        <p class="lede" style="margin-top:14px">Membership, training opportunities and NUFA Awards updates — all in one place.</p>
      </div>
      <a href="contact.php" class="btn btn-brand">Get In Touch</a>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
