<?php
/**
 * Editable content schema per page. Each entry becomes one field in the
 * admin "Page Content" editor (admin/content-edit.php?page=<key>).
 *
 * type: text (single line), textarea (plain paragraph), html (rich text,
 * output unescaped — may contain simple tags like <em> or <span class="serif">),
 * image (file upload, stored under assets/images/uploads/content/).
 *
 * Defaults match the copy the site shipped with, so nothing changes on the
 * public pages until an admin edits a field.
 */

$CMS_SCHEMAS = [];

$CMS_SCHEMAS['home'] = [
    'label' => 'Home Page',
    'fields' => [
        'hero_eyebrow'     => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'Northern Uganda Filmmakers Association'],
        'hero_heading'     => ['label' => 'Hero — heading (HTML allowed, e.g. <em>word</em>)', 'type' => 'html', 'default' => 'Celebrating the <em>spirit</em> of Northern Cinema'],
        'hero_stat_number' => ['label' => 'Hero — stat number', 'type' => 'text', 'default' => '1,000+'],
        'hero_stat_label'  => ['label' => 'Hero — stat label', 'type' => 'text', 'default' => 'NUFA26 gala attendees'],

        'assoc_eyebrow' => ['label' => 'Association — eyebrow', 'type' => 'text', 'default' => 'The Association'],
        'assoc_heading' => ['label' => 'Association — heading (HTML allowed)', 'type' => 'html', 'default' => 'A regional home for the people telling <span class="serif">Northern Uganda\'s</span> stories'],
        'assoc_lede'    => ['label' => 'Association — paragraph', 'type' => 'textarea', 'default' => "NUFA is a registered collective of filmmakers — camera operators, editors, actors, writers, costume and set designers — building a film industry where local stories are made, owned and celebrated by the people who live them."],
        'assoc1_title'  => ['label' => 'Association — item 1 title', 'type' => 'text', 'default' => 'Training & capacity building'],
        'assoc1_desc'   => ['label' => 'Association — item 1 text', 'type' => 'textarea', 'default' => 'Screenwriting, directing and technical masterclasses reaching thousands of creatives.'],
        'assoc2_title'  => ['label' => 'Association — item 2 title', 'type' => 'text', 'default' => 'Recognition & platform'],
        'assoc2_desc'   => ['label' => 'Association — item 2 text', 'type' => 'textarea', 'default' => 'The NUFA Awards give technical and creative achievement a regional stage.'],
        'assoc3_title'  => ['label' => 'Association — item 3 title', 'type' => 'text', 'default' => 'Advocacy & policy'],
        'assoc3_desc'   => ['label' => 'Association — item 3 text', 'type' => 'textarea', 'default' => 'Championing the policies and market access that make film a viable career.'],

        'stat1_number' => ['label' => 'Stat 1 — number', 'type' => 'text', 'default' => '6,700+'],
        'stat1_label'  => ['label' => 'Stat 1 — label', 'type' => 'text', 'default' => 'People reached through NUFA programmes'],
        'stat2_number' => ['label' => 'Stat 2 — number', 'type' => 'text', 'default' => '92'],
        'stat2_label'  => ['label' => 'Stat 2 — label', 'type' => 'text', 'default' => 'Film submissions to NUFA26 — up 31% on 2025'],
        'stat3_number' => ['label' => 'Stat 3 — number', 'type' => 'text', 'default' => '4'],
        'stat3_label'  => ['label' => 'Stat 3 — label', 'type' => 'text', 'default' => 'Regions united — Acholi, Lango, West Nile, Karamoja'],
        'stat4_number' => ['label' => 'Stat 4 — number', 'type' => 'text', 'default' => '2027'],
        'stat4_label'  => ['label' => 'Stat 4 — label', 'type' => 'text', 'default' => 'Next edition of the NUFA Awards'],

        'awards_eyebrow' => ['label' => 'Awards teaser — eyebrow', 'type' => 'text', 'default' => 'The NUFA Awards'],
        'awards_heading' => ['label' => 'Awards teaser — heading (HTML allowed)', 'type' => 'html', 'default' => 'Two editions strong. <span class="serif">One big stage ahead.</span>'],
        'awards_lede'    => ['label' => 'Awards teaser — paragraph', 'type' => 'textarea', 'default' => "2025 and 2026 are history — 2027 is where we're headed. Every edition brings Northern Uganda's finest storytellers to Acholi Inn, Gulu."],

        'why_eyebrow' => ['label' => 'Why NUFA — eyebrow', 'type' => 'text', 'default' => 'Why NUFA'],
        'why_heading' => ['label' => 'Why NUFA — heading (HTML allowed)', 'type' => 'html', 'default' => 'Built for filmmakers, <span class="serif">by filmmakers</span>'],
        'why1_title'  => ['label' => 'Why NUFA — card 1 title', 'type' => 'text', 'default' => 'Mentorship that shows up'],
        'why1_desc'   => ['label' => 'Why NUFA — card 1 text', 'type' => 'textarea', 'default' => 'Guilds and workshops led by working professionals, in camera, editing, sound, writing and design.'],
        'why2_title'  => ['label' => 'Why NUFA — card 2 title', 'type' => 'text', 'default' => 'A stage that recognises you'],
        'why2_desc'   => ['label' => 'Why NUFA — card 2 text', 'type' => 'textarea', 'default' => 'The NUFA Awards put technical craft — not just star power — in the spotlight every edition.'],
        'why3_title'  => ['label' => 'Why NUFA — card 3 title', 'type' => 'text', 'default' => 'A network across the North'],
        'why3_desc'   => ['label' => 'Why NUFA — card 3 text', 'type' => 'textarea', 'default' => 'One collective spanning Acholi, Lango, West Nile and Karamoja — production support included.'],

        'cta_eyebrow' => ['label' => 'Bottom CTA — eyebrow', 'type' => 'text', 'default' => 'Accelerate Our Impact'],
        'cta_heading' => ['label' => 'Bottom CTA — heading (HTML allowed)', 'type' => 'html', 'default' => 'Become a sponsor of the <span class="serif">NUFA Awards 2027</span>'],
        'cta_lede'    => ['label' => 'Bottom CTA — paragraph', 'type' => 'textarea', 'default' => "Put your brand behind the region's biggest night in film — and the training that happens all year round."],
    ],
];

