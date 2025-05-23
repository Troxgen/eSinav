<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php?pages=meetinglist&status=invalid");
  exit;
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
  // Toplantı gerçekten bu binaya mı ait?
  $stmt = $pdo->prepare("SELECT id FROM meetings WHERE id = ? AND building_id = ?");
  $stmt->execute([$id, $building_id]);
  $meeting = $stmt->fetch();

  if (!$meeting) {
    header("Location: index.php?pages=meetinglist&status=notfound");
    exit;
  }

  // Silme işlemi
  $delete = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
  $delete->execute([$id]);

  header("Location: index.php?pages=meetinglist&status=deleted");
  exit;

} catch (PDOException $e) {
  header("Location: index.php?pages=meetinglist&status=dberror");
  exit;
}
