<?php
http_response_code(404);
require_once __DIR__ . "/includes/ui.php";
$pageTitle = "Page not found — NUFA";
$pageDesc  = "The page you were looking for could not be found.";
$activeNav = "";
$noindex = true;
$canonicalPath = "404.php";
include __DIR__ . "/includes/header.php";
?>
<section class="section not-found">
  <div class="container">
    <div class="code" aria-hidden="true">404</div>
    <h1 style="font-size:clamp(1.6rem,3vw,2.4rem)">This scene isn't on the reel</h1>
    <p class="lede" style="margin:0 auto 28px">The page you were looking for may have moved or no longer exists. Try one of these instead.</p>
    <div class="actions" style="justify-content:center">
      <a href="index.php" class="btn btn-gold">Back to home</a>
      <a href="news.php" class="btn btn-outline-dark">News &amp; Stories</a>
      <a href="contact.php" class="btn btn-outline-dark">Contact us</a>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
