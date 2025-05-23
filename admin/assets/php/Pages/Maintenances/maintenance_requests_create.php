<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'];
$success = $error = "";

// Talep gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apartment_input = $_POST['apartment_id'] ?? null;
    $user_input = in_array($role_id, [4, 5 ,3 , 2]) ? ($_POST['user_id'] ?? null) : $user_id;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = 'beklemede';

    if (!$apartment_input || !$user_input || !$title) {
        $error = "Tüm alanları doldurmanız gerekmektedir.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO maintenance_requests (apartment_id, user_id, title, description, status, building_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$apartment_input, $user_input, $title, $description, $status, $building_id]);
            $success = "✅ Talep başarıyla eklendi.";
        } catch (PDOException $e) {
            $error = "❌ Hata: " . $e->getMessage();
        }
    }
}

// Bu binaya ait daireler
$apartments = $pdo->prepare("
  SELECT id, block, floor, door_number 
  FROM apartments 
  WHERE building_id = ?
  ORDER BY block, floor, door_number
");
$apartments->execute([$building_id]);
$apartments = $apartments->fetchAll(PDO::FETCH_ASSOC);

// Bu binaya ait kullanıcılar (daire sahipleri veya kiracılar)
$users = $pdo->prepare("
  SELECT DISTINCT u.id, u.full_name
  FROM users u
  JOIN apartments a ON u.id = a.owner_id AND a.building_id = ?
  UNION
  SELECT DISTINCT u.id, u.full_name
  FROM users u
  JOIN rentals r ON u.id = r.tenant_id
  JOIN apartments a ON r.apartment_id = a.id
  WHERE a.building_id = ?
  ORDER BY full_name
");
$users->execute([$building_id, $building_id]);
$users = $users->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="pagetitle">
  <h1>Yeni Bakım & Onarım Talebi</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Anasayfa</a></li>
      <li class="breadcrumb-item active">Talep Ekle</li>
    </ol>
  </nav>
</div>

<div class="card">
  <div class="card-body pt-4">
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3">
      <div class="col-md-6">
        <label for="apartment_id" class="form-label">Daire</label>
        <select name="apartment_id" id="apartment_id" class="form-select" required>
          <option value="">Seçiniz...</option>
          <?php foreach ($apartments as $a): ?>
            <option value="<?= $a['id'] ?>">
              <?= "Blok {$a['block']} - Kat {$a['floor']} - Kapı {$a['door_number']}" ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (in_array($role_id, [4, 5])): ?>
        <div class="col-md-6">
          <label for="user_id" class="form-label">Kullanıcı</label>
          <select name="user_id" id="user_id" class="form-select" required>
            <option value="">Seçiniz...</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php else: ?>
        <input type="hidden" name="user_id" value="<?= $user_id ?>">
      <?php endif; ?>

      <div class="col-12">
        <label for="title" class="form-label">Talep Başlığı</label>
        <input type="text" name="title" id="title" class="form-control" required>
      </div>

      <div class="col-12">
        <label for="description" class="form-label">Açıklama</label>
        <textarea name="description" id="description" class="form-control" rows="4"></textarea>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-success">Kaydet</button>
        <a href="index.php?pages=maintenancelist" class="btn btn-secondary">Geri Dön</a>
      </div>
    </form>
  </div>
</div>
