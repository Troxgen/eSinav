<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

$apartment_id = $_SESSION['active_apartment_id'];
$success = $error = "";

// ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php?pages=rentallist&status=invalid");
  exit;
}

$id = (int) $_GET['id'];

// Kayıt kontrolü
$stmt = $db->prepare("
  SELECT r.* FROM rentals r
  JOIN apartments a ON r.apartment_id = a.id
  WHERE r.id = ? AND a.apartment_id = ?
");
$stmt->execute([$id, $apartment_id]);
$data = $stmt->fetch();

if (!$data) {
  header("Location: index.php?pages=rentallist&status=unauthorized");
  exit;
}

// Güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $apartment_input = $_POST['apartment_id'];
  $tenant_input = $_POST['tenant_id'];
  $amount = $_POST['rent_amount'];
  $start = $_POST['start_date'];
  $end = $_POST['end_date'] ?: null;

  try {
    $update = $db->prepare("
      UPDATE rentals 
      SET apartment_id = ?, tenant_id = ?, rent_amount = ?, start_date = ?, end_date = ? 
      WHERE id = ?
    ");
    $update->execute([$apartment_input, $tenant_input, $amount, $start, $end, $id]);
    $success = "✅ Güncelleme başarılı.";

    // Yeniden veri çek
    $stmt->execute([$id, $apartment_id]);
    $data = $stmt->fetch();
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}

// Apartmandaki daire ve kiracılar
$daireler = $db->prepare("SELECT id, block, door_number FROM apartments WHERE apartment_id = ? ORDER BY block, door_number");
$daireler->execute([$apartment_id]);
$daireler = $daireler->fetchAll();

$kiracilar = $db->prepare("SELECT id, full_name FROM users WHERE role = 'kiraci' AND apartment_id = ? ORDER BY full_name");
$kiracilar->execute([$apartment_id]);
$kiracilar = $kiracilar->fetchAll();
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Kiralama Güncelle</h5>
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Daire</label>
          <select name="apartment_id" class="form-select" required>
            <?php foreach ($daireler as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $d['id'] == $data['apartment_id'] ? 'selected' : '' ?>>
                Blok <?= htmlspecialchars($d['block']) ?> - No <?= htmlspecialchars($d['door_number']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Kiracı</label>
          <select name="tenant_id" class="form-select" required>
            <?php foreach ($kiracilar as $u): ?>
              <option value="<?= $u['id'] ?>" <?= $u['id'] == $data['tenant_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Kira Tutarı (₺)</label>
          <input type="number" step="0.01" name="rent_amount" class="form-control" value="<?= $data['rent_amount'] ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Başlangıç Tarihi</label>
          <input type="date" name="start_date" class="form-control" value="<?= $data['start_date'] ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Bitiş Tarihi</label>
          <input type="date" name="end_date" class="form-control" value="<?= $data['end_date'] ?>">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
          <a href="index.php?pages=rentallist" class="btn btn-secondary">Geri Dön</a>
        </div>
      </form>
    </div>
  </div>
</section>
