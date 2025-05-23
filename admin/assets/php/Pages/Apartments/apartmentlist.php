<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireBuilding();

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$isAdminOrManager = in_array($role_id, [3, 4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Daireler</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Blok</th>
            <th>Kat</th>
            <th>Kapı No</th>
            <th>Ev Sahibi</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->prepare("
          SELECT a.*, u.full_name 
          FROM apartments a
          LEFT JOIN users u ON a.owner_id = u.id
          WHERE a.building_id = ?
          ORDER BY a.block, a.door_number
        ");
        $stmt->execute([$building_id]);

        $i = 1;
        foreach ($stmt as $row) {
          echo "<tr>
            <td>{$i}</td>
            <td>" . htmlspecialchars($row['block']) . "</td>
            <td>" . htmlspecialchars($row['floor']) . "</td>
            <td>" . htmlspecialchars($row['door_number']) . "</td>
            <td>" . htmlspecialchars($row['full_name'] ?? '—') . "</td>";

          if ($isAdminOrManager) {
            echo "<td class='text-center'>
              <a href='index.php?pages=apartmentedit&id={$row['id']}' class='btn btn-sm btn-warning'>Düzenle</a>
              <a href='index.php?pages=apartmentdelete&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Bu daireyi silmek istediğine emin misiniz?\");'>Sil</a>
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
