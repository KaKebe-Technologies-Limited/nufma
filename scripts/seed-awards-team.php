<?php
/**
 * One-time seed: populates award_editions / award_winners / team_members
 * with the exact copy the site shipped with, so switching awards.php and
 * about.php over to the database doesn't change anything visually.
 *
 * Safe to re-run: skips any table that already has rows.
 * Usage: C:\xampp\php\php.exe scripts\seed-awards-team.php
 */
require __DIR__ . "/../includes/db.php";

function already_seeded($conn, $table) {
    $r = $conn->query("SELECT COUNT(*) c FROM $table")->fetch_assoc();
    return (int)$r['c'] > 0;
}

// ---------------------------------------------------------------- editions
if (already_seeded($conn, 'award_editions')) {
    echo "award_editions already has rows — skipping.\n";
} else {
    $editions = [
        [
            'slug' => 'nufa27', 'year' => 2027, 'status' => 'upcoming', 'sort_order' => 1,
            'badge_text' => 'Upcoming Edition · Registration Open',
            'heading_html' => 'NUFA Awards <span class="serif">2027</span>',
            'theme_quote' => '',
            'description' => "The next chapter is being written. NUFA27 will bring an even bigger red carpet to Gulu — with new categories, more districts represented, and room for new partners to stand alongside us.",
            'image' => 'gallery/2026/nufa26-15.jpg',
            'event_date_label' => 'Date to be announced',
            'location_label' => 'Gulu, Northern Uganda',
            'chip_title' => 'Save the Date', 'chip_sub' => 'Nominations open soon',
            'stat1_value'=>'','stat1_label'=>'','stat2_value'=>'','stat2_label'=>'','stat3_value'=>'','stat3_label'=>'','stat4_value'=>'','stat4_label'=>'',
            'masonry1'=>'','masonry2'=>'','masonry3'=>'','masonry4'=>'',
            'extra_html' => '',
        ],
        [
            'slug' => 'nufa26', 'year' => 2026, 'status' => 'past', 'sort_order' => 2,
            'badge_text' => 'Past Edition',
            'heading_html' => 'NUFA Awards <span class="serif">2026</span>',
            'theme_quote' => '"Stories That Redefine Us"',
            'description' => "The second edition of the NUFA Awards returned to the Acholi Inn on 2 May 2026 — a fuller red carpet, a growing list of partners, and Northern Uganda's creatives dressed for the occasion. Submissions grew 31% year on year, from 70 films in 2025 to 92 in 2026.",
            'image' => 'gallery/2026/nufa26-04.jpg',
            'event_date_label' => '2 May 2026',
            'location_label' => 'Acholi Inn, Gulu',
            'chip_title' => 'NUFA26', 'chip_sub' => '2 May 2026 · Acholi Inn, Gulu',
            'stat1_value' => '1,000+', 'stat1_label' => 'Gala attendees',
            'stat2_value' => '92',     'stat2_label' => 'Film submissions',
            'stat3_value' => '64',     'stat3_label' => 'Nominations across 29 films',
            'stat4_value' => '16',     'stat4_label' => 'Award categories, incl. Honorary',
            'masonry1' => 'gallery/2026/nufa26-01.jpg',
            'masonry2' => 'gallery/2026/nufa26-05.jpg',
            'masonry3' => 'gallery/2026/nufa26-11.jpg',
            'masonry4' => 'gallery/2026/nufa26-14.jpg',
            'extra_html' => <<<HTML
<div class="section-head center" style="margin-top:72px">
  <div class="eyebrow">How NUFA26 Came Together</div>
  <h3 style="margin-top:14px;font-size:clamp(1.4rem,2.4vw,1.9rem)">From launch to gala night</h3>
</div>
<div class="process-timeline" data-reveal-stagger>
  <div class="pt-step"><span class="pt-dot"></span><b>18 Oct 2025</b><h4>Official Launch</h4><span>Kakebe Technologies offices, Lira City</span></div>
  <div class="pt-step"><span class="pt-dot"></span><b>31 Dec 2025</b><h4>Submissions Close</h4><span>92 films submitted via FilmFreeway</span></div>
  <div class="pt-step"><span class="pt-dot"></span><b>28 Feb 2026</b><h4>Nomination Night</h4><span>Beal Mall Hotel, Arua City — ~150 guests</span></div>
  <div class="pt-step"><span class="pt-dot"></span><b>2 May 2026</b><h4>Awards Gala Night</h4><span>Acholi Inn, Gulu City — 1,000+ guests</span></div>
</div>

<div class="section-head center" style="margin-top:72px">
  <div class="eyebrow">Regional Representation</div>
  <h3 style="margin-top:14px;font-size:clamp(1.4rem,2.4vw,1.9rem)">64 nominations, four sub-regions</h3>
</div>
<div class="region-bars" data-reveal-stagger>
  <div class="region-bar-row"><b>Acholi</b><div class="region-bar-track"><div class="region-bar-fill" style="width:42%"></div></div><span>42%</span></div>
  <div class="region-bar-row"><b>Karamoja</b><div class="region-bar-track"><div class="region-bar-fill" style="width:20%"></div></div><span>20%</span></div>
  <div class="region-bar-row"><b>Lango</b><div class="region-bar-track"><div class="region-bar-fill" style="width:17%"></div></div><span>17%</span></div>
  <div class="region-bar-row"><b>West Nile</b><div class="region-bar-track"><div class="region-bar-fill" style="width:14%"></div></div><span>14%</span></div>
  <div class="region-bar-row"><b>Kampala</b><div class="region-bar-track"><div class="region-bar-fill" style="width:7%"></div></div><span>7%</span></div>
</div>

<div class="section-head center" style="margin-top:72px">
  <div class="eyebrow">On The Night</div>
  <h3 style="margin-top:14px;font-size:clamp(1.4rem,2.4vw,1.9rem)">Hosted by</h3>
</div>
<div class="host-row" data-reveal-stagger>
  <div class="host-chip">
    <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/></svg></span>
    <b>Bernard Loum</b><span>Host</span>
  </div>
  <div class="host-chip">
    <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/></svg></span>
    <b>Emma Akwero Bongomin</b><span>Co-Host</span>
  </div>
  <div class="host-chip">
    <span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/></svg></span>
    <b>Tracy Gloria Ayot</b><span>Black Carpet Host</span>
  </div>
</div>

<p class="lede" style="text-align:center;margin:40px auto 0">NUFA26 was graced by dignitaries from government, diplomatic and cultural circles, including:</p>
<div class="guest-cloud" data-reveal-stagger>
  <span class="guest-tag">Amb. Rosa Malango — Special Presidential Envoy on Trade &amp; Tourism</span>
  <span class="guest-tag">Chief Justice Emeritus Alfonse Owiny-Dollo</span>
  <span class="guest-tag">Ambassador Olara Otunnu</span>
  <span class="guest-tag">Lt. General Otema Awany</span>
  <span class="guest-tag">Sam Atul — Lord Mayor, Lira City</span>
  <span class="guest-tag">Acire Julius Gunya — Lord Mayor Elect</span>
  <span class="guest-tag">Sheikh Musa Khalil</span>
</div>

<div class="pledge-quote" data-reveal>
  <span class="quote-mark">"</span>
  <p>Government recognises the creative sector — including film — as a significant contributor to Uganda's GDP.</p>
  <cite>Hon. Betty Amongi<span>Minister of Gender, Labour &amp; Social Development — pledging UGX 1 billion toward developing Northern Uganda's creative industry</span></cite>
</div>
HTML,
        ],
        [
            'slug' => 'nufa25', 'year' => 2025, 'status' => 'past', 'sort_order' => 3,
            'badge_text' => 'Past Edition',
            'heading_html' => 'NUFA Awards <span class="serif">2025</span>',
            'theme_quote' => '',
            'description' => "The inaugural NUFA Awards — the region's first-ever dedicated film awards platform — filled the Acholi Inn on 25 April 2025, launching what has become Northern Uganda's biggest night in film.",
            'image' => 'gallery/2025/nufa25-03.webp',
            'event_date_label' => '25 April 2025',
            'location_label' => 'Acholi Inn, Gulu',
            'chip_title' => '1st Edition', 'chip_sub' => 'Where it all began',
            'stat1_value'=>'','stat1_label'=>'','stat2_value'=>'','stat2_label'=>'','stat3_value'=>'','stat3_label'=>'','stat4_value'=>'','stat4_label'=>'',
            'masonry1' => 'gallery/2025/nufa25-07.webp',
            'masonry2' => 'gallery/2025/nufa25-10.webp',
            'masonry3' => 'gallery/2025/nufa25-13.webp',
            'masonry4' => 'gallery/2025/nufa25-15.webp',
            'extra_html' => '',
        ],
    ];

    $cols = ['slug','year','status','sort_order','badge_text','heading_html','theme_quote','description','image',
        'event_date_label','location_label','chip_title','chip_sub',
        'stat1_value','stat1_label','stat2_value','stat2_label','stat3_value','stat3_label','stat4_value','stat4_label',
        'masonry1','masonry2','masonry3','masonry4','extra_html'];
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $sql = "INSERT INTO award_editions (" . implode(',', $cols) . ") VALUES ($placeholders)";
    $types = str_repeat('s', count($cols));
    $types[1] = 'i'; // year
    $types[3] = 'i'; // sort_order
    $editionIds = [];
    foreach ($editions as $e) {
        $stmt = $conn->prepare($sql);
        $vals = [];
        foreach ($cols as $c) $vals[] = $e[$c];
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $editionIds[$e['slug']] = $stmt->insert_id;
        echo "edition {$e['slug']} -> id {$stmt->insert_id}\n";
    }

    // ----------------------------------------------------------- winners
    $winners = [
        ['Best Short Film', 'Hurdles of Hope', '', 0],
        ['Best Feature Film', 'Karamoja', '', 0],
        ['Best Documentary', 'Beauty Without Teeth', '', 0],
        ['Best Production Design', 'Akithak', '', 0],
        ['Best Screenplay', 'The Last Goodbye', 'Joshua Adoli Omara', 0],
        ['Best Director', 'Reverie', 'Emma Okello', 0],
        ['Best Editor', 'Reverie', 'Emma Okello', 0],
        ['Best Cinematography', 'Becoming Her, Becoming Free', 'Lucas Ravie', 0],
        ['Best Actor', 'Reverie', 'Akena Godfrey', 0],
        ['Best Actress', 'Karamoja', 'Atim Evelyn', 0],
        ['Best Child Actor', 'Firewood', 'Olem L. Veen Victor', 0],
        ['Best Sound', "Serah's Vector", 'Okello Henry', 0],
        ['Best VFX', "Serah's Vector", '', 0],
        ['Best Original Soundtrack', 'My Cousin — "Asite"', 'Lync C Black Mama', 0],
        ["Jury's Award", 'Half Chocolate, Half Vanilla', '', 0],
        ['Honorary Award', 'Bishop Emeritus MacLeod Baker Ochola II', 'Lifelong service to peacebuilding & oral storytelling', 1],
    ];
    $editionId26 = $editionIds['nufa26'];
    $stmt = $conn->prepare("INSERT INTO award_winners (edition_id, category, winner_title, person_name, is_honorary, sort_order) VALUES (?,?,?,?,?,?)");
    foreach ($winners as $i => $w) {
        $sort = $i;
        $stmt->bind_param('isssii', $editionId26, $w[0], $w[1], $w[2], $w[3], $sort);
        $stmt->execute();
    }
    echo "seeded " . count($winners) . " winners for nufa26\n";
}

