<?php
$adminTitle = "Award invitations";
$adminNav = "invitations";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/invitations.php";
inv_install($conn);

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; $row = $conn->query("SELECT template_file FROM invite_campaigns WHERE id=$id")->fetch_assoc();
    if ($row && $row['template_file'] !== '') @unlink(inv_storage() . basename($row['template_file']));
    $conn->query("DELETE FROM invite_guests WHERE campaign_id=$id"); $conn->query("DELETE FROM invite_campaigns WHERE id=$id");
    header("Location: invitations.php?deleted=1"); exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mb_substr(trim($_POST['title'] ?? ''), 0, 190); $ed = (int)($_POST['edition_id'] ?? 0);
    if ($title === '') $err = "Give the invitation a name, e.g. “NUFA Awards 2027 — VIP guests”.";
    else {
        $layout = json_encode(inv_default_layout());
        $body = "Dear {full_name},\n\nOn behalf of the Northern Uganda Filmmakers Association, we have the honour to invite you to the {event}.\n\nYour personal invitation is attached to this email. We look forward to welcoming you.\n\nWith warm regards,\nNUFA";
        $subject = "You are invited: {event}";
        $s = $conn->prepare("INSERT INTO invite_campaigns (title, edition_id, subject, body, layout) VALUES (?,?,?,?,?)"); $s->bind_param("sisss", $title, $ed, $subject, $body, $layout); $s->execute();
        header("Location: invitation.php?id=" . $conn->insert_id . "&new=1"); exit;
    }
}
$editions = $conn->query("SELECT id, year FROM award_editions ORDER BY year DESC")->fetch_all(MYSQLI_ASSOC);
$rows = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM invite_guests g WHERE g.campaign_id=c.id) guests, (SELECT COUNT(*) FROM invite_guests g WHERE g.campaign_id=c.id AND g.status='sent') sent,
    (SELECT COUNT(*) FROM invite_guests g WHERE g.campaign_id=c.id AND g.rsvp='yes') yes, (SELECT COUNT(*) FROM invite_guests g WHERE g.campaign_id=c.id AND g.rsvp='no') nope FROM invite_campaigns c ORDER BY c.id DESC")->fetch_all(MYSQLI_ASSOC);
$e = fn($v) => htmlspecialchars((string)$v);
include __DIR__ . "/includes/admin-header.php";
?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Invitation deleted.</div><?php endif; ?>
<?php if ($err): ?><div class="admin-alert err"><?php echo $e($err); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Create an invitation</h2><span class="admin-help">Upload your PDF invitation, add guests (title + name + email) and send each guest a personalised copy.</span></div>
  <form method="post" class="admin-form" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
    <div class="field" style="margin:0;flex:2;min-width:260px"><label>Invitation name</label><input name="title" placeholder="e.g. NUFA Awards 2027 — VIP guests" required></div>
    <div class="field" style="margin:0;min-width:170px"><label>Awards edition</label><select name="edition_id"><option value="0">—</option><?php foreach ($editions as $ed) echo '<option value="' . $ed['id'] . '">NUFA Awards ' . (int)$ed['year'] . '</option>'; ?></select></div>
    <button class="admin-btn admin-btn-brand">Create</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Invitations</h2></div>
  <table class="admin-table"><thead><tr><th>Invitation</th><th>Guests</th><th>Sent</th><th>Attending</th><th>Declined</th><th></th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="6" style="color:var(--text-muted)">No invitations yet.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?><tr><td><b><?php echo $e($r['title']); ?></b><br><small><?php echo $r['template_file'] ? 'PDF template uploaded' : '<span style="color:#b3432f">No PDF template yet</span>'; ?></small></td>
      <td><?php echo (int)$r['guests']; ?></td><td><?php echo (int)$r['sent']; ?></td><td><?php echo (int)$r['yes']; ?></td><td><?php echo (int)$r['nope']; ?></td>
      <td><div class="admin-row-actions"><a class="admin-btn admin-btn-line admin-btn-sm" href="invitation.php?id=<?php echo $r['id']; ?>">Open</a>
        <a class="admin-btn admin-btn-danger admin-btn-sm" href="invitations.php?delete=<?php echo $r['id']; ?>" data-confirm="Delete this invitation, its guest list and the uploaded PDF?">Delete</a></div></td></tr>
    <?php endforeach; ?></tbody></table>
</div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
