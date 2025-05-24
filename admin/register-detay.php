<?php
session_start();
$error = "";
$success = "";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=e_sinav;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $okul_sorgu = $pdo->query("SELECT id, ad FROM okullar");
    $okullar = $okul_sorgu->fetchAll(PDO::FETCH_ASSOC);

    $sinif_sorgu = $pdo->query("SELECT id, ad FROM siniflar");
    $siniflar = $sinif_sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Veritabanı bağlantı hatası: " . $e->getMessage();
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
                    <a href="index.php"><img src="assets/images/logo/logo.png" alt="Logo"></a>
                </div>
                <h1 class="auth-title">Kayıt ol !</h1>
                <p class="auth-subtitle mb-5">Web sitemize kayıt olunuz.</p>

                <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
                <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group mb-4">
                        <label>Okulunuzu Seçiniz</label>
                        <select class="form-select" name="okul_id" required>
                            <option value="">-- Okul Seçin --</option>
                            <?php foreach ($okullar as $okul): ?>
                                <option value="<?= $okul['id'] ?>"><?= htmlspecialchars($okul['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label>Sınıfınızı Seçiniz</label>
                        <select class="form-select" name="sinif_id" required>
                            <option value="">-- Sınıf Seçin --</option>
                            <?php foreach ($siniflar as $sinif): ?>
                                <option value="<?= $sinif['id'] ?>"><?= htmlspecialchars($sinif['ad']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label for="formFileMultiple" class="form-label">Fotoğraf    (isteğe bağlı)</label>
                        <input class="form-control" type="file" id="formFileMultiple" name="dosyalar[]" multiple>
                    </div>
                    <button class="btn btn-primary btn-block btn-lg shadow-lg mt-5" type="submit">Kayıt Ol</button>
                </form>

            </div>
        </div>

        <div class="col-lg-7 d-none d-lg-block">
            <div id="auth-right"></div>
        </div>
    </div>
</div>
</body>
</html>
