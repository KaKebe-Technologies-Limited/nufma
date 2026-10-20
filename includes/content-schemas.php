<?php
/**
 * Editable page content, one schema per page. Each field appears in
 * admin → Page Content. Defaults are the approved wording from the NUFA master
 * brief; nothing here states an unverified fact. Clearing a field in the admin
 * restores its default.
 *
 * type: text · textarea (plain text) · html (limited markup allowed) · image (upload)
 */
$CMS_SCHEMAS = [];

/* ------------------------------------------------------------------ HOME */
$CMS_SCHEMAS['home'] = ['label' => 'Home Page', 'fields' => [
    'hero_line1'   => ['label' => 'Hero — heading line 1', 'type' => 'text', 'default' => 'Our Stories.'],
    'hero_line2'   => ['label' => 'Hero — heading line 2', 'type' => 'text', 'default' => 'Our Culture.'],
    'hero_line3'   => ['label' => 'Hero — heading line 3 (gold)', 'type' => 'text', 'default' => 'Our Future.'],
    'hero_lead'    => ['label' => 'Hero — supporting copy', 'type' => 'textarea', 'default' => "The Northern Uganda Filmmakers Association brings filmmakers together to strengthen the craft of storytelling, celebrate creative excellence, and build a stronger future for the film industry in Northern Uganda."],
    'who_eyebrow'  => ['label' => 'Who We Are — eyebrow', 'type' => 'text', 'default' => 'Who We Are'],
    'who_heading'  => ['label' => 'Who We Are — heading', 'type' => 'text', 'default' => "A Stronger Future for Northern Uganda's Film Industry"],
    'who_body'     => ['label' => 'Who We Are — copy', 'type' => 'textarea', 'default' => "NUFA is committed to bringing filmmakers and industry stakeholders together to develop talent, encourage collaboration, recognise achievement, and promote authentic stories from Northern Uganda.\n\nWe believe film is more than entertainment. It is a powerful medium for preserving culture, sharing experiences, inspiring communities, and connecting people across generations."],
    'who_image'    => ['label' => 'Who We Are — image', 'type' => 'image', 'default' => 'action2.webp'],
    'awards_heading' => ['label' => 'NUFA Awards feature — heading', 'type' => 'text', 'default' => 'Celebrating the Stories That Move Us'],
    'awards_body'  => ['label' => 'NUFA Awards feature — copy', 'type' => 'textarea', 'default' => "NUFA Awards celebrates the creativity, dedication, and achievements of filmmakers and audiovisual creatives from Northern Uganda. It creates a platform to recognise outstanding work, encourage professional excellence, connect industry stakeholders, and inspire the next generation of storytellers."],
    'awards_image' => ['label' => 'NUFA Awards feature — background image', 'type' => 'image', 'default' => 'slider/hero/hero-slide-2.webp'],
    'prog_heading' => ['label' => 'Programmes — heading', 'type' => 'text', 'default' => 'Programmes and Initiatives'],
    'prog_lede'    => ['label' => 'Programmes — intro', 'type' => 'textarea', 'default' => "The areas of work NUFA is building. Status labels appear only where an activity's status has been confirmed."],
    'impact_heading' => ['label' => 'Our Impact — heading', 'type' => 'text', 'default' => 'Building the Foundations for Better Stories'],
    'impact_body'  => ['label' => 'Our Impact — copy', 'type' => 'textarea', 'default' => "NUFA's long-term ambition is to strengthen the people, skills, relationships, and opportunities that make a thriving regional film industry possible."],
    'impact1_title' => ['label' => 'Impact pillar 1 — title', 'type' => 'text', 'default' => 'People and skills'],
    'impact1_text' => ['label' => 'Impact pillar 1 — text', 'type' => 'textarea', 'default' => 'Training, mentorship and professional development that help emerging and established filmmakers grow.'],
    'impact2_title' => ['label' => 'Impact pillar 2 — title', 'type' => 'text', 'default' => 'Relationships and collaboration'],
    'impact2_text' => ['label' => 'Impact pillar 2 — text', 'type' => 'textarea', 'default' => 'A connected community of filmmakers, institutions and partners working together across Northern Uganda.'],
    'impact3_title' => ['label' => 'Impact pillar 3 — title', 'type' => 'text', 'default' => 'Recognition and opportunity'],
    'impact3_text' => ['label' => 'Impact pillar 3 — text', 'type' => 'textarea', 'default' => 'Platforms such as NUFA Awards that celebrate achievement and open doors for the region’s storytellers.'],
    'partner_heading' => ['label' => 'Partnership feature — heading', 'type' => 'text', 'default' => 'Help Shape the Future of Film in Northern Uganda'],
    'partner_body' => ['label' => 'Partnership feature — copy', 'type' => 'textarea', 'default' => "A stronger film industry requires collaboration. NUFA welcomes engagement with government institutions, cultural organisations, development partners, sponsors, educational institutions, private companies, and individuals who share a commitment to creative development and authentic storytelling."],
    'partner_image' => ['label' => 'Partnership feature — background image', 'type' => 'image', 'default' => 'production/karamoja-shoot-2.webp'],
]];

