<?php
/**
 * Page registry — the single description of every public page that the admin
 * "Page Editor" works from: which sections it has (and which can be hidden),
 * which functions can be configured, and which data lists feed it.
 *
 * Section order here MUST match the order of the <section> blocks in the page
 * file (the public pages wrap each one with nufa_show()).
 */

function nufa_registry() {
    $form = fn($type) => $type;
    return [
        'home' => ['label' => 'Home', 'file' => 'index.php', 'schema' => 'home', 'seo' => 'home',
            'sections' => [['hero', 'Hero banner', false], ['who', 'Who We Are'], ['awards', 'NUFA Awards feature'], ['programmes', 'Programmes and Initiatives'], ['impact', 'Our Impact'], ['partnership', 'Partnership feature'], ['news', 'News and Stories'], ['cta', 'Bottom call-to-action']],
            'functions' => [['news_count', 'Number of news cards shown', 'number', '3', 'Between 1 and 6.']],
            'related' => [['Hero photographs', 'sliders.php'], ['Programmes (cards)', 'programmes.php'], ['News & Stories', 'posts.php'], ['Verified impact figures', 'settings.php']]],
        'about' => ['label' => 'About NUFA', 'file' => 'about.php', 'schema' => 'about', 'seo' => 'about',
            'sections' => [['story', 'Our Story'], ['vision', 'Vision & Mission'], ['values', 'Core Values'], ['leadership', 'Leadership & Governance'], ['membership', 'Membership'], ['cta', 'Bottom call-to-action']],
            'functions' => [], 'related' => [['Leadership profiles', 'team.php'], ['Approval switch for vision & mission', 'settings.php'], ['Membership settings', 'membership-settings.php']]],
        'membership' => ['label' => 'Membership', 'file' => 'membership.php', 'schema' => 'membership', 'seo' => 'membership',
            'sections' => [], 'functions' => [],
            'related' => [['Applications', 'members.php'], ['Fee, payment details, reg. number, card, e-mails', 'membership-settings.php'], ['Guilds', 'guilds.php']], 'note' => 'The membership form’s fee, open/closed switch and e-mail wording are managed under Membership settings.'],
        'awards' => ['label' => 'NUFA Awards (landing)', 'file' => 'awards.php', 'schema' => 'awards', 'seo' => 'awards',
            'sections' => [['purpose', 'Purpose & what is on the platform'], ['upcoming', 'Upcoming edition feature'], ['roadmap', 'Road map to the next edition'], ['teaser', 'Teaser (video / image)'], ['winners', 'Winners of the last two editions'], ['highlights', 'Highlights & guests'], ['moments', 'Key moments'], ['impact', 'Impact stories'], ['films', 'Highlight films'], ['archive', 'Previous editions archive'], ['takepart', 'How members can take part'], ['judging', 'Categories, eligibility & judging'], ['submissions', 'Submission portal'], ['tickets', 'Tickets & booking'], ['team', 'Awards technical team'], ['faq', 'FAQ'], ['news', 'Awards news'], ['cta', 'Bottom call-to-action']],
            'functions' => [], 'related' => [['Awards setup (submission portal, tickets, road map, guests, moments, impact, films)', 'awards-setup.php'], ['Award editions', 'award-editions.php'], ['Winners', 'award-winners.php'], ['Technical team (Team → category Awards Technical Team)', 'team.php'], ['Ticket requests', 'enquiries.php?type=tickets'], ['Photo gallery', 'gallery.php']]],
        'programmes' => ['label' => 'Programmes (landing)', 'file' => 'programmes.php', 'schema' => 'programmes', 'seo' => 'programmes',
            'sections' => [['list', 'Status note and programme cards'], ['cta', 'Bottom call-to-action']],
            'functions' => [], 'related' => [['Each programme page', 'programmes.php']]],
        'sacco' => ['label' => 'NUFA SACCO', 'file' => 'sacco.php', 'schema' => 'sacco', 'seo' => 'sacco', 'form' => 'sacco', 'form_label' => 'Expression-of-interest form',
            'sections' => [['status', 'Status banner'], ['about', 'About NUFA SACCO (background, relationship, beneficiaries, vision & mission)'], ['benefits', 'Purpose, benefits & objectives'], ['membership', 'Membership information'], ['products', 'Savings & credit product catalogue'], ['how', 'How the SACCO works'], ['governance', 'Governance & leadership'], ['faq', 'FAQs'], ['documents', 'Policies & documents'], ['announcements', 'Announcements'], ['regulatory', 'Regulatory information'], ['interest', 'Contact, enquiry & application form']],
            'functions' => [], 'related' => [['SACCO manager (status, products, documents, announcements)', 'sacco-manager.php'], ['Submissions', 'enquiries.php?type=sacco']], 'note' => 'The SACCO status (Proposed / In Development / Operational), products, documents, announcements and regulatory details are managed in the SACCO manager.'],
        'network' => ['label' => 'Industry Network', 'file' => 'network.php', 'schema' => 'network', 'seo' => 'network', 'form' => 'network', 'form_label' => 'Registration-of-interest form',
            'sections' => [['status', 'Planned-feature banner'], ['categories', 'Categories & how it works'], ['interest', 'Registration-of-interest form']],
            'functions' => [], 'related' => [['Submissions', 'enquiries.php?type=network'], ['Role choices', 'lists.php']]],
        'news' => ['label' => 'News & Stories', 'file' => 'news.php', 'schema' => 'news', 'seo' => 'news',
            'sections' => [],
            'functions' => [['per_page', 'Stories per page', 'number', '9', 'Between 3 and 30.'], ['show_featured', 'Show the featured story on top', 'switch', '1', ''], ['show_search', 'Show the search box', 'switch', '1', ''], ['show_categories', 'Show the category filter bar', 'switch', '1', '']],
            'related' => [['Stories', 'posts.php'], ['Category list', 'lists.php']]],
        'resources' => ['label' => 'Resources', 'file' => 'resources.php', 'schema' => 'resources', 'seo' => 'resources',
            'sections' => [], 'functions' => [['show_search', 'Show the search box', 'switch', '1', ''], ['show_categories', 'Show the category filter bar', 'switch', '1', '']],
            'related' => [['Library items', 'resources.php'], ['Category list', 'lists.php']]],
        'gallery' => ['label' => 'Gallery & Video Library', 'file' => 'gallery.php', 'schema' => 'gallery', 'seo' => 'gallery',
            'sections' => [['photos', 'Photo gallery'], ['videos', 'Video library']],
            'functions' => [], 'related' => [['Photographs', 'gallery.php'], ['Videos', 'videos.php'], ['Album categories', 'lists.php']]],
        'partners' => ['label' => 'Partner With Us', 'file' => 'partners.php', 'schema' => 'partners', 'seo' => 'partners', 'form' => 'partner', 'form_label' => 'Partnership enquiry form',
            'sections' => [['opportunities', 'Partnership opportunities'], ['wall', 'Partners & previous supporters'], ['cohost', 'Co-host / partner on an activity (apply)'], ['contributors', 'Recent co-hosts & contributors'], ['enquiry', 'Partnership enquiry form']],
            'functions' => [], 'related' => [['Partnership applications (approve, track commitments, publish)', 'partner-applications.php'], ['Partners', 'partners.php'], ['Submissions', 'enquiries.php?type=partner'], ['Form choices', 'lists.php']]],
        'schedule' => ['label' => 'Schedule & Calendar', 'file' => 'schedule.php', 'schema' => 'schedule', 'seo' => 'schedule', 'form' => 'schedule', 'form_label' => 'Reminder sign-up form',
            'sections' => [['next', 'Coming up (next sessions)'], ['calendar', 'Calendar & monthly agenda'], ['reminders', 'Reminder sign-up form']], 'functions' => [],
            'related' => [['Schedule manager (events, holidays, reminders, SMS settings)', 'schedule.php']]],
        'newsletters' => ['label' => 'Newsletter', 'file' => 'newsletters.php', 'schema' => 'newsletters', 'seo' => 'newsletters',
            'sections' => [['latest', 'Latest issue'], ['archive', 'Newsletter archive'], ['subscribe', 'Subscribe form']], 'functions' => [],
            'related' => [['Newsletters (write, upload PDF, e-mail)', 'newsletters.php'], ['Subscribers', 'subscribers.php'], ['News & stories', 'posts.php']]],
        'feedback' => ['label' => 'Feedback', 'file' => 'feedback.php', 'schema' => 'feedback', 'seo' => 'feedback',
            'sections' => [['form', 'Feedback form'], ['showcase', 'Public comments (What people say)']], 'functions' => [],
            'related' => [['Feedback inbox, insights and settings', 'feedback.php']]],
        'support' => ['label' => 'Support a Programme (sponsor · donate · volunteer · partner)', 'file' => 'support.php', 'schema' => 'support', 'seo' => 'support', 'form' => 'support', 'form_label' => 'Support request form',
            'sections' => [], 'functions' => [],
            'related' => [['Support requests (approve / confirm payment)', 'support-requests.php'], ['Payment methods & giving settings', 'support-setup.php'], ['Programmes (which support options each offers)', 'programmes.php']]],
        'contact' => ['label' => 'Contact', 'file' => 'contact.php', 'schema' => 'contact', 'seo' => 'contact', 'form' => 'contact', 'form_label' => 'Contact form',
            'sections' => [], 'functions' => [['show_details', 'Show the contact-details panel beside the form', 'switch', '1', '']],
            'related' => [['Messages received', 'enquiries.php?type=contact'], ['Topic choices', 'lists.php'], ['Phone, e-mail, address, socials', 'settings.php']]],
        'policies' => ['label' => 'Policies (all)', 'file' => 'policy.php', 'schema' => 'policies', 'seo' => null,
            'sections' => [], 'functions' => [], 'related' => [['“Reviewed by a professional” switch', 'settings.php']]],
        'site' => ['label' => 'Site-wide (header & footer)', 'file' => 'index.php', 'schema' => 'site', 'seo' => null,
            'sections' => [], 'functions' => [], 'related' => [['Menus & Footer', 'menus.php'], ['Logo, colours', 'appearance.php'], ['Contact details', 'settings.php']], 'virtual' => true],
    ];
}

