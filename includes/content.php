<?php
/**
 * Generic, schema-driven page content system.
 *
 * Each front-end page defines a schema (see includes/content-schemas.php) of
 * editable blocks: headings, paragraphs, rich text or images. Values are
 * stored in page_content (page, block_key, value) and merged over the
 * schema's defaults, so a page renders correctly even before anything has
 * been edited from the admin.
 */

function cms_load($conn, $page, $schema) {
    $values = [];
    foreach ($schema as $key => $def) $values[$key] = $def['default'] ?? '';

    $stmt = $conn->prepare("SELECT block_key, value FROM page_content WHERE page=?");
    $stmt->bind_param("s", $page);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!isset($schema[$row['block_key']])) continue;
        $v = $row['value'];
        if ($v !== null && trim($v) !== '') $values[$row['block_key']] = $v;
    }
    return $values;
}

function cms_set($conn, $page, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO page_content (page, block_key, value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
    $stmt->bind_param("sss", $page, $key, $value);
    $stmt->execute();
}

/** Handle a POST save for every field in a page's schema. */
function cms_save($conn, $page, $schema, $post, $files) {
    foreach ($schema as $key => $def) {
        $type = $def['type'] ?? 'text';
        if ($type === 'image') {
            if (!empty($files[$key]['name']) && $files[$key]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($files[$key]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $dir = __DIR__ . "/../assets/images/uploads/content/";
                    if (!is_dir($dir)) @mkdir($dir, 0775, true);
                    $fname = $page . '-' . $key . '-' . time() . '.' . $ext;
                    if (move_uploaded_file($files[$key]['tmp_name'], $dir . $fname)) {
                        cms_set($conn, $page, $key, "uploads/content/" . $fname);
                    }
                }
            }
            continue; // no new file => keep whatever is already stored
        }
        cms_set($conn, $page, $key, trim($post[$key] ?? ''));
    }
}

/** Escape for plain text/textarea output; pass html-type values through untouched. */
function cms_out($schema, $values, $key) {
    $type = $schema[$key]['type'] ?? 'text';
    $v = $values[$key] ?? '';
    return $type === 'html' ? $v : htmlspecialchars($v);
}
