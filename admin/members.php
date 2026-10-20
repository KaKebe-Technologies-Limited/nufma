<?php
$adminTitle = "Membership";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require __DIR__ . "/../includes/membership.php";

$status = $_GET['status'] ?? '';
$guild  = (int)($_GET['guild'] ?? 0);
$q      = trim($_GET['q'] ?? '');

$where = []; $types = ''; $params = [];
if (in_array($status, ['pending', 'approved', 'rejected'], true)) { $where[] = "m.status=?"; $types .= 's'; $params[] = $status; }
if ($guild) { $where[] = "m.guild_id=?"; $types .= 'i'; $params[] = $guild; }
if ($q !== '') {
    $where[] = "(m.full_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ? OR m.reg_no LIKE ?)";
    $types .= 'ssss'; $like = "%$q%"; array_push($params, $like, $like, $like, $like);
}
$sql = "SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id"
     . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY (m.status='pending') DESC, m.created_at DESC LIMIT 500";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (nufa_export_format() !== '') {
$__ex = [];
    $__ex[] = ['Reg No', 'Name', 'Email', 'Phone', 'Gender', 'DOB', 'District', 'Guild', 'Status', 'Valid from', 'Valid until', 'Applied'];
    foreach ($rows as $r) $__ex[] = [$r['reg_no'], $r['full_name'], $r['email'], $r['phone'], $r['gender'], $r['dob'], $r['district'], $r['guild_name'], $r['status'], $r['valid_from'], $r['valid_until'], $r['created_at']];
    nufa_export_send(nufa_export_format(), 'NUFA members', array_shift($__ex), $__ex, 'nufa-members');
}

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$cr = $conn->query("SELECT status, COUNT(*) c FROM members GROUP BY status");
while ($c = $cr->fetch_assoc()) $counts[$c['status']] = (int)$c['c'];
$expiring = (int)$conn->query("SELECT COUNT(*) c FROM members WHERE status='approved' AND valid_until < CURDATE()")->fetch_assoc()['c'];
$guildList = $conn->query("SELECT id, name FROM guilds ORDER BY sort_order, name")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . "/includes/admin-header.php";
$qs = fn($extra) => '?' . http_build_query(array_merge(['q' => $q, 'guild' => $guild ?: '', 'status' => $status], $extra));
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Member deleted.</div><?php endif; ?>

<div class="mem-tabs">
  <a href="members.php" class="active">Applications</a>
  <a href="guilds.php">Guilds</a>
  <a href="membership-settings.php">Membership settings</a>
</div>

<div class="admin-grid" style="grid-template-columns:repeat(4,1fr)">
  <a class="admin-stat" href="members.php?status=pending"><b><?php echo $counts['pending']; ?></b><span>Pending review</span></a>
  <a class="admin-stat" href="members.php?status=approved"><b><?php echo $counts['approved']; ?></b><span>Approved members</span></a>
  <a class="admin-stat" href="members.php?status=rejected"><b><?php echo $counts['rejected']; ?></b><span>Rejected</span></a>
  <div class="admin-stat"><b><?php echo $expiring; ?></b><span>Memberships expired</span></div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Members <?php echo $status ? '— ' . ucfirst($status) : ''; ?></h2>
    <?php echo nufa_export_menu($qs([])); ?>
  </div>
  <form method="get" class="mem-filter">
    <input type="search" name="q" placeholder="Search name, email, phone or reg. no." value="<?php echo htmlspecialchars($q); ?>">
    <select name="status">
      <option value="">All statuses</option>
      <?php foreach (['pending', 'approved', 'rejected'] as $s) echo '<option value="' . $s . '"' . ($status === $s ? ' selected' : '') . '>' . ucfirst($s) . '</option>'; ?>
    </select>
    <select name="guild">
      <option value="">All guilds</option>
      <?php foreach ($guildList as $g) echo '<option value="' . $g['id'] . '"' . ($guild === (int)$g['id'] ? ' selected' : '') . '>' . htmlspecialchars($g['name']) . '</option>'; ?>
    </select>
    <button class="admin-btn admin-btn-brand">Filter</button>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);padding:20px 0">No applications found.</p>
  <?php else: ?>
  <div style="overflow-x:auto">
  <table class="admin-table">
    <thead><tr><th>Applicant</th><th>Guild</th><th>Reg. no.</th><th>Status</th><th>Valid until</th><th>Applied</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><b><?php echo htmlspecialchars($r['full_name']); ?></b><br><span style="color:var(--text-muted);font-size:.8rem"><?php echo htmlspecialchars($r['email']); ?> · <?php echo htmlspecialchars($r['phone']); ?></span></td>
        <td><?php echo htmlspecialchars($r['guild_name'] ?? '—'); ?></td>
        <td><?php echo $r['reg_no'] ? htmlspecialchars($r['reg_no']) : '—'; ?></td>
        <td><?php echo mem_status_badge($r['status']); ?></td>
        <td><?php echo $r['valid_until'] ? date('d M Y', strtotime($r['valid_until'])) : '—'; ?></td>
        <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
        <td><a class="admin-btn admin-btn-line admin-btn-sm" href="member-view.php?id=<?php echo $r['id']; ?>"><?php echo $r['status'] === 'pending' ? 'Review' : 'View'; ?></a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
