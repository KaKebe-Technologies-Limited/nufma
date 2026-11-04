<?php
/**
 * Admin-side member registration: add one member, import many from a CSV file, issue membership IDs (registration numbers)
 * in bulk, and delete members. Uses the same `members` table and numbering as the public application form.
 */
require_once __DIR__ . "/membership.php";

/** Creates a member row. $d: full_name (required), email, phone, gender, dob, district, address, occupation, id_number, guild_id, status. Returns the new id. */
function mem_admin_create($conn, array $d) {
    $name = trim((string)($d['full_name'] ?? ''));
    if ($name === '') throw new InvalidArgumentException('A name is required.');
    $g = fn($k) => trim((string)($d[$k] ?? ''));
    $gender = in_array(ucfirst(strtolower($g('gender'))), ['Male', 'Female', 'Other'], true) ? ucfirst(strtolower($g('gender'))) : '';
    $dob = null; if ($g('dob') !== '' && ($t = strtotime($g('dob')))) $dob = date('Y-m-d', $t);
    $guild = (int)($d['guild_id'] ?? 0) ?: null;
    $status = in_array($d['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $d['status'] : 'pending';
    $token = bin2hex(random_bytes(16));
    mem_subregion_col($conn);
    $sub = $g('subregion');
    if (!in_array($sub, mem_subregion_options(), true)) { $sub = ''; foreach (mem_subregion_options() as $o) if (strcasecmp($o, $g('subregion')) === 0) $sub = $o; }
    if ($sub === '') $sub = mem_subregion_of($g('district'), mem_subregion_map($conn));
    $email = $g('email'); $phone = $g('phone'); $district = $g('district'); $address = $g('address'); $occ = $g('occupation'); $idn = $g('id_number');
    $note = 'Registered by admin' . (!empty($d['note']) ? ': ' . $d['note'] : '');
    $s = $conn->prepare("INSERT INTO members (card_token, full_name, gender, dob, phone, email, id_number, district, subregion, address, occupation, guild_id, status, admin_note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $s->bind_param("sssssssssssiss", $token, $name, $gender, $dob, $phone, $email, $idn, $district, $sub, $address, $occ, $guild, $status, $note);
    $s->execute();
    return (int)$conn->insert_id;
}

/** Approves a member and gives them a membership ID (kept if they already have one). Returns the reg_no. */
function mem_issue_id($conn, $id, $from = '', $until = '') {
    $S = mem_settings($conn);
    $m = $conn->query("SELECT reg_no, valid_from, valid_until FROM members WHERE id=" . (int)$id)->fetch_assoc();
    if (!$m) return '';
    $from = $from ?: ($m['valid_from'] ?: date('Y-m-d'));
    $until = $until ?: ($m['valid_until'] ?: date('Y-m-d', strtotime("$from +" . max(1, (int)$S['validity_months']) . " months -1 day")));
    $reg = $m['reg_no'] ?: mem_next_reg_no($conn);
    $u = $conn->prepare("UPDATE members SET status='approved', reg_no=?, valid_from=?, valid_until=?, reject_reason='', approved_at=COALESCE(approved_at, NOW()) WHERE id=?");
    $u->bind_param("sssi", $reg, $from, $until, $id); $u->execute();
    return $reg;
}

/** E-mails the approval message (membership ID + card link). Returns true if it was handed to the mail system. */
function mem_admin_notify($conn, $id) {
    $S = mem_settings($conn);
    $m = $conn->query("SELECT m.*, g.name AS guild_name FROM members m LEFT JOIN guilds g ON g.id=m.guild_id WHERE m.id=" . (int)$id)->fetch_assoc();
    if (!$m || $m['email'] === '' || !filter_var($m['email'], FILTER_VALIDATE_EMAIL)) return false;
    $v = mem_member_vars($conn, $m);
    return (bool)mem_send($conn, (int)$id, 'approved', $m['email'], mem_fill($S['email_approved_subject'], $v), mem_fill($S['email_approved_body'], $v), $v['card_link']);
}

function mem_admin_delete($conn, $id) {
    $id = (int)$id;
    $m = $conn->query("SELECT photo, payment_proof FROM members WHERE id=$id")->fetch_assoc();
    if (!$m) return;
    foreach (['photo', 'payment_proof'] as $col) if ($m[$col] && defined('MEM_STORAGE_DIR')) @unlink(MEM_STORAGE_DIR . basename($m[$col]));
    $conn->query("DELETE FROM member_emails WHERE member_id=$id");
    $conn->query("DELETE FROM members WHERE id=$id");
}

/** Does a member with this email (or, without an email, this name + phone) already exist? */
function mem_admin_exists($conn, $name, $email, $phone) {
    if ($email !== '') { $s = $conn->prepare("SELECT id FROM members WHERE email=? LIMIT 1"); $s->bind_param("s", $email); }
    else { $s = $conn->prepare("SELECT id FROM members WHERE LOWER(full_name)=LOWER(?) AND phone=? LIMIT 1"); $s->bind_param("ss", $name, $phone); }
    $s->execute(); return $s->get_result()->num_rows > 0;
}

/** Column headings accepted in an import file (any capitalisation / spacing). */
function mem_import_aliases() {
    return [
        'full_name' => ['full name', 'fullname', 'name', 'names', 'member', 'member name', 'full_name'],
        'first_name' => ['first name', 'firstname', 'first_name', 'given name'],
        'last_name' => ['last name', 'lastname', 'last_name', 'surname', 'family name'],
        'email' => ['email', 'e-mail', 'email address', 'mail'],
        'phone' => ['phone', 'phone number', 'telephone', 'tel', 'mobile', 'contact', 'whatsapp'],
        'gender' => ['gender', 'sex'],
        'dob' => ['dob', 'date of birth', 'birthday', 'birth date'],
        'district' => ['district', 'location', 'town', 'area'],
        'subregion' => ['subregion', 'sub-region', 'sub region', 'sub_region', 'region'],
        'address' => ['address', 'residence', 'village'],
        'occupation' => ['occupation', 'role', 'profession', 'skill', 'job'],
        'id_number' => ['id number', 'id_number', 'national id', 'nin', 'id no', 'id'],
        'guild' => ['guild', 'category', 'group', 'department'],
    ];
}

/** Reads an uploaded CSV/TXT into [headerMap|null, rows]. A file without recognisable headings is read as one name per line. */
function mem_import_read($path) {
    $raw = file_get_contents($path);
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    if (!mb_check_encoding($raw, 'UTF-8')) $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    $first = strtok($raw, "\n");
    $delim = ','; $best = substr_count($first, ',');
    foreach ([';' => ';', "\t" => "\t", '|' => '|'] as $d) if (substr_count($first, $d) > $best) { $best = substr_count($first, $d); $delim = $d; }
    $rows = [];
    $f = fopen('php://temp', 'w+'); fwrite($f, $raw); rewind($f);
    while (($r = fgetcsv($f, 0, $delim)) !== false) { if (count($r) === 1 && trim((string)$r[0]) === '') continue; $rows[] = array_map(fn($c) => trim((string)$c), $r); }
    fclose($f);
    if (!$rows) return [null, []];
    $map = []; $al = mem_import_aliases();
    foreach ($rows[0] as $i => $h) { $k = strtolower(trim($h)); foreach ($al as $field => $names) if (in_array($k, $names, true) && !in_array($field, $map, true)) { $map[$i] = $field; break; } }
    if ($map) { array_shift($rows); return [$map, $rows]; }
    return [null, $rows];
}

/** Turns one parsed row into member fields. */
function mem_import_row(?array $map, array $r, array $guildByName) {
    $d = [];
    if ($map === null) { $d['full_name'] = $r[0] ?? ''; }
    else foreach ($map as $i => $field) $d[$field] = $r[$i] ?? '';
    if (empty($d['full_name']) && (!empty($d['first_name']) || !empty($d['last_name']))) $d['full_name'] = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
    if (!empty($d['guild'])) { $k = strtolower($d['guild']); if (isset($guildByName[$k])) $d['guild_id'] = $guildByName[$k]; }
    return $d;
}

/** Earlier records in the database that can be turned into members: key => [label, FROM/SELECT sql (normalised columns), search columns]. */
function mem_sources($conn) {
    $all = [
        'members'    => ['Existing members — renew membership', "SELECT id, full_name, email, phone, gender, dob, district, occupation, id_number, address, reg_no, status, valid_until FROM members", ['full_name', 'email', 'phone', 'reg_no']],
        'sacco'      => ['SACCO applicants', "SELECT id, full_name, email, phone, gender, dob, district, occupation, id_number, address, '' AS reg_no, status, NULL AS valid_until FROM sacco_applications", ['full_name', 'email', 'phone']],
        'learners'   => ['NUFA Academy learners', "SELECT id, name AS full_name, email, phone, '' AS gender, NULL AS dob, '' AS district, '' AS occupation, '' AS id_number, '' AS address, '' AS reg_no, '' AS status, NULL AS valid_until FROM academy_learners", ['name', 'email', 'phone']],
        'ambassadors' => ['Ambassador applicants', "SELECT id, name AS full_name, email, phone, '' AS gender, NULL AS dob, city AS district, occupation, '' AS id_number, '' AS address, '' AS reg_no, status, NULL AS valid_until FROM ambassadors", ['name', 'email', 'phone', 'city']],
        'films'      => ['Film Showcase filmmakers', "SELECT id, fm_name AS full_name, email, phone, '' AS gender, NULL AS dob, fm_location AS district, '' AS occupation, '' AS id_number, '' AS address, '' AS reg_no, status, NULL AS valid_until FROM films WHERE fm_name<>''", ['fm_name', 'email', 'phone']],
        'subscribers' => ['Newsletter subscribers (with a name)', "SELECT id, name AS full_name, email, '' AS phone, '' AS gender, NULL AS dob, '' AS district, '' AS occupation, '' AS id_number, '' AS address, '' AS reg_no, '' AS status, NULL AS valid_until FROM subscribers WHERE name<>'' AND unsubscribed=0", ['name', 'email']],
    ];
    $out = [];
    foreach ($all as $k => $s) {
        preg_match('/FROM (\w+)/', $s[1], $m);
        $t = $conn->query("SHOW TABLES LIKE '" . $m[1] . "'");
        if ($t && $t->num_rows) $out[$k] = $s;
    }
    return $out;
}

/** Rows of one source (optionally filtered by a search text), newest first. */
function mem_source_rows($conn, array $src, $q = '', $limit = 100, array $ids = []) {
    [$label, $sql, $cols] = $src;
    $where = []; $types = ''; $params = [];
    $hasWhere = stripos($sql, ' WHERE ') !== false;
    if ($ids) $where[] = "id IN (" . implode(',', array_map('intval', $ids)) . ")";
    if ($q !== '') {
        $likes = []; foreach ($cols as $c) { $likes[] = "$c LIKE ?"; $types .= 's'; $params[] = "%$q%"; }
        $where[] = '(' . implode(' OR ', $likes) . ')';
    }
    $sql .= ($where ? ($hasWhere ? ' AND ' : ' WHERE ') . implode(' AND ', $where) : '') . " ORDER BY id DESC LIMIT " . (int)$limit;
    $st = $conn->prepare($sql); if ($params) $st->bind_param($types, ...$params); $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}
