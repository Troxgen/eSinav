<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([4, 5]); // admin / yönetici

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$filters = [];
$where = [];

$sql = "
  SELECT l.*, u.full_name
  FROM login_logs l
  JOIN users u ON l.user_id = u.id
  WHERE u.id IN (
    SELECT a.owner_id FROM apartments a WHERE a.building_id = :building_id
    UNION
    SELECT r.tenant_id FROM apartments a
    JOIN rentals r ON r.apartment_id = a.id
    WHERE a.building_id = :building_id
  )
";
$filters['building_id'] = $building_id;

if (!empty($_GET['user_id'])) {
    $sql .= " AND u.id = :user_id";
    $filters['user_id'] = $_GET['user_id'];
}

if (!empty($_GET['date'])) {
    $sql .= " AND DATE(l.login_time) = :date";
    $filters['date'] = $_GET['date'];
}

$sql .= " ORDER BY l.login_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($filters);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcı listesi (bu binadaki tüm ev sahipleri ve kiracılar)
$user_stmt = $pdo->prepare("
  SELECT DISTINCT u.id, u.full_name
  FROM users u
  WHERE u.id IN (
    SELECT a.owner_id FROM apartments a WHERE a.building_id = :building_id
    UNION
    SELECT r.tenant_id FROM apartments a
    JOIN rentals r ON r.apartment_id = a.id
    WHERE a.building_id = :building_id
  )
  ORDER BY u.full_name
");
$user_stmt->execute(['building_id' => $building_id]);
$users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Giriş Logları (Filtreli)</h5>

      <form class="row g-3 mb-4" method="GET">
        <input type="hidden" name="pages" value="logfilter">
        <div class="col-md-5">
          <label for="user_id" class="form-label">Kullanıcı</label>
          <select name="user_id" id="user_id" class="form-select">
            <option value="">Tümü</option>
            <?php foreach ($users as $user): ?>
              <option value="<?= $user['id'] ?>" <?= ($_GET['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['full_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-5">
          <label for="date" class="form-label">Tarih</label>
          <input type="date" name="date" id="date" class="form-control" value="<?= $_GET['date'] ?? '' ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Filtrele</button>
        </div>
      </form>

      <?php if (empty($logs)): ?>
        <div class="alert alert-info">Sonuç bulunamadı.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark text-center">
              <tr>
                <th>#</th>
                <th>Kullanıcı</th>
                <th>Giriş Zamanı</th>
                <th>IP</th>
                <th>Tarayıcı</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $index => $log): ?>
              <tr class="text-center">
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($log['full_name']) ?></td>
                <td><?= date("d.m.Y H:i", strtotime($log['login_time'])) ?></td>
                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                <td><?= htmlspecialchars(substr($log['user_agent'], 0, 60)) ?><?= strlen($log['user_agent']) > 60 ? '...' : '' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