/* ----------------------------------------------------------------- ABOUT */
$CMS_SCHEMAS['about'] = ['label' => 'About NUFA', 'fields' => [
    'hero_image'   => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/crew-monitor-check.webp'],
    'hero_lede'    => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "A regional association bringing together filmmakers and other stakeholders in the film and audiovisual industry in Northern Uganda."],
    'story_heading' => ['label' => 'Our Story — heading', 'type' => 'text', 'default' => 'Our Story'],
    'story_body'   => ['label' => 'Our Story — narrative (HTML, one <p> per paragraph)', 'type' => 'html', 'default' => "<p>The Northern Uganda Filmmakers Association is a regional association that brings together filmmakers and other stakeholders in the film and audiovisual industry in Northern Uganda. NUFA seeks to strengthen the regional film ecosystem by supporting filmmakers, encouraging collaboration, improving professional capacity, recognising creative achievement, and promoting stories that reflect the region's people, culture, experiences, identity, and aspirations.</p><p>Northern Uganda has a rich cultural heritage and a powerful collection of stories shaped by its communities, traditions, history, resilience, and changing social and economic realities. The region's experience of prolonged conflict, including approximately two decades of Lord's Resistance Army insurgency, has influenced generations and shaped many of the stories that deserve to be documented and understood.</p><p>NUFA recognises the importance of developing a stronger, more professional, inclusive, and sustainable film industry that enables local creatives to tell their stories with authenticity, technical excellence, and creative freedom. Through film recognition, professional development, industry engagement, collaboration, and partnerships, NUFA aims to contribute to the growth of the creative economy while helping Northern Uganda's stories reach wider audiences.</p>"],
    'story_image'  => ['label' => 'Our Story — image', 'type' => 'image', 'default' => 'production/cultural-portrait.webp'],
    'vision_text'  => ['label' => 'Vision statement (DRAFT — pending leadership approval)', 'type' => 'textarea', 'default' => "To build a vibrant, inclusive, and sustainable film industry in Northern Uganda that celebrates cultural identity, promotes authentic storytelling, and competes confidently on national and international platforms."],
    'mission_text' => ['label' => 'Mission statement (DRAFT — pending leadership approval)', 'type' => 'textarea', 'default' => "To unite and empower filmmakers through professional development, industry recognition, collaboration, resource mobilisation, and opportunities that strengthen film production, creative enterprise, and the preservation of Northern Uganda's stories."],
    'val1_title' => ['label' => 'Value 1 — title', 'type' => 'text', 'default' => 'Authenticity'],
    'val1_text'  => ['label' => 'Value 1 — text', 'type' => 'textarea', 'default' => 'Promoting truthful, meaningful, and culturally grounded storytelling.'],
    'val2_title' => ['label' => 'Value 2 — title', 'type' => 'text', 'default' => 'Professionalism'],
    'val2_text'  => ['label' => 'Value 2 — text', 'type' => 'textarea', 'default' => 'Encouraging high standards in film production, management, and industry practice.'],
    'val3_title' => ['label' => 'Value 3 — title', 'type' => 'text', 'default' => 'Collaboration'],
    'val3_text'  => ['label' => 'Value 3 — text', 'type' => 'textarea', 'default' => 'Building partnerships among filmmakers, institutions, communities, and creative professionals.'],
    'val4_title' => ['label' => 'Value 4 — title', 'type' => 'text', 'default' => 'Inclusion'],
    'val4_text'  => ['label' => 'Value 4 — text', 'type' => 'textarea', 'default' => 'Expanding opportunities for emerging filmmakers, women, young people, and underrepresented voices.'],
    'val5_title' => ['label' => 'Value 5 — title', 'type' => 'text', 'default' => 'Cultural Identity'],
    'val5_text'  => ['label' => 'Value 5 — text', 'type' => 'textarea', 'default' => "Preserving and promoting Northern Uganda's cultures, languages, traditions, and lived experiences."],
    'val6_title' => ['label' => 'Value 6 — title', 'type' => 'text', 'default' => 'Innovation'],
    'val6_text'  => ['label' => 'Value 6 — text', 'type' => 'textarea', 'default' => 'Embracing technology, new production methods, and evolving distribution opportunities.'],
    'val7_title' => ['label' => 'Value 7 — title', 'type' => 'text', 'default' => 'Integrity'],
    'val7_text'  => ['label' => 'Value 7 — text', 'type' => 'textarea', 'default' => 'Promoting accountability, transparency, respect, and responsible representation.'],
    'leadership_note' => ['label' => 'Leadership — intro / note', 'type' => 'textarea', 'default' => "The people guiding NUFA. Profiles are published only once names, roles and portraits have been approved by the association."],
    'membership_intro' => ['label' => 'Membership — intro', 'type' => 'textarea', 'default' => "NUFA membership is intended to connect filmmakers and audiovisual creatives with opportunities for collaboration, professional development, industry engagement, recognition, and information sharing."],
    'membership_criteria' => ['label' => 'Membership — criteria note', 'type' => 'textarea', 'default' => "Applications are subject to NUFA's membership criteria and confirmation process. Submitting an application does not automatically make someone a member. Formal membership requirements are awaiting confirmation."],
]];

/* ---------------------------------------------------------------- AWARDS */
$CMS_SCHEMAS['awards'] = ['label' => 'NUFA Awards (landing page)', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'slider/hero/hero-slide-3.webp'],
    'hero_heading' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => 'Recognising Excellence. Celebrating Our Stories.'],
    'hero_lede'   => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "NUFA Awards celebrates the talent, creativity, technical skill, and commitment of filmmakers and audiovisual creatives contributing to Northern Uganda's film industry."],
    'purpose_heading' => ['label' => 'Purpose — heading', 'type' => 'text', 'default' => 'Why the NUFA Awards matter'],
    'purpose_body' => ['label' => 'Purpose — copy', 'type' => 'textarea', 'default' => "The awards platform recognises creative achievement, inspires emerging talent, strengthens industry relationships, and brings audiences closer to regional filmmaking.\n\nNUFA Awards 2025 was the association's maiden edition and recognised filmmaking in Northern Uganda."],
    'participation_body' => ['label' => 'Participation information', 'type' => 'textarea', 'default' => "Dates, venue, eligibility, submission process, categories, rules, deadlines, nominees, winners, tickets and sponsorship opportunities are published here only when officially confirmed by NUFA. For questions about participation, please contact the awards team."],
    'submission_url' => ['label' => 'Official submission platform link (leave blank unless officially confirmed)', 'type' => 'text', 'default' => ''],
]];

/* ------------------------------------------------------------ PROGRAMMES */
$CMS_SCHEMAS['programmes'] = ['label' => 'Programmes (landing page)', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/on-set-action.webp'],
    'hero_lede'   => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "NUFA's programme areas are designed to strengthen skills, create pathways for emerging talent, connect filmmakers with audiences, support creative enterprise, and protect authentic storytelling."],
    'status_note' => ['label' => 'Status note', 'type' => 'textarea', 'default' => "Programme statuses (Planned, Ongoing, Completed, Under Development) are shown only where they have been confirmed by NUFA."],
]];

/* ----------------------------------------------------------------- SACCO */
$CMS_SCHEMAS['sacco'] = ['label' => 'NUFA SACCO', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'front1.webp'],
    'hero_heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Building Financial Resilience for Filmmakers'],
    'status_text' => ['label' => 'Status banner', 'type' => 'textarea', 'default' => "The NUFA SACCO is a proposed initiative and is under development. It is not yet registered, authorised or operational. NUFA is not collecting deposits, advertising loans or accepting loan applications."],
    'intro_body'  => ['label' => 'Introduction', 'type' => 'textarea', 'default' => "The proposed NUFA SACCO reflects an ambition to explore practical ways of strengthening financial inclusion, savings culture, access to responsible financial services, and long-term sustainability for filmmakers and creative professionals.\n\nThe initiative is intended to explore how a structured member-focused financial model could support the economic resilience of the film community while encouraging financial literacy, collective responsibility, and sustainable creative enterprise."],
    'rationale'   => ['label' => 'Background and rationale', 'type' => 'html', 'default' => "<p>Creative careers are often project-based and income can be irregular. A well-governed, member-owned financial cooperative could help filmmakers plan, save and build resilience. Whether and how such a model can be established is still being explored.</p>"],
    'objectives'  => ['label' => 'Intended objectives', 'type' => 'html', 'default' => "<ul><li>Explore a member-focused model for financial inclusion in the film community</li><li>Encourage a savings culture and financial literacy among creatives</li><li>Understand the governance and regulatory requirements before any launch</li></ul>"],
    'areas'       => ['label' => 'Potential areas of development', 'type' => 'html', 'default' => "<ul><li>Savings and financial literacy</li><li>Member education</li><li>Responsible access to credit, subject to applicable approvals</li><li>Financial planning for creative professionals</li><li>Governance and member participation</li><li>Partnerships with appropriate financial institutions</li></ul>"],
    'benefits'    => ['label' => 'Proposed benefits (no promises of returns)', 'type' => 'html', 'default' => "<p>Any benefits would depend on the final structure, member decisions and regulatory approval. NUFA makes no promises about loan amounts, interest rates, dividends, returns or guaranteed benefits.</p>"],
    'development_status' => ['label' => 'Development status', 'type' => 'html', 'default' => "<p><strong>Status: proposed / under development.</strong> Formal progress will be published on this page once confirmed.</p>"],
    'governance'  => ['label' => 'Governance and regulatory considerations', 'type' => 'html', 'default' => "<p>A SACCO must be formally established, registered and authorised under applicable Ugandan law before it can accept member savings or provide credit. NUFA will seek appropriate professional and regulatory guidance and will communicate any decision to members.</p>"],
    'faq'         => ['label' => 'Frequently asked questions (HTML: repeat <details><summary>Question</summary><div class="ans">Answer</div></details>)', 'type' => 'html', 'default' => "<details><summary>Is the NUFA SACCO operational?</summary><div class=\"ans\">No. It is a proposed initiative under development. It is not registered or authorised and does not currently accept deposits or loan applications.</div></details><details><summary>Can I deposit money or apply for a loan?</summary><div class=\"ans\">No. Please do not send money to anyone claiming to represent a NUFA SACCO. NUFA will announce formally if and when services become available.</div></details><details><summary>How can I stay informed?</summary><div class=\"ans\">Submit the expression-of-interest form below. We will only use your details to share formal progress updates.</div></details><details><summary>Will members receive guaranteed returns?</summary><div class=\"ans\">No. NUFA does not promise interest, dividends, returns or any guaranteed benefit.</div></details>"],
]];

