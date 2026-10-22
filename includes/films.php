<?php
/**
 * NUFA Film Showcase: filmmakers submit films (MP4 upload or a link), NUFA reviews them, approved films are shown
 * to the public with the NUFA logo on the player and a "support this filmmaker" tab. Managed in admin → Film Showcase.
 * NUFA does not handle donations: viewers send support straight to the filmmaker's own details.
 */
require_once __DIR__ . "/membership.php";   // mem_send, mem_store_upload, MEM_IMG_TYPES

const FILM_STORAGE_DIR = __DIR__ . '/../storage/films/';
const FILM_IMG_DIR = __DIR__ . '/../assets/images/uploads/films/';

function film_storage() {
    if (!is_dir(FILM_STORAGE_DIR)) @mkdir(FILM_STORAGE_DIR, 0775, true);
    if (!is_file(FILM_STORAGE_DIR . '.htaccess')) @file_put_contents(FILM_STORAGE_DIR . '.htaccess', "Require all denied\nDeny from all\n");
    if (!is_file(FILM_STORAGE_DIR . 'index.html')) @file_put_contents(FILM_STORAGE_DIR . 'index.html', '');
    return FILM_STORAGE_DIR;
}
function film_genres() { return ['Drama', 'Comedy', 'Documentary', 'Animation', 'Thriller', 'Horror', 'Romance', 'Action', 'Music video', 'Experimental', 'Family', 'Educational', 'Other']; }
function film_ratings() { return ['All ages', '13+', '16+', '18+']; }

function film_defaults() {
    return [
        'film_open' => '1', 'film_max_mb' => '300', 'film_max_minutes' => '60', 'film_logo_pos' => 'tr', 'film_logo_opacity' => '75', 'film_notify_email' => '',
        'film_intro' => 'Watch short films and productions by filmmakers from Northern Uganda and beyond. Every film is reviewed by NUFA before it is shown.',
        'film_submit_intro' => 'Share your film with the world. Upload an MP4 file or send a link, tell us about yourself and your film, and our team will review it. Approved films are shown on this website with the NUFA logo on the player.',
        'film_guidelines' => "Original work that you have the right to show\nShort films and productions up to the maximum length; clear picture and sound\nNo hate speech, incitement to violence, sexual content involving minors, or content that exposes people to harm\nMusic, footage and images used with permission or a suitable licence\nEveryone clearly identifiable in the film has agreed to appear\nAccurate title, credits and description",
        'film_checklist' => "Picture quality is watchable and the film plays from start to finish\nSound is clear (dialogue can be understood)\nContent follows the submission guidelines\nRights, music and consent declarations are credible\nTitle, credits, synopsis and poster are accurate\nFilmmaker details and support (donation) details look genuine",
        'film_donate_note' => 'Gifts go straight to the filmmaker. NUFA does not receive, hold or take a share of these gifts, so only send what you are comfortable with and check the details before you pay.',
    ];
}
function film_settings($conn) {
    $d = film_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'film\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d)) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}
function film_save_setting($conn, $k, $v) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); }
/** Largest upload the server will really accept, in MB. */
function film_server_limit_mb() {
    $conv = function ($v) { $v = trim((string)$v); $n = (float)$v; switch (strtolower(substr($v, -1))) { case 'g': $n *= 1024; case 'm': break; case 'k': $n /= 1024; break; default: $n /= 1048576; } return $n; };
    return (int)floor(min($conv(ini_get('upload_max_filesize')), $conv(ini_get('post_max_size'))));
}

function film_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS films (
        id INT AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(20) NOT NULL DEFAULT '', token_hash VARCHAR(64) NOT NULL DEFAULT '', slug VARCHAR(120) NOT NULL DEFAULT '',
        title VARCHAR(190) NOT NULL, tagline VARCHAR(255) NOT NULL DEFAULT '', synopsis TEXT, genre VARCHAR(40) NOT NULL DEFAULT '', language VARCHAR(80) NOT NULL DEFAULT '', subtitles VARCHAR(120) NOT NULL DEFAULT '',
        year INT NOT NULL DEFAULT 0, runtime_min INT NOT NULL DEFAULT 0, country VARCHAR(80) NOT NULL DEFAULT '', rating VARCHAR(12) NOT NULL DEFAULT 'All ages', content_notes VARCHAR(500) NOT NULL DEFAULT '', credits TEXT,
        source_type VARCHAR(6) NOT NULL DEFAULT 'link', video_file VARCHAR(255) NOT NULL DEFAULT '', video_url VARCHAR(500) NOT NULL DEFAULT '', file_size BIGINT NOT NULL DEFAULT 0, poster VARCHAR(255) NOT NULL DEFAULT '',
        fm_name VARCHAR(150) NOT NULL DEFAULT '', fm_bio TEXT, fm_photo VARCHAR(255) NOT NULL DEFAULT '', fm_location VARCHAR(150) NOT NULL DEFAULT '', email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '',
        website VARCHAR(255) NOT NULL DEFAULT '', instagram VARCHAR(255) NOT NULL DEFAULT '', facebook VARCHAR(255) NOT NULL DEFAULT '', x_url VARCHAR(255) NOT NULL DEFAULT '', youtube VARCHAR(255) NOT NULL DEFAULT '',
        donate_mtn VARCHAR(40) NOT NULL DEFAULT '', donate_airtel VARCHAR(40) NOT NULL DEFAULT '', donate_name VARCHAR(150) NOT NULL DEFAULT '', donate_link VARCHAR(500) NOT NULL DEFAULT '', donate_link_label VARCHAR(60) NOT NULL DEFAULT '',
        donate_bank VARCHAR(400) NOT NULL DEFAULT '', donate_note VARCHAR(400) NOT NULL DEFAULT '',
        status VARCHAR(10) NOT NULL DEFAULT 'pending', reject_reason VARCHAR(600) NOT NULL DEFAULT '', admin_notes TEXT, checklist VARCHAR(80) NOT NULL DEFAULT '', is_featured TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0,
        views INT NOT NULL DEFAULT 0, support_clicks INT NOT NULL DEFAULT 0, approved_at DATETIME NULL, ip VARCHAR(45) NOT NULL DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status, sort_order), KEY idx_slug (slug)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $m = $conn->query("SELECT setting_value FROM settings WHERE setting_key='film_menu_added'");
    if ($m && !$m->num_rows) {
        $p = $conn->query("SELECT id, nav_key FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='resources.php' LIMIT 1")->fetch_assoc();
        if ($p) {
            if (strpos($p['nav_key'], 'films') === false) $conn->query("UPDATE nav_items SET nav_key='" . $conn->real_escape_string(trim($p['nav_key'] . ',films', ',')) . "' WHERE id=" . (int)$p['id']);
            if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='films.php'")->fetch_row()[0]) {
                $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$p['id'])->fetch_row()[0];
                $conn->query("INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', " . (int)$p['id'] . ", 'Film Showcase', 'films.php', $o)");
            }
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('film_menu_added','1')");
    }
}

