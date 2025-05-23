<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$isAdminOrManager = in_array($role_id, [4, 5]);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Toplantılar</h5>

      <div class="table-responsive">
        <table class="table table-hover table-bordered" id="meetingTable">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th>
              <th>Konu</th>
              <th>Tarih</th>
              <th>Yer</th>
              <?php if ($isAdminOrManager): ?>
                <th>İşlemler</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt = $pdo->prepare("SELECT * FROM meetings WHERE building_id = ? ORDER BY date DESC");
            $stmt->execute([$building_id]);
            $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $i = 1;
            foreach ($meetings as $row): ?>
              <tr class="text-center">
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['topic']) ?></td>
                <td><?= date("d.m.Y H:i", strtotime($row['date'])) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <?php if ($isAdminOrManager): ?>
                  <td>
                    <a href="index.php?pages=meetingedit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                    <a href="index.php?pages=meetingdelete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<!-- DataTables (isteğe bağlı) -->
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function () {
    $('#meetingTable').DataTable({
      language: {
        url: '//cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json'
      },
      order: [[2, 'desc']]
    });
  });
</script>
