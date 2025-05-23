<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin & yönetici

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

// Aktif binadaki kullanıcıları çek
$users = $db->prepare("
  SELECT u.id, u.full_name
  FROM users u
  JOIN apartments a ON (a.owner_id = u.id OR EXISTS (
    SELECT 1 FROM rentals r WHERE r.apartment_id = a.id AND r.tenant_id = u.id
  ))
  WHERE a.building_id = ?
  GROUP BY u.id
  ORDER BY u.full_name
");
$users->execute([$building_id]);
$users = $users->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_POST['user_id'];
  $type = $_POST['type'];
  $amount = $_POST['amount'];
  $reference_id = $_POST['reference_id'] ?: null;

  if (!$user_id || !$type || !$amount) {
    $error = "❌ Tüm alanlar zorunludur.";
  } else {
    try {
      $stmt = $db->prepare("
        INSERT INTO payments (user_id, type, amount, reference_id, paid_at)
        VALUES (?, ?, ?, ?, NOW())
      ");
      $stmt->execute([$user_id, $type, $amount, $reference_id]);
      $success = "✅ Ödeme başarıyla kaydedildi.";
    } catch (PDOException $e) {
      $error = "❌ Hata: " . $e->getMessage();
    }
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Yeni Ödeme Kaydı</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Kullanıcı</label>
          <select name="user_id" class="form-select" required>
            <option value="">Seçiniz</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Ödeme Türü</label>
          <select name="type" class="form-select" required>
            <option value="">Seçiniz</option>
            <option value="aidat">Aidat</option>
            <option value="kira">Kira</option>
            <option value="fatura">Fatura</option>
            <option value="diger">Diğer</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Tutar (₺)</label>
          <input type="number" step="0.01" name="amount" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Referans ID</label>
          <input type="text" name="reference_id" class="form-control" placeholder="İlgili fatura/aidat ID'si">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-success">Kaydet</button>
          <a href="index.php?pages=paymentlist" class="btn btn-secondary">Geri</a>
        </div>
      </form>
    </div>
  </div>
</section>
