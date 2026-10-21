<?php
/**
 * Maintenance mode: the public website is replaced by a holding page (logo, message, contact details,
 * countdown and expected date) while an update is made. Controlled from admin → Maintenance mode.
 * Signed-in admins still see the real site so they can check the update.
 */
function maint_tz() { return new DateTimeZone('Africa/Kampala'); }

function maint_settings($conn) {
    $d = ['maint_on' => '0', 'maint_start' => '', 'maint_end' => '', 'maint_auto_end' => '1', 'maint_countdown' => '1',
          'maint_title' => 'We\'ll be right back', 'maint_message' => 'The NUFA website is being updated. Thank you for your patience — we will be back online shortly.'];
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'maint\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d)) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}

function maint_ts($v) { if ($v === '') return null; try { return (new DateTime($v, maint_tz()))->getTimestamp(); } catch (Exception $e) { return null; } }

/** 'live' | 'scheduled' (switched on, starts later) | 'down' */
function maint_state($m) {
    if ($m['maint_on'] !== '1') return 'live';
    $now = time(); $s = maint_ts($m['maint_start']); $e = maint_ts($m['maint_end']);
    if ($s && $now < $s) return 'scheduled';
    if ($e && $now >= $e && $m['maint_auto_end'] === '1') return 'live';
    return 'down';
}

function maint_is_admin() {
    if (empty($_COOKIE['nufa_admin'])) return false;
    require_once __DIR__ . "/../admin/includes/security.php";
    admin_session_start();
    return isset($_SESSION['admin_id']);
}

/** Called from the public header: shows the holding page and stops, unless the site is live or an admin is signed in. */
function maint_gate($conn, $cfg, $logoRel) {
    $m = maint_settings($conn);
    if (maint_state($m) !== 'down') return;
    if (empty($_GET['visitor']) && maint_is_admin()) {   // signed-in admins keep seeing the site, with a reminder
        ob_start(function ($html) {
            $bar = '<div style="position:fixed;left:0;right:0;bottom:0;z-index:99999;background:#c0392b;color:#fff;padding:10px 16px;font:600 14px system-ui,sans-serif;text-align:center">Maintenance mode is ON — visitors see the holding page; you see the site because you are signed in to the admin. <a href="?visitor=1" style="color:#fff;text-decoration:underline">Preview as a visitor</a> · <a href="admin/maintenance.php" style="color:#fff;text-decoration:underline">Manage</a></div>';
            return preg_replace('/<\/body>/i', $bar . '</body>', $html, 1);
        });
        return;
    }
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $end = maint_ts($m['maint_end']);
    $endIso = $end ? (new DateTime('@' . $end))->setTimezone(maint_tz())->format('Y-m-d\TH:i:sP') : '';
    $endTxt = $end ? (new DateTime('@' . $end))->setTimezone(maint_tz())->format('l, j F Y \a\t H:i') . ' (East Africa Time)' : '';
    http_response_code(503); header('Retry-After: ' . ($end && $end > time() ? $end - time() : 3600)); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
    $soc = array_filter(['Facebook' => $cfg['facebook_url'] ?? '', 'Instagram' => $cfg['instagram_url'] ?? '', 'X' => $cfg['x_url'] ?? '', 'LinkedIn' => $cfg['linkedin_url'] ?? '', 'YouTube' => $cfg['youtube_url'] ?? '']);
    $gold = preg_match('/^#[0-9a-f]{6}$/i', $cfg['brand_gold'] ?? '') ? $cfg['brand_gold'] : '#D4AF63';
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
<title><?php echo $e($m['maint_title']); ?> — <?php echo $e($cfg['site_name']); ?></title>
<style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 50% 0,#2a2418,#0e0d0b 70%);color:#fff;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;padding:24px}
.m{max-width:680px;width:100%;text-align:center}.m img{max-height:92px;max-width:240px;object-fit:contain;margin-bottom:22px}
.m h1{font-size:clamp(1.8rem,5vw,2.8rem);margin:0 0 12px;color:<?php echo $gold; ?>}.m p.msg{color:#d9d4c7;line-height:1.7;font-size:1.05rem;margin:0 auto 26px;white-space:pre-line}
.cd{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:0 auto 12px;max-width:460px}.cd div{background:#0d0c0a;border:1px solid <?php echo $gold; ?>66;border-radius:16px;padding:14px 4px}
.cd b{display:block;font-size:clamp(1.6rem,6vw,2.3rem);color:<?php echo $gold; ?>;font-variant-numeric:tabular-nums;line-height:1}.cd span{font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#a8a290}
.when{color:#d9d4c7;margin:0 0 26px}.when b{color:#fff}
.c{display:flex;flex-wrap:wrap;gap:10px 22px;justify-content:center;border-top:1px solid #ffffff22;padding-top:22px;color:#d9d4c7;font-size:.95rem}.c a{color:<?php echo $gold; ?>;text-decoration:none}.c a:hover{text-decoration:underline}
.s{margin-top:14px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap;font-size:.88rem}.s a{color:#a8a290;text-decoration:none}.s a:hover{color:<?php echo $gold; ?>}
</style></head><body><main class="m">
<img src="assets/images/<?php echo $e($logoRel); ?>" alt="<?php echo $e($cfg['site_name']); ?>">
<h1><?php echo $e($m['maint_title']); ?></h1>
<p class="msg"><?php echo $e($m['maint_message']); ?></p>
<?php if ($end && $m['maint_countdown'] === '1' && $end > time()): ?><div class="cd" id="cd" data-end="<?php echo $e($endIso); ?>"><div><b data-u="d">--</b><span>days</span></div><div><b data-u="h">--</b><span>hours</span></div><div><b data-u="m">--</b><span>mins</span></div><div><b data-u="s">--</b><span>secs</span></div></div><?php endif; ?>
<?php if ($end): ?><p class="when">Expected back: <b><?php echo $e($endTxt); ?></b></p><?php endif; ?>
<div class="c"><?php if (!empty($cfg['site_email'])): ?><a href="mailto:<?php echo $e($cfg['site_email']); ?>"><?php echo $e($cfg['site_email']); ?></a><?php endif; ?>
<?php foreach (['site_phone', 'site_phone2'] as $k) if (!empty($cfg[$k])): ?><a href="tel:<?php echo $e(preg_replace('/[^+\d]/', '', $cfg[$k])); ?>"><?php echo $e($cfg[$k]); ?></a><?php endif; ?>
<?php if (!empty($cfg['site_address'])): ?><span><?php echo $e($cfg['site_address']); ?></span><?php endif; ?></div>
<?php if ($soc): ?><div class="s"><?php foreach ($soc as $n => $u): ?><a href="<?php echo $e($u); ?>" target="_blank" rel="noopener"><?php echo $e($n); ?></a><?php endforeach; ?></div><?php endif; ?>
</main>
<script>(function(){var el=document.getElementById('cd');if(!el)return;var t=Date.parse(el.dataset.end);function p(n){return n<10?'0'+n:''+n}
function tick(){var d=t-Date.now();if(d<=0){location.reload();return;}var s=Math.floor(d/1000),v={d:Math.floor(s/86400),h:p(Math.floor(s%86400/3600)),m:p(Math.floor(s%3600/60)),s:p(s%60)};for(var k in v){var b=el.querySelector('[data-u="'+k+'"]');if(b)b.textContent=v[k];}}
tick();setInterval(tick,1000);})();</script></body></html>
<?php
    exit;
}
