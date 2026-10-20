<?php
$adminTitle = "Support requests";
$adminNav = "support";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/support.php";

$kinds = sup_kinds();
$kind = isset($kinds[$_GET['kind'] ?? '']) ? $_GET['kind'] : '';
$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'completed', 'rejected'], true) ? $_GET['status'] : '';
$progId = (int)($_GET['programme'] ?? 0);
$q = trim($_GET['q'] ?? '');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("SELECT payment_proof FROM engagements WHERE id=$id")->fetch_assoc();
    if ($row && $row['payment_proof']) @unlink(SUP_STORAGE_DIR . basename($row['payment_proof']));
    $conn->query("DELETE FROM engagements WHERE id=$id");
    header("Location: support-requests.php?deleted=1"); exit;
}

$where = []; $tp = ''; $pr = [];
if ($kind)   { $where[] = "e.kind=?";   $tp .= 's'; $pr[] = $kind; }
if ($status) { $where[] = "e.status=?"; $tp .= 's'; $pr[] = $status; }
if ($progId) { $where[] = "e.programme_id=?"; $tp .= 'i'; $pr[] = $progId; }
if ($q !== '') { $where[] = "(e.name LIKE ? OR e.email LIKE ? OR e.organisation LIKE ? OR e.ref_no LIKE ?)"; $tp .= 'ssss'; $l = "%$q%"; array_push($pr, $l, $l, $l, $l); }
$st = $conn->prepare("SELECT e.*, p.title AS programme FROM engagements e LEFT JOIN programmes p ON p.id=e.programme_id" . ($where ? " WHERE " . implode(' AND ', $where) : '') . " ORDER BY (e.status='pending') DESC, e.created_at DESC LIMIT 500");
if ($pr) $st->bind_param($tp, ...$pr);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

if (nufa_export_format() !== '') {
$__ex = [];
    $__ex[] = ['Reference', 'Received', 'Type', 'Programme', 'Name', 'Organisation', 'Email', 'Phone', 'Amount', 'Currency', 'Frequency', 'Method', 'Payment ref', 'Status', 'Message', 'Details'];
    foreach ($rows as $r) $__ex[] = [$r['ref_no'], $r['created_at'], $r['kind'], $r['programme'] ?? 'General', $r['name'], $r['organisation'], $r['email'], $r['phone'], $r['amount'], $r['currency'], $r['frequency'], $r['method'], $r['payment_ref'], $r['status'], $r['message'], $r['details']];
    nufa_export_send(nufa_export_format(), 'Support requests', array_shift($__ex), $__ex, 'nufa-support-requests');
}

$counts = ['pending' => 0, 'approved' => 0, 'completed' => 0, 'rejected' => 0];
$cr = $conn->query("SELECT status, COUNT(*) c FROM engagements GROUP BY status");
while ($c = $cr->fetch_assoc()) $counts[$c['status']] = (int)$c['c'];
$raised = $conn->query("SELECT currency, SUM(amount) s FROM engagements WHERE status='completed' AND kind IN ('donate','sponsor') AND amount IS NOT NULL GROUP BY currency")->fetch_all(MYSQLI_ASSOC);
$progList = $conn->query("SELECT id, title FROM programmes ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Request deleted.</div><?php endif; ?>
<div class="mem-tabs"><a href="support-requests.php" class="active">Requests</a><a href="support-setup.php">Payment &amp; giving setup</a></div>

<div class="admin-grid" style="grid-template-columns:repeat(4,1fr)">
  <a class="admin-stat" href="support-requests.php?status=pending"><b><?php echo $counts['pending']; ?></b><span>Pending review</span></a>
  <a class="admin-stat" href="support-requests.php?status=approved"><b><?php echo $counts['approved']; ?></b><span>Approved — awaiting payment / follow-up</span></a>
  <a class="admin-stat" href="support-requests.php?status=completed"><b><?php echo $counts['completed']; ?></b><span>Completed</span></a>
  <div class="admin-stat"><b style="font-size:1.1rem"><?php echo $raised ? implode('<br>', array_map(fn($r) => htmlspecialchars(sup_money($r['s'], $r['currency'])), $raised)) : '—'; ?></b><span>Confirmed donations &amp; sponsorship</span></div>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>All requests</h2><?php echo nufa_export_menu('support-requests.php?' . http_build_query(array_filter(['kind' => $kind, 'status' => $status, 'programme' => $progId ?: '', 'q' => $q]))); ?></div>
  <form method="get" class="mem-filter">
    <input type="search" name="q" placeholder="Search name, organisation, email or reference" value="<?php echo htmlspecialchars($q); ?>">
    <select name="kind"><option value="">All types</option><?php foreach ($kinds as $k => $kd) echo '<option value="' . $k . '"' . ($kind === $k ? ' selected' : '') . '>' . $kd['label'] . '</option>'; ?></select>
    <select name="status"><option value="">Any status</option><?php foreach (['pending', 'approved', 'completed', 'rejected'] as $s) echo '<option value="' . $s . '"' . ($status === $s ? ' selected' : '') . '>' . ucfirst($s) . '</option>'; ?></select>
    <select name="programme"><option value="">All programmes</option><?php foreach ($progList as $p) echo '<option value="' . $p['id'] . '"' . ($progId === (int)$p['id'] ? ' selected' : '') . '>' . htmlspecialchars($p['title']) . '</option>'; ?></select>
    <button class="admin-btn admin-btn-brand">Filter</button>
  </form>
  <?php if (!$rows): ?><p class="admin-help" style="padding:20px 0">No requests match.</p><?php else: ?>
  <div style="overflow-x:auto"><table class="admin-table">
    <thead><tr><th>Reference</th><th>From</th><th>Type</th><th>Programme</th><th>Amount</th><th>Status</th><th>Received</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($r['ref_no']); ?></b></td>
        <td><b><?php echo htmlspecialchars($r['name']); ?></b><?php echo $r['organisation'] ? '<br><span class="admin-help">' . htmlspecialchars($r['organisation']) . '</span>' : ''; ?></td>
        <td><span class="admin-badge gray"><?php echo htmlspecialchars($kinds[$r['kind']]['label'] ?? $r['kind']); ?></span></td>
        <td><?php echo htmlspecialchars($r['programme'] ?? 'General'); ?></td>
        <td><?php echo $r['amount'] !== null ? htmlspecialchars(sup_money($r['amount'], $r['currency'])) . ($r['payment_ref'] ? ' <span class="admin-help">· paid?</span>' : '') : '—'; ?></td>
        <td><?php echo sup_status_badge($r['status']); ?></td>
        <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
        <td style="white-space:nowrap"><a class="admin-btn admin-btn-line admin-btn-sm" href="support-view.php?id=<?php echo $r['id']; ?>"><?php echo $r['status'] === 'pending' ? 'Review' : 'Open'; ?></a>
          <a class="admin-btn admin-btn-danger admin-btn-sm" href="support-requests.php?delete=<?php echo $r['id']; ?>" data-confirm="Delete this request permanently?">Delete</a></td>
      </tr>
    <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
