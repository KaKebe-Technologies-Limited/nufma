<?php
/**
 * NUFA SACCO membership applications.
 * The public form (sacco-apply.php) only accepts applications while the admin has switched "Accept membership applications" on
 * (Admin → NUFA SACCO). Each applicant is given a registration number automatically; files are kept in private storage.
 */
require_once __DIR__ . "/membership.php";

const SAC_STORAGE_DIR = __DIR__ . '/../storage/sacco/';

function sac_storage() {
    if (!is_dir(SAC_STORAGE_DIR)) @mkdir(SAC_STORAGE_DIR, 0775, true);
    if (!is_file(SAC_STORAGE_DIR . '.htaccess')) @file_put_contents(SAC_STORAGE_DIR . '.htaccess', "Require all denied\nDeny from all\n");
    if (!is_file(SAC_STORAGE_DIR . 'index.html')) @file_put_contents(SAC_STORAGE_DIR . 'index.html', '');
    return SAC_STORAGE_DIR;
}

function sac_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS sacco_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reg_no VARCHAR(60) NOT NULL,
        token CHAR(32) NOT NULL,
        status VARCHAR(10) NOT NULL DEFAULT 'pending',
        full_name VARCHAR(150) NOT NULL,
        gender VARCHAR(20) NOT NULL DEFAULT '',
        dob DATE NULL,
        nationality VARCHAR(80) NOT NULL DEFAULT '',
        marital_status VARCHAR(30) NOT NULL DEFAULT '',
        id_type VARCHAR(40) NOT NULL DEFAULT '',
        id_number VARCHAR(60) NOT NULL DEFAULT '',
        phone VARCHAR(40) NOT NULL DEFAULT '',
        alt_phone VARCHAR(40) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        district VARCHAR(100) NOT NULL DEFAULT '',
        subcounty VARCHAR(100) NOT NULL DEFAULT '',
        village VARCHAR(150) NOT NULL DEFAULT '',
        address VARCHAR(255) NOT NULL DEFAULT '',
        occupation VARCHAR(150) NOT NULL DEFAULT '',
        employer VARCHAR(190) NOT NULL DEFAULT '',
        creative_role VARCHAR(150) NOT NULL DEFAULT '',
        years_in_industry VARCHAR(40) NOT NULL DEFAULT '',
        income_band VARCHAR(60) NOT NULL DEFAULT '',
        nufa_member VARCHAR(5) NOT NULL DEFAULT '',
        nufa_reg_no VARCHAR(60) NOT NULL DEFAULT '',
        shares_applied INT NOT NULL DEFAULT 0,
        initial_deposit VARCHAR(40) NOT NULL DEFAULT '',
        monthly_savings VARCHAR(40) NOT NULL DEFAULT '',
        frequency VARCHAR(30) NOT NULL DEFAULT '',
        products VARCHAR(500) NOT NULL DEFAULT '',
        purpose TEXT,
        kin_name VARCHAR(150) NOT NULL DEFAULT '',
        kin_relationship VARCHAR(60) NOT NULL DEFAULT '',
        kin_phone VARCHAR(40) NOT NULL DEFAULT '',
        kin_address VARCHAR(255) NOT NULL DEFAULT '',
        ben_name VARCHAR(150) NOT NULL DEFAULT '',
        ben_relationship VARCHAR(60) NOT NULL DEFAULT '',
        ben_share VARCHAR(10) NOT NULL DEFAULT '',
        referee_name VARCHAR(150) NOT NULL DEFAULT '',
        referee_phone VARCHAR(40) NOT NULL DEFAULT '',
        pay_method VARCHAR(60) NOT NULL DEFAULT '',
        pay_ref VARCHAR(120) NOT NULL DEFAULT '',
        pay_date DATE NULL,
        pay_amount VARCHAR(40) NOT NULL DEFAULT '',
        pay_received TINYINT(1) NOT NULL DEFAULT 0,
        photo VARCHAR(120) NOT NULL DEFAULT '',
        id_copy VARCHAR(120) NOT NULL DEFAULT '',
        pay_proof VARCHAR(120) NOT NULL DEFAULT '',
        signature_name VARCHAR(150) NOT NULL DEFAULT '',
        reject_reason VARCHAR(500) NOT NULL DEFAULT '',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        approved_at DATETIME NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_reg (reg_no), UNIQUE KEY uq_token (token), KEY idx_status (status), KEY idx_email (email), KEY idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function sac_defaults() {
    return [
        'sacapp_reg_prefix' => 'NUFA-SACCO', 'sacapp_reg_format' => '{PREFIX}/{YEAR}/{SEQ}', 'sacapp_reg_pad' => '4', 'sacapp_reg_next' => '1',
        'sacapp_reg_fee' => '', 'sacapp_share_price' => '', 'sacapp_min_shares' => '1',
        'sacapp_pay_instructions' => '',
        'sacapp_intro' => "Complete this form to apply for membership of the NUFA SACCO. Your application is reviewed by the SACCO committee. Submitting it does not make you a member until you are told in writing that it has been approved.",
        'sacapp_declaration' => "I declare that the information I have given is true and complete.\nI have read and agree to be bound by the NUFA SACCO bylaws and membership rules.\nI understand that my application is not accepted until approved in writing by the SACCO.",
        'sacapp_require_photo' => '1', 'sacapp_require_id' => '1', 'sacapp_require_proof' => '0',
        'sacapp_income_bands' => "Below UGX 200,000\nUGX 200,000 – 500,000\nUGX 500,000 – 1,000,000\nUGX 1,000,000 – 3,000,000\nAbove UGX 3,000,000\nIrregular / project-based",
        'sacapp_pay_methods' => "Mobile Money\nBank deposit\nCash at the office",
        'sacapp_notify_email' => '',
        'sacapp_email_received_subject' => 'NUFA SACCO application received — {reg_no}',
        'sacapp_email_received_body' => "Hello {name},\n\nThank you for applying to join the NUFA SACCO. Your registration number is:\n\n{reg_no}\n\nPlease keep it safe and quote it whenever you contact us. Your application is now waiting for review by the SACCO committee — it is not membership until you receive an approval message.\n\n— NUFA SACCO",
        'sacapp_email_approved_subject' => 'Welcome to the NUFA SACCO — {reg_no}',
        'sacapp_email_approved_body' => "Hello {name},\n\nCongratulations! Your application to join the NUFA SACCO has been approved.\n\nRegistration number: {reg_no}\n\n{next_steps}\n\nWelcome.\n\n— NUFA SACCO",
        'sacapp_approved_next_steps' => 'The SACCO team will contact you with the next steps for your savings and shares.',
        'sacapp_email_rejected_subject' => 'Your NUFA SACCO application — {reg_no}',
        'sacapp_email_rejected_body' => "Hello {name},\n\nThank you for your interest in the NUFA SACCO. Unfortunately we could not approve your application ({reg_no}) at this time.\n\nReason: {reason}\n\nYou are welcome to contact us or apply again.\n\n— NUFA SACCO",
    ];
}

