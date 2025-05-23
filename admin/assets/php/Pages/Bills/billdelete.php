<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin / yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ Geçersiz ID.");
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
    // Fatura gerçekten bu binaya mı ait?
    $stmt = $pdo->prepare("SELECT id FROM bills WHERE id = ? AND building_id = ?");
    $stmt->execute([$id, $building_id]);
    $bill = $stmt->fetch();

    if (!$bill) {
        die("❌ Bu fatura bu binaya ait değil veya bulunamadı.");
    }

    $pdo->beginTransaction();

    // Önce payları sil
    $pdo->prepare("DELETE FROM bill_shares WHERE bill_id = ?")->execute([$id]);

    // Sonra faturayı sil
    $pdo->prepare("DELETE FROM bills WHERE id = ?")->execute([$id]);

    $pdo->commit();

    header("Location: index.php?pages=billlist&deleted=1");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div class='alert alert-danger'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
