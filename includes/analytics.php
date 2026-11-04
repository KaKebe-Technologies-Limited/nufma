<?php
/**
 * Visitor statistics for the admin dashboard: visitors, countries, mobile / tablet / desktop and the most visited pages.
 * Privacy: no cookies and no IP addresses are stored. A visitor is an anonymous code that changes every day
 * (a one-way hash, so people cannot be followed from one day to the next). Country comes from the hosting / CDN header
 * when there is one, otherwise from the browser language; an optional IP look-up can be switched on in admin → Visitors.
 */
function ana_defaults() { return ['ana_geo_lookup' => '0', 'ana_salt' => '']; }

function ana_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS visit_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY, day DATE NOT NULL, hr TINYINT NOT NULL DEFAULT 0, page VARCHAR(40) NOT NULL DEFAULT '', item VARCHAR(100) NOT NULL DEFAULT '',
        device CHAR(1) NOT NULL DEFAULT 'd', country CHAR(2) NOT NULL DEFAULT 'ZZ', csrc CHAR(1) NOT NULL DEFAULT 'u', vhash CHAR(16) NOT NULL DEFAULT '', ref VARCHAR(80) NOT NULL DEFAULT '',
        KEY idx_day (day), KEY idx_day_page (day, page), KEY idx_day_vh (day, vhash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS geo_cache (ip_hash CHAR(16) PRIMARY KEY, country CHAR(2) NOT NULL, checked DATE NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ana_setting($conn, $k) {
    $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key='" . $conn->real_escape_string($k) . "'");
    $v = ($r && ($x = $r->fetch_row())) ? (string)$x[0] : null;
    return $v === null ? (ana_defaults()[$k] ?? '') : $v;
}
function ana_set($conn, $k, $v) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); }

/** 'm' mobile, 't' tablet, 'd' desktop */
function ana_device($ua) {
    if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle|(Android(?!.*Mobile))/i', $ua)) return 't';
    if (preg_match('/Mobi|iPhone|iPod|Android|Opera Mini|IEMobile|BlackBerry|Windows Phone/i', $ua)) return 'm';
    return 'd';
}

function ana_client_ip() { return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? ''; }

/** [country code, source] where source is h(eader), i(p look-up), l(anguage) or u(nknown). */
function ana_country($conn, $ip) {
    foreach (['HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_X_GEO_COUNTRY'] as $h) {
        $c = strtoupper(trim($_SERVER[$h] ?? '')); if (preg_match('/^[A-Z]{2}$/', $c) && !in_array($c, ['XX', 'T1', 'ZZ'], true)) return [$c, 'h'];
    }
    if (ana_setting($conn, 'ana_geo_lookup') === '1' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $key = substr(hash('sha256', $ip . ana_setting($conn, 'ana_salt')), 0, 16);
        $r = $conn->query("SELECT country FROM geo_cache WHERE ip_hash='$key' AND checked > (CURDATE() - INTERVAL 30 DAY)");
        if ($r && ($x = $r->fetch_row())) { if ($x[0] !== 'ZZ') return [$x[0], 'i']; }
        else {
            $ctx = stream_context_create(['http' => ['timeout' => 1.5, 'ignore_errors' => true]]);
            $j = @json_decode((string)@file_get_contents('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country_code', false, $ctx), true);
            $cc = (!empty($j['success']) && preg_match('/^[A-Z]{2}$/', $j['country_code'] ?? '')) ? $j['country_code'] : 'ZZ';
            $conn->query("REPLACE INTO geo_cache (ip_hash, country, checked) VALUES ('$key', '$cc', CURDATE())");
            if ($cc !== 'ZZ') return [$cc, 'i'];
        }
    }
    if (preg_match('/[a-z]{2,3}[-_]([A-Za-z]{2})\b/', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', $m)) return [strtoupper($m[1]), 'l'];
    return ['ZZ', 'u'];
}

function ana_record($conn, $page, $item = '') {
    ana_install($conn);
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? ''); $ip = ana_client_ip();
    $salt = ana_setting($conn, 'ana_salt'); if ($salt === '') { $salt = bin2hex(random_bytes(12)); ana_set($conn, 'ana_salt', $salt); }
    $vh = substr(hash('sha256', $ip . '|' . $ua . '|' . date('Y-m-d') . '|' . $salt), 0, 16);
    [$cc, $src] = ana_country($conn, $ip);
    $ref = ''; $rh = strtolower((string)parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST));
    if ($rh !== '' && $rh !== strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''))) $ref = mb_substr(preg_replace('/^www\./', '', $rh), 0, 80);
    $s = $conn->prepare("INSERT INTO visit_log (day, hr, page, item, device, country, csrc, vhash, ref) VALUES (CURDATE(), HOUR(NOW()), ?, ?, ?, ?, ?, ?, ?)");
    $dev = ana_device($ua); $item = mb_substr($item, 0, 100);
    $s->bind_param("sssssss", $page, $item, $dev, $cc, $src, $vh, $ref); $s->execute();
    if (random_int(1, 400) === 1) $conn->query("DELETE FROM visit_log WHERE day < (CURDATE() - INTERVAL 400 DAY)");
}

