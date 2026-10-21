<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/academy.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
acad_install($conn); acad_session();
$S = acad_settings($conn);
if (acad_learner($conn)) { header("Location: academy-portal.php"); exit; }
$next = acad_safe_next($_GET['next'] ?? $_POST['next'] ?? '');
$errors = []; $v = ['name' => '', 'email' => '', 'phone' => '', 'country' => 'Uganda'];
if ($S['acad_registration_open'] !== '1') $errors[] = "New learner registration is currently closed.";
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($g = form_guard($conn, 'academy_register', 'academy_learners')) $errors[] = $g;
    foreach ($v as $k => $_) $v[$k] = form_clean($_POST[$k] ?? '', $k === 'email' ? 190 : 150);
    $v['email'] = strtolower($v['email']); $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');
    if (mb_strlen($v['name']) < 2) $errors[] = "Enter your full name.";
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen($pw) < 8) $errors[] = "Your password must be at least 8 characters.";
    if ($pw !== $pw2) $errors[] = "The two passwords do not match.";
    if (empty($_POST['terms'])) $errors[] = "Please agree to the learner terms to continue.";
    if (!$errors) {
        $x = $conn->prepare("SELECT id FROM academy_learners WHERE email=?"); $x->bind_param("s", $v['email']); $x->execute();
        if ($x->get_result()->num_rows) $errors[] = "An account with this email already exists. Please sign in, or reset your password.";
    }
    if (!$errors) {
        $hash = password_hash($pw, PASSWORD_DEFAULT); $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
        $s = $conn->prepare("INSERT INTO academy_learners (name,email,password_hash,phone,country,ip) VALUES (?,?,?,?,?,?)"); $s->bind_param("ssssss", $v['name'], $v['email'], $hash, $v['phone'], $v['country'], $ip); $s->execute();
        $id = $conn->insert_id; acad_login($conn, $id);
        mem_send($conn, null, 'academy-welcome', $v['email'], "Welcome to NUFA Academy", "Hello " . $v['name'] . ",\n\nYour NUFA Academy learner account is ready. Choose a course, work through the lessons and take the exam to earn your certificate.", mem_site_url() . '/academy.php');
        header("Location: " . $next); exit;
    }
}
$pageTitle = "Create a learner account — NUFA Academy"; $pageDesc = "Create your NUFA Academy learner account."; $activeNav = "academy"; $canonicalPath = "academy-register.php"; $noindex = true;
include __DIR__ . "/includes/header.php";
echo ui_page_hero('NUFA Academy', 'Create your learner account', 'It is free and takes a minute. Use your email address to sign in to the learner portal.', cms_img_raw('gallery', 'hero_image'), [['NUFA Academy', 'academy.php'], ['Create account']]);
?>
<section class="section"><div class="container" style="max-width:560px">
  <?php foreach ($errors as $e): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($e); ?></div><?php endforeach; ?>
  <?php if ($S['acad_registration_open'] === '1'): ?>
  <form method="post" class="ac-form" novalidate>
    <?php echo form_open_fields(); ?><input type="hidden" name="next" value="<?php echo ui_e($next); ?>">
    <div class="field"><label for="name">Full name</label><input id="name" name="name" value="<?php echo ui_e($v['name']); ?>" required autocomplete="name"></div>
    <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="<?php echo ui_e($v['email']); ?>" required autocomplete="email"></div>
    <div class="field"><label for="phone">Phone (optional)</label><input id="phone" name="phone" value="<?php echo ui_e($v['phone']); ?>" autocomplete="tel"></div>
    <div class="field"><label for="country">Country</label><input id="country" name="country" value="<?php echo ui_e($v['country']); ?>" autocomplete="country-name"></div>
    <div class="field"><label for="password">Password (at least 8 characters)</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label for="password2">Confirm password</label><input id="password2" type="password" name="password2" required autocomplete="new-password"></div>
    <label class="op-check" style="margin:8px 0 18px"><input type="checkbox" name="terms" value="1"> I agree that NUFA may store my details and exam results to run my account and issue certificates, as described in the <a href="policy.php?p=privacy" target="_blank">Privacy Policy</a>.</label>
    <button class="btn btn-gold" style="width:100%;justify-content:center">Create my account</button>
    <p class="muted" style="margin-top:14px;text-align:center">Already registered? <a href="academy-login.php?next=<?php echo urlencode($next); ?>">Sign in</a></p>
  </form>
  <?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
