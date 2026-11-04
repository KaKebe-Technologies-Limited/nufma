<?php
/**
 * "Track my registration": finds a person's applications across the website (membership, SACCO, partner, ambassador, Film Showcase,
 * award-night acts and workshops, support/donation requests) by reference number, or by email + phone number.
 */

/** Reference shown to a membership applicant (membership forms have no number until approval). */
function track_member_ref($tokenOrRow) {
    $t = is_array($tokenOrRow) ? ($tokenOrRow['card_token'] ?? '') : (string)$tokenOrRow;
    return 'MEM-' . strtoupper(substr(sha1($t), 0, 8));
}

function track_has_table($conn, $t) { $r = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($t) . "'"); return $r && $r->num_rows > 0; }
function track_digits($p) { $d = preg_replace('/\D+/', '', (string)$p); return substr($d, -9); }

/** Plain-language status. Returns [label, explanation, tone (wait|ok|bad|info)]. */
function track_status($status, $reason = '') {
    $reason = trim((string)$reason);
    switch (strtolower((string)$status)) {
        case 'pending': return ['Received — waiting for review', 'We have your application. The NUFA team reviews applications in the order they arrive and will email you once a decision is made.', 'wait'];
        case 'awaiting': return ['Awaiting payment / confirmation', 'Your application is almost complete — we are waiting for confirmation before it can be finalised.', 'wait'];
        case 'approved': return ['Approved', 'Your application has been approved.', 'ok'];
        case 'confirmed': return ['Confirmed', 'Your request has been confirmed.', 'ok'];
        case 'completed': return ['Completed', 'This request has been completed. Thank you.', 'ok'];
        case 'waitlist': return ['On the waiting list', 'We could not place you yet, but you are on the waiting list and will be contacted if a place opens.', 'info'];
        case 'changes': return ['Changes requested', ($reason !== '' ? $reason : 'Please check your email for the changes we need.'), 'info'];
        case 'rejected': case 'declined': return ['Not approved', ($reason !== '' ? 'Reason given: ' . $reason : 'Unfortunately this application was not approved. You are welcome to contact us for more information.'), 'bad'];
        default: return [ucfirst((string)$status) ?: 'Received', 'Contact the NUFA team if you need more information.', 'info'];
    }
}

