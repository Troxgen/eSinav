<?php



require_once __DIR__ . '/../../Core/auth.php';
require_once __DIR__ . '/../../Settings/db.php';

requireLogin();
requireApartment();

$apartment_id = $_SESSION['apartment_id'];
$role_id = $_SESSION['role_id']; // int geliyor

// admin = 5, yönetici = 4 (örneğin, roller tablosundaki ID'ler)
$isAdminOrManager = in_array($role_id, [4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Aidat Listesi</h5>
      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Blok</th>
            <th>Daire No</th>
            <th>Tutar</th>
            <th>Son Tarih</th>
            <th>Durum</th>
            <th>Ödendiği Tarih</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlemler</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $query = "
            SELECT 
              bs.id, bs.share_amount, bs.status, bs.paid_at,
              bs.apartment_id,
              ap.block, ap.door_number,
              b.due_date
            FROM bill_shares bs
            JOIN bills b ON bs.bill_id = b.id
            JOIN apartments ap ON bs.apartment_id = ap.id
            WHERE bs.apartment_id = ?
              AND b.type = 'aidat'
            ORDER BY bs.paid_at DESC, b.due_date DESC
          ";

          $stmt = $pdo->prepare($query);
          $stmt->execute([$apartment_id]);

          $i = 1;
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo "<tr>
                <td>{$i}</td>
                <td>{$row['block']}</td>
                <td>{$row['door_number']}</td>
                <td>{$row['share_amount']} ₺</td>
                <td>{$row['due_date']}</td>
                <td><span class='badge " . ($row['status'] == 'odendi' ? 'bg-success' : 'bg-warning text-dark') . "'>{$row['status']}</span></td>
                <td>" . ($row['paid_at'] ?? '-') . "</td>";

              if ($isAdminOrManager) {
                  echo "<td class='text-center'>
                    <a href='index.php?pages=aidatedit&id={$row['id']}' class='btn btn-sm btn-warning'>Düzenle</a>
                    <a href='index.php?pages=aidatdelete&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Silmek istediğine emin misin?\")'>Sil</a>
                  </td>";
              }

              echo "</tr>";
              $i++;
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
