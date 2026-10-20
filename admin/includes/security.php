<?php
/**
 * Admin security helpers: hardened session, CSRF token, login throttling,
 * and an output filter that adds CSRF protection to every admin form and
 * destructive link without touching each page individually.
 */

function admin_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_name('nufa_admin');
    session_start();
}

/** Same session key the membership module uses, so both agree on one token. */
function admin_token() {
    if (empty($_SESSION['mem_csrf'])) $_SESSION['mem_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['mem_csrf'];
}
function admin_token_ok($given) {
    return is_string($given) && isset($_SESSION['mem_csrf']) && hash_equals($_SESSION['mem_csrf'], $given);
}

function admin_client_ip() { return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45); }

function admin_security_install($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS admin_login_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL DEFAULT '',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        success TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ip (ip, created_at),
        KEY idx_user (username, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

const ADMIN_MAX_FAILS = 5;
const ADMIN_LOCK_MINUTES = 15;

/** Seconds remaining on a lockout for this IP/username, or 0. */
function admin_lockout_remaining($conn, $username) {
    $ip = admin_client_ip();
    $s = $conn->prepare("SELECT COUNT(*) c, TIMESTAMPDIFF(SECOND, NOW(), MAX(created_at) + INTERVAL " . ADMIN_LOCK_MINUTES . " MINUTE) AS wait FROM admin_login_log
        WHERE success=0 AND created_at > (NOW() - INTERVAL " . ADMIN_LOCK_MINUTES . " MINUTE) AND (ip=? OR username=?)");
    $s->bind_param("ss", $ip, $username);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    if ((int)$r['c'] < ADMIN_MAX_FAILS) return 0;
    return max(0, (int)$r['wait']);
}

function admin_log_attempt($conn, $username, $ok) {
    $ip = admin_client_ip();
    $s = $conn->prepare("INSERT INTO admin_login_log (username, ip, success) VALUES (?,?,?)");
    $o = $ok ? 1 : 0;
    $s->bind_param("ssi", $username, $ip, $o);
    $s->execute();
    if ($ok) { // a good login clears earlier failures for this IP/user
        $d = $conn->prepare("DELETE FROM admin_login_log WHERE success=0 AND (ip=? OR username=?)");
        $d->bind_param("ss", $ip, $username);
        $d->execute();
    }
    if (mt_rand(1, 50) === 1) $conn->query("DELETE FROM admin_login_log WHERE created_at < (NOW() - INTERVAL 90 DAY)");
}

/** Output filter: inject the CSRF field into POST forms and a token into destructive GET links. */
function admin_output_filter($html) {
    foreach (headers_list() as $hd) {
        if (stripos($hd, 'content-type:') === 0 && stripos($hd, 'text/html') === false) return $html;
    }
    if (stripos($html, '<form') === false && stripos($html, 'href=') === false) return $html;
    $tok = admin_token();
    $html = preg_replace_callback('#<form\b([^>]*)>(.*?)</form>#is', function ($m) use ($tok) {
        if (!preg_match('#method\s*=\s*["\']?post#i', $m[1])) return $m[0];
        if (stripos($m[2], 'name="csrf"') !== false) return $m[0];
        return '<form' . $m[1] . '><input type="hidden" name="csrf" value="' . $tok . '">' . $m[2] . '</form>';
    }, $html);
    $html = preg_replace_callback('#href="([^"]*[?&](?:delete|toggle|read|remove|activate)=[^"]*)"#i', function ($m) use ($tok) {
        if (preg_match('#[?&]t=#', $m[1])) return $m[0];
        return 'href="' . $m[1] . '&amp;t=' . $tok . '"';
    }, $html);
    return $html;
}
