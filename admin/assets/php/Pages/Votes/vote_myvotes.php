<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$user_id = $_SESSION['user_id'];
$building_id = $_SESSION['building_id'];

$stmt = $db->prepare("
  SELECT v.title, vo.option_text, v.start_date, v.end_date 
  FROM vote_responses vr 
  JOIN vote_options vo ON vr.option_id = vo.id 
  JOIN votes v ON vr.vote_id = v.id 
  WHERE vr.user_id = ? AND v.building_id = ?
  ORDER BY v.end_date DESC
");
$stmt->execute([$user_id, $building_id]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Kullandığınız Oylar</h5>

      <?php if (count($votes) === 0): ?>
        <div class="alert alert-info">Henüz oy kullanmadınız.</div>
      <?php else: ?>
        <ul class="list-group">
          <?php foreach ($votes as $vote): ?>
            <li class="list-group-item">
              <strong><?= htmlspecialchars($vote['title']) ?></strong><br>
              Seçiminiz: <span class="text-success"><?= htmlspecialchars($vote['option_text']) ?></span><br>
              Tarih: <?= date("d.m.Y", strtotime($vote['start_date'])) ?> - <?= date("d.m.Y", strtotime($vote['end_date'])) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
