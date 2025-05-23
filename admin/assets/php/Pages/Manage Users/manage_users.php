<?php
require_once __DIR__ . '/../../Core/auth.php';
require_once __DIR__ . '/../../Settings/db.php';

// 1) Giriş ve apartman kontrolü
requireLogin();         // Kullanıcının giriş yapmış olması
requireApartment();     // Kullanıcının bir apartmanı olmalı (oturumda building_id bekleniyor)

// 2) Oturum değişkenleri
$building_id = $_SESSION['building_id']; 
$role_id     = $_SESSION['role_id'];    // Örn: 4=Yönetici, 5=Admin

// 3) Yönetici veya Admin kontrolü
$isAdminOrManager = in_array($role_id, [4, 5]);

// 4) Formdan onay/red işlemi geldiyse, veritabanını güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);

    if (isset($_POST['approve'])) {
        // Onay: status=1
        $stmt = $pdo->prepare("UPDATE users SET status=1 WHERE id=?");
        $stmt->execute([$userId]);
    } elseif (isset($_POST['reject'])) {
        // Reddet: status=2
        $stmt = $pdo->prepare("UPDATE users SET status=2 WHERE id=?");
        $stmt->execute([$userId]);
    }

    // İşlemlerden sonra tekrar aynı sayfaya yönlendir
    header("Location: index.php?pages=manage_users");
    exit;
}

// 5) Veritabanından onay bekleyen kullanıcılar (status=0) çekilir
if ($role_id == 5) {
    // Admin, TÜM bekleyen kullanıcıları görsün
    $sql = "
        SELECT u.*, r.name AS role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.status = 0
        ORDER BY u.id DESC
    ";
    $stmt = $pdo->query($sql);

} else {
    // Yönetici (role_id=4), SADECE kendi binasına ait kullanıcıları görsün
    // (varsayım: rentals veya apartments tablosundan building_id filtreleniyor)
    $sql = "
        SELECT u.*, r.name AS role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        JOIN rentals rt ON rt.tenant_id = u.id
        JOIN apartments a ON a.id = rt.apartment_id
        WHERE u.status = 0
          AND a.building_id = ?
        ORDER BY u.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$building_id]);
}

$pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Onay Bekleyen Kullanıcılar</h5>

      <table class="table table-bordered table-hover">
        <thead class="table-dark text-center">
          <tr>
            <th>#</th>
            <th>Ad Soyad</th>
            <th>E-Posta</th>
            <th>Telefon</th>
            <th>Rol</th>
            <?php if ($isAdminOrManager): ?>
              <th>İşlem</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php 
          $i = 1;
          foreach ($pendingUsers as $user):
          ?>
            <tr>
              <td class="text-center"><?= $i ?></td>
              <td><?= htmlspecialchars($user['full_name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['phone']) ?></td>
              <td><?= htmlspecialchars($user['role_name']) ?></td>

              <?php if ($isAdminOrManager): ?>
                <td class="text-center">
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <button type="submit" name="approve" class="btn btn-sm btn-success">Onayla</button>
                  </form>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <button type="submit" name="reject" class="btn btn-sm btn-danger">Reddet</button>
                  </form>
                </td>
              <?php endif; ?>
            </tr>
          <?php 
            $i++;
          endforeach; 
          ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
