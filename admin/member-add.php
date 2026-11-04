<?php
$adminTitle = "Register members";
$adminNav = "members";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
require_once __DIR__ . "/../includes/member-admin.php";

$S = mem_settings($conn);
$guilds = $conn->query("SELECT id, name FROM guilds WHERE is_active=1 ORDER BY sort_order, name")->fetch_all(MYSQLI_ASSOC);
$guildByName = []; foreach ($guilds as $g) $guildByName[strtolower($g['name'])] = (int)$g['id'];
$tabs = ['add' => 'Register a member', 'import' => 'Import from CSV', 'existing' => 'From existing records', 'ids' => 'Issue membership IDs', 'export' => 'Export records'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'add';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$flash = ''; $flashType = 'ok'; $report = null;
$validityDefault = max(1, (int)$S['validity_months']);

/* ---- downloads: CSV template, export ---- */
if (isset($_GET['template'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="nufa-members-import-template.csv"');
    echo "\xEF\xBB\xBF" . "full_name,email,phone,gender,dob,district,subregion,guild,occupation,id_number,address\r\n" . "Akello Grace,grace@example.com,0772000000,Female,1995-04-12,Gulu,Acholi,Actors & Performers,Actor,,Pece\r\n" . "Ocen David,,0703000000,Male,,Kitgum,,Directors & Producers,Director,,\r\n";
    exit;
}
function mem_export_where($conn, array $q, &$types, array &$params) {
    $w = [];
    if (in_array($q['status'] ?? '', ['pending', 'approved', 'rejected'], true)) { $w[] = "m.status=?"; $types .= 's'; $params[] = $q['status']; }
    if (!empty($q['guild'])) { $w[] = "m.guild_id=?"; $types .= 'i'; $params[] = (int)$q['guild']; }
    if (!empty($q['district'])) { $w[] = "m.district LIKE ?"; $types .= 's'; $params[] = '%' . $q['district'] . '%'; }
    if (($q['id'] ?? '') === 'with') $w[] = "m.reg_no IS NOT NULL AND m.reg_no<>''";
    if (($q['id'] ?? '') === 'without') $w[] = "(m.reg_no IS NULL OR m.reg_no='')";
    if (!empty($q['from']) && strtotime($q['from'])) { $w[] = "DATE(m.created_at)>=?"; $types .= 's'; $params[] = date('Y-m-d', strtotime($q['from'])); }
    if (!empty($q['to']) && strtotime($q['to'])) { $w[] = "DATE(m.created_at)<=?"; $types .= 's'; $params[] = date('Y-m-d', strtotime($q['to'])); }
    return $w ? ' WHERE ' . implode(' AND ', $w) : '';
}
if (isset($_GET['fmt']) && isset(NUFA_EXPORT_FORMATS[$_GET['fmt']])) {
    $types = ''; $params = [];
    $sql = "SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id" . mem_export_where($conn, $_GET, $types, $params) . " ORDER BY m.full_name";
    $st = $conn->prepare($sql); if ($params) $st->bind_param($types, ...$params); $st->execute();
    $ex = []; $srMap = mem_subregion_map($conn);
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $ex[] = [$r['reg_no'] ?: '', $r['full_name'], $r['gender'], $r['dob'], $r['phone'], $r['email'], $r['id_number'], $r['district'], mem_subregion_for($r, $srMap), $r['address'], $r['occupation'], $r['experience'], $r['guild_name'], $r['status'], $r['valid_from'], $r['valid_until'], $r['payment_method'], $r['payment_ref'], $r['amount_paid'], $r['created_at'], mem_card_link($r)];
    nufa_export_send($_GET['fmt'], 'NUFA members register', ['Membership ID', 'Full name', 'Gender', 'Date of birth', 'Phone', 'Email', 'ID number', 'District', 'Sub-region', 'Address', 'Occupation', 'Experience', 'Guild', 'Status', 'Valid from', 'Valid until', 'Payment method', 'Payment ref', 'Amount paid', 'Registered', 'Card link'], $ex, 'nufa-members-register', count($ex) . ' member(s) · exported ' . date('d M Y H:i'));
}

/* ---- actions ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mem_csrf_ok()) { $flash = 'Session expired — please try again.'; $flashType = 'err'; }
    else {
        $act = $_POST['action'] ?? '';
        $issue = !empty($_POST['issue']); $notify = !empty($_POST['notify']);
        $from = $_POST['valid_from'] ?? ''; $until = $_POST['valid_until'] ?? '';
        if ($from !== '' && !strtotime($from)) $from = ''; if ($until !== '' && !strtotime($until)) $until = '';
        if ($act === 'add') {
            try {
                $d = $_POST; $d['status'] = $issue ? 'approved' : 'pending';
                if (mem_admin_exists($conn, trim($d['full_name'] ?? ''), trim($d['email'] ?? ''), trim($d['phone'] ?? ''))) throw new RuntimeException('A member with the same ' . (trim($d['email'] ?? '') !== '' ? 'email' : 'name and phone') . ' is already registered.');
                $id = mem_admin_create($conn, $d); $msg = 'Registered ' . trim($d['full_name']) . '.';
                if ($issue) { $reg = mem_issue_id($conn, $id, $from, $until); $msg .= ' Membership ID: ' . $reg . '.'; if ($notify) $msg .= mem_admin_notify($conn, $id) ? ' Email sent.' : ' (Email could not be sent — see the member\'s email log.)'; }
                $flash = $msg . ' <a href="member-view.php?id=' . $id . '">Open record</a>';
            } catch (Throwable $ex) { $flash = $e($ex->getMessage()); $flashType = 'err'; }
        } elseif ($act === 'import') {
            $f = $_FILES['file'] ?? null;
            if (!$f || $f['error'] !== UPLOAD_ERR_OK) { $flash = 'Please choose a CSV file to upload.'; $flashType = 'err'; }
            elseif ($f['size'] > 5 * 1048576) { $flash = 'That file is larger than 5 MB.'; $flashType = 'err'; }
            elseif (!preg_match('/\.(csv|txt|tsv)$/i', $f['name'])) { $flash = 'Please upload a .csv, .tsv or .txt file (in Excel: Save As → CSV).'; $flashType = 'err'; }
            else {
                [$map, $rows] = mem_import_read($f['tmp_name']);
                $defGuild = (int)($_POST['guild_id'] ?? 0); $skipDup = !empty($_POST['skip_dup']);
                $report = ['ok' => 0, 'dup' => 0, 'bad' => 0, 'mail' => 0, 'lines' => [], 'headers' => $map !== null];
                foreach (array_slice($rows, 0, 5000) as $n => $r) {
                    $d = mem_import_row($map, $r, $guildByName);
                    $line = $n + 1 + ($map !== null ? 1 : 0);
                    $name = trim((string)($d['full_name'] ?? ''));
                    if ($name === '') { $report['bad']++; $report['lines'][] = [$line, '—', 'Skipped — no name']; continue; }
                    if (empty($d['guild_id']) && $defGuild) $d['guild_id'] = $defGuild;
                    if ($skipDup && mem_admin_exists($conn, $name, trim($d['email'] ?? ''), trim($d['phone'] ?? ''))) { $report['dup']++; $report['lines'][] = [$line, $name, 'Skipped — already registered']; continue; }
                    $d['status'] = $issue ? 'approved' : 'pending'; $d['note'] = 'CSV import ' . date('Y-m-d');
                    try {
                        $id = mem_admin_create($conn, $d); $report['ok']++; $reg = '';
                        if ($issue) $reg = mem_issue_id($conn, $id, $from, $until);
                        if ($issue && $notify && mem_admin_notify($conn, $id)) $report['mail']++;
                        $report['lines'][] = [$line, $name, $reg ? 'Registered · ID ' . $reg : 'Registered (pending — no ID yet)'];
                    } catch (Throwable $ex) { $report['bad']++; $report['lines'][] = [$line, $name, 'Error — ' . $ex->getMessage()]; }
                }
                $flash = 'Import finished: ' . $report['ok'] . ' registered' . ($report['dup'] ? ', ' . $report['dup'] . ' duplicate(s) skipped' : '') . ($report['bad'] ? ', ' . $report['bad'] . ' row(s) with problems' : '') . ($report['mail'] ? ', ' . $report['mail'] . ' email(s) sent' : '') . '.';
                if ($report['bad']) $flashType = 'warn';
            }
            $tab = 'import';
        } elseif ($act === 'existing') {
            $srcs = mem_sources($conn); $key = $_POST['source'] ?? '';
            $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
            if (!isset($srcs[$key]) || !$ids) { $flash = 'Choose a source and tick at least one record.'; $flashType = 'warn'; }
            else {
                $rows = mem_source_rows($conn, $srcs[$key], '', 500, $ids); $defGuild = (int)($_POST['guild_id'] ?? 0);
                $report = ['ok' => 0, 'dup' => 0, 'bad' => 0, 'mail' => 0, 'lines' => [], 'headers' => true];
                foreach ($rows as $n => $r) {
                    $name = trim((string)$r['full_name']);
                    if ($key === 'members') {   // renew an existing member: new validity, same ID
                        $reg = mem_issue_id($conn, (int)$r['id'], $from ?: date('Y-m-d'), $until ?: date('Y-m-d', strtotime('+' . $validityDefault . ' months -1 day')));
                        $report['ok']++; if ($notify && mem_admin_notify($conn, (int)$r['id'])) $report['mail']++;
                        $report['lines'][] = [$n + 1, $name, 'Renewed · ID ' . $reg]; continue;
                    }
                    if ($name === '') { $report['bad']++; $report['lines'][] = [$n + 1, '—', 'Skipped — no name']; continue; }
                    if (mem_admin_exists($conn, $name, trim((string)$r['email']), trim((string)$r['phone']))) { $report['dup']++; $report['lines'][] = [$n + 1, $name, 'Skipped — already a member']; continue; }
                    $d = $r; $d['guild_id'] = $defGuild; $d['status'] = $issue ? 'approved' : 'pending'; $d['note'] = 'From ' . $srcs[$key][0];
                    try {
                        $id = mem_admin_create($conn, $d); $report['ok']++; $reg = $issue ? mem_issue_id($conn, $id, $from, $until) : '';
                        if ($issue && $notify && mem_admin_notify($conn, $id)) $report['mail']++;
                        $report['lines'][] = [$n + 1, $name, $reg ? 'Registered · ID ' . $reg : 'Registered (pending — no ID yet)'];
                    } catch (Throwable $ex) { $report['bad']++; $report['lines'][] = [$n + 1, $name, 'Error — ' . $ex->getMessage()]; }
                }
                $flash = ($key === 'members' ? 'Renewed ' : 'Registered ') . $report['ok'] . ' member(s)' . ($report['dup'] ? ', ' . $report['dup'] . ' already members (skipped)' : '') . ($report['bad'] ? ', ' . $report['bad'] . ' problem(s)' : '') . ($report['mail'] ? ', ' . $report['mail'] . ' email(s) sent' : '') . '.';
            }
            $tab = 'existing';
        } elseif ($act === 'issue') {
            $ids = array_values(array_filter(array_map('intval', $_POST['ids'] ?? [])));
            if (!empty($_POST['all'])) $ids = array_map('intval', array_column($conn->query("SELECT id FROM members WHERE reg_no IS NULL OR reg_no='' ORDER BY created_at, id LIMIT 5000")->fetch_all(MYSQLI_ASSOC), 'id'));
            $n = 0; $mailed = 0;
            foreach ($ids as $id) { mem_issue_id($conn, $id, $from, $until); $n++; if ($notify && mem_admin_notify($conn, $id)) $mailed++; }
            $flash = $n ? "Issued membership IDs to $n member(s) and approved them." . ($notify ? " $mailed email(s) sent." : '') : 'Nobody was selected.'; if (!$n) $flashType = 'warn';
            $tab = 'ids';
        }
    }
}

/* ---- data for the tabs ---- */
$noId = (int)$conn->query("SELECT COUNT(*) FROM members WHERE reg_no IS NULL OR reg_no=''")->fetch_row()[0];
$total = (int)$conn->query("SELECT COUNT(*) FROM members")->fetch_row()[0];
$nextId = mem_format_reg($S, max(1, (int)$S['reg_next']));
$idRows = $tab === 'ids' ? $conn->query("SELECT m.id, m.full_name, m.email, m.phone, m.status, m.created_at, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE m.reg_no IS NULL OR m.reg_no='' ORDER BY m.created_at DESC LIMIT 500")->fetch_all(MYSQLI_ASSOC) : [];
include __DIR__ . "/includes/admin-header.php";
$validity = function () use ($e, $validityDefault) { return '<div class="field"><label>Valid from</label><input type="date" name="valid_from" value="' . date('Y-m-d') . '"></div><div class="field"><label>Valid until</label><input type="date" name="valid_until" value="' . date('Y-m-d', strtotime("+$validityDefault months -1 day")) . '"></div>'; };
?>
<div class="mem-tabs"><a href="member-dashboard.php">Dashboard</a><a href="members.php">Applications</a><a href="member-add.php" class="active">Register &amp; import</a><a href="member-cards.php">Cards</a><a href="guilds.php">Guilds</a><a href="membership-settings.php">Membership settings</a></div>
<?php if ($flash): ?><div class="admin-alert <?php echo $flashType; ?>"><?php echo $flash; ?></div><?php endif; ?>

<div class="admin-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="admin-stat"><b><?php echo $total; ?></b><span>Members on record</span></div>
  <div class="admin-stat"><b><?php echo $noId; ?></b><span>Without a membership ID</span></div>
  <div class="admin-stat"><b style="font-size:1.1rem"><?php echo $e($nextId); ?></b><span>Next ID to be issued</span></div>
</div>

<div class="mem-tabs"><?php foreach ($tabs as $k => $l): ?><a href="member-add.php?tab=<?php echo $k; ?>" class="<?php echo $tab === $k ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?></div>

<?php if ($tab === 'add'): ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Register a member</h2><span class="admin-help">For people who joined offline, at an event or by phone. Only the name is required.</span></div>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="add">
    <div class="admin-form-grid">
      <div class="field"><label>Full name *</label><input name="full_name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email"></div>
      <div class="field"><label>Phone / WhatsApp</label><input name="phone"></div>
      <div class="field"><label>Gender</label><select name="gender"><option value="">—</option><option>Female</option><option>Male</option><option>Other</option></select></div>
      <div class="field"><label>Date of birth</label><input type="date" name="dob"></div>
      <div class="field"><label>District</label><input name="district"></div>
      <div class="field"><label>Sub-region</label><select name="subregion"><option value="">Work out from the district</option><?php foreach (mem_subregion_options() as $o) echo '<option>' . $e($o) . '</option>'; ?></select></div>
      <div class="field"><label>Guild / craft</label><select name="guild_id"><option value="">—</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '">' . $e($g['name']) . '</option>'; ?></select></div>
      <div class="field"><label>Occupation</label><input name="occupation"></div>
      <div class="field"><label>ID number (NIN)</label><input name="id_number"></div>
      <div class="field"><label>Address</label><input name="address"></div>
    </div>
    <h3 style="margin:18px 0 8px">Membership ID</h3>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="issue" value="1" checked> Approve and issue a membership ID now (next: <b><?php echo $e($nextId); ?></b>)</label>
    <div class="admin-form-grid"><?php echo $validity(); ?></div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="notify" value="1"> Email the member their ID and membership card link (needs an email address)</label>
    <p><button class="admin-btn admin-btn-brand">Register member</button></p>
  </form>
</div>

<?php elseif ($tab === 'import'): ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Import members from a CSV file</h2><a class="admin-btn admin-btn-line admin-btn-sm" href="member-add.php?template=1">⬇ Download the CSV template</a></div>
  <p class="admin-help">Columns recognised (any order, first row = headings): <b>full_name</b> (or name, or first_name + last_name), email, phone, gender, dob, district, subregion, guild, occupation, id_number, address. A file with <b>just a list of names, one per line</b>, also works. In Excel use <i>Save As → CSV</i>. Up to 5,000 rows per file.</p>
  <form method="post" enctype="multipart/form-data" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="import">
    <div class="admin-form-grid">
      <div class="field"><label>CSV file *</label><input data-no-picker type="file" name="file" accept=".csv,.tsv,.txt,text/csv,text/plain" required></div>
      <div class="field"><label>Guild for rows that don't name one</label><select name="guild_id"><option value="">— none —</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '">' . $e($g['name']) . '</option>'; ?></select></div>
    </div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="skip_dup" value="1" checked> Skip people already registered (same email, or same name + phone)</label>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="issue" value="1" checked> Approve and issue membership IDs to everyone imported</label>
    <div class="admin-form-grid"><?php echo $validity(); ?></div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="notify" value="1"> Email each member their ID and card link (only rows with an email; may be slow for large files)</label>
    <p><button class="admin-btn admin-btn-brand">Import</button></p>
  </form>
</div>
<?php if ($report && $report['lines']): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Import report</h2><span class="admin-help"><?php echo $report['headers'] ? 'Headings were detected.' : 'No headings found — each line was read as a name.'; ?></span></div>
  <div style="overflow-x:auto"><table class="admin-table"><thead><tr><th>Row</th><th>Name</th><th>Result</th></tr></thead><tbody>
  <?php foreach ($report['lines'] as [$ln, $nm, $res]): ?><tr><td><?php echo (int)$ln; ?></td><td><?php echo $e($nm); ?></td><td><?php echo $e($res); ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
<?php endif; ?>

<?php elseif ($tab === 'existing'):
    $srcs = mem_sources($conn); $sk = $_GET['source'] ?? ($_POST['source'] ?? 'members'); if (!isset($srcs[$sk])) $sk = array_key_first($srcs);
    $sq = trim($_GET['q'] ?? ''); $srows = $sk ? mem_source_rows($conn, $srcs[$sk], $sq, 100) : [];
    $isMembers = $sk === 'members';
?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Add members from earlier records</h2><span class="admin-help">Pick people already in the database — SACCO applicants, Academy learners, ambassadors, Film Showcase filmmakers or newsletter subscribers — and register them as members, or renew existing members.</span></div>
  <form method="get" class="mem-filter"><input type="hidden" name="tab" value="existing">
    <select name="source" onchange="this.form.submit()"><?php foreach ($srcs as $k => $s) echo '<option value="' . $k . '"' . ($k === $sk ? ' selected' : '') . '>' . $e($s[0]) . '</option>'; ?></select>
    <input type="search" name="q" placeholder="Search name, email or phone" value="<?php echo $e($sq); ?>"><button class="admin-btn admin-btn-brand">Search</button>
  </form>
  <?php if (!$srows): ?><p style="color:var(--text-muted);padding:14px 0">No records found in this source.</p>
  <?php else: ?>
  <form method="post"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="existing"><input type="hidden" name="source" value="<?php echo $e($sk); ?>">
    <p class="admin-help">Showing up to 100 of the latest records — use the search to narrow down.</p>
    <div style="overflow-x:auto"><table class="admin-table" data-noexport><thead><tr><th><input type="checkbox" id="exAll" aria-label="Select all"></th><th>Name</th><th>Contact</th><th>District</th><th><?php echo $isMembers ? 'Membership ID' : 'Status'; ?></th><th><?php echo $isMembers ? 'Valid until' : 'Already a member?'; ?></th></tr></thead><tbody>
    <?php foreach ($srows as $r): $isM = !$isMembers && mem_admin_exists($conn, trim((string)$r['full_name']), trim((string)$r['email']), trim((string)$r['phone'])); ?>
      <tr><td><input type="checkbox" name="ids[]" value="<?php echo (int)$r['id']; ?>" class="exRow"<?php echo $isM ? '' : ''; ?>></td><td><b><?php echo $e($r['full_name']); ?></b></td><td><?php echo $e(trim($r['email'] . ' ' . $r['phone'])); ?></td><td><?php echo $e($r['district'] ?: '—'); ?></td>
      <td><?php echo $isMembers ? $e($r['reg_no'] ?: '—') : ($r['status'] !== '' ? $e($r['status']) : '—'); ?></td>
      <td><?php echo $isMembers ? ($r['valid_until'] ? date('d M Y', strtotime($r['valid_until'])) . (strtotime($r['valid_until'] . ' 23:59:59') < time() ? ' <span class="admin-badge gray">expired</span>' : '') : '—') : ($isM ? '<span class="admin-badge">Yes</span>' : 'No'); ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
    <?php if (!$isMembers): ?><div class="admin-form-grid" style="margin-top:12px"><div class="field"><label>Guild for the new members</label><select name="guild_id"><option value="">— choose later —</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '">' . $e($g['name']) . '</option>'; ?></select></div></div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="issue" value="1" checked> Approve and issue membership IDs now</label><?php else: ?><input type="hidden" name="issue" value="1"><p class="admin-help" style="margin-top:12px">Renewing keeps each member's ID and sets the new validity dates below.</p><?php endif; ?>
    <div class="admin-form-grid"><?php echo $validity(); ?></div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="notify" value="1"> Email each person their ID and card link</label>
    <p><button class="admin-btn admin-btn-brand"><?php echo $isMembers ? 'Renew selected members' : 'Register selected as members'; ?></button></p>
  </form>
  <script>document.getElementById('exAll')?.addEventListener('change',function(){var v=this.checked;document.querySelectorAll('.exRow').forEach(function(c){c.checked=v})});</script>
  <?php endif; ?>
</div>
<?php if ($report && $report['lines']): ?>
<div class="admin-card"><div class="admin-card-head"><h2>Result</h2></div>
  <div style="overflow-x:auto"><table class="admin-table"><thead><tr><th>#</th><th>Name</th><th>Result</th></tr></thead><tbody>
  <?php foreach ($report['lines'] as [$ln, $nm, $res]): ?><tr><td><?php echo (int)$ln; ?></td><td><?php echo $e($nm); ?></td><td><?php echo $e($res); ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
<?php endif; ?>

<?php elseif ($tab === 'ids'): ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Issue membership IDs</h2><span class="admin-help">Gives each selected person the next number (<?php echo $e($nextId); ?>…) and approves them. The numbering format is set in <a href="membership-settings.php">Membership settings</a>.</span></div>
  <?php if (!$idRows): ?><p style="color:var(--text-muted)">Everyone on record already has a membership ID.</p>
  <?php else: ?>
  <form method="post" class="admin-form"><?php echo mem_csrf_field(); ?><input type="hidden" name="action" value="issue">
    <div class="admin-form-grid"><?php echo $validity(); ?></div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="notify" value="1"> Email each member their ID and card link</label>
    <p style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
      <button class="admin-btn admin-btn-brand">Issue IDs to selected</button>
      <button class="admin-btn admin-btn-line" name="all" value="1" data-confirm="Issue membership IDs to ALL <?php echo $noId; ?> members without one?">Issue IDs to all <?php echo $noId; ?></button>
      <label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" id="idAll"> Select all shown</label>
    </p>
    <div style="overflow-x:auto"><table class="admin-table"><thead><tr><th></th><th>Name</th><th>Contact</th><th>Guild</th><th>Status</th><th>Registered</th></tr></thead><tbody>
    <?php foreach ($idRows as $r): ?><tr><td><input type="checkbox" name="ids[]" value="<?php echo $r['id']; ?>" class="idRow"></td><td><a href="member-view.php?id=<?php echo $r['id']; ?>"><?php echo $e($r['full_name']); ?></a></td><td><?php echo $e(trim($r['email'] . ' ' . $r['phone'])); ?></td><td><?php echo $e($r['guild_name'] ?? '—'); ?></td><td><?php echo mem_status_badge($r['status']); ?></td><td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </form>
  <script>document.getElementById('idAll')?.addEventListener('change',function(){var v=this.checked;document.querySelectorAll('.idRow').forEach(function(c){c.checked=v})});</script>
  <?php endif; ?>
</div>

<?php else: /* export */ ?>
<div class="admin-card">
  <div class="admin-card-head"><h2>Export member records</h2><span class="admin-help">Full register with every detail (ID, contacts, guild, status, validity, payment, card link). Narrow it down, then pick a format.</span></div>
  <form method="get" class="admin-form" id="expForm"><input type="hidden" name="tab" value="export">
    <div class="admin-form-grid">
      <div class="field"><label>Status</label><select name="status"><option value="">All</option><option value="approved">Approved</option><option value="pending">Pending</option><option value="rejected">Rejected</option></select></div>
      <div class="field"><label>Guild</label><select name="guild"><option value="">All</option><?php foreach ($guilds as $g) echo '<option value="' . $g['id'] . '">' . $e($g['name']) . '</option>'; ?></select></div>
      <div class="field"><label>District contains</label><input name="district"></div>
      <div class="field"><label>Membership ID</label><select name="id"><option value="">Any</option><option value="with">Has an ID</option><option value="without">No ID yet</option></select></div>
      <div class="field"><label>Registered from</label><input type="date" name="from"></div>
      <div class="field"><label>Registered to</label><input type="date" name="to"></div>
    </div>
    <p style="display:flex;flex-wrap:wrap;gap:8px"><?php foreach (NUFA_EXPORT_FORMATS as $k => $l): ?><button class="admin-btn <?php echo in_array($k, ['xlsx', 'pdf', 'docx', 'csv'], true) ? 'admin-btn-brand' : 'admin-btn-line'; ?> admin-btn-sm" name="fmt" value="<?php echo $k; ?>">⬇ <?php echo $e($l); ?></button><?php endforeach; ?></p>
  </form>
  <p class="admin-help">Every table elsewhere in the admin also has its own Export menu with the same formats.</p>
</div>
<?php endif; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
