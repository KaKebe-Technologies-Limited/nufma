<?php
/**
 * Site-wide contact details & social links, stored in the settings table
 * and editable from admin/settings.php. Falls back to these defaults for
 * any key that hasn't been set (or was cleared) so the site never renders
 * a blank email/phone/address/social link.
 */
function nufa_site_settings($conn) {
    $settings = [
        'site_email'    => 'info@nufa.media',
        'site_phone'    => '+256 700 000 000',
        'site_address'  => 'Acholi Inn, Gulu City — Northern Uganda',
        'ticket_price'  => '30,000',
        'facebook_url'  => 'https://www.facebook.com/nufa2026',
        'instagram_url' => 'https://www.instagram.com/nufa2026',
        'x_url'         => 'https://x.com/NUFA_OFFICIAL1',
        'linkedin_url'  => 'https://www.linkedin.com/company/northern-uganda-film-makers-association-nufa',
    ];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $v = trim($row['setting_value'] ?? '');
            if ($v !== '') $settings[$row['setting_key']] = $v;
        }
    }
    return $settings;
}

function nufa_tel_href($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}
