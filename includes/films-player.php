<?php
/** Player markup for the NUFA Film Showcase. */
function film_logo_rel($conn) {
    require_once __DIR__ . "/settings.php"; $cfg = nufa_site_settings($conn);
    return ($cfg['site_logo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $cfg['site_logo'])) ? $cfg['site_logo'] : 'logo/nufa-logo.png';
}
/** The clean NUFA player: the film with the NUFA logo on top, custom controls and fullscreen that keeps the logo. */
function film_player_html($conn, $f, $S, $adminPreview = false) {
    $src = film_source($f, $adminPreview); $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $pre = $adminPreview ? '../' : '';
    if (!$src) return '<div class="fp fp--empty"><p>This film cannot be played right now.</p></div>';
    $pos = in_array($S['film_logo_pos'], ['tl', 'tr', 'bl', 'br'], true) ? $S['film_logo_pos'] : 'tr'; $op = max(20, min(100, (int)$S['film_logo_opacity'])) / 100;
    $poster = film_poster_ok($f) ? ' poster="' . $h($pre . 'assets/images/' . $f['poster']) . '"' : '';
    $fullIcon = '<svg viewBox="0 0 24 24" width="20" height="20"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round"/></svg>';
    $o = '<div class="fp fp--' . $src['type'] . '" data-fp="' . $src['type'] . '"><div class="fp-stage">';
    if ($src['type'] === 'embed') $o .= '<iframe src="' . $h($src['src']) . (strpos($src['src'], '?') === false ? '?' : '&amp;') . 'rel=0&amp;modestbranding=1" title="' . $h($f['title']) . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="autoplay; encrypted-media; picture-in-picture"></iframe>';
    else $o .= '<video preload="metadata" playsinline controlsList="nodownload noplaybackrate" disablePictureInPicture oncontextmenu="return false"' . $poster . '><source src="' . $h(($src['type'] === 'mp4' ? $pre : '') . $src['src']) . '" type="video/' . (preg_match('/\.webm(\?|$)/i', $src['src']) ? 'webm' : 'mp4') . '"></video>';
    $o .= '<img class="fp-logo fp-logo--' . $pos . '" style="opacity:' . $op . '" src="' . $pre . 'assets/images/' . $h(film_logo_rel($conn)) . '" alt="NUFA" draggable="false">';
    if ($src['type'] !== 'embed') {
        $o .= '<button type="button" class="fp-big" aria-label="Play"><svg viewBox="0 0 24 24" width="38" height="38"><path d="M8 5v14l11-7z" fill="currentColor"/></svg></button>'
            . '<div class="fp-bar"><button type="button" class="fp-play" aria-label="Play or pause"><svg viewBox="0 0 24 24" width="22" height="22"><path class="p" d="M8 5v14l11-7z" fill="currentColor"/><path class="q" d="M7 5h4v14H7zM13 5h4v14h-4z" fill="currentColor" style="display:none"/></svg></button>'
            . '<span class="fp-t">0:00</span><input type="range" class="fp-seek" min="0" max="1000" value="0" aria-label="Seek"><span class="fp-d">0:00</span>'
            . '<button type="button" class="fp-mute" aria-label="Mute"><svg viewBox="0 0 24 24" width="20" height="20"><path d="M3 9v6h4l5 4V5L7 9H3z" fill="currentColor"/><path class="w" d="M16 8a5 5 0 010 8M18.5 5.5a9 9 0 010 13" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round"/></svg></button>'
            . '<input type="range" class="fp-vol" min="0" max="1" step="0.05" value="1" aria-label="Volume"><button type="button" class="fp-full" aria-label="Fullscreen">' . $fullIcon . '</button></div>';
    } else $o .= '<button type="button" class="fp-full fp-full--embed" aria-label="Fullscreen">' . $fullIcon . '</button>';
    return $o . '</div></div>';
}
