<?php
/**
 * Included at the top of every admin page: opens the session, requires a
 * signed-in admin, enforces an idle timeout and CSRF protection.
 */
require_once __DIR__ . "/security.php";
admin_session_start();
require_once __DIR__ . "/../../includes/db.php";
admin_security_install($conn);

const ADMIN_IDLE_SECONDS = 7200;   // sign out after 2 hours of inactivity

if (!isset($_SESSION['admin_id'])) {
    $next = basename($_SERVER['SCRIPT_NAME']);
    header("Location: login.php" . ($next && $next !== 'index.php' ? "?next=" . urlencode($next) : ""));
    exit;
}
if (isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > ADMIN_IDLE_SECONDS) {
    $next = basename($_SERVER['SCRIPT_NAME']);
    session_unset(); session_destroy();
    header("Location: login.php?timeout=1&next=" . urlencode($next));
    exit;
}
$_SESSION['last_active'] = time();

// Re-check the account still exists (e.g. it was deleted while signed in).
$__me = $conn->prepare("SELECT id, display_name FROM admins WHERE id=? LIMIT 1");
$__me->bind_param("i", $_SESSION['admin_id']);
$__me->execute();
$__row = $__me->get_result()->fetch_assoc();
if (!$__row) { session_unset(); session_destroy(); header("Location: login.php"); exit; }
$_SESSION['admin_name'] = $__row['display_name'] ?: 'Admin';

// Roles & permissions: each admin page belongs to a module; viewing needs 'view', changing anything needs 'manage'.
require_once __DIR__ . "/../../includes/roles.php";
roles_install($conn);
admin_load_role($conn, $_SESSION['admin_id']);
$__file = basename($_SERVER['SCRIPT_NAME']);
$__mod = admin_page_modules()[$__file] ?? null;
$__changing = $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete']) || isset($_GET['toggle']) || isset($_GET['read']) || isset($_GET['remove']) || isset($_GET['activate']);
if ($__mod !== null && !admin_can($__mod, $__changing ? ROLE_MANAGE : ROLE_VIEW)) {
    http_response_code(403);
    $__ml = admin_modules()[$__mod][0];
    exit('<!doctype html><meta charset="utf-8"><title>Not allowed</title><body style="font-family:system-ui;max-width:540px;margin:80px auto;padding:0 20px"><h2>You don\'t have access to this</h2><p>Your role (<b>' . htmlspecialchars(admin_role_label()) . '</b>) ' . ($__changing && admin_can($__mod, ROLE_VIEW) ? 'can view but not change' : 'cannot open') . ' <b>' . htmlspecialchars($__ml) . '</b>. Ask an administrator if you need this permission.</p><p><a href="index.php">&larr; Back to the dashboard</a></p></body>');
}

// CSRF: every POST must carry the token; destructive GET links must carry ?t=
$__destructive = isset($_GET['delete']) || isset($_GET['toggle']) || isset($_GET['read']) || isset($_GET['remove']) || isset($_GET['activate']);
if (($_SERVER['REQUEST_METHOD'] === 'POST' && !admin_token_ok($_POST['csrf'] ?? null))
    || ($__destructive && !admin_token_ok($_GET['t'] ?? null))) {
    http_response_code(403);
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
    exit('<!doctype html><meta charset="utf-8"><title>Request blocked</title><body style="font-family:system-ui;max-width:520px;margin:80px auto;padding:0 20px">'
        . '<h2>Request could not be verified</h2><p>' . ($isPost && empty($_POST) ? 'The upload may be larger than the server allows, or the page expired.' : 'Your session token did not match — the page may have expired.')
        . '</p><p><a href="javascript:history.back()">&larr; Go back</a> and try again, or <a href="index.php">return to the dashboard</a>.</p></body>');
}

ob_start('admin_output_filter');
