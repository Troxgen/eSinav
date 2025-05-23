<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]);
require_once __DIR__ . '/../../Settings/db.php';

$id = $_GET['id'] ?? null;
$successMessage = '';
$errorMessage = '';

if (!$id || !is_numeric($id)) {
  header("Location: index.php?pages=votelist&status=invalid");
  exit;
}

$vote_id = (int)$id;
$building_id = $_SESSION['building_id'];

// Oylama bilgilerini getir (sadece kendi binasına ait olan)
$stmt = $db->prepare("SELECT * FROM votes WHERE id = ? AND building_id = ?");
$stmt->execute([$vote_id, $building_id]);
$vote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vote) {
  header("Location: index.php?pages=votelist&status=unauthorized");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $start_date = $_POST['start_date'];
  $end_date = $_POST['end_date'];

  try {
    $update = $db->prepare("UPDATE votes SET title = ?, description = ?, start_date = ?, end_date = ? WHERE id = ?");
    $update->execute([$title, $description, $start_date, $end_date, $vote_id]);
    $successMessage = "✅ Oylama başarıyla güncellendi.";

    // Güncel veri tekrar çekilsin
    $stmt->execute([$vote_id, $building_id]);
    $vote = $stmt->fetch(PDO::FETCH_ASSOC);

  } catch (PDOException $e) {
    $errorMessage = "❌ Hata: " . $e->getMessage();
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Oylamayı Güncelle</h5>

      <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
      <?php endif; ?>
      <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
      <?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Başlık</label>
          <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($vote['title']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Başlangıç Tarihi</label>
          <input type="date" name="start_date" class="form-control" required value="<?= $vote['start_date'] ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Bitiş Tarihi</label>
          <input type="date" name="end_date" class="form-control" required value="<?= $vote['end_date'] ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Açıklama</label>
          <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($vote['description']) ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-warning">Güncelle</button>
          <a href="index.php?pages=votelist" class="btn btn-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
</section>
