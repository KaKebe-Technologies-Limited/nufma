<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$S = acad_settings($conn);
$next = acad_safe_next($_GET['next'] ?? $_POST['next'] ?? '');
if (acad_learner($conn)) { header("Location: " . $next); exit; }
$err = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(form_clean($_POST['email'] ?? '', 190)); $pw = (string)($_POST['password'] ?? '');
    $_SESSION['acad_fail'] = array_values(array_filter($_SESSION['acad_fail'] ?? [], fn($t) => $t > time() - 900));
    if (!mem_csrf_ok()) $err = "Your session expired. Please try again.";
    elseif (count($_SESSION['acad_fail']) >= 6) $err = "Too many attempts. Please wait 15 minutes, or reset your password.";
    else {
        $x = $conn->prepare("SELECT * FROM academy_learners WHERE email=? AND is_active=1"); $x->bind_param("s", $email); $x->execute(); $u = $x->get_result()->fetch_assoc();
        if ($u && password_verify($pw, $u['password_hash'])) {
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) { $nh = password_hash($pw, PASSWORD_DEFAULT); $conn->query("UPDATE academy_learners SET password_hash='" . $conn->real_escape_string($nh) . "' WHERE id=" . (int)$u['id']); }
            $_SESSION['acad_fail'] = []; acad_login($conn, $u['id']); header("Location: " . $next); exit;
        }
        $_SESSION['acad_fail'][] = time(); usleep(400000);
        $err = "That email and password do not match.";
    }
}
$pageTitle = "Sign in — NUFA Academy"; $pageDesc = "Sign in to your NUFA Academy learner portal."; $activeNav = "academy"; $canonicalPath = "academy-login.php"; $noindex = true;
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Academy', 'Learner sign in', 'Sign in with your email to continue learning, take exams and download certificates.', cms_img_raw('gallery', 'hero_image'), [['NUFA Academy', 'academy.php'], ['Sign in']]);
?>
<section class="section"><div class="container" style="max-width:520px">
  <?php if (!empty($_GET['reset'])): ?><div style="padding:12px 16px;border-radius:12px;background:#e8f5ea;color:#1f6b2b;margin-bottom:10px">Your password was changed. Please sign in.</div><?php endif; ?>
  <?php if (!empty($_GET['out'])): ?><div style="padding:12px 16px;border-radius:12px;background:#e8f5ea;color:#1f6b2b;margin-bottom:10px">You have signed out.</div><?php endif; ?>
  <?php if ($err): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($err); ?></div><?php endif; ?>
  <form method="post" class="ac-form">
    <?php echo mem_csrf_field(); ?><input type="hidden" name="next" value="<?php echo ui_e($next); ?>">
    <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="<?php echo ui_e($email); ?>" required autocomplete="email"></div>
    <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-gold" style="width:100%;justify-content:center">Sign in</button>
    <p class="muted" style="margin-top:14px;text-align:center"><a href="academy-forgot.php">Forgot your password?</a><?php if ($S['acad_registration_open'] === '1'): ?> · <a href="academy-register.php?next=<?php echo urlencode($next); ?>">Create an account</a><?php endif; ?></p>
  </form>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