/* --------------------------------------------------------------- NETWORK */
$CMS_SCHEMAS['network'] = ['label' => 'Industry Network', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'nufa2.webp'],
    'hero_lede'   => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "A planned space to connect filmmakers, production professionals, creative organisations, and industry stakeholders across Northern Uganda."],
    'status_text' => ['label' => 'Status note', 'type' => 'textarea', 'default' => "The public filmmaker directory is planned for a later phase. Until then you can register your interest below. No profiles are published without the owner's explicit consent, and listing in the directory does not imply NUFA membership."],
    'privacy_note' => ['label' => 'Privacy note', 'type' => 'textarea', 'default' => "This will be a privacy-conscious directory: private contact details are never published without explicit consent."],
]];

/* --------------------------------------------------------------- PARTNER */
$CMS_SCHEMAS['partners'] = ['label' => 'Partner With NUFA', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/karamoja-shoot-1.webp'],
    'hero_heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Partner With NUFA. Invest in the Power of Storytelling.'],
    'hero_lede'   => ['label' => 'Intro', 'type' => 'textarea', 'default' => "A stronger regional film industry requires investment in people, skills, infrastructure, visibility, and opportunity. NUFA welcomes partnerships that help filmmakers develop their craft, expand audiences, preserve cultural heritage, build creative enterprises, and bring Northern Uganda's stories to wider platforms."],
    'why_body'    => ['label' => 'Why partner with NUFA', 'type' => 'textarea', 'default' => "Partnering with NUFA connects your organisation with a growing community of filmmakers, a regional awards platform, and a shared commitment to authentic storytelling and creative development in Northern Uganda."],
    'formats_note' => ['label' => 'Partnership formats note', 'type' => 'textarea', 'default' => "Partnership formats are developed together with each partner. Sponsorship packages and figures are shared on request — none are published here until confirmed."],
]];

/* --------------------------------------------------------------- CONTACT */
$CMS_SCHEMAS['contact'] = ['label' => 'Contact', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/crew-monitor-check.webp'],
    'hero_lede'   => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "Membership, awards, programmes, partnerships or general questions — send us a message and the NUFA team will respond."],
    'hours'       => ['label' => 'Office hours (leave blank to hide)', 'type' => 'text', 'default' => ''],
]];

/* ------------------------------------------------ NEWS / RESOURCES / MEDIA */
$CMS_SCHEMAS['news'] = ['label' => 'News & Stories (landing)', 'fields' => [
    'hero_image' => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/on-set-action.webp'],
    'hero_lede'  => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "News, announcements, filmmaker profiles, opportunities and stories from the film community of Northern Uganda."],
]];
$CMS_SCHEMAS['resources'] = ['label' => 'Resource Library (landing)', 'fields' => [
    'hero_image' => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'nufa4.webp'],
    'hero_lede'  => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "Approved guides, reports, calls for submissions and opportunities for filmmakers. Documents are added here as they are supplied and approved."],
]];
$CMS_SCHEMAS['gallery'] = ['label' => 'Gallery & Video Library (landing)', 'fields' => [
    'hero_image' => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'slider/hero/hero-slide-1.webp'],
    'hero_lede'  => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "A visual archive of NUFA Awards ceremonies, productions, screenings and the people behind Northern Uganda's stories. Photographs are published with NUFA's permission."],
    'credit_note' => ['label' => 'Image credit note', 'type' => 'text', 'default' => 'Photography: NUFA and its photographers. Please request permission before reusing any image.'],
]];

/* -------------------------------------------------------------- POLICIES */
$CMS_SCHEMAS['policies'] = ['label' => 'Policies & Transparency (draft legal text)', 'fields' => [
    'privacy' => ['label' => 'Privacy Policy', 'type' => 'html', 'default' => <<<'HTML'
<p>This policy explains how the Northern Uganda Filmmakers Association ("NUFA", "we") collects and uses personal information through this website. We aim to handle personal data in line with applicable Ugandan law, including the Data Protection and Privacy Act, 2019.</p>
<h2>What we collect</h2>
<ul><li><strong>Enquiry forms</strong> (contact, partnership, SACCO interest, industry network): name, email, telephone, organisation, your message and any details you choose to provide.</li>
<li><strong>Membership applications:</strong> identity and contact details, date of birth, district, area of creative practice, experience, an optional portfolio link, a passport-style photograph, payment reference and proof of payment.</li></ul>
<h2>How we use it</h2>
<p>We use your information to respond to your enquiry, process your application, administer membership and communicate about NUFA activities you asked about. We do not sell personal data.</p>
<h2>Who can see it</h2>
<p>Only authorised NUFA administrators. Uploaded documents are stored privately and are never published on the website. Membership card details (name, photograph, guild, registration number and validity period) are visible to anyone holding the private card link issued to the member.</p>
<h2>Retention and deletion</h2>
<p>We keep information only as long as needed for the purpose it was collected. You may ask us to access, correct or delete your information by contacting nuf.association@gmail.com.</p>
<h2>Security</h2>
<p>Forms are protected against spam and misuse, administrative areas require authentication, and the site should be accessed over HTTPS.</p>
<h2>Contact</h2>
<p>Northern Uganda Filmmakers Association, Plot 75, Jomo Kenyatta Road, Gulu City, Uganda · nuf.association@gmail.com</p>
HTML],
    'cookies' => ['label' => 'Cookie Policy', 'type' => 'html', 'default' => <<<'HTML'
<p>This website uses only essential cookies needed for it to work — for example a session cookie that protects forms against misuse and keeps administrators signed in. We do not use advertising or tracking cookies and do not currently run third-party analytics.</p>
<p>If analytics or other non-essential technologies are added in future, this policy will be updated and consent will be requested where required.</p>
<p>Fonts are loaded from Google Fonts, which may receive your IP address when a page loads.</p>
HTML],
    'terms' => ['label' => 'Website Terms of Use', 'type' => 'html', 'default' => <<<'HTML'
<p>By using this website you agree to these terms.</p>
<h2>Use of content</h2>
<p>Website text, images and branding belong to NUFA or its contributors unless stated otherwise. You may share links to pages. Please request permission before reproducing content.</p>
<h2>Accuracy</h2>
<p>We work to keep information accurate and up to date but do not guarantee that every page is complete or current. Event details, programme status and awards information are published only when confirmed.</p>
<h2>Forms and applications</h2>
<p>Submitting a form does not create a contract. Membership applications are subject to NUFA's criteria and confirmation process.</p>
<h2>External links</h2>
<p>We are not responsible for the content of external websites linked from this site.</p>
HTML],
    'permissions' => ['label' => 'Image and Content Permissions', 'type' => 'html', 'default' => <<<'HTML'
<p>NUFA publishes photographs, video and written stories with the consent of the people involved and the permission of the creators wherever possible. We respect the dignity of the communities we represent.</p>
<ul><li>If you appear in a photograph or story and would like it credited differently or removed, contact us and we will respond promptly.</li><li>Please do not reuse NUFA images or footage without written permission.</li><li>Film footage from third parties is used only with authorisation.</li></ul>
HTML],
    'accessibility' => ['label' => 'Accessibility Statement', 'type' => 'html', 'default' => <<<'HTML'
<p>NUFA wants everyone to be able to use this website. We aim to provide semantic page structure, keyboard navigation with visible focus, labelled forms, descriptive link text, alternative text for meaningful images, adequate colour contrast and respect for reduced-motion preferences.</p>
<p>If you experience difficulty using any part of the site, please contact nuf.association@gmail.com and we will work to help and to fix the problem.</p>
HTML],
    'membership_terms' => ['label' => 'Membership Terms (pending approval)', 'type' => 'html', 'default' => <<<'HTML'
<p><strong>Awaiting confirmation.</strong> NUFA's formal membership terms, criteria and fees are being finalised and will be published here once approved by the association's leadership.</p>
HTML],
    'complaints' => ['label' => 'Complaints and Feedback (pending approval)', 'type' => 'html', 'default' => <<<'HTML'
<p>If you have a complaint or feedback about NUFA or this website, please write to nuf.association@gmail.com with the subject "Feedback". A formal complaints process will be published here once approved.</p>
HTML],
]];