/* ---------- reading ---------- */
function ana_countries() {
    return ['UG' => 'Uganda', 'KE' => 'Kenya', 'TZ' => 'Tanzania', 'RW' => 'Rwanda', 'SS' => 'South Sudan', 'CD' => 'DR Congo', 'BI' => 'Burundi', 'ET' => 'Ethiopia', 'SO' => 'Somalia', 'SD' => 'Sudan', 'ER' => 'Eritrea', 'DJ' => 'Djibouti',
        'NG' => 'Nigeria', 'GH' => 'Ghana', 'ZA' => 'South Africa', 'EG' => 'Egypt', 'MA' => 'Morocco', 'DZ' => 'Algeria', 'TN' => 'Tunisia', 'SN' => 'Senegal', 'CI' => 'Côte d’Ivoire', 'CM' => 'Cameroon', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
        'MW' => 'Malawi', 'MZ' => 'Mozambique', 'AO' => 'Angola', 'NA' => 'Namibia', 'BW' => 'Botswana', 'LR' => 'Liberia', 'SL' => 'Sierra Leone', 'ML' => 'Mali', 'BF' => 'Burkina Faso', 'NE' => 'Niger', 'GA' => 'Gabon', 'CG' => 'Congo', 'MG' => 'Madagascar', 'MU' => 'Mauritius',
        'GB' => 'United Kingdom', 'US' => 'United States', 'CA' => 'Canada', 'DE' => 'Germany', 'FR' => 'France', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland', 'IE' => 'Ireland', 'IT' => 'Italy', 'ES' => 'Spain', 'PT' => 'Portugal',
        'CH' => 'Switzerland', 'AT' => 'Austria', 'PL' => 'Poland', 'RU' => 'Russia', 'UA' => 'Ukraine', 'TR' => 'Türkiye', 'AE' => 'United Arab Emirates', 'SA' => 'Saudi Arabia', 'QA' => 'Qatar', 'KW' => 'Kuwait', 'OM' => 'Oman', 'IN' => 'India', 'PK' => 'Pakistan', 'BD' => 'Bangladesh',
        'CN' => 'China', 'JP' => 'Japan', 'KR' => 'South Korea', 'SG' => 'Singapore', 'MY' => 'Malaysia', 'ID' => 'Indonesia', 'PH' => 'Philippines', 'TH' => 'Thailand', 'VN' => 'Vietnam', 'AU' => 'Australia', 'NZ' => 'New Zealand', 'BR' => 'Brazil', 'MX' => 'Mexico', 'AR' => 'Argentina',
        'CO' => 'Colombia', 'CL' => 'Chile', 'JM' => 'Jamaica', 'IL' => 'Israel', 'ZZ' => 'Unknown'];
}
function ana_country_name($c) { return ana_countries()[$c] ?? $c; }
function ana_flag($c) { if (!preg_match('/^[A-Z]{2}$/', $c) || $c === 'ZZ') return '🌐'; return mb_chr(0x1F1E6 + ord($c[0]) - 65) . mb_chr(0x1F1E6 + ord($c[1]) - 65); }
function ana_page_label($p) {
    $m = ['index' => 'Home', 'about' => 'About NUFA', 'awards' => 'NUFA Awards', 'award' => 'Awards edition', 'programmes' => 'Programmes', 'programme' => 'Programme', 'sacco' => 'NUFA SACCO', 'sacco-apply' => 'SACCO application', 'network' => 'Industry Network', 'news' => 'News & Stories',
        'post' => 'News story', 'newsletters' => 'Newsletters', 'newsletter' => 'Newsletter issue', 'resources' => 'Resource Library', 'gallery' => 'Gallery & Video', 'partners' => 'Partner With Us', 'partner-apply' => 'Partnership application', 'contact' => 'Contact', 'membership' => 'Membership',
        'schedule' => 'Calendar', 'support' => 'Support a programme', 'feedback' => 'Feedback', 'opportunities' => 'Opportunities', 'academy' => 'NUFA Academy', 'academy-course' => 'Academy course', 'academy-library' => 'Academy study library', 'academy-teach' => 'Teach with the Academy',
        'academy-register' => 'Academy sign-up', 'academy-login' => 'Academy sign-in', 'academy-learn' => 'Academy lesson', 'films' => 'Film Showcase', 'film' => 'Film page', 'film-submit' => 'Submit a film', 'guests' => 'Distinguished Guests', 'guest' => 'Guest profile', 'ambassadors' => 'Ambassadors',
        'awards-perform' => 'Perform at the Awards', 'awards-workshop' => 'Host a workshop', 'awards-cohost' => 'Co-host or sponsor', 'policy' => 'Policies', 'card' => 'Membership card', 'rsvp' => 'Invitation RSVP', '404' => 'Page not found'];
    return $m[$p] ?? ucfirst(str_replace(['-', '_'], ' ', $p));
}
function ana_has_data($conn) { $r = @$conn->query("SELECT 1 FROM visit_log LIMIT 1"); return $r && $r->num_rows > 0; }
function ana_since($days) { return date('Y-m-d', strtotime('-' . ((int)$days - 1) . ' day')); }

