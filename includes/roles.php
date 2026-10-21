<?php
/**
 * Admin roles & permissions.
 * Each role grants a level per module: 0 = no access, 1 = view, 2 = manage (view + change).
 * Roles are stored in `admin_roles` (editable in admin → Roles & permissions); each admin account has a `role_key`.
 */

const ROLE_NONE = 0, ROLE_VIEW = 1, ROLE_MANAGE = 2;

function admin_modules() {
    return [
        'members'     => ['Membership', 'Applications, approvals, member records, guilds and membership settings'],
        'team'        => ['Leadership & team', 'Leadership, team and staff profiles'],
        'enquiries'   => ['Enquiries & messages', 'Contact, partnership, SACCO, network and ticket enquiries'],
        'support'     => ['Support, donations & payments', 'Sponsor / donate / volunteer / partner requests and payment methods'],
        'sacco'       => ['NUFA SACCO', 'SACCO status, products, documents and announcements'],
        'events'      => ['Schedule & calendar', 'Programme dates, holidays and event reminders'],
        'awards'      => ['NUFA Awards', 'Editions, winners, road map, tickets and submission portal'],
        'invitations' => ['Award invitations', 'Guest lists, invitation PDFs and sending invitations'],
        'news'        => ['News & newsletters', 'Stories, newsletters and subscribers'],
        'website'     => ['Website content', 'Pages, menus, appearance, sliders, gallery, videos, resources, programmes and partners'],
        'settings'    => ['Site settings', 'Site-wide settings'],
        'users'       => ['Users & roles', 'Admin accounts and the permissions of each role'],
    ];
}

/** Which module guards each admin page (files not listed are open to every signed-in admin). */
function admin_page_modules() {
    $map = [
        'members' => ['members.php', 'member-view.php', 'member-file.php', 'guilds.php', 'membership-settings.php'],
        'team' => ['team.php', 'team-edit.php'],
        'enquiries' => ['enquiries.php', 'enquiry-view.php', 'messages.php', 'reply.php', 'feedback.php'],
        'support' => ['support-requests.php', 'support-view.php', 'support-file.php', 'support-setup.php', 'partner-applications.php', 'partner-file.php'],
        'sacco' => ['sacco-manager.php', 'sacco-applications.php', 'sacco-file.php'],
        'events' => ['schedule.php', 'greetings.php'],
        'awards' => ['award-events.php', 'award-event-file.php', 'award-editions.php', 'award-edition-edit.php', 'award-winners.php', 'award-winner-edit.php', 'awards-setup.php'],
        'invitations' => ['invitations.php', 'invitation.php', 'invitation-file.php'],
        'news' => ['posts.php', 'post-edit.php', 'newsletters.php', 'newsletter-edit.php', 'subscribers.php', 'upload-image.php'],
        'website' => ['pages.php', 'page-editor.php', 'content.php', 'content-edit.php', 'menus.php', 'appearance.php', 'lists.php', 'website-map.php', 'sliders.php', 'slide-edit.php',
                      'gallery.php', 'videos.php', 'resources.php', 'opportunities.php', 'programmes.php', 'programme-edit.php', 'partners.php', 'partner-edit.php'],
        'settings' => ['settings.php', 'maintenance.php'],
        'users' => ['users.php', 'roles.php'],
    ];
    $out = [];
    foreach ($map as $m => $files) foreach ($files as $f) $out[$f] = $m;
    return $out;
}