/* ====================================================================
   Site-wide text, SEO, calls-to-action and list content
   ==================================================================== */
$CMS_SCHEMAS['site'] = ['label' => 'Site-wide (header, footer, brand)', 'fields' => [
    'brand_subtitle'     => ['label' => 'Header — small text under the NUFA name', 'type' => 'text', 'default' => 'Filmmakers Association'],
    'header_cta_label'   => ['label' => 'Header button — label (leave blank to hide)', 'type' => 'text', 'default' => 'Become a Member'],
    'header_cta_url'     => ['label' => 'Header button — link', 'type' => 'text', 'default' => 'membership.php'],
    'footer_tagline'     => ['label' => 'Footer — tagline', 'type' => 'text', 'default' => 'Our Stories. Our Culture. Our Future.'],
    'footer_description' => ['label' => 'Footer — organisation description', 'type' => 'textarea', 'default' => "NUFA brings filmmakers together, strengthens their craft, celebrates their achievements, and helps ensure that Northern Uganda's stories are created, preserved and shared with the world."],
    'footer_col1_title'  => ['label' => 'Footer — first link column title', 'type' => 'text', 'default' => 'Explore'],
    'footer_col2_title'  => ['label' => 'Footer — second link column title', 'type' => 'text', 'default' => 'Get Involved'],
    'footer_col3_title'  => ['label' => 'Footer — contact column title', 'type' => 'text', 'default' => 'Contact'],
    'footer_copyright'   => ['label' => 'Footer — copyright line (the year is added automatically)', 'type' => 'text', 'default' => 'Northern Uganda Filmmakers Association (NUFA). All rights reserved.'],
    'mobile_cta2_label'  => ['label' => 'Mobile menu — second button label (blank to hide)', 'type' => 'text', 'default' => 'Partner With NUFA'],
    'mobile_cta2_url'    => ['label' => 'Mobile menu — second button link', 'type' => 'text', 'default' => 'partners.php'],
]];

$CMS_SCHEMAS['seo'] = ['label' => 'SEO — page titles & descriptions', 'fields' => [
    'home_title' => ['label' => 'Home — browser/search title', 'type' => 'text', 'default' => 'NUFA — Northern Uganda Filmmakers Association | Our Stories. Our Culture. Our Future.'],
    'home_desc'  => ['label' => 'Home — search description (about 155 characters)', 'type' => 'textarea', 'default' => 'The Northern Uganda Filmmakers Association (NUFA) brings filmmakers together to strengthen the craft of storytelling, celebrate creative excellence and build a stronger future for the film industry in Northern Uganda.'],
    'about_title' => ['label' => 'About — title', 'type' => 'text', 'default' => 'About NUFA — Northern Uganda Filmmakers Association'],
    'about_desc'  => ['label' => 'About — description', 'type' => 'textarea', 'default' => 'Learn about the Northern Uganda Filmmakers Association: our story, vision and mission, core values, leadership and membership.'],
    'membership_title' => ['label' => 'Membership — title', 'type' => 'text', 'default' => 'Become a Member — NUFA | Northern Uganda Filmmakers Association'],
    'membership_desc'  => ['label' => 'Membership — description', 'type' => 'textarea', 'default' => 'Apply to join the Northern Uganda Filmmakers Association: complete the application, submit proof of payment and receive your NUFA registration number and membership card on approval.'],
    'awards_title' => ['label' => 'NUFA Awards — title', 'type' => 'text', 'default' => 'NUFA Awards — Recognising Excellence. Celebrating Our Stories.'],
    'awards_desc'  => ['label' => 'NUFA Awards — description', 'type' => 'textarea', 'default' => 'NUFA Awards celebrates the talent, creativity and technical skill of filmmakers and audiovisual creatives in Northern Uganda. Explore the awards, past editions and how to take part.'],
    'programmes_title' => ['label' => 'Programmes — title', 'type' => 'text', 'default' => 'Programmes & Projects — NUFA | Film Training and Development in Northern Uganda'],
    'programmes_desc'  => ['label' => 'Programmes — description', 'type' => 'textarea', 'default' => "NUFA's programme areas: film capacity building, mentorship and emerging talent, screenings and outreach, creative enterprise, and cultural preservation in Northern Uganda."],
    'sacco_title' => ['label' => 'NUFA SACCO — title', 'type' => 'text', 'default' => 'NUFA SACCO (Proposed) — Financial Resilience for Filmmakers | NUFA'],
    'sacco_desc'  => ['label' => 'NUFA SACCO — description', 'type' => 'textarea', 'default' => 'The proposed NUFA SACCO is an initiative under development to explore financial inclusion and resilience for filmmakers in Northern Uganda. It is not yet operational.'],
    'network_title' => ['label' => 'Industry Network — title', 'type' => 'text', 'default' => 'Industry Network — Connecting Filmmakers in Northern Uganda | NUFA'],
    'network_desc'  => ['label' => 'Industry Network — description', 'type' => 'textarea', 'default' => 'A planned filmmaker directory and industry network connecting directors, producers, writers, actors, crews and partners across Northern Uganda. Register your interest.'],
    'news_title' => ['label' => 'News & Stories — title', 'type' => 'text', 'default' => 'News & Stories — NUFA | Film Industry News from Northern Uganda'],
    'news_desc'  => ['label' => 'News & Stories — description', 'type' => 'textarea', 'default' => 'News, announcements, filmmaker profiles, training updates, opportunities and stories from the film community of Northern Uganda.'],
    'resources_title' => ['label' => 'Resources — title', 'type' => 'text', 'default' => 'Resource Library — Guides, Reports and Opportunities | NUFA'],
    'resources_desc'  => ['label' => 'Resources — description', 'type' => 'textarea', 'default' => 'Approved film production guides, training materials, reports, calls for submissions and funding opportunities for filmmakers in Northern Uganda.'],
    'gallery_title' => ['label' => 'Gallery — title', 'type' => 'text', 'default' => 'Gallery & Video Library — NUFA Awards, Productions and Screenings'],
    'gallery_desc'  => ['label' => 'Gallery — description', 'type' => 'textarea', 'default' => 'Photographs and video from NUFA Awards ceremonies, film productions, screenings and community storytelling in Northern Uganda.'],
    'partners_title' => ['label' => 'Partner With NUFA — title', 'type' => 'text', 'default' => 'Partner With NUFA — Invest in the Power of Storytelling'],
    'partners_desc'  => ['label' => 'Partner With NUFA — description', 'type' => 'textarea', 'default' => 'Partner with the Northern Uganda Filmmakers Association to support film training, awards, audience development and creative enterprise in Northern Uganda.'],
    'contact_title' => ['label' => 'Contact — title', 'type' => 'text', 'default' => 'Contact NUFA — Northern Uganda Filmmakers Association, Gulu'],
    'contact_desc'  => ['label' => 'Contact — description', 'type' => 'textarea', 'default' => 'Contact the Northern Uganda Filmmakers Association in Gulu City for membership, awards, programme and partnership enquiries.'],
]];

