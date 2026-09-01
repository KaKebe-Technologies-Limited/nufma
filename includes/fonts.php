<?php
/**
 * Site-wide font presets. Selected preset is stored in settings.font_preset
 * and can be changed from the admin dashboard (admin/settings.php).
 */
$FONT_PRESETS = [
  'editorial' => [
    'label'    => 'Editorial (default)',
    'sample'   => 'Manrope + Inter + Fraunces',
    'google'   => "family=Fraunces:opsz,wght@9..144,300;9..144,500;9..144,600&family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800",
    'heading'  => "'Manrope',ui-sans-serif,system-ui,sans-serif",
    'body'     => "'Inter',ui-sans-serif,system-ui,sans-serif",
    'display'  => "'Fraunces',ui-serif,Georgia,serif",
  ],
  'bold' => [
    'label'    => 'Bold & Modern',
    'sample'   => 'Poppins + Work Sans + Playfair Display',
    'google'   => "family=Playfair+Display:ital,wght@1,500;1,600&family=Work+Sans:wght@400;500;600;700&family=Poppins:wght@600;700;800",
    'heading'  => "'Poppins',ui-sans-serif,system-ui,sans-serif",
    'body'     => "'Work Sans',ui-sans-serif,system-ui,sans-serif",
    'display'  => "'Playfair Display',ui-serif,Georgia,serif",
  ],
  'classic' => [
    'label'    => 'Classic Elegant',
    'sample'   => 'Playfair Display + Lato',
    'google'   => "family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,500&family=Lato:wght@400;500;700",
    'heading'  => "'Playfair Display',ui-serif,Georgia,serif",
    'body'     => "'Lato',ui-sans-serif,system-ui,sans-serif",
    'display'  => "'Playfair Display',ui-serif,Georgia,serif",
  ],
];

function nufa_get_font_preset($conn) {
    global $FONT_PRESETS;
    $key = 'editorial';
    $res = $conn->query("SELECT setting_value FROM settings WHERE setting_key='font_preset' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        if (isset($FONT_PRESETS[$row['setting_value']])) $key = $row['setting_value'];
    }
    return array_merge(['key' => $key], $FONT_PRESETS[$key]);
}
