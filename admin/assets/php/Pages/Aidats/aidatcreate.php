<?php
require_once __DIR__ . '/../../Core/auth.php';
require_once __DIR__ . '/../../Settings/db.php';

requireLogin();
requireApartment();

$apartment_id = $_SESSION['apartment_id'];

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tutar = $_POST["tutar"];
    $tarih = $_POST["tarih"];

    try {
        $pdo->beginTransaction();

        // 1) bills tablosuna ekle (type = 'aidat')
        $insertBill = $pdo->prepare("INSERT INTO bills (building_id, type, total_amount, due_date) VALUES (?, 'aidat', ?, ?)");
        $insertBill->execute([$_SESSION['building_id'], $tutar, $tarih]);
        $bill_id = $pdo->lastInsertId();

        // 2) ilgili daireye bill_shares kaydı oluştur
        $insertShare = $pdo->prepare("INSERT INTO bill_shares (bill_id, apartment_id, share_amount, status) VALUES (?, ?, ?, 'beklemede')");
        $insertShare->execute([$bill_id, $apartment_id, $tutar]);

        $pdo->commit();
        header("Location: index.php?pages=aidatlist&created=1");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "❌ Hata oluştu: " . $e->getMessage();
    }
}
?>
    
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Aidat Ekle</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Yeni Aidat Ekle</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="tutar" class="form-label">Tutar (₺)</label>
            <input type="number" step="0.01" name="tutar" id="tutar" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="tarih" class="form-label">Son Ödeme Tarihi</label>
            <input type="date" name="tarih" id="tarih" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Kaydet</button>
        <a href="index.php?pages=aidatlist" class="btn btn-secondary">Geri Dön</a>
    </form>
</div>
</body>
</html>
