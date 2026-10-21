<?php
/**
 * Opportunities for filmmakers: film awards and festivals, funding (Uganda and international), labs and training.
 * Managed in admin → Opportunities. Every entry links to the organiser's official page; NUFA only lists them.
 */

function opp_categories() {
    return [
        'award'     => 'Film awards & festivals',
        'fund_ug'   => 'Funding · Uganda',
        'fund_intl' => 'Funding · International',
        'lab'       => 'Labs, markets & training',
        'other'     => 'Other opportunities',
    ];
}
function opp_statuses() { return ['open' => 'Open for applications', 'rolling' => 'Rolling / ongoing', 'check' => 'Check official page', 'closed' => 'Closed for now']; }

function opp_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS opportunities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(20) NOT NULL DEFAULT 'other',
        title VARCHAR(190) NOT NULL,
        organisation VARCHAR(190) NOT NULL DEFAULT '',
        region VARCHAR(120) NOT NULL DEFAULT '',
        summary TEXT,
        eligibility TEXT,
        funding VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(10) NOT NULL DEFAULT 'check',
        deadline DATE NULL,
        deadline_note VARCHAR(255) NOT NULL DEFAULT '',
        apply_url VARCHAR(500) NOT NULL DEFAULT '',
        source_url VARCHAR(500) NOT NULL DEFAULT '',
        verified_on DATE NULL,
        is_featured TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_cat (category, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // seed once, so the admin can later edit or delete entries without them coming back
    $s = $conn->query("SELECT setting_value FROM settings WHERE setting_key='opp_seeded'");
    if ($s && !$s->num_rows) {
        $ins = $conn->prepare("INSERT INTO opportunities (category,title,organisation,region,summary,eligibility,funding,status,deadline,deadline_note,apply_url,source_url,verified_on,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach (opp_seed() as $i => $o) {
            $dl = $o['deadline'] ?: null; $v = '2026-10-21'; $n = $i + 1;
            $ins->bind_param("sssssssssssssi", $o['category'], $o['title'], $o['org'], $o['region'], $o['summary'], $o['eligibility'], $o['funding'], $o['status'], $dl, $o['note'], $o['url'], $o['source'], $v, $n);
            $ins->execute();
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('opp_seeded','1')");
    }

    // menu: Resources → Opportunities (once, so the admin can remove it later)
    $m = $conn->query("SELECT setting_value FROM settings WHERE setting_key='opp_menu_added'");
    if ($m && !$m->num_rows) {
        $p = $conn->query("SELECT id, nav_key FROM nav_items WHERE location='header' AND parent_id IS NULL AND url='resources.php' LIMIT 1")->fetch_assoc();
        if ($p) {
            if (strpos($p['nav_key'], 'opportunities') === false) { $k = trim($p['nav_key'] . ',opportunities', ','); $conn->query("UPDATE nav_items SET nav_key='" . $conn->real_escape_string($k) . "' WHERE id=" . (int)$p['id']); }
            if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='opportunities.php' AND location='header'")->fetch_row()[0]) {
                $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE parent_id=" . (int)$p['id'])->fetch_row()[0];
                $conn->query("INSERT INTO nav_items (location, parent_id, label, url, sort_order) VALUES ('header', " . (int)$p['id'] . ", 'Opportunities', 'opportunities.php', $o)");
            }
        }
        if (!(int)$conn->query("SELECT COUNT(*) FROM nav_items WHERE url='opportunities.php' AND location='footer1'")->fetch_row()[0]) {
            $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM nav_items WHERE location='footer1'")->fetch_row()[0];
            $conn->query("INSERT INTO nav_items (location, label, url, sort_order) VALUES ('footer1', 'Opportunities', 'opportunities.php', $o)");
        }
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('opp_menu_added','1')");
    }
}

