<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]);

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = "";
$error = "";

// Sadece bu binaya ait bekleyen faturalar
$bills = $pdo->prepare("SELECT id, type, total_amount, due_date FROM bills WHERE status = 'beklemede' AND building_id = ? ORDER BY due_date DESC");
$bills->execute([$building_id]);
$bills = $bills->fetchAll(PDO::FETCH_ASSOC);

// Bu binaya ait daireler
$apartments = $pdo->prepare("SELECT id, block, floor, door_number FROM apartments WHERE building_id = ?");
$apartments->execute([$building_id]);
$apartments = $apartments->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = $_POST['bill_id'];
    $share_type = $_POST['share_type'];

    try {
        // Seçilen faturanın tutarını kontrol et
        $stmt = $pdo->prepare("SELECT total_amount FROM bills WHERE id = ? AND building_id = ?");
        $stmt->execute([$bill_id, $building_id]);
        $bill = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bill) throw new Exception("Fatura bulunamadı veya bu binaya ait değil.");

        $total_amount = $bill['total_amount'];
        $pdo->beginTransaction();

        if ($share_type === 'equal') {
            $daire_sayisi = count($apartments);
            if ($daire_sayisi == 0) throw new Exception("Bu binaya ait hiç daire yok.");
            $share_amount = round($total_amount / $daire_sayisi, 2);

            foreach ($apartments as $apartment) {
                $stmt = $pdo->prepare("INSERT INTO bill_shares (bill_id, apartment_id, share_amount, status) VALUES (?, ?, ?, 'beklemede')");
                $stmt->execute([$bill_id, $apartment['id'], $share_amount]);
            }
        } elseif ($share_type === 'custom') {
            foreach ($_POST['custom_shares'] as $apartment_id_custom => $amount) {
                if (is_numeric($amount) && $amount >= 0) {
                    $stmt = $pdo->prepare("INSERT INTO bill_shares (bill_id, apartment_id, share_amount, status) VALUES (?, ?, ?, 'beklemede')");
                    $stmt->execute([$bill_id, $apartment_id_custom, $amount]);
                }
            }
        }

        $pdo->commit();
        $success = "✅ Fatura başarıyla paylaştırıldı.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ Hata: " . $e->getMessage();
    }
}
?>


<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Fatura Paylaştır</h5>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="bill_id" class="form-label">Fatura Seç</label>
            <select name="bill_id" id="bill_id" class="form-select" required>
              <option value="">Seçiniz...</option>
              <?php foreach ($bills as $bill): ?>
                <option value="<?= $bill['id'] ?>">
                  <?= ucfirst($bill['type']) ?> - <?= number_format($bill['total_amount'], 2) ?>₺ (<?= $bill['due_date'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label for="share_type" class="form-label">Paylaşım Tipi</label>
            <select name="share_type" id="share_type" class="form-select" required onchange="toggleCustomShares(this.value)">
              <option value="equal">Eşit Paylaştır</option>
              <option value="custom">Özel Paylaştır</option>
            </select>
          </div>
        </div>

        <div id="customShareBox" style="display: none;">
          <h6>🔧 Özel Paylar:</h6>
          <?php foreach ($apartments as $apartment): ?>
            <div class="mb-2">
              <label class="form-label"><?= htmlspecialchars($apartment['block']) ?> Blok - Kat <?= $apartment['floor'] ?> - No <?= $apartment['door_number'] ?></label>
              <input type="number" name="custom_shares[<?= $apartment['id'] ?>]" class="form-control" placeholder="Tutar ₺" min="0" step="0.01">
            </div>
          <?php endforeach; ?>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-primary">Paylaştır</button>
        </div>
      </form>
    </div>
  </div>
</section>

<script>
function toggleCustomShares(value) {
  document.getElementById("customShareBox").style.display = value === 'custom' ? 'block' : 'none';
}
</script>