function admin_default_roles() {
    $all = array_fill_keys(array_keys(admin_modules()), ROLE_MANAGE);
    $r = fn($p) => array_merge(array_fill_keys(array_keys(admin_modules()), ROLE_NONE), $p);
    return [
        'administrator' => ['Administrator', 'Full access to everything, including users and roles.', $all],
        'president' => ['President', 'Oversees the association: manages membership, enquiries, support, events, awards, invitations and news; can see SACCO and website content.',
            $r(['members' => 2, 'team' => 2, 'enquiries' => 2, 'support' => 2, 'sacco' => 1, 'events' => 2, 'awards' => 2, 'invitations' => 2, 'news' => 2, 'website' => 1])],
        'event_manager' => ['NUFA Event Manager', 'Plans events: schedule, awards, invitations and reminders.',
            $r(['events' => 2, 'awards' => 2, 'invitations' => 2, 'news' => 1, 'enquiries' => 1, 'members' => 1])],
        'hr' => ['HR', 'Looks after people: membership records and approvals, leadership and team profiles.',
            $r(['members' => 2, 'team' => 2, 'enquiries' => 1, 'news' => 1])],
        'finance' => ['Finance Manager', 'Handles money: support requests, donations and payment methods; can see membership, SACCO and award records.',
            $r(['support' => 2, 'members' => 1, 'sacco' => 1, 'awards' => 1, 'enquiries' => 1])],
        'technical_lead' => ['Technical Lead', 'Runs the website: all content, settings, awards, events and news; can see users and records.',
            $r(['website' => 2, 'settings' => 2, 'team' => 2, 'news' => 2, 'events' => 2, 'awards' => 2, 'users' => 1, 'enquiries' => 1, 'members' => 1, 'sacco' => 1, 'invitations' => 1, 'support' => 1])],
        'sacco_manager' => ['Sacco Manager', 'Runs the NUFA SACCO page: status, products, documents and announcements.',
            $r(['sacco' => 2, 'members' => 1, 'enquiries' => 1, 'news' => 1, 'events' => 1])],
        'secretary' => ['NUFA Secretary', 'Administration: enquiries, events, news and invitations; can see membership and awards.',
            $r(['enquiries' => 2, 'events' => 2, 'news' => 2, 'invitations' => 2, 'members' => 1, 'awards' => 1, 'team' => 1])],
        'protocol' => ['Head of Protocol', 'Guests and ceremony: award invitations and events; can see awards and members.',
            $r(['invitations' => 2, 'events' => 2, 'awards' => 1, 'members' => 1])],
    ];
}

function roles_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS admin_roles (
        role_key VARCHAR(40) NOT NULL PRIMARY KEY,
        label VARCHAR(80) NOT NULL,
        description VARCHAR(400) NOT NULL DEFAULT '',
        perms TEXT NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_system TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ($conn->query("SHOW COLUMNS FROM admins LIKE 'role_key'")->num_rows === 0) $conn->query("ALTER TABLE admins ADD COLUMN role_key VARCHAR(40) NOT NULL DEFAULT 'administrator'");
    $have = array_column($conn->query("SELECT role_key FROM admin_roles")->fetch_all(MYSQLI_ASSOC), 'role_key');
    $s = $conn->prepare("INSERT INTO admin_roles (role_key, label, description, perms, sort_order, is_system) VALUES (?,?,?,?,?,?)");
    $i = 0;
    foreach (admin_default_roles() as $k => [$label, $desc, $perms]) {
        $i++;
        if (in_array($k, $have, true)) continue;
        $json = json_encode($perms); $sys = $k === 'administrator' ? 1 : 0;
        $s->bind_param("ssssii", $k, $label, $desc, $json, $i, $sys); $s->execute();
    }
}

function roles_all($conn) {
    $rows = $conn->query("SELECT * FROM admin_roles ORDER BY sort_order, label")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) $r['perm'] = admin_normalise_perms(json_decode($r['perms'], true) ?: []);
    return $rows;
}

function admin_normalise_perms(array $p) {
    $out = [];
    foreach (admin_modules() as $m => $_) $out[$m] = max(0, min(2, (int)($p[$m] ?? 0)));
    return $out;
}

/** Loads the signed-in admin's role and permissions into the request-level cache. */
function admin_load_role($conn, $adminId) {
    global $__ADMIN_ROLE;
    // two lookups instead of a JOIN: the tables can carry different collations on some hosts
    $a = $conn->query("SELECT role_key FROM admins WHERE id=" . (int)$adminId)->fetch_assoc();
    $r = $a ? $conn->query("SELECT label, perms FROM admin_roles WHERE role_key='" . $conn->real_escape_string($a['role_key']) . "'")->fetch_assoc() : null;
    if ($r !== null && $a) $r['role_key'] = $a['role_key'];
    $perms = $r && $r['perms'] ? admin_normalise_perms(json_decode($r['perms'], true) ?: []) : null;
    if ($perms === null) $perms = array_fill_keys(array_keys(admin_modules()), ROLE_MANAGE);   // unknown role key: keep the account usable
    $__ADMIN_ROLE = ['key' => $r['role_key'] ?? 'administrator', 'label' => ($r['label'] ?? '') ?: 'Administrator', 'perms' => $perms];
    return $__ADMIN_ROLE;
}

function admin_level($module) { global $__ADMIN_ROLE; return (int)($__ADMIN_ROLE['perms'][$module] ?? ROLE_NONE); }
function admin_can($module, $level = ROLE_VIEW) { return admin_level($module) >= $level; }
function admin_role_label() { global $__ADMIN_ROLE; return $__ADMIN_ROLE['label'] ?? 'Administrator'; }
function admin_can_file($file) { $m = admin_page_modules()[basename($file)] ?? null; return $m === null || admin_can($m, ROLE_VIEW); }

function roles_level_label($l) { return [0 => 'No access', 1 => 'View only', 2 => 'Manage'][(int)$l] ?? ''; }
