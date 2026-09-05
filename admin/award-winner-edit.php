<?php
$adminNav = "awards";
require __DIR__ . "/includes/auth.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$w = ['edition_id' => isset($_GET['edition']) ? (int)$_GET['edition'] : 0, 'category'=>'', 'winner_title'=>'', 'person_name'=>'', 'is_honorary'=>0, 'sort_order'=>0];
if ($id) {
    $r = $conn->query("SELECT * FROM award_winners WHERE id=$id")->fetch_assoc();
    if ($r) $w = $r; else { header("Location: award-winners.php"); exit; }
}
$adminTitle = $id ? "Edit Winner" : "New Winner";

$editions = $conn->query("SELECT id, slug, year FROM award_editions ORDER BY year DESC");
$editionList = [];
while ($e = $editions->fetch_assoc()) $editionList[] = $e;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editionId = (int)($_POST['edition_id'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $winnerTitle = trim($_POST['winner_title'] ?? '');
    $personName = trim($_POST['person_name'] ?? '');
    $isHonorary = isset($_POST['is_honorary']) ? 1 : 0;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!$editionId) $error = "Choose an edition.";
    elseif ($category === '' || $winnerTitle === '') $error = "Category and winner/film are required.";

    if (!$error) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE award_winners SET edition_id=?, category=?, winner_title=?, person_name=?, is_honorary=?, sort_order=? WHERE id=?");
            $stmt->bind_param("isssiii", $editionId, $category, $winnerTitle, $personName, $isHonorary, $sortOrder, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO award_winners (edition_id, category, winner_title, person_name, is_honorary, sort_order) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isssii", $editionId, $category, $winnerTitle, $personName, $isHonorary, $sortOrder);
        }
        $stmt->execute();
        header("Location: award-winners.php?edition=" . $editionId . "&saved=1");
        exit;
    }
    $w = compact('editionId','category','winnerTitle','personName','isHonorary','sortOrder');
    $w['edition_id'] = $editionId; $w['winner_title'] = $winnerTitle; $w['person_name'] = $personName; $w['is_honorary'] = $isHonorary; $w['sort_order'] = $sortOrder;
}

include __DIR__ . "/includes/admin-header.php";
?>

<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo $id ? 'Edit Winner' : 'New Winner'; ?></h2></div>
  <form class="admin-form" method="post">
    <div class="field">
      <label>Edition</label>
      <select name="edition_id" required>
        <?php foreach ($editionList as $e): ?>
        <option value="<?php echo $e['id']; ?>" <?php echo $w['edition_id'] == $e['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(strtoupper($e['slug'])); ?> (<?php echo (int)$e['year']; ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-form-grid">
      <div class="field">
        <label>Category</label>
        <input type="text" name="category" value="<?php echo htmlspecialchars($w['category']); ?>" placeholder="e.g. Best Director" required>
      </div>
      <div class="field">
        <label>Winner (film/project title)</label>
        <input type="text" name="winner_title" value="<?php echo htmlspecialchars($w['winner_title']); ?>" required>
      </div>
    </div>
    <div class="admin-form-grid">
      <div class="field">
        <label>Person (optional)</label>
        <input type="text" name="person_name" value="<?php echo htmlspecialchars($w['person_name']); ?>" placeholder="e.g. Emma Okello">
      </div>
      <div class="field">
        <label>Sort order</label>
        <input type="number" name="sort_order" value="<?php echo (int)$w['sort_order']; ?>">
      </div>
    </div>
    <div class="field" style="display:flex;align-items:center">
      <label style="display:flex;align-items:center;gap:8px;margin:0">
        <input type="checkbox" name="is_honorary" style="width:auto" <?php echo $w['is_honorary'] ? 'checked' : ''; ?>>
        Honorary award (highlighted style)
      </label>
    </div>
    <button type="submit" class="admin-btn admin-btn-brand"><?php echo $id ? 'Save Changes' : 'Add Winner'; ?></button>
    <a href="award-winners.php?edition=<?php echo (int)$w['edition_id']; ?>" class="admin-btn admin-btn-line">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
