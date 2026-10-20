<?php
/**
 * Checks an uploaded hero/banner photo before it is saved.
 * Returns [error, warning]: an error blocks the upload (image too small to look sharp), a warning is shown after saving.
 */
function hero_image_advice($tmpPath, $bytes) {
    $info = @getimagesize($tmpPath);
    if (!$info) return ['That file is not a readable image.', ''];
    [$w, $h] = $info;
    if ($w < 1200) return ["This photo is only {$w} px wide — it would look blurry across the hero. Please upload one at least 1600 px wide (ideal: 1920 × 1080).", ''];
    $warn = [];
    if ($bytes > 600 * 1024) $warn[] = "The photo is " . round($bytes / 1048576, 1) . " MB. Large hero photos slow the homepage down — compress it (for example at squoosh.app, saved as WebP, quality 75) to under about 400 KB.";
    if ($w > 2800) $warn[] = "The photo is {$w} px wide; 1920 px is plenty for a hero and loads much faster.";
    if ($h > 0 && $w / $h < 1.3) $warn[] = "The photo is nearly square or portrait; wide (16:9) photos fill the hero best and avoid heavy cropping.";
    return ['', implode(' ', $warn)];
}
