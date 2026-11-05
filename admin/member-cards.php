<?php
$adminTitle = "Membership cards";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/member-card.php";
mem_card_ensure($conn);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$tab = ($_GET['tab'] ?? 'ready') === 'issue' ? 'issue' : 'ready';
$q = trim($_GET['q'] ?? ''); $pg = max(1, (int)($_GET['p'] ?? 1)); $per = 12;
$flash = ''; $flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
        if (!empty($_POST['all'])) $ids = array_map('intval', array_column($conn->query("SELECT id FROM members WHERE reg_no IS NULL OR reg_no='' OR card_issued_at IS NULL ORDER BY created_at LIMIT 2000")->fetch_all(MYSQLI_ASSOC), 'id'));
        $n = 0; foreach ($ids as $i) { mem_card_issue($conn, $i, !empty($_POST['notify'])); $n++; }
        $flash = $n ? "Issued $n card(s) — membership IDs assigned where missing." : 'Nobody was selected.'; if (!$n) $flashType = 'warn';
    }
}

/* ---- print sheet: ?sheet=1&ids[]=..  (or &all=1 = every ready card) ---- */
if (isset($_GET['sheet'])) {
    $ids = array_values(array_filter(array_map('intval', (array)($_GET['ids'] ?? []))));
    $sql = "SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE m.status='approved' AND m.reg_no<>''" . (empty($_GET['all']) ? ($ids ? " AND m.id IN (" . implode(',', $ids) . ")" : " AND 0") : "") . " ORDER BY m.full_name LIMIT 200";
    $cards = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    $side = in_array($_GET['side'] ?? '', ['front', 'back'], true) ? $_GET['side'] : 'both';
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Membership cards — print</title>
<link rel="stylesheet" href="../assets/css/member-card.css"><style>body{margin:0;font-family:system-ui,sans-serif;background:#f2f2f2}.bar{padding:12px 16px;background:#111;color:#fff;display:flex;gap:12px;align-items:center;flex-wrap:wrap}.bar a,.bar button{color:#fff;background:#D50000;border:0;border-radius:8px;padding:9px 16px;font:600 14px system-ui;text-decoration:none;cursor:pointer}.bar a.l{background:transparent;border:1px solid #666}.idc-sheet{background:#fff;margin:12px auto;max-width:210mm}</style></head><body>
<div class="bar no-print"><b><?php echo count($cards); ?> card(s)</b><button onclick="window.print()">Print / Save as PDF</button><a class="l" href="?sheet=1&amp;side=front&amp;<?php echo $e(http_build_query(['all' => $_GET['all'] ?? '', 'ids' => $ids])); ?>">Fronts only</a><a class="l" href="?sheet=1&amp;side=back&amp;<?php echo $e(http_build_query(['all' => $_GET['all'] ?? '', 'ids' => $ids])); ?>">Backs only</a><a class="l" href="?sheet=1&amp;<?php echo $e(http_build_query(['all' => $_GET['all'] ?? '', 'ids' => $ids])); ?>">Both sides</a><a class="l" href="member-cards.php">Back</a><span style="color:#bbb;font-size:13px">Print at 100% (no scaling). Cards are credit-card size, 85.6 × 54 mm.</span></div>
<div class="idc-sheet"><?php foreach ($cards as $c) echo mem_card_html($conn, $c, ['prefix' => '../', 'side' => $side]); ?></div>
</body></html><?php exit;
}

$where = $tab === 'ready' ? "m.status='approved' AND m.reg_no IS NOT NULL AND m.reg_no<>''" : "(m.reg_no IS NULL OR m.reg_no='' OR m.card_issued_at IS NULL)";
$types = ''; $params = [];
if ($q !== '') { $where .= " AND (m.full_name LIKE ? OR m.reg_no LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)"; $types = 'ssss'; $l = "%$q%"; $params = [$l, $l, $l, $l]; }
$cnt = $conn->prepare("SELECT COUNT(*) FROM members m WHERE $where"); if ($params) $cnt->bind_param($types, ...$params); $cnt->execute();
$total = (int)$cnt->get_result()->fetch_row()[0]; $pages = max(1, (int)ceil($total / $per)); $pg = min($pg, $pages);
$st = $conn->prepare("SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE $where ORDER BY m.full_name LIMIT $per OFFSET " . (($pg - 1) * $per));
if ($params) $st->bind_param($types, ...$params); $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$nReady = (int)$conn->query("SELECT COUNT(*) FROM members WHERE status='approved' AND reg_no IS NOT NULL AND reg_no<>''")->fetch_row()[0];
$nIssue = (int)$conn->query("SELECT COUNT(*) FROM members WHERE reg_no IS NULL OR reg_no='' OR card_issued_at IS NULL")->fetch_row()[0];
include __DIR__ . "/includes/admin-header.php";
?>
<link rel="stylesheet" href="../assets/css/member-card.css">
<div class="mem-tabs"><a href="member-dashboard.php">Dashboard</a><a href="members.php">Applications</a><a href="member-add.php">Register &amp; import</a><a href="member-cards.php" class="active">Cards</a><a href="directory.php">Directory</a><a href="guilds.php">Guilds</a><a href="membership-settings.php">Membership settings</a></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $flash; ?></div><?php endif; ?>
<div class="mem-tabs"><a href="member-cards.php?tab=ready" class="<?php echo $tab === 'ready' ? 'active' : ''; ?>">Ready cards (<?php echo $nReady; ?>)</a><a href="member-cards.php?tab=issue" class="<?php echo $tab === 'issue' ? 'active' : ''; ?>">Awaiting issue (<?php echo $nIssue; ?>)</a></div>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo $tab === 'ready' ? 'Ready membership cards' : 'Members awaiting a card'; ?> (<?php echo $total; ?>)</h2>
    <?php if ($tab === 'ready' && $nReady): ?><a class="admin-btn admin-btn-line admin-btn-sm" href="member-cards.php?sheet=1&amp;all=1" target="_blank">Print all ready cards</a><?php endif; ?></div>
  <form method="get" class="mem-filter"><input type="hidden" name="tab" value="<?php echo $tab; ?>"><input type="search" name="q" placeholder="Search name, ID, email or phone" value="<?php echo $e($q); ?>"><button class="admin-btn admin-btn-brand">Search</button></form>

  <?php if (!$rows): ?><p style="color:var(--text-muted);padding:16px 0"><?php echo $tab === 'ready' ? 'No ready cards yet. Issue cards from the “Awaiting issue” tab.' : 'Every member already has a card.'; ?></p>
  <?php elseif ($tab === 'ready'): ?>
  <form method="get" target="_blank"><input type="hidden" name="sheet" value="1">
    <p style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><button class="admin-btn admin-btn-brand admin-btn-sm">Print selected cards</button><label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" id="cAll"> Select all on this page</label></p>
    <div class="idc-set" style="justify-content:flex-start;gap:26px">
    <?php foreach ($rows as $r): ?>
      <div style="width:290px"><label style="display:flex;gap:6px;align-items:center;margin-bottom:6px;font-weight:600;font-size:.85rem"><input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>" class="cRow"> <?php echo $e($r['full_name']); ?></label>
        <div class="idc-sm-wrap"><?php echo str_replace('class="idc idc-front', 'style="--w:290px" class="idc idc-front', mem_card_html($conn, $r, ['prefix' => '../', 'side' => 'front'])); ?></div>
        <p style="margin:8px 0 0;display:flex;gap:6px;flex-wrap:wrap"><a class="admin-btn admin-btn-brand admin-btn-sm" href="member-card.php?id=<?php echo $r['id']; ?>">Preview card</a><a class="admin-btn admin-btn-line admin-btn-sm" href="member-cards.php?sheet=1&amp;ids[]=<?php echo $r['id']; ?>" target="_blank">Print</a><?php echo !empty($r['card_issued_at']) ? '<span class="admin-badge">Issued ' . date('d M Y', strtotime($r['card_issued_at'])) . '</span>' : '<span class="admin-badge gray">Not issued</span>'; ?></p></div>
    <?php endforeach; ?></div>
  </form>
  <script>document.getElementById('cAll')?.addEventListener('change',function(){var v=this.checked;document.querySelectorAll('.cRow').forEach(function(c){c.checked=v})});</script>
  <?php else: ?>
  <form method="post"><?php echo mem_csrf_field(); ?>
    <p style="display:flex;flex-wrap:wrap;gap:10px;align-items:center"><button class="admin-btn admin-btn-brand">Issue cards to selected</button><button class="admin-btn admin-btn-line" name="all" value="1" data-confirm="Issue cards (and membership IDs) to all <?php echo $nIssue; ?> members?">Issue to all <?php echo $nIssue; ?></button>
    <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="notify" value="1"> Email each member their card link</label>
    <label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" id="cAll"> Select all on this page</label></p>
    <div style="overflow-x:auto"><table class="admin-table"><thead><tr><th></th><th>Name</th><th>Contact</th><th>Guild</th><th>Status</th><th>Membership ID</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>" class="cRow"></td><td><b><?php echo $e($r['full_name']); ?></b></td><td><?php echo $e(trim($r['email'] . ' ' . $r['phone'])); ?></td><td><?php echo $e($r['guild_name'] ?? '—'); ?></td><td><?php echo mem_status_badge($r['status']); ?></td><td><?php echo $r['reg_no'] ? $e($r['reg_no']) : '—'; ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="member-card.php?id=<?php echo $r['id']; ?>">Preview</a></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </form>
  <script>document.getElementById('cAll')?.addEventListener('change',function(){var v=this.checked;document.querySelectorAll('.cRow').forEach(function(c){c.checked=v})});</script>
  <?php endif; ?>
  <?php if ($pages > 1): ?><div class="mem-tabs" style="margin-top:14px"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="member-cards.php?tab=<?php echo $tab; ?>&amp;q=<?php echo urlencode($q); ?>&amp;p=<?php echo $i; ?>" class="<?php echo $i === $pg ? 'active' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?></div><?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
