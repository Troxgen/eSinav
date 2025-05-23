<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

// Oturumdan gerekli bilgileri alalım
$userId      = $_SESSION['user_id'] ?? null;
$building_id = $_SESSION['building_id'] ?? null;

$success = "";
$error   = "";

// Kişisel bilgiler güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $tc_no     = trim($_POST['tc_no'] ?? '');

    if (empty($full_name) || empty($email) || empty($phone) || empty($tc_no)) {
        $error = "Tüm alanları doldurmanız gerekmektedir.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, tc_no = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $tc_no, $userId]);
            $success = "Bilgiler başarıyla güncellendi.";
        } catch (PDOException $e) {
            $error = "Hata: " . $e->getMessage();
        }
    }
}

// Kullanıcının güncel bilgilerini çekelim
$userQuery = $db->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->execute([$userId]);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

// Kullanıcının apartman ve daire bilgilerini çekelim (rentals tablosu üzerinden)
$apartmentData = null;
$apartmentQuery = $db->prepare("SELECT a.*, b.name AS building_name, b.address 
                                 FROM apartments a 
                                 JOIN buildings b ON a.building_id = b.id 
                                 JOIN rentals r ON r.apartment_id = a.id 
                                 WHERE r.tenant_id = ?");
$apartmentQuery->execute([$userId]);
$apartmentData = $apartmentQuery->fetch(PDO::FETCH_ASSOC);

// Kullanıcının son 3 ödenmiş faturasını çekelim (bina bazında)
$bills = [];
$billsQuery = $db->prepare("
    SELECT id, total_amount, paid_at 
    FROM bills 
    WHERE building_id = ? 
      AND status = 'ödendi'
    ORDER BY paid_at DESC
    LIMIT 3
");
$billsQuery->execute([$building_id]);
$bills = $billsQuery->fetchAll(PDO::FETCH_ASSOC);

// Son giriş tarihi: Eğer user tablosunda yoksa, login_log tablosundan çekilebilir.
// Bu örnekte $user['last_login'] varsa onu kullanıyoruz.
$lastLogin = isset($user['last_login']) ? $user['last_login'] : null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Ayarlar - Profil Bilgilerim</title>
  <style>
    body {
      background-color: #f8f9fa;
    }
    .profile-card {
      margin-bottom: 20px;
    }
    .profile-header {
      background-color: #007bff;
      color: #fff;
      padding: 15px;
      border-top-left-radius: 0.25rem;
      border-top-right-radius: 0.25rem;
    }
    .profile-header h5 {
      margin: 0;
    }
    .profile-body {
      background: #fff;
      padding: 20px;
    }
    .card {
      border: none;
      box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <div class="container my-5">
    <h1 class="mb-4 text-center">Profil Ayarları</h1>
    
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <div class="row">
      <!-- Kişisel Bilgiler Güncelleme Formu -->
      <div class="col-md-6">
        <div class="card profile-card">
          <div class="card-header profile-header">
            <h5>Kişisel Bilgilerim</h5>
          </div>
          <div class="card-body profile-body">
            <form method="POST" class="row g-3">
              <div class="col-12">
                <label for="full_name" class="form-label">Ad Soyad</label>
                <input type="text" id="full_name" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label for="email" class="form-label">E-Posta</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label for="phone" class="form-label">Telefon</label>
                <input type="text" id="phone" name="phone" class="form-control"
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label for="tc_no" class="form-label">TC Kimlik No</label>
                <input type="text" id="tc_no" name="tc_no" class="form-control"
                       value="<?= htmlspecialchars($user['tc_no'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">Bilgilerimi Güncelle</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Profil Özeti / Künye -->
      <div class="col-md-6">
        <div class="card profile-card">
          <div class="card-header profile-header">
            <h5>Profil Detaylarım</h5>
          </div>
          <div class="card-body profile-body">
            <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($user['full_name'] ?? 'Bilgi yok') ?></p>
            <p><strong>E-Posta:</strong> <?= htmlspecialchars($user['email'] ?? 'Bilgi yok') ?></p>
            <p><strong>Telefon:</strong> <?= htmlspecialchars($user['phone'] ?? 'Bilgi yok') ?></p>
            <p><strong>TC Kimlik No:</strong> <?= htmlspecialchars($user['tc_no'] ?? 'Bilgi yok') ?></p>
            <p><strong>Son Giriş:</strong>
              <?php if ($lastLogin): ?>
                <?= date('d.m.Y H:i', strtotime($lastLogin)) ?>
              <?php else: ?>
                <em>Bilgi yok</em>
              <?php endif; ?>
            </p>
            
            <?php if ($apartmentData): ?>
              <hr>
              <h6>Apartman / Daire Bilgileri</h6>
              <p><strong>Binanın Adı:</strong> <?= htmlspecialchars($apartmentData['building_name'] ?? 'Bilgi yok') ?></p>
              <p><strong>Adres:</strong> <?= htmlspecialchars($apartmentData['address'] ?? 'Bilgi yok') ?></p>
              <p><strong>Blok:</strong> <?= htmlspecialchars($apartmentData['block'] ?? 'Bilgi yok') ?></p>
              <p><strong>Kat:</strong> <?= htmlspecialchars($apartmentData['floor'] ?? 'Bilgi yok') ?></p>
              <p><strong>Daire No:</strong> <?= htmlspecialchars($apartmentData['door_number'] ?? 'Bilgi yok') ?></p>
            <?php else: ?>
              <p><em>Apartman ve daire bilgileriniz henüz atanmadı.</em></p>
            <?php endif; ?>
            
            <hr>
            <h6>Son Ödediğim Faturalar</h6>
            <?php if (isset($bills) && count($bills) > 0): ?>
              <ul class="list-group">
                <?php foreach ($bills as $bill): ?>
                  <li class="list-group-item">
                    <strong>Fatura #<?= $bill['id'] ?></strong> - Tutar: <?= htmlspecialchars($bill['total_amount']) ?> TL<br>
                    <small>Ödeme Tarihi: <?= date('d.m.Y', strtotime($bill['paid_at'])) ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p><em>Ödenmiş fatura bilgisi bulunmamaktadır.</em></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div><!-- .row -->
  </div><!-- .container -->
</body>
</html>
<?php ob_end_flush(); ?>
