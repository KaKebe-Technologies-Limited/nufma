<?php
/**
 * NUFA SACCO membership card — reached through the private link emailed to the member on approval (sacco-card.php?t=<token>).
 * Doubles as a verification page.
 */
require_once __DIR__ . "/includes/sacco-members.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/ui.php";
sac_install($conn);

$token = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
$m = null;
if (strlen($token) === 32) {
    $st = $conn->prepare("SELECT * FROM sacco_applications WHERE token=? AND status='approved' LIMIT 1");
    $st->bind_param("s", $token); $st->execute(); $m = $st->get_result()->fetch_assoc();
}
$activeNav = "sacco"; $noindex = true; $canonicalPath = "sacco-card.php";
if (!$m) {
    http_response_code(404); $pageTitle = "Card not found — NUFA SACCO";
    include __DIR__ . "/includes/header.php";
    echo '<section class="section"><div class="container" style="text-align:center;max-width:560px"><h2>Membership card not found</h2><p class="lede" style="margin:14px auto 24px">This link is invalid, or the SACCO membership has not been approved yet.</p><a class="btn btn-gold" href="sacco.php">NUFA SACCO</a></div></section>';
    include __DIR__ . "/includes/footer.php"; exit;
}

$S = sac_settings($conn); $MS = mem_settings($conn);
$expired = !empty($m['card_valid_until']) && strtotime($m['card_valid_until'] . ' 23:59:59') < time();
$photoSrc = '';
if ($m['photo'] && is_file(sac_storage() . $m['photo'])) { $mime = (new finfo(FILEINFO_MIME_TYPE))->file(sac_storage() . $m['photo']); $photoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(sac_storage() . $m['photo'])); }
$sig = $MS['president_signature'] ?? ''; $sigOk = $sig !== '' && is_file(__DIR__ . '/assets/images/' . $sig);
$signer = $S['sacapp_signer_name'] !== '' ? $S['sacapp_signer_name'] : ($MS['president_name'] ?? '');
$shares = (int)($m['shares_confirmed'] ?: $m['shares_applied']);
$fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';
$since = $m['card_issued_at'] ?: $m['approved_at'];

$pageTitle = "NUFA SACCO Membership Card — " . $m['full_name'];
$pageDesc = "NUFA SACCO member verification";
include __DIR__ . "/includes/header.php";
?>
<section class="section section--soft">
  <div class="container" style="max-width:720px">
    <div class="card-actions no-print">
      <div><h2 style="font-size:1.4rem">Your NUFA SACCO membership card</h2><p style="color:var(--text-muted);font-size:.9rem;margin-top:4px">Print it or choose “Save as PDF” in the print dialog.</p></div>
      <button type="button" class="btn btn-gold btn-sm" onclick="window.print()">Print / Save PDF</button>
    </div>
    <div class="mcard mcard--sacco <?php echo $expired ? 'is-expired' : ''; ?>" id="mcard">
      <div class="mcard-top"><img src="assets/images/logo/nufa-logo.png" alt="" class="mcard-logo"><div><b>NUFA SACCO</b><span>Northern Uganda Filmmakers Association</span></div><em>MEMBER</em></div>
      <div class="mcard-body">
        <div class="mcard-photo"><?php if ($photoSrc): ?><img src="<?php echo $photoSrc; ?>" alt=""><?php else: ?><span><?php echo htmlspecialchars(mb_substr($m['full_name'], 0, 1)); ?></span><?php endif; ?></div>
        <div class="mcard-info"><h3><?php echo htmlspecialchars($m['full_name']); ?></h3>
          <p class="mcard-guild"><?php echo htmlspecialchars($m['creative_role'] ?: 'Savings & credit member'); ?></p>
          <dl><div><dt>Member No.</dt><dd class="mcard-reg"><?php echo htmlspecialchars($m['reg_no']); ?></dd></div>
            <div><dt>Shares held</dt><dd><?php echo $shares; ?></dd></div>
            <div><dt>Member since</dt><dd><?php echo $fmt($since); ?></dd></div>
            <div><dt>Valid until</dt><dd><?php echo $m['card_valid_until'] ? $fmt($m['card_valid_until']) : 'While in good standing'; ?></dd></div></dl></div>
      </div>
      <div class="mcard-foot"><p><?php echo htmlspecialchars($S['sacapp_card_note']); ?></p>
        <div class="mcard-sign"><?php if ($sigOk): ?><img src="assets/images/<?php echo htmlspecialchars($sig); ?>" alt="Signature"><?php else: ?><span class="mcard-nosig"></span><?php endif; ?><i></i><b><?php echo htmlspecialchars($signer); ?></b><small><?php echo htmlspecialchars($S['sacapp_signer_title']); ?></small></div></div>
      <?php if ($expired): ?><div class="mcard-stamp">EXPIRED</div><?php endif; ?>
    </div>
    <div class="card-verify no-print <?php echo $expired ? 'bad' : 'good'; ?>">
      <?php if ($expired): ?><b>Membership card expired</b> on <?php echo $fmt($m['card_valid_until']); ?>. Contact the SACCO to renew.
      <?php else: ?><b>Verified NUFA SACCO member</b> — member no. <?php echo htmlspecialchars($m['reg_no']); ?>, <?php echo $shares; ?> share<?php echo $shares === 1 ? '' : 's'; ?>.<?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
