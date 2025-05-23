<?php
session_start();
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ogrenci_id = $_POST['ogrenci_id'] ?? '';
    $toplam_puan = $_POST['toplam_puan'] ?? 0;

    if (!$ogrenci_id) {
        $error = "Öğrenci ID giriniz.";
    } else {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO sinavlar (ogrenci_id, toplam_puan) VALUES (?, ?)");
            $stmt->execute([$ogrenci_id, $toplam_puan]);

            header('Location: admin.php');
            exit;
        } catch (PDOException $e) {
            $error = "Veritabanı hatası: " . $e->getMessage();
        }
    }
}

// Öğrencileri çekelim seçim için
try {
    $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT kullanici_id, kullanici_adi, ad_soyad FROM kullanicilar WHERE rol = 'ogrenci'");
    $ogrenciler = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Yeni Sınav Ekle</title>
</head>
<body>
<h2>Yeni Sınav Ekle</h2>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    Öğrenci:
    <select name="ogrenci_id" required>
        <option value="">Seçiniz</option>
        <?php foreach($ogrenciler as $ogr): ?>
            <option value="<?=htmlspecialchars($ogr['kullanici_id'])?>"><?=htmlspecialchars($ogr['kullanici_adi'])?> - <?=htmlspecialchars($ogr['ad_soyad'])?></option>
        <?php endforeach; ?>
    </select><br><br>
    Toplam Puan: <input type="number" name="toplam_puan" value="0" min="0"><br><br>
    <button type="submit">Ekle</button>
</form>
<p><a href="admin.php">Geri Dön</a></p>
</body>
</html>
