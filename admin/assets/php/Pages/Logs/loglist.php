<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5]); // admin / yönetici

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];

try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.full_name
        FROM login_logs l
        JOIN users u ON l.user_id = u.id
        JOIN apartments a ON u.id = a.owner_id OR u.id IN (
            SELECT tenant_id FROM rentals WHERE apartment_id = a.id
        )
        WHERE a.building_id = ?
        GROUP BY l.id
        ORDER BY l.login_time DESC
    ");
    $stmt->execute([$building_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Hata: " . htmlspecialchars($e->getMessage()));
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Giriş Kayıtları</h5>

      <?php if (empty($logs)): ?>
        <div class="alert alert-info">Henüz giriş kaydı bulunmuyor.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark text-center">
              <tr>
                <th>#</th>
                <th>Kullanıcı</th>
                <th>Giriş Zamanı</th>
                <th>IP Adresi</th>
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
                <td><?= htmlspecialchars(substr($log['user_agent'], 0, 80)) ?><?= strlen($log['user_agent']) > 80 ? '...' : '' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

