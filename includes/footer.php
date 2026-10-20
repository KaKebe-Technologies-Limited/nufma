</main>
<?php
if (!isset($base)) $base = "";
if (!isset($cfg)) { require_once __DIR__ . "/settings.php"; require_once __DIR__ . "/icons.php"; $cfg = nufa_site_settings($conn); }
$fe = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<footer class="site-footer">
  <div class="container-wide footer-top">
    <div class="footer-brand">
      <a href="<?php echo $base; ?>index.php" class="brand">
        <span class="mark"><img src="<?php echo $base; ?>assets/images/logo/nufa-logo.png" alt="" width="46" height="46" loading="lazy"></span>
        <span class="word">NUFA<small>Northern Uganda Filmmakers Association</small></span>
      </a>
      <p class="footer-tag">Our Stories. Our Culture. Our Future.</p>
      <p>NUFA brings filmmakers together, strengthens their craft, celebrates their achievements, and helps ensure that Northern Uganda's stories are created, preserved and shared with the world.</p>
      <div class="social-row">
        <?php foreach (['x_url' => ['X', 'x'], 'facebook_url' => ['Facebook', 'facebook'], 'instagram_url' => ['Instagram', 'instagram'], 'linkedin_url' => ['LinkedIn', 'linkedin'], 'youtube_url' => ['YouTube', 'youtube']] as $k => [$label, $ic]):
          if ($cfg[$k] === '') continue; ?>
          <a href="<?php echo $fe($cfg[$k]); ?>" target="_blank" rel="noopener" aria-label="<?php echo $label; ?>"><?php echo nufa_icon($ic, 16); ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="footer-col">
      <h4>Explore</h4>
      <ul>
        <li><a href="<?php echo $base; ?>about.php">About NUFA</a></li>
        <li><a href="<?php echo $base; ?>programmes.php">Programmes</a></li>
        <li><a href="<?php echo $base; ?>sacco.php">NUFA SACCO (proposed)</a></li>
        <li><a href="<?php echo $base; ?>network.php">Industry Network</a></li>
        <li><a href="<?php echo $base; ?>news.php">News &amp; Stories</a></li>
        <li><a href="<?php echo $base; ?>resources.php">Resources</a></li>
        <li><a href="<?php echo $base; ?>gallery.php">Gallery &amp; Video</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Get Involved</h4>
      <ul>
        <li><a href="<?php echo $base; ?>awards.php">NUFA Awards</a></li>
        <li><a href="<?php echo $base; ?>membership.php">Membership</a></li>
        <li><a href="<?php echo $base; ?>partners.php#enquiry">Partnership enquiries</a></li>
        <li><a href="<?php echo $base; ?>contact.php">Contact us</a></li>
        <li><a href="<?php echo $base; ?>policy.php?p=privacy">Privacy Policy</a></li>
        <li><a href="<?php echo $base; ?>policy.php?p=terms">Terms of Use</a></li>
        <li><a href="<?php echo $base; ?>policy.php?p=accessibility">Accessibility</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Contact</h4>
      <address>
        <strong style="color:var(--white)">Northern Uganda Filmmakers Association</strong>
        <span><?php echo $fe($cfg['site_address']); ?></span>
        <a href="mailto:<?php echo $fe($cfg['site_email']); ?>"><?php echo $fe($cfg['site_email']); ?></a>
        <a href="tel:<?php echo nufa_tel_href($cfg['site_phone']); ?>"><?php echo $fe($cfg['site_phone']); ?></a>
        <?php if ($cfg['site_phone2'] !== ''): ?><a href="tel:<?php echo nufa_tel_href($cfg['site_phone2']); ?>"><?php echo $fe($cfg['site_phone2']); ?></a><?php endif; ?>
        <a href="<?php echo $fe($cfg['site_website']); ?>"><?php echo $fe(preg_replace('#^https?://#', '', rtrim($cfg['site_website'], '/'))); ?></a>
      </address>
    </div>
  </div>

  <div class="container-wide footer-bottom">
    <span>© <span data-year>2026</span> Northern Uganda Filmmakers Association (NUFA). All rights reserved.</span>
    <div class="legal">
      <a href="<?php echo $base; ?>policy.php?p=privacy">Privacy</a>
      <a href="<?php echo $base; ?>policy.php?p=cookies">Cookies</a>
      <a href="<?php echo $base; ?>policy.php?p=permissions">Image &amp; content permissions</a>
      <a href="<?php echo $base; ?>sitemap.php">Sitemap</a>
    </div>
  </div>
</footer>

<a href="#top" class="to-top" aria-label="Back to top"><?php echo nufa_icon('up', 18); ?></a>

<div class="lightbox" role="dialog" aria-label="Image viewer" aria-hidden="true">
  <button class="lightbox-close" aria-label="Close">&times;</button>
  <button class="lightbox-prev" aria-label="Previous image">&#8249;</button>
  <img src="" alt="">
  <button class="lightbox-next" aria-label="Next image">&#8250;</button>
  <div class="lightbox-caption"></div>
</div>

<script src="<?php echo $base; ?>assets/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/main.js'); ?>" defer></script>
</body>
</html>
