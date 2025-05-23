<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>❌ Geçersiz ID!</div>";
    exit;
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$building_id = $_SESSION['building_id'];

try {
    // Kullanıcı admin/yonetici ise binaya bağlı her şeyi görebilir, değilse sadece kendi dairesine ait olanı
    $stmt = $pdo->prepare("
        SELECT 
          bs.*, 
          b.type AS bill_type, 
          b.total_amount, 
          b.due_date, 
          b.status AS bill_status, 
          b.paid_at AS bill_paid_at,
          a.block, a.floor, a.door_number
        FROM bill_shares bs
        JOIN bills b ON bs.bill_id = b.id
        JOIN apartments a ON bs.apartment_id = a.id
        WHERE bs.id = :id AND b.building_id = :building_id
    ");
    $stmt->execute([
        'id' => $id,
        'building_id' => $building_id
    ]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$share) {
        echo "<div class='alert alert-warning'>❌ Kayıt bulunamadı veya erişim yetkiniz yok.</div>";
        exit;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ Hata: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Fatura Paylaşım Detayı</h5>

      <div class="row mb-3">
        <div class="col-md-6">
          <strong>Blok:</strong> <?= htmlspecialchars($share['block']) ?>
        </div>
        <div class="col-md-6">
          <strong>Kat:</strong> <?= htmlspecialchars($share['floor']) ?> | <strong>Kapı:</strong> <?= htmlspecialchars($share['door_number']) ?>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <strong>Fatura Türü:</strong> <?= ucfirst(htmlspecialchars($share['bill_type'])) ?>
        </div>
        <div class="col-md-6">
          <strong>Toplam Fatura Tutarı:</strong> ₺<?= number_format($share['total_amount'], 2) ?>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <strong>Paylaşılan Tutar:</strong> ₺<?= number_format($share['share_amount'], 2) ?>
        </div>
        <div class="col-md-6">
          <strong>Durum:</strong> 
          <?php if ($share['status'] === 'odendi'): ?>
            <span class="badge bg-success">Ödendi</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark">Beklemede</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <strong>Son Ödeme Tarihi:</strong> <?= date("d.m.Y", strtotime($share['due_date'])) ?>
        </div>
        <div class="col-md-6">
          <strong>Ödendiği Tarih:</strong> <?= $share['paid_at'] ? date("d.m.Y", strtotime($share['paid_at'])) : "-" ?>
        </div>
      </div>

      <a href="index.php?pages=bill_share_list" class="btn btn-secondary">Geri Dön</a>
    </div>
  </div>
</section>
