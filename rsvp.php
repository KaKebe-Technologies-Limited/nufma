<?php
require_once __DIR__ . "/includes/invitations.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
inv_install($conn);

$tok = preg_match('/^[a-f0-9]{32}$/', $_REQUEST['t'] ?? '') ? $_REQUEST['t'] : '';
$g = null;
if ($tok !== '') { $s = $conn->prepare("SELECT g.*, c.title AS ctitle, c.event_date_text, c.venue_text, c.rsvp_deadline, c.rsvp_on FROM invite_guests g JOIN invite_campaigns c ON c.id=g.campaign_id WHERE g.token=?"); $s->bind_param("s", $tok); $s->execute(); $g = $s->get_result()->fetch_assoc(); }
$done = false;
$pre = in_array($_GET['a'] ?? '', ['yes', 'no'], true) ? $_GET['a'] : '';
if ($g && $g['rsvp_on'] && $_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['answer'] ?? '', ['yes', 'no'], true)) {
    $a = $_POST['answer']; $u = $conn->prepare("UPDATE invite_guests SET rsvp=?, rsvp_at=NOW() WHERE id=?"); $u->bind_param("si", $a, $g['id']); $u->execute();
    $g['rsvp'] = $a; $done = true;
}

$pageTitle = "Your invitation | NUFA Awards";
$pageDesc = "Reply to your NUFA Awards invitation.";
$noindex = true;
$activeNav = "awards";
$canonicalPath = "rsvp.php";
include __DIR__ . "/includes/header.php";
?>
<section class="section" style="min-height:55vh">
  <div class="container narrow">
    <?php if (!$g || !$g['rsvp_on']): ?>
      <div class="placeholder-card"><span class="ph-icon"><?php echo nufa_icon('mail', 22); ?></span><h2>We could not find your invitation</h2><p>The link may be incomplete or no longer active. Please reply to the email you received, or <a href="contact.php?topic=Awards">contact the awards team</a>.</p></div>
    <?php else: $full = trim(($g['title_text'] !== '' ? $g['title_text'] . ' ' : '') . $g['name']); ?>
      <div class="form-card" style="text-align:center">
        <div class="eyebrow" style="justify-content:center">Your invitation</div>
        <h1 style="font-size:clamp(1.6rem,4vw,2.3rem)"><?php echo ui_e($g['ctitle']); ?></h1>
        <p class="lede">Dear <?php echo ui_e($full); ?>,</p>
        <?php if ($g['event_date_text'] || $g['venue_text']): ?><ul class="fact-list" style="max-width:460px;margin:14px auto"><?php if ($g['event_date_text']): ?><li><b>Date</b><span><?php echo ui_e($g['event_date_text']); ?></span></li><?php endif; ?><?php if ($g['venue_text']): ?><li><b>Venue</b><span><?php echo ui_e($g['venue_text']); ?></span></li><?php endif; ?></ul><?php endif; ?>
        <?php if ($done || $g['rsvp'] !== ''): ?>
          <div class="alert ok" role="status" style="margin:18px auto;max-width:460px"><b><?php echo $g['rsvp'] === 'yes' ? 'Thank you — we look forward to seeing you!' : 'Thank you for letting us know.'; ?></b><br><?php echo $g['rsvp'] === 'yes' ? 'Your attendance has been recorded.' : 'We are sorry you cannot join us and hope to see you at a future event.'; ?></div>
          <?php if (!$done): ?><p class="muted">You can change your reply below.</p><?php endif; ?>
        <?php else: ?><p>Will you attend?<?php echo $g['rsvp_deadline'] !== '' ? ' Please reply by ' . ui_e($g['rsvp_deadline']) . '.' : ''; ?></p><?php endif; ?>
        <form method="post" class="actions" style="justify-content:center"><input type="hidden" name="t" value="<?php echo ui_e($tok); ?>">
          <button class="btn btn-gold" name="answer" value="yes" type="submit"<?php echo $pre === 'yes' ? ' autofocus' : ''; ?>>Yes, I will attend</button>
          <button class="btn btn-outline-dark" name="answer" value="no" type="submit"<?php echo $pre === 'no' ? ' autofocus' : ''; ?>>Sorry, I cannot</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . "/includes/footer.php"; ?>
