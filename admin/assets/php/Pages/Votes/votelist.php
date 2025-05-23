<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4 ,3]);
require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];

$stmt = $db->prepare("
  SELECT v.*, u.full_name
  FROM votes v
  LEFT JOIN users u ON v.created_by = u.id
  WHERE v.building_id = ?
  ORDER BY v.id DESC
");
$stmt->execute([$building_id]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
  <div class="card">
    <div class="card-body pt-3">
      <h5 class="card-title">Oylama Listesi</h5>

      <?php if (count($votes) === 0): ?>
        <div class="alert alert-info">Henüz oylama oluşturulmamış.</div>
      <?php else: ?>
        <table class="table table-bordered table-striped">
          <thead class="table-dark text-center">
            <tr>
              <th>#</th>
              <th>Başlık</th>
              <th>Başlangıç</th>
              <th>Bitiş</th>
              <th>Oluşturan</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1; foreach ($votes as $vote): ?>
              <tr>
                <td class="text-center"><?= $i++ ?></td>
                <td><?= htmlspecialchars($vote['title']) ?></td>
                <td><?= date('d.m.Y', strtotime($vote['start_date'])) ?></td>
                <td><?= date('d.m.Y', strtotime($vote['end_date'])) ?></td>
                <td><?= htmlspecialchars($vote['full_name']) ?></td>
                <td class="text-center">
                  <a href="index.php?pages=voteedit&id=<?= $vote['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                  <a href="index.php?pages=votedelete&id=<?= $vote['id'] ?>" onclick="return confirm('Bu oylamayı silmek istiyor musunuz?')" class="btn btn-sm btn-danger">Sil</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>