/** Everything the dashboards need for the last $days days. */
function ana_report($conn, $days) {
    ana_install($conn); $d = max(1, (int)$days); $since = ana_since($d); $W = "WHERE day >= '$since'";
    $q = function ($sql) use ($conn) { $r = @$conn->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; };
    $o = ['days' => $d, 'since' => $since];
    $t = $q("SELECT COUNT(*) views, COUNT(DISTINCT day, vhash) visitors FROM visit_log $W")[0] ?? ['views' => 0, 'visitors' => 0]; $o['views'] = (int)$t['views']; $o['visitors'] = (int)$t['visitors'];
    $prevFrom = date('Y-m-d', strtotime('-' . (2 * $d - 1) . ' day')); $p = $q("SELECT COUNT(DISTINCT day, vhash) v FROM visit_log WHERE day >= '$prevFrom' AND day < '$since'")[0] ?? ['v' => 0]; $o['prev_visitors'] = (int)$p['v'];
    $o['today'] = (int)($q("SELECT COUNT(DISTINCT vhash) v FROM visit_log WHERE day = CURDATE()")[0]['v'] ?? 0);
    $o['daily'] = []; for ($i = $d - 1; $i >= 0; $i--) $o['daily'][date('Y-m-d', strtotime("-$i day"))] = ['v' => 0, 'p' => 0];
    foreach ($q("SELECT day, COUNT(*) p, COUNT(DISTINCT vhash) v FROM visit_log $W GROUP BY day") as $x) if (isset($o['daily'][$x['day']])) $o['daily'][$x['day']] = ['v' => (int)$x['v'], 'p' => (int)$x['p']];
    $o['devices'] = ['m' => 0, 't' => 0, 'd' => 0]; foreach ($q("SELECT device, COUNT(DISTINCT day, vhash) v FROM visit_log $W GROUP BY device") as $x) $o['devices'][$x['device']] = (int)$x['v'];
    $o['countries'] = $q("SELECT country, COUNT(DISTINCT day, vhash) v, COUNT(*) p, MAX(csrc) src FROM visit_log $W GROUP BY country ORDER BY v DESC, p DESC LIMIT 15");
    $o['sources'] = $q("SELECT csrc, COUNT(*) n FROM visit_log $W GROUP BY csrc");
    $o['pages'] = $q("SELECT page, COUNT(*) views, COUNT(DISTINCT day, vhash) visitors FROM visit_log $W GROUP BY page ORDER BY views DESC LIMIT 12");
    $o['items'] = $q("SELECT page, item, COUNT(*) views FROM visit_log $W AND item <> '' GROUP BY page, item ORDER BY views DESC LIMIT 8");
    $o['refs'] = $q("SELECT ref, COUNT(DISTINCT day, vhash) v FROM visit_log $W AND ref <> '' GROUP BY ref ORDER BY v DESC LIMIT 6");
    $o['hours'] = array_fill(0, 24, 0); foreach ($q("SELECT hr, COUNT(*) n FROM visit_log $W GROUP BY hr") as $x) $o['hours'][(int)$x['hr']] = (int)$x['n'];
    return $o;
}

/* ---------- panels shared by the dashboard and the Visitors page ---------- */
function ana_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ana_trend($now, $prev) { if ($prev <= 0) return ''; $t = round(($now - $prev) * 100 / $prev); return '<span class="' . ($t >= 0 ? 'up' : '') . '">' . ($t >= 0 ? '▲ ' : '▼ ') . abs($t) . '%</span> vs previous ' . ''; }

