</main>
<?php
if (!isset($base)) $base = "";
if (!isset($cfg)) { require_once __DIR__ . "/settings.php"; require_once __DIR__ . "/icons.php"; $cfg = nufa_site_settings($conn); }
require_once __DIR__ . "/menu.php";
require_once __DIR__ . "/content.php";
$fe = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$logoRel = ($cfg['site_logo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $cfg['site_logo'])) ? $cfg['site_logo'] : 'logo/nufa-logo.png';
$footer1 = nufa_menu($conn, 'footer1'); $footer2 = nufa_menu($conn, 'footer2'); $footerLegal = nufa_menu($conn, 'footer_legal');
$mobileCta2Label = cms_value('site', 'mobile_cta2_label');
?>
<footer class="site-footer">
  <div class="container-wide footer-top">
    <div class="footer-brand">
      <a href="<?php echo $base; ?>index.php" class="brand">
        <span class="mark"><img src="<?php echo $base; ?>assets/images/<?php echo $fe($logoRel); ?>" alt="" width="46" height="46" loading="lazy"></span>
        <span class="word">NUFA<small><?php echo $fe($cfg['site_name']); ?></small></span>
      </a>
      <p class="footer-tag"><?php echo cms_t('site', 'footer_tagline'); ?></p>
      <p><?php echo cms_t('site', 'footer_description'); ?></p>
      <div class="social-row">
        <?php foreach (['x_url' => ['X', 'x'], 'facebook_url' => ['Facebook', 'facebook'], 'instagram_url' => ['Instagram', 'instagram'], 'linkedin_url' => ['LinkedIn', 'linkedin'], 'youtube_url' => ['YouTube', 'youtube']] as $fk => [$flabel, $fic]):
          if ($cfg[$fk] === '') continue; ?>
          <a href="<?php echo $fe($cfg[$fk]); ?>" target="_blank" rel="noopener" aria-label="<?php echo $flabel; ?>"><?php echo nufa_icon($fic, 16); ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="footer-col">
      <h4><?php echo cms_t('site', 'footer_col1_title'); ?></h4>
      <ul><?php foreach ($footer1 as $it): ?><li><a href="<?php echo nufa_href($it['url']); ?>"<?php echo nufa_link_attrs($it); ?>><?php echo $fe($it['label']); ?></a></li><?php endforeach; ?></ul>
    </div>

    <div class="footer-col">
      <h4><?php echo cms_t('site', 'footer_col2_title'); ?></h4>
      <ul><?php foreach ($footer2 as $it): ?><li><a href="<?php echo nufa_href($it['url']); ?>"<?php echo nufa_link_attrs($it); ?>><?php echo $fe($it['label']); ?></a></li><?php endforeach; ?></ul>
    </div>

    <div class="footer-col">
      <h4><?php echo cms_t('site', 'footer_col3_title'); ?></h4>
      <address>
        <strong style="color:var(--white)"><?php echo $fe($cfg['site_name']); ?></strong>
        <span><?php echo $fe($cfg['site_address']); ?></span>
        <a href="mailto:<?php echo $fe($cfg['site_email']); ?>"><?php echo $fe($cfg['site_email']); ?></a>
        <a href="tel:<?php echo nufa_tel_href($cfg['site_phone']); ?>"><?php echo $fe($cfg['site_phone']); ?></a>
        <?php if ($cfg['site_phone2'] !== ''): ?><a href="tel:<?php echo nufa_tel_href($cfg['site_phone2']); ?>"><?php echo $fe($cfg['site_phone2']); ?></a><?php endif; ?>
        <a href="<?php echo $fe($cfg['site_website']); ?>"><?php echo $fe(preg_replace('#^https?://#', '', rtrim($cfg['site_website'], '/'))); ?></a>
      </address>
    </div>
  </div>

  <div class="container-wide footer-bottom">
    <span>© <span data-year>2026</span> <?php echo cms_t('site', 'footer_copyright'); ?></span>
    <div class="legal"><?php foreach ($footerLegal as $it): ?><a href="<?php echo nufa_href($it['url']); ?>"<?php echo nufa_link_attrs($it); ?>><?php echo $fe($it['label']); ?></a><?php endforeach; ?></div>
  </div>
</footer>

<?php if (($cfg['pwa_enabled'] ?? '1') === '1'): ?>
<div class="install-banner" data-install-banner hidden role="dialog" aria-label="Install the NUFA app"><img src="<?php echo $base; ?>assets/images/pwa/icon-192.png" alt="" width="44" height="44"><div><b>Install the NUFA app</b><span>Open NUFA from your home screen — fast, full-screen, works offline.</span></div><button type="button" class="btn btn-gold btn-sm" data-install-go>Install</button><button type="button" class="install-x" data-install-dismiss aria-label="Not now">&times;</button></div>
<div class="ios-help" data-ios-help hidden role="dialog" aria-label="Add NUFA to your home screen"><div class="ios-help__box"><button type="button" class="install-x" data-ios-close aria-label="Close">&times;</button><h3>Add NUFA to your home screen</h3><ol><li>Tap the <b>Share</b> button in Safari (the square with an arrow).</li><li>Scroll down and choose <b>Add to Home Screen</b>.</li><li>Tap <b>Add</b> — NUFA now opens like an app.</li></ol></div></div>
<?php endif; ?>
<a href="#top" class="to-top" aria-label="Back to top"><?php echo nufa_icon('up', 18); ?></a>

<div class="lightbox" role="dialog" aria-label="Image viewer" aria-hidden="true">
  <button class="lightbox-close" aria-label="Close">&times;</button>
  <button class="lightbox-prev" aria-label="Previous image">&#8249;</button>
  <img src="" alt="">
  <button class="lightbox-next" aria-label="Next image">&#8250;</button>
  <div class="lightbox-caption"></div>
</div>

<?php if (($cfg['pwa_enabled'] ?? '1') === '1'): ?><script>if('serviceWorker' in navigator){window.addEventListener('load',function(){navigator.serviceWorker.register('<?php echo $base; ?>sw.php').catch(function(){});});}</script>
<?php endif; ?>
<script src="<?php echo $base; ?>assets/js/main.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/main.js'); ?>" defer></script>
</body>
</html>