$CMS_SCHEMAS['about'] = [
    'label' => 'About Page',
    'fields' => [
        'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2025/nufa25-11.webp'],
        'hero_eyebrow' => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'Our Association'],
        'hero_heading' => ['label' => 'Hero — heading (HTML allowed, e.g. <br>)', 'type' => 'html', 'default' => 'Empowering Storytellers.<br>Uniting Northern Uganda.'],
        'hero_lede'    => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => 'NUFA is the regional home for filmmakers across Acholi, Lango, West Nile and Karamoja — where stories are trained, produced, and honoured on our own stage: the NUFA Awards.'],

        'story_eyebrow' => ['label' => 'Our Story — eyebrow', 'type' => 'text', 'default' => 'Our Story'],
        'story_heading' => ['label' => 'Our Story — heading', 'type' => 'text', 'default' => 'Where NUFA began'],
        'story_intro'   => ['label' => 'Our Story — intro paragraph', 'type' => 'textarea', 'default' => "At NUFA, we are passionate about training, platforming and celebrating the filmmakers of Northern Uganda — storytellers who elevate the region's culture and enrich the wider creative economy."],
        'story_body'    => [
            'label' => 'Our Story — full narrative (HTML, one &lt;p&gt; per paragraph)',
            'type'  => 'html',
            'default' => "<p>For years, the people making films in Acholi, Lango, West Nile and Karamoja worked the way most creatives in the region did — alone. A camera operator in Gulu had no easy way to find an editor in Lira. A costume designer in Arua had no guild to trade notes with. Talent was never the problem; the region has always had storytellers. What it lacked was a shared roof to work under, and a stage to be seen from.</p>\n"
                . "<p>NUFA — the Northern Uganda Filmmakers Association — was formed to be that roof. It is a registered, filmmaker-led body that brings camera operators, editors, actors, writers, costume and set designers, sound engineers and directors into one regional community, with training, equipment access and professional guilds built around each craft.</p>\n"
                . "<p class=\"story-pull\">A career in film is something a young creative in Northern Uganda can actually build — not just dream about.</p>\n"
                . "<p>That mission carries extra weight in a region that spent decades being written about by outsiders more often than it got to write its own story. Northern Uganda's recovery and resilience are real, lived history — and NUFA's members are increasingly the ones putting that history, and the region's culture, language and everyday life, on screen <strong>in their own words</strong>. Documentary work made in partnership with cultural institutions like Ker Kwaro Acholi sits alongside music videos, short films and community dramas — all evidence of a place actively authoring its own narrative.</p>\n"
                . "<p>The NUFA Awards are where all of that comes together once a year — a red carpet at Acholi Inn that turns craft most people never see (editing, sound design, production management) into something the whole community can celebrate. Two editions in, with NUFA27 already taking shape, the goal hasn't changed: make Northern Uganda a place where a career in film is <strong>not the exception, but the expectation</strong>.</p>",
        ],

        'vision_text'  => ['label' => 'Vision statement', 'type' => 'textarea', 'default' => 'An independent platform that celebrates excellence, preserves culture, and positions Northern Uganda as a vibrant hub for film and creative innovation.'],
        'mission_text' => ['label' => 'Mission statement', 'type' => 'textarea', 'default' => "To celebrate excellence in filmmaking, preserve Northern Uganda's cultural heritage, and inspire innovation that empowers storytellers and amplifies indigenous voices."],

        'offer_eyebrow' => ['label' => 'What We Offer — eyebrow', 'type' => 'text', 'default' => 'The Challenge'],
        'offer_heading' => ['label' => 'What We Offer — heading (HTML allowed, e.g. <br>)', 'type' => 'html', 'default' => 'What We<br>Offer'],
        'offer_lede'    => ['label' => 'What We Offer — paragraph', 'type' => 'textarea', 'default' => "We specialise in transforming raw talent into real careers. Explore the training, guilds and platforms NUFA has built to support every stage of a filmmaker's journey."],
        'offer1_title'  => ['label' => 'Offer card 1 — title', 'type' => 'text', 'default' => 'Training & workshops'],
        'offer1_desc'   => ['label' => 'Offer card 1 — text', 'type' => 'textarea', 'default' => 'Hands-on sessions led by experienced filmmakers, delivered across five districts and counting.'],
        'offer2_title'  => ['label' => 'Offer card 2 — title', 'type' => 'text', 'default' => 'Professional guilds'],
        'offer2_desc'   => ['label' => 'Offer card 2 — text', 'type' => 'textarea', 'default' => 'Specialised communities for camera, editing, sound, costume, writing and set design.'],
        'offer3_title'  => ['label' => 'Offer card 3 — title', 'type' => 'text', 'default' => 'Equipment & production support'],
        'offer3_desc'   => ['label' => 'Offer card 3 — text', 'type' => 'textarea', 'default' => 'Access to gear and production know-how that would otherwise sit out of reach.'],
        'offer4_title'  => ['label' => 'Offer card 4 — title', 'type' => 'text', 'default' => 'Community screenings'],
        'offer4_desc'   => ['label' => 'Offer card 4 — text', 'type' => 'textarea', 'default' => 'Public screenings that open dialogue between filmmakers and the communities they portray.'],
        'offer5_title'  => ['label' => 'Offer card 5 — title', 'type' => 'text', 'default' => 'The NUFA Awards'],
        'offer5_desc'   => ['label' => 'Offer card 5 — text', 'type' => 'textarea', 'default' => 'An annual, growing platform that gives technical and creative excellence its due recognition.'],
        'offer6_title'  => ['label' => 'Offer card 6 — title', 'type' => 'text', 'default' => 'Advocacy & policy'],
        'offer6_desc'   => ['label' => 'Offer card 6 — text', 'type' => 'textarea', 'default' => 'Pushing for the market access and policy support that make film a viable livelihood.'],

        'regions_eyebrow' => ['label' => 'Where We Work — eyebrow', 'type' => 'text', 'default' => 'Where We Work'],
        'regions_heading' => ['label' => 'Where We Work — heading (HTML allowed)', 'type' => 'html', 'default' => 'Four regions. <span class="serif">One creative voice.</span>'],
        'regions_lede'    => ['label' => 'Where We Work — paragraph', 'type' => 'textarea', 'default' => "NUFA's reach spans the whole of Northern Uganda — bringing training, equipment access and recognition to creatives well beyond the capital."],

        'team_eyebrow' => ['label' => 'Team — eyebrow', 'type' => 'text', 'default' => 'Meet Our Team'],
        'team_heading' => ['label' => 'Team — heading', 'type' => 'text', 'default' => 'Our Team'],

        'cta_eyebrow' => ['label' => 'Bottom CTA — eyebrow', 'type' => 'text', 'default' => 'Join The Movement'],
        'cta_heading' => ['label' => 'Bottom CTA — heading', 'type' => 'text', 'default' => 'Are you a filmmaker in Northern Uganda?'],
        'cta_lede'    => ['label' => 'Bottom CTA — paragraph', 'type' => 'textarea', 'default' => 'Membership, training opportunities and NUFA Awards updates — all in one place.'],
    ],
];

