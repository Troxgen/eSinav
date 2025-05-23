<?php
session_start();
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
if ($id) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("DELETE FROM sinavlar WHERE sinav_id = ?");
        $stmt->execute([$id]);

        header('Location: admin.php');
        exit;
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    header('Location: admin.php');
    exit;
}
?>
