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
