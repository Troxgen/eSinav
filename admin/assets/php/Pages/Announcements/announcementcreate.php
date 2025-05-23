<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // sadece yönetici (4) ve admin (5)

require_once __DIR__ . '/../../Settings/db.php';

// Eğer projenizde "requireBuilding()" gibi bir fonksiyon varsa ve bina/apartman seçimi zorunluysa:
requireBuilding();

$building_id = $_SESSION['building_id'];
$userId = $_SESSION['user_id'];

$success = $error = "";

// Bu roller örnek olarak verildi. Projedeki rollerle eşleştirin
$roles = [
  'kiraci'     => 'Kiracı',
  'ev_sahibi'  => 'Ev Sahibi',
  'yonetici'   => 'Yönetici',
  'guvenlik'   => 'Güvenlik'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $visibleRolesInput = $_POST['visible_roles'] ?? [];

  // Geçerli rollerle kesişim
  $validRoles = array_keys($roles);
  $visibleRolesInput = array_intersect($visibleRolesInput, $validRoles);

  // JSON formatına dönüştür
  // (veritabanındaki visible_roles alanı JSON tipinde ise bu şekilde saklayabiliriz)
  $visibleRoles = json_encode(array_values($visibleRolesInput), JSON_THROW_ON_ERROR);

  if (empty($title) || empty($content)) {
    $error = "Tüm alanları doldurmanız gerekiyor.";
  }

  if (!$error) {
    try {
      $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, visible_roles, created_by, building_id)
        VALUES (?, ?, ?, ?, ?)
      ");
      $stmt->execute([$title, $content, $visibleRoles, $userId, $building_id]);
      $success = "✅ Duyuru başarıyla oluşturuldu.";
    } catch (PDOException $e) {
      $error = "❌ Hata: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Duyuru Oluştur</title>
  <!-- Projenizdeki Bootstrap dosya yolunu düzenleyin -->
  <link href="../../../../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Duyuru Oluştur</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" class="mt-3">
    <div class="mb-3">
      <label for="title" class="form-label">Başlık</label>
      <input type="text" name="title" id="title" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="content" class="form-label">İçerik</label>
      <textarea name="content" id="content" rows="4" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Gösterilecek Roller</label><br>
      <?php foreach ($roles as $roleKey => $roleLabel): ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="visible_roles[]" value="<?= $roleKey ?>" id="role_<?= $roleKey ?>">
          <label class="form-check-label" for="role_<?= $roleKey ?>">
            <?= htmlspecialchars($roleLabel) ?>
          </label>
        </div>
      <?php endforeach; ?>
      <p class="small text-muted">Bu duyuruyu hangi rollerin görebileceğini seçin.</p>
    </div>

    <button type="submit" class="btn btn-primary">Kaydet</button>
    <a href="index.php?pages=announcementlist" class="btn btn-secondary">Geri</a>
  </form>

</body>
</html>
