<?php
require_once __DIR__ . '/../../Core/auth.php';
require_once __DIR__ . '/../../Settings/db.php';

requireLogin();
requireApartment();

$apartment_id = $_SESSION['apartment_id'];
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("❌ Geçersiz ID.");
}

try {
    // Önce gerçekten aidat mı diye kontrol et (aidat olmayan bir şeyi silmeye kalkmasın)
    $check = $pdo->prepare("
        SELECT bs.id
        FROM bill_shares bs
        JOIN bills b ON bs.bill_id = b.id
        WHERE bs.id = ? AND bs.apartment_id = ? AND b.type = 'aidat'
    ");
    $check->execute([$id, $apartment_id]);
    $aidat = $check->fetch();

    if (!$aidat) {
        echo "<div class='alert alert-warning'>⚠️ Bu aidat bu apartmana ait değil veya zaten silinmiş.</div>";
        exit;
    }

    // Silme işlemi
    $stmt = $pdo->prepare("DELETE FROM bill_shares WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header("Location: index.php?pages=aidatlist&deleted=1");
        exit;
    } else {
        echo "<div class='alert alert-warning'>⚠️ Silme işlemi başarısız oldu.</div>";
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Hata: " . $e->getMessage() . "</div>";
}
?>
