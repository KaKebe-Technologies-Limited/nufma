<?php
$adminTitle = "Trainer applications";
$adminNav = "academy";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/ui.php";
require_once __DIR__ . "/../includes/academy.php";
acad_install($conn);
$e = fn($v) => htmlspecialchars((string)$v);
$modes = ['physical' => 'In person', 'virtual' => 'Online', 'both' => 'In person & online'];

if (isset($_GET['cv'])) {   // stream a private CV
    $r = $conn->query("SELECT cv FROM academy_trainers WHERE id=" . (int)$_GET['cv'])->fetch_assoc(); $p = $r ? acad_storage() . basename($r['cv']) : '';
    if (!$r || $r['cv'] === '' || !is_file($p)) { http_response_code(404); exit("Not found"); }
    header("Content-Type: application/pdf"); header("Content-Length: " . filesize($p)); header("Content-Disposition: inline; filename=\"cv.pdf\""); header("X-Content-Type-Options: nosniff"); readfile($p); exit;
}
if (isset($_GET['delete'])) { $id = (int)$_GET['delete']; $r = $conn->query("SELECT cv FROM academy_trainers WHERE id=$id")->fetch_assoc(); if ($r && $r['cv']) @unlink(acad_storage() . basename($r['cv'])); $conn->query("DELETE FROM academy_trainers WHERE id=$id"); header("Location: academy-trainers.php?deleted=1"); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0); $a = $conn->query("SELECT * FROM academy_trainers WHERE id=$id")->fetch_assoc();
    if ($a) {
        $notes = trim($_POST['notes'] ?? ''); $pub = isset($_POST['is_public']) ? 1 : 0; $act = $_POST['act'] ?? 'save';
        $status = $a['status']; if ($act === 'approve') $status = 'approved'; elseif ($act === 'reject') $status = 'rejected'; elseif ($act === 'pending') $status = 'pending';
        $s = $conn->prepare("UPDATE academy_trainers SET status=?, notes=?, is_public=? WHERE id=?"); $s->bind_param("ssii", $status, $notes, $pub, $id); $s->execute();
        if ($status !== $a['status'] && in_array($status, ['approved', 'rejected'], true) && !empty($_POST['send_email'])) {
            $body = $status === 'approved' ? "Hello " . $a['name'] . ",\n\nGood news — your application to facilitate with NUFA Academy has been approved. Our team will contact you about classes and schedules." . (trim($_POST['message'] ?? '') !== '' ? "\n\n" . trim($_POST['message']) : '')
                : "Hello " . $a['name'] . ",\n\nThank you for applying to facilitate with NUFA Academy. We are not able to take your application forward at this time." . (trim($_POST['message'] ?? '') !== '' ? "\n\n" . trim($_POST['message']) : '');
            mem_send($conn, null, 'academy-trainer-' . $status, $a['email'], "Your NUFA Academy facilitator application (" . $a['ref'] . ")", $body);
        }
        header("Location: academy-trainers.php?view=$id&saved=1"); exit;
    }
}
$view = (int)($_GET['view'] ?? 0); $A = $view ? $conn->query("SELECT * FROM academy_trainers WHERE id=$view")->fetch_assoc() : null;
$st = in_array($_GET['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_GET['status'] : '';
$rows = $conn->query("SELECT * FROM academy_trainers" . ($st ? " WHERE status='$st'" : '') . " ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
include __DIR__ . "/includes/admin-header.php";
echo acad_admin_tabs('academy-trainers.php');
?>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok">Saved.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Application deleted.</div><?php endif; ?>
<?php if ($A): ?>
<div class="admin-card"><div class="admin-card-head"><h2><?php echo $e($A['name']); ?> <span class="admin-badge <?php echo $A['status'] === 'approved' ? '' : 'gray'; ?>"><?php echo $e(ucfirst($A['status'])); ?></span> <small><?php echo $e($A['ref']); ?></small></h2><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-trainers.php">← All applications</a></div>
  <table class="admin-table" data-noexport><tbody>
    <tr><th>Contact</th><td><?php echo $e($A['email']); ?> · <?php echo $e($A['phone']); ?> · <a href="https://wa.me/<?php echo $e(preg_replace('/\D/', '', $A['phone'])); ?>" target="_blank" rel="noopener">WhatsApp</a></td></tr>
    <tr><th>Organisation / base</th><td><?php echo $e($A['organisation'] ?: '—'); ?> · <?php echo $e($A['location'] ?: '—'); ?></td></tr>
    <tr><th>Can teach</th><td><?php echo $e($A['expertise']); ?></td></tr>
    <tr><th>Mode · experience</th><td><?php echo $e($modes[$A['mode']] ?? ''); ?> · <?php echo $e($A['experience']); ?></td></tr>
    <tr><th>Background</th><td style="white-space:pre-line"><?php echo $e($A['bio']); ?></td></tr>
    <tr><th>Proposed topics</th><td style="white-space:pre-line"><?php echo $e($A['topics']); ?></td></tr>
    <tr><th>Availability</th><td><?php echo $e($A['availability'] ?: '—'); ?></td></tr>
    <tr><th>Links</th><td><?php echo $A['portfolio_url'] ? '<a href="' . $e($A['portfolio_url']) . '" target="_blank" rel="noopener">Portfolio ↗</a> ' : ''; ?><?php echo $A['cv'] ? '<a href="academy-trainers.php?cv=' . $A['id'] . '" target="_blank">CV (PDF)</a>' : ($A['portfolio_url'] ? '' : '—'); ?></td></tr>
    <tr><th>Applied</th><td><?php echo $e(date('j M Y H:i', strtotime($A['created_at']))); ?></td></tr></tbody></table>
  <form method="post" class="admin-form" style="margin-top:14px"><input type="hidden" name="id" value="<?php echo $A['id']; ?>">
    <div class="field"><label>Internal notes</label><textarea name="notes" rows="3"><?php echo $e($A['notes']); ?></textarea></div>
    <div class="field"><label>Message to include in the email (optional)</label><textarea name="message" rows="2"></textarea></div>
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px"><label><input type="checkbox" name="send_email" value="1" checked> Email the applicant when approving or rejecting</label><label><input type="checkbox" name="is_public" <?php echo $A['is_public'] ? 'checked' : ''; ?>> Show as a facilitator on the Academy page (when approved)</label></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="admin-btn admin-btn-brand" name="act" value="approve">Approve</button><button class="admin-btn admin-btn-danger" name="act" value="reject">Reject</button><button class="admin-btn admin-btn-line" name="act" value="save">Save notes</button><a class="admin-btn admin-btn-danger" href="academy-trainers.php?delete=<?php echo $A['id']; ?>" data-confirm="Delete this application permanently?">Delete</a></div></form></div>
<?php else: ?>
<div class="admin-card"><div class="admin-card-head"><h2>Facilitator applications (<?php echo count($rows); ?>)</h2><div class="ax-seg"><?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?><a href="academy-trainers.php<?php echo $k ? '?status=' . $k : ''; ?>" class="<?php echo $st === $k ? 'on' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?></div></div>
  <?php if (!$rows): ?><p class="admin-help">No applications yet.</p><?php else: ?><table class="admin-table" data-export="Trainer applications"><thead><tr><th>Ref</th><th>Name</th><th>Teaches</th><th>Mode</th><th>Experience</th><th>Status</th><th>Applied</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?php echo $e($r['ref']); ?></td><td><b><?php echo $e($r['name']); ?></b><br><small><?php echo $e($r['email']); ?></small></td><td><?php echo $e($r['expertise']); ?></td><td><?php echo $e($modes[$r['mode']] ?? ''); ?></td><td><?php echo $e($r['experience']); ?></td><td><span class="admin-badge <?php echo $r['status'] === 'approved' ? '' : 'gray'; ?>"><?php echo $e(ucfirst($r['status'])); ?></span></td><td><?php echo $e(date('j M Y', strtotime($r['created_at']))); ?></td><td><a class="admin-btn admin-btn-line admin-btn-sm" href="academy-trainers.php?view=<?php echo $r['id']; ?>">Review</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