function ana_panel_chart($r, $title = 'Visitors') {
    $max = max(1, ...array_values(array_map(fn($x) => $x["p"], $r["daily"]))); $n = count($r['daily']); $step = $n > 40 ? 10 : ($n > 16 ? 5 : 1); $i = 0;
    $o = '<div class="admin-card"><div class="admin-card-head"><h2>' . ana_h($title) . ' — last ' . $r['days'] . ' days</h2><span class="admin-help"><i class="an-key v"></i>Visitors <i class="an-key p"></i>Page views</span></div>';
    if (!$r['views']) $o .= '<p class="admin-help">Visits will appear here as people browse the website (your own admin visits are not counted).</p>';
    $o .= '<div class="dx-chart wide an-chart">';
    foreach ($r['daily'] as $d => $x) { $i++; $o .= '<div class="c" title="' . ana_h(date('D j M', strtotime($d)) . ': ' . $x['v'] . ' visitors, ' . $x['p'] . ' page views') . '"><i class="pv" style="height:' . max(3, round($x['p'] / $max * 105)) . 'px"><b style="height:' . ($x['p'] ? round($x['v'] / $x['p'] * 100) : 0) . '%"></b></i><span>' . (($i % $step === 0 || $i === 1) ? date('j', strtotime($d)) : '&nbsp;') . '</span></div>'; }
    return $o . '</div></div>';
}
function ana_panel_devices($r) {
    $t = max(1, array_sum($r['devices'])); $lab = ['m' => ['Mobile', '#D50000'], 't' => ['Tablet', '#FF7900'], 'd' => ['Desktop', '#111111']];
    $o = '<div class="admin-card"><div class="admin-card-head"><h2>Mobile or desktop?</h2><span class="admin-help">share of visitors</span></div>';
    if (!array_sum($r['devices'])) return $o . '<p class="admin-help">No visits recorded yet.</p></div>';
    $o .= '<div class="an-stack" role="img" aria-label="Device split">';
    foreach ($lab as $k => [$l, $c]) if ($r['devices'][$k]) $o .= '<div style="width:' . round($r['devices'][$k] * 100 / $t, 2) . '%;background:' . $c . ';color:' . ($k === 'd' ? '#fff' : '#111') . '">' . (($r['devices'][$k] / $t) >= .08 ? round($r['devices'][$k] * 100 / $t) . '%' : '') . '</div>';
    $o .= '</div><div class="an-legend">';
    foreach ($lab as $k => [$l, $c]) $o .= '<span><i style="background:' . $c . '"></i><b>' . ana_h($l) . '</b> ' . number_format($r['devices'][$k]) . ' <small>(' . round($r['devices'][$k] * 100 / $t) . '%)</small></span>';
    return $o . '</div></div>';
}
function ana_panel_countries($r, $limit = 8) {
    $o = '<div class="admin-card"><div class="admin-card-head"><h2>Where visitors come from</h2><span class="admin-help">top countries</span></div>';
    if (!$r['countries']) return $o . '<p class="admin-help">No visits recorded yet.</p></div>';
    $max = max(1, (int)$r['countries'][0]['v']); $o .= '<div class="dx-top">';
    foreach (array_slice($r['countries'], 0, $limit) as $c) $o .= '<div class="r"><span>' . ana_flag($c['country']) . ' ' . ana_h(ana_country_name($c['country'])) . '</span><div class="bar"><i style="width:' . round($c['v'] / $max * 100) . '%"></i></div><em>' . number_format((int)$c['v']) . '</em></div>';
    $o .= '</div>'; $src = array_column($r['sources'], 'n', 'csrc'); $tot = max(1, array_sum($src)); $est = (int)($src['l'] ?? 0) + (int)($src['u'] ?? 0);
    if ($est / $tot > .5) $o .= '<p class="admin-help" style="margin-top:10px">Country is estimated from the visitor’s browser language because the host does not supply a location. <a href="visitors.php#accuracy">Make it more accurate</a>.</p>';
    return $o . '</div>';
}
function ana_panel_pages($r, $limit = 8, $title = 'Most visited pages') {
    $o = '<div class="admin-card"><div class="admin-card-head"><h2>' . ana_h($title) . '</h2><span class="admin-help">' . $r['days'] . ' days</span></div>';
    if (!$r['pages']) return $o . '<p class="admin-help">No visits recorded yet.</p></div>';
    $max = max(1, (int)$r['pages'][0]['views']); $o .= '<div class="dx-top">';
    foreach (array_slice($r['pages'], 0, $limit) as $p) $o .= '<div class="r"><span>' . ana_h(ana_page_label($p['page'])) . '</span><div class="bar"><i style="width:' . round($p['views'] / $max * 100) . '%"></i></div><em title="' . number_format((int)$p['visitors']) . ' visitors">' . number_format((int)$p['views']) . '</em></div>';
    return $o . '</div></div>';
}
