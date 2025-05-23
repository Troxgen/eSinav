<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

$vote_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$current_user_id = $_SESSION['user_id'];
$building_id = $_SESSION['building_id'];

if (!$vote_id) {
  echo "<div class='alert alert-danger'>Geçersiz oylama ID'si.</div>";
  exit;
}

// Oylama bilgisi (kendi binasına ait mi?)
$stmt = $db->prepare("SELECT * FROM votes WHERE id = ? AND building_id = ?");
$stmt->execute([$vote_id, $building_id]);
$voteData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$voteData) {
  echo "<div class='alert alert-warning'>Bu oylamaya erişiminiz yok veya oylama bulunamadı.</div>";
  exit;
}

// Kullanıcı zaten oy kullandı mı?
$check = $db->prepare("SELECT id FROM vote_responses WHERE vote_id = ? AND user_id = ?");
$check->execute([$vote_id, $current_user_id]);
$hasVoted = $check->rowCount() > 0;

// Seçenekleri getir
$options = $db->prepare("SELECT * FROM vote_options WHERE vote_id = ?");
$options->execute([$vote_id]);
$options = $options->fetchAll(PDO::FETCH_ASSOC);

// Oy gönderimi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasVoted) {
  $option_id = $_POST['option_id'] ?? null;

  $valid = $db->prepare("SELECT id FROM vote_options WHERE id = ? AND vote_id = ?");
  $valid->execute([$option_id, $vote_id]);

  if ($valid->rowCount() > 0) {
    $insert = $db->prepare("INSERT INTO vote_responses (vote_id, user_id, option_id) VALUES (?, ?, ?)");
    $insert->execute([$vote_id, $current_user_id, $option_id]);
    echo "<script>alert('✅ Oy başarıyla kaydedildi.'); window.location.href='index.php?pages=voteresult&id=$vote_id';</script>";
    exit;
  } else {
    echo "<div class='alert alert-danger'>❌ Geçersiz seçenek.</div>";
  }
}
?>

<section class="section">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="card">
        <div class="card-body pt-4">
          <h5 class="card-title"><?= htmlspecialchars($voteData['title']) ?></h5>
          <p><?= nl2br(htmlspecialchars($voteData['description'])) ?></p>

          <?php if ($hasVoted): ?>
            <div class="alert alert-info">Bu oylamaya zaten katıldınız.</div>
          <?php else: ?>
            <form method="POST">
              <?php foreach ($options as $opt): ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="option_id" value="<?= $opt['id'] ?>" required>
                  <label class="form-check-label"><?= htmlspecialchars($opt['option_text']) ?></label>
                </div>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-success mt-3">Oy Kullan</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