// extra editable text on existing pages
$CMS_SCHEMAS['home']['fields'] += [
    'who_button'       => ['label' => 'Who We Are — button label', 'type' => 'text', 'default' => 'Discover NUFA'],
    'hero_btn1_label'  => ['label' => 'Hero button 1 — label', 'type' => 'text', 'default' => 'Explore NUFA Awards'],
    'hero_btn1_url'    => ['label' => 'Hero button 1 — link', 'type' => 'text', 'default' => 'awards.php'],
    'hero_btn2_label'  => ['label' => 'Hero button 2 — label', 'type' => 'text', 'default' => 'Become a Member'],
    'hero_btn2_url'    => ['label' => 'Hero button 2 — link', 'type' => 'text', 'default' => 'membership.php'],
    'hero_btn3_label'  => ['label' => 'Hero button 3 (outline) — label', 'type' => 'text', 'default' => 'Partner With NUFA'],
    'hero_btn3_url'    => ['label' => 'Hero button 3 (outline) — link', 'type' => 'text', 'default' => 'partners.php'],
    'news_heading'     => ['label' => 'News section — heading', 'type' => 'text', 'default' => 'Latest from the film community'],
    'cta_title'        => ['label' => 'Bottom call-to-action — title', 'type' => 'text', 'default' => 'Are you a filmmaker in Northern Uganda?'],
    'cta_text'         => ['label' => 'Bottom call-to-action — text', 'type' => 'textarea', 'default' => "Learn about NUFA membership and how to apply. Applications are subject to NUFA's membership criteria and confirmation process."],
    'cta_btn1_label'   => ['label' => 'Call-to-action button 1 — label', 'type' => 'text', 'default' => 'Become a Member'],
    'cta_btn1_url'     => ['label' => 'Call-to-action button 1 — link', 'type' => 'text', 'default' => 'membership.php'],
    'cta_btn2_label'   => ['label' => 'Call-to-action button 2 — label (blank to hide)', 'type' => 'text', 'default' => 'Contact NUFA'],
    'cta_btn2_url'     => ['label' => 'Call-to-action button 2 — link', 'type' => 'text', 'default' => 'contact.php'],
];
$CMS_SCHEMAS['about']['fields'] += [
    'cta_title'      => ['label' => 'Bottom call-to-action — title', 'type' => 'text', 'default' => 'Explore what NUFA is building'],
    'cta_text'       => ['label' => 'Bottom call-to-action — text', 'type' => 'textarea', 'default' => "From capacity building to the NUFA Awards, discover the programmes shaping the region's film industry."],
    'cta_btn1_label' => ['label' => 'Call-to-action button 1 — label', 'type' => 'text', 'default' => 'View Programmes'],
    'cta_btn1_url'   => ['label' => 'Call-to-action button 1 — link', 'type' => 'text', 'default' => 'programmes.php'],
    'cta_btn2_label' => ['label' => 'Call-to-action button 2 — label (blank to hide)', 'type' => 'text', 'default' => 'NUFA Awards'],
    'cta_btn2_url'   => ['label' => 'Call-to-action button 2 — link', 'type' => 'text', 'default' => 'awards.php'],
    'membership_heading' => ['label' => 'Membership section — heading', 'type' => 'text', 'default' => 'Belong to a connected film community'],
    'membership_steps' => ['label' => 'Membership — how it works (one step per line: Title | description)', 'type' => 'textarea', 'default' => "Complete the application | Share your details, area of creative practice and a short profile.\nSubmit proof of payment | Upload your payment receipt as instructed on the membership page.\nReview and confirmation | NUFA reviews each application against its membership criteria and confirms the outcome by email.\nYour membership card | Confirmed members receive a NUFA registration number and membership card."],
];
$CMS_SCHEMAS['awards']['fields'] += [
    'cta_title'      => ['label' => 'Bottom call-to-action — title', 'type' => 'text', 'default' => 'Support the NUFA Awards'],
    'cta_text'       => ['label' => 'Bottom call-to-action — text', 'type' => 'textarea', 'default' => 'Sponsor the awards, support emerging filmmakers or partner on film training and audience development.'],
    'cta_btn1_label' => ['label' => 'Call-to-action button 1 — label', 'type' => 'text', 'default' => 'Partner With NUFA'],
    'cta_btn1_url'   => ['label' => 'Call-to-action button 1 — link', 'type' => 'text', 'default' => 'partners.php'],
    'cta_btn2_label' => ['label' => 'Call-to-action button 2 — label (blank to hide)', 'type' => 'text', 'default' => 'Become a Member'],
    'cta_btn2_url'   => ['label' => 'Call-to-action button 2 — link', 'type' => 'text', 'default' => 'membership.php'],
    'platform_list'  => ['label' => 'On this platform — list (one item per line)', 'type' => 'textarea', 'default' => "Awards overview and purpose\nPrevious editions and the awards archive\nApproved award categories\nNominees and winners, where verified\nPhotographs and video highlights\nSponsors and partners\nNews and announcements\nParticipation information and awards enquiries"],
];
$CMS_SCHEMAS['programmes']['fields'] += [
    'cta_title'      => ['label' => 'Bottom call-to-action — title', 'type' => 'text', 'default' => 'Want to take part in a NUFA programme?'],
    'cta_text'       => ['label' => 'Bottom call-to-action — text', 'type' => 'textarea', 'default' => 'Tell us about your interest and the NUFA team will share participation information as it is confirmed.'],
    'cta_btn1_label' => ['label' => 'Call-to-action button 1 — label', 'type' => 'text', 'default' => 'Programme enquiries'],
    'cta_btn1_url'   => ['label' => 'Call-to-action button 1 — link', 'type' => 'text', 'default' => 'contact.php?topic=Programmes'],
    'cta_btn2_label' => ['label' => 'Call-to-action button 2 — label (blank to hide)', 'type' => 'text', 'default' => 'Partner with NUFA'],
    'cta_btn2_url'   => ['label' => 'Call-to-action button 2 — link', 'type' => 'text', 'default' => 'partners.php'],
];
$CMS_SCHEMAS['partners']['fields'] += [
    'opps_heading' => ['label' => 'Opportunities — heading', 'type' => 'text', 'default' => 'Where your support makes a difference'],
    'opps_list'    => ['label' => 'Partnership opportunities (one per line)', 'type' => 'textarea', 'default' => "Sponsorship of NUFA Awards\nFilm training and mentorship\nEquipment and technical support\nFilm screenings and audience development\nYouth and emerging filmmaker initiatives\nCultural preservation projects\nCreative enterprise development\nDigital innovation and industry infrastructure\nResearch, advocacy and industry engagement"],
    'partners_heading' => ['label' => 'Partner wall — heading', 'type' => 'text', 'default' => 'Organisations standing with NUFA'],
];
$CMS_SCHEMAS['network']['fields'] += [
    'who_heading' => ['label' => 'Who will be listed — heading', 'type' => 'text', 'default' => 'Professionals across the film value chain'],
    'cats_list'   => ['label' => 'Professional categories (one per line)', 'type' => 'textarea', 'default' => "Film production companies\nDirectors and producers\nScreenwriters\nActors and casting professionals\nCinematographers\nEditors and post-production specialists\nSound professionals\nProduction designers\nFilm trainers and mentors\nIndustry partners"],
    'profile_fields' => ['label' => 'What each profile will include (one per line)', 'type' => 'textarea', 'default' => "Name and professional role\nBiography\nLocation\nPortfolio or showreel\nContact preferences\nAn approved photograph"],
];

