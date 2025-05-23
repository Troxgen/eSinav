<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin / yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?pages=maintenancelist&status=invalid");
    exit();
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];

try {
    // Talep gerçekten bu binaya mı ait?
    $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE id = ? AND building_id = ?");
    $stmt->execute([$id, $building_id]);
    $talep = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$talep) {
        header("Location: index.php?pages=maintenancelist&status=notfound");
        exit();
    }

    // Silme işlemi
    $delete = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ?");
    $delete->execute([$id]);

    header("Location: index.php?pages=maintenancelist&status=deleted");
    exit();

} catch (PDOException $e) {
    header("Location: index.php?pages=maintenancelist&status=error");
    exit();
}
?>