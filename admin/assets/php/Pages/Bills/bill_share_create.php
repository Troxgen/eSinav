<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]);

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = $_POST['bill_id'];
    $apartment_id = $_POST['apartment_id'];
    $amount = $_POST['amount'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("INSERT INTO bill_shares (bill_id, apartment_id, share_amount, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$bill_id, $apartment_id, $amount, $status]);
        header("Location: index.php?pages=bill_share_list&created=1");
        exit;
    } catch (PDOException $e) {
        $error = "❌ Hata: " . htmlspecialchars($e->getMessage());
    }
}

// Binaya ait faturaları getir
$bills = $pdo->prepare("SELECT id, type, total_amount, due_date FROM bills WHERE building_id = ?");
$bills->execute([$building_id]);
$bills = $bills->fetchAll();

// Binaya ait daireleri getir
$apartments = $pdo->prepare("SELECT id, block, floor, door_number FROM apartments WHERE building_id = ?");
$apartments->execute([$building_id]);
$apartments = $apartments->fetchAll();
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Fatura Paylaştır</h5>

      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST" class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Fatura</label>
    <select name="bill_id" class="form-select" required>
      <?php foreach ($bills as $b): ?>
        <option value="<?= $b['id'] ?>">
          <?= ucfirst($b['type']) ?> - <?= number_format($b['total_amount'], 2) ?>₺ (<?= $b['due_date'] ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Daire</label>
    <select name="apartment_id" class="form-select" required>
      <?php foreach ($apartments as $a): ?>
        <option value="<?= $a['id'] ?>">
          <?= htmlspecialchars($a['block']) ?> Blok - Kat <?= $a['floor'] ?> - No <?= $a['door_number'] ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Tutar (₺)</label>
    <input type="number" step="0.01" name="amount" class="form-control" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Durum</label>
    <select name="status" class="form-select">
      <option value="beklemede">Beklemede</option>
      <option value="odendi">Ödendi</option>
    </select>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-success">Kaydet</button>
    <a href="index.php?pages=bill_share_list" class="btn btn-secondary">Geri Dön</a>
  </div>
</form>

    </div>
  </div>
</section>