function film_slug($conn, $title, $skip = 0) {
    $b = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'film'; $s = $b; $n = 1;
    while ((int)$conn->query("SELECT COUNT(*) FROM films WHERE slug='" . $conn->real_escape_string($s) . "' AND id<>" . (int)$skip)->fetch_row()[0]) $s = $b . '-' . (++$n);
    return $s;
}
function film_poster_ok($f) { return $f['poster'] !== '' && is_file(__DIR__ . '/../assets/images/' . $f['poster']); }
function film_photo_ok($f) { return $f['fm_photo'] !== '' && is_file(__DIR__ . '/../assets/images/' . $f['fm_photo']); }
function film_has_support($f) { return $f['donate_mtn'] !== '' || $f['donate_airtel'] !== '' || $f['donate_link'] !== '' || $f['donate_bank'] !== ''; }
function film_runtime($f) { $m = (int)$f['runtime_min']; return $m ? ($m >= 60 ? floor($m / 60) . ' h ' . ($m % 60 ? ($m % 60) . ' min' : '') : $m . ' min') : ''; }
function film_lines($t) { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$t)))); }

/** What the player should load: ['type' => 'mp4'|'embed'|'direct', 'src' => url], or null. */
function film_source($f, $adminPreview = false) {
    if ($f['source_type'] === 'mp4' && $f['video_file'] !== '') return ['type' => 'mp4', 'src' => ($adminPreview ? 'admin/film-file.php' : 'film-stream.php') . '?id=' . (int)$f['id'] . ($adminPreview ? '' : '&v=' . substr(md5($f['video_file']), 0, 6))];
    $u = trim($f['video_url']);
    if ($e = ui_video_embed($u)) return ['type' => 'embed', 'src' => $e];
    if (preg_match('#^https://[^\s]+\.(mp4|webm)(\?[^\s]*)?$#i', $u)) return ['type' => 'direct', 'src' => $u];
    return null;
}
/** Valid link for a film: YouTube, Vimeo or a direct https .mp4/.webm file. */
function film_link_ok($u) { $u = trim($u); return $u !== '' && (ui_video_embed($u) !== '' || preg_match('#^https://[^\s]+\.(mp4|webm)(\?[^\s]*)?$#i', $u)); }

/** Streams a stored film with HTTP Range support so players can seek. */
function film_stream_file($path, $mime = 'video/mp4') {
    if (!is_file($path)) { http_response_code(404); exit("Not found"); }
    $size = filesize($path); $start = 0; $end = $size - 1;
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    while (ob_get_level()) ob_end_clean();
    header("Content-Type: $mime"); header("Accept-Ranges: bytes"); header("X-Content-Type-Options: nosniff"); header("Cache-Control: private, max-age=3600"); header("Content-Disposition: inline");
    if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        if ($m[1] === '' && $m[2] !== '') { $start = max(0, $size - (int)$m[2]); } else { $start = (int)$m[1]; if ($m[2] !== '') $end = min($end, (int)$m[2]); }
        if ($start > $end || $start >= $size) { http_response_code(416); header("Content-Range: bytes */$size"); exit; }
        http_response_code(206); header("Content-Range: bytes $start-$end/$size");
    }
    header("Content-Length: " . ($end - $start + 1));
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') exit;
    @set_time_limit(0); $fp = fopen($path, 'rb'); fseek($fp, $start); $left = $end - $start + 1;
    while ($left > 0 && !feof($fp) && !connection_aborted()) { $chunk = fread($fp, min(262144, $left)); echo $chunk; $left -= strlen($chunk); flush(); }
    fclose($fp); exit;
}

require_once __DIR__ . "/films-player.php";
