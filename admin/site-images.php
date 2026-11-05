<?php
$adminTitle = "All pictures on the site";
$adminNav = "siteimages";
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/schema.php";
require_once __DIR__ . "/../includes/settings.php";
require_once __DIR__ . "/../includes/sections.php";
require_once __DIR__ . "/../includes/content.php";
require_once __DIR__ . "/../includes/site-images.php";
require_once __DIR__ . "/../includes/membership.php";   // mem_store_upload(), csrf

$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$registry = nufa_registry();
$slots = site_slot_registry();

/* ---- build the page list: every CMS image field + every picture slot, grouped page → section ---- */
$pages = [];   // key => ['label','view','sections'=>[section=>[items]]]
foreach ($CMS_SCHEMAS as $sk => $sc) {
    $imgs = array_filter($sc['fields'], fn($f) => ($f['type'] ?? '') === 'image'); if (!$imgs) continue;
    $pk = null; foreach ($registry as $rk => $rp) if ($rp['schema'] === $sk) { $pk = $rk; break; }
    $key = $pk ?: 'cms-' . $sk; $label = $pk ? $registry[$pk]['label'] : $sc['label']; $view = $pk && empty($registry[$pk]['virtual']) ? $registry[$pk]['file'] : '';
    $pages[$key] = $pages[$key] ?? ['label' => $label, 'view' => $view, 'sections' => []];
    foreach ($imgs as $fk => $def) {
        $sec = strpos($def['label'], ' — ') !== false ? explode(' — ', $def['label'], 2)[0] : 'Pictures';
        $nm = strpos($def['label'], ' — ') !== false ? explode(' — ', $def['label'], 2)[1] : $def['label'];
        $pages[$key]['sections'][$sec][] = ['kind' => 'cms', 'page' => $sk, 'key' => $fk, 'label' => $nm, 'default' => $def['default'], 'hint' => ''];
    }
}
foreach ($slots as $key => [$label, $view, $secs]) {
    $pages[$key] = $pages[$key] ?? ['label' => $label, 'view' => $view, 'sections' => []];
    foreach ($secs as $sec => $items) foreach ($items as [$slot, $nm, $def, $hint]) $pages[$key]['sections'][$sec][] = ['kind' => 'slot', 'page' => 'img', 'key' => $slot, 'label' => $nm, 'default' => $def, 'hint' => $hint];
}
// home first, then in registry order, then the rest
$order = array_merge(array_keys($registry), array_keys($slots)); $sorted = [];
foreach ($order as $k) if (isset($pages[$k])) $sorted[$k] = $pages[$k]; foreach ($pages as $k => $p) if (!isset($sorted[$k])) $sorted[$k] = $p;
$pages = $sorted;

$cur = fn($it) => cms_overrides($conn, $it['page'])[$it['key']] ?? '';
$flash = ''; $errors = [];

