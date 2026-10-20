<?php
/**
 * Site-wide organisation details, social links and feature switches, stored in
 * the `settings` table and edited from admin → Site Settings. Any key that is
 * unset (or cleared) falls back to the verified defaults below, so public
 * pages never render a blank contact detail.
 */
function nufa_site_settings($conn) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $settings = [
        'site_name'     => 'Northern Uganda Filmmakers Association',
        'site_email'    => 'nuf.association@gmail.com',
        'site_phone'    => '+256 774 757 587',
        'site_phone2'   => '+256 779 712 990',
        'site_address'  => 'Plot 75, Jomo Kenyatta Road, Gulu City, Uganda',
        'site_website'  => 'https://www.nufa.media/',
        'facebook_url'  => 'https://www.facebook.com/nufa2026',
        'instagram_url' => 'https://www.instagram.com/nufa2026',
        'x_url'         => 'https://x.com/NUFA_OFFICIAL1',
        'linkedin_url'  => 'https://www.linkedin.com/company/northern-uganda-film-makers-association-nufa',
        'youtube_url'   => '',
        // feature switches (stored as '1' / '0')
        'tickets_open'      => '0',   // show ticket information on the Awards page
        'ticket_price'      => '',
        'vm_approved'       => '0',   // vision & mission approved by leadership
        'policies_approved' => '0',   // legal pages reviewed by a qualified professional
        // appearance
        'brand_gold' => '#D4AF63', 'brand_ink' => '#111111', 'site_logo' => '', 'site_favicon' => '', 'og_default_image' => '', 'show_topbar' => '1', 'show_clock' => '1', 'whatsapp_enabled' => '1', 'whatsapp_number' => '0779712990', 'whatsapp_message' => 'Hello NUFA, I would like to make an enquiry.', 'clock_timezone' => 'Africa/Kampala', 'pwa_enabled' => '1',
        // editable option lists (one entry per line)
        'list_contact_topics'      => "General enquiry
Membership
Awards
Programmes
Partnership and sponsorship",
        'list_org_types'           => "Government institution
Development partner or donor
Cultural organisation
Educational institution
Media company
Private company
Non-profit organisation
Individual",
        'list_partner_interests'   => "Sponsorship of NUFA Awards
Film training and mentorship
Equipment and technical support
Film screenings and audience development
Youth and emerging filmmaker initiatives
Cultural preservation projects
Creative enterprise development
Digital innovation and industry infrastructure
Research, advocacy and industry engagement
Other",
        'list_network_roles'       => "Film production company
Director or producer
Screenwriter
Actor or casting professional
Cinematographer
Editor or post-production specialist
Sound professional
Production designer
Film trainer or mentor
Industry partner
Other",
        'list_news_categories'     => "NUFA News
NUFA Awards
Filmmaker Profiles
Film Industry Updates
Training and Workshops
Opportunities and Calls
Events and Screenings
Culture and Storytelling
Partner News",
        'list_resource_categories' => "Film production guides
Training materials
Industry reports
Calls for submissions
Funding and grant opportunities
Filmmaking opportunities
Policies and guidelines
Annual reports
Event reports
Publications",
        'list_team_categories'     => "Board\nManagement\nSecretariat\nGuild Leaders\nAdvisors\nAwards Technical Team",
        'list_partner_categories'  => "Government\nMedia\nTechnology\nIndustry bodies\nProduction\nCorporate sponsors\nCommunity & culture\nEducation",
        'list_gallery_categories'  => "Awards ceremony\nRed carpet\nProductions\nTraining & workshops\nScreenings\nCommunity\nBehind the scenes",
        // NUFA SACCO (status must reflect verified reality)
        'sacco_status' => 'ongoing',              // proposed | in_development | ongoing | operational
        'sacco_applications_open' => '0',         // membership application form on the SACCO page
        'sacco_vm_approved' => '0',               // SACCO vision/mission approved
        'sacco_reg_number' => '', 'sacco_regulator' => '', 'sacco_licence_status' => '', 'sacco_reg_date' => '',
        // verified impact figures — the Impact section stays qualitative until these are filled
        'impact1_value' => '', 'impact1_label' => '',
        'impact2_value' => '', 'impact2_label' => '',
        'impact3_value' => '', 'impact3_label' => '',
        'impact4_value' => '', 'impact4_label' => '',
    ];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $v = trim($row['setting_value'] ?? '');
            if ($v !== '' || in_array($row['setting_key'], ['tickets_open', 'vm_approved', 'policies_approved', 'show_topbar', 'show_clock', 'whatsapp_enabled', 'pwa_enabled', 'sacco_applications_open', 'sacco_vm_approved'], true)) $settings[$row['setting_key']] = $v;
        }
    }
    return $cache = $settings;
}

function nufa_tel_href($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/** An editable option list (one entry per line) as an array. */
function nufa_list($cfg, $key) {
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string)($cfg[$key] ?? '')))));
}

/** Darkens/lightens a #RRGGBB colour by mixing with black (negative) or white (positive) — used to derive the brand shades. */
function nufa_shade($hex, $pct) {
    $hex = ltrim($hex, '#'); if (strlen($hex) !== 6 || !ctype_xdigit($hex)) return '#D4AF63';
    $out = '#';
    foreach ([0, 2, 4] as $i) { $c = hexdec(substr($hex, $i, 2)); $c = $pct >= 0 ? $c + (255 - $c) * $pct / 100 : $c * (100 + $pct) / 100; $out .= str_pad(dechex((int)round(max(0, min(255, $c)))), 2, '0', STR_PAD_LEFT); }
    return strtoupper($out);
}
