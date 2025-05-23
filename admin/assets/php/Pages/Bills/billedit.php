<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]);

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$success = $error = "";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) die("❌ Geçersiz ID.");

$id = (int) $_GET['id'];

// Faturayı sadece bağlı olunan binaya aitse getir
$stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ? AND building_id = ?");
$stmt->execute([$id, $building_id]);
$data = $stmt->fetch();

if (!$data) die("❌ Fatura bulunamadı veya bu binaya ait değil.");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $type = $_POST['type'];
  $total = $_POST['total_amount'];
  $due = $_POST['due_date'];
  $status = $_POST['status'];
  $paid_at = $_POST['paid_at'] ?: null;

  try {
    $update = $pdo->prepare("UPDATE bills SET type=?, total_amount=?, due_date=?, status=?, paid_at=? WHERE id=? AND building_id=?");
    $update->execute([$type, $total, $due, $status, $paid_at, $id, $building_id]);
    $success = "✅ Güncelleme başarılı.";

    // Güncellenmiş veriyi tekrar al
    $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ? AND building_id = ?");
    $stmt->execute([$id, $building_id]);
    $data = $stmt->fetch();
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}
?>


<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Fatura Güncelle</h5>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Fatura Türü</label>
          <select name="type" class="form-select">
            <?php
              $types = ['elektrik','su','dogalgaz','internet','diger'];
              foreach ($types as $t) {
                $sel = $t == $data['type'] ? 'selected' : '';
                echo "<option value='$t' $sel>" . ucfirst($t) . "</option>";
              }
            ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Tutar</label>
          <input type="number" step="0.01" name="total_amount" class="form-control" value="<?= $data['total_amount'] ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Son Tarih</label>
          <input type="date" name="due_date" class="form-control" value="<?= $data['due_date'] ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Durum</label>
          <select name="status" class="form-select">
            <option value="beklemede" <?= $data['status'] === 'beklemede' ? 'selected' : '' ?>>Beklemede</option>
            <option value="odendi" <?= $data['status'] === 'odendi' ? 'selected' : '' ?>>Ödendi</option>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label">Ödendiği Tarih</label>
          <input type="date" name="paid_at" class="form-control" value="<?= $data['paid_at'] ?>">
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Güncelle</button>
        </div>
      </form>
    </div>
  </div>
</section>
