<?php
$adminTitle = "Enquiries";
$adminNav = "enquiries";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/schema.php";

$types = ['contact' => 'Contact', 'partner' => 'Partnership', 'sacco' => 'SACCO interest', 'network' => 'Industry network', 'tickets' => 'Ticket requests'];
$type = isset($types[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$status = in_array($_GET['status'] ?? '', ['new', 'handled'], true) ? $_GET['status'] : '';
$q = trim($_GET['q'] ?? '');

if (isset($_GET['delete'])) { $conn->query("DELETE FROM enquiries WHERE id=" . (int)$_GET['delete']); header("Location: enquiries.php?deleted=1"); exit; }
if (isset($_GET['toggle'])) { $conn->query("UPDATE enquiries SET status = IF(status='new','handled','new') WHERE id=" . (int)$_GET['toggle']); header("Location: enquiries.php?" . http_build_query(array_filter(['type' => $type, 'status' => $status]))); exit; }

$where = []; $tp = ''; $pr = [];
if ($type)   { $where[] = "type=?";   $tp .= 's'; $pr[] = $type; }
if ($status) { $where[] = "status=?"; $tp .= 's'; $pr[] = $status; }
if ($q !== '') { $where[] = "(name LIKE ? OR email LIKE ? OR organisation LIKE ? OR message LIKE ?)"; $tp .= 'ssss'; $l = "%$q%"; array_push($pr, $l, $l, $l, $l); }
$st = $conn->prepare("SELECT * FROM enquiries" . ($where ? " WHERE " . implode(' AND ', $where) : '') . " ORDER BY (status='new') DESC, created_at DESC LIMIT 500");
if ($pr) $st->bind_param($tp, ...$pr);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

if (nufa_export_format() !== '') {
$__ex = [];
    $__ex[] = ['Received', 'Type', 'Topic', 'Name', 'Organisation', 'Email', 'Phone', 'Status', 'Message', 'Details'];
    foreach ($rows as $r) $__ex[] = [$r['created_at'], $r['type'], $r['topic'], $r['name'], $r['organisation'], $r['email'], $r['phone'], $r['status'], $r['message'], $r['details']];
    nufa_export_send(nufa_export_format(), 'NUFA enquiries', array_shift($__ex), $__ex, 'nufa-enquiries');
}
$counts = array_fill_keys(array_keys($types), 0); $newTotal = 0;
$cr = $conn->query("SELECT type, SUM(status='new') n, COUNT(*) c FROM enquiries GROUP BY type");
while ($c = $cr->fetch_assoc()) { $counts[$c['type']] = (int)$c['c']; $newTotal += (int)$c['n']; }
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Enquiry deleted.</div><?php endif; ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>All enquiries <span class="admin-badge"><?php echo $newTotal; ?> new</span></h2>
    <?php echo nufa_export_menu('enquiries.php?' . http_build_query(array_filter(['type' => $type, 'status' => $status, 'q' => $q]))); ?></div>
  <p class="admin-help" style="margin-bottom:14px">Submissions from the Contact, Partner With Us, NUFA SACCO and Industry Network forms. Personal details are private — never shown on the public site.</p>
  <form method="get" class="mem-filter">
    <input type="search" name="q" placeholder="Search name, email, organisation or message" value="<?php echo htmlspecialchars($q); ?>">
    <select name="type"><option value="">All forms</option><?php foreach ($types as $k => $l) echo '<option value="' . $k . '"' . ($type === $k ? ' selected' : '') . '>' . $l . ' (' . $counts[$k] . ')</option>'; ?></select>
    <select name="status"><option value="">Any status</option><option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option><option value="handled" <?php echo $status === 'handled' ? 'selected' : ''; ?>>Handled</option></select>
    <button class="admin-btn admin-btn-brand">Filter</button>
  </form>
  <?php if (!$rows): ?><p class="admin-help" style="padding:20px 0">No enquiries match.</p><?php else: ?>
  <div style="overflow-x:auto"><table class="admin-table">
    <thead><tr><th>From</th><th>Form</th><th>Topic</th><th>Received</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($r['name']); ?></b><?php if ($r['organisation']): ?> <span class="admin-help">· <?php echo htmlspecialchars($r['organisation']); ?></span><?php endif; ?><br><span class="admin-help"><?php echo htmlspecialchars($r['email']); ?></span></td>
        <td><span class="admin-badge gray"><?php echo htmlspecialchars($types[$r['type']] ?? $r['type']); ?></span></td>
        <td><?php echo htmlspecialchars($r['topic'] ?: '—'); ?></td>
        <td><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></td>
        <td><a href="enquiries.php?toggle=<?php echo $r['id']; ?>" class="admin-badge <?php echo $r['status'] === 'new' ? '' : 'gray'; ?>"><?php echo $r['status'] === 'new' ? 'New' : 'Handled'; ?></a></td>
        <td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="enquiry-view.php?id=<?php echo $r['id']; ?>">Open</a>
          <a class="admin-btn admin-btn-danger admin-btn-sm" href="enquiries.php?delete=<?php echo $r['id']; ?>" data-confirm="Delete this enquiry permanently?">Delete</a></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
