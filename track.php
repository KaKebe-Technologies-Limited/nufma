<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
require_once __DIR__ . "/includes/track.php";
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

$ref = trim($_REQUEST['ref'] ?? ''); $email = trim($_REQUEST['email'] ?? ''); $phone = trim($_REQUEST['phone'] ?? '');
$searched = $_SERVER['REQUEST_METHOD'] === 'POST'; $results = []; $error = '';
if ($searched) {
    // light rate limit: 15 look-ups per 10 minutes per visitor
    $_SESSION['trk'] = array_values(array_filter($_SESSION['trk'] ?? [], fn($t) => $t > time() - 600));
    if (count($_SESSION['trk']) >= 15) $error = 'Too many look-ups. Please wait a few minutes and try again.';
    elseif ($ref === '' && ($email === '' || $phone === '')) $error = 'Enter your reference number, or both the email address and phone number you applied with.';
    else { $_SESSION['trk'][] = time(); $results = track_find($conn, $ref, $email, $phone); }
}
$pageTitle = "Track my registration — NUFA"; $pageDesc = "Check the status of your NUFA membership, SACCO, partnership, film or other application."; $activeNav = "membership"; $canonicalPath = "track.php"; $noindex = true;
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Applications', 'Track my registration', 'See where your application stands — membership, SACCO, partnership, ambassador, film submissions and more.', 'gallery/2026/nufa26-31.jpg', [['Home', 'index.php'], ['Track my registration']]);
$steps = ['wait' => 1, 'info' => 1, 'ok' => 2, 'bad' => 2];
?>
<style>
.trk-form{background:#fff;border:1px solid var(--nufa-border,#E6E6E6);border-radius:18px;padding:clamp(20px,3vw,32px);box-shadow:0 8px 28px rgba(0,0,0,.06)}
.trk-or{text-align:center;color:#777;font-weight:700;margin:14px 0;position:relative}.trk-or::before,.trk-or::after{content:"";position:absolute;top:50%;width:42%;height:1px;background:#E6E6E6}.trk-or::before{left:0}.trk-or::after{right:0}
.trk-item{background:#fff;border:1px solid #E6E6E6;border-left:6px solid #999;border-radius:16px;padding:20px 22px;margin-top:18px}
.trk-item.ok{border-left-color:#0E7A3F}.trk-item.bad{border-left-color:#B71C1C}.trk-item.wait{border-left-color:#FF7900}.trk-item.info{border-left-color:#1565C0}
.trk-top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start}
.trk-type{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:var(--nufa-primary,#D50000)}
.trk-item h3{margin:4px 0 2px;font-size:1.12rem}.trk-meta{color:#666;font-size:.86rem}
.trk-chip{display:inline-block;padding:6px 14px;border-radius:99px;font-weight:800;font-size:.8rem;color:#fff;background:#777}.trk-item.ok .trk-chip{background:#0E7A3F}.trk-item.bad .trk-chip{background:#B71C1C}.trk-item.wait .trk-chip{background:#C75A00}.trk-item.info .trk-chip{background:#1565C0}
.trk-steps{display:flex;gap:0;margin:16px 0 10px;list-style:none;padding:0}
.trk-steps li{flex:1;text-align:center;font-size:.76rem;font-weight:700;color:#999;position:relative;padding-top:22px}
.trk-steps li::before{content:"";position:absolute;top:0;left:50%;width:16px;height:16px;margin-left:-8px;border-radius:50%;background:#DDD;z-index:1}
.trk-steps li::after{content:"";position:absolute;top:7px;left:-50%;width:100%;height:2px;background:#DDD}.trk-steps li:first-child::after{display:none}
.trk-steps li.done{color:#111}.trk-steps li.done::before,.trk-steps li.done::after{background:var(--nufa-primary,#D50000)}
.trk-item.ok .trk-steps li.done::before,.trk-item.ok .trk-steps li.done::after{background:#0E7A3F}.trk-item.bad .trk-steps li:last-child.done::before{background:#B71C1C}
.trk-info{display:flex;flex-wrap:wrap;gap:8px 22px;margin:10px 0 0;font-size:.88rem}.trk-info b{color:#111}
</style>
<section class="section"><div class="container" style="max-width:780px">
  <form method="post" class="trk-form" autocomplete="off">
    <?php if ($error): ?><div class="form-alert" role="alert" style="background:#FDECEC;color:#8E0000;border-radius:10px;padding:12px 14px;margin-bottom:14px"><?php echo ui_e($error); ?></div><?php endif; ?>
    <div class="field"><label for="t-ref">Reference number</label><input id="t-ref" name="ref" placeholder="e.g. MEM-1A2B3C4D or the number in your confirmation email" value="<?php echo ui_e($ref); ?>"></div>
    <div class="trk-or">or</div>
    <div class="form-grid">
      <div class="field"><label for="t-email">Email you applied with</label><input id="t-email" name="email" type="email" value="<?php echo ui_e($email); ?>"></div>
      <div class="field"><label for="t-phone">Phone number you applied with</label><input id="t-phone" name="phone" type="tel" value="<?php echo ui_e($phone); ?>"></div>
    </div>
    <p class="muted" style="font-size:.85rem;margin:6px 0 16px">We ask for both your email and phone number so only you can see your applications. Nothing is changed by looking up a status.</p>
    <button class="btn btn-gold" type="submit">Check status</button>
  </form>

  <?php if ($searched && !$error): ?>
    <?php if (!$results): ?>
      <div class="empty-state" style="margin-top:22px"><h3>We could not find an application</h3><p>Check the spelling of your email and phone number (use the same ones you applied with), or enter your reference number. Still stuck? <a href="contact.php" style="text-decoration:underline">Contact us</a>.</p></div>
    <?php else: ?>
      <h2 style="font-size:1.25rem;margin:28px 0 0"><?php echo count($results); ?> application<?php echo count($results) === 1 ? '' : 's'; ?> found</h2>
      <?php foreach ($results as [$kind, $title, $who, $rf, $when, $status, $reason, $info, $acts]):
          [$label, $text, $tone] = track_status($status, $reason); $n = $steps[$tone] ?? 1; ?>
      <article class="trk-item <?php echo $tone; ?>">
        <div class="trk-top"><div><div class="trk-type"><?php echo ui_e($kind); ?></div><h3><?php echo ui_e($title); ?></h3>
          <div class="trk-meta"><?php echo ui_e($who); ?> · Reference <b><?php echo ui_e($rf); ?></b> · Submitted <?php echo ui_e(date('j M Y', strtotime($when))); ?></div></div>
          <span class="trk-chip"><?php echo ui_e($label); ?></span></div>
        <ol class="trk-steps"><li class="done">Received</li><li class="<?php echo $n >= 1 ? 'done' : ''; ?>">Under review</li><li class="<?php echo $n >= 2 ? 'done' : ''; ?>"><?php echo $tone === 'bad' ? 'Decision: not approved' : 'Decision made'; ?></li></ol>
        <p style="margin:0;white-space:pre-line"><?php echo ui_e($text); ?></p>
        <?php if ($info): ?><div class="trk-info"><?php foreach ($info as $k => $v): ?><span><?php echo ui_e($k); ?>: <b><?php echo ui_e($v); ?></b></span><?php endforeach; ?></div><?php endif; ?>
        <?php if ($acts): ?><p style="margin:14px 0 0;display:flex;gap:10px;flex-wrap:wrap"><?php foreach ($acts as [$al, $au]): ?><a class="btn btn-gold btn-sm" href="<?php echo ui_e($au); ?>"><?php echo ui_e($al); ?></a><?php endforeach; ?></p><?php endif; ?>
      </article>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
