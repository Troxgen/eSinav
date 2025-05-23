<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]);
require_once __DIR__ . '/../../Settings/db.php';

$successMessage = "";
$errorMessage = "";

function isValidTCKimlik($tc) {
    if (!preg_match('/^[1-9][0-9]{10}$/', $tc)) return false;
    $digits = str_split($tc);
    $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
    $digit10 = (($oddSum * 7) - $evenSum) % 10;
    $digit11 = (array_sum(array_slice($digits, 0, 10))) % 10;
    return ($digit10 == $digits[9]) && ($digit11 == $digits[10]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['Ad'] . ' ' . $_POST['Soyad']);
    $email = trim($_POST['Email']);
    $phone = trim($_POST['CepTelefonu']);
    $tc_no = trim($_POST['TcKimlik']);
    $role_id = (int)$_POST['Rol'];
    $raw_password = $_POST['Sifre'];

    if (!isValidTCKimlik($tc_no)) {
        $errorMessage = "❌ Geçersiz TC Kimlik numarası.";
    } elseif (!preg_match('/^[1-9][0-9]{9}$/', $phone)) {
        $errorMessage = "❌ Telefon numarası 0 ile başlamamalı ve 10 haneli olmalı.";
    } else {
        $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

        $check = $db->prepare("SELECT id FROM users WHERE email = ? OR tc_no = ?");
        $check->execute([$email, $tc_no]);
        if ($check->fetch()) {
            $errorMessage = "❌ Bu e-posta veya TC kimlik numarası zaten kayıtlı.";
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO users (role_id, full_name, email, phone, tc_no, password_hash, apartment_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$role_id, $full_name, $email, $phone, $tc_no, $password_hash, $apartment_id]);
                $successMessage = "✅ Kullanıcı başarıyla eklendi.";
            } catch (PDOException $e) {
                $errorMessage = "❌ Hata: " . $e->getMessage();
            }
        }
    }
}

// Rolleri role_id ve name ile çekiyoruz
try {
    $roller = $db->query("SELECT id, name FROM roles ORDER BY level ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $roller = [];
}
?>

<section class="section">
  <div class="row">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Yeni Kullanıcı Ekle</h5>

        <?php if ($successMessage): ?>
          <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Ad</label>
            <input type="text" name="Ad" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Soyad</label>
            <input type="text" name="Soyad" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">E-Posta</label>
            <input type="email" name="Email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Cep Telefonu</label>
            <input type="text" name="CepTelefonu" class="form-control" maxlength="10" pattern="[1-9]{1}[0-9]{9}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">TC Kimlik No</label>
            <input type="text" name="TcKimlik" class="form-control" maxlength="11" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Şifre</label>
            <input type="password" name="Sifre" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Rol</label>
            <select name="Rol" class="form-select" required>
              <option value="">Seçiniz...</option>
              <?php foreach ($roller as $rol): ?>
                <option value="<?= $rol['id'] ?>"><?= ucfirst($rol['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-success">Kaydet</button>
            <button type="reset" class="btn btn-secondary">Temizle</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
