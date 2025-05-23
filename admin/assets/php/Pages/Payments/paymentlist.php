<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'];
$isAdminOrManager = in_array($role_id, [4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Ödeme Geçmişi</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Kullanıcı</th>
            <th>Tür</th>
            <th>Tutar</th>
            <th>Tarih</th>
            <th>Referans ID</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php
        if ($isAdminOrManager) {
          // Yöneticiler: tüm binanın ödemelerini görebilir
          $stmt = $pdo->prepare("
            SELECT p.*, u.full_name 
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE EXISTS (
              SELECT 1 FROM apartments a 
              WHERE a.building_id = ? AND (
                a.owner_id = u.id OR EXISTS (
                  SELECT 1 FROM rentals r WHERE r.apartment_id = a.id AND r.tenant_id = u.id
                )
              )
            )
            ORDER BY p.paid_at DESC
          ");
          $stmt->execute([$building_id]);
        } else {
          // Diğer roller: sadece kendi ödemelerini görebilir
          $stmt = $pdo->prepare("
            SELECT p.*, u.full_name 
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.paid_at DESC
          ");
          $stmt->execute([$user_id]);
        }

        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 1;

        foreach ($payments as $row): ?>
          <tr class="text-center">
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><span class='badge bg-primary text-uppercase'><?= htmlspecialchars($row['type']) ?></span></td>
            <td>₺<?= number_format($row['amount'], 2) ?></td>
            <td><?= date('d.m.Y H:i', strtotime($row['paid_at'])) ?></td>
            <td><?= $row['reference_id'] ?? '-' ?></td>
            <?php if ($isAdminOrManager): ?>
              <td>
                <a href="index.php?pages=paymentdelete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