// ------------------------------------------------------------- team
if (already_seeded($conn, 'team_members')) {
    echo "team_members already has rows — skipping.\n";
} else {
    $team = [
        ['Ojok Odong', 'President', 'team/team-president.webp',
            'https://www.facebook.com/ojok.francisodong', 'https://x.com/ojok_odong', 'https://www.instagram.com/ojokodong', ''],
        ['Nimaro Precious', 'Vice President', 'team/team-vice-president.webp', '', '', '', ''],
        ['Achiro Margereth Sharon', 'Finance & Admin', 'team/team-finance.webp', '', '', '', ''],
        ['Komakech Moses', 'Publicity Secretary', 'team/team-publicity.webp',
            '', 'https://x.com/VandricajrMoze', 'https://www.instagram.com/bonomosesjr', 'https://www.linkedin.com/in/moses-komakech-27a61120b/'],
        ['Ryekotoo Julius Peter', 'Speaker', 'team/team-speaker.webp', '', '', '', ''],
        ['Okumu Robin', 'Secretary', 'team/team-secretary.webp', '', '', '', ''],
        ['Akullu Susan', 'Guild Leader', 'team/team-guild-leader.webp', '', '', '', ''],
        ['Komakech Ravens Felix', 'Media Lead', 'team/team-media-lead.webp',
            'https://www.facebook.com/ravens.frank', 'https://x.com/lucas_ravie', 'https://www.instagram.com/lucas_ravie', ''],
    ];
    $stmt = $conn->prepare("INSERT INTO team_members (name, role, photo, facebook_url, x_url, instagram_url, linkedin_url, sort_order) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($team as $i => $t) {
        $sort = $i;
        $stmt->bind_param('sssssssi', $t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $sort);
        $stmt->execute();
    }
    echo "seeded " . count($team) . " team members\n";
}

echo "Done.\n";
