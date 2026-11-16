<?php
/**
 * NUFA Film Fund page. Nothing about the fund (amounts, dates, rules) is invented: the page ships with neutral wording and every part is
 * editable in Admin → Edit the website → NUFA Film Fund page.
 */
function ff_defaults() {
    return [
        'ff_hero_title' => 'NUFA Film Fund',
        'ff_hero_lede' => 'Supporting Northern Uganda filmmakers to turn strong stories into finished films.',
        'ff_intro_title' => 'About the fund',
        'ff_intro' => "The NUFA Film Fund is the association's way of backing filmmakers from Northern Uganda with the resources to develop, produce and share their work.\n\nThe full details of the fund will be published here by NUFA.",
        'ff_status_show' => '1',
        'ff_status' => 'Details such as how much is available, who may apply and the application dates have not been announced yet. Check this page and our news for the official call.',
        'ff_steps_title' => 'How to get ready',
        'ff_step1_t' => 'Join NUFA', 'ff_step1_d' => 'Become a registered member so you are on the NUFA register and can be reached about funding calls.',
        'ff_step2_t' => 'Choose your guild', 'ff_step2_d' => 'Register under the guild that matches your craft so your work and skills are easy to find.',
        'ff_step3_t' => 'Watch for the call', 'ff_step3_d' => 'When the fund opens, NUFA will announce the rules, dates and how to apply here and on its channels.',
        'ff_btn1_label' => 'Become a member', 'ff_btn1_url' => 'membership.php',
        'ff_btn2_label' => 'Ask about the fund', 'ff_btn2_url' => 'contact.php',
    ];
}
function ff_settings($conn) {
    $d = ff_defaults();
    $r = @$conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ff\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d)) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}
