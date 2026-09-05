<?php
$pageTitle = "Contact NUFA — Membership, Training & Press";
$pageDesc  = "Get in touch with NUFA for membership, training, press and partnership enquiries. Reach the Northern Uganda Filmmakers Association team in Gulu City.";
$activeNav = "contact";
$base = "";
$canonicalPath = "contact.php";
$ogImage = "assets/images/og/og-contact.jpg";
$ogImageAlt = "A guest in beaded regalia at the NUFA Awards";
include __DIR__ . "/includes/header.php";
require __DIR__ . "/includes/content.php";
require __DIR__ . "/includes/content-schemas.php";
$contactSchema = $CMS_SCHEMAS['contact']['fields'];
$contactVals = cms_load($conn, 'contact', $contactSchema);
function contact_c($key) { global $contactSchema, $contactVals; return cms_out($contactSchema, $contactVals, $key); }
?>
<a id="top"></a>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Home","item":"<?php echo $siteUrl; ?>/index.php"},
  {"@type":"ListItem","position":2,"name":"Contact","item":"<?php echo $siteUrl; ?>/contact.php"}
]}
</script>

<section class="page-hero">
  <div class="page-hero-bg"><img src="assets/images/<?php echo htmlspecialchars($contactVals['hero_image']); ?>" alt="" aria-hidden="true"></div>
  <div class="hero-glow g1"></div>
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>Contact</span></div>
    <div class="eyebrow"><?php echo contact_c('hero_eyebrow'); ?></div>
    <h1 style="margin-top:18px"><?php echo contact_c('hero_heading'); ?></h1>
    <p><?php echo contact_c('hero_lede'); ?></p>
  </div>
</section>

<section class="section bg-paper">
  <div class="container contact-grid">
    <div class="contact-info-card" data-reveal>
      <div class="contact-row">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <div><b>Visit</b><span><?php echo htmlspecialchars($siteSettings['site_address']); ?></span></div>
      </div>
      <div class="contact-row">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <div><b>Email</b><a href="mailto:<?php echo htmlspecialchars($siteSettings['site_email']); ?>"><?php echo htmlspecialchars($siteSettings['site_email']); ?></a></div>
      </div>
      <div class="contact-row">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .7 3a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.2-1.3a2 2 0 0 1 2.1-.5c1 .4 2 .6 3 .7a2 2 0 0 1 1.7 2Z" stroke="currentColor" stroke-width="1.6"/></svg></span>
        <div><b>Call</b><a href="tel:<?php echo htmlspecialchars(nufa_tel_href($siteSettings['site_phone'])); ?>"><?php echo htmlspecialchars($siteSettings['site_phone']); ?></a></div>
      </div>
      <div class="contact-row">
        <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M7 3v4M17 3v4M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.5"/></svg></span>
        <div><b>Office hours</b><span><?php echo contact_c('office_hours'); ?></span></div>
      </div>
      <div class="social-row">
        <a href="<?php echo htmlspecialchars($siteSettings['x_url']); ?>" target="_blank" rel="noopener" aria-label="X / Twitter"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.6 8.7L23.3 22h-7l-5.5-7.2L4.5 22H1.4l8.2-9.3L1 2h7.2l5 6.6L18.9 2Zm-1.2 18h1.7L7.4 4h-1.8l12.1 16Z"/></svg></a>
        <a href="<?php echo htmlspecialchars($siteSettings['facebook_url']); ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 22v-8.4h2.8l.4-3.3h-3.2V8.1c0-.95.27-1.6 1.63-1.6H17V3.5c-.3-.04-1.3-.13-2.5-.13-2.47 0-4.16 1.5-4.16 4.27v2.6H7.5v3.3h2.84V22h3.16Z"/></svg></a>
        <a href="<?php echo htmlspecialchars($siteSettings['instagram_url']); ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 8.4a3.6 3.6 0 1 0 0 7.2 3.6 3.6 0 0 0 0-7.2Zm0 5.9a2.3 2.3 0 1 1 0-4.6 2.3 2.3 0 0 1 0 4.6ZM16.9 6a.85.85 0 1 1 0 1.7.85.85 0 0 1 0-1.7Z"/><path d="M17 2H7a5 5 0 0 0-5 5v10a5 5 0 0 0 5 5h10a5 5 0 0 0 5-5V7a5 5 0 0 0-5-5Zm3.3 15a3.3 3.3 0 0 1-3.3 3.3H7A3.3 3.3 0 0 1 3.7 17V7A3.3 3.3 0 0 1 7 3.7h10A3.3 3.3 0 0 1 20.3 7v10Z"/></svg></a>
        <a href="<?php echo htmlspecialchars($siteSettings['linkedin_url']); ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.94 8.5H3.56V20h3.38V8.5ZM5.25 3a1.96 1.96 0 1 0 0 3.92 1.96 1.96 0 0 0 0-3.92ZM20.44 20h-3.37v-5.9c0-1.4-.03-3.2-1.95-3.2-1.96 0-2.26 1.53-2.26 3.1V20H9.5V8.5h3.24v1.57h.05c.45-.86 1.56-1.77 3.21-1.77 3.44 0 4.07 2.26 4.07 5.2V20Z"/></svg></a>
      </div>
    </div>

    <div class="form-card" data-reveal>
      <h3 style="font-size:1.4rem;margin-bottom:24px">Send us a message</h3>
      <?php if(isset($_GET['contact']) && $_GET['contact']==='sent'): ?>
        <div style="background:var(--brand-100);color:var(--brand-700);padding:14px 18px;border-radius:var(--radius-md);margin-bottom:20px;font-size:.9rem;font-weight:600">Thanks — your message has been sent. We'll get back to you soon.</div>
      <?php elseif(isset($_GET['contact']) && $_GET['contact']==='invalid'): ?>
        <div style="background:#FDEBEC;color:var(--red-600);padding:14px 18px;border-radius:var(--radius-md);margin-bottom:20px;font-size:.9rem;font-weight:600">Please fill in all fields with a valid email address.</div>
      <?php endif; ?>
      <form action="contact-submit.php" method="post">
        <input type="hidden" name="redirect" value="contact.php">
        <div class="form-grid">
          <div class="field">
            <label for="c-name">Full name</label>
            <input id="c-name" name="name" type="text" placeholder="Your name" required>
          </div>
          <div class="field">
            <label for="c-email">Email address</label>
            <input id="c-email" name="email" type="email" placeholder="you@email.com" required>
          </div>
        </div>
        <div class="field">
          <label for="c-subject">I'm reaching out about</label>
          <select id="c-subject" name="subject">
            <option>Membership</option>
            <option>Training &amp; workshops</option>
            <option>NUFA Awards 2027</option>
            <option>Partnership &amp; sponsorship</option>
            <option>Press &amp; media</option>
            <option>Other</option>
          </select>
        </div>
        <div class="field full">
          <label for="c-msg">Message</label>
          <textarea id="c-msg" name="message" placeholder="How can we help?" required></textarea>
        </div>
        <button type="submit" class="btn btn-brand" style="width:100%">Send Message</button>
      </form>
    </div>
  </div>
</section>

<section class="section-tight bg-cream2">
  <div class="container">
    <div class="map-embed" data-reveal>
      <iframe src="https://www.openstreetmap.org/export/embed.html?bbox=32.27%2C2.76%2C32.30%2C2.79&layer=mapnik&marker=2.774%2C32.285" style="width:100%;height:100%;border:0" loading="lazy" title="Acholi Inn, Gulu City map"></iframe>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