/** Effective status: a dated call that has passed shows as closed, whatever the admin chose. */
function opp_status($o) {
    if (!empty($o['deadline']) && $o['deadline'] < date('Y-m-d')) return 'closed';
    return isset(opp_statuses()[$o['status']]) ? $o['status'] : 'check';
}

/** Starting list, compiled from the organisers' own pages and published calls (checked 21 Oct 2026). Dates change — each entry points to the official source. */
function opp_seed() {
    $d = 'film-resource-africa.com/opportunities/';
    return [
        ['category' => 'award', 'title' => 'Uganda Film Festival & Awards', 'org' => 'Uganda Communications Commission (UCC)', 'region' => 'Uganda',
         'summary' => 'Uganda\'s annual national film festival and awards, run by UCC. It includes workshops and forums on technical skills, distribution and commercialisation of local content, and ends with a gala night. The 2026 edition has already taken place; entries for the next edition are announced by UCC.',
         'eligibility' => "Made for Ugandan filmmakers — the vast majority of entries come from Ugandans\nA 2018 call required films to have been classified and rated as Ugandan law requires — confirm whether this still applies\nEntries were handed in at UCC House, Bugolobi, and UCC regional offices in past years\nRules, categories and fees are published with each call", 'funding' => 'Awards and recognition', 'status' => 'check', 'deadline' => '', 'note' => 'No call for the next edition seen yet — watch UCC\'s website and social media.',
         'url' => 'https://www.ucc.co.ug/uganda-film-festival-26/', 'source' => 'https://www.ucc.co.ug/uganda-film-festival-26/'],
        ['category' => 'fund_ug', 'title' => 'Content Development Support Programme (CDSP)', 'org' => 'Uganda Communications Commission (UCC)', 'region' => 'Uganda',
         'summary' => 'UCC\'s support programme for local film and television content. Categories include feature films, TV dramas, documentaries, animation and short films, with priority to content that promotes Ugandan culture and tourism. Support runs on a recoupment basis where applicable and must be raised and used within one financial year.',
         'eligibility' => "Ugandan production companies and individuals; applicants must be Ugandan citizens\nCompanies must be primarily engaged in film and television\nNot eligible: corporate videos, NGOs and institutions not primarily in film/TV, and training focused mainly on performing arts\nDocuments: application form and letter, synopsis or script, company registration or National ID copy, proof of project ownership, CVs, budget and marketing strategy, co-production agreements if any\nJudged on originality and relevance to Ugandan values, team, feasibility of the budget and plan, marketing strategy and likely impact on the industry",
         'funding' => 'Amounts set per call (UCC granted Shs 1.2 billion to 14 projects in 2023)', 'status' => 'check', 'deadline' => '', 'note' => 'UCC lists a CDSP-III form for 2024–2025 but no open/closed status — contact UCC to confirm the current call.',
         'url' => 'https://www.ucc.co.ug/content-development-support-programme-cdsp/', 'source' => 'https://www.ucc.co.ug/content-development-support-programme-cdsp/'],
        ['category' => 'fund_intl', 'title' => 'Docubox East African Documentary Film Fund', 'org' => 'Docubox', 'region' => 'East Africa',
         'summary' => 'Fund and lab for East African filmmakers. The 2026 Short Fiction Development Grant and Lab selected five filmmakers from Kenya, Uganda, Tanzania and Rwanda, with €2,500 for each.',
         'eligibility' => "Filmmakers from East Africa (Kenya, Uganda, Tanzania, Rwanda have all been selected)\nSeparate calls exist for documentaries and for short fiction — read the call for the exact stage and format\nMentorship and a lab accompany the grants", 'funding' => 'Development €2,500 per filmmaker (2026 short fiction round); other rounds differ', 'status' => 'check', 'deadline' => '', 'note' => 'Calls are announced on Docubox\'s website and social media.',
         'url' => 'https://www.docubox.org/', 'source' => 'https://www.sinemafocus.com/docubox-short-films-grant-east-africa-winners-2026/'],
        ['category' => 'fund_intl', 'title' => 'IDFA Bertha Fund', 'org' => 'International Documentary Film Festival Amsterdam (IDFA)', 'region' => 'Africa, Asia, Latin America & more',
         'summary' => 'Supports visionary documentary projects from supported countries in Africa, Asia, Eastern Europe, Latin America, the Caribbean and Oceania. IBF Classic backs development and (post-)production; IBF Europe backs minority co-productions.',
         'eligibility' => "Documentary projects from the fund\'s list of supported countries — check that your country is on the list\nIBF Classic: development and (post-)production support\nIBF Europe: minority co-production support\nGuidelines, country list and deadlines are on the IDFA professionals site", 'funding' => 'See the IBF Classic and IBF Europe pages', 'status' => 'check', 'deadline' => '', 'note' => 'Check the fund pages for current deadlines.',
         'url' => 'https://professionals.idfa.nl/training-funding/funding', 'source' => 'https://professionals.idfa.nl/training-funding/funding'],
        ['category' => 'fund_intl', 'title' => 'Hubert Bals Fund (HBF)', 'org' => 'International Film Festival Rotterdam (IFFR)', 'region' => 'Africa, Asia, Latin America, Middle East & more',
         'summary' => 'IFFR\'s fund for filmmakers from listed countries, supporting projects from script development to post-production. HBF+Europe backs co-productions (€60,000 per project, eight projects a year).',
         'eligibility' => "Director, writer and main producer should live and work in one of the listed eligible countries (diaspora and dual-national filmmakers can qualify in some cases)\nProject should be mainly set and shot in an eligible country\nDesigned for feature-length fiction (over 60 minutes); documentaries only with a strong artistic approach\nApplication materials and subtitles in English", 'funding' => 'Development, production and post-production grants; HBF+Europe €60,000', 'status' => 'check', 'deadline' => '', 'note' => 'Read the FAQ and the list of eligible countries before applying.',
         'url' => 'https://iffr.com/en/iffr-pro-faq/hbf-general', 'source' => 'https://iffr.com/en/iffr-pro-faq/hbf-general'],
        ['category' => 'fund_intl', 'title' => 'Hot Docs–Blue Ice Docs Fund', 'org' => 'Hot Docs & Blue Ice Group', 'region' => 'Africa',
         'summary' => 'Fund for African documentary filmmakers, offering development and production grants.',
         'eligibility' => "African filmmakers with documentary projects\nDevelopment grants up to CAD 10,000\nProduction grants up to CAD 40,000\nIt was listed as closed when we last checked — look for the next call", 'funding' => 'Development up to CAD 10,000; production up to CAD 40,000', 'status' => 'closed', 'deadline' => '', 'note' => 'Applications were closed on 21 Oct 2026; the next cycle is announced by the funder.',
         'url' => 'https://' . $d . 'hot-docs-blue-ice-docs-fund-african-documentary', 'source' => 'https://film-resource-africa.com/directory'],
        ['category' => 'fund_intl', 'title' => 'Red Sea Fund', 'org' => 'Red Sea Film Foundation', 'region' => 'Global — focus on Africa & Middle East',
         'summary' => 'Development, production and post-production funding, open globally with a strong focus on African and Middle Eastern filmmakers.',
         'eligibility' => "Open to filmmakers worldwide, with a strong focus on Africa and the Middle East\nCovers development, production and post-production\nFunding cycles run four times a year", 'funding' => 'Amount depends on stage — see the fund page', 'status' => 'rolling', 'deadline' => '', 'note' => 'Four cycles a year.',
         'url' => 'https://' . $d . 'red-sea-fund-development-production-and-post-production-funding', 'source' => 'https://film-resource-africa.com/directory'],
        ['category' => 'fund_intl', 'title' => 'Kekere Storytellers Fund', 'org' => 'Africa No Filter', 'region' => 'Africa',
         'summary' => 'Fund for African storytellers: short films, feature films, documentaries, digital storytelling and youth-led content.',
         'eligibility' => "African storytellers\nReported amounts: short films up to US$5,000 and feature films up to US$10,000\nConfirm the current format, categories and dates with the funder", 'funding' => 'Reported: shorts up to $5,000; features up to $10,000', 'status' => 'check', 'deadline' => '', 'note' => 'Amounts are as reported by a directory — verify with the funder.',
         'url' => 'https://arrayastudios.com/african-film-grants/', 'source' => 'https://arrayastudios.com/african-film-grants/'],
        ['category' => 'fund_intl', 'title' => 'EAccelerate Fund', 'org' => 'East Africa Screen Collective & DW Akademie', 'region' => 'East Africa',
         'summary' => 'Fund for East African filmmakers; a Ugandan filmmaker was among four East Africans selected in 2024.',
         'eligibility' => "East African filmmakers\nThe call sets out the exact eligibility and format — follow the link and check each round", 'funding' => 'Set per round', 'status' => 'check', 'deadline' => '', 'note' => 'Follow the East Africa Screen Collective for new rounds.',
         'url' => 'https://arrayastudios.com/african-film-grants/', 'source' => 'https://susinsight.com/uganda-film-industry-funding'],
        ['category' => 'lab', 'title' => 'Rotterdam Lab 2027 — Africa & MENA open call', 'org' => 'International Film Festival Rotterdam', 'region' => 'Africa & MENA',
         'summary' => 'A lab for emerging producers, with an open call for Africa and the Middle East & North Africa.',
         'eligibility' => "Emerging producers, with a focus on Africa and MENA\nApplications close 26 October 2026 at 23:59 (check the time zone on the call)", 'funding' => 'Lab participation', 'status' => 'open', 'deadline' => '2026-10-26', 'note' => '',
         'url' => 'https://' . $d . 'rotterdam-lab-2027-africa-and-mena-open-call', 'source' => 'https://film-resource-africa.com/directory'],
        ['category' => 'lab', 'title' => 'DOCanarias Market 2027 — documentary project call', 'org' => 'DOCanarias', 'region' => 'Global South — special consideration for Africa',
         'summary' => 'A market for documentary projects seeking financing. Projects from the Global South are eligible, with special consideration for Africa.',
         'eligibility' => "Documentary projects looking for financing\nGlobal South projects eligible, with special consideration for Africa\nCall closes 26 October 2026", 'funding' => 'Market access and pitching', 'status' => 'open', 'deadline' => '2026-10-26', 'note' => '',
         'url' => 'https://' . $d . 'docanarias-market-2027-documentary-project-call', 'source' => 'https://film-resource-africa.com/directory'],
        ['category' => 'lab', 'title' => 'Talents Durban', 'org' => 'Durban FilmMart Institute', 'region' => 'Africa & diaspora',
         'summary' => 'The official African satellite programme of Berlinale Talents, held during the Durban FilmMart. The 2026 edition (9–12 October) received 551 applications and selected 32 participants.',
         'eligibility' => "Emerging film professionals and critics from the African continent and diaspora\n2026 call: a minimum of two completed professional projects\nEarlier calls asked for African citizenship and age 18+ — each call sets its own rules, so read it first", 'funding' => 'Programme participation', 'status' => 'check', 'deadline' => '', 'note' => 'The call for the next edition usually opens early in the year — confirm with the organiser.',
         'url' => 'https://durbanfilmmart.co.za/', 'source' => 'https://durbanfilmmart.co.za/the-durban-filmmart-institute-dfmi-announces-call-for-submissions-for-19th-edition-of-talents-durban/'],
        ['category' => 'award', 'title' => 'Africa Movie Academy Awards (AMAA)', 'org' => 'Africa Film Academy', 'region' => 'Africa & diaspora',
         'summary' => 'A pan-African film awards event. The latest cycle took submissions through FilmFreeway from 1 December 2025 to 31 May 2026.',
         'eligibility' => "Films produced or released by African or Black filmmakers (or in collaboration with them) in the cycle\'s window — July 2025 onwards for the latest cycle\nSubmitted through FilmFreeway by an authorised representative of the film\nFeatures up to 120 minutes; shorts up to 40 minutes\nNon-English films fully subtitled (including pidgin); no sequels, only standalone works\nSubmitter must be the copyright owner or hold a signed letter of consent", 'funding' => 'Awards and recognition', 'status' => 'closed', 'deadline' => '', 'note' => 'The 2025/26 cycle closed on 31 May 2026. Dates and rules change each year.',
         'url' => 'https://filmfreeway.com/festival/africamovieacademyawards-882089', 'source' => 'https://filmfreeway.com/festival/africamovieacademyawards-882089'],
        ['category' => 'award', 'title' => 'Zanzibar International Film Festival (ZIFF)', 'org' => 'ZIFF', 'region' => 'Africa, diaspora & Dhow countries',
         'summary' => 'East Africa\'s oldest film festival. It is open to African films, including the diaspora, and films from the Dhow countries region (South East Asia, Arabian Peninsula, Iran, Pakistan, India and the Indian Ocean islands).',
         'eligibility' => "African filmmakers, the African diaspora and Dhow-region filmmakers\n2024 call: films produced after 1 January 2022\nA press kit (technical and artistic data, English synopsis, photographs, posters) and a director biography were required\nRules change by year — read the current call", 'funding' => 'Screening and awards', 'status' => 'check', 'deadline' => '', 'note' => 'No call for the next edition seen yet.',
         'url' => 'https://festhome.com/f/893', 'source' => 'https://dailynews.co.tz/ziff-calls-for-films-submission/'],
        ['category' => 'award', 'title' => 'Academy Awards — International Feature Film', 'org' => 'Academy of Motion Picture Arts and Sciences', 'region' => 'Global',
         'summary' => 'Each country has a selection committee that submits films for the International Feature Film category. The Academy publishes the rules, FAQs and committee requirements.',
         'eligibility' => "Films are submitted through a country\'s official selection committee, not directly by filmmakers\nRead the International Feature Film rules and the selection committee requirements\nTalk to your national committee early about their submission process", 'funding' => 'Recognition', 'status' => 'check', 'deadline' => '', 'note' => '',
         'url' => 'https://www.oscars.org/oscars/rules-eligibility', 'source' => 'https://www.oscars.org/oscars/rules-eligibility'],
        ['category' => 'other', 'title' => 'AU–EU Youth Voices Lab — Lens of Change', 'org' => 'AU–EU Youth Voices Lab', 'region' => 'Africa & Europe',
         'summary' => 'An open call for young filmmakers across Africa and Europe, with small project grants.',
         'eligibility' => "Emerging filmmakers across Africa and Europe\nThe Youth Voices Lab is for creatives aged 18–35\nProject funding of €765 to €1,800 per project", 'funding' => '€765 – €1,800 per project', 'status' => 'rolling', 'deadline' => '', 'note' => 'Listed as rolling — confirm before applying.',
         'url' => 'https://' . $d . 'lens-of-change-open-call-for-young-filmmakers-au-eu-youth-voices-lab', 'source' => 'https://film-resource-africa.com/directory'],
        ['category' => 'other', 'title' => 'VII Foundation Film Lab', 'org' => 'VII Foundation', 'region' => 'Africa, Asia & Latin America',
         'summary' => 'Fully funded filmmaking fellowships for underserved students.',
         'eligibility' => "Underserved students across Africa, Asia and Latin America\nFully funded fellowship", 'funding' => 'Fully funded', 'status' => 'rolling', 'deadline' => '', 'note' => 'Listed as rolling — confirm before applying.',
         'url' => 'https://' . $d . 'vii-foundation-film-lab-fully-funded-filmmaking-fellowships', 'source' => 'https://film-resource-africa.com/directory'],
    ];
}
