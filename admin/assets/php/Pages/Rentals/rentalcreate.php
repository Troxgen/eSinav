<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin ve yönetici
require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

// Aktif binaya ait daireleri çek
$daireler = $db->prepare("SELECT id, block, floor, door_number FROM apartments WHERE building_id = ? ORDER BY block, door_number");
$daireler->execute([$building_id]);
$daireler = $daireler->fetchAll();

// Kiracı rolündeki kullanıcıları çek
$kiracilar = $db->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'kiraci' ORDER BY u.full_name");
$kiracilar->execute();
$kiracilar = $kiracilar->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $apartment_input = $_POST['apartment_id'];
  $tenant_input = $_POST['tenant_id'];
  $amount = $_POST['rent_amount'];
  $start = $_POST['start_date'];
  $end = $_POST['end_date'] ?: null;

  try {
    $stmt = $db->prepare("INSERT INTO rentals (apartment_id, tenant_id, rent_amount, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$apartment_input, $tenant_input, $amount, $start, $end]);
    $success = "✅ Kiralama başarıyla eklendi.";
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Yeni Kiralama</h5>
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Daire</label>
          <select name="apartment_id" class="form-select" required>
            <option value="">Seçiniz</option>
            <?php foreach ($daireler as $d): ?>
              <option value="<?= $d['id'] ?>">
                Blok <?= htmlspecialchars($d['block']) ?> - Kat <?= htmlspecialchars($d['floor']) ?> - No <?= htmlspecialchars($d['door_number']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Kiracı</label>
          <select name="tenant_id" class="form-select" required>
            <option value="">Seçiniz</option>
            <?php foreach ($kiracilar as $u): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Kira Tutarı</label>
          <input type="number" step="0.01" name="rent_amount" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Başlangıç Tarihi</label>
          <input type="date" name="start_date" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Bitiş Tarihi (isteğe bağlı)</label>
          <input type="date" name="end_date" class="form-control">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-success">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</section>