$CMS_SCHEMAS['membership'] = ['label' => 'Membership page', 'fields' => [
    'hero_lede' => ['label' => 'Page banner — paragraph', 'type' => 'textarea', 'default' => 'Connect with a community of filmmakers and audiovisual creatives. Apply online, submit your proof of payment and receive your NUFA registration number and card once approved.'],
]];

// editable page-banner headings
$CMS_SCHEMAS['about']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'Northern Uganda Filmmakers Association']];
$CMS_SCHEMAS['programmes']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'Building skills, pathways and opportunity']];
$CMS_SCHEMAS['network']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'Connect with the people behind Northern Uganda’s film industry']];
$CMS_SCHEMAS['news']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'What\'s happening at NUFA']];
$CMS_SCHEMAS['resources']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'Resource Library']];
$CMS_SCHEMAS['gallery']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'The moments behind the stories']];
$CMS_SCHEMAS['contact']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'Let\'s talk film']];
$CMS_SCHEMAS['membership']['fields'] += ['hero_title' => ['label' => 'Page banner — heading', 'type' => 'text', 'default' => 'Become a NUFA member']];

$CMS_SCHEMAS['home']['fields'] += [
    'hero_eyebrow'     => ['label' => 'Hero — small line above the heading', 'type' => 'text', 'default' => 'Northern Uganda Filmmakers Association'],
    'awards_card_title' => ['label' => 'Programme card — NUFA Awards — title', 'type' => 'text', 'default' => 'NUFA Awards'],
    'awards_card_text'  => ['label' => 'Programme card — NUFA Awards — text', 'type' => 'textarea', 'default' => 'Recognising outstanding work and celebrating the stories and storytellers of Northern Uganda.'],
    'sacco_card_title'  => ['label' => 'Programme card — SACCO — title', 'type' => 'text', 'default' => 'NUFA SACCO — Proposed Initiative'],
    'sacco_card_text'   => ['label' => 'Programme card — SACCO — text', 'type' => 'textarea', 'default' => 'An initiative being explored to strengthen financial resilience for filmmakers. Not yet operational.'],
];
/* ---------------------------------------------------------- SUPPORT A PROGRAMME */
$CMS_SCHEMAS['support'] = ['label' => 'Support a Programme', 'fields' => [
    'hero_image'  => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/karamoja-shoot-2.webp'],
    'hero_title'  => ['label' => 'Hero — heading', 'type' => 'text', 'default' => 'Stand behind Northern Uganda’s storytellers'],
    'hero_lede'   => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => "Choose a programme and a way to help — donate, sponsor, volunteer or partner. Every request is reviewed by the NUFA team."],
    'choose_programme' => ['label' => 'Form — programme selector label', 'type' => 'text', 'default' => 'Programme you would like to support'],
    'general_option'   => ['label' => 'Form — "no specific programme" option', 'type' => 'text', 'default' => 'Where it is needed most'],
    'donate_blurb'    => ['label' => 'Option — Donate — description', 'type' => 'textarea', 'default' => 'Give a one-off or recurring gift to help this programme reach more filmmakers.'],
    'sponsor_blurb'   => ['label' => 'Option — Sponsor — description', 'type' => 'textarea', 'default' => 'Back a programme as an organisation or individual sponsor, with recognition where agreed.'],
    'volunteer_blurb' => ['label' => 'Option — Volunteer — description', 'type' => 'textarea', 'default' => 'Give your time and skills — mentoring, production support, events or outreach.'],
    'partner_blurb'   => ['label' => 'Option — Partner — description', 'type' => 'textarea', 'default' => 'Collaborate with NUFA on training, equipment, screenings or other joint work.'],
    'thanks_title' => ['label' => 'Thank-you page — heading', 'type' => 'text', 'default' => 'Thank you — we have your request'],
    'thanks_text'  => ['label' => 'Thank-you page — message', 'type' => 'textarea', 'default' => "Your request has been recorded and the NUFA team will review it. We have sent a confirmation to your email address. Keep your reference number — you will need it if you contact us."],
    'involved_heading' => ['label' => 'Programme pages — "Get involved" heading', 'type' => 'text', 'default' => 'Support this programme'],
    'involved_intro'   => ['label' => 'Programme pages — "Get involved" intro', 'type' => 'textarea', 'default' => 'Choose how you would like to help. Every request is reviewed by the NUFA team and confirmed by email.'],
]];
$CMS_SCHEMAS['seo']['fields'] += [
    'schedule_title' => ['label' => 'Schedule — title', 'type' => 'text', 'default' => 'Programme Schedule & Calendar | NUFA'],
    'schedule_desc'  => ['label' => 'Schedule — description', 'type' => 'textarea', 'default' => 'Dates, venues and meeting links for NUFA programmes, plus a calendar of important days and public holidays in Uganda.'],
    'newsletters_title' => ['label' => 'Newsletter — title', 'type' => 'text', 'default' => 'NUFA Newsletter — News from Northern Uganda\'s Filmmakers | NUFA'],
    'newsletters_desc'  => ['label' => 'Newsletter — description', 'type' => 'textarea', 'default' => 'Read and download NUFA newsletters, and subscribe to get news about awards, programmes and the film community in Northern Uganda.'],
    'support_title' => ['label' => 'Support a Programme — title', 'type' => 'text', 'default' => 'Support a Programme — Donate, Sponsor, Volunteer or Partner | NUFA'],
    'support_desc'  => ['label' => 'Support a Programme — description', 'type' => 'textarea', 'default' => "Support NUFA's programmes in Northern Uganda: donate, sponsor, volunteer or partner to help filmmakers develop their craft and tell their stories."],
];

