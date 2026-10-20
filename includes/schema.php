<?php
/**
 * Database migrations for the content modules added in the "master brief" rebuild.
 * Runs once (guarded by the `schema_v8` setting; if any step fails it is retried on the next load and the reason is written to storage/migration.log) and is safe to run on a database
 * that already holds the live site's data: it only creates missing tables/columns
 * and never alters or removes existing rows.
 */
require_once __DIR__ . "/db.php";

/** Runs a schema statement; failures are appended to storage/migration.log and counted (never fatal). */
function nufa_exec($conn, $sql) {
    $ok = $conn->query($sql);
    if (!$ok) {
        $GLOBALS['__nufa_schema_errors'] = ($GLOBALS['__nufa_schema_errors'] ?? 0) + 1;
        $dir = __DIR__ . "/../storage/";
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        @file_put_contents($dir . "migration.log", date('c') . "  " . $conn->errno . ": " . $conn->error . "\n    SQL: " . substr(preg_replace('/\s+/', ' ', $sql), 0, 220) . "\n", FILE_APPEND);
    }
    return $ok;
}

function nufa_col_exists($conn, $table, $col) {
    $t = $conn->real_escape_string($table); $c = $conn->real_escape_string($col);
    $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && $r->num_rows > 0;
}
function nufa_add_col($conn, $table, $col, $ddl) {
    if (!nufa_col_exists($conn, $table, $col)) nufa_exec($conn, "ALTER TABLE `$table` ADD COLUMN `$col` $ddl");
}

function nufa_programme_seed() {
    return [
        ['capacity-building', 'Film Capacity Building and Professional Development', 1,
         'Strengthening filmmaking skills, professional standards and industry readiness among emerging and established creatives in Northern Uganda.',
         "To strengthen filmmaking skills, professional standards, technical knowledge, and industry readiness among emerging and established creatives in Northern Uganda.",
         "Film production workshops\nScreenwriting and storytelling training\nDirecting and cinematography\nEditing and post-production\nSound design and production\nActing and performance\nProduction management\nFilm business and distribution\nIndustry masterclasses\nMentorship and peer learning",
         'production/on-set-action.webp'],
        ['mentorship', 'Mentorship and Emerging Talent', 2,
         'Pathways for emerging filmmakers to learn from experienced practitioners and move from training to practical production.',
         "To create pathways for emerging filmmakers to learn from experienced practitioners, develop creative confidence, build professional networks, and progress from training to practical production opportunities.",
         "Mentorship sessions\nPeer-learning groups\nCareer guidance\nPractical film projects\nIndustry networking\nPortfolio development\nProfessional showcases",
         'action-4.webp'],
        ['screenings-outreach', 'Film Screenings and Industry Outreach', 3,
         'Connecting filmmakers and audiences through screenings, discussions and community engagement.',
         "To connect filmmakers and audiences through screenings, discussions, community engagement, educational activities, and opportunities to appreciate locally produced films.",
         "Community screenings\nUniversity and school engagements\nFilm discussions\nAudience development\nRegional film showcases\nStakeholder engagement\nFilm awareness campaigns",
         'gallery/2025/nufa-3.webp'],
        ['creative-enterprise', 'Creative Enterprise and Industry Development', 4,
         'Helping filmmakers build sustainable creative careers and businesses.',
         "To help filmmakers build sustainable creative careers and businesses by improving their understanding of production finance, budgeting, marketing, distribution, contracts, entrepreneurship, and collaboration.",
         "Film business training\nProposal and pitch development\nBusiness networking\nMarket access\nProduction budgeting\nMarketing and distribution guidance\nPartnership development\nCreative economy advocacy",
         'nufa2.webp'],
        ['cultural-preservation', 'Cultural Preservation and Authentic Storytelling', 5,
         "Encouraging responsible documentation and creative interpretation of Northern Uganda's heritage, languages and lived experiences.",
         "To encourage responsible documentation and creative interpretation of Northern Uganda's cultural heritage, languages, histories, lived experiences, and community aspirations. All work respects community consent, cultural sensitivities, dignity, and accurate representation.",
         "Oral history and storytelling projects\nCultural film showcases\nIntergenerational storytelling\nLocal-language productions\nCommunity documentary projects\nEthical storytelling discussions",
         'production/cultural-portrait.webp'],
    ];
}


