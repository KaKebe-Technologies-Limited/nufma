<?php
$adminTitle = "Newsletter subscribers";
$adminNav = "newsletters";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/newsletter.php";
nl_install($conn);

if (isset($_GET['export'])) {
    $rows = $conn->query("SELECT email, name, source, unsubscribed, created_at FROM subscribers ORDER BY created_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="nufa-subscribers.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email', 'Name', 'Source', 'Status', 'Subscribed At']);
    while ($r = $rows->fetch_assoc()) fputcsv($out, [$r['email'], $r['name'], $r['source'], $r['unsubscribed'] ? 'unsubscribed' : 'active', $r['created_at']]);
    fclose($out);
    exit;
}
if (isset($_GET['delete'])) { $conn->query("DELETE FROM subscribers WHERE id=" . (int)$_GET['delete']); header("Location: subscribers.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE subscribers SET unsubscribed = 1 - unsubscribed WHERE id=" . (int)$_GET['toggle']); header("Location: subscribers.php"); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $added = 0; $bad = 0;
    foreach (preg_split('/[\s,;]+/', (string)($_POST['emails'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $em) { [$ok] = nl_subscribe($conn, $em, '', 'admin'); $ok ? $added++ : $bad++; }
    $msg = "$added address" . ($added === 1 ? '' : 'es') . " added" . ($bad ? ", $bad skipped (not valid emails)" : '') . ". Only add people who have agreed to receive NUFA news.";
}
$q = trim($_GET['q'] ?? '');
$where = $q !== '' ? "WHERE email LIKE '%" . $conn->real_escape_string($q) . "%' OR name LIKE '%" . $conn->real_escape_string($q) . "%'" : '';
$subs = $conn->query("SELECT * FROM subscribers $where ORDER BY created_at DESC LIMIT 500")->fetch_all(MYSQLI_ASSOC);
$active = (int)$conn->query("SELECT COUNT(*) FROM subscribers WHERE unsubscribed=0")->fetch_row()[0];
$total = (int)$conn->query("SELECT COUNT(*) FROM subscribers")->fetch_row()[0];
$e = fn($v) => htmlspecialchars((string)$v);
include __DIR__ . "/includes/admin-header.php";
$newsTab = 'subscribers'; include __DIR__ . "/includes/news-tabs.php";
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Subscriber removed.</div><?php endif; ?>
<?php if ($msg): ?><div class="admin-alert ok"><?php echo $e($msg); ?></div><?php endif; ?>

<div class="stat-row"><div><b><?php echo $active; ?></b><span>Active subscribers</span></div><div><b><?php echo $total - $active; ?></b><span>Unsubscribed</span></div><div><b><?php echo $total; ?></b><span>All addresses</span></div></div>

<div class="admin-card"><div class="admin-card-head"><h2>Add subscribers</h2><span class="admin-help">Paste one or many email addresses (separated by spaces, commas or new lines).</span></div>
  <form method="post" class="admin-form"><div class="field"><textarea name="emails" style="min-height:80px" placeholder="name@example.com, another@example.com"></textarea></div><button class="admin-btn admin-btn-brand">Add to the list</button></form></div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Mailing list</h2><span><form method="get" style="display:inline-flex;gap:8px"><input name="q" value="<?php echo $e($q); ?>" placeholder="Search…" style="padding:8px 12px;border:1px solid #e4e0d6;border-radius:8px"><button class="admin-btn admin-btn-line admin-btn-sm">Search</button></form> <a href="subscribers.php?export=1" class="admin-btn admin-btn-line">Export CSV</a></span></div>
  <table class="admin-table">
    <thead><tr><th>Email</th><th>Name</th><th>Source</th><th>Status</th><th>Joined</th><th></th></tr></thead>
    <tbody>
      <?php if (!$subs): ?><tr><td colspan="6" style="color:var(--text-muted)">No subscribers yet. Visitors can join from the Newsletter page.</td></tr><?php endif; ?>
      <?php foreach ($subs as $s): ?>
      <tr<?php echo $s['unsubscribed'] ? ' style="opacity:.55"' : ''; ?>>
        <td><?php echo $e($s['email']); ?></td><td><?php echo $e($s['name']); ?></td>
        <td><span class="admin-badge gray"><?php echo $e($s['source']); ?></span></td>
        <td><?php echo $s['unsubscribed'] ? '<span class="admin-badge gray">Unsubscribed</span>' : '<span class="admin-badge">Active</span>'; ?></td>
        <td><?php echo date("d M Y", strtotime($s['created_at'])); ?></td>
        <td><div class="admin-row-actions"><a href="subscribers.php?toggle=<?php echo $s['id']; ?>" class="admin-btn admin-btn-line admin-btn-sm"><?php echo $s['unsubscribed'] ? 'Resubscribe' : 'Pause'; ?></a>
          <a href="subscribers.php?delete=<?php echo $s['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this subscriber?">Delete</a></div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
