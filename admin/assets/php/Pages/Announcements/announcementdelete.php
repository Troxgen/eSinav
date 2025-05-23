<!-- <?php
require_once __DIR__ . '/../../Settings/db.php';
require_once __DIR__ . '/../../Core/auth.php';

requireLogin();
requireBuilding();

$userId = $_SESSION['user_id'];
$buildingId = $_SESSION['building_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$announcementId = (int) $_GET['id'];

// Bu duyuru gerçekten bu kullanıcıya ait binaya mı ait?
$stmt = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND building_id = ?");
$stmt->execute([$announcementId, $buildingId]);
$exists = $stmt->fetch();

if (!$exists) {
  header("Location: index.php");
  exit;
}

// Okundu olarak işaretle (daha önce yoksa)
$stmt = $pdo->prepare("INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)");
$stmt->execute([$announcementId, $userId]);

header("Location: index.php");
exit;
?>
-->