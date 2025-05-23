<?php
require_once __DIR__ . '/../../Core/auth.php';
require_once __DIR__ . '/../../Settings/db.php';

requireLogin();
requireApartment();

$apartment_id = $_SESSION['apartment_id'];
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) die("❌ Geçersiz ID.");

// aidat tipi fatura ve daireye ait mi?
$query = "
  SELECT bs.*, b.due_date, ap.block, ap.door_number
  FROM bill_shares bs
  JOIN bills b ON bs.bill_id = b.id
  JOIN apartments ap ON bs.apartment_id = ap.id
  WHERE bs.id = ? AND bs.apartment_id = ? AND b.type = 'aidat'
";
$stmt = $pdo->prepare($query);
$stmt->execute([$id, $apartment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("❌ Aidat kaydı bulunamadı veya yetkin yok.");

// Daire bilgisi sabit
$block = $data['block'];
$door_number = $data['door_number'];

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $amount = $_POST['share_amount'];
    $status = $_POST['status'];
    $paid_at = $_POST['paid_at'] ?: null;

    try {
        $stmt = $pdo->prepare("UPDATE bill_shares SET share_amount = ?, status = ?, paid_at = ? WHERE id = ? AND apartment_id = ?");
        $stmt->execute([$amount, $status, $paid_at, $id, $apartment_id]);
        $success = "✅ Güncelleme başarılı.";
        $data['share_amount'] = $amount;
        $data['status'] = $status;
        $data['paid_at'] = $paid_at;
    } catch (PDOException $e) {
        $error = "❌ Hata: " . $e->getMessage();
    }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Aidat Güncelle</h5>
      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Daire</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($block . ' - ' . $door_number . ' No') ?>" disabled>
        </div>

        <div class="col-md-6">
          <label class="form-label">Tutar (₺)</label>
          <input type="number" step="0.01" name="share_amount" class="form-control" value="<?= $data['share_amount'] ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Son Ödeme Tarihi</label>
          <input type="date" class="form-control" value="<?= $data['due_date'] ?>" disabled>
        </div>

        <div class="col-md-6">
          <label class="form-label">Durum</label>
          <select name="status" class="form-select">
            <option value="beklemede" <?= $data['status'] == 'beklemede' ? 'selected' : '' ?>>Beklemede</option>
            <option value="odendi" <?= $data['status'] == 'odendi' ? 'selected' : '' ?>>Ödendi</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Ödeme Tarihi</label>
          <input type="date" name="paid_at" class="form-control" value="<?= $data['paid_at'] ?>">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
          <a href="index.php?pages=aidatlist" class="btn btn-secondary">Geri</a>
        </div>
      </form>
    </div>
  </div>
</section>
