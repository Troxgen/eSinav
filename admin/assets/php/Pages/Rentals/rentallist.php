<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$apartment_id = $_SESSION['active_apartment_id'];
$role_id = $_SESSION['role_id'];
$isAdminOrManager = in_array($role_id, [4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Kiralama Listesi</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Daire</th>
            <th>Kiracı</th>
            <th>Kira Tutarı (₺)</th>
            <th>Başlangıç</th>
            <th>Bitiş</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $db->prepare("
            SELECT r.*, a.block, a.door_number, u.full_name 
            FROM rentals r
            LEFT JOIN apartments a ON r.apartment_id = a.id
            LEFT JOIN users u ON r.tenant_id = u.id
            WHERE a.apartment_id = ?
            ORDER BY r.start_date DESC
          ");
          $stmt->execute([$apartment_id]);
          $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          $i = 1;

          foreach ($rows as $row):
          ?>
            <tr class="text-center">
              <td><?= $i++ ?></td>
              <td><?= "Blok " . htmlspecialchars($row['block']) . " / No " . htmlspecialchars($row['door_number']) ?></td>
              <td><?= htmlspecialchars($row['full_name'] ?? '—') ?></td>
              <td>₺<?= number_format($row['rent_amount'], 2) ?></td>
              <td><?= date("d.m.Y", strtotime($row['start_date'])) ?></td>
              <td><?= $row['end_date'] ? date("d.m.Y", strtotime($row['end_date'])) : '—' ?></td>
              <?php if ($isAdminOrManager): ?>
                <td>
                  <a href="index.php?pages=rentaledit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                  <a href="index.php?pages=rentaldelete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
            <tr><td colspan="<?= $isAdminOrManager ? 7 : 6 ?>" class="text-center text-muted">Kiralama kaydı bulunamadı.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
