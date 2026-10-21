<?php
/**
 * NUFA Academy: film courses, lessons, exams, accredited certificates, live classes (physical / virtual),
 * trainer applications and a study-material library. Everything is configured in admin → NUFA Academy.
 * Learners create an account (email + password) and use the learner portal.
 */
require_once __DIR__ . "/membership.php";   // mem_send, mem_store_upload, MEM_IMG_TYPES, mem_csrf

const ACAD_STORAGE_DIR = __DIR__ . '/../storage/academy/';
function acad_storage() {
    if (!is_dir(ACAD_STORAGE_DIR)) @mkdir(ACAD_STORAGE_DIR, 0775, true);
    if (!is_file(ACAD_STORAGE_DIR . '.htaccess')) @file_put_contents(ACAD_STORAGE_DIR . '.htaccess', "Require all denied\nDeny from all\n");
    if (!is_file(ACAD_STORAGE_DIR . 'index.html')) @file_put_contents(ACAD_STORAGE_DIR . 'index.html', '');
    return ACAD_STORAGE_DIR;
}

function acad_levels() { return ['Beginner' => 'Beginner', 'Intermediate' => 'Intermediate', 'Advanced' => 'Advanced']; }
function acad_categories() { return ['In-camera basics', 'Cinematography & lighting', 'Directing', 'Acting', 'Screenwriting', 'Sound', 'Editing & post-production', 'Producing & production management', 'Documentary', 'Art department', 'Film business & distribution', 'Film studies']; }
function acad_resource_types() { return ['Free course', 'Video channel', 'Article library', 'Guide / book', 'Script library', 'Software', 'Template', 'Sound & music library', 'Directory']; }

