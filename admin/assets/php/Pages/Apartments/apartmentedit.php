<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([3, 4, 5]); // Yönetici veya Admin

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ Geçersiz ID.");
}

$id = (int) $_GET['id'];

// Bu daire gerçekten bu binaya mı ait?
$stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ? AND building_id = ?");
$stmt->execute([$id, $building_id]);
$data = $stmt->fetch();

if (!$data) {
  die("❌ Bu daire bu binaya ait değil veya bulunamadı.");
}

// Tüm ev sahiplerini role_id = 3 olanlardan çek
$owners = $pdo->query("SELECT id, full_name FROM users WHERE role_id = 3")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $block = $_POST['block'];
  $floor = $_POST['floor'];
  $door = $_POST['door_number'];
  $owner_id = $_POST['owner_id'];

  try {
    $update = $pdo->prepare("UPDATE apartments SET block = ?, floor = ?, door_number = ?, owner_id = ? WHERE id = ? AND building_id = ?");
    $update->execute([$block, $floor, $door, $owner_id, $id, $building_id]);
    $success = "✅ Güncelleme başarılı.";

    // Yeniden veri çek
    $stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ? AND building_id = ?");
    $stmt->execute([$id, $building_id]);
    $data = $stmt->fetch();
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Daire Güncelle</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Blok</label>
          <input type="text" name="block" class="form-control" value="<?= htmlspecialchars($data['block']) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kat</label>
          <input type="number" name="floor" class="form-control" value="<?= $data['floor'] ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kapı No</label>
          <input type="number" name="door_number" class="form-control" value="<?= $data['door_number'] ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Ev Sahibi</label>
          <select name="owner_id" class="form-select" required>
            <option value="">Seçiniz</option>
            <?php foreach ($owners as $o): ?>
              <option value="<?= $o['id'] ?>" <?= $o['id'] == $data['owner_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
        </div>
      </form>
    </div>
  </div>
</section>