function sac_settings($conn, $refresh = false) {
    static $c = null;
    if ($c !== null && !$refresh) return $c;
    $c = sac_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sacapp\\_%'");
    while ($r && ($x = $r->fetch_assoc())) if (array_key_exists($x['setting_key'], $c) && ($x['setting_value'] !== '' || in_array($x['setting_key'], ['sacapp_reg_fee', 'sacapp_share_price', 'sacapp_pay_instructions', 'sacapp_notify_email', 'sacapp_require_photo', 'sacapp_require_id', 'sacapp_require_proof'], true))) $c[$x['setting_key']] = $x['setting_value'];
    return $c;
}

function sac_lines($t) { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$t)))); }

/** Atomically takes the next registration number (e.g. NUFA-SACCO/2027/0001). */
function sac_next_reg_no($conn) {
    $conn->begin_transaction();
    try {
        $conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('sacapp_reg_next', '1')");
        $r = $conn->query("SELECT setting_value FROM settings WHERE setting_key='sacapp_reg_next' FOR UPDATE")->fetch_assoc();
        $seq = max(1, (int)($r['setting_value'] ?? 1)); $S = sac_settings($conn, true);
        $pad = max(1, (int)$S['sacapp_reg_pad']);
        do {
            $reg = strtr($S['sacapp_reg_format'], ['{PREFIX}' => $S['sacapp_reg_prefix'], '{YEAR}' => date('Y'), '{SEQ}' => str_pad((string)$seq, $pad, '0', STR_PAD_LEFT)]);
            $exists = $conn->query("SELECT id FROM sacco_applications WHERE reg_no='" . $conn->real_escape_string($reg) . "'")->num_rows;
            if ($exists) $seq++;
        } while ($exists);
        $next = (string)($seq + 1);
        $u = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='sacapp_reg_next'"); $u->bind_param("s", $next); $u->execute();
        $conn->commit();
        return $reg;
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}

function sac_vars($a, $extra = []) {
    return ['{name}' => $a['full_name'], '{reg_no}' => $a['reg_no'], '{email}' => $a['email'], '{phone}' => $a['phone'], '{status}' => $a['status'], '{reason}' => $a['reject_reason'] !== '' ? $a['reject_reason'] : 'Not stated', '{next_steps}' => ''] + $extra;
}

function sac_status_badge($s) {
    return ['pending' => '<span class="admin-badge gray">Pending</span>', 'approved' => '<span class="admin-badge">Approved</span>', 'rejected' => '<span class="admin-badge red">Rejected</span>'][$s] ?? $s;
}

/** The fields shown on the public form and in the admin detail view, grouped. [label, column] pairs. */
function sac_sections() {
    return [
        'Personal details' => [['Full name', 'full_name'], ['Gender', 'gender'], ['Date of birth', 'dob'], ['Nationality', 'nationality'], ['Marital status', 'marital_status'], ['ID type', 'id_type'], ['ID number', 'id_number'], ['Phone', 'phone'], ['Alternative phone', 'alt_phone'], ['Email', 'email']],
        'Residence' => [['District', 'district'], ['Sub-county / division', 'subcounty'], ['Village / parish', 'village'], ['Physical address', 'address']],
        'Work & NUFA' => [['Occupation', 'occupation'], ['Employer / business', 'employer'], ['Area of film / creative practice', 'creative_role'], ['Years in the industry', 'years_in_industry'], ['Monthly income', 'income_band'], ['NUFA member?', 'nufa_member'], ['NUFA registration no.', 'nufa_reg_no']],
        'Savings plan' => [['Shares applied for', 'shares_applied'], ['Initial deposit (UGX)', 'initial_deposit'], ['Planned regular savings (UGX)', 'monthly_savings'], ['Saving frequency', 'frequency'], ['Products of interest', 'products'], ['Why join', 'purpose']],
        'Next of kin' => [['Name', 'kin_name'], ['Relationship', 'kin_relationship'], ['Phone', 'kin_phone'], ['Address', 'kin_address']],
        'Beneficiary' => [['Name', 'ben_name'], ['Relationship', 'ben_relationship'], ['Share (%)', 'ben_share']],
        'Introduced by' => [['Referee name', 'referee_name'], ['Referee phone', 'referee_phone']],
        'Registration fee' => [['Payment method', 'pay_method'], ['Reference / transaction ID', 'pay_ref'], ['Payment date', 'pay_date'], ['Amount paid', 'pay_amount']],
    ];
}
