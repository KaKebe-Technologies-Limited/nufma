<?php
$adminTitle = "Membership renewals";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/renewals.php";
mem_renew_install($conn);
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$S = mem_settings($conn); $F = mem_fees($S);
$flash = ''; $flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $act = $_POST['action'] ?? ''; $rid = (int)($_POST['id'] ?? 0);
        $r = $conn->query("SELECT * FROM member_renewals WHERE id=$rid")->fetch_assoc();
        $m = $r ? $conn->query("SELECT * FROM members WHERE id=" . (int)$r['member_id'])->fetch_assoc() : null;
        if ($r && $m && $act === 'approve') {
            $until = mem_renew_approve($conn, $rid, trim($_POST['note'] ?? ''));
            if ($until) {
                $link = mem_card_link($m);
                $ok = mem_send($conn, (int)$m['id'], 'renew-approved', $m['email'], "Your NUFA membership has been renewed",
                    "Hello {$m['full_name']},\n\nThank you — your subscription payment is confirmed and your NUFA membership has been renewed.\n\nMembership ID: {$m['reg_no']}\nValid until: " . date('j F Y', strtotime($until)) . "\nRenewal reference: {$r['ref']}\n\nYour membership card has been updated with the new validity:\n$link\n\n— NUFA Membership Team", $link);
                $flash = "Renewal approved — {$m['full_name']} is valid until " . date('j M Y', strtotime($until)) . ($ok ? '. Email sent.' : '. (The email could not be sent from this server; it is saved in the email log.)');
            } else { $flash = 'This renewal was already approved.'; $flashType = 'warn'; }
        } elseif ($r && $m && $act === 'reject') {
            $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 500);
            if ($reason === '') { $flash = 'Enter a reason for declining the renewal.'; $flashType = 'warn'; }
            else {
                $u = $conn->prepare("UPDATE member_renewals SET status='rejected', reject_reason=? WHERE id=?"); $u->bind_param("si", $reason, $rid); $u->execute();
                mem_send($conn, (int)$m['id'], 'renew-rejected', $m['email'], "About your NUFA membership renewal", "Hello {$m['full_name']},\n\nWe could not confirm your renewal ({$r['ref']}).\n\nReason: $reason\n\nPlease contact us or submit the renewal again.\n\n— NUFA Membership Team");
                $flash = 'Renewal declined and the member was notified.';
            }
        } elseif ($r && $act === 'delete') { if ($r['payment_proof'] && defined('MEM_STORAGE_DIR')) @unlink(MEM_STORAGE_DIR . basename($r['payment_proof'])); $conn->query("DELETE FROM member_renewals WHERE id=$rid"); $flash = 'Renewal record deleted.'; }
    }
}

$status = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : 'pending';
$cnt = ['pending' => 0, 'approved' => 0, 'rejected' => 0]; foreach ($conn->query("SELECT status, COUNT(*) c FROM member_renewals GROUP BY status")->fetch_all(MYSQLI_ASSOC) as $c) $cnt[$c['status']] = (int)$c['c'];
$rows = $conn->query("SELECT r.*, m.reg_no, m.valid_until AS cur_until, m.valid_from AS cur_from FROM member_renewals r LEFT JOIN members m ON m.id=r.member_id WHERE r.status='$status' ORDER BY r.created_at DESC LIMIT 300")->fetch_all(MYSQLI_ASSOC);

if (isset($_GET['fmt']) && isset(NUFA_EXPORT_FORMATS[$_GET['fmt']])) {
    $ex = []; foreach ($rows as $r) $ex[] = [$r['ref'], $r['name'], $r['reg_no'], $r['email'], $r['phone'], $r['amount'], $r['payment_method'], $r['payment_ref'], $r['payment_date'], $r['status'], $r['new_valid_until'], $r['created_at']];
    nufa_export_send($_GET['fmt'], 'NUFA membership renewals (' . $status . ')', ['Reference', 'Member', 'Membership ID', 'Email', 'Phone', 'Amount', 'Method', 'Payment ref', 'Paid on', 'Status', 'New valid until', 'Requested'], $ex, 'nufa-renewals', count($ex) . ' renewal(s)');
}
include __DIR__ . "/includes/admin-header.php";
?>
<div class="mem-tabs"><a href="member-dashboard.php">Dashboard</a><a href="members.php">Applications</a><a href="member-renewals.php" class="active">Renewals<?php echo $cnt['pending'] ? ' (' . $cnt['pending'] . ')' : ''; ?></a><a href="member-add.php">Register &amp; import</a><a href="member-cards.php">Cards</a><a href="directory.php">Directory</a><a href="guilds.php">Guilds</a><a href="membership-settings.php">Membership settings</a></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $e($flash); ?></div><?php endif; ?>
<div class="admin-grid" style="grid-template-columns:repeat(4,1fr)">
  <a class="admin-stat" href="?status=pending"><b><?php echo $cnt['pending']; ?></b><span>Waiting for review</span></a>
  <a class="admin-stat" href="?status=approved"><b><?php echo $cnt['approved']; ?></b><span>Approved</span></a>
  <a class="admin-stat" href="?status=rejected"><b><?php echo $cnt['rejected']; ?></b><span>Declined</span></a>
  <div class="admin-stat"><b style="font-size:1.05rem"><?php echo $e(mem_money($F['renew'], $F['cur'])); ?></b><span>Renewal fee · new members pay <?php echo $e(mem_money($F['new'], $F['cur'])); ?></span></div>
