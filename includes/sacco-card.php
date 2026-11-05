<?php
/**
 * NUFA SACCO membership card — a clean white, two-sided card (deliberately different from the dark NUFA membership card).
 * Front: photo, name, member number and the key bio data. Back: QR code to the verification page, a few bio details and the
 * Chairperson's signature. Self-contained: the styles are printed with the card, so no other stylesheet is needed.
 */
require_once __DIR__ . "/sacco-members.php";
require_once __DIR__ . "/settings.php";
require_once __DIR__ . "/qr.php";
require_once __DIR__ . "/membership.php";

/** ID numbers are only partly shown on the card. */
function sac_mask($s) { $s = trim((string)$s); $n = mb_strlen($s); return $n <= 4 ? $s : str_repeat('•', $n - 4) . mb_substr($s, -4); }

function sac_card_photo($a) {
    if (empty($a['photo']) || !is_file(sac_storage() . $a['photo'])) return '';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file(sac_storage() . $a['photo']);
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(sac_storage() . $a['photo']));
}

function sac_card_css() {
    static $done = false; if ($done) return ''; $done = true;
    return <<<'CSS'
<style>
.sc-set{display:flex;flex-wrap:wrap;gap:22px;justify-content:center;align-items:flex-start}
.sc{--w:360px;--ink:#1c1c1c;--mute:#8a8a8a;--org:#FF7900;--red:#D50000;position:relative;width:var(--w);height:calc(var(--w) * .631);font-size:calc(var(--w) / 36);flex:none;box-sizing:border-box;overflow:hidden;border-radius:1.1em;background:#fff;color:var(--ink);box-shadow:0 .5em 1.8em rgba(0,0,0,.13),0 0 0 .08em #ececec;font-family:Montserrat,"Segoe UI",Arial,sans-serif;line-height:1.3;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.sc *{box-sizing:border-box;margin:0;padding:0}
.sc::before{content:"";position:absolute;left:0;top:0;bottom:0;width:.7em;background:linear-gradient(180deg,var(--org),var(--red))}
.sc::after{content:"";position:absolute;right:0;bottom:0;width:9em;height:9em;border-radius:50%;background:radial-gradient(circle,rgba(255,121,0,.09),rgba(255,121,0,0) 70%);transform:translate(35%,35%);pointer-events:none}
.sc-brand{position:absolute;left:2.4em;top:1.7em;display:flex;align-items:center;gap:.8em}
.sc-logo{width:3em;height:3em;border-radius:50%;border:.1em solid #eee;display:flex;align-items:center;justify-content:center;background:#fff}
.sc-logo img{width:84%;height:84%;object-fit:contain}
.sc-brand b{display:block;font-size:1.05em;font-weight:800;letter-spacing:.12em;color:var(--ink)}
.sc-brand i{display:block;font-style:normal;font-size:.5em;letter-spacing:.2em;text-transform:uppercase;color:var(--mute);margin-top:.35em;font-weight:600}
.sc-chip{position:absolute;right:1.7em;top:2em;font-size:.5em;font-weight:800;letter-spacing:.24em;text-transform:uppercase;color:var(--org);border:.14em solid var(--org);border-radius:2em;padding:.5em 1.1em}
.sc-rule{position:absolute;left:2.4em;right:1.7em;top:5.7em;height:.07em;background:#ececec}
.sc-photo{position:absolute;left:2.4em;top:7.3em;width:8em;height:8em;border-radius:50%;overflow:hidden;background:#f1f1f1;display:flex;align-items:center;justify-content:center;box-shadow:0 0 0 .22em #fff,0 0 0 .4em var(--org)}
.sc-photo img{width:100%;height:100%;object-fit:cover}.sc-photo span{font-size:3em;font-weight:800;color:#c8c8c8}
.sc-info{position:absolute;left:13em;right:1.7em;top:7em}
.sc-info h3{font-family:Georgia,"Times New Roman",serif;font-weight:700;font-size:1.55em;line-height:1.1;color:var(--ink);max-height:1.2em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.sc-role{margin-top:.55em;font-size:.55em;letter-spacing:.2em;text-transform:uppercase;color:var(--mute);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sc-no{margin-top:1.3em}.sc-no small,.sc-g dt{display:block;font-size:.46em;letter-spacing:.2em;text-transform:uppercase;color:var(--mute);font-weight:700}
.sc-no b{display:block;font-size:1.2em;font-weight:800;letter-spacing:.06em;color:var(--red);margin-top:.1em;white-space:nowrap}
.sc-g{display:grid;grid-template-columns:1fr 1fr;gap:.8em 1em;margin-top:1.1em}
.sc-g dd{font-size:.78em;font-weight:700;color:var(--ink);margin-top:.15em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sc-foot{position:absolute;left:2.4em;right:1.7em;bottom:1.1em;display:flex;justify-content:space-between;font-size:.46em;letter-spacing:.16em;text-transform:uppercase;color:#b3b3b3;font-weight:600}
.sc-status{color:#0E7A3F}.sc-status.bad{color:#B71C1C}
.sc-stamp{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%) rotate(-12deg);font-size:3.2em;font-weight:900;letter-spacing:.12em;color:rgba(183,28,28,.55);border:.12em solid rgba(183,28,28,.55);padding:0 .3em;border-radius:.2em}
/* back */
.sc-back-title{position:absolute;left:2.4em;top:1.8em;font-size:.55em;letter-spacing:.24em;text-transform:uppercase;font-weight:800;color:var(--org)}
.sc-qr{position:absolute;left:2.4em;top:4em;width:10.6em;text-align:center}
.sc-qr svg{display:block;width:10.6em;height:10.6em;border-radius:.7em;box-shadow:0 0 0 .1em #ececec}
.sc-qr small{display:block;margin-top:.9em;font-size:.46em;letter-spacing:.22em;text-transform:uppercase;font-weight:700;color:var(--mute)}
.sc-bio{position:absolute;left:15.2em;right:1.7em;top:3.9em}
.sc-bio div{display:flex;justify-content:space-between;gap:1em;padding:.62em 0;border-bottom:.07em solid #f0f0f0}.sc-bio div:last-child{border-bottom:0}
.sc-bio dt{font-size:.5em;letter-spacing:.16em;text-transform:uppercase;color:var(--mute);font-weight:700;align-self:center}
.sc-bio dd{font-size:.76em;font-weight:700;color:var(--ink);text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sc-sign{position:absolute;right:1.7em;bottom:2.5em;width:11em;text-align:right}
.sc-sign img{height:2.3em;width:auto;max-width:9em;object-fit:contain;display:inline-block;margin-bottom:-.3em}
.sc-sign i{display:block;border-top:.07em solid #bbb;margin:.2em 0 .3em}
.sc-sign b{display:block;font-size:.6em;font-weight:700}.sc-sign small{display:block;font-size:.46em;color:var(--mute);letter-spacing:.1em}
.sc.is-expired .sc-qr svg{opacity:.4}
@media print{.idc-single .sc{--w:85.6mm;box-shadow:none;border:.2mm solid #ddd}}
</style>
CSS;
}

/** $o: side ('both'|'front'|'back'), prefix ('' on public pages, '../' in the admin). */
function sac_card_html($conn, array $a, array $o = []) {
    $S = sac_settings($conn); $C = nufa_site_settings($conn);
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $pre = $o['prefix'] ?? ''; $side = $o['side'] ?? 'both';
    $fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '—';
    $expired = !empty($a['card_valid_until']) && strtotime($a['card_valid_until'] . ' 23:59:59') < time();
    $shares = (int)($a['shares_confirmed'] ?: $a['shares_applied']);
    $since = $a['card_issued_at'] ?: $a['approved_at'];
    $sub = trim((string)($a['subregion'] ?? '')); if ($sub === '') $sub = mem_subregion_of($a['district'] ?? '', mem_subregion_map($conn));
    $sig = $S['sacapp_chair_signature'] ?? ''; $sigOk = $sig !== '' && is_file(__DIR__ . '/../assets/images/' . $sig);
    $signer = trim($S['sacapp_signer_name']); $title = $S['sacapp_signer_title'] ?: 'Chairperson, NUFA SACCO';
    $photo = sac_card_photo($a); $initial = mb_strtoupper(mb_substr($a['full_name'], 0, 1));
    $site = preg_replace('#^https?://#', '', rtrim($C['site_website'] ?? '', '/'));
    $link = sac_card_url($a);
    $role = trim($a['creative_role'] ?: $a['occupation']) ?: 'Savings & credit member';
    $out = sac_card_css() . '<div class="sc-set">';
    if ($side !== 'back') {
        $out .= '<div class="sc sc-front' . ($expired ? ' is-expired' : '') . '">'
            . '<div class="sc-brand"><span class="sc-logo"><img src="' . $e($pre) . 'assets/images/logo/nufa-logo.png" alt=""></span><span><b>NUFA SACCO</b><i>Savings &amp; Credit Cooperative</i></span></div><span class="sc-chip">Member</span><div class="sc-rule"></div>'
            . '<div class="sc-photo">' . ($photo ? '<img src="' . $photo . '" alt="">' : '<span>' . $e($initial) . '</span>') . '</div>'
            . '<div class="sc-info"><h3>' . $e($a['full_name']) . '</h3><p class="sc-role">' . $e($role) . '</p>'
            . '<div class="sc-no"><small>Member No.</small><b>' . $e($a['reg_no']) . '</b></div>'
            . '<dl class="sc-g"><div><dt>Sub-region</dt><dd>' . $e($sub ?: '—') . '</dd></div><div><dt>District</dt><dd>' . $e($a['district'] ?: '—') . '</dd></div><div><dt>Member since</dt><dd>' . $fmt($since) . '</dd></div><div><dt>Valid until</dt><dd>' . ($a['card_valid_until'] ? $fmt($a['card_valid_until']) : 'In good standing') . '</dd></div></dl></div>'
            . '<div class="sc-foot"><span>' . $e($site) . '</span><span class="sc-status' . ($expired ? ' bad' : '') . '">' . ($expired ? 'Expired' : 'Active') . ' · ' . $shares . ' share' . ($shares === 1 ? '' : 's') . '</span></div>'
            . ($expired ? '<div class="sc-stamp">EXPIRED</div>' : '') . '</div>';
    }
    if ($side !== 'front') {
        $out .= '<div class="sc sc-back' . ($expired ? ' is-expired' : '') . '"><div class="sc-back-title">Member details</div>'
            . '<div class="sc-qr">' . qr_svg($link, 200, '#1c1c1c', '#ffffff') . '<small>Scan to verify</small></div>'
            . '<dl class="sc-bio"><div><dt>Date of birth</dt><dd>' . $fmt($a['dob']) . '</dd></div><div><dt>Gender</dt><dd>' . $e($a['gender'] ?: '—') . '</dd></div><div><dt>' . $e($a['id_type'] ?: 'ID') . '</dt><dd>' . $e(sac_mask($a['id_number']) ?: '—') . '</dd></div><div><dt>Phone</dt><dd>' . $e($a['phone'] ?: '—') . '</dd></div><div><dt>Village</dt><dd>' . $e($a['village'] ?: '—') . '</dd></div></dl>'
            . '<div class="sc-sign">' . ($sigOk ? '<img src="' . $e($pre) . 'assets/images/' . $e($sig) . '" alt="Signature of the Chairperson">' : '<span style="display:block;height:2em"></span>') . '<i></i><b>' . $e($signer) . '</b><small>' . $e($title) . '</small></div>'
            . '<div class="sc-foot" style="right:13em"><span>' . $e($a['reg_no']) . '</span></div></div>';
    }
    return $out . '</div>';
}