/* ------------------------------------------------ NUFA SACCO — full page content */
$CMS_SCHEMAS['sacco']['fields'] += [
    'hero_lede'   => ['label' => 'Hero — purpose statement', 'type' => 'textarea', 'default' => 'A proposed member-owned savings and credit initiative for filmmakers and creative professionals in Northern Uganda — being explored, not yet operational.'],
    'status_text_dev' => ['label' => 'Status banner — wording when status is "In Development"', 'type' => 'textarea', 'default' => "The NUFA SACCO is in development. Registration, licensing, governance and products are still being finalised and verified. NUFA is not yet accepting deposits, loan repayments or loan applications."],
    'status_text_op'  => ['label' => 'Status banner — wording when status is "Operational"', 'type' => 'textarea', 'default' => "The NUFA SACCO is operational. See the regulatory information and approved products below for what is available and how to join."],
    'about_relationship' => ['label' => 'About — relationship with NUFA', 'type' => 'html', 'default' => "<p>The NUFA SACCO is an initiative of the Northern Uganda Filmmakers Association, intended to serve the association's members and the wider film and creative community. Its legal form, ownership and relationship with NUFA will be set out here once confirmed.</p>"],
    'about_beneficiaries' => ['label' => 'About — intended beneficiaries', 'type' => 'html', 'default' => "<ul><li>Filmmakers and audiovisual creatives in Northern Uganda</li><li>Crew, producers and small production businesses</li><li>Emerging creatives building their first ventures</li></ul>"],
    'vision_text'  => ['label' => 'Vision (DRAFT — subject to approval)', 'type' => 'textarea', 'default' => "A financially resilient film community in Northern Uganda, supported by a member-owned institution that rewards saving and responsible borrowing."],
    'mission_text' => ['label' => 'Mission (DRAFT — subject to approval)', 'type' => 'textarea', 'default' => "To encourage a savings culture and provide suitable, responsibly managed financial services to filmmakers and creative professionals, once the SACCO is legally and operationally approved."],
    'purpose_list' => ['label' => 'Purpose and benefits — list (one per line)', 'type' => 'textarea', 'default' => "Encourage a savings culture among filmmakers\nImprove access to suitable financing\nSupport film production and creative businesses\nHelp members plan for equipment, training and business growth\nPromote financial literacy and responsible borrowing"],
    'membership_eligibility' => ['label' => 'Membership — eligibility requirements', 'type' => 'html', 'default' => "<p>Eligibility requirements are <strong>awaiting confirmation</strong> and will be published once approved.</p>"],
    'membership_process' => ['label' => 'Membership — application process (one step per line: Title | description)', 'type' => 'textarea', 'default' => "Register your interest | Submit the membership enquiry form below. This does not make you a member.\nEligibility check | NUFA confirms whether you meet the approved membership requirements.\nDocuments and fees | You provide the approved documents and any membership fee or share contribution once these are approved.\nConfirmation | You are told the outcome in writing, with your member rights and obligations."],
    'membership_documents' => ['label' => 'Membership — required documents (one per line)', 'type' => 'textarea', 'default' => "To be confirmed once the membership process is approved"],
    'membership_fees' => ['label' => 'Membership — fees and share requirements', 'type' => 'html', 'default' => "<p>Membership fees and share requirements will be published here <strong>once approved</strong>. No fees are being collected.</p>"],
    'membership_rights' => ['label' => 'Membership — rights, responsibilities and obligations', 'type' => 'html', 'default' => "<p>Member rights, responsibilities and obligations will be set out in the SACCO's approved bylaws and published here with the bylaws.</p>"],
    'products_note' => ['label' => 'Products — note shown above the catalogue', 'type' => 'textarea', 'default' => "Products marked Proposed are ideas under consideration only. Loan eligibility, repayment terms, interest, fees and security requirements will be published for each product once it is approved. Nothing here is an offer of credit."],
    'how_it_works' => ['label' => 'How the SACCO works (one step per line: Title | description)', 'type' => 'textarea', 'default' => "Membership | People who meet the approved requirements join the SACCO and become members.\nShares | Members contribute shares, which give them ownership and a vote.\nSavings | Members save regularly, building a pool of member funds.\nCredit assessment | Members may apply for loans, which are assessed against approved criteria.\nRepayment | Loans are repaid under agreed terms, keeping funds available for other members.\nMember participation | Members take part in meetings and help elect those who govern the SACCO."],
    'governance_people' => ['label' => 'Governance and leadership (HTML — board, management, committees, documents; leave blank until approved)', 'type' => 'html', 'default' => ''],
    'governance_note' => ['label' => 'Governance — note when nothing is published yet', 'type' => 'textarea', 'default' => "Board members, management, committees and governance documents will be published here only once they have been formally constituted and approved."],
    'documents_intro' => ['label' => 'Policies and documents — intro', 'type' => 'textarea', 'default' => "Approved bylaws, membership rules, forms, loan policy, privacy notice and complaints procedure will be available to download here once approved."],
    'announcements_intro' => ['label' => 'Announcements — intro', 'type' => 'textarea', 'default' => "Official notices: registration updates, member meetings and product launches."],
    'regulatory_note' => ['label' => 'Regulatory information (HTML)', 'type' => 'html', 'default' => "<p>Verified registration and licence details will be published here once the SACCO is registered and authorised. Until then, nothing on this page should be read as a statement that the SACCO is registered, licensed or regulated.</p>"],
    'enquiry_heading' => ['label' => 'Enquiry — heading', 'type' => 'text', 'default' => 'Membership enquiry'],
    'enquiry_intro'   => ['label' => 'Enquiry — intro', 'type' => 'textarea', 'default' => "Ask a question or register your interest in membership. This is an enquiry only — not an application for membership, savings or a loan, and no money should be sent."],
];
$CMS_SCHEMAS['sacco']['fields']['faq']['default'] = "<details><summary>Is the NUFA SACCO operational?</summary><div class=\"ans\">Not unless the status at the top of this page says so. While it is Proposed or In Development it is not registered or authorised and does not accept deposits, loan repayments or loan applications.</div></details><details><summary>Who can join?</summary><div class=\"ans\">Eligibility requirements will be published once approved. Registering interest does not make you a member.</div></details><details><summary>What will membership cost?</summary><div class=\"ans\">Membership fees and share requirements will be published once approved. NUFA is not collecting any fees now.</div></details><details><summary>Can I save money with the SACCO now?</summary><div class=\"ans\">No. Please do not send money to anyone claiming to represent the NUFA SACCO.</div></details><details><summary>Can I apply for a loan?</summary><div class=\"ans\">Not yet. Loan products, eligibility, repayment terms, interest, fees and security will be published for each approved product. Nothing is promised in advance.</div></details><details><summary>How will withdrawals work?</summary><div class=\"ans\">Withdrawal rules will be set in the approved bylaws and savings product terms.</div></details><details><summary>Will I need a guarantor?</summary><div class=\"ans\">Any guarantor or security requirements will be part of each approved loan product's terms.</div></details><details><summary>How do I make a complaint?</summary><div class=\"ans\">Use the contact form or email the address on this page. A formal complaints procedure will be published with the approved documents.</div></details><details><summary>How can I stay informed?</summary><div class=\"ans\">Submit the enquiry form below and watch the Announcements section for official notices.</div></details>";
$CMS_SCHEMAS['seo']['fields']['sacco_title']['default'] = 'NUFA SACCO — Proposed Savings and Credit Initiative for Filmmakers | NUFA';

$CMS_SCHEMAS['schedule'] = ['label' => 'Schedule & Calendar', 'fields' => [
    'hero_image' => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'production/karamoja-shoot-2.webp'],
    'hero_title' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => 'When and where NUFA programmes happen'],
    'hero_lede'  => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => 'Every programme session, meeting and important date in one calendar — with public holidays marked so you can plan ahead.'],
    'next_heading' => ['label' => 'Coming up — heading', 'type' => 'text', 'default' => 'Next sessions'],
    'calendar_heading' => ['label' => 'Calendar — heading', 'type' => 'text', 'default' => 'NUFA calendar'],
    'calendar_intro' => ['label' => 'Calendar — intro', 'type' => 'textarea', 'default' => 'Browse by month. Select a programme to see only its dates; public holidays and important days always show.'],
    'remind_heading' => ['label' => 'Reminders — heading', 'type' => 'text', 'default' => 'Never miss a session'],
    'remind_intro' => ['label' => 'Reminders — intro', 'type' => 'textarea', 'default' => 'Tell us which programmes you follow and how to reach you. We will send reminders by email or SMS before sessions.'],
]];

