<?php
$pageTitle = "Partners & Sponsors — NUFA | Northern Uganda Filmmakers Association";
$pageDesc  = "Meet the organisations backing NUFA and the NUFA Awards, and find out how your brand can partner with Northern Uganda's film industry.";
$activeNav = "partners";
$base = "";
$canonicalPath = "partners.php";
require __DIR__ . "/includes/db.php";
include __DIR__ . "/includes/header.php";
?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Home","item":"<?php echo $siteUrl; ?>/index.php"},
  {"@type":"ListItem","position":2,"name":"Partners","item":"<?php echo $siteUrl; ?>/partners.php"}
]}
</script>
<?php
$partners = [];
$res = $conn->query("SELECT * FROM partners WHERE is_active=1 ORDER BY sort_order ASC");
while ($row = $res->fetch_assoc()) {
    $row['has_logo'] = file_exists(__DIR__ . "/assets/images/" . $row['logo']);
    $partners[] = $row;
}
?>
<a id="top"></a>

<section class="page-hero">
  <div class="page-hero-bg"><img src="assets/images/gallery/2025/nufa25-19.webp" alt="" aria-hidden="true"></div>
  <div class="hero-glow g1"></div>
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>Partners</span></div>
    <div class="eyebrow">Partners &amp; Sponsors</div>
    <h1 style="margin-top:18px">Backed by organisations who believe in Northern storytelling</h1>
    <p>From media houses to technology firms, these partners make NUFA's training and the NUFA Awards possible.</p>
  </div>
</section>

<section class="section bg-paper">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">NUFA26 Partners</div>
      <h2>Standing behind <span class="serif">Northern Uganda's creatives</span></h2>
    </div>
    <div class="partner-wall" data-reveal-stagger>
      <?php foreach ($partners as $p): ?>
      <div class="partner-tile">
        <?php if ($p['has_logo']): ?>
          <img src="assets/images/<?php echo htmlspecialchars($p['logo']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?> — NUFA <?php echo htmlspecialchars($p['tier']); ?>" loading="lazy">
        <?php else: ?>
          <div><b><?php echo htmlspecialchars($p['name']); ?></b><span><?php echo htmlspecialchars($p['tier']); ?></span></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="lede" style="text-align:center;margin-inline:auto;margin-top:32px">This wall updates every edition — new partner logos can be added straight from the admin dashboard.</p>
  </div>
</section>

<section class="section bg-cream2" id="become">
  <div class="container">
    <div class="section-head center">
      <div class="eyebrow">Why Partner With NUFA</div>
      <h2>Put your brand where the region is <span class="serif">watching</span></h2>
    </div>
    <div class="grid-3" data-reveal-stagger>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg></div>
        <h3>Regional visibility</h3>
        <p>Reach audiences and creatives across Acholi, Lango, West Nile and Karamoja — on the red carpet and year-round.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.6 6.6L21 10l-5 4.3L17.4 21 12 17.3 6.6 21 8 14.3 3 10l6.4-1.4L12 2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></div>
        <h3>Credible association</h3>
        <p>Align your brand with a registered, professional body building a real film industry — not a one-off event.</p>
      </div>
      <div class="value-card">
        <div class="ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M4 20v-1a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v1M15 4.2a3.5 3.5 0 0 1 0 6.6M17 20v-1a5 5 0 0 0-3-4.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/></svg></div>
        <h3>Direct community access</h3>
        <p>Connect with 700+ trained creatives and the communities that turn out for every screening and awards night.</p>
      </div>
    </div>
  </div>
</section>

<section class="section bg-paper">
  <div class="container">
    <div class="grid-2" style="align-items:stretch">
      <div class="assoc-visual" data-reveal>
        <div class="frame-main"><img src="assets/images/gallery/2025/nufa25-06.webp" alt="NUFA Awards partners and guests"></div>
      </div>
      <div class="form-card" data-reveal>
        <div class="eyebrow" style="margin-bottom:14px">Partnership Enquiry</div>
        <h3 style="font-size:1.4rem;margin-bottom:24px">Tell us about your organisation</h3>
        <?php if(isset($_GET['contact']) && $_GET['contact']==='sent'): ?>
          <div style="background:var(--brand-100);color:var(--brand-700);padding:14px 18px;border-radius:var(--radius-md);margin-bottom:20px;font-size:.9rem;font-weight:600">Thanks — your enquiry has been sent. We'll be in touch soon.</div>
        <?php endif; ?>
        <form action="contact-submit.php" method="post">
          <input type="hidden" name="redirect" value="partners.php">
          <div class="field">
            <label for="p-org">Organisation name</label>
            <input id="p-org" name="name" type="text" placeholder="Your company or organisation" required>
          </div>
          <div class="field">
            <label for="p-email">Email address</label>
            <input id="p-email" name="email" type="email" placeholder="you@company.com" required>
          </div>
          <div class="field">
            <label for="p-tier">Interested in</label>
            <select id="p-tier" name="subject">
              <option>NUFA Awards 2027 sponsorship</option>
              <option>Media / distribution partnership</option>
              <option>Training &amp; equipment support</option>
              <option>Other</option>
            </select>
          </div>
          <div class="field full">
            <label for="p-msg">Message</label>
            <textarea id="p-msg" name="message" placeholder="Tell us a bit about what you have in mind" required></textarea>
          </div>
          <button type="submit" class="btn btn-brand" style="width:100%">Send Enquiry</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
