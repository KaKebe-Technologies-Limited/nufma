<?php
require __DIR__ . "/includes/db.php";
header("Content-Type: application/xml; charset=utf-8");

$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$x = fn($s) => htmlspecialchars($s, ENT_XML1);

$urls = [
    ["index.php", "1.0", "weekly"], ["about.php", "0.8", "monthly"], ["membership.php", "0.8", "monthly"],
    ["awards.php", "0.9", "weekly"], ["programmes.php", "0.8", "monthly"], ["sacco.php", "0.6", "monthly"], ["network.php", "0.6", "monthly"],
    ["news.php", "0.9", "daily"], ["resources.php", "0.6", "weekly"], ["guests.php", "0.6", "monthly"], ["opportunities.php", "0.7", "weekly"], ["gallery.php", "0.7", "weekly"], ["partners.php", "0.7", "monthly"], ["contact.php", "0.6", "monthly"],
    ["support.php", "0.7", "monthly"], ["policy.php?p=privacy", "0.3", "yearly"], ["policy.php?p=cookies", "0.2", "yearly"], ["policy.php?p=terms", "0.2", "yearly"], ["policy.php?p=accessibility", "0.2", "yearly"],
];
foreach ($conn->query("SELECT slug FROM award_editions WHERE is_active=1") as $r) $urls[] = ["award.php?e=" . $r['slug'], "0.8", "monthly"];
foreach ($conn->query("SELECT slug FROM programmes WHERE is_active=1") as $r) $urls[] = ["programme.php?slug=" . $r['slug'], "0.7", "monthly"];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$path, $prio, $freq]) {
    echo "  <url><loc>" . $x("$siteUrl/$path") . "</loc><changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
}
foreach ($conn->query("SELECT slug, updated_at FROM posts WHERE is_published=1") as $row) {
    echo "  <url><loc>" . $x("$siteUrl/post.php?slug=" . $row['slug']) . "</loc><lastmod>" . date('c', strtotime($row['updated_at'])) . "</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>\n";
}
echo '</urlset>';
