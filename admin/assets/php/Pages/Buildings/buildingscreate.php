<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // Yönetici ve Admin

require_once __DIR__ . '/../../Settings/db.php';

requireBuilding();
$building_id = $_SESSION['building_id'];

$success = $error = "";

// Ev sahibi listesi: role_id = 3
$owners = $pdo->query("SELECT id, full_name FROM users WHERE role_id = 3")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $block = $_POST['block'];
  $floor = $_POST['floor'];
  $door = $_POST['door_number'];
  $owner_id = $_POST['owner_id'];

  try {
    $stmt = $pdo->prepare("INSERT INTO apartments (block, floor, door_number, owner_id, building_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$block, $floor, $door, $owner_id, $building_id]);
    $success = "✅ Daire başarıyla eklendi.";
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}
?>


<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Yeni Daire</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Blok</label>
          <input type="text" name="block" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kat</label>
          <input type="number" name="floor" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kapı No</label>
          <input type="number" name="door_number" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Ev Sahibi</label>
          <select name="owner_id" class="form-select" required>
            <option value="">Seçiniz</option>
            <?php foreach ($owners as $o): ?>
              <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</section>
