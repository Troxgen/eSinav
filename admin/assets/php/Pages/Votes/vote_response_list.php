<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]);
require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];

try {
    $stmt = $db->prepare("
        SELECT vr.*, u.full_name, v.title AS vote_title, vo.option_text
        FROM vote_responses vr
        JOIN users u ON vr.user_id = u.id
        JOIN votes v ON vr.vote_id = v.id
        JOIN vote_options vo ON vr.option_id = vo.id
        WHERE v.building_id = ?
        ORDER BY vr.id DESC
    ");
    $stmt->execute([$building_id]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Veritabanı hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Oylama Katılım Listesi</h5>

      <?php if (count($responses) === 0): ?>
        <div class="alert alert-info">Henüz oy kullanılmamış.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark text-center">
              <tr>
                <th>#</th>
                <th>Kullanıcı</th>
                <th>Oylama</th>
                <th>Seçilen Seçenek</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($responses as $index => $r): ?>
              <tr>
                <td class="text-center"><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['vote_title']) ?></td>
                <td><?= htmlspecialchars($r['option_text']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
