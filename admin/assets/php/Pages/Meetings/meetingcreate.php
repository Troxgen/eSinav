<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $topic = trim($_POST['topic']);
  $date = $_POST['date'];
  $location = trim($_POST['location']);
  $notes = trim($_POST['notes']);

  if (empty($topic) || empty($date) || empty($location)) {
    $error = "❌ Tüm zorunlu alanları doldurmalısınız.";
  } else {
    try {
      $stmt = $pdo->prepare("INSERT INTO meetings (building_id, topic, date, location, notes) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$building_id, $topic, $date, $location, $notes]);
      $success = "✅ Toplantı başarıyla oluşturuldu.";
    } catch (PDOException $e) {
      $error = "❌ Hata: " . $e->getMessage();
    }
  }
}
?>
<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Yeni Toplantı Oluştur</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Toplantı Konusu</label>
          <input type="text" name="topic" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Toplantı Tarihi</label>
          <input type="datetime-local" name="date" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Yer</label>
          <input type="text" name="location" class="form-control" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Notlar</label>
          <textarea name="notes" class="form-control" rows="4"></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</section>
