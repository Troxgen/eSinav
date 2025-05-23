<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // yönetici ve admin
require_once __DIR__ . '/../../Settings/db.php';

$currentLevel = $_SESSION['user_role_level'] ?? 99;
$building_id = $_SESSION['building_id'] ?? 0;
?>

<section class="section">
  <div class="pagetitle mb-4">
    <h1>Kullanıcılar</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Anasayfa</a></li>
        <li class="breadcrumb-item active">Kullanıcı Listesi</li>
      </ol>
    </nav>
  </div>

  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Kullanıcı Listesi</h5>

      <a href="index.php?pages=usercreate" class="btn btn-success mb-3">+ Yeni Kullanıcı</a>

      <table id="userTable" class="table table-bordered table-striped table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Ad Soyad</th>
            <th>Rol</th>
            <th>Email</th>
            <th>Telefon</th>
            <th>TC No</th>
            <th>Kayıt Tarihi</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $db->prepare("
            SELECT u.*, r.name AS role_name, r.level AS role_level
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE r.level < ?
              AND (
                u.id IN (
                  SELECT owner_id FROM apartments WHERE building_id = ?
                )
                OR u.id IN (
                  SELECT tenant_id FROM rentals r
                  JOIN apartments a ON r.apartment_id = a.id
                  WHERE a.building_id = ?
                )
              )
            ORDER BY u.created_at DESC
          ");
          $stmt->execute([$currentLevel, $building_id, $building_id]);

          $counter = 1;
          foreach ($stmt as $user):
            $tcValid = preg_match('/^[1-9][0-9]{10}$/', $user['tc_no']);
          ?>
            <tr<?= $tcValid ? "" : " class='table-danger'" ?>>
              <td class="text-center"><?= $counter++ ?></td>
              <td><?= htmlspecialchars($user['full_name']) ?></td>
              <td><span class="badge bg-primary text-capitalize"><?= htmlspecialchars($user['role_name']) ?></span></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['phone']) ?></td>
              <td><?= $tcValid ? $user['tc_no'] : "<strong>Geçersiz TC</strong>" ?></td>
              <td><?= date("d.m.Y H:i", strtotime($user['created_at'])) ?></td>
              <td class="text-center">
                <a href="index.php?pages=useredit&id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                <a href="index.php?pages=userdelete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');">Sil</a>
              </td>
            </tr>
          <?php endforeach; ?>
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
    $('#userTable').DataTable({
      language: {
        url: '//cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json'
      }
    });
  });
</script>
