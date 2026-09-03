<?php
$pageTitle = "NUFA Awards Gallery — Red Carpet & Gala Photos";
$pageDesc  = "Pictorials from the NUFA Awards — the red carpet, the gala night and the community driving Northern Uganda's film industry forward.";
$activeNav = "gallery";
$base = "";
$canonicalPath = "gallery.php";
$ogImage = "assets/images/og/og-gallery.jpg";
$ogImageAlt = "Guests at the NUFA Awards gala night";
include __DIR__ . "/includes/header.php";
?>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Home","item":"<?php echo $siteUrl; ?>/index.php"},
  {"@type":"ListItem","position":2,"name":"Gallery","item":"<?php echo $siteUrl; ?>/gallery.php"}
]}
</script>
<?php
$photos26 = [
  ["nufa26-01.jpg","NUFA26 accreditation on the red carpet"],
  ["nufa26-02.jpg","Guest arriving at NUFA26"],
  ["nufa26-03.jpg","NUFA26 red carpet arrival"],
  ["nufa26-04.jpg","Guest posing on the NUFA26 red carpet"],
  ["nufa26-05.jpg","NUFA26 guests"],
  ["nufa26-06.jpg","NUFA26 arrival"],
  ["nufa26-07.jpg","NUFA26 red carpet moment"],
  ["nufa26-08.jpg","Guests mingling at NUFA26"],
  ["nufa26-09.jpg","NUFA26 red carpet"],
  ["nufa26-10.jpg","Guest smiling at the NUFA26 gala table"],
  ["nufa26-11.jpg","NUFA26 evening"],
  ["nufa26-12.jpg","Cultural fashion at NUFA26"],
  ["nufa26-13.jpg","NUFA26 red carpet moment"],
  ["nufa26-14.jpg","Celebrating at NUFA26"],
  ["nufa26-15.jpg","NUFA26 award night"],
];
$photos25 = [];
for($i=1;$i<=21;$i++){
  $photos25[] = ["nufa25-".sprintf('%02d',$i).".webp","NUFA25 Awards moment #$i"];
}
?>
<a id="top"></a>

<section class="page-hero">
  <div class="page-hero-bg"><img src="assets/images/gallery/2025/nufa25-12.webp" alt="" aria-hidden="true"></div>
  <div class="hero-glow g1"></div>
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>Gallery</span></div>
    <div class="eyebrow">Pictorials</div>
    <h1 style="margin-top:18px">The moments behind the red carpet</h1>
    <p>A running record of every NUFA Awards edition — from the inaugural 2025 gala to NUFA26 at Acholi Inn.</p>
  </div>
</section>

<section class="section-tight bg-paper">
  <div class="container">
    <div class="gallery-tabs" data-reveal>
      <button class="gallery-tab is-active" data-filter="all">All</button>
      <button class="gallery-tab" data-filter="2026">NUFA26 Gala</button>
      <button class="gallery-tab" data-filter="2025">NUFA25 Gala</button>
    </div>
  </div>
</section>

<section class="section-tight bg-paper" style="padding-top:24px">
  <div class="container-wide">
    <div class="masonry" data-reveal>
      <?php foreach($photos26 as $p): ?>
      <div class="m-item" data-gallery-item="2026">
        <img src="assets/images/gallery/2026/<?php echo $p[0]; ?>"
             data-lightbox="assets/images/gallery/2026/<?php echo $p[0]; ?>"
             data-caption="<?php echo htmlspecialchars($p[1]); ?> — NUFA26, Acholi Inn, Gulu"
             alt="<?php echo htmlspecialchars($p[1]); ?>" loading="lazy">
        <div class="m-overlay"><span>NUFA26 · Acholi Inn</span></div>
      </div>
      <?php endforeach; ?>
      <?php foreach($photos25 as $p): ?>
      <div class="m-item" data-gallery-item="2025">
        <img src="assets/images/gallery/2025/<?php echo $p[0]; ?>"
             data-lightbox="assets/images/gallery/2025/<?php echo $p[0]; ?>"
             data-caption="<?php echo htmlspecialchars($p[1]); ?> — NUFA25, Gulu"
             alt="<?php echo htmlspecialchars($p[1]); ?>" loading="lazy">
        <div class="m-overlay"><span>NUFA25 · Gulu</span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-cream2">
  <div class="container">
    <div class="cta-band" data-reveal>
      <div>
        <div class="eyebrow">Were You There?</div>
        <h2 style="margin-top:16px">Follow us for the full NUFA27 pictorial drop</h2>
      </div>
      <a href="contact.php" class="btn btn-brand">Stay Updated</a>
    </div>
  </div>
</section>

<?php include __DIR__ . "/includes/footer.php"; ?>
