<?php
require __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/seo.php";
header("Content-Type: application/xml; charset=utf-8");

$siteUrl = nufa_site_base(nufa_site_settings($conn));
$x = fn($s) => htmlspecialchars($s, ENT_XML1);

$urls = [
    ["index.php", "1.0", "weekly"], ["about.php", "0.8", "monthly"], ["membership.php", "0.8", "monthly"],
    ["awards.php", "0.9", "weekly"], ["programmes.php", "0.8", "monthly"], ["sacco.php", "0.6", "monthly"], ["network.php", "0.6", "monthly"],
    ["news.php", "0.9", "daily"], ["resources.php", "0.6", "weekly"], ["academy.php", "0.8", "weekly"], ["films.php", "0.8", "weekly"], ["ambassadors.php", "0.7", "monthly"], ["awards-perform.php", "0.5", "monthly"], ["awards-workshop.php", "0.5", "monthly"], ["academy-library.php", "0.6", "weekly"], ["academy-teach.php", "0.5", "monthly"], ["guests.php", "0.6", "monthly"], ["opportunities.php", "0.7", "weekly"], ["gallery.php", "0.7", "weekly"], ["partners.php", "0.7", "monthly"], ["contact.php", "0.6", "monthly"],
    ["support.php", "0.7", "monthly"], ["policy.php?p=privacy", "0.3", "yearly"], ["policy.php?p=cookies", "0.2", "yearly"], ["policy.php?p=terms", "0.2", "yearly"], ["policy.php?p=accessibility", "0.2", "yearly"],
];
foreach ($conn->query("SELECT slug FROM award_editions WHERE is_active=1") as $r) $urls[] = ["award.php?e=" . $r['slug'], "0.8", "monthly"];
foreach ($conn->query("SELECT slug FROM programmes WHERE is_active=1") as $r) $urls[] = ["programme.php?slug=" . $r['slug'], "0.7", "monthly"];

foreach (['guests.php' => '0.6', 'gallery.php' => '0.7', 'directory.php' => '0.7', 'opportunities.php' => '0.7', 'partners.php' => '0.7', 'contact.php' => '0.6', 'schedule.php' => '0.6', 'newsletters.php' => '0.6', 'sacco-apply.php' => '0.6', 'ambassadors.php' => '0.6'] as $__p => $__pr) if (is_file(__DIR__ . '/' . $__p) && !in_array($__p, array_column($urls, 0), true)) $urls[] = [$__p, $__pr, 'monthly'];
$__q = function ($sql) use ($conn) { $r = @$conn->query($sql); return $r ? $r->fetch_all(MYSQLI_ASSOC) : []; };
foreach ($__q("SELECT id FROM team_members WHERE is_active=1 AND bio IS NOT NULL AND bio<>''") as $r) $urls[] = ["profile.php?id=" . $r['id'], "0.5", "monthly"];
foreach ($__q("SELECT id FROM vip_guests WHERE is_active=1") as $r) $urls[] = ["guest.php?id=" . $r['id'], "0.5", "monthly"];
foreach ($__q("SELECT slug FROM films WHERE status='approved' AND slug<>''") as $r) $urls[] = ["film.php?slug=" . $r['slug'], "0.6", "monthly"];
foreach ($__q("SELECT slug FROM academy_courses WHERE is_active=1") as $r) $urls[] = ["academy-course.php?slug=" . $r['slug'], "0.6", "monthly"];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$path, $prio, $freq]) {
    echo "  <url><loc>" . $x("$siteUrl/$path") . "</loc><changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
}
foreach ($conn->query("SELECT slug, updated_at FROM posts WHERE is_published=1") as $row) {
    echo "  <url><loc>" . $x("$siteUrl/post.php?slug=" . $row['slug']) . "</loc><lastmod>" . date('c', strtotime($row['updated_at'])) . "</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>\n";
}
echo '</urlset>';
