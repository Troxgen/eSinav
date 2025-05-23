<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin ve yönetici
require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php?pages=rentallist&status=invalid");
  exit;
}

$rental_id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
  // Kiralama gerçekten bu binaya mı ait?
  $stmt = $db->prepare("
    SELECT r.id 
    FROM rentals r
    JOIN apartments a ON r.apartment_id = a.id
    WHERE r.id = ? AND a.building_id = ?
  ");
  $stmt->execute([$rental_id, $building_id]);
  $rental = $stmt->fetch();

  if (!$rental) {
    header("Location: index.php?pages=rentallist&status=unauthorized");
    exit;
  }

  // Sil
  $del = $db->prepare("DELETE FROM rentals WHERE id = ?");
  $del->execute([$rental_id]);

  header("Location: index.php?pages=rentallist&deleted=1");
  exit;

} catch (PDOException $e) {
  error_log("❌ Kiralama silme hatası: " . $e->getMessage());
  header("Location: index.php?pages=rentallist&status=dberror");
  exit;
}
