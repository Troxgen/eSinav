<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5 , 3 ,2 ]); // admin, yönetici

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
?>

<div class="pagetitle mb-4">
  <h1>Bakım & Onarım Talepleri</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Anasayfa</a></li>
      <li class="breadcrumb-item active">Talepler</li>
    </ol>
  </nav>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Talep Listesi</h5>

    <div class="table-responsive">
      <table id="requestTable" class="table table-bordered table-striped table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Daire</th>
            <th>Kullanıcı</th>
            <th>Başlık</th>
            <th>Açıklama</th>
            <th>Durum</th>
            <th>Tarih</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->prepare("
            SELECT r.*, u.full_name, a.block, a.floor, a.door_number 
            FROM maintenance_requests r 
            JOIN users u ON r.user_id = u.id 
            JOIN apartments a ON r.apartment_id = a.id 
            WHERE r.building_id = ?
            ORDER BY r.created_at DESC
          ");
          $stmt->execute([$building_id]);

          $i = 1;
          foreach ($stmt as $row):
          ?>
          <tr>
            <td class="text-center"><?= $i++ ?></td>
            <td><?= "Blok " . htmlspecialchars($row['block']) . " / Kat " . $row['floor'] . " / Kapı " . $row['door_number'] ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td class="text-center">
              <?php
                $status = $row['status'];
                $badge = match($status) {
                  'beklemede' => 'warning',
                  'yapildi'   => 'success',
                  'iptal'     => 'danger',
                  default     => 'secondary'
                };
              ?>
              <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
            </td>
            <td><?= date("d.m.Y H:i", strtotime($row['created_at'])) ?></td>
            <td class="text-center">
              <a href="index.php?pages=maintenanceedit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
              <a href="index.php?pages=maintenancedelete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    $('#requestTable').DataTable({
      language: {
        url: '//cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json'
      },
      pageLength: 10,
      order: [[6, 'desc']]
    });
  });
</script>
