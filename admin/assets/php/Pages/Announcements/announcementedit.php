<?php
require_once __DIR__ . '/../../Settings/db.php';
require_once __DIR__ . '/../../Core/auth.php';

requireLogin();
requireRole([4, 5]); // sadece yönetici(4) ve admin(5)

$success = "";
$error = "";

$building_id = $_SESSION['building_id'];
$userId = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<div class='alert alert-danger'>Geçersiz duyuru ID.</div>";
  exit;
}

$id = (int)$_GET['id'];

// Duyuru çek (building filtresiyle!)
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? AND building_id = ?");
$stmt->execute([$id, $building_id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$announcement) {
  echo "<div class='alert alert-warning'>❌ Bu duyuru bulunamadı veya bu binaya ait değil.</div>";
  exit;
}

$roles = [
  'kiraci' => 'Kiracı',
  'ev_sahibi' => 'Ev Sahibi',
  'yonetici' => 'Yönetici',
  'guvenlik' => 'Güvenlik'
];
$visibleRoles = json_decode($announcement['visible_roles'], true) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);
  $visibleRolesInput = $_POST['visible_roles'] ?? [];

  $validRoles = array_keys($roles);
  $visibleRolesInput = array_intersect($visibleRolesInput, $validRoles);
  $visibleRoles = json_encode(array_values($visibleRolesInput), JSON_THROW_ON_ERROR);

  if (empty($title) || empty($content)) {
    $error = "Tüm alanları doldurmanız gerekiyor.";
  } else {
    try {
      $update = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, visible_roles = ?, created_by = ? WHERE id = ? AND building_id = ?");
      $update->execute([$title, $content, $visibleRoles, $userId, $id, $building_id]);

      $success = "✅ Duyuru başarıyla güncellendi.";

      // Güncellenmiş veriyi tekrar çek
      $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ? AND building_id = ?");
      $stmt->execute([$id, $building_id]);
      $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
      $visibleRoles = json_decode($announcement['visible_roles'], true) ?? [];
    } catch (PDOException $e) {
      $error = "❌ Hata: " . $e->getMessage();
    }
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Duyuru Düzenle</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-12">
          <label class="form-label">Başlık</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($announcement['title']) ?>" required>
        </div>

        <div class="col-md-12">
          <label class="form-label">İçerik</label>
          <textarea name="content" rows="5" class="form-control" required><?= htmlspecialchars($announcement['content']) ?></textarea>
        </div>

        <div class="col-md-12">
          <label class="form-label">Görülebilir Roller</label>
          <select name="visible_roles[]" class="form-select" multiple>
            <?php foreach ($roles as $key => $label): ?>
              <option value="<?= $key ?>" <?= in_array($key, $visibleRoles) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Birden fazla rol seçebilirsiniz.</small>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
          <a href="index.php?pages=announcementlist" class="btn btn-secondary">Geri Dön</a>
        </div>
      </form>
    </div>
  </div>
</section>
