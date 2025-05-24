<?php
session_start();
$error = "";
$success = ""; // ← bu satırı 3. satıra ekle

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eposta = $_POST['eposta'] ?? '';
    $kullanici_adi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';
    $sifre2 = $_POST['sifre2'] ?? '';

    if ($sifre !== $sifre2) {
        $error = "Şifreler aynı değil laaa! 🙃";
    } else {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE eposta = ?");
            $stmt->execute([$eposta]);
            if ($stmt->fetch()) {
                $error = "Bu e-posta zaten kayıtlıymış canım! 👀";
            } else {
                $stmt = $pdo->prepare("INSERT INTO kullanicilar (ad, eposta, sifre, rol) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kullanici_adi, $eposta, md5($sifre), 'ogrenci']);

                $last_id = $pdo->lastInsertId(); // kayıt edilen kullanıcının ID'si
                $_SESSION['kullanici_id'] = $last_id; // sonraki sayfaya taşı

                header("Location: register-detay.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "DB Hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/pages/auth.css">
</head>
<div id="auth">
    <div class="row h-100">
        <div class="col-lg-5 col-12">
            <div id="auth-left">
                <div class="auth-logo">
                    <a href="index.php"><img src="assets/images/logo/logo.png" alt="Logo"></a>
                </div>
                <h1 class="auth-title">Kayıt ol !</h1>
                <p class="auth-subtitle mb-5">Web sitemize kayıt olunuz.</p>

                <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
                <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

                <form method="POST">
                    <div class="form-group position-relative has-icon-left mb-4">
                        <input type="email" name="eposta" class="form-control form-control-xl" placeholder="Mail adresiniz ile kayıt olunuz" required>
                        <div class="form-control-icon"><i class="bi bi-envelope"></i></div>
                    </div>

                    <div class="form-group position-relative has-icon-left mb-4">
                        <input type="text" name="kullanici_adi" class="form-control form-control-xl" placeholder="Eposta" required>
                        <div class="form-control-icon"><i class="bi bi-person"></i></div>
                    </div>

                    <div class="form-group position-relative has-icon-left mb-4">
                        <input type="password" name="sifre" class="form-control form-control-xl" placeholder="Şifre" required>
                        <div class="form-control-icon"><i class="bi bi-shield-lock"></i></div>
                    </div>

                    <div class="form-group position-relative has-icon-left mb-4">
                        <input type="password" name="sifre2" class="form-control form-control-xl" placeholder="Şifreyi Doğrula" required>
                        <div class="form-control-icon"><i class="bi bi-shield-lock"></i></div>
                    </div>

                    <button class="btn btn-primary btn-block btn-lg shadow-lg mt-5" type="submit">Kayıt Ol</button>
                </form>

                <div class="text-center mt-5 text-lg fs-4">
                    <p class='text-gray-600'>hesabınız var mı ? <a href="login.php" class="font-bold">Giriş yapın</a>.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7 d-none d-lg-block">
            <div id="auth-right"></div>
        </div>
    </div>
</div>
