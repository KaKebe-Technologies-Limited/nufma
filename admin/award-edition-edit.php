<?php
$adminNav = "awards";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ed = [
    'slug'=>'', 'year'=>date('Y'), 'status'=>'upcoming', 'sort_order'=>0, 'is_active'=>1,
    'badge_text'=>'', 'heading_html'=>'', 'theme_quote'=>'', 'description'=>'', 'image'=>'',
    'event_date_label'=>'', 'location_label'=>'', 'chip_title'=>'', 'chip_sub'=>'',
    'stat1_value'=>'','stat1_label'=>'','stat2_value'=>'','stat2_label'=>'',
    'stat3_value'=>'','stat3_label'=>'','stat4_value'=>'','stat4_label'=>'',
    'masonry1'=>'','masonry2'=>'','masonry3'=>'','masonry4'=>'', 'extra_html'=>'',
];
if ($id) {
    $r = $conn->query("SELECT * FROM award_editions WHERE id=$id")->fetch_assoc();
    if ($r) $ed = $r; else { header("Location: award-editions.php"); exit; }
}
$adminTitle = $id ? "Edit Edition" : "New Edition";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '', $_POST['slug'] ?? '')));
    $year = (int)($_POST['year'] ?? 0);
    $status = ($_POST['status'] ?? 'past') === 'upcoming' ? 'upcoming' : 'past';
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $fields = [
        'badge_text' => trim($_POST['badge_text'] ?? ''),
        'heading_html' => trim($_POST['heading_html'] ?? ''),
        'theme_quote' => trim($_POST['theme_quote'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'image' => trim($_POST['image'] ?? $ed['image']),
        'event_date_label' => trim($_POST['event_date_label'] ?? ''),
        'location_label' => trim($_POST['location_label'] ?? ''),
        'chip_title' => trim($_POST['chip_title'] ?? ''),
        'chip_sub' => trim($_POST['chip_sub'] ?? ''),
        'extra_html' => $_POST['extra_html'] ?? '',
    ];
    for ($i = 1; $i <= 4; $i++) {
        $fields["stat{$i}_value"] = trim($_POST["stat{$i}_value"] ?? '');
        $fields["stat{$i}_label"] = trim($_POST["stat{$i}_label"] ?? '');
        $fields["masonry{$i}"] = trim($_POST["masonry{$i}"] ?? '');
    }

    if (!empty($_FILES['image_upload']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $dir = __DIR__ . "/../assets/images/uploads/awards/";
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $fname = ($slug ?: 'edition') . '-hero-' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir . $fname)) {
                $fields['image'] = "uploads/awards/" . $fname;
            }
        }
    }

    if ($slug === '') $error = "Slug is required (e.g. nufa27).";
    elseif ($year < 2000) $error = "Enter a valid year.";

    if (!$error) {
        if ($id) {
            $sets = "slug=?, year=?, status=?, sort_order=?, is_active=?";
            $params = [$slug, $year, $status, $sortOrder, $isActive];
            $types = "sisii";
            foreach ($fields as $k => $v) { $sets .= ", $k=?"; $params[] = $v; $types .= "s"; }
            $params[] = $id; $types .= "i";
            $stmt = $conn->prepare("UPDATE award_editions SET $sets WHERE id=?");
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) $error = "Save failed: " . $conn->error;
        } else {
            $cols = array_merge(['slug','year','status','sort_order','is_active'], array_keys($fields));
            $params = array_merge([$slug, $year, $status, $sortOrder, $isActive], array_values($fields));
            $types = "sisii" . str_repeat("s", count($fields));
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $stmt = $conn->prepare("INSERT INTO award_editions (" . implode(',', $cols) . ") VALUES ($placeholders)");
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) $error = ($conn->errno === 1062) ? "That slug is already used by another edition." : "Save failed: " . $conn->error;
        }
        if (!$error) { header("Location: award-editions.php?saved=1"); exit; }
    }
    $ed = array_merge($ed, $fields, compact('slug','year','status','sortOrder','isActive'));
    $ed['sort_order'] = $sortOrder; $ed['is_active'] = $isActive;
}