$CMS_SCHEMAS['awards'] = [
    'label' => 'Awards Page',
    'fields' => [
        'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2026/nufa26-09.jpg'],
        'hero_eyebrow' => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'The NUFA Awards'],
        'hero_heading' => ['label' => 'Hero — heading (HTML allowed)', 'type' => 'html', 'default' => 'Two editions of history. <span style="color:var(--brand-300)">One big night ahead.</span>'],
        'hero_lede'    => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "The Northern Uganda Film Awards is the region's premier platform for celebrating filmmaking talent — hosted annually at Acholi Inn, Gulu."],

        'n27_ticket_title' => ['label' => '2027 section — ticket card title', 'type' => 'text', 'default' => 'General Admission'],
        'n27_ticket_desc'  => ['label' => '2027 section — ticket card text', 'type' => 'text', 'default' => 'Secure your seat for the NUFA Awards 2027 gala night'],

        'cta_eyebrow' => ['label' => 'Bottom CTA — eyebrow', 'type' => 'text', 'default' => 'Get Involved'],
        'cta_heading' => ['label' => 'Bottom CTA — heading (HTML allowed)', 'type' => 'html', 'default' => 'Help us make <span class="serif">NUFA27</span> the biggest edition yet'],
    ],
];

$CMS_SCHEMAS['gallery'] = [
    'label' => 'Gallery Page',
    'fields' => [
        'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2025/nufa25-12.webp'],
        'hero_eyebrow' => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'Pictorials'],
        'hero_heading' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => 'The moments behind the red carpet'],
        'hero_lede'    => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => 'A running record of every NUFA Awards edition — from the inaugural 2025 gala to NUFA26 at Acholi Inn.'],
    ],
];

