<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php?pages=paymentlist&status=invalid");
  exit;
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
  // Ödeme yapan kullanıcı bu binaya ait mi?
  $stmt = $db->prepare("
    SELECT p.id
    FROM payments p
    JOIN users u ON u.id = p.user_id
    JOIN apartments a ON (
      a.owner_id = u.id OR EXISTS (
        SELECT 1 FROM rentals r WHERE r.apartment_id = a.id AND r.tenant_id = u.id
      )
    )
    WHERE p.id = ? AND a.building_id = ?
    LIMIT 1
  ");
  $stmt->execute([$id, $building_id]);
  $exists = $stmt->fetch();

  if (!$exists) {
    header("Location: index.php?pages=paymentlist&status=unauthorized");
    exit;
  }

  // Silme işlemi
  $delete = $db->prepare("DELETE FROM payments WHERE id = ?");
  $delete->execute([$id]);

  header("Location: index.php?pages=paymentlist&deleted=1");
  exit;

} catch (PDOException $e) {
  header("Location: index.php?pages=paymentlist&status=error");
  exit;
}
