<?php
session_start();
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kadi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    $adsoyad = $_POST['ad_soyad'] ?? '';
    $rol = $_POST['rol'] ?? '';

    if (!$kadi || !$sifre || !$adsoyad || !in_array($rol, ['ogretmen','ogrenci','admin'])) {
        $error = "Lütfen tüm alanları doğru doldurunuz.";
    } else {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre, ad_soyad, rol) VALUES (?, ?, ?, ?)");
            $stmt->execute([$kadi, md5($sifre), $adsoyad, $rol]);

            header('Location: admin.php');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = "Kullanıcı adı zaten alınmış.";
            } else {
                $error = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Yeni Kullanıcı Ekle</title>
</head>
<body>
<h2>Yeni Kullanıcı Ekle</h2>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    Kullanıcı Adı: <input type="text" name="kullanici_adi" required><br><br>
    Şifre: <input type="password" name="sifre" required><br><br>
    Ad Soyad: <input type="text" name="ad_soyad" required><br><br>
    Rol:
    <select name="rol" required>
        <option value="">Seçiniz</option>
        <option value="admin">Admin</option>
        <option value="ogretmen">Öğretmen</option>
        <option value="ogrenci">Öğrenci</option>
    </select><br><br>
    <button type="submit">Ekle</button>
</form>
<p><a href="admin.php">Geri Dön</a></p>
</body>
</html>
