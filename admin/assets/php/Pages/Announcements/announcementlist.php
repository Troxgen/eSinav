<?php
require_once __DIR__ . '/../../Core/auth.php';
require_once __DIR__ . '/../../Settings/db.php';

requireLogin();
requireApartment();

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id']; // int geliyor

// Admin (5) ve Yönetici (4) için işlem izni
$isAdminOrManager = in_array($role_id, [4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Duyurular</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Başlık</th>
            <th>İçerik</th>
            <th>Oluşturan</th>
            <th>Tarih</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->prepare("
            SELECT a.*, u.full_name 
            FROM announcements a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.building_id = ?
            ORDER BY a.created_at DESC
          ");
          $stmt->execute([$building_id]);

          $i = 1;
          foreach ($stmt as $row) {
            echo "<tr>
              <td>{$i}</td>
              <td>" . htmlspecialchars($row['title']) . "</td>
              <td>" . htmlspecialchars(mb_strimwidth($row['content'], 0, 50, '...')) . "</td>
              <td>" . htmlspecialchars($row['full_name']) . "</td>
              <td>" . date('d.m.Y H:i', strtotime($row['created_at'])) . "</td>";

            if ($isAdminOrManager) {
              echo "<td class='text-center'>
                <a href='index.php?pages=announcementedit&id={$row['id']}' class='btn btn-sm btn-warning'>Düzenle</a>
                <a href='index.php?pages=announcementdelete&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Silmek istediğine emin misin?\")'>Sil</a>
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