function nufa_registry_key_by_file($file) {
    foreach (nufa_registry() as $k => $p) if (empty($p['virtual']) && $p['file'] === $file) return $k;
    return '';
}

/** Is a page section switched on? Sections default to visible. */
function nufa_show($page, $section) {
    global $conn;
    $cfg = nufa_site_settings($conn);
    return ($cfg["show_{$page}_{$section}"] ?? '1') !== '0';
}

/** Per-page function setting (admin → Page Editor → Functions), falling back to the registry default. */
function nufa_fn($page, $key) {
    global $conn;
    $cfg = nufa_site_settings($conn);
    if (isset($cfg["fn_{$page}_{$key}"]) && $cfg["fn_{$page}_{$key}"] !== '') return $cfg["fn_{$page}_{$key}"];
    foreach (nufa_registry()[$page]['functions'] ?? [] as $f) if ($f[0] === $key) return $f[3];
    return '';
}
function nufa_fn_on($page, $key) {
    global $conn;
    $cfg = nufa_site_settings($conn);
    $v = $cfg["fn_{$page}_{$key}"] ?? null;
    if ($v === null) { foreach (nufa_registry()[$page]['functions'] ?? [] as $f) if ($f[0] === $key) return $f[3] !== '0'; return true; }
    return $v !== '0';
}

