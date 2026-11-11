<?php
/**
 * Search-engine helpers: one canonical address for the whole site (www.nufawards.com by default, from Site settings → Website),
 * so Google never sees the same page under two addresses.
 */
/** Base URL of the site without a trailing slash. Uses the configured website when the visitor is on that domain (with or without www). */
function nufa_site_base($cfg) {
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $cw = parse_url(trim((string)($cfg['site_website'] ?? '')), PHP_URL_HOST) ?: '';
    $strip = fn($h) => preg_replace('/^www\./i', '', strtolower($h));
    if ($cw !== '' && $strip($cw) === $strip($host) && !preg_match('/^(localhost|127\.|\[::1\])/i', $host)) {
        $scheme = (parse_url((string)$cfg['site_website'], PHP_URL_SCHEME) ?: 'https');
        return $scheme . '://' . $cw . $dir;
    }
    return ($https ? 'https://' : 'http://') . $host . $dir;
}
