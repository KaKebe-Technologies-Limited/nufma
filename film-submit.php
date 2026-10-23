<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/films.php";
require_once __DIR__ . "/includes/forms.php";
require_once __DIR__ . "/includes/content.php";
require_once __DIR__ . "/includes/ui.php";
film_install($conn);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$S = film_settings($conn);
$limitMb = min((int)$S['film_max_mb'], film_server_limit_mb() ?: (int)$S['film_max_mb']);
$errors = []; $done = $_SESSION['film_done'] ?? null; if (empty($_GET['done'])) $done = null;
$v = fn($k) => ui_e($_POST[$k] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) $errors[] = "The upload was too large for this server (limit about " . $limitMb . " MB). Choose a smaller file, compress it, or submit a link instead.";
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $S['film_open'] === '1') {
    if ($g = form_guard($conn, 'film_submit', 'films')) $errors[] = $g;
    $p = fn($k, $m = 190) => form_clean($_POST[$k] ?? '', $m);
    $title = $p('title'); $tag = $p('tagline', 250); $syn = form_clean($_POST['synopsis'] ?? '', 3000); $genre = in_array($_POST['genre'] ?? '', film_genres(), true) ? $_POST['genre'] : '';
    $lang = $p('language', 80); $subs = $p('subtitles', 120); $year = (int)($_POST['year'] ?? 0); $mins = (int)($_POST['runtime_min'] ?? 0); $country = $p('country', 80);
    $rating = in_array($_POST['rating'] ?? '', film_ratings(), true) ? $_POST['rating'] : 'All ages'; $notes = $p('content_notes', 490); $credits = form_clean($_POST['credits'] ?? '', 3000);
    $src = ($_POST['source_type'] ?? '') === 'mp4' ? 'mp4' : 'link'; $link = trim($_POST['video_url'] ?? '');
    $name = $p('fm_name', 150); $bio = form_clean($_POST['fm_bio'] ?? '', 3000); $loc = $p('fm_location', 150); $email = strtolower($p('email')); $phone = $p('phone', 40);
    $soc = []; foreach (['website', 'instagram', 'facebook', 'x_url', 'youtube'] as $k) { $soc[$k] = $p($k, 250); if ($soc[$k] !== '' && !preg_match('#^https?://#i', $soc[$k])) $errors[] = "The " . str_replace('_url', '', $k) . " link must start with https://"; }
    $don = []; foreach (['donate_mtn' => 40, 'donate_airtel' => 40, 'donate_name' => 150, 'donate_link' => 490, 'donate_link_label' => 60, 'donate_bank' => 390, 'donate_note' => 390] as $k => $m) $don[$k] = $p($k, $m);
    if ($don['donate_link'] !== '' && !preg_match('#^https://#i', $don['donate_link'])) $errors[] = "The support link must start with https://";
    foreach (['donate_mtn', 'donate_airtel'] as $k) if ($don[$k] !== '' && strlen(preg_replace('/\D/', '', $don[$k])) < 9) $errors[] = "Check the " . ($k === 'donate_mtn' ? 'MTN' : 'Airtel') . " Mobile Money number.";
    if ((trim($don['donate_mtn'] . $don['donate_airtel']) !== '') && $don['donate_name'] === '') $errors[] = "Enter the name registered on the Mobile Money number, so supporters can confirm it.";
    if ($title === '') $errors[] = "Enter the film title.";
    if (mb_strlen($syn) < 30) $errors[] = "Write a short synopsis (at least a couple of sentences).";
    if ($genre === '') $errors[] = "Choose a genre.";
    if ($lang === '') $errors[] = "Enter the language of the film.";
    if ($year < 1990 || $year > (int)date('Y') + 1) $errors[] = "Enter the year the film was made.";
    if ($mins < 1) $errors[] = "Enter the running time in minutes."; elseif ((int)$S['film_max_minutes'] > 0 && $mins > (int)$S['film_max_minutes']) $errors[] = "Films can be up to " . (int)$S['film_max_minutes'] . " minutes long.";
    if ($name === '') $errors[] = "Enter your name as the filmmaker.";
    if (mb_strlen($bio) < 20) $errors[] = "Tell us a little about yourself (your profile).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = "Enter a phone number we can reach you on.";
    if ($src === 'link' && !film_link_ok($link)) $errors[] = "Give a YouTube or Vimeo link, or a direct https link to an MP4 file.";
    if ($src === 'mp4' && empty($_FILES['video']['name'])) $errors[] = "Choose the MP4 file to upload, or switch to a link.";
    foreach (['declare_rights' => "that you own or control the rights to the film", 'declare_music' => "that music, footage and images are used with permission or a suitable licence", 'declare_consent' => "that the people who appear in the film agreed to appear", 'declare_rules' => "that you have read and follow the submission guidelines", 'declare_display' => "that NUFA may show the film, your profile and your support details on this website once approved"] as $k => $t)
        if (empty($_POST[$k])) $errors[] = "Please confirm $t.";
    $vfile = ''; $vsize = 0; $poster = ''; $photo = '';
    if (!$errors && $src === 'mp4') {
        $f = $_FILES['video']; if ($f['size'] > $limitMb * 1048576) $errors[] = "The film file is larger than the $limitMb MB limit. Compress it or use a link.";
        else { [$fn, $er] = mem_store_upload($f, ['video/mp4' => 'mp4', 'video/x-m4v' => 'mp4'], $limitMb * 1048576, 'film', film_storage()); if ($er) $errors[] = "Film file: $er (only MP4 files are accepted)"; else { $vfile = $fn; $vsize = (int)$f['size']; } }
    }
    if (!$errors && !empty($_FILES['poster']['name'])) { [$fn, $er] = mem_store_upload($_FILES['poster'], MEM_IMG_TYPES, 5 * 1048576, 'poster', FILM_IMG_DIR); if ($er) $errors[] = "Poster: $er"; else $poster = 'uploads/films/' . $fn; }
    if (!$errors && !empty($_FILES['fm_photo']['name'])) { [$fn, $er] = mem_store_upload($_FILES['fm_photo'], MEM_IMG_TYPES, 4 * 1048576, 'maker', FILM_IMG_DIR); if ($er) $errors[] = "Your photo: $er"; else $photo = 'uploads/films/' . $fn; }
    if ($errors && $vfile) @unlink(film_storage() . $vfile);
    if (!$errors) {
        $token = bin2hex(random_bytes(16)); $th = hash('sha256', $token); $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $slug = film_slug($conn, $title); if ($src === 'mp4') $link = '';
        $s = $conn->prepare("INSERT INTO films (token_hash,slug,title,tagline,synopsis,genre,language,subtitles,year,runtime_min,country,rating,content_notes,credits,source_type,video_file,video_url,file_size,poster,fm_name,fm_bio,fm_photo,fm_location,email,phone,website,instagram,facebook,x_url,youtube,donate_mtn,donate_airtel,donate_name,donate_link,donate_link_label,donate_bank,donate_note,ip) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->bind_param("ssssssssiisssssssissssssssssssssssssss", $th, $slug, $title, $tag, $syn, $genre, $lang, $subs, $year, $mins, $country, $rating, $notes, $credits, $src, $vfile, $link, $vsize, $poster, $name, $bio, $photo, $loc, $email, $phone, $soc['website'], $soc['instagram'], $soc['facebook'], $soc['x_url'], $soc['youtube'], $don['donate_mtn'], $don['donate_airtel'], $don['donate_name'], $don['donate_link'], $don['donate_link_label'], $don['donate_bank'], $don['donate_note'], $ip);
        $s->execute(); $id = $conn->insert_id; $ref = 'FS-' . date('Y') . '-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT); $conn->query("UPDATE films SET ref='$ref' WHERE id=$id");
        $statusUrl = mem_site_url() . '/film-status.php?ref=' . $ref . '&t=' . $token;
        mem_send($conn, null, 'film-received', $email, "We received your film: $title ($ref)", "Hello $name,\n\nThank you for submitting “$title” to the NUFA Film Showcase. Our team will review it and email you the outcome. Keep your reference ($ref) and use the button below to check the status at any time.", $statusUrl);
        $to = filter_var($S['film_notify_email'], FILTER_VALIDATE_EMAIL) ? $S['film_notify_email'] : (mem_settings($conn)['admin_notify_email'] ?? '');
        if ($to) mem_send($conn, null, 'film-admin', $to, "New film submitted: $title ($ref)", "$name ($email) submitted “$title” ($genre, $mins min, " . ($src === 'mp4' ? 'MP4 upload' : 'link') . ").\n\nReview it in admin → Film Showcase.", mem_site_url() . '/admin/films.php?view=' . $id, $email);
        $_SESSION['film_done'] = ['ref' => $ref, 'url' => 'film-status.php?ref=' . $ref . '&t=' . $token, 'title' => $title]; header("Location: film-submit.php?done=1"); exit;
    }
}
$pageTitle = "Submit your film — NUFA Film Showcase"; $pageDesc = "Filmmakers can submit short films and productions to the NUFA Film Showcase for review and public viewing."; $activeNav = "films"; $canonicalPath = "film-submit.php";
include __DIR__ . "/includes/header.php";
echo ui_page_hero('Film Showcase', 'Submit your film', $S['film_submit_intro'], 'gallery/2026/nufa26-31.jpg', [['Resources', 'resources.php'], ['Film Showcase', 'films.php'], ['Submit']]);
$sel = fn($k, $val, $d = '') => (($_POST[$k] ?? $d) === $val) ? 'selected' : '';
$ck = fn($k) => !empty($_POST[$k]) ? 'checked' : '';
?>
<section class="section"><div class="container" style="max-width:860px">
<?php if ($done): ?>
  <div class="ac-result is-pass"><h2>Film received</h2><p>Thank you. “<?php echo ui_e($done['title']); ?>” is waiting for review. Your reference is <b><?php echo ui_e($done['ref']); ?></b>.</p><p>Save this private link — it is the only way to check your film’s status:</p><p><a class="btn btn-gold" href="<?php echo ui_e($done['url']); ?>">Check my film’s status</a></p><p class="muted" style="font-size:.85rem">We also emailed this link to you.</p></div>
<?php elseif ($S['film_open'] !== '1'): ?>
  <div class="empty-state"><h3>Submissions are closed for now</h3><p>Please check back soon.</p></div>
<?php else: ?>
  <?php foreach ($errors as $e): ?><div style="padding:12px 16px;border-radius:12px;background:#fde8e8;color:#7a1c1c;margin-bottom:10px"><?php echo ui_e($e); ?></div><?php endforeach; ?>
  <div class="op-note"><b>Before you start.</b> <ul style="margin:8px 0 0 18px"><?php foreach (film_lines($S['film_guidelines']) as $l) echo '<li>' . ui_e($l) . '</li>'; ?></ul>Films up to <?php echo (int)$S['film_max_minutes']; ?> minutes. MP4 uploads up to <?php echo $limitMb; ?> MB — for bigger files, send a YouTube or Vimeo link.</div>
  <form method="post" enctype="multipart/form-data" class="ac-form" novalidate id="filmform"><?php echo form_open_fields(); ?>
    <h3>1 · About the film</h3>
    <div class="ac-two"><div class="field"><label>Film title</label><input name="title" value="<?php echo $v('title'); ?>" required></div><div class="field"><label>One-line tagline (optional)</label><input name="tagline" value="<?php echo $v('tagline'); ?>"></div></div>
    <div class="field"><label>Synopsis</label><textarea name="synopsis" rows="4" required><?php echo $v('synopsis'); ?></textarea></div>
    <div class="ac-two"><div class="field"><label>Genre</label><select name="genre"><option value="">Select…</option><?php foreach (film_genres() as $g): ?><option <?php echo $sel('genre', $g); ?>><?php echo ui_e($g); ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Language spoken</label><input name="language" value="<?php echo $v('language'); ?>" placeholder="e.g. Luo, English"></div></div>
    <div class="ac-two"><div class="field"><label>Subtitles (if any)</label><input name="subtitles" value="<?php echo $v('subtitles'); ?>" placeholder="e.g. English"></div><div class="field"><label>Country / place where it was made</label><input name="country" value="<?php echo $v('country'); ?>" placeholder="e.g. Uganda"></div></div>
    <div class="ac-two"><div class="field"><label>Year made</label><input type="number" name="year" min="1990" max="<?php echo (int)date('Y') + 1; ?>" value="<?php echo $v('year') ?: date('Y'); ?>"></div><div class="field"><label>Running time (minutes)</label><input type="number" name="runtime_min" min="1" value="<?php echo $v('runtime_min'); ?>"></div></div>
    <div class="ac-two"><div class="field"><label>Suitable for</label><select name="rating"><?php foreach (film_ratings() as $r): ?><option <?php echo $sel('rating', $r, 'All ages'); ?>><?php echo ui_e($r); ?></option><?php endforeach; ?></select></div><div class="field"><label>Content notes (violence, language…)</label><input name="content_notes" value="<?php echo $v('content_notes'); ?>"></div></div>
    <div class="field"><label>Cast and crew credits — one per line, e.g. “Director: Name”</label><textarea name="credits" rows="4"><?php echo $v('credits'); ?></textarea></div>
    <div class="field"><label>Poster (JPG, PNG or WebP, up to 5 MB — recommended)</label><input type="file" name="poster" accept="image/jpeg,image/png,image/webp"></div>

    <h3 style="margin-top:20px">2 · The film</h3>
    <div class="ac-ticks" style="grid-template-columns:1fr 1fr"><label class="op-check"><input type="radio" name="source_type" value="mp4" <?php echo ($_POST['source_type'] ?? 'mp4') === 'mp4' ? 'checked' : ''; ?>> Upload an MP4 file</label><label class="op-check"><input type="radio" name="source_type" value="link" <?php echo ($_POST['source_type'] ?? '') === 'link' ? 'checked' : ''; ?>> Send a link</label></div>
    <div class="field" id="src-mp4"><label>MP4 file (up to <?php echo $limitMb; ?> MB)</label><input type="file" name="video" accept="video/mp4"></div>
    <div class="field" id="src-link"><label>YouTube or Vimeo link, or a direct https link to an MP4</label><input type="url" name="video_url" value="<?php echo $v('video_url'); ?>" placeholder="https://"><small class="muted">Unlisted YouTube or Vimeo videos work too. Make sure embedding is allowed.</small></div>

    <h3 style="margin-top:20px">3 · About you (your public profile)</h3>
    <div class="ac-two"><div class="field"><label>Your name (as the filmmaker)</label><input name="fm_name" value="<?php echo $v('fm_name'); ?>" required></div><div class="field"><label>Where are you based?</label><input name="fm_location" value="<?php echo $v('fm_location'); ?>"></div></div>
    <div class="field"><label>Short biography</label><textarea name="fm_bio" rows="4" required><?php echo $v('fm_bio'); ?></textarea></div>
    <div class="field"><label>Your photo (optional)</label><input type="file" name="fm_photo" accept="image/jpeg,image/png,image/webp"></div>
    <div class="ac-two"><div class="field"><label>Email (not shown publicly)</label><input type="email" name="email" value="<?php echo $v('email'); ?>" required></div><div class="field"><label>Phone / WhatsApp (not shown publicly)</label><input name="phone" value="<?php echo $v('phone'); ?>" required></div></div>
    <div class="ac-two"><div class="field"><label>Website (optional)</label><input type="url" name="website" value="<?php echo $v('website'); ?>" placeholder="https://"></div><div class="field"><label>Instagram (optional)</label><input type="url" name="instagram" value="<?php echo $v('instagram'); ?>" placeholder="https://"></div>
      <div class="field"><label>Facebook (optional)</label><input type="url" name="facebook" value="<?php echo $v('facebook'); ?>" placeholder="https://"></div><div class="field"><label>X / Twitter (optional)</label><input type="url" name="x_url" value="<?php echo $v('x_url'); ?>" placeholder="https://"></div>
      <div class="field"><label>YouTube channel (optional)</label><input type="url" name="youtube" value="<?php echo $v('youtube'); ?>" placeholder="https://"></div></div>

    <h3 style="margin-top:20px">4 · Support for your film (optional)</h3>
    <p class="muted" style="margin:0"><?php echo ui_e($S['film_donate_note']); ?> Add the details where you would like viewers to send support. They appear on your film’s page under “Support this filmmaker”.</p>
    <div class="ac-two"><div class="field"><label>MTN Mobile Money number</label><input name="donate_mtn" value="<?php echo $v('donate_mtn'); ?>"></div><div class="field"><label>Airtel Money number</label><input name="donate_airtel" value="<?php echo $v('donate_airtel'); ?>"></div></div>
    <div class="field"><label>Name registered on the Mobile Money number</label><input name="donate_name" value="<?php echo $v('donate_name'); ?>"></div>
    <div class="ac-two"><div class="field"><label>Online support link (PayPal, Flutterwave, GoFundMe…)</label><input type="url" name="donate_link" value="<?php echo $v('donate_link'); ?>" placeholder="https://"></div><div class="field"><label>Button label</label><input name="donate_link_label" value="<?php echo $v('donate_link_label'); ?>" placeholder="e.g. Support on PayPal"></div></div>
    <div class="field"><label>Bank details (optional)</label><input name="donate_bank" value="<?php echo $v('donate_bank'); ?>" placeholder="Bank, account name, account number"></div>
    <div class="field"><label>Message to supporters (optional)</label><input name="donate_note" value="<?php echo $v('donate_note'); ?>" placeholder="e.g. Your support will fund my next film"></div>

    <h3 style="margin-top:20px">5 · Declarations</h3>
    <label class="op-check"><input type="checkbox" name="declare_rights" value="1" <?php echo $ck('declare_rights'); ?>> I own or control the rights to this film.</label>
    <label class="op-check"><input type="checkbox" name="declare_music" value="1" <?php echo $ck('declare_music'); ?>> Music, footage and images in the film are used with permission or a suitable licence.</label>
    <label class="op-check"><input type="checkbox" name="declare_consent" value="1" <?php echo $ck('declare_consent'); ?>> The people who appear in the film agreed to appear.</label>
    <label class="op-check"><input type="checkbox" name="declare_rules" value="1" <?php echo $ck('declare_rules'); ?>> I have read and follow the submission guidelines above.</label>
    <label class="op-check"><input type="checkbox" name="declare_display" value="1" <?php echo $ck('declare_display'); ?>> NUFA may show the film, my profile and my support details on this website once approved, and may remove them if a problem is found.</label>
    <button class="btn btn-gold" style="margin-top:14px">Submit my film for review</button>
  </form>
  <script>
  (function(){var f=document.getElementById('filmform');if(!f)return;var m=document.getElementById('src-mp4'),l=document.getElementById('src-link');function s(){var v=f.querySelector('input[name=source_type]:checked');v=v?v.value:'mp4';m.style.display=v==='mp4'?'':'none';l.style.display=v==='link'?'':'none';}
  f.querySelectorAll('input[name=source_type]').forEach(function(r){r.addEventListener('change',s)});s();
  f.addEventListener('submit',function(){var b=f.querySelector('button.btn-gold');if(b){b.disabled=true;b.textContent='Uploading… please keep this page open';}});})();
  </script>
<?php endif; ?>
</div></section>
<?php include __DIR__ . "/includes/footer.php"; ?>
