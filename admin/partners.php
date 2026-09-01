<?php
$adminTitle = "Partners";
$adminNav = "partners";
require __DIR__ . "/includes/auth.php";

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM partners WHERE id=" . (int)$_GET['delete']);
    header("Location: partners.php?deleted=1");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $tier = trim($_POST['tier']);
    $website = trim($_POST['website']);
    $logo = null;

    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','svg'])) {
            $fname = strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)) . '-' . time() . '.' . $ext;
            $dest = __DIR__ . "/../assets/images/uploads/partners/" . $fname;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                $logo = "uploads/partners/" . $fname;
            }
        }
    }

    if ($name && $logo) {
        $stmt = $conn->prepare("INSERT INTO partners (name, logo, website, tier) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $name, $logo, $website, $tier);
        $stmt->execute();
        header("Location: partners.php?added=1");
        exit;
    }
    $error = "Please provide a partner name and logo image.";
}

$partners = $conn->query("SELECT * FROM partners ORDER BY sort_order ASC");
include __DIR__ . "/includes/admin-header.php";
?>

<?php if (isset($_GET['added'])): ?><div class="admin-alert ok">Partner added.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="admin-alert ok">Partner removed.</div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Add a Partner</h2></div>
  <form class="admin-form" method="post" enctype="multipart/form-data">
    <div class="admin-form-grid">
      <div class="field">
        <label>Organisation name</label>
        <input type="text" name="name" required>
      </div>
      <div class="field">
        <label>Tier / role</label>
        <input type="text" name="tier" placeholder="e.g. Media Partner">
      </div>
    </div>
    <div class="admin-form-grid">
      <div class="field">
        <label>Logo</label>
        <input type="file" name="logo" accept="image/*" required>
      </div>
      <div class="field">
        <label>Website (optional)</label>
        <input type="url" name="website" placeholder="https://">
      </div>
    </div>
    <button type="submit" class="admin-btn admin-btn-brand">Add Partner</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Current Partners</h2></div>
  <table class="admin-table">
    <thead><tr><th></th><th>Name</th><th>Tier</th><th></th></tr></thead>
    <tbody>
      <?php while ($p = $partners->fetch_assoc()): ?>
      <tr>
        <td><img class="admin-thumb" style="object-fit:contain;background:var(--cream)" src="../assets/images/<?php echo htmlspecialchars($p['logo']); ?>" alt=""></td>
        <td><b><?php echo htmlspecialchars($p['name']); ?></b></td>
        <td><?php echo htmlspecialchars($p['tier']); ?></td>
        <td><a href="partners.php?delete=<?php echo $p['id']; ?>" class="admin-btn admin-btn-danger admin-btn-sm" data-confirm="Remove this partner?">Delete</a></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/admin-footer.php"; ?>