include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo $id ? 'Edit Edition' : 'New Edition'; ?></h2></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">

    <div class="admin-form-grid">
      <div class="field">
        <label>Slug (used as the page anchor, e.g. nufa27)</label>
        <input type="text" name="slug" value="<?php echo htmlspecialchars($ed['slug']); ?>" required>
      </div>
      <div class="field">
        <label>Year</label>
        <input type="number" name="year" value="<?php echo (int)$ed['year']; ?>" required>
      </div>
    </div>
    <div class="admin-form-grid">
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="upcoming" <?php echo $ed['status']==='upcoming'?'selected':''; ?>>Upcoming</option>
          <option value="past" <?php echo $ed['status']==='past'?'selected':''; ?>>Past</option>
        </select>
      </div>
      <div class="field">
        <label>Sort order (lower shows first)</label>
        <input type="number" name="sort_order" value="<?php echo (int)$ed['sort_order']; ?>">
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Badge text</label>
        <input type="text" name="badge_text" value="<?php echo htmlspecialchars($ed['badge_text']); ?>" placeholder="e.g. Past Edition">
      </div>
      <div class="field">
        <label>Heading (HTML allowed, e.g. NUFA Awards &lt;span class="serif"&gt;2027&lt;/span&gt;)</label>
        <input type="text" name="heading_html" value="<?php echo htmlspecialchars($ed['heading_html']); ?>">
      </div>
    </div>

    <div class="field">
      <label>Theme quote (optional, e.g. "Stories That Redefine Us")</label>
      <input type="text" name="theme_quote" value="<?php echo htmlspecialchars($ed['theme_quote']); ?>">
    </div>
    <div class="field">
      <label>Description</label>
      <textarea name="description" style="min-height:90px"><?php echo htmlspecialchars($ed['description']); ?></textarea>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Date label</label>
        <input type="text" name="event_date_label" value="<?php echo htmlspecialchars($ed['event_date_label']); ?>" placeholder="e.g. 2 May 2026 or Date to be announced">
      </div>
      <div class="field">
        <label>Location label</label>
        <input type="text" name="location_label" value="<?php echo htmlspecialchars($ed['location_label']); ?>" placeholder="e.g. Acholi Inn, Gulu">
      </div>
    </div>

    <div class="admin-form-grid">
      <div class="field">
        <label>Chip title (small badge on the photo)</label>
        <input type="text" name="chip_title" value="<?php echo htmlspecialchars($ed['chip_title']); ?>" placeholder="e.g. NUFA26">
      </div>
      <div class="field">
        <label>Chip subtitle</label>
        <input type="text" name="chip_sub" value="<?php echo htmlspecialchars($ed['chip_sub']); ?>" placeholder="e.g. 2 May 2026 · Acholi Inn, Gulu">
      </div>
    </div>

    <div class="field">
      <label>Hero photo — upload new, or type a path under assets/images/</label>
      <input type="file" name="image_upload" accept=".jpg,.jpeg,.png,.webp,image/*">
      <input type="text" name="image" value="<?php echo htmlspecialchars($ed['image']); ?>" style="margin-top:8px" placeholder="gallery/2026/nufa26-04.jpg">
      <?php if ($ed['image'] && file_exists(__DIR__ . "/../assets/images/" . $ed['image'])): ?>
        <div class="admin-help" style="margin-top:8px"><img src="../assets/images/<?php echo htmlspecialchars($ed['image']); ?>" style="height:70px;border-radius:6px;object-fit:cover"></div>
      <?php endif; ?>
    </div>

    <div class="admin-card-head" style="margin-top:8px"><h3 style="font-size:1rem">Stats (leave blank to hide the stats row — e.g. only past editions with numbers to show)</h3></div>
    <?php for ($i = 1; $i <= 4; $i++): ?>
    <div class="admin-form-grid">
      <div class="field">
        <label>Stat <?php echo $i; ?> value</label>
        <input type="text" name="stat<?php echo $i; ?>_value" value="<?php echo htmlspecialchars($ed["stat{$i}_value"]); ?>" placeholder="e.g. 1,000+">
      </div>
      <div class="field">
        <label>Stat <?php echo $i; ?> label</label>
        <input type="text" name="stat<?php echo $i; ?>_label" value="<?php echo htmlspecialchars($ed["stat{$i}_label"]); ?>" placeholder="e.g. Gala attendees">
      </div>
    </div>
    <?php endfor; ?>

    <div class="admin-card-head" style="margin-top:8px"><h3 style="font-size:1rem">Photo strip (up to 4 images, path under assets/images/ — leave blank to hide)</h3></div>
    <div class="admin-form-grid">
      <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="field">
        <label>Photo <?php echo $i; ?><?php echo $i===1 ? ' (shown wider)' : ''; ?></label>
        <input type="text" name="masonry<?php echo $i; ?>" value="<?php echo htmlspecialchars($ed["masonry{$i}"]); ?>" placeholder="gallery/2026/nufa26-05.jpg">
      </div>
      <?php endfor; ?>
    </div>

    <div class="field">
      <label>Extra content (advanced — raw HTML rendered after the winners grid: timeline, hosts, guest list, region breakdown, pledge quote. Leave blank if not needed.)</label>
      <textarea name="extra_html" style="min-height:160px;font-family:monospace;font-size:.85rem"><?php echo htmlspecialchars($ed['extra_html']); ?></textarea>
    </div>

    <div class="field" style="display:flex;align-items:center">
      <label style="display:flex;align-items:center;gap:8px;margin:0">
        <input type="checkbox" name="is_active" style="width:auto" <?php echo $ed['is_active'] ? 'checked' : ''; ?>>
        Show on the website
      </label>
    </div>

    <button type="submit" class="admin-btn admin-btn-brand"><?php echo $id ? 'Save Changes' : 'Add Edition'; ?></button>
    <a href="award-editions.php" class="admin-btn admin-btn-line">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