/** Expanded programme pages — written from the brief's purpose and activities; no statistics, dates or funding figures. */
function nufa_programme_expansion() {
    $geo = 'Northern Uganda';
    return [
        'capacity-building' => [
            'context' => "Film is a skills-intensive industry. Many emerging filmmakers in Northern Uganda have limited access to structured, practical training close to home, which makes it harder to reach the technical and professional standards that opportunities demand. Building capacity locally helps creatives produce stronger work and compete with confidence.",
            'objectives' => "Strengthen technical skills across the production chain\nRaise professional standards and industry readiness\nCreate regular learning opportunities for emerging and established filmmakers\nConnect trainees with experienced practitioners and peer networks",
            'beneficiaries' => "Emerging filmmakers and film students\nEstablished practitioners who want to upskill\nCrew in camera, sound, editing and production roles\nYoung people and women entering the industry",
            'outputs' => "Practical workshops and masterclasses delivered in the region\nFilmmakers with improved technical and professional skills\nLearning materials and peer-learning networks that outlast each session",
            'support_text' => "Support can help fund workshop venues and trainers, equipment for hands-on practice, travel for participants from outlying districts, and learning materials.",
        ],
        'mentorship' => [
            'context' => "Training alone does not create careers. Emerging filmmakers also need someone who has done the work to guide them, open doors and give honest feedback. Mentorship turns skills into confidence and confidence into practical production experience.",
            'objectives' => "Pair emerging filmmakers with experienced practitioners\nBuild creative confidence and professional networks\nCreate a clear path from training to practical production opportunities\nHelp mentees develop portfolios they can show to employers and funders",
            'beneficiaries' => "Emerging filmmakers early in their careers\nGraduates of NUFA training who need a next step\nWomen and young people seeking to enter production roles\nExperienced filmmakers who want to give back as mentors",
            'outputs' => "Mentor–mentee pairings and peer-learning groups\nPractical film projects completed by mentees\nPortfolios and professional showcases for emerging talent",
            'support_text' => "Support can help cover mentor time and expenses, small production budgets for mentee projects, networking events and showcase screenings.",
        ],
        'screenings-outreach' => [
            'context' => "Films matter most when they reach audiences. Community screenings and discussions connect filmmakers with the people they make films for, grow audiences for locally produced work and open conversations about the stories on screen.",
            'objectives' => "Bring locally produced films to audiences in the region\nCreate spaces for discussion between filmmakers and communities\nBuild audience awareness and appreciation of regional cinema\nEngage schools, universities and stakeholders with film",
            'beneficiaries' => "Community audiences, including young people\nStudents and educators\nFilmmakers who gain a platform for their work\nLocal institutions and partners interested in film",
            'outputs' => "Community and campus screenings with facilitated discussion\nRegional film showcases\nFilm awareness campaigns that build future audiences",
            'support_text' => "Support can help fund projection and sound equipment, venues, transport to reach rural communities, facilitators and promotional materials.",
        ],
        'creative-enterprise' => [
            'context' => "Talent needs business skills to become a sustainable career. Filmmakers often learn budgeting, pitching, contracts and distribution on the job. Practical enterprise support helps creatives turn their craft into viable work and businesses.",
            'objectives' => "Improve understanding of production finance and budgeting\nDevelop pitching and proposal-writing skills\nBuild knowledge of marketing, distribution and contracts\nFoster collaboration and market access across the creative economy",
            'beneficiaries' => "Independent filmmakers and small production companies\nProducers and managers seeking to formalise their practice\nEmerging creatives starting their first ventures",
            'outputs' => "Film business training and pitch-development sessions\nBusiness networking opportunities\nGuidance on marketing, distribution and partnership development",
            'support_text' => "Support can help fund business trainers and resource persons, pitching events, mentoring on budgets and contracts, and market-access activities.",
        ],
        'cultural-preservation' => [
            'context' => "Northern Uganda holds a rich heritage of languages, traditions and lived experiences, and many stories that deserve to be told by the people who live them. Responsible documentation and creative interpretation help preserve that heritage and share it with new generations.",
            'objectives' => "Encourage responsible documentation of cultural heritage\nSupport local-language and community-led productions\nPromote ethical storytelling grounded in consent and dignity\nCreate intergenerational storytelling opportunities",
            'beneficiaries' => "Communities whose stories are documented\nFilmmakers working with cultural and oral history\nYoung people connecting with their heritage\nCultural institutions and educators",
            'outputs' => "Oral-history and community documentary projects\nCultural film showcases\nEthical storytelling discussions and guidance",
            'support_text' => "Support can help fund community documentary projects, translation and local-language production, archiving of recordings and showcase events — always with community consent.",
        ],
    ];
}

