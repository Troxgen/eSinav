<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kadi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT kullanici_id, ad_soyad, rol FROM kullanicilar WHERE kullanici_adi = ? AND sifre = ?");
        $stmt->execute([$kadi, md5($sifre)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['kullanici_id'] = $user['kullanici_id'];
            $_SESSION['ad_soyad'] = $user['ad_soyad'];
            $_SESSION['rol'] = $user['rol'];

            if ($user['rol'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Kullanıcı adı veya şifre yanlış.";
        }
    } catch (PDOException $e) {
        $error = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Giriş Yap</title>
</head>
<body>
<h2>Giriş Yap</h2>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    Kullanıcı Adı: <input type="text" name="kullanici_adi" required><br><br>
    Şifre: <input type="password" name="sifre" required><br><br>
    <button type="submit">Giriş</button>
</form>
</body>
</html>
