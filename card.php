<?php
/**
 * Public membership card — reached through the private link e-mailed to the
 * member (card.php?t=<token>). Doubles as a verification page.
 */
require __DIR__ . "/includes/membership.php";

$token = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
$m = null;
if (strlen($token) === 32) {
    $st = $conn->prepare("SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE m.card_token=? AND m.status='approved' LIMIT 1");
    $st->bind_param("s", $token);
    $st->execute();
    $m = $st->get_result()->fetch_assoc();
}
if (!$m) {
    http_response_code(404);
    $pageTitle = "Card not found — NUFA";
    $activeNav = "membership"; $base = "";
    include __DIR__ . "/includes/header.php";
    echo '<section class="section bg-paper" style="padding-top:200px"><div class="container" style="text-align:center;max-width:560px"><h2>Membership card not found</h2><p class="lede" style="margin:14px auto 24px">This link is invalid, or the membership has not been approved.</p><a class="btn btn-brand" href="membership.php">Apply for membership</a></div></section>';
    include __DIR__ . "/includes/footer.php";
    exit;
}

$S = mem_settings($conn);
$expired = $m['valid_until'] && strtotime($m['valid_until'] . ' 23:59:59') < time();
$photoSrc = '';
if ($m['photo'] && is_file(MEM_STORAGE_DIR . $m['photo'])) {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file(MEM_STORAGE_DIR . $m['photo']);
    $photoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(MEM_STORAGE_DIR . $m['photo']));
}
$sig = $S['president_signature'];
$sigOk = $sig !== '' && is_file(__DIR__ . '/assets/images/' . $sig);
$fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';

$pageTitle = "NUFA Membership Card — " . $m['full_name'];
$pageDesc = "NUFA member verification";
$activeNav = "membership"; $base = "";
include __DIR__ . "/includes/header.php";
?>
<section class="section bg-cream2 card-page" style="padding-top:170px">
  <div class="container" style="max-width:720px">
    <div class="card-actions no-print">
      <div>
        <h2 style="font-size:1.4rem">Your NUFA membership card</h2>
        <p style="color:var(--text-muted);font-size:.9rem;margin-top:4px">Print it or choose “Save as PDF” in the print dialog.</p>
      </div>
      <button type="button" class="btn btn-brand btn-sm" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="mcard <?php echo $expired ? 'is-expired' : ''; ?>" id="mcard">
      <div class="mcard-top">
        <img src="assets/images/logo/nufa-logo.png" alt="" class="mcard-logo">
        <div><b>NUFA</b><span>Northern Uganda Filmmakers Association</span></div>
        <em>MEMBER</em>
      </div>
      <div class="mcard-body">
        <div class="mcard-photo"><?php if ($photoSrc): ?><img src="<?php echo $photoSrc; ?>" alt=""><?php else: ?><span><?php echo htmlspecialchars(mb_substr($m['full_name'], 0, 1)); ?></span><?php endif; ?></div>
        <div class="mcard-info">
          <h3><?php echo htmlspecialchars($m['full_name']); ?></h3>
          <p class="mcard-guild"><?php echo htmlspecialchars($m['guild_name'] ?? ''); ?></p>
          <dl>
            <div><dt>Reg. No.</dt><dd class="mcard-reg"><?php echo htmlspecialchars($m['reg_no']); ?></dd></div>
            <div><dt>Valid from</dt><dd><?php echo $fmt($m['valid_from']); ?></dd></div>
            <div><dt>Valid until</dt><dd><?php echo $fmt($m['valid_until']); ?></dd></div>
          </dl>
        </div>
      </div>
      <div class="mcard-foot">
        <p><?php echo htmlspecialchars($S['card_note']); ?></p>
        <div class="mcard-sign">
          <?php if ($sigOk): ?><img src="assets/images/<?php echo htmlspecialchars($sig); ?>" alt="Signature"><?php else: ?><span class="mcard-nosig"></span><?php endif; ?>
          <i></i>
          <b><?php echo htmlspecialchars($S['president_name']); ?></b>
          <small><?php echo htmlspecialchars($S['president_title']); ?></small>
        </div>
      </div>
      <?php if ($expired): ?><div class="mcard-stamp">EXPIRED</div><?php endif; ?>
    </div>

    <div class="card-verify no-print <?php echo $expired ? 'bad' : 'good'; ?>">
      <?php if ($expired): ?>
        <b>Membership expired</b> on <?php echo $fmt($m['valid_until']); ?>. Contact NUFA to renew.
      <?php else: ?>
        <b>Verified NUFA member</b> — registration <?php echo htmlspecialchars($m['reg_no']); ?>, valid until <?php echo $fmt($m['valid_until']); ?>.
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