</div>
<div class="admin-card">
  <div class="admin-card-head"><h2>Renewals — <?php echo $e(ucfirst($status)); ?> (<?php echo count($rows); ?>)</h2><span><?php echo nufa_export_menu('member-renewals.php?status=' . $status); ?></span></div>
  <p class="admin-help">Members renew on the public Membership page (“Renew my membership”). Approving a renewal extends the validity by <b><?php echo (int)$S['validity_months']; ?> months</b> from the current end date (or from today if it has already lapsed), updates the member’s card and emails them. Fees are set in <a href="membership-settings.php">Membership settings</a>.</p>
  <?php if (!$rows): ?><p style="color:var(--text-muted);padding:14px 0">No <?php echo $e($status); ?> renewals.</p><?php else: ?>
  <div style="overflow-x:auto"><table class="admin-table" data-noexport><thead><tr><th>Reference</th><th>Member</th><th>Payment</th><th>Validity</th><th>Requested</th><th>Action</th></tr></thead><tbody>
  <?php foreach ($rows as $r): $newU = $r['status'] === 'pending' ? mem_renew_new_until(['valid_until' => $r['cur_until']], (int)$S['validity_months']) : $r['new_valid_until']; ?>
    <tr>
      <td><b><?php echo $e($r['ref']); ?></b></td>
      <td><a href="member-view.php?id=<?php echo (int)$r['member_id']; ?>"><b><?php echo $e($r['name']); ?></b></a><br><span style="color:#777;font-size:.82rem"><?php echo $e($r['reg_no']); ?> · <?php echo $e($r['phone']); ?></span></td>
      <td><?php echo $e($r['amount']); ?><br><span style="color:#777;font-size:.82rem"><?php echo $e($r['payment_method'] ?: '—'); ?> · ref <?php echo $e($r['payment_ref'] ?: '—'); ?><?php echo $r['payment_date'] ? ' · ' . date('d M Y', strtotime($r['payment_date'])) : ''; ?></span><?php if ($r['payment_proof']): ?><br><a href="renewal-file.php?id=<?php echo (int)$r['id']; ?>" target="_blank">View proof</a><?php endif; ?></td>
      <td><?php echo $r['cur_until'] ? 'Now: ' . date('d M Y', strtotime($r['cur_until'])) : '—'; ?><br><b><?php echo $r['status'] === 'rejected' ? '' : ($r['status'] === 'pending' ? 'Will become: ' : 'New: ') . date('d M Y', strtotime($newU)); ?></b></td>
      <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
      <td style="min-width:230px">
        <?php if ($r['status'] === 'pending'): ?>
        <form method="post" style="margin-bottom:6px"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><input type="hidden" name="action" value="approve"><button class="admin-btn admin-btn-brand admin-btn-sm" data-confirm="Confirm the payment and extend this membership?">Confirm payment &amp; renew</button></form>
        <form method="post" style="display:flex;gap:6px"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><input type="hidden" name="action" value="reject"><input name="reason" placeholder="Reason for declining" style="flex:1;min-width:0"><button class="admin-btn admin-btn-line admin-btn-sm">Decline</button></form>
        <?php else: echo $r['status'] === 'rejected' ? '<span class="admin-badge red">Declined</span><br><span style="font-size:.82rem;color:#777">' . $e($r['reject_reason']) . '</span>' : '<span class="admin-badge">Approved ' . date('d M Y', strtotime($r['approved_at'])) . '</span>'; ?>
        <form method="post" style="margin-top:6px"><?php echo mem_csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><input type="hidden" name="action" value="delete"><button class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Delete this renewal record?">Delete</button></form>
        <?php endif; ?></td>
    </tr>
  <?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
