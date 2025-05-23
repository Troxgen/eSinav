<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin ve yönetici

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

// Binaya bağlı tüm daireleri al
$apartments = $pdo->prepare("SELECT id FROM apartments WHERE building_id = ?");
$apartments->execute([$building_id]);
$apartments = $apartments->fetchAll();
$apartmentCount = count($apartments);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = $_POST['type'];
  $total = $_POST['total_amount'];
  $due = $_POST['due_date'];

  try {
    $pdo->beginTransaction();

    // 1. Fatura oluştur
    $stmt = $pdo->prepare("INSERT INTO bills (building_id, type, total_amount, due_date, status) VALUES (?, ?, ?, ?, 'beklemede')");
    $stmt->execute([$building_id, $type, $total, $due]);
    $bill_id = $pdo->lastInsertId();

    // 2. Paylaştır
    $payPerApartment = round($total / $apartmentCount, 2);
    $insertShare = $pdo->prepare("INSERT INTO bill_shares (bill_id, apartment_id, share_amount, status) VALUES (?, ?, ?, 'beklemede')");

    foreach ($apartments as $apartment) {
      $insertShare->execute([$bill_id, $apartment['id'], $payPerApartment]);
    }

    $pdo->commit();
    $success = "✅ Fatura ve paylar başarıyla eklendi.";
  } catch (PDOException $e) {
    $pdo->rollBack();
    $error = "❌ Hata: " . $e->getMessage();
  }
}
?>


<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Yeni Fatura</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Fatura Türü</label>
          <select name="type" class="form-select" required>
            <option value="elektrik">Elektrik</option>
            <option value="su">Su</option>
            <option value="dogalgaz">Doğalgaz</option>
            <option value="internet">İnternet</option>
            <option value="diger">Diğer</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Toplam Tutar (₺)</label>
          <input type="number" step="0.01" name="total_amount" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Son Ödeme Tarihi</label>
          <input type="date" name="due_date" class="form-control" required>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-success">Faturayı Kaydet ve Paylaştır</button>
        </div>
      </form>
    </div>
  </div>
</section>
