<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([3, 4, 5]); // Admin veya Yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ Geçersiz ID.");
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
  // Sadece bu binaya aitse sil
  $stmt = $pdo->prepare("DELETE FROM apartments WHERE id = ? AND building_id = ?");
  $stmt->execute([$id, $building_id]);

  if ($stmt->rowCount() > 0) {
    header("Location: index.php?pages=apartmentlist&deleted=1");
    exit;
  } else {
    echo "<div class='alert alert-warning'>⚠️ Silme yetkiniz yok veya kayıt bulunamadı.</div>";
  }
} catch (PDOException $e) {
  echo "<div class='alert alert-danger'>❌ Silinemedi: " . $e->getMessage() . "</div>";
}
?>
