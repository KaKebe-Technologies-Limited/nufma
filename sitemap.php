<?php
require __DIR__ . "/includes/db.php";
header("Content-Type: application/xml; charset=utf-8");

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https://' : 'http://';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/');
$siteUrl = $scheme . $_SERVER['HTTP_HOST'] . $scriptDir;

$staticPages = [
    ["index.php", "1.0", "weekly"],
    ["about.php", "0.8", "monthly"],
    ["awards.php", "0.9", "weekly"],
    ["gallery.php", "0.8", "weekly"],
    ["news.php", "0.9", "daily"],
    ["partners.php", "0.6", "monthly"],
    ["contact.php", "0.6", "monthly"],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>{$siteUrl}/{$p[0]}</loc>\n";
    echo "    <changefreq>{$p[2]}</changefreq>\n";
    echo "    <priority>{$p[1]}</priority>\n";
    echo "  </url>\n";
}

$res = $conn->query("SELECT slug, updated_at FROM posts WHERE is_published=1");
while ($row = $res->fetch_assoc()) {
    echo "  <url>\n";
    echo "    <loc>{$siteUrl}/post.php?slug=" . htmlspecialchars($row['slug']) . "</loc>\n";
    echo "    <lastmod>" . date('c', strtotime($row['updated_at'])) . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
