<?php
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/filmfund.php";
$F = ff_settings($conn);
$pageTitle = $F['ff_hero_title'] . ' | NUFA';
$pageDesc = $F['ff_hero_lede'];
$activeNav = "filmfund";
$canonicalPath = "film-fund.php";
include __DIR__ . "/includes/header.php";
$e = fn($s) => ui_e($s);
echo ui_page_hero('NUFA Film Fund', $F['ff_hero_title'], $F['ff_hero_lede'], 'action.webp', [['NUFA Film Fund']], '');
$btn = function ($n, $cls) use ($F, $e) { return ($F["ff_btn{$n}_label"] !== '' && $F["ff_btn{$n}_url"] !== '') ? '<a class="btn ' . $cls . '" href="' . $e($F["ff_btn{$n}_url"]) . '">' . $e($F["ff_btn{$n}_label"]) . '</a>' : ''; };
?>
<section class="section">
  <div class="container" style="max-width:860px">
    <div class="section-head" data-reveal><div class="eyebrow">NUFA Film Fund</div><h2><?php echo $e($F['ff_intro_title']); ?></h2></div>
    <div data-reveal style="font-size:1.05rem;line-height:1.8;color:#333"><?php foreach (preg_split('/\n{2,}/', trim($F['ff_intro'])) as $p) if (trim($p) !== '') echo '<p>' . nl2br($e(trim($p))) . '</p>'; ?></div>
  </div>
</section>
<?php if ($F['ff_status_show'] === '1' && trim($F['ff_status']) !== ''): ?>
<section class="section--tight"><div class="container"><div class="ff-note" role="note"><?php echo nufa_icon('compass', 26); ?><div><b>Please note:</b> <?php echo $e($F['ff_status']); ?></div></div></div></section>
<?php endif; ?>
<section class="section">
  <div class="container">
    <div class="section-head center" data-reveal><div class="eyebrow">Step by step</div><h2><?php echo $e($F['ff_steps_title']); ?></h2></div>
    <div class="ff-steps" data-reveal-stagger>
      <?php foreach ([1, 2, 3] as $n): if ($F["ff_step{$n}_t"] === '' && $F["ff_step{$n}_d"] === '') continue; ?>
      <div class="ff-step"><h3><?php echo $e($F["ff_step{$n}_t"]); ?></h3><p><?php echo $e($F["ff_step{$n}_d"]); ?></p></div>
      <?php endforeach; ?>
    </div>
    <div class="ff-cta"><?php echo $btn(1, 'btn-gold') . $btn(2, 'btn-outline'); ?></div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
