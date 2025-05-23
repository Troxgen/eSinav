<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5]); // Sadece admin ekler

require_once __DIR__ . '/../../Settings/db.php';

$success = $error = "";
$currentLevel = $_SESSION['user_role_level'] ?? 99;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $level = (int)$_POST['level'];

    if ($name === "") {
        $error = "❌ Rol adı boş bırakılamaz.";
    } elseif ($level >= $currentLevel) {
        $error = "❌ Kendi seviyenizden yüksek veya eşit bir rol oluşturamazsınız.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO roles (name, level) VALUES (?, ?)");
            $stmt->execute([$name, $level]);
            $success = "✅ Rol başarıyla eklendi.";
        } catch (PDOException $e) {
            $error = "❌ Veritabanı hatası: " . $e->getMessage();
        }
    }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Yeni Rol Oluştur</h5>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label for="name" class="form-label">Rol Adı</label>
          <input type="text" class="form-control" id="name" name="name" placeholder="Örn: Temizlik Görevlisi" required>
        </div>

        <div class="col-md-6">
          <label for="level" class="form-label">Yetki Seviyesi</label>
          <input type="number" class="form-control" id="level" name="level" min="1" max="<?= $currentLevel - 1 ?>" required>
          <div class="form-text">1 en düşük seviye, <?= $currentLevel - 1 ?> en yüksek oluşturulabilir seviye.</div>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-success">Kaydet</button>
          <button type="reset" class="btn btn-outline-secondary">Temizle</button>
        </div>
      </form>
    </div>
  </div>
</section>
