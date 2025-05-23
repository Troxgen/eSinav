<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireBuilding();

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$isAdminOrManager = in_array($role_id, [4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Faturalar</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Tür</th>
            <th>Toplam Tutar</th>
            <th>Son Ödeme</th>
            <th>Durum</th>
            <th>Ödendiği Tarih</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM bills WHERE building_id = ? ORDER BY due_date DESC");
        $stmt->execute([$building_id]);
        $i = 1;
        foreach ($stmt as $row) {
          echo "<tr class='text-center'>
            <td>{$i}</td>
            <td>" . ucfirst(htmlspecialchars($row['type'])) . "</td>
            <td>₺" . number_format($row['total_amount'], 2) . "</td>
            <td>{$row['due_date']}</td>
            <td><span class='badge " . ($row['status'] === 'odendi' ? 'bg-success' : 'bg-warning text-dark') . "'>" . ucfirst($row['status']) . "</span></td>
            <td>" . ($row['paid_at'] ?? '-') . "</td>";

          if ($isAdminOrManager) {
            echo "<td>
              <a href='index.php?pages=billdelete&id={$row['id']}&csrf_token={$_SESSION['csrf_token']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Silinsin mi?\");'>Sil</a>
              <a href='index.php?pages=billedit&id={$row['id']}' class='btn btn-sm btn-warning'>Düzenle</a>
              <a href='index.php?pages=bill_share&bill_id={$row['id']}' class='btn btn-sm btn-secondary'>Paylaştır</a>
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
