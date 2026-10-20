<?php
/**
 * Editable page content (admin → Page Content).
 *
 * Fields are declared in includes/content-schemas.php with a default that
 * matches the original copy. Edited values live in the `page_content` table;
 * anything not edited (or cleared) falls back to the default, so a public page
 * never renders blank.
 *
 *   cms_t($page, $key)   → value, HTML-escaped   (text / textarea fields)
 *   cms_h($page, $key)   → value, raw HTML       (html fields)
 *   cms_img($page, $key) → image path relative to assets/images/
 */
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/content-schemas.php";

function cms_install($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS page_content (
        page VARCHAR(50) NOT NULL,
        field_key VARCHAR(80) NOT NULL,
        field_value MEDIUMTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (page, field_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** All stored overrides for a page (cached per request). */
function cms_overrides($conn, $page) {
    static $cache = [];
    if (!isset($cache[$page])) {
        cms_install($conn);
        $cache[$page] = [];
        $s = $conn->prepare("SELECT field_key, field_value FROM page_content WHERE page=?");
        $s->bind_param("s", $page);
        $s->execute();
        $r = $s->get_result();
        while ($row = $r->fetch_assoc()) $cache[$page][$row['field_key']] = $row['field_value'];
    }
    return $cache[$page];
}

/** Current values for every field of a page (stored value, else the schema default). */
function cms_load($conn, $page, array $schema) {
    $ov = cms_overrides($conn, $page);
    $out = [];
    foreach ($schema as $key => $def) {
        $out[$key] = (isset($ov[$key]) && $ov[$key] !== '') ? $ov[$key] : ($def['default'] ?? '');
    }
    return $out;
}

function cms_value($page, $key) {
    global $conn, $CMS_SCHEMAS;
    $ov = cms_overrides($conn, $page);
    if (isset($ov[$key]) && $ov[$key] !== '') return $ov[$key];
    return $CMS_SCHEMAS[$page]['fields'][$key]['default'] ?? '';
}
function cms_t($page, $key)   { return htmlspecialchars(cms_value($page, $key), ENT_QUOTES, 'UTF-8'); }
function cms_h($page, $key)   { return cms_value($page, $key); }
function cms_img($page, $key) {
    $v = cms_value($page, $key);
    $def = $GLOBALS['CMS_SCHEMAS'][$page]['fields'][$key]['default'] ?? '';
    // fall back to the default if the stored file has been removed
    if ($v !== $def && !is_file(__DIR__ . "/../assets/images/" . $v)) return $def;
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/** Saves the posted form for one page. Empty values clear the override (back to default). */
function cms_save($conn, $page, array $schema, array $post, array $files) {
    cms_install($conn);
    $up = $conn->prepare("INSERT INTO page_content (page, field_key, field_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value)");
    $del = $conn->prepare("DELETE FROM page_content WHERE page=? AND field_key=?");
    $errors = [];
    foreach ($schema as $key => $def) {
        $type = $def['type'] ?? 'text';
        if ($type === 'image') {
            $f = $files[$key] ?? null;
            if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            if ($f['error'] !== UPLOAD_ERR_OK) { $errors[] = $def['label'] . ": upload failed."; continue; }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
            if (!$ext || $f['size'] > 6 * 1048576) { $errors[] = $def['label'] . ": use a JPG, PNG or WebP under 6 MB."; continue; }
            $dir = __DIR__ . "/../assets/images/uploads/content/";
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $name = $page . '-' . $key . '-' . time() . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $dir . $name)) {
                $val = "uploads/content/" . $name;
                $up->bind_param("sss", $page, $key, $val);
                $up->execute();
            } else $errors[] = $def['label'] . ": could not save the file.";
            continue;
        }
        if (!array_key_exists($key, $post)) continue;
        $val = $type === 'html' ? trim((string)$post[$key]) : trim(strip_tags((string)$post[$key]));
        if ($val === '' || $val === ($def['default'] ?? null)) {
            $del->bind_param("ss", $page, $key);
            $del->execute();
        } else {
            $up->bind_param("sss", $page, $key, $val);
            $up->execute();
        }
    }
    return $errors;
}

/** Image path (relative to assets/images/), unescaped — for helpers that escape on output. */
function cms_img_raw($page, $key) {
    return htmlspecialchars_decode(cms_img($page, $key), ENT_QUOTES);
}