/** Settings for a form page: enabled flag, intro, button label, success message, notification address. */
function nufa_form_cfg($page) {
    global $conn;
    $cfg = nufa_site_settings($conn);
    $defaults = [
        'contact' => ['button' => 'Send message', 'success' => 'Your message has been sent. The NUFA team will get back to you.', 'intro' => ''],
        'partners' => ['button' => 'Send enquiry', 'success' => 'Your partnership enquiry has been received. The NUFA team will be in touch.', 'intro' => ''],
        'sacco' => ['button' => 'Register interest', 'success' => 'Your expression of interest has been recorded. We will contact you with formal progress updates.', 'intro' => ''],
        'network' => ['button' => 'Register interest', 'success' => 'Your interest has been recorded. We will contact you when the network opens.', 'intro' => ''],
        'support' => ['button' => 'Submit', 'success' => 'Thank you for your support.', 'intro' => ''],
        'schedule' => ['button' => 'Sign me up', 'success' => 'You are signed up. We will send you reminders for the programmes you chose.', 'intro' => ''],
    ][$page] ?? ['button' => 'Submit', 'success' => 'Thank you.', 'intro' => ''];
    $g = fn($k, $d) => (isset($cfg["form_{$page}_{$k}"]) && trim($cfg["form_{$page}_{$k}"]) !== '') ? $cfg["form_{$page}_{$k}"] : $d;
    return [
        'enabled' => ($cfg["form_{$page}_enabled"] ?? '1') !== '0',
        'intro'   => $g('intro', $defaults['intro']),
        'button'  => $g('button', $defaults['button']),
        'success' => $g('success', $defaults['success']),
        'notify'  => $g('notify', ''),
        'closed'  => $g('closed', 'This form is not accepting submissions at the moment. Please contact us directly.'),
    ];
}
