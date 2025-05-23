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
      <h5 class="card-title">Daireler</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Apartman Adı</th>
            <th>Adresi</th>
            <th>Daire Adeti</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM `buildings`");
        $stmt->execute();

        $i = 1;
        foreach ($stmt as $row) {
          // İsteğe bağlı olarak gerçek daire sayısı hesaplanabilir:
          $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM apartments WHERE building_id = ?');
          $stmt2->execute([$row['id']]);
          $apartmentCount = $stmt2->fetchColumn();

          echo "<tr>
            <td>{$i}</td>
            <td>" . htmlspecialchars($row['name']) . "</td>
            <td>" . htmlspecialchars($row['address']) . "</td>
            <td>" . htmlspecialchars($row['piece']) . "</td>";

          if ($isAdminOrManager) {
            echo "<td class='text-center'>
              <a href='index.php?pages=buildingsview&id={$row['id']}' class='btn btn-sm btn-primary'>Görüntüle</a>
              <a href='index.php?pages=buildingsedit&id={$row['id']}' class='btn btn-sm btn-warning'>Düzenle</a>
              <a href='index.php?pages=buildingsdelete&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Bu binayı silmek istediğine emin misiniz?\");'>Sil</a>
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
