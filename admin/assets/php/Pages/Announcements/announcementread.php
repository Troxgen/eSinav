<?php
require_once __DIR__ . '/../../Settings/db.php';
require_once __DIR__ . '/../../Core/auth.php';

requireLogin();
requireBuilding(); // 👈 ekle, building_id kontrolü için

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$announcementId = (int) $_GET['id'];
$userId = $_SESSION['user_id'];
$buildingId = $_SESSION['building_id'];

// Duyurunun gerçekten bu binaya ait olup olmadığını kontrol et
$stmt = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND building_id = ?");
$stmt->execute([$announcementId, $buildingId]);
$valid = $stmt->fetch();

if (!$valid) {
  header("Location: index.php");
  exit;
}

// Daha önce işaretlenmemişse, okundu olarak işaretle
$stmt = $pdo->prepare("INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)");
$stmt->execute([$announcementId, $userId]);

header("Location: index.php");
exit;
?>
