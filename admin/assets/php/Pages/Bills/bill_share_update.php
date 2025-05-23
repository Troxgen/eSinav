<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]);

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = "";
$error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("❌ Geçersiz ID.");
}

$id = (int)$_GET['id'];

// Pay kontrolü (sadece bu binaya aitse işlem yapılır)
$stmt = $pdo->prepare("
    SELECT bs.*, b.building_id
    FROM bill_shares bs
    JOIN bills b ON bs.bill_id = b.id
    WHERE bs.id = ? AND b.building_id = ?
");
$stmt->execute([$id, $building_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("❌ Bu pay kaydı bulunamadı veya yetkiniz yok.");
}

// Güncellenebilir faturaları al
$bills = $pdo->prepare("SELECT id, type, total_amount, due_date FROM bills WHERE building_id = ?");
$bills->execute([$building_id]);
$bills = $bills->fetchAll(PDO::FETCH_ASSOC);

// Apartman bilgisi için
$apartments = $pdo->prepare("SELECT a.id, a.block, a.door_number, u.full_name 
                             FROM apartments a 
                             LEFT JOIN users u ON a.owner_id = u.id 
                             WHERE a.building_id = ?");
$apartments->execute([$building_id]);
$users = $apartments->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $bill_id = $_POST['bill_id'];
    $apartment_id = $_POST['apartment_id'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];

    try {
        $update = $pdo->prepare("UPDATE bill_shares SET bill_id = ?, apartment_id = ?, share_amount = ?, status = ? WHERE id = ? ");
        $update->execute([$bill_id, $apartment_id, $amount, $status, $id]);
        $success = "✅ Paylaştırma başarıyla güncellendi.";

        // Veriyi tekrar çek
        $stmt = $pdo->prepare("
            SELECT bs.*, b.building_id
            FROM bill_shares bs
            JOIN bills b ON bs.bill_id = b.id
            WHERE bs.id = ? AND b.building_id = ?
        ");
        $stmt->execute([$id, $building_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "❌ Hata: " . htmlspecialchars($e->getMessage());
    }
}
?>


<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Paylaştırmayı Güncelle</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST" class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Fatura</label>
    <select name="bill_id" class="form-select" required>
      <?php foreach ($bills as $b): ?>
        <option value="<?= $b['id'] ?>" <?= $b['id'] == $data['bill_id'] ? 'selected' : '' ?>>
          <?= ucfirst($b['type']) ?> - <?= number_format($b['total_amount'], 2) ?>₺ (<?= $b['due_date'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Daire</label>
    <select name="apartment_id" class="form-select" required>
      <?php foreach ($users as $u): ?>
        <option value="<?= $u['id'] ?>" <?= $u['id'] == $data['apartment_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['block']) ?> Blok - No <?= $u['door_number'] ?> <?= $u['full_name'] ? '(' . $u['full_name'] . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Tutar (₺)</label>
    <input type="number" step="0.01" name="amount" class="form-control" value="<?= $data['share_amount'] ?>" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Durum</label>
    <select name="status" class="form-select">
      <option value="beklemede" <?= $data['status'] === 'beklemede' ? 'selected' : '' ?>>Beklemede</option>
      <option value="odendi" <?= $data['status'] === 'odendi' ? 'selected' : '' ?>>Ödendi</option>
    </select>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="index.php?pages=billsharelist" class="btn btn-secondary">Geri</a>
  </div>
</form>

    </div>
  </div>
</section>
