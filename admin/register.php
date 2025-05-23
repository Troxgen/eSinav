<?php
session_start();
require_once __DIR__ . '/assets/php/Settings/db.php';
require_once __DIR__ . '/assets/php/Pages/Logs/logkaydet.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = $success = "";

// Apartman listesi (var olan apartmanları çekiyoruz)
$apartmentList = $pdo->query("
    SELECT a.id, b.name AS building_name, a.block, a.floor, a.door_number
    FROM apartments a
    JOIN buildings b ON a.building_id = b.id
    ORDER BY b.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $tc_no = trim($_POST['tc_no']);
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $selected_apartment_id = $_POST['selected_apartment_id'] ?? '';

    // Form kontrolleri
    if (!$full_name || !$email || !$phone || !$tc_no || !$password) {
        $error = "Lütfen tüm alanları doldurunuz.";
    } elseif (!$selected_apartment_id) {
        $error = "Lütfen bir apartman seçiniz.";
    }

    if (!$error) {
        try {
            // 1) Yeni kullanıcı ekle (status=0 ile onay bekleyecek)
            //    role_id=3 → kiracı olsun (örnek)
            $stmt = $pdo->prepare("
                INSERT INTO users (role_id, full_name, email, phone, tc_no, password_hash, status)
                VALUES (3, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$full_name, $email, $phone, $tc_no, $password_hash]);
            $user_id = $pdo->lastInsertId();

            // 2) rentals tablosuna ekle (kiraya başvuruyor varsayalım)
            $pdo->prepare("
                INSERT INTO rentals (apartment_id, tenant_id, rent_amount, start_date)
                VALUES (?, ?, 0, NOW())
            ")->execute([$selected_apartment_id, $user_id]);

            // 3) Apartman yöneticisini bul
            //    apartments tablosunda `owner_id` varsa ve bu owner_id bir yöneticiyse vs.
            $stmt = $pdo->prepare("
                SELECT u.id
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN apartments a ON a.id = ?
                WHERE a.owner_id = u.id AND r.name = 'yonetici'
                LIMIT 1
            ");
            $stmt->execute([$selected_apartment_id]);
            $yonetici = $stmt->fetch();

            // 4) Mesaj/Bildirim tablosuna ekle
            if ($yonetici) {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (sender_id, receiver_id, message, status)
                    VALUES (?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $user_id,
                    $yonetici['id'],
                    'Seçtiğim apartmana kayıt başvurusu yapıyorum.'
                ]);
            }

            $success = "✅ Kayıt başvurunuz alındı! Yöneticinin onayı sonrası giriş yapabilirsiniz.";
        } catch (PDOException $e) {
            $error = "❌ Kayıt sırasında hata oluştu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Kayıt Ol - ApartHub</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<main>
  <div class="container">
    <section class="section register d-flex flex-column align-items-center justify-content-center py-4">
      <div class="col-lg-6 col-md-8">
        <div class="card shadow">
          <div class="card-body">
            <h5 class="card-title text-center">Yeni Kayıt</h5>

            <?php if ($error): ?>
              <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3">
              <div class="col-md-6">
                <input type="text" name="full_name" class="form-control" placeholder="Ad Soyad" required>
              </div>
              <div class="col-md-6">
                <input type="email" name="email" class="form-control" placeholder="E-Posta" required>
              </div>
              <div class="col-md-6">
                <input type="text" name="phone" class="form-control" placeholder="Telefon" required>
              </div>
              <div class="col-md-6">
                <input type="text" name="tc_no" class="form-control" placeholder="TC Kimlik No" required>
              </div>
              <div class="col-md-6">
                <input type="password" name="password" class="form-control" placeholder="Şifre" required>
              </div>

              <div class="col-12">
                <label class="form-label fw-bold">Apartman Ara</label>
                <select name="selected_apartment_id" class="form-select" required>
                  <option value="">Apartman Seçiniz</option>
                  <?php foreach ($apartmentList as $a): ?>
                    <option value="<?= $a['id'] ?>">
                      <?= $a['building_name'] ?> / Blok <?= $a['block'] ?> / Kat <?= $a['floor'] ?> / No <?= $a['door_number'] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 text-center mt-3">
                <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
              </div>
              <div class="col-12 text-center">
                <a href="login.php" class="btn btn-link">Zaten hesabın var mı? Giriş Yap</a>
              </div>
            </form>

          </div>
        </div>
      </div>
    </section>
  </div>
</main>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
