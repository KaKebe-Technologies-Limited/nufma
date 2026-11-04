<?php
// JSON feed for the "Choose from gallery" picker used by image fields across the admin.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/gallery-lib.php";
gallery_hl_import($conn);
header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
$q = strtolower(trim($_GET['q'] ?? '')); $cat = trim($_GET['cat'] ?? ''); $page = max(1, (int)($_GET['page'] ?? 1)); $per = 48;
$all = gallery_all($conn);
$cats = []; foreach ($all as $a) $cats[$a[2]] = ($cats[$a[2]] ?? 0) + 1;
$list = array_values(array_filter($all, fn($a) => ($cat === '' || $a[2] === $cat) && ($q === '' || strpos(strtolower($a[1] . ' ' . basename($a[0]) . ' ' . $a[2]), $q) !== false)));
$total = count($list);
$items = array_map(fn($a) => ['path' => $a[0], 'thumb' => gallery_thumb($a[0]), 'caption' => $a[1], 'cat' => $a[2]], array_slice($list, ($page - 1) * $per, $per));
echo json_encode(['items' => $items, 'total' => $total, 'page' => $page, 'more' => $page * $per < $total, 'cats' => $cats], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
