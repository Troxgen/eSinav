<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]); // Admin ve Yönetici
require_once __DIR__ . '/../../Settings/db.php';

$success = "";
$error = "";
$building_id = $_SESSION['building_id'];
$user_id = $_SESSION['user_id'];

// Kullanıcının binasına ait oylamaları getir
$votes = $db->prepare("SELECT id, title FROM votes WHERE building_id = ? ORDER BY id DESC");
$votes->execute([$building_id]);
$votes = $votes->fetchAll(PDO::FETCH_ASSOC);

// Seçenek ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vote_id = $_POST['vote_id'] ?? null;
    $option_text = trim($_POST['option_text']);

    if (!$vote_id || empty($option_text)) {
        $error = "❌ Lütfen tüm alanları doldurun.";
    } else {
        // Güvenlik: Seçilen oylama bu kullanıcıya ait mi
        $check = $db->prepare("SELECT id FROM votes WHERE id = ? AND building_id = ?");
        $check->execute([$vote_id, $building_id]);

        if (!$check->fetch()) {
            $error = "❌ Bu oylamaya seçenek ekleme yetkiniz yok.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO vote_options (vote_id, option_text) VALUES (?, ?)");
                $stmt->execute([$vote_id, $option_text]);
                $success = "✅ Seçenek başarıyla eklendi.";
            } catch (PDOException $e) {
                $error = "❌ Hata: " . $e->getMessage();
            }
        }
    }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Yeni Seçenek Ekle</h5>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Oylama Başlığı</label>
          <select class="form-select" name="vote_id" required>
            <option value="">Seçiniz...</option>
            <?php foreach ($votes as $vote): ?>
              <option value="<?= $vote['id'] ?>"><?= htmlspecialchars($vote['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Seçenek Metni</label>
          <input type="text" class="form-control" name="option_text" required>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-success">Ekle</button>
          <a href="index.php?pages=votelist" class="btn btn-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
</section>
