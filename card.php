<?php
/**
 * Public membership card — reached through the private link e-mailed to the
 * member (card.php?t=<token>). Doubles as a verification page.
 */
require __DIR__ . "/includes/membership.php";
require_once __DIR__ . "/includes/member-card.php";

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
    echo '<section class="section"><div class="container" style="text-align:center;max-width:560px"><h2>Membership card not found</h2><p class="lede" style="margin:14px auto 24px">This link is invalid, or the membership has not been approved.</p><a class="btn btn-gold" href="membership.php">Apply for membership</a></div></section>';
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
<section class="section section--soft">
  <div class="container" style="max-width:860px">
    <div class="card-actions no-print">
      <div>
        <h2 style="font-size:1.4rem">Your NUFA membership card</h2>
        <p style="color:var(--text-muted);font-size:.9rem;margin-top:4px">Print it or choose “Save as PDF” in the print dialog.</p>
      </div>
      <button type="button" class="btn btn-gold btn-sm" onclick="window.print()">Print / Save PDF</button>
    </div>

    <link rel="stylesheet" href="assets/css/member-card.css">
    <div class="idc-single"><?php echo mem_card_html($conn, $m, ["prefix" => ""]); ?></div>
    <p class="no-print" style="text-align:center;color:var(--text-muted);font-size:.85rem;margin:14px 0 0">Front and back of your card. The QR code on the back opens this verification page.</p>

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
