<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$userId = $_SESSION['user_id'];
$building_id = $_SESSION['building_id'];
$success = "";
$error = "";

// Kullanıcının kendi binasında, aktif ve henüz oy vermediği oylamalar
$stmt = $db->prepare("
  SELECT v.*
  FROM votes v
  WHERE CURDATE() BETWEEN v.start_date AND v.end_date
    AND v.building_id = ?
    AND NOT EXISTS (
      SELECT 1 FROM vote_responses vr
      WHERE vr.vote_id = v.id AND vr.user_id = ?
    )
  ORDER BY v.end_date ASC
");
$stmt->execute([$building_id, $userId]);
$activeVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Oy gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vote_id = $_POST['vote_id'] ?? null;
  $option_id = $_POST['option_id'] ?? null;

  if (!$vote_id || !$option_id) {
    $error = "❌ Lütfen bir seçenek işaretleyin.";
  } else {
    // Seçenek ve oylama gerçekten bu binaya mı ait?
    $check = $db->prepare("
      SELECT vo.id FROM vote_options vo
      JOIN votes v ON vo.vote_id = v.id
      WHERE vo.id = ? AND vo.vote_id = ? AND v.building_id = ?
    ");
    $check->execute([$option_id, $vote_id, $building_id]);

    if (!$check->fetch()) {
      $error = "❌ Yetkiniz olmayan bir oylamaya müdahale edemezsiniz.";
    } else {
      // Zaten oy kullanmış mı?
      $dupe = $db->prepare("SELECT id FROM vote_responses WHERE vote_id = ? AND user_id = ?");
      $dupe->execute([$vote_id, $userId]);

      if ($dupe->rowCount() > 0) {
        $error = "❌ Bu oylamaya zaten oy kullandınız.";
      } else {
        try {
          $stmt = $db->prepare("INSERT INTO vote_responses (vote_id, user_id, option_id) VALUES (?, ?, ?)");
          $stmt->execute([$vote_id, $userId, $option_id]);
          $success = "✅ Oy başarıyla kaydedildi.";
          header("Refresh: 1"); // Sayfa yenilensin
        } catch (PDOException $e) {
          $error = "❌ Veritabanı hatası: " . $e->getMessage();
        }
      }
    }
  }
}
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-4">
      <h5 class="card-title">Aktif Oylamalar</h5>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (count($activeVotes) === 0): ?>
        <p class="text-muted">Şu anda oy kullanabileceğiniz bir oylama yok.</p>
      <?php else: ?>
        <?php foreach ($activeVotes as $vote): ?>
          <?php
            $voteOptions = $db->prepare("SELECT * FROM vote_options WHERE vote_id = ?");
            $voteOptions->execute([$vote['id']]);
            $options = $voteOptions->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <div class="mb-4 border p-3 rounded shadow-sm">
            <h5><?= htmlspecialchars($vote['title']) ?></h5>
            <p><?= nl2br(htmlspecialchars($vote['description'])) ?></p>
            <form method="POST">
              <input type="hidden" name="vote_id" value="<?= $vote['id'] ?>">
              <?php foreach ($options as $opt): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="option_id" id="opt<?= $opt['id'] ?>" value="<?= $opt['id'] ?>" required>
                  <label class="form-check-label" for="opt<?= $opt['id'] ?>">
                    <?= htmlspecialchars($opt['option_text']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary btn-sm mt-2">Oy Kullan</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