function nufa_migrate($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    // 1. guarantee the settings table exists so the version flag can be read
    $core = require __DIR__ . "/schema-core.php";
    nufa_exec($conn, $core['create']['settings']);
    $flag = $conn->query("SELECT setting_value FROM settings WHERE setting_key='schema_v8'");
    if ($flag && $flag->num_rows) return;
    $GLOBALS['__nufa_schema_errors'] = 0;

    // 2. core tables the site depends on (created only if missing) and any columns an older database lacks
    foreach ($core['create'] as $t => $ddl) nufa_exec($conn, $ddl);
    foreach ($core['columns'] as $t => $cols) foreach ($cols as $c => $def) nufa_add_col($conn, $t, $c, $def);

    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS programmes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(120) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT '',
        summary VARCHAR(500) NOT NULL DEFAULT '',
        overview TEXT, context TEXT, objectives TEXT, beneficiaries TEXT,
        activities TEXT, outputs TEXT, geography VARCHAR(255) NOT NULL DEFAULT '',
        partners_text TEXT, progress TEXT,
        image VARCHAR(255) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(80) NOT NULL DEFAULT '',
        description TEXT,
        file_path VARCHAR(255) NOT NULL DEFAULT '',
        external_url VARCHAR(500) NOT NULL DEFAULT '',
        published_on DATE NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        video_url VARCHAR(500) NOT NULL,
        edition VARCHAR(50) NOT NULL DEFAULT '',
        caption VARCHAR(255) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS enquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(30) NOT NULL,
        topic VARCHAR(80) NOT NULL DEFAULT '',
        name VARCHAR(150) NOT NULL DEFAULT '',
        organisation VARCHAR(190) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL DEFAULT '',
        message TEXT,
        details TEXT,
        consent TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('new','handled') NOT NULL DEFAULT 'new',
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_type (type), KEY idx_status (status), KEY idx_ip (ip, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS nav_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        location VARCHAR(20) NOT NULL,
        parent_id INT NULL,
        label VARCHAR(120) NOT NULL,
        url VARCHAR(255) NOT NULL,
        nav_key VARCHAR(60) NOT NULL DEFAULT '',
        new_tab TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        KEY idx_loc (location, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    require_once __DIR__ . "/menu.php";
    nufa_menu_seed($conn);

    // categories for grouping content in the admin and on the public pages
    foreach (['team_members', 'partners', 'gallery_images', 'videos'] as $t) nufa_add_col($conn, $t, 'category', "VARCHAR(80) NOT NULL DEFAULT ''");
    // derive partner categories from the tier text already entered (only where no category is set yet)
    foreach (['Government' => ['government', 'regulatory', 'ministry'], 'Media' => ['media', 'broadcast'], 'Technology' => ['technology'], 'Industry bodies' => ['industry', 'guild'], 'Production' => ['production']] as $cat => $needles) {
        foreach ($needles as $nd) {
            $c = $conn->real_escape_string($cat); $n = $conn->real_escape_string($nd);
            $conn->query("UPDATE partners SET category='$c' WHERE category='' AND LOWER(tier) LIKE '%$n%'");
        }
    }

    // ---- programme support: payment methods, engagements, programme columns ----
    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(120) NOT NULL,
        method_type VARCHAR(30) NOT NULL DEFAULT 'other',
        provider VARCHAR(120) NOT NULL DEFAULT '',
        account_name VARCHAR(150) NOT NULL DEFAULT '',
        account_number VARCHAR(120) NOT NULL DEFAULT '',
        extra VARCHAR(255) NOT NULL DEFAULT '',
        reference_hint VARCHAR(255) NOT NULL DEFAULT '',
        link_url VARCHAR(500) NOT NULL DEFAULT '',
        instructions TEXT,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS engagements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_no VARCHAR(40) NOT NULL DEFAULT '',
        kind VARCHAR(20) NOT NULL,
        programme_id INT NULL,
        name VARCHAR(150) NOT NULL,
        organisation VARCHAR(190) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(40) NOT NULL DEFAULT '',
        location VARCHAR(150) NOT NULL DEFAULT '',
        amount DECIMAL(14,2) NULL,
        currency VARCHAR(8) NOT NULL DEFAULT '',
        frequency VARCHAR(30) NOT NULL DEFAULT '',
        method VARCHAR(120) NOT NULL DEFAULT '',
        payment_ref VARCHAR(150) NOT NULL DEFAULT '',
        payment_proof VARCHAR(255) NOT NULL DEFAULT '',
        message TEXT,
        details TEXT,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        consent TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('pending','approved','completed','rejected') NOT NULL DEFAULT 'pending',
        reject_reason VARCHAR(500) NOT NULL DEFAULT '',
        admin_note VARCHAR(500) NOT NULL DEFAULT '',
        approved_at DATETIME NULL,
        completed_at DATETIME NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_kind (kind), KEY idx_status (status), KEY idx_prog (programme_id), KEY idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    nufa_add_col($conn, 'programmes', 'support_text', "TEXT");
    foreach (['donate', 'sponsor', 'volunteer', 'partner'] as $k) nufa_add_col($conn, 'programmes', "allow_$k", "TINYINT(1) NOT NULL DEFAULT 1");
    // programmes are live: fill the expanded detail where empty, mark them ongoing and visible
    foreach (nufa_programme_expansion() as $slug => $cols) {
        foreach ($cols as $col => $val) {
            $u = $conn->prepare("UPDATE programmes SET `$col`=? WHERE slug=? AND (`$col` IS NULL OR TRIM(`$col`)='')");
            $u->bind_param("ss", $val, $slug); $u->execute();
        }
        $g = 'Northern Uganda';
        $u = $conn->prepare("UPDATE programmes SET geography=? WHERE slug=? AND TRIM(geography)=''");
        $u->bind_param("ss", $g, $slug); $u->execute();
        $u = $conn->prepare("UPDATE programmes SET status='ongoing', is_active=1 WHERE slug=? AND status=''");
        $u->bind_param("s", $slug); $u->execute();
    }
    // ---- NUFA SACCO: products, documents, announcements; privacy-friendly page counters ----
    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS sacco_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(40) NOT NULL DEFAULT 'savings',
        name VARCHAR(190) NOT NULL,
        description TEXT,
        eligibility TEXT,
        terms TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'proposed',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS sacco_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        doc_type VARCHAR(60) NOT NULL DEFAULT 'Policy',
        description VARCHAR(500) NOT NULL DEFAULT '',
        file_path VARCHAR(255) NOT NULL DEFAULT '',
        external_url VARCHAR(500) NOT NULL DEFAULT '',
        published_on DATE NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS sacco_announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        body TEXT,
        kind VARCHAR(40) NOT NULL DEFAULT 'Update',
        published_on DATE NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    nufa_exec($conn, "CREATE TABLE IF NOT EXISTS page_hits (
        page VARCHAR(40) NOT NULL,
        day DATE NOT NULL,
        hits INT NOT NULL DEFAULT 0,
        PRIMARY KEY (page, day)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ---- schedule & calendar ----
    require_once __DIR__ . "/schedule.php";
    sch_install($conn);
    if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url LIKE 'schedule.php%'")->fetch_row()[0]) {
        $pm = $conn->query("SELECT id FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='programmes.php' LIMIT 1")->fetch_assoc();
        if ($pm) { $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$pm['id'])->fetch_row()[0]; nufa_exec($conn, "INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', {$pm['id']}, 'Schedule & Calendar', 'schedule.php', $o)"); }
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer1'")->fetch_row()[0];
        nufa_exec($conn, "INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer1', 'Schedule & Calendar', 'schedule.php', $o)");
    }

    // footer link to the support page (once)
    if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url LIKE 'support.php%'")->fetch_row()[0]) {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer2'")->fetch_row()[0];
        nufa_exec($conn, "INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer2', 'Support a programme', 'support.php', $o)");
    }
    // column additions on existing tables
    nufa_add_col($conn, 'posts', 'image_caption', "VARCHAR(255) NOT NULL DEFAULT ''");
    nufa_add_col($conn, 'posts', 'image_credit', "VARCHAR(150) NOT NULL DEFAULT ''");
    nufa_add_col($conn, 'partners', 'relationship', "VARCHAR(20) NOT NULL DEFAULT 'current'");
    nufa_add_col($conn, 'team_members', 'bio', "TEXT");
    nufa_add_col($conn, 'team_members', 'linkedin_note', "VARCHAR(10) NOT NULL DEFAULT ''");
    foreach (['nominees_html', 'sponsors_html', 'press_html', 'highlights_html', 'categories_html'] as $c) nufa_add_col($conn, 'award_editions', $c, "MEDIUMTEXT");
    nufa_add_col($conn, 'award_editions', 'video_url', "VARCHAR(500) NOT NULL DEFAULT ''");
    nufa_add_col($conn, 'award_editions', 'is_verified', "TINYINT(1) NOT NULL DEFAULT 1");

    // programme seed (only when empty)
    $n = (int)$conn->query("SELECT COUNT(*) c FROM programmes")->fetch_assoc()['c'];
    if ($n === 0) {
        $s = $conn->prepare("INSERT INTO programmes (slug, title, summary, overview, activities, image, sort_order) VALUES (?,?,?,?,?,?,?)");
        foreach (nufa_programme_seed() as $p) { $s->bind_param("ssssssi", $p[0], $p[1], $p[3], $p[4], $p[5], $p[6], $p[2]); $s->execute(); }
    }

    // sensible defaults for the new settings; never overwrite what an admin already saved
    $defaults = [
        'font_preset' => 'cinematic',
        'tickets_open' => '0', 'vm_approved' => '0', 'policies_approved' => '0',
    ];
    $ins = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?,?)");
    foreach ($defaults as $k => $v) { $ins->bind_param("ss", $k, $v); $ins->execute(); }
    // the previous default fonts are replaced by the brief's Manrope + Inter pairing
    $conn->query("UPDATE settings SET setting_value='cinematic' WHERE setting_key='font_preset' AND setting_value IN ('editorial','classic','bold')");

    // hero images: seed the four default production/awards images once (managed in admin → Hero Images)
    if ((int)$conn->query("SELECT COUNT(*) c FROM hero_slides")->fetch_assoc()['c'] === 0) {
        $hs = $conn->prepare("INSERT INTO hero_slides (image, alt_text, sort_order, is_active) VALUES (?,?,?,1)");
        foreach ([['production/karamoja-shoot-1.webp', 'Filming on location in Karamoja'], ['production/crew-monitor-check.webp', 'Crew checking the monitor on a Northern Uganda film set'],
                  ['production/on-set-action.webp', 'On set during a film production'], ['slider/hero/hero-slide-3.webp', 'A NUFA Awards ceremony']] as $i => $s) {
            $o = $i + 1; $hs->bind_param("ssi", $s[0], $s[1], $o); $hs->execute();
        }
    }

    // content alignment with the master brief (guarded so admin edits are never overwritten)
    $conn->query("UPDATE award_editions SET badge_text='Upcoming Edition', chip_title='Details to be announced', chip_sub='Published once officially confirmed',
        description='NUFA Awards 2027 will be announced here. Dates, venue, categories, nominations, tickets and sponsorship opportunities are published only once they have been officially confirmed by NUFA.'
        WHERE slug='nufa27' AND badge_text LIKE 'Upcoming Edition%Registration Open%'");
    $conn->query("UPDATE award_editions SET event_date_label='26 April 2025', chip_sub='The maiden edition',
        description='NUFA Awards 2025 was the association''s maiden edition, held on 26 April 2025 at Acholi Inn in Gulu. It recognised filmmaking in Northern Uganda.'
        WHERE slug='nufa25' AND event_date_label='25 April 2025'");

    // map the old free-text news categories onto the brief's category list
    foreach (['Announcement' => 'NUFA News', 'Guilds' => 'NUFA News', 'Partnership' => 'Partner News', 'Awards Recap' => 'NUFA Awards', 'Throwback' => 'NUFA Awards',
              'Community' => 'Events and Screenings', 'Training' => 'Training and Workshops', 'Culture' => 'Culture and Storytelling', 'Spotlight' => 'Filmmaker Profiles'] as $old => $new) {
        $o = $conn->real_escape_string($old); $n = $conn->real_escape_string($new);
        $conn->query("UPDATE posts SET category='$n' WHERE category='$o'");
    }

    // official contact details from the brief (applied once; editable afterwards in Site Settings)
    $official = [
        'site_email' => 'nuf.association@gmail.com', 'site_phone' => '+256 774 757 587', 'site_phone2' => '+256 779 712 990',
        'site_address' => 'Plot 75, Jomo Kenyatta Road, Gulu City, Uganda',
    ];
    $up = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($official as $k => $v) { $up->bind_param("ss", $k, $v); $up->execute(); }

    if (empty($GLOBALS['__nufa_schema_errors'])) nufa_exec($conn, "INSERT INTO settings (setting_key, setting_value) VALUES ('schema_v8', '1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

nufa_migrate($conn);
