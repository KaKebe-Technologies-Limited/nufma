<?php
$adminTitle = "Form options & categories";
$adminNav = "lists";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";

$lists = [
    'list_contact_topics'      => ['Contact form — "My enquiry is about"', 'Choices on the Contact page. Each entry also becomes a topic you can filter by in Enquiries.'],
    'list_org_types'           => ['Partnership form — organisation types', 'Choices for "Organisation type".'],
    'list_partner_interests'   => ['Partnership form — areas of interest', 'Choices for "Area of interest".'],
    'list_network_roles'       => ['Industry Network form — professional roles', 'Choices for "Professional role".'],
    'list_team_categories'     => ['Leadership — groups (Board, Management …)', 'Used to group people on the About page and to filter the Leadership list in the admin.'],
    'list_partner_categories'  => ['Partners — categories', 'Used to group the partner wall and to filter the Partners list in the admin.'],
    'list_gallery_categories'  => ['Gallery & videos — categories', 'Albums for uploaded photographs and videos; they become filter tabs on the public Gallery page.'],
    'list_news_categories'     => ['News & Stories — categories', 'Used for story categories, the filter bar on News and the category picker when you write a story. Renaming a category does not rename it on existing stories.'],
    'list_resource_categories' => ['Resource Library — categories', 'Used when adding resources and for the filter bar on the Resources page.'],
];
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($lists as $k => $_) {
        if (($_POST['action'] ?? '') === 'reset') $v = '';
        else {
            $items = array_values(array_unique(array_filter(array_map(fn($x) => mb_substr(trim($x), 0, 120), preg_split('/\R/', $_POST[$k] ?? '')))));
            if (!$items) { $err = "Each list needs at least one entry (" . $lists[$k][0] . ")."; break; }
            $v = implode("\n", $items);
        }
        $s->bind_param("ss", $k, $v); $s->execute();
    }
    if (!$err) $msg = ($_POST['action'] ?? '') === 'reset' ? "Lists restored to their defaults." : "Lists saved.";
}
$cur = [];
$r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'list\\_%'");
while ($x = $r->fetch_assoc()) $cur[$x['setting_key']] = $x['setting_value'];
$defaults = nufa_site_settings($conn);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
<p class="admin-help" style="margin-bottom:16px">One entry per line. These lists feed the drop-downs and category bars across the website.</p>
<form method="post" class="admin-form">
  <?php foreach ($lists as $k => [$title, $help]): $val = trim($cur[$k] ?? '') !== '' ? $cur[$k] : $defaults[$k]; ?>
  <div class="admin-card">
    <div class="admin-card-head"><h2><?php echo htmlspecialchars($title); ?></h2></div>
    <div class="field"><textarea name="<?php echo $k; ?>" style="min-height:150px"><?php echo htmlspecialchars($val); ?></textarea><span class="admin-help"><?php echo htmlspecialchars($help); ?></span></div>
  </div>
  <?php endforeach; ?>
  <button class="admin-btn admin-btn-brand">Save lists</button>
</form>
<form method="post" style="margin-top:12px" onsubmit="return confirm('Restore every list to its default entries?')"><input type="hidden" name="action" value="reset"><button class="admin-btn admin-btn-line admin-btn-sm">Restore defaults</button></form>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
