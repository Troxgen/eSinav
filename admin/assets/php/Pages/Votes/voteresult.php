<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$user_id = $_SESSION['user_id'];
$building_id = $_SESSION['building_id']; // apartment_id değil artık!

// Sadece kullanıcının binasındaki oylamalar
$stmt = $db->prepare("SELECT * FROM votes WHERE building_id = ? ORDER BY end_date DESC");
$stmt->execute([$building_id]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Oylama Sonuçları</h5>

      <?php if (count($votes) == 0): ?>
        <div class="alert alert-info">Henüz hiç oylama yapılmamış.</div>
      <?php else: ?>
        <?php foreach ($votes as $vote): ?>
          <div class="mb-4 border p-3 rounded shadow-sm">
            <h5><?= htmlspecialchars($vote['title']) ?></h5>
            <p><?= nl2br(htmlspecialchars($vote['description'])) ?></p>

            <?php
            // Toplam oy sayısı
            $total = $db->prepare("SELECT COUNT(*) FROM vote_responses WHERE vote_id = ?");
            $total->execute([$vote['id']]);
            $totalCount = $total->fetchColumn();

            // Seçenekler ve oy sayıları
            $options = $db->prepare("
              SELECT vo.option_text, COUNT(vr.id) AS vote_count
              FROM vote_options vo
              LEFT JOIN vote_responses vr ON vr.option_id = vo.id
              WHERE vo.vote_id = ?
              GROUP BY vo.id
              ORDER BY vote_count DESC
            ");
            $options->execute([$vote['id']]);
            $results = $options->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if ($totalCount == 0): ?>
              <p class="text-muted">Henüz oy kullanılmamış.</p>
            <?php else: ?>
              <ul class="list-group">
                <?php foreach ($results as $res): 
                  $percent = round(($res['vote_count'] / $totalCount) * 100);
                ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($res['option_text']) ?>
                    <span class="badge bg-primary rounded-pill">
                      <?= $res['vote_count'] ?> oy (<?= $percent ?>%)
                    </span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <small class="text-muted d-block mt-2">Toplam Oy: <?= $totalCount ?></small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
