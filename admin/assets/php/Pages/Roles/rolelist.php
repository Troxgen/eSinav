<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5]); // Sadece admin


require_once __DIR__ . '/../../Settings/db.php';

$currentLevel = $_SESSION['user_role_level'] ?? 99;

// Sadece kendi seviyesinden düşük roller listelenebilir
$stmt = $db->prepare("SELECT * FROM roles WHERE level < ? ORDER BY level ASC");
$stmt->execute([$currentLevel]);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Geri bildirim mesajı
$status = $_GET['status'] ?? null;
$feedback = [
  'deleted'      => '✅ Rol başarıyla silindi.',
  'notfound'     => '❌ Rol bulunamadı.',
  'unauthorized' => '⚠️ Bu rolü görmeye yetkiniz yok.',
  'dberror'      => '❌ Veritabanı hatası.',
  'invalid'      => '⚠️ Geçersiz işlem.',
];
$message = $feedback[$status] ?? '';
$alertType = in_array($status, ['deleted']) ? 'success' : 'danger';
?>

<div class="pagetitle">
  <h1>Rol Yönetimi</h1>
</div>

<div class="card">
  <div class="card-body pt-4">
    <h5 class="card-title">Kayıtlı Roller</h5>

    <?php if ($message): ?>
      <div class="alert alert-<?= $alertType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <a href="index.php?pages=rolecreate" class="btn btn-success mb-3">+ Yeni Rol Ekle</a>

    <table class="table table-bordered table-striped">
      <thead class="table-dark text-center">
        <tr>
          <th>#</th>
          <th>Rol Adı</th>
          <th>Yetki Seviyesi</th>
          <th>İşlemler</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($roles as $i => $role): ?>
          <tr class="text-center">
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($role['name']) ?></td>
            <td><?= $role['level'] ?></td>
            <td>
              <a href="index.php?pages=roleedit&id=<?= $role['id'] ?>" class="btn btn-warning btn-sm">Düzenle</a>
              <a href="index.php?pages=roledelete&id=<?= $role['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Bu rolü silmek istediğinizden emin misiniz?')">Sil</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>
</div>
