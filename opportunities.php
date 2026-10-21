<?php
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/opportunities.php";
opp_install($conn);
$pageTitle = "Opportunities for Filmmakers — Awards, Funding, Labs & Calls | NUFA";
$pageDesc  = "Film awards, festivals, funding in Uganda and internationally, labs and other opportunities for filmmakers — with eligibility criteria and links to apply.";
$activeNav = "opportunities";
$canonicalPath = "opportunities.php";
include __DIR__ . "/includes/header.php";

$cats = opp_categories(); $sts = opp_statuses();
$rows = $conn->query("SELECT * FROM opportunities WHERE is_active=1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$rank = ['open' => 0, 'rolling' => 1, 'check' => 2, 'closed' => 3];
foreach ($rows as &$r) $r['_st'] = opp_status($r); unset($r);
usort($rows, function ($a, $b) use ($rank) {
    if ($a['_st'] !== $b['_st']) return $rank[$a['_st']] <=> $rank[$b['_st']];
    if ($a['_st'] === 'open') return strcmp($a['deadline'] ?: '9999', $b['deadline'] ?: '9999');
    return (int)$b['is_featured'] <=> (int)$a['is_featured'];
});
$counts = array_fill_keys(array_keys($cats), 0); foreach ($rows as $r) if (isset($counts[$r['category']])) $counts[$r['category']]++;
$closing = array_filter($rows, fn($r) => $r['_st'] === 'open' && $r['deadline'] && strtotime($r['deadline']) - time() < 21 * 86400);

echo ui_page_hero('Resources', 'Opportunities for filmmakers', 'Film awards and festivals, funding in Uganda and abroad, labs and training — with who can apply and where to apply. Find the ones that fit your film.', cms_img_raw('gallery', 'hero_image'), [['Resources', 'resources.php'], ['Opportunities']],
    '<a href="#list" class="btn btn-gold">Browse opportunities</a><a href="contact.php?topic=Opportunities" class="btn btn-outline">Suggest an opportunity</a>');
?>
<section class="section" id="list">
  <div class="container">
    <div class="op-note" data-reveal><b>Please read first.</b> NUFA lists these opportunities for information only. Calls, amounts and deadlines change — always confirm the details on the organiser's official page before you apply. Applications go to the organiser, not to NUFA.</div>
    <?php if ($closing): ?><div class="op-soon"><b>Closing soon:</b> <?php echo implode(' · ', array_map(fn($r) => '<a href="#op-' . (int)$r['id'] . '">' . ui_e($r['title']) . '</a> (' . ui_e(date('j M', strtotime($r['deadline']))) . ')', $closing)); ?></div><?php endif; ?>

    <div class="op-tools">
      <div class="filter-bar" role="tablist" aria-label="Filter opportunities">
        <button class="op-tab is-active" type="button" data-cat="all">All <small><?php echo count($rows); ?></small></button>
        <?php foreach ($cats as $k => $l) if ($counts[$k]): ?><button class="op-tab" type="button" data-cat="<?php echo $k; ?>"><?php echo ui_e($l); ?> <small><?php echo $counts[$k]; ?></small></button><?php endif; ?>
      </div>
      <div class="op-search"><input type="search" id="op-q" placeholder="Search by name, country or keyword" aria-label="Search opportunities"><label class="op-check"><input type="checkbox" id="op-hide"> Hide closed</label></div>
    </div>

    <?php if (!$rows): ?><div class="empty-state"><h3>Opportunities coming soon</h3><p>We are compiling opportunities for filmmakers. Check back shortly.</p></div><?php else: ?>
    <div class="op-grid" id="op-grid">
      <?php foreach ($rows as $r): $st = $r['_st'];
        $elig = array_filter(array_map('trim', preg_split('/\R/', (string)$r['eligibility'])));
        $hay = strtolower($r['title'] . ' ' . $r['organisation'] . ' ' . $r['region'] . ' ' . $r['summary'] . ' ' . $r['eligibility']); ?>
      <article class="op-card <?php echo $st === 'closed' ? 'is-closed' : ''; ?>" id="op-<?php echo (int)$r['id']; ?>" data-cat="<?php echo ui_e($r['category']); ?>" data-closed="<?php echo $st === 'closed' ? 1 : 0; ?>" data-hay="<?php echo ui_e($hay); ?>">
        <div class="op-top"><span class="op-cat"><?php echo ui_e($cats[$r['category']] ?? 'Other'); ?></span><span class="op-st op-st--<?php echo $st; ?>"><?php echo ui_e($sts[$st]); ?></span></div>
        <h3><?php echo ui_e($r['title']); ?></h3>
        <p class="op-org"><?php echo ui_e($r['organisation']); ?><?php if ($r['region'] !== ''): ?> · <?php echo ui_e($r['region']); ?><?php endif; ?></p>
        <?php if (trim((string)$r['summary']) !== ''): ?><p class="op-sum"><?php echo ui_e($r['summary']); ?></p><?php endif; ?>
        <dl class="op-facts">
          <?php if ($r['funding'] !== ''): ?><div><dt>Funding / benefit</dt><dd><?php echo ui_e($r['funding']); ?></dd></div><?php endif; ?>
          <div><dt>Deadline</dt><dd><?php if ($r['deadline']): echo ui_e(date('j F Y', strtotime($r['deadline']))); echo $st === 'closed' ? ' (passed)' : ''; else: echo ui_e($r['deadline_note'] !== '' ? $r['deadline_note'] : 'See the official page'); endif; ?></dd></div>
          <?php if ($r['deadline'] && $r['deadline_note'] !== ''): ?><div><dt>Note</dt><dd><?php echo ui_e($r['deadline_note']); ?></dd></div><?php endif; ?>
        </dl>
        <?php if ($elig): ?><details class="op-elig" <?php echo $st === 'open' ? 'open' : ''; ?>><summary>Eligibility &amp; requirements</summary><ul><?php foreach ($elig as $e) echo '<li>' . ui_e($e) . '</li>'; ?></ul></details><?php endif; ?>
        <div class="op-foot">
          <?php if ($r['apply_url'] !== ''): ?><a class="btn btn-gold btn-sm" href="<?php echo ui_e($r['apply_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo $st === 'closed' ? 'Official page' : 'Apply / learn more'; ?> ↗</a><?php endif; ?>
          <?php if ($r['source_url'] !== '' && $r['source_url'] !== $r['apply_url']): ?><a class="op-src" href="<?php echo ui_e($r['source_url']); ?>" target="_blank" rel="noopener noreferrer">Source</a><?php endif; ?>
          <?php if ($r['verified_on']): ?><span class="op-ver">Checked <?php echo ui_e(date('j M Y', strtotime($r['verified_on']))); ?></span><?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="empty-state" id="op-none" hidden>No opportunities match. Try another filter or search.</p>
    <?php endif; ?>

    <div class="op-cta" data-reveal>
      <div><h3>Know an opportunity we should list?</h3><p>Funders, festivals and organisers can send us a call for filmmakers and we will review it for the page.</p></div>
      <a class="btn btn-gold" href="contact.php?topic=Opportunities">Suggest an opportunity</a>
    </div>
  </div>
</section>
<script>
(function(){var cat='all',q=document.getElementById('op-q'),hide=document.getElementById('op-hide'),cards=[].slice.call(document.querySelectorAll('.op-card')),none=document.getElementById('op-none');
function run(){var t=(q.value||'').toLowerCase().trim(),n=0;cards.forEach(function(c){var ok=(cat==='all'||c.dataset.cat===cat)&&(!hide.checked||c.dataset.closed!=='1')&&(!t||c.dataset.hay.indexOf(t)>-1);c.hidden=!ok;if(ok)n++;});if(none)none.hidden=n>0;}
document.querySelectorAll('.op-tab').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.op-tab').forEach(function(x){x.classList.remove('is-active')});b.classList.add('is-active');cat=b.dataset.cat;run();});});
q&&q.addEventListener('input',run);hide&&hide.addEventListener('change',run);})();
</script>
<?php include __DIR__ . "/includes/footer.php"; ?>