/* ------------------------------------------------ NUFA Awards — detailed sections */
$CMS_SCHEMAS['awards']['fields'] += [
    'tech_heading' => ['label' => 'Technical team — heading', 'type' => 'text', 'default' => 'The team behind NUFA Awards'],
    'tech_intro'   => ['label' => 'Technical team — intro', 'type' => 'textarea', 'default' => 'The people who plan, judge, produce and run the awards. Add or edit profiles in Admin → Team (category: Awards Technical Team).'],
    'tech_empty'   => ['label' => 'Technical team — placeholder when no profiles yet', 'type' => 'textarea', 'default' => 'Profiles of the awards technical team — producers, judges coordinators, programmers and production crew — will be published here.'],
    'winners_heading' => ['label' => 'Winners — heading', 'type' => 'text', 'default' => 'Winners of the last two editions'],
    'winners_intro'   => ['label' => 'Winners — intro', 'type' => 'textarea', 'default' => 'Every category, the winning film or person, and the story behind the win.'],
    'winners_empty'   => ['label' => 'Winners — placeholder when none recorded', 'type' => 'text', 'default' => 'Winners for this edition will be published here once they are recorded.'],
    'highlights_heading' => ['label' => 'Highlights & guests — heading', 'type' => 'text', 'default' => 'Highlights and guests'],
    'highlights_empty'   => ['label' => 'Highlights & guests — placeholder', 'type' => 'text', 'default' => 'Highlights of the ceremony and the guests who joined us will be published here.'],
    'moments_heading' => ['label' => 'Key moments — heading', 'type' => 'text', 'default' => 'Key moments'],
    'moments_empty'   => ['label' => 'Key moments — placeholder', 'type' => 'text', 'default' => 'The moments that defined the night will be shared here.'],
    'impact_heading' => ['label' => 'Impact stories — heading', 'type' => 'text', 'default' => 'Impact stories'],
    'impact_intro'   => ['label' => 'Impact stories — intro', 'type' => 'textarea', 'default' => 'What the awards have meant for the filmmakers and communities they celebrate.'],
    'impact_empty'   => ['label' => 'Impact stories — placeholder', 'type' => 'text', 'default' => 'Impact stories will be published here once they have been verified with the people involved.'],
    'films_heading' => ['label' => 'Highlight films — heading', 'type' => 'text', 'default' => 'Highlight films from the last edition'],
    'films_empty'   => ['label' => 'Highlight films — placeholder', 'type' => 'text', 'default' => 'Highlight films, with synopses and trailers, will be published here.'],
    'roadmap_heading' => ['label' => 'Road map — heading', 'type' => 'text', 'default' => 'Road map to the next edition'],
    'roadmap_intro'   => ['label' => 'Road map — intro', 'type' => 'textarea', 'default' => 'The key steps between now and the next awards night. Dates are shown only once the team has confirmed them.'],
    'teaser_heading'   => ['label' => 'Teaser — heading', 'type' => 'text', 'default' => 'A first look at the next edition'],
    'teaser_text'      => ['label' => 'Teaser — text', 'type' => 'textarea', 'default' => 'Teasers, announcements and behind-the-scenes previews for the next edition will appear here.'],
    'teaser_video_url' => ['label' => 'Teaser — video link (YouTube or Vimeo; blank for none)', 'type' => 'text', 'default' => ''],
    'teaser_image'     => ['label' => 'Teaser — image', 'type' => 'image', 'default' => 'gallery/2026/nufa26-15.jpg'],
    'takepart_heading' => ['label' => 'Take part — heading', 'type' => 'text', 'default' => 'How members can be part of the next edition'],
    'takepart_intro'   => ['label' => 'Take part — intro', 'type' => 'textarea', 'default' => 'There are many ways to be part of NUFA Awards, whether you make films, support those who do, or simply love the stories.'],
    'takepart_list'    => ['label' => 'Take part — ways (one per line: Title | Description | Button label | Link)', 'type' => 'textarea', 'default' => "Submit your film | Enter your work in the categories open for the next edition. | Submission details | #submissions\nBecome a member | Members take part in NUFA's guilds, trainings and the awards community. | Join NUFA | membership.php\nVolunteer | Help with production, hospitality, ushering and outreach during the awards. | Volunteer | support.php?type=volunteer\nSponsor or partner | Back the awards as a sponsor or partner. | Sponsor the awards | support.php?type=sponsor\nAttend the ceremony | Book your seat and celebrate with the community. | Tickets | #tickets"],
    'judging_heading' => ['label' => 'Judging & eligibility — heading', 'type' => 'text', 'default' => 'Categories, eligibility and judging'],
    'judging_body'    => ['label' => 'Judging & eligibility — content (HTML)', 'type' => 'html', 'default' => '<p>The approved award categories, eligibility rules and judging criteria will be published here once confirmed for the next edition.</p>'],
    'submit_heading' => ['label' => 'Submissions — heading', 'type' => 'text', 'default' => 'Submit your film'],
    'tickets_heading' => ['label' => 'Tickets — heading', 'type' => 'text', 'default' => 'Tickets and booking'],
    'faq_heading' => ['label' => 'FAQ — heading', 'type' => 'text', 'default' => 'Frequently asked questions'],
    'faq_html'    => ['label' => 'FAQ (HTML: <details><summary>Question</summary><div class="ans">Answer</div></details>)', 'type' => 'html', 'default' => '<details><summary>When is the next NUFA Awards?</summary><div class="ans">The date and venue are published in the road map and the upcoming-edition section as soon as they are confirmed.</div></details><details><summary>Who can submit a film?</summary><div class="ans">Eligibility rules are published with the submission details once the call for entries opens.</div></details><details><summary>How do I get tickets?</summary><div class="ans">Use the tickets and booking section on this page when sales open, or contact the awards team.</div></details><details><summary>Can my organisation sponsor the awards?</summary><div class="ans">Yes. Use the sponsor option on this page or the partnership enquiry form.</div></details>'],
];

$CMS_SCHEMAS['newsletters'] = ['label' => 'Newsletter', 'fields' => [
    'hero_image' => ['label' => 'Hero — background image', 'type' => 'image', 'default' => 'gallery/2026/nufa26-08.jpg'],
    'hero_title' => ['label' => 'Hero — heading', 'type' => 'text', 'default' => 'The NUFA Newsletter'],
    'hero_lede'  => ['label' => 'Hero — paragraph', 'type' => 'textarea', 'default' => 'News, updates and stories from NUFA and the film community in Northern Uganda — to read online, download or receive in your inbox.'],
    'empty_text' => ['label' => 'Placeholder when no issue is published', 'type' => 'text', 'default' => 'The first NUFA newsletter will be published here. Subscribe below to receive it when it is ready.'],
    'archive_heading' => ['label' => 'Archive — heading', 'type' => 'text', 'default' => 'Previous issues'],
    'subscribe_heading' => ['label' => 'Subscribe — heading', 'type' => 'text', 'default' => 'Get the newsletter in your inbox'],
    'subscribe_text' => ['label' => 'Subscribe — text', 'type' => 'textarea', 'default' => 'News about NUFA Awards, programmes, trainings and opportunities for filmmakers. No spam — unsubscribe whenever you like.'],
]];
