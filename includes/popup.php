<?php
/**
 * Welcome popup: an announcement shown once to each visitor (per phone / computer) when the site first opens.
 * Everything is editable in Admin → Edit the website → Welcome popup. Changing the "version" shows it again to everyone.
 */
function popup_defaults() {
    return [
        'popup_enabled' => '1',
        'popup_version' => '1',
        'popup_delay' => '1',
        'popup_image' => 'popup/nufa27-popup.jpg',
        'popup_title' => 'THE COUNTDOWN BEGINS.',
        'popup_lead' => 'The 3rd Edition of the NUFA Awards is officially on its way!',
        'popup_body' => "Northern Uganda's premier film and television awards return to celebrate excellence, recognise talent and elevate the stories shaping our industry.\n\nA new chapter. A bigger stage. Another opportunity to take Northern Uganda's stories to the world.\n\nThe journey to NUFA Awards 2027 begins now.\n\nFurther announcements, including submission guidelines and important dates, will follow.",
        'popup_tagline' => 'Northern Uganda to the World. Northern Uganda with a Global Sight.',
        'popup_tags' => '#NUFAAwards2027 #NUFAAwards #NorthernUgandaToTheWorld #UgandaFilmIndustry',
        'popup_btn_label' => 'Explore NUFA Awards',
        'popup_btn_url' => 'awards.php',
    ];
}

function popup_settings($conn) {
    $d = popup_defaults();
    $r = @$conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'popup\\_%'");
    if ($r) foreach ($r->fetch_all(MYSQLI_ASSOC) as $x) if (array_key_exists($x['setting_key'], $d) && ($x['setting_value'] !== '' || in_array($x['setting_key'], ['popup_btn_label', 'popup_btn_url', 'popup_tags', 'popup_tagline', 'popup_lead'], true))) $d[$x['setting_key']] = $x['setting_value'];
    return $d;
}

