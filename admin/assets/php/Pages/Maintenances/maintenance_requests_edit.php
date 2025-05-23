<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?pages=maintenancelist&status=invalid");
    exit;
}

$id = (int) $_GET['id'];
$building_id = $_SESSION['building_id'];
$success = "";
$error = "";

// Talep bilgisi çekiliyor
try {
    $stmt = $pdo->prepare("
        SELECT mr.*, u.full_name, a.block, a.floor, a.door_number
        FROM maintenance_requests mr
        JOIN users u ON mr.user_id = u.id
        JOIN apartments a ON mr.apartment_id = a.id
        WHERE mr.id = ? AND mr.building_id = ?
    ");
    $stmt->execute([$id, $building_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        header("Location: index.php?pages=maintenancelist&status=notfound");
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php?pages=maintenancelist&status=dberror");
    exit;
}

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];

    if (!in_array($status, ['beklemede', 'yapildi', 'iptal'])) {
        $error = "❌ Geçersiz durum seçimi.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ? AND building_id = ?");
            $stmt->execute([$status, $id, $building_id]);
            $success = "✅ Durum başarıyla güncellendi.";
            $request['status'] = $status;
        } catch (PDOException $e) {
            $error = "❌ Hata: " . $e->getMessage();
        }
    }
}
?>

<section class="section">
  <div class="row">
    <div class="card">
      <div class="card-body pt-4">
        <h5 class="card-title">Bakım Talebi Durum Güncelle</h5>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
          <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Talep Eden</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($request['full_name']) ?>" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Daire</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($request['block']) ?> Blok / <?= htmlspecialchars($request['floor']) ?>. Kat / Kapı No <?= htmlspecialchars($request['door_number']) ?>" readonly>
          </div>

          <div class="col-md-12">
            <label class="form-label">Açıklama</label>
            <textarea class="form-control" rows="4" readonly><?= htmlspecialchars($request['description']) ?></textarea>
          </div>

          <div class="col-md-6">
            <label for="status" class="form-label">Durum</label>
            <select name="status" id="status" class="form-select" required>
              <option value="beklemede" <?= $request['status'] === 'beklemede' ? 'selected' : '' ?>>Beklemede</option>
              <option value="yapildi" <?= $request['status'] === 'yapildi' ? 'selected' : '' ?>>Yapıldı</option>
              <option value="iptal" <?= $request['status'] === 'iptal' ? 'selected' : '' ?>>İptal</option>
            </select>
          </div>

          <div class="col-12">
            <button type="submit" class="btn btn-success">Güncelle</button>
            <a href="index.php?pages=maintenancelist" class="btn btn-secondary">Geri Dön</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
