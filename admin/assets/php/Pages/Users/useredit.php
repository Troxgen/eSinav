<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]); // admin = 5, yönetici = 4
require_once __DIR__ . '/../../Settings/db.php';

$myLevel = $_SESSION['user_role_level'];
$myApartment = $_SESSION['active_apartment_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?pages=userlist&status=invalid");
    exit;
}

$id = (int)$_GET['id'];

// Kullanıcıyı çek
$stmt = $db->prepare("SELECT u.*, r.level AS role_level FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php?pages=userlist&status=notfound");
    exit;
}

if ((int)$user['role_level'] >= $myLevel) {
    header("Location: index.php?pages=userlist&status=unauthorized");
    exit;
}

if ((int)$user['apartment_id'] !== (int)$myApartment) {
    header("Location: index.php?pages=userlist&status=denied");
    exit;
}

// Formdan veri geldiyse
$success = $error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function isValidTCKimlik($tc) {
        if (!preg_match('/^[1-9][0-9]{10}$/', $tc)) return false;
        $digits = str_split($tc);
        $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        $digit10 = (($oddSum * 7) - $evenSum) % 10;
        $digit11 = (array_sum(array_slice($digits, 0, 10))) % 10;
        return ($digit10 == $digits[9]) && ($digit11 == $digits[10]);
    }

    $full_name = trim($_POST['Ad'] . ' ' . $_POST['Soyad']);
    $email = trim($_POST['Email']);
    $phone = trim($_POST['CepTelefonu']);
    $tc_no = trim($_POST['TcKimlik']);
    $role_id = (int)$_POST['Rol'];

    if (!isValidTCKimlik($tc_no)) {
        $error = "❌ Geçersiz TC Kimlik numarası.";
    } elseif (!preg_match('/^[1-9][0-9]{9}$/', $phone)) {
        $error = "❌ Telefon numarası geçerli değil.";
    } else {
        try {
            $update = $db->prepare("UPDATE users SET full_name=?, email=?, phone=?, tc_no=?, role_id=? WHERE id=?");
            $update->execute([$full_name, $email, $phone, $tc_no, $role_id, $id]);
            $success = "✅ Kullanıcı bilgileri güncellendi.";

            // Güncel veriyi tekrar çek
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "❌ Güncelleme hatası: " . $e->getMessage();
        }
    }
}

// Roller (sadece kendi seviyenden düşük olanlar)
$roller = $db->prepare("SELECT id, name, level FROM roles WHERE level < ? ORDER BY level ASC");
$roller->execute([$myLevel]);
$roller = $roller->fetchAll();
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Kullanıcıyı Düzenle</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <?php $adSoyad = explode(' ', $user['full_name'], 2); ?>
      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Ad</label>
          <input type="text" name="Ad" class="form-control" value="<?= htmlspecialchars($adSoyad[0]) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Soyad</label>
          <input type="text" name="Soyad" class="form-control" value="<?= htmlspecialchars($adSoyad[1] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">E-Posta</label>
          <input type="email" name="Email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefon</label>
          <input type="text" name="CepTelefonu" class="form-control" maxlength="10" pattern="[1-9][0-9]{9}" value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">TC Kimlik</label>
          <input type="text" name="TcKimlik" class="form-control" maxlength="11" value="<?= htmlspecialchars($user['tc_no']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select name="Rol" class="form-select" required>
            <option value="">Seçiniz...</option>
            <?php foreach ($roller as $rol): ?>
              <option value="<?= $rol['id'] ?>" <?= $rol['id'] == $user['role_id'] ? 'selected' : '' ?>>
                <?= ucfirst($rol['name']) ?> (<?= $rol['level'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
          <a href="index.php?pages=userlist" class="btn btn-secondary">Geri Dön</a>
        </div>
      </form>
    </div>
  </div>
</section>
