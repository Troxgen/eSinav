<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // Admin / Yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("❌ Geçersiz ID.");
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
  // Pay bu binaya mı ait kontrol et
  $stmt = $pdo->prepare("
    SELECT bs.id
    FROM bill_shares bs
    JOIN bills b ON bs.bill_id = b.id
    WHERE bs.id = ? AND b.building_id = ?
  ");
  $stmt->execute([$id, $building_id]);
  $check = $stmt->fetch();

  if (!$check) {
    echo "<div class='alert alert-warning'>⚠️ Bu kayıt bu binaya ait değil veya bulunamadı.</div>";
    exit;
  }

  // Sil
  $delete = $pdo->prepare("DELETE FROM bill_shares WHERE id = ?");
  $delete->execute([$id]);

  if ($delete->rowCount() > 0) {
    header("Location: index.php?pages=bill_share_list&deleted=1");
    exit;
  } else {
    echo "<div class='alert alert-warning'>⚠️ Silinemedi. Zaten silinmiş olabilir.</div>";
  }

} catch (PDOException $e) {
  echo "<div class='alert alert-danger'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
