<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5]); // sadece admin

require_once __DIR__ . '/../../Settings/db.php';

$roleId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
$currentLevel = $_SESSION['user_role_level'] ?? 99;

if (!$roleId) {
  header("Location: index.php?pages=rolelist&status=invalid");
  exit;
}

// Rolü al
$stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$role) {
  header("Location: index.php?pages=rolelist&status=notfound");
  exit;
}

// Yetki kontrolü
if ((int)$role['level'] >= $currentLevel) {
  header("Location: index.php?pages=rolelist&status=unauthorized");
  exit;
}

$success = $error = "";

// CSRF Token üret
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "⚠️ Güvenlik doğrulaması başarısız.";
  } else {
    $name = trim($_POST['name']);

    if (empty($name)) {
      $error = "⚠️ Rol adı boş bırakılamaz.";
    } else {
      try {
        $update = $db->prepare("UPDATE roles SET name = ? WHERE id = ?");
        $update->execute([$name, $roleId]);
        $success = "✅ Rol başarıyla güncellendi.";
        $role['name'] = $name; // ekranda güncelle
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
      <h5 class="card-title">Rol Güncelle</h5>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="col-md-6">
          <label for="name" class="form-label">Rol Adı</label>
          <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($role['name']) ?>" required>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
          <a href="index.php?pages=rolelist" class="btn btn-secondary">İptal</a>
        </div>
      </form>
    </div>
  </div>
</section>
