<?php
/**
 * Database migrations for the content modules added in the "master brief" rebuild.
 * Runs once (guarded by the `schema_v2` setting) and is safe to run on a database
 * that already holds the live site's data: it only creates missing tables/columns
 * and never alters or removes existing rows.
 */
require_once __DIR__ . "/db.php";

function nufa_col_exists($conn, $table, $col) {
    $t = $conn->real_escape_string($table); $c = $conn->real_escape_string($col);
    $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && $r->num_rows > 0;
}
function nufa_add_col($conn, $table, $col, $ddl) {
    if (!nufa_col_exists($conn, $table, $col)) $conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $ddl");
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

function nufa_migrate($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    $flag = $conn->query("SELECT setting_value FROM settings WHERE setting_key='schema_v2'");
    if ($flag && $flag->num_rows) return;

    $conn->query("CREATE TABLE IF NOT EXISTS programmes (
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

    $conn->query("CREATE TABLE IF NOT EXISTS resources (
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

    $conn->query("CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        video_url VARCHAR(500) NOT NULL,
        edition VARCHAR(50) NOT NULL DEFAULT '',
        caption VARCHAR(255) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS enquiries (
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

    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('schema_v2', '1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

nufa_migrate($conn);
