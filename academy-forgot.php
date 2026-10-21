<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$err = ''; $sent = false; $token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? $_POST['token'] ?? '');
$u = null;
if ($token !== '') {
    $h = hash('sha256', $token); $x = $conn->prepare("SELECT * FROM academy_learners WHERE reset_hash=? AND reset_expires > NOW() AND is_active=1"); $x->bind_param("s", $h); $x->execute(); $u = $x->get_result()->fetch_assoc();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) $err = "Your session expired. Please try again.";
    elseif ($token !== '') {
        $pw = (string)($_POST['password'] ?? '');
        if (!$u) $err = "This reset link is invalid or has expired. Please request a new one.";
        elseif (strlen($pw) < 8) $err = "Your password must be at least 8 characters.";
        elseif ($pw !== (string)($_POST['password2'] ?? '')) $err = "The two passwords do not match.";
        else { $nh = password_hash($pw, PASSWORD_DEFAULT); $s = $conn->prepare("UPDATE academy_learners SET password_hash=?, reset_hash='', reset_expires=NULL WHERE id=?"); $s->bind_param("si", $nh, $u['id']); $s->execute(); header("Location: academy-login.php?reset=1"); exit; }
    } else {
        $email = strtolower(form_clean($_POST['email'] ?? '', 190));
        $_SESSION['acad_reset'] = array_values(array_filter($_SESSION['acad_reset'] ?? [], fn($t) => $t > time() - 3600));
        if (count($_SESSION['acad_reset']) < 5) {
            $_SESSION['acad_reset'][] = time();
            $x = $conn->prepare("SELECT * FROM academy_learners WHERE email=? AND is_active=1"); $x->bind_param("s", $email); $x->execute(); $r = $x->get_result()->fetch_assoc();
            if ($r) {
                $tok = bin2hex(random_bytes(24)); $hh = hash('sha256', $tok); $exp = date('Y-m-d H:i:s', time() + 3600);
                $s = $conn->prepare("UPDATE academy_learners SET reset_hash=?, reset_expires=? WHERE id=?"); $s->bind_param("ssi", $hh, $exp, $r['id']); $s->execute();
                mem_send($conn, null, 'academy-reset', $r['email'], "Reset your NUFA Academy password", "Hello " . $r['name'] . ",\n\nUse the button below to choose a new password. The link works for one hour. If you did not ask for this, you can ignore this email.", mem_site_url() . '/academy-forgot.php?token=' . $tok);
            }
        }
        $sent = true;   // same answer whether or not the email exists
    }
}
$pageTitle = "Reset your password — NUFA Academy"; $pageDesc = "Reset your NUFA Academy password."; $activeNav = "academy"; $canonicalPath = "academy-forgot.php"; $noindex = true;
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Academy', 'Reset your password', '', cms_img_raw('gallery', 'hero_image'), [['NUFA Academy', 'academy.php'], ['Reset password']]);
?>
<section class="section"><div class="container" style="max-width:520px">
  <?php if ($err): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($err); ?></div><?php endif; ?>
  <?php if ($sent): ?><div style="padding:14px 18px;border-radius:12px;background:#e8f5ea;color:#1f6b2b">If an account exists for that email, we have sent a link to reset the password. It works for one hour.</div>
  <?php elseif ($token !== '' && $u): ?>
  <form method="post" class="ac-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="token" value="<?php echo ui_e($token); ?>">
    <div class="field"><label>New password (at least 8 characters)</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label>Confirm new password</label><input type="password" name="password2" required autocomplete="new-password"></div>
    <button class="btn btn-gold" style="width:100%;justify-content:center">Save new password</button></form>
  <?php else: ?>
  <?php if ($token !== ''): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px">This reset link is invalid or has expired. Request a new one below.</div><?php endif; ?>
  <form method="post" class="ac-form"><?php echo mem_csrf_field(); ?>
    <div class="field"><label>Your email address</label><input type="email" name="email" required autocomplete="email"></div>
    <button class="btn btn-gold" style="width:100%;justify-content:center">Email me a reset link</button>
    <p class="muted" style="margin-top:14px;text-align:center"><a href="academy-login.php">Back to sign in</a></p></form>
  <?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