$CMS_SCHEMAS['news'] = [
    'label' => 'News Page',
    'fields' => [
        'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2025/nufa25-16.webp'],
        'hero_eyebrow' => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'Newsroom'],
        'hero_heading' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => "What's happening at NUFA"],
    ],
];

$CMS_SCHEMAS['partners'] = [
    'label' => 'Partners Page',
    'fields' => [
        'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2025/nufa25-19.webp'],
        'hero_eyebrow' => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'Partners & Sponsors'],
        'hero_heading' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => 'Backed by organisations who believe in Northern storytelling'],
        'hero_lede'    => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "From media houses to technology firms, these partners make NUFA's training and the NUFA Awards possible."],
    ],
];

$CMS_SCHEMAS['contact'] = [
    'label' => 'Contact Page',
    'fields' => [
        'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2025/nufa25-20.webp'],
        'hero_eyebrow' => ['label' => 'Hero — eyebrow', 'type' => 'text', 'default' => 'Get In Touch'],
        'hero_heading' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => "Let's talk film"],
        'hero_lede'    => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => 'Membership, training, press or partnership — reach out and the NUFA team will get back to you.'],
        'office_hours' => ['label' => 'Office hours', 'type' => 'text', 'default' => 'Mon – Fri, 9am – 5pm EAT'],
    ],
];
