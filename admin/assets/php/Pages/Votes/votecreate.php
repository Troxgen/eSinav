<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]);
require_once __DIR__ . '/../../Settings/db.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];
  $created_by = $_SESSION['user_id'];
  $building_id = $_SESSION['building_id'];
  $options = array_filter($_POST['options'], fn($opt) => trim($opt) !== '');

  if (empty($title) || count($options) < 1) {
    $error = "❌ Başlık ve en az bir seçenek girilmelidir.";
  } else {
    try {
      $db->beginTransaction();

      $stmt = $db->prepare("
        INSERT INTO votes (title, description, start_date, end_date, created_by, building_id)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$title, $description, $start_date, $end_date, $created_by, $building_id]);
      $vote_id = $db->lastInsertId();

      $optStmt = $db->prepare("INSERT INTO vote_options (vote_id, option_text) VALUES (?, ?)");
      foreach ($options as $opt) {
        $optStmt->execute([$vote_id, trim($opt)]);
      }

      $db->commit();
      $success = "✅ Oylama başarıyla oluşturuldu.";
    } catch (PDOException $e) {
      $db->rollBack();
      $error = "❌ Hata: " . $e->getMessage();
    }
  }
}
?>

<div class="card">
  <div class="card-body pt-3">
    <h5 class="card-title">Yeni Oylama Oluştur</h5>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Oylama Başlığı</label>
        <input type="text" class="form-control" name="title" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Açıklama</label>
        <textarea class="form-control" name="description" rows="3"></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Başlangıç Tarihi</label>
        <input type="date" class="form-control" name="start_date" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Bitiş Tarihi</label>
        <input type="date" class="form-control" name="end_date" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Seçenekler</label>
        <?php for ($i = 0; $i < 5; $i++): ?>
          <input type="text" name="options[]" class="form-control mb-2" placeholder="Seçenek <?= $i+1 ?>">
        <?php endfor; ?>
      </div>
      <button type="submit" class="btn btn-success">Oylamayı Oluştur</button>
    </form>
  </div>
</div>
