<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kadi = $_POST['kullanici_adi'] ?? '';
    $sifre = $_POST['sifre'] ?? '';

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, ad, rol FROM kullanicilar WHERE eposta = ? AND sifre = ?");
        $stmt->execute([$kadi, md5($sifre)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['ad'] = $user['ad'];
            $_SESSION['rol'] = $user['rol'];

            // Rol kontrolü varsa buraya eklenebilir
            header("Location: index.php");
            exit();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/pages/auth.css">
</head>
<body>
    <div id="auth">
        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <div class="auth-logo">
                        <a href="#"><img src="assets/images/logo/logo.png" alt="Logo"></a>
                    </div>
                    <h1 class="auth-title">Giriş Yap</h1>
                    <p class="auth-subtitle mb-5">Kayıt olurken kullandığın bilgilerle giriş yap.</p>

                    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="post">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" class="form-control form-control-xl" placeholder="Email" name="kullanici_adi" required>
                            <div class="form-control-icon">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="password" class="form-control form-control-xl" placeholder="Şifre" name="sifre" required>
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-block btn-lg shadow-lg mt-5" type="submit">Giriş Yap</button>
                    </form>

                    <div class="text-center mt-5 text-lg fs-4">
                        <p class="text-gray-600">Hesabın yok mu? <a href="auth-register.html" class="font-bold">Kayıt ol</a>.</p>
                        <p><a class="font-bold" href="auth-forgot-password.html">Şifremi unuttum</a>.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right"></div>
            </div>
        </div>
    </div>
</body>
</html>
