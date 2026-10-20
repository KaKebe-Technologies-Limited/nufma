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
            if ($v !== '' || in_array($row['setting_key'], ['tickets_open', 'vm_approved', 'policies_approved'], true)) $settings[$row['setting_key']] = $v;
        }
    }
    return $cache = $settings;
}

function nufa_tel_href($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}