/** Markup + script for the popup (printed once, just before </body>). $base = path to the site root ('' on public pages). */
function popup_render($conn, $base = '') {
    static $done = false; if ($done) return ''; $done = true;
    $P = popup_settings($conn);
    $preview = ($_GET['popup'] ?? '') === '1';
    if ($P['popup_enabled'] !== '1' && !$preview) return '';
    $e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $img = ($P['popup_image'] !== '' && $P['popup_image'] !== '__none__' && is_file(__DIR__ . '/../assets/images/' . $P['popup_image'])) ? $P['popup_image'] : '';
    $key = 'nufa_popup_v' . preg_replace('/[^A-Za-z0-9_-]/', '', $P['popup_version']);
    $paras = ''; foreach (preg_split('/\n{2,}/', trim($P['popup_body'])) as $p) { $p = trim($p); if ($p !== '') $paras .= '<p>' . nl2br($e($p)) . '</p>'; }
    ob_start(); ?>
<style>
.wp-ov{position:fixed;inset:0;z-index:100000;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(10,4,4,.72);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);opacity:0;transition:opacity .35s ease}
.wp-ov.is-open{display:flex}.wp-ov.is-show{opacity:1}
.wp-box{position:relative;display:grid;grid-template-columns:minmax(240px,40%) 1fr;width:min(940px,100%);max-height:min(92vh,720px);background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 30px 90px rgba(0,0,0,.55);transform:translateY(26px) scale(.97);transition:transform .45s cubic-bezier(.2,.8,.2,1)}
.wp-ov.is-show .wp-box{transform:none}
.wp-img{background:#5b0a0c;position:relative;min-height:0}.wp-img img{width:100%;height:100%;object-fit:cover;display:block}
.wp-body{padding:clamp(22px,3.4vw,40px);overflow:auto;display:flex;flex-direction:column;justify-content:flex-start;font-family:Montserrat,Inter,system-ui,sans-serif;color:#1c1c1c}
.wp-kick{margin-top:auto;display:inline-flex;align-items:center;gap:10px;font-size:.7rem;font-weight:800;letter-spacing:.22em;text-transform:uppercase;color:#D50000;margin-bottom:10px}.wp-kick::before{content:"";width:30px;height:3px;border-radius:3px;background:linear-gradient(90deg,#FF7900,#D50000)}
.wp-body h2{font-size:clamp(1.6rem,3.6vw,2.4rem);line-height:1.05;margin:0 0 12px;font-weight:900;letter-spacing:-.01em;color:#111}
.wp-lead{font-size:1.08rem;font-weight:700;line-height:1.45;margin:0 0 14px;color:#B00000}
.wp-body p{margin:0 0 11px;font-size:.93rem;line-height:1.65;color:#444}
.wp-tag{margin-top:6px;font-weight:700;color:#111!important;border-left:4px solid #FF7900;padding-left:12px}
.wp-hash{font-size:.8rem!important;color:#D50000!important;font-weight:700;word-break:break-word}
.wp-act{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;margin-bottom:auto}
.wp-btn{display:inline-flex;align-items:center;justify-content:center;padding:13px 26px;border-radius:99px;background:#D50000;color:#fff;font-weight:800;font-size:.9rem;text-decoration:none;box-shadow:0 10px 24px rgba(213,0,0,.3);transition:.2s}.wp-btn:hover{background:#B30000;transform:translateY(-2px);color:#fff}
.wp-skip{background:none;border:1px solid #ddd;border-radius:99px;padding:12px 22px;font:700 .88rem inherit;color:#555;cursor:pointer}.wp-skip:hover{border-color:#D50000;color:#D50000}
.wp-x{position:absolute;right:12px;top:12px;z-index:3;width:42px;height:42px;border-radius:50%;border:0;background:rgba(255,255,255,.92);color:#111;font-size:1.5rem;line-height:1;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center}.wp-x:hover{background:#D50000;color:#fff}
@media(max-width:720px){.wp-box{grid-template-columns:1fr;grid-template-rows:auto 1fr;max-height:94vh}.wp-img{height:min(36vh,290px)}.wp-img img{object-position:center 0}.wp-body{padding:20px 20px 24px}.wp-act .wp-btn,.wp-act .wp-skip{flex:1}}
@media(prefers-reduced-motion:reduce){.wp-ov,.wp-box{transition:none}}
</style>
<div class="wp-ov" id="wpOv" role="dialog" aria-modal="true" aria-labelledby="wpTitle" aria-hidden="true">
  <div class="wp-box">
    <button type="button" class="wp-x" id="wpClose" aria-label="Close announcement">&times;</button>
    <?php if ($img): ?><div class="wp-img"><img data-src="<?php echo $e($base); ?>assets/images/<?php echo $e($img); ?>" alt="NUFA 27 — the NUFA Awards golden trophy" width="641" height="960"></div><?php endif; ?>
    <div class="wp-body">
      <span class="wp-kick">NUFA Awards 2027</span>
      <h2 id="wpTitle"><?php echo $e($P['popup_title']); ?></h2>
      <?php if ($P['popup_lead'] !== ''): ?><p class="wp-lead"><?php echo $e($P['popup_lead']); ?></p><?php endif; ?>
      <?php echo $paras; ?>
      <?php if ($P['popup_tagline'] !== ''): ?><p class="wp-tag"><?php echo $e($P['popup_tagline']); ?></p><?php endif; ?>
      <?php if ($P['popup_tags'] !== ''): ?><p class="wp-hash"><?php echo $e($P['popup_tags']); ?></p><?php endif; ?>
      <div class="wp-act"><?php if ($P['popup_btn_label'] !== '' && $P['popup_btn_url'] !== ''): ?><a class="wp-btn" href="<?php echo $e((preg_match('#^(https?:|mailto:|tel:|\#)#i', $P['popup_btn_url']) ? '' : $base) . $P['popup_btn_url']); ?>" id="wpGo"><?php echo $e($P['popup_btn_label']); ?></a><?php endif; ?><button type="button" class="wp-skip" id="wpSkip">Continue to the website</button></div>
    </div>
  </div>
</div>
<script>(function(){
  var KEY=<?php echo json_encode($key); ?>,DELAY=<?php echo max(0, (int)$P['popup_delay']) * 1000; ?>,ov=document.getElementById('wpOv');if(!ov)return;
  var force=/[?&]popup=1(&|$)/.test(location.search);
  if(!force){try{if(localStorage.getItem(KEY))return;}catch(e){try{if(sessionStorage.getItem(KEY))return;}catch(e2){}}}
  var prev=null;
  function remember(){try{localStorage.setItem(KEY,String(Date.now()));}catch(e){try{sessionStorage.setItem(KEY,'1');}catch(e2){}}}
  function open(){var im=ov.querySelector('img[data-src]');if(im){im.src=im.getAttribute('data-src');im.removeAttribute('data-src');}prev=document.activeElement;ov.classList.add('is-open');ov.setAttribute('aria-hidden','false');document.documentElement.style.overflow='hidden';requestAnimationFrame(function(){requestAnimationFrame(function(){ov.classList.add('is-show');});});var f=document.getElementById('wpGo')||document.getElementById('wpSkip');if(f)f.focus({preventScroll:true});}
  function close(){remember();ov.classList.remove('is-show');ov.setAttribute('aria-hidden','true');document.documentElement.style.overflow='';setTimeout(function(){ov.classList.remove('is-open');},350);if(prev&&prev.focus)try{prev.focus();}catch(e){}}
  document.getElementById('wpClose').addEventListener('click',close);document.getElementById('wpSkip').addEventListener('click',close);
  var go=document.getElementById('wpGo');if(go)go.addEventListener('click',remember);
  ov.addEventListener('click',function(e){if(e.target===ov)close();});
  document.addEventListener('keydown',function(e){if(!ov.classList.contains('is-open'))return;if(e.key==='Escape')close();if(e.key==='Tab'){var f=[].slice.call(ov.querySelectorAll('a[href],button')).filter(function(x){return x.offsetParent!==null;});if(!f.length)return;var a=f[0],z=f[f.length-1];if(e.shiftKey&&document.activeElement===a){e.preventDefault();z.focus();}else if(!e.shiftKey&&document.activeElement===z){e.preventDefault();a.focus();}}});
  function start(){setTimeout(open,DELAY);}
  if(document.readyState==='complete')start();else window.addEventListener('load',start);
  window.nufaPopupOpen=open;
})();</script>
<?php return ob_get_clean();
}