function acad_defaults() {
    return [
        'acad_registration_open' => '1', 'acad_pass_mark' => '75', 'acad_exam_minutes' => '30', 'acad_exam_questions' => '10', 'acad_max_attempts' => '3', 'acad_retake_hours' => '0',
        'acad_require_lessons' => '1', 'acad_show_answers' => '1', 'acad_trainers_open' => '1',
        'acad_intro' => 'Learn film from the basics to advanced practice — at your own pace, online. Create a free learner account, enrol in a course, work through the lessons, pass the exam and earn a NUFA Academy certificate.',
        'acad_cert_title' => 'Certificate of Achievement', 'acad_cert_org' => 'NUFA Academy', 'acad_cert_sub' => 'Northern Uganda Filmmakers Association',
        'acad_sign1_name' => '', 'acad_sign1_title' => 'Academy Director', 'acad_sign2_name' => '', 'acad_sign2_title' => 'NUFA Chairperson',
        'acad_accreditation' => '', 'acad_payment_info' => 'Payment instructions will be shown here for paid courses. After paying, your enrolment is confirmed by the Academy team.',
        'acad_notify_email' => '', 'acad_trainer_intro' => 'Are you an experienced film professional? Apply to facilitate a class — in person in Northern Uganda, or online.',
    ];
}
function acad_settings($conn) {
    $d = acad_defaults();
    $r = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'acad\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d)) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}
function acad_save_setting($conn, $k, $v) { $s = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"); $s->bind_param("ss", $k, $v); $s->execute(); }

function acad_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $T = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query("CREATE TABLE IF NOT EXISTS academy_learners (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) NOT NULL,
        phone VARCHAR(40) NOT NULL DEFAULT '', country VARCHAR(80) NOT NULL DEFAULT '', reset_hash VARCHAR(64) NOT NULL DEFAULT '', reset_expires DATETIME NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1, last_login DATETIME NULL, ip VARCHAR(45) NOT NULL DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_email (email)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_courses (
        id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(90) NOT NULL, title VARCHAR(190) NOT NULL, level VARCHAR(20) NOT NULL DEFAULT 'Beginner', category VARCHAR(60) NOT NULL DEFAULT '',
        summary VARCHAR(500) NOT NULL DEFAULT '', description TEXT, outcomes TEXT, duration VARCHAR(60) NOT NULL DEFAULT '', fee INT NOT NULL DEFAULT 0, currency VARCHAR(6) NOT NULL DEFAULT 'UGX',
        pass_mark INT NOT NULL DEFAULT 0, exam_minutes INT NOT NULL DEFAULT 0, exam_questions INT NOT NULL DEFAULT 0, cover VARCHAR(255) NOT NULL DEFAULT '',
        is_active TINYINT(1) NOT NULL DEFAULT 1, enrol_open TINYINT(1) NOT NULL DEFAULT 1, is_featured TINYINT(1) NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_slug (slug)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_lessons (
        id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, sort_order INT NOT NULL DEFAULT 0, title VARCHAR(190) NOT NULL, minutes INT NOT NULL DEFAULT 10, body MEDIUMTEXT,
        video_url VARCHAR(500) NOT NULL DEFAULT '', resource_url VARCHAR(500) NOT NULL DEFAULT '', resource_label VARCHAR(190) NOT NULL DEFAULT '', KEY idx_course (course_id, sort_order)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_questions (
        id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, question TEXT NOT NULL, opt_a VARCHAR(300) NOT NULL, opt_b VARCHAR(300) NOT NULL, opt_c VARCHAR(300) NOT NULL DEFAULT '', opt_d VARCHAR(300) NOT NULL DEFAULT '',
        correct TINYINT NOT NULL DEFAULT 0, explanation VARCHAR(600) NOT NULL DEFAULT '', KEY idx_course (course_id)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_enrolments (
        id INT AUTO_INCREMENT PRIMARY KEY, learner_id INT NOT NULL, course_id INT NOT NULL, status VARCHAR(12) NOT NULL DEFAULT 'active', enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL, cert_no VARCHAR(40) NULL, best_score INT NOT NULL DEFAULT 0, extra_attempts INT NOT NULL DEFAULT 0, UNIQUE KEY uq_enrol (learner_id, course_id), UNIQUE KEY uq_cert (cert_no)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_progress (enrolment_id INT NOT NULL, lesson_id INT NOT NULL, done_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (enrolment_id, lesson_id)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY, enrolment_id INT NOT NULL, started_at DATETIME NOT NULL, submitted_at DATETIME NULL, total INT NOT NULL DEFAULT 0, correct INT NOT NULL DEFAULT 0,
        score INT NOT NULL DEFAULT 0, passed TINYINT(1) NOT NULL DEFAULT 0, status VARCHAR(10) NOT NULL DEFAULT 'open', qids TEXT, answers TEXT, minutes INT NOT NULL DEFAULT 30, KEY idx_en (enrolment_id)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_classes (
        id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL DEFAULT 0, title VARCHAR(190) NOT NULL, mode VARCHAR(10) NOT NULL DEFAULT 'virtual', start_date DATE NULL, end_date DATE NULL, time_text VARCHAR(80) NOT NULL DEFAULT '',
        venue VARCHAR(255) NOT NULL DEFAULT '', meeting_link VARCHAR(500) NOT NULL DEFAULT '', facilitator VARCHAR(150) NOT NULL DEFAULT '', seats INT NOT NULL DEFAULT 0, description TEXT, fee_note VARCHAR(190) NOT NULL DEFAULT '',
        reg_open TINYINT(1) NOT NULL DEFAULT 1, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_class_signups (id INT AUTO_INCREMENT PRIMARY KEY, class_id INT NOT NULL, learner_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_su (class_id, learner_id)) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_trainers (
        id INT AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(20) NOT NULL DEFAULT '', name VARCHAR(150) NOT NULL, email VARCHAR(190) NOT NULL, phone VARCHAR(40) NOT NULL DEFAULT '', organisation VARCHAR(190) NOT NULL DEFAULT '',
        expertise VARCHAR(255) NOT NULL DEFAULT '', mode VARCHAR(12) NOT NULL DEFAULT 'both', location VARCHAR(190) NOT NULL DEFAULT '', experience VARCHAR(60) NOT NULL DEFAULT '', bio TEXT, topics TEXT, availability VARCHAR(255) NOT NULL DEFAULT '',
        portfolio_url VARCHAR(500) NOT NULL DEFAULT '', cv VARCHAR(255) NOT NULL DEFAULT '', status VARCHAR(10) NOT NULL DEFAULT 'pending', is_public TINYINT(1) NOT NULL DEFAULT 0, notes TEXT, ip VARCHAR(45) NOT NULL DEFAULT '', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) $T");
    $conn->query("CREATE TABLE IF NOT EXISTS academy_resources (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, provider VARCHAR(150) NOT NULL DEFAULT '', type VARCHAR(30) NOT NULL DEFAULT 'Article library', category VARCHAR(60) NOT NULL DEFAULT '', level VARCHAR(20) NOT NULL DEFAULT 'All levels',
        url VARCHAR(500) NOT NULL, description VARCHAR(600) NOT NULL DEFAULT '', cost VARCHAR(40) NOT NULL DEFAULT 'Free', verified_on DATE NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) $T");

    $s = $conn->query("SELECT setting_value FROM settings WHERE setting_key='acad_seeded'");
    if ($s && !$s->num_rows) { require_once __DIR__ . "/academy-seed.php"; acad_seed($conn); $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('acad_seeded','1')"); }

    $m = $conn->query("SELECT setting_value FROM settings WHERE setting_key='acad_menu_added'");
    if ($m && !$m->num_rows) {
        $p = $conn->query("SELECT id, nav_key FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='resources.php' LIMIT 1")->fetch_assoc();
        if ($p) {
            if (strpos($p['nav_key'], 'academy') === false) $conn->query("UPDATE nav_items SET nav_key='" . $conn->real_escape_string(trim($p['nav_key'] . ',academy', ',')) . "' WHERE id=" . (int)$p['id']);
            if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='academy.php'")->fetch_row()[0]) {
                $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$p['id'])->fetch_row()[0];
                $conn->query("INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', " . (int)$p['id'] . ", 'NUFA Academy', 'academy.php', $o)");
            }
        }
        if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='academy.php' AND location='footer1'")->fetch_row()[0]) {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer1'")->fetch_row()[0];
            $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer1', 'NUFA Academy', 'academy.php', $o)");
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('acad_menu_added','1')");
    }
}

/* ---------- learner session ---------- */
function acad_session() { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); }
function acad_learner($conn) {
    acad_session();
    static $l = false;
    if ($l !== false) return $l;
    $id = (int)($_SESSION['learner_id'] ?? 0);
    if (!$id) return $l = null;
    $r = $conn->query("SELECT * FROM academy_learners WHERE id=$id AND is_active=1")->fetch_assoc();
    if (!$r) { unset($_SESSION['learner_id']); return $l = null; }
    return $l = $r;
}
function acad_require($conn) {
    $l = acad_learner($conn);
    if (!$l) { header("Location: academy-login.php?next=" . urlencode(basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''))); exit; }
    return $l;
}
function acad_login($conn, $id) { acad_session(); session_regenerate_id(true); $_SESSION['learner_id'] = (int)$id; $conn->query("UPDATE academy_learners SET last_login=NOW() WHERE id=" . (int)$id); }
function acad_safe_next($n) { return preg_match('#^academy[a-z-]*\.php(\?[A-Za-z0-9=&_%.-]*)?$#', (string)$n) ? $n : 'academy-portal.php'; }
function acad_field() { require_once __DIR__ . "/forms.php"; return form_open_fields(); }

/* ---------- helpers ---------- */
function acad_pass_mark($c, $S) { return (int)($c['pass_mark'] ?: $S['acad_pass_mark']) ?: 75; }
function acad_exam_minutes($c, $S) { return (int)($c['exam_minutes'] ?: $S['acad_exam_minutes']) ?: 30; }
function acad_exam_count($c, $S) { return (int)($c['exam_questions'] ?: $S['acad_exam_questions']) ?: 10; }
function acad_fee_text($c) { return (int)$c['fee'] > 0 ? $c['currency'] . ' ' . number_format((int)$c['fee']) : 'Free'; }
function acad_lines($t) { return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$t)))); }
function acad_cover_ok($c) { return $c['cover'] !== '' && is_file(__DIR__ . '/../assets/images/' . $c['cover']); }
function acad_slug($s, $conn, $table = 'academy_courses', $skipId = 0) {
    $b = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $s), '-')) ?: 'course'; $slug = $b; $n = 1;
    while ((int)$conn->query("SELECT COUNT(*) FROM $table WHERE slug='" . $conn->real_escape_string($slug) . "' AND id<>" . (int)$skipId)->fetch_row()[0]) $slug = $b . '-' . (++$n);
    return $slug;
}
function acad_enrolment($conn, $learnerId, $courseId) {
    return $conn->query("SELECT * FROM academy_enrolments WHERE learner_id=" . (int)$learnerId . " AND course_id=" . (int)$courseId)->fetch_assoc() ?: null;
}
function acad_progress($conn, $enrolId, $courseId) {
    $tot = (int)$conn->query("SELECT COUNT(*) FROM academy_lessons WHERE course_id=" . (int)$courseId)->fetch_row()[0];
    $done = (int)$conn->query("SELECT COUNT(*) FROM academy_progress p JOIN academy_lessons l ON l.id=p.lesson_id WHERE p.enrolment_id=" . (int)$enrolId . " AND l.course_id=" . (int)$courseId)->fetch_row()[0];
    return [$done, $tot, $tot ? (int)round($done * 100 / $tot) : 0];
}
function acad_attempts_used($conn, $enrolId) { return (int)$conn->query("SELECT COUNT(*) FROM academy_attempts WHERE enrolment_id=" . (int)$enrolId)->fetch_row()[0]; }
function acad_cert_no($enrolId) { return 'NUFA-AC-' . date('Y') . '-' . str_pad((string)$enrolId, 5, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2))); }
function acad_notify_to($conn, $S) { return $S['acad_notify_email'] !== '' ? $S['acad_notify_email'] : (mem_settings($conn)['admin_notify_email'] ?? ''); }

function acad_admin_tabs($active) {
    $t = ['academy.php' => 'Courses', 'academy-classes.php' => 'Live classes', 'academy-learners.php' => 'Learners & results', 'academy-trainers.php' => 'Trainer applications', 'academy-resources.php' => 'Study library', 'academy.php?tab=settings' => 'Settings'];
    $h = '<div class="mem-tabs">';
    foreach ($t as $f => $l) $h .= '<a href="' . $f . '" class="' . ($active === $f ? 'active' : '') . '">' . $l . '</a>';
    return $h . '<a href="../academy.php" target="_blank">View the Academy ↗</a></div>';
}
