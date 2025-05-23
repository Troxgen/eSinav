<?php
ob_start();
session_start();

require_once __DIR__ . '/assets/php/Settings/db.php';
require_once __DIR__ . '/assets/php/Pages/Logs/logkaydet.php';

// Eğer zaten giriş yapılmışsa, index'e yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Kullanıcıyı ve rol bilgilerini çekiyoruz
    $stmt = $pdo->prepare("
        SELECT u.*, r.name AS role_name, r.level AS role_level
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {

        // 1) Kullanıcı durumu kontrolü (onaylanmış mı, pasif mi vb.)
        if ($user['status'] == 0) {
            // 0 -> Onay bekleyen
            $error = "❌ Hesabınız henüz onaylanmamış. Lütfen yöneticinizin onayını bekleyin.";
        } elseif ($user['status'] == 2) {
            // 2 -> Reddedilmiş veya pasif (örnek)
            $error = "❌ Hesabınız şu anda pasif durumda. Detaylar için yöneticinize danışın.";
        } else {
            // 1) Status = 1 (Aktif) ise oturum açalım
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['role_level'] = $user['role_level'];

            // ✅ CSRF token üret
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            // Giriş logu
            logLogin($user['id']);

            // Kullanıcının bir daireye ilişkisi var mı?
            $stmt = $pdo->prepare("
                SELECT a.id AS apartment_id, b.id AS building_id
                FROM apartments a
                JOIN buildings b ON a.building_id = b.id
                LEFT JOIN rentals r ON r.apartment_id = a.id
                WHERE a.owner_id = ? OR r.tenant_id = ?
                LIMIT 1
            ");
            $stmt->execute([$user['id'], $user['id']]);
            $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($apartment) {
                $_SESSION['apartment_id'] = $apartment['apartment_id'];
                $_SESSION['building_id'] = $apartment['building_id'];
                header("Location: index.php");
                exit;
            } else {
                // Henüz daireye atanmadıysa ayrı bir sayfaya yönlendirebilirsiniz
                header("Location: select_apartment.php");
                exit;
            }
        }
    } else {
        $error = "❌ E-posta veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Giriş Yap - ApartHub</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<main>
  <div class="container">
    <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

            <div class="d-flex justify-content-center py-4">
              <a href="index.php" class="logo d-flex align-items-center w-auto">
                <img src="assets/img/logo.png" alt="">
                <span class="d-none d-lg-block ms-2">ApartHub</span>
              </a>
            </div>

            <div class="card shadow-lg">
              <div class="card-body">
                <div class="pt-4 pb-2">
                  <h5 class="card-title text-center pb-0 fs-4">Giriş Yap</h5>
                  <p class="text-center small">Lütfen giriş bilgilerinizi girin</p>
                </div>

                <?php if (!empty($error)): ?>
                  <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="row g-3 needs-validation" novalidate>
                  <div class="col-12">
                    <label for="yourUsername" class="form-label">E-Posta</label>
                    <div class="input-group has-validation">
                      <span class="input-group-text">@</span>
                      <input type="email" name="username" class="form-control" id="yourUsername" required>
                      <div class="invalid-feedback">Lütfen geçerli bir e-posta adresi girin.</div>
                    </div>
                  </div>

                  <div class="col-12">
                    <label for="yourPassword" class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" id="yourPassword" required>
                    <div class="invalid-feedback">Lütfen şifrenizi girin.</div>
                  </div>

                  <div class="col-12">
                    <button class="btn btn-primary w-100" type="submit">Giriş Yap</button>
                  </div>

                  <div class="col-12 text-center">
                    <a href="register.php" class="btn btn-link">Hesabın yok mu? Kayıt Ol</a>
                  </div>
                </form>

              </div>
            </div>

            <div class="credits mt-3 text-muted small text-center">
              © <?= date('Y') ?> ApartHub | Tüm hakları saklıdır.
            </div>

          </div>
        </div>
      </div>
    </section>
  </div>
</main>

<a href="#" class="back-to-top d-flex align-items-center justify-content-center">
  <i class="bi bi-arrow-up-short"></i>
</a>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