/** All applications that match. $ref alone, or $email + $phone together. */
function track_find($conn, $ref, $email, $phone) {
    $ref = strtoupper(trim($ref)); $email = strtolower(trim($email)); $d = track_digits($phone);
    $byRef = $ref !== ''; $byContact = !$byRef && $email !== '' && strlen($d) >= 7;
    if (!$byRef && !$byContact) return [];
    $ok = fn($row) => $byRef || ($d !== '' && track_digits($row['phone'] ?? '') === $d);
    $out = [];

    $q = function ($sql, $refCol, $emailCol) use ($conn, $byRef, $ref, $email) {
        $where = $byRef ? $refCol . " = ?" : "LOWER($emailCol) = ?";
        $s = $conn->prepare(str_replace('{W}', $where, $sql)); $v = $byRef ? $ref : $email; $s->bind_param("s", $v); $s->execute();
        return $s->get_result()->fetch_all(MYSQLI_ASSOC);
    };

    // Membership
    if (track_has_table($conn, 'members')) {
        $rows = $byRef ? $q("SELECT * FROM members WHERE {W}", "UPPER(CONCAT('MEM-', SUBSTRING(SHA1(card_token),1,8)))", 'email') : $q("SELECT * FROM members WHERE {W}", '', 'email');
        foreach ($rows as $r) if ($ok($r)) {
            $act = []; $info = [];
            if ($r['status'] === 'approved') {
                if ($r['reg_no']) $info['Membership ID'] = $r['reg_no'];
                if ($r['valid_until']) $info['Valid until'] = date('j M Y', strtotime($r['valid_until']));
                $act[] = ['View my membership card', 'card.php?t=' . $r['card_token']];
            }
            $out[] = ['Membership', 'NUFA membership application', $r['full_name'], track_member_ref($r), $r['created_at'], $r['status'], $r['reject_reason'], $info, $act];
        }
    }
    // SACCO
    if (track_has_table($conn, 'sacco_applications')) {
        foreach ($q("SELECT * FROM sacco_applications WHERE {W}", 'UPPER(reg_no)', 'email') as $r) if ($ok($r)) {
            $act = []; $info = [];
            if ($r['status'] === 'approved' && !empty($r['token'])) $act[] = ['View my SACCO card', 'sacco-card.php?t=' . $r['token']];
            $out[] = ['NUFA SACCO', 'SACCO membership application', $r['full_name'], $r['reg_no'] ?: '—', $r['created_at'], $r['status'], $r['reject_reason'] ?? '', $info, $act];
        }
    }
    // Partners
    if (track_has_table($conn, 'partner_applications')) {
        foreach ($q("SELECT * FROM partner_applications WHERE {W}", 'UPPER(ref_no)', 'email') as $r) if ($ok($r))
            $out[] = ['Partnership', 'Partnership application' . ($r['org_name'] ? ' — ' . $r['org_name'] : ''), $r['contact_name'], $r['ref_no'], $r['created_at'], $r['status'], $r['reject_reason'] ?? '', [], []];
    }
    // Ambassadors
    if (track_has_table($conn, 'ambassadors')) {
        foreach ($q("SELECT * FROM ambassadors WHERE {W}", 'UPPER(ref)', 'email') as $r) if ($ok($r))
            $out[] = ['Ambassador', 'Ambassador application', $r['name'], $r['ref'], $r['created_at'], $r['status'], '', [], []];
    }
    // Film Showcase
    if (track_has_table($conn, 'films')) {
        foreach ($q("SELECT * FROM films WHERE {W}", 'UPPER(ref)', 'email') as $r) if ($ok($r)) {
            $act = $r['status'] === 'approved' && $r['slug'] ? [['Watch it on the Film Showcase', 'film.php?slug=' . $r['slug']]] : [];
            $out[] = ['Film Showcase', 'Film submission — “' . $r['title'] . '”', $r['fm_name'], $r['ref'], $r['created_at'], $r['status'], $r['reject_reason'] ?? '', [], $act];
        }
    }
    // Award-night acts and workshops
    if (track_has_table($conn, 'award_acts')) {
        foreach ($q("SELECT * FROM award_acts WHERE {W}", 'UPPER(ref)', 'email') as $r) if ($ok($r))
            $out[] = ['NUFA Awards', 'Award-night performance — ' . $r['name'], $r['contact_name'], $r['ref'], $r['created_at'], $r['status'], '', [], []];
    }
    if (track_has_table($conn, 'award_workshops')) {
        foreach ($q("SELECT * FROM award_workshops WHERE {W}", 'UPPER(ref)', 'email') as $r) if ($ok($r))
            $out[] = ['NUFA Awards', 'Pre-event workshop — ' . $r['title'], $r['contact_name'], $r['ref'], $r['created_at'], $r['status'], '', [], []];
    }
    // Support / donations
    if (track_has_table($conn, 'engagements')) {
        foreach ($q("SELECT * FROM engagements WHERE {W}", 'UPPER(ref_no)', 'email') as $r) if ($ok($r))
            $out[] = ['Support', 'Support request (' . ($r['kind'] ?: 'support') . ')', $r['name'], $r['ref_no'], $r['created_at'], $r['status'], $r['reject_reason'] ?? '', [], []];
    }
    usort($out, fn($a, $b) => strcmp($b[4], $a[4]));
    return $out;
}

/** Adds "Track my application" to the Get-involved footer list once (an admin can remove it under Menus). */
function track_menu_seed($conn) {
    static $done = false; if ($done) return; $done = true;
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='track_menu_added'");
    if (!$f || $f->num_rows) return;
    if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='track.php'")->fetch_row()[0]) {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer2'")->fetch_row()[0];
        $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer2', 'Track my application', 'track.php', $o)");
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('track_menu_added','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}