/* ---- save one page's pictures ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pk = $_POST['page_key'] ?? '';
    if (!mem_csrf_ok()) $errors[] = 'Session expired — please try again.';
    elseif (isset($pages[$pk])) {
        $up = $conn->prepare("INSERT INTO page_content (page, field_key, field_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE field_value=VALUES(field_value)");
        $del = $conn->prepare("DELETE FROM page_content WHERE page=? AND field_key=?");
        $n = 0;
        // hero slides (home)
        if ($pk === 'home') foreach ($_FILES as $fn => $f) if (strpos($fn, 'slide_') === 0 && ($f['error'] ?? 4) === UPLOAD_ERR_OK) {
            $sid = (int)substr($fn, 6);
            [$saved, $err] = mem_store_upload($f, MEM_IMG_TYPES, 8 * 1048576, 'slide', __DIR__ . "/../assets/images/uploads/content/");
            if ($err) { $errors[] = 'Hero slide: ' . $err; continue; }
            $v = 'uploads/content/' . $saved; $u = $conn->prepare("UPDATE hero_slides SET image=? WHERE id=?"); $u->bind_param("si", $v, $sid); $u->execute(); $n++;
        }
        foreach ($pages[$pk]['sections'] as $items) foreach ($items as $it) {
            $fld = 'p_' . md5($it['page'] . '|' . $it['key']);
            if (!empty($_POST['reset_' . $fld])) { $del->bind_param("ss", $it['page'], $it['key']); $del->execute(); $n++; continue; }
            $f = $_FILES[$fld] ?? null; if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            if ($f['error'] !== UPLOAD_ERR_OK) { $errors[] = $it['label'] . ': upload failed.'; continue; }
            [$saved, $err] = mem_store_upload($f, MEM_IMG_TYPES, 8 * 1048576, 'pic', __DIR__ . "/../assets/images/uploads/content/");
            if ($err) { $errors[] = $it['label'] . ': ' . $err; continue; }
            $v = 'uploads/content/' . $saved; $up->bind_param("sss", $it['page'], $it['key'], $v); $up->execute(); $n++;
        }
        if (!$errors) { header("Location: site-images.php?saved=$n&open=" . urlencode($pk) . "#pg-" . urlencode($pk)); exit; }
    }
}
$open = $_GET['open'] ?? array_key_first($pages);
$slides = $conn->query("SELECT id, image, alt_text AS caption FROM hero_slides ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC);
$thumb = function ($rel) use ($e) { return $rel && is_file(__DIR__ . '/../assets/images/' . $rel) ? '<img src="../assets/images/' . $e($rel) . '" alt="" loading="lazy">' : '<span class="si-none">no picture</span>'; };
include __DIR__ . "/includes/admin-header.php";
?>
<style>
.si-intro{display:flex;gap:14px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:14px}
.si-chips{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 18px}.si-chips a{padding:7px 14px;border-radius:99px;background:#fff;border:1px solid #ddd;font-weight:700;font-size:.82rem;color:#111}.si-chips a:hover{border-color:#D50000;color:#D50000}
.si-page{background:#fff;border:1px solid #e5e5e5;border-radius:14px;margin-bottom:14px;overflow:hidden}
.si-page>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:12px;padding:16px 18px;font-weight:800;font-size:1.02rem}
.si-page>summary::-webkit-details-marker{display:none}.si-page>summary::after{content:"▾";margin-left:auto;color:#888;transition:transform .2s}.si-page[open]>summary::after{transform:rotate(180deg)}
.si-page[open]>summary{border-bottom:1px solid #eee;background:#fafafa}
.si-body{padding:6px 18px 18px}.si-sec{margin:18px 0 10px;font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;font-weight:800;color:#D50000}
.si-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.si-card{border:1px solid #e3e3e3;border-radius:12px;overflow:hidden;background:#fff;display:flex;flex-direction:column}
.si-img{aspect-ratio:16/10;background:#eee;display:flex;align-items:center;justify-content:center}.si-img img{width:100%;height:100%;object-fit:cover;display:block}.si-none{color:#999;font-size:.8rem}
.si-meta{padding:10px 12px;display:flex;flex-direction:column;gap:6px;flex:1}.si-meta b{font-size:.9rem}.si-meta small{color:#777}
.si-meta input[type=file]{font-size:.78rem;max-width:100%}
.si-meta label.rs{display:flex;gap:6px;align-items:center;font-size:.78rem;color:#555}
.si-save{margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.si-man{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}.si-man a{display:block;border:1px solid #e3e3e3;border-radius:12px;padding:14px 16px;background:#fff;color:#111}.si-man a:hover{border-color:#D50000}.si-man b{display:block}.si-man span{font-size:.82rem;color:#666}.si-man em{font-style:normal;float:right;background:#111;color:#fff;border-radius:99px;font-size:.72rem;padding:2px 9px}
</style>
<?php if (isset($_GET['saved'])): ?><div class="admin-alert ok"><?php echo (int)$_GET['saved'] ? (int)$_GET['saved'] . ' change(s) saved — the website shows them immediately.' : 'Nothing was changed.'; ?></div><?php endif; ?>
<?php if ($errors): ?><div class="admin-alert err"><?php echo $e(implode(' ', $errors)); ?></div><?php endif; ?>

<div class="si-intro"><p class="admin-help" style="margin:0;max-width:760px">Every picture on the website, page by page and section by section. Open a page, then use <b>Choose file</b> to upload from your computer or <b>Choose from gallery</b> to use a photo you already uploaded. Tick <b>Use original</b> to go back to the picture that came with the site. Pictures that belong to a record (team, partners, news…) are listed at the bottom with a link to where you change them.</p></div>
<div class="si-chips"><?php foreach ($pages as $k => $p): ?><a href="site-images.php?open=<?php echo urlencode($k); ?>#pg-<?php echo $e($k); ?>"><?php echo $e($p['label']); ?></a><?php endforeach; ?><a href="#managed">Pictures managed elsewhere</a></div>

<?php foreach ($pages as $k => $p):
    $count = 0; foreach ($p['sections'] as $items) $count += count($items); if ($k === 'home') $count += count($slides); ?>
<details class="si-page" id="pg-<?php echo $e($k); ?>" <?php echo $open === $k ? 'open' : ''; ?>>
  <summary><?php echo $e($p['label']); ?> <span class="admin-badge gray"><?php echo $count; ?> picture<?php echo $count === 1 ? '' : 's'; ?></span><?php if ($p['view']): ?><a href="../<?php echo $e($p['view']); ?>" target="_blank" class="admin-help" style="font-weight:600;margin-left:8px" onclick="event.stopPropagation()">view page ↗</a><?php endif; ?></summary>
  <div class="si-body">
  <form method="post" enctype="multipart/form-data"><?php echo mem_csrf_field(); ?><input type="hidden" name="page_key" value="<?php echo $e($k); ?>">
    <?php if ($k === 'home'): ?>
      <h3 class="si-sec">Hero slider — the big pictures at the very top of the page</h3>
      <div class="si-grid">
      <?php foreach ($slides as $i => $s): ?>
        <div class="si-card"><div class="si-img"><?php echo $thumb($s['image']); ?></div><div class="si-meta"><b><?php echo $i === 0 ? 'Slide 1 — the first picture visitors see' : 'Slide ' . ($i + 1); ?></b><?php if ($s['caption']): ?><small><?php echo $e($s['caption']); ?></small><?php endif; ?>
          <input type="file" name="slide_<?php echo (int)$s['id']; ?>" accept="image/jpeg,image/png,image/webp"><small><a href="slide-edit.php?id=<?php echo (int)$s['id']; ?>">Edit text, buttons &amp; order →</a></small></div></div>
      <?php endforeach; ?>
      </div><p class="admin-help" style="margin-top:8px"><a href="sliders.php">Add, remove or reorder slides →</a></p>
    <?php endif; ?>
    <?php foreach ($p['sections'] as $sec => $items): ?>
      <h3 class="si-sec"><?php echo $e($sec); ?></h3>
      <div class="si-grid">
      <?php foreach ($items as $it): $fld = 'p_' . md5($it['page'] . '|' . $it['key']); $ov = $cur($it); $shown = ($ov !== '' && is_file(__DIR__ . '/../assets/images/' . $ov)) ? $ov : $it['default']; ?>
        <div class="si-card"><div class="si-img"><?php echo $thumb($shown); ?></div>
          <div class="si-meta"><b><?php echo $e($it['label']); ?></b><?php if ($it['hint']): ?><small><?php echo $e($it['hint']); ?></small><?php endif; ?>
            <?php echo $ov !== '' ? '<span class="admin-badge">Custom picture</span>' : '<span class="admin-badge gray">Original</span>'; ?>
            <input type="file" name="<?php echo $fld; ?>" accept="image/jpeg,image/png,image/webp">
            <?php if ($ov !== ''): ?><label class="rs"><input type="checkbox" name="reset_<?php echo $fld; ?>" value="1"> Use original picture</label><?php endif; ?></div></div>
      <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <div class="si-save"><button class="admin-btn admin-btn-brand">Save <?php echo $e($p['label']); ?> pictures</button><span class="admin-help">Only the pictures you changed are updated.</span></div>
  </form>
  </div>
</details>
<?php endforeach; ?>

<h2 id="managed" style="margin:30px 0 12px;font-size:1.15rem">Pictures managed elsewhere</h2>
<p class="admin-help" style="margin-bottom:12px">These pictures belong to a record (a person, a story, a programme…), so you change them where the record is edited.</p>
<div class="si-man"><?php foreach (site_managed_images() as [$lbl, $url, $desc, $sql]): $c = ''; if ($sql) { $r = @$conn->query($sql); if ($r) $c = '<em>' . (int)$r->fetch_row()[0] . '</em>'; } ?>
  <a href="<?php echo $e($url); ?>"><?php echo $c; ?><b><?php echo $e($lbl); ?></b><span><?php echo $e($desc); ?></span></a><?php endforeach; ?></div>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>
