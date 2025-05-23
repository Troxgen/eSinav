<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'];

$isAdminOrManager = in_array($role_id, [4, 5]);

try {
  if ($isAdminOrManager) {
    // Admin / Yönetici: Tüm faturalar
    $stmt = $pdo->prepare("
      SELECT 
        bs.id,
        b.type AS bill_type,
        b.due_date,
        a.block,
        a.floor,
        a.door_number,
        bs.share_amount,
        bs.status,
        bs.paid_at
      FROM bill_shares bs
      JOIN bills b ON bs.bill_id = b.id
      JOIN apartments a ON bs.apartment_id = a.id
      WHERE b.building_id = ?
      ORDER BY b.due_date DESC
    ");
    $stmt->execute([$building_id]);
  } else {
    // Ev sahibi / Kiracı: Sadece kendi daireleri
    $stmt = $pdo->prepare("
      SELECT 
        bs.id,
        b.type AS bill_type,
        b.due_date,
        a.block,
        a.floor,
        a.door_number,
        bs.share_amount,
        bs.status,
        bs.paid_at
      FROM bill_shares bs
      JOIN bills b ON bs.bill_id = b.id
      JOIN apartments a ON bs.apartment_id = a.id
      WHERE b.building_id = ?
        AND (
          a.owner_id = ? OR EXISTS (
            SELECT 1 FROM rentals r WHERE r.apartment_id = a.id AND r.tenant_id = ?
          )
        )
      ORDER BY b.due_date DESC
    ");
    $stmt->execute([$building_id, $user_id, $user_id]);
  }

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo "<div class='alert alert-danger'>Veritabanı hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>


<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Paylaştırılan Faturalar</h5>

      <table class="table table-bordered table-hover" id="billSharesTable">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Fatura Türü</th>
            <th>Son Ödeme</th>
            <th>Blok</th>
            <th>Kat</th>
            <th>Kapı No</th>
            <th>Tutar (₺)</th>
            <th>Durum</th>
            <th>Ödendiği Tarih</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows): ?>
            <?php $i = 1; foreach ($rows as $row): ?>
              <tr class="text-center">
                <td><?= $i++ ?></td>
                <td><?= ucfirst(htmlspecialchars($row['bill_type'])) ?></td>
                <td><?= date('d.m.Y', strtotime($row['due_date'])) ?></td>
                <td><?= htmlspecialchars($row['block']) ?></td>
                <td><?= htmlspecialchars($row['floor']) ?></td>
                <td><?= htmlspecialchars($row['door_number']) ?></td>
                <td>₺<?= number_format($row['share_amount'], 2) ?></td>
                <td>
                  <?php if ($row['status'] === 'odendi'): ?>
                    <span class="badge bg-success">Ödendi</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Beklemede</span>
                  <?php endif; ?>
                </td>
                <td><?= $row['paid_at'] ? date('d.m.Y', strtotime($row['paid_at'])) : '-' ?></td>
                <?php if ($isAdminOrManager): ?>
                  <td>
                    <a href="index.php?pages=billshareupdate&id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary">Düzenle</a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="10" class="text-center text-muted">Henüz paylaştırılmış fatura yok.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>


<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    $('#billSharesTable').DataTable({
      language: { url: '//cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json' }
    });
  });
</script>
