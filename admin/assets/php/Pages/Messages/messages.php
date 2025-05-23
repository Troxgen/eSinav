<?php
require_once __DIR__ . '/../../Settings/db.php';
require_once __DIR__ . '/../../Core/auth.php';

requireLogin();
requireRole([4, 5]); // admin & yönetici

$userId = $_SESSION['user_id'];
$building_id = $_SESSION['building_id'];
$success = $error = "";

// Başvuru işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
  $message_id = $_POST['message_id'];
  $status = $_POST['status'];

  try {
    // Mesajı kontrol et
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$message_id, $userId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
      $error = "❌ Başvuru bulunamadı.";
    } else {
      // Güncelle
      $stmt = $pdo->prepare("UPDATE messages SET status = ? WHERE id = ?");
      $stmt->execute([$status, $message_id]);

      if ($status === 'approved') {
        // Kullanıcıyı daireye bağla (örnek: rental ilişkisi kur)
        $apartmentId = $message['apartment_id'] ?? null;

        if ($apartmentId) {
          // KİRACI OLARAK EKLE
          $stmt = $pdo->prepare("INSERT IGNORE INTO rentals (apartment_id, tenant_id, start_date) VALUES (?, ?, NOW())");
          $stmt->execute([$apartmentId, $message['sender_id']]);
        }
      }

      $success = "✅ Başvuru durumu güncellendi.";
    }
  } catch (PDOException $e) {
    $error = "❌ Hata: " . $e->getMessage();
  }
}

// Başvuru listesi
$stmt = $pdo->prepare("
  SELECT m.*, u.full_name, a.block, a.door_number
  FROM messages m
  JOIN users u ON m.sender_id = u.id
  JOIN apartments a ON m.apartment_id = a.id
  WHERE m.receiver_id = ? AND m.status = 'pending'
  ORDER BY m.created_at DESC
");
$stmt->execute([$userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Daire Katılım Başvuruları</h5>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <?php if (count($messages) === 0): ?>
        <div class="alert alert-info">📭 Henüz başvuru yok.</div>
      <?php else: ?>
        <table class="table table-bordered">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th>
              <th>Gönderen</th>
              <th>Daire</th>
              <th>Mesaj</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($messages as $index => $msg): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($msg['full_name']) ?></td>
                <td><?= "Blok {$msg['block']} - Kapı {$msg['door_number']}" ?></td>
                <td><?= nl2br(htmlspecialchars($msg['message'])) ?></td>
                <td class="text-center">
                  <form method="POST" class="d-flex gap-1">
                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                    <button type="submit" name="status" value="approved" class="btn btn-success btn-sm">Onayla</button>
                    <button type="submit" name="status" value="rejected" class="btn btn-danger btn-sm">Reddet</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>
