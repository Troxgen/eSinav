<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php?pages=meetinglist&status=invalid");
  exit;
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];
$success = $error = "";

// Sadece bu binaya ait toplantıyı getir
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ? AND building_id = ?");
$stmt->execute([$id, $building_id]);
$data = $stmt->fetch();

if (!$data) {
  header("Location: index.php?pages=meetinglist&status=notfound");
  exit;
}

// Form gönderildiyse güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $topic = trim($_POST['topic']);
  $date = $_POST['date'];
  $location = trim($_POST['location']);
  $notes = trim($_POST['notes']);

  try {
    $stmt = $pdo->prepare("UPDATE meetings SET topic = ?, date = ?, location = ?, notes = ? WHERE id = ? AND building_id = ?");
    $stmt->execute([$topic, $date, $location, $notes, $id, $building_id]);
    $success = "✅ Güncelleme başarılı.";

    // Yeni veriyi tekrar al
    $stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ? AND building_id = ?");
    $stmt->execute([$id, $building_id]);
    $data = $stmt->fetch();
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Toplantı Güncelle</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Toplantı Konusu</label>
          <input type="text" name="topic" class="form-control" value="<?= htmlspecialchars($data['topic']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Toplantı Tarihi</label>
          <input type="datetime-local" name="date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($data['date'])) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Yer</label>
          <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($data['location']) ?>" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Notlar</label>
          <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($data['notes']) ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
          <a href="index.php?pages=meetinglist" class="btn btn-secondary">Geri Dön</a>
        </div>
      </form>
    </div>
  </div>
</section>
