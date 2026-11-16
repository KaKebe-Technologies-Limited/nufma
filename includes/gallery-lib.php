<?php
/**
 * Gallery helpers: the "Highlights" photo set import, thumbnails, and the combined photo list used by the admin
 * "Choose from gallery" picker (so any picture in the gallery can be featured anywhere on the website).
 */
const GALLERY_HL_DIR = 'gallery/highlights/';

/** Small preview path for a gallery image (Highlights have dedicated thumbnails). */
function gallery_thumb($rel) {
    if (strpos($rel, GALLERY_HL_DIR) === 0 && strpos($rel, 'thumbs/') === false) {
        $t = GALLERY_HL_DIR . 'thumbs/' . basename($rel);
        if (is_file(__DIR__ . '/../assets/images/' . $t)) return $t;
    }
    if (strpos($rel, 'uploads/posts/') === 0 && strpos($rel, 'thumbs/') === false) {   // admin uploads: small preview made at upload time
        $t = 'uploads/posts/thumbs/' . pathinfo($rel, PATHINFO_FILENAME) . '.jpg';
        if (is_file(__DIR__ . '/../assets/images/' . $t)) return $t;
    }
    return $rel;
}

/** Makes a small JPEG preview (max 760 px wide) of an uploaded picture so big originals are not sent to every visitor. Does nothing if the server has no image library. */
function gallery_make_thumb($srcAbs, $destAbs) {
    if (!function_exists('imagecreatetruecolor')) return false;
    $info = @getimagesize($srcAbs); if (!$info || $info[0] < 1 || $info[1] < 1) return false;
    if ($info[0] * $info[1] > 60000000) return false;   // too large to decode safely
    $im = $info[2] === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($srcAbs) : ($info[2] === IMAGETYPE_PNG && function_exists('imagecreatefrompng') ? @imagecreatefrompng($srcAbs) : ($info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcAbs) : false));
    if (!$im) return false;
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) { $ex = @exif_read_data($srcAbs); $o = $ex['Orientation'] ?? 1; if ($o === 3) $im = imagerotate($im, 180, 0); elseif ($o === 6) $im = imagerotate($im, -90, 0); elseif ($o === 8) $im = imagerotate($im, 90, 0); }
    $w = imagesx($im); $h = imagesy($im); $nw = min(760, $w); $nh = (int)round($h * $nw / $w);
    $t = imagecreatetruecolor($nw, $nh); imagefill($t, 0, 0, imagecolorallocate($t, 255, 255, 255)); imagecopyresampled($t, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    if (!is_dir(dirname($destAbs))) @mkdir(dirname($destAbs), 0775, true);
    $ok = @imagejpeg($t, $destAbs, 82); imagedestroy($im); imagedestroy($t);
    return $ok;
}

/** Makes sure the "featured on the home page" column exists (checked once, then remembered). */
function gallery_ensure($conn) {
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='gallery_featured_col'");
    if (!$f || $f->num_rows) return;
    $c = $conn->query("SHOW COLUMNS FROM gallery_images LIKE 'featured'");
    if ($c && !$c->num_rows) $conn->query("ALTER TABLE gallery_images ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('gallery_featured_col','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

/** Photos the admin ticked as "Feature on home page": [[path, caption], …] (max $n), or [] when none. */
function gallery_featured($conn, $n = 9) {
    gallery_ensure($conn);
    $r = $conn->query("SELECT image, caption FROM gallery_images WHERE featured=1 ORDER BY id ASC LIMIT " . (int)$n);
    $out = [];
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $g) $out[] = [$g['image'], $g['caption'] ?: 'NUFA gallery'];
    return $out;
}

/** One-time: add every photo found in assets/images/gallery/highlights/ to the gallery (category "Highlights"). Safe to call on every request. */
function gallery_hl_import($conn) {
    gallery_ensure($conn);
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='gallery_hl_v1'");
    if (!$f || $f->num_rows) return;
    $files = glob(__DIR__ . '/../assets/images/' . GALLERY_HL_DIR . '*.jpg') ?: [];
    if (!$files) return;                       // photos not uploaded yet — try again next time
    sort($files);
    $have = [];
    $r = $conn->query("SELECT image FROM gallery_images WHERE image LIKE 'gallery/highlights/%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) $have[$x['image']] = 1;
    $ins = $conn->prepare("INSERT INTO gallery_images (image, caption, edition, category, created_at) VALUES (?, '', '', 'Highlights', ?)"); $ts = date('Y-m-d H:i:s');
    foreach ($files as $p) {
        $rel = GALLERY_HL_DIR . basename($p);
        if (isset($have[$rel])) continue;
        $ins->bind_param("ss", $rel, $ts); $ins->execute();
    }
    // make "Highlights" a selectable album in Admin → Lists, if the list has been customised
    $l = $conn->query("SELECT setting_value FROM settings WHERE setting_key='list_gallery_categories'");
    if ($l && ($row = $l->fetch_assoc()) && stripos("\n" . $row['setting_value'] . "\n", "\nHighlights\n") === false) {
        $v = rtrim($row['setting_value']) . "\nHighlights";
        $u = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='list_gallery_categories'"); $u->bind_param("s", $v); $u->execute();
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('gallery_hl_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

/** Every site photo that can be picked: gallery uploads and Highlights first, then the photos shipped with the site. Each: [path, caption, category]. */
function gallery_all($conn) {
    $out = []; $seen = [];
    $r = $conn->query("SELECT image, caption, category FROM gallery_images ORDER BY created_at DESC, id ASC");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $g) { $out[] = [$g['image'], $g['caption'], $g['category'] !== '' ? $g['category'] : 'Gallery']; $seen[$g['image']] = 1; }
    $root = __DIR__ . '/../assets/images/';
    $static = [['gallery/2026/*.jpg', 'NUFA Awards 2026'], ['gallery/2025/*.webp', 'NUFA Awards 2025'], ['production/*.webp', 'Productions & community']];
    foreach ($static as [$g, $label]) foreach (glob($root . $g) ?: [] as $p) {
        $rel = substr(str_replace('\\', '/', $p), strlen(str_replace('\\', '/', $root)));
        if (!isset($seen[$rel])) $out[] = [$rel, '', $label];
    }
    return $out;
}
