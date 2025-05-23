<?php
require_once __DIR__ . '/../../Settings/db.php';
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();

$building_id = $_SESSION['building_id'] ?? null;
if (!$building_id) {
    echo "Bina seçili değil.";
    exit;
}

$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$paid = $pdo->query("SELECT COUNT(*) FROM bill_shares WHERE LOWER(status) = 'odendi'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM bill_shares WHERE LOWER(status) = 'beklemede'")->fetchColumn();
$totalBills = $paid + $pending;

$lastAidats = $pdo->query("
  SELECT a.block, a.floor, a.door_number, b.share_amount AS amount, b.status, bs.due_date
  FROM bill_shares b
  JOIN apartments a ON b.apartment_id = a.id
  JOIN bills bs ON b.bill_id = bs.id
  ORDER BY bs.due_date DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$aidatPaidAmount = $pdo->query("SELECT SUM(share_amount) FROM bill_shares WHERE LOWER(status) = 'odendi'")->fetchColumn() ?: 0;
$aidatPendingAmount = $pdo->query("SELECT SUM(share_amount) FROM bill_shares WHERE LOWER(status) = 'beklemede'")->fetchColumn() ?: 0;
$billPaidAmount = $pdo->query("SELECT SUM(total_amount) FROM bills WHERE LOWER(status) = 'odendi'")->fetchColumn() ?: 0;
$billPendingAmount = $pdo->query("SELECT SUM(total_amount) FROM bills WHERE LOWER(status) = 'beklemede'")->fetchColumn() ?: 0;

$billPaid = $pdo->query("SELECT COUNT(*) FROM bills WHERE LOWER(status) = 'odendi'")->fetchColumn();
$billPending = $pdo->query("SELECT COUNT(*) FROM bills WHERE LOWER(status) = 'beklemede'")->fetchColumn();

$announcements = $pdo->query("SELECT id, title, content, created_at FROM announcements ORDER BY created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

function getAnnouncementBadgeClass($date) {
    $daysDifference = (new DateTime())->diff(new DateTime($date))->days;
    if ($daysDifference <= 7) return 'text-success';
    elseif ($daysDifference <= 30) return 'text-warning';
    else return 'text-danger';
}

// Apartman özet verileri
$stmt = $pdo->prepare("
  SELECT a.id, a.door_number, a.floor,
         COALESCE(SUM(CASE WHEN LOWER(bs.status) != 'odendi' THEN 1 ELSE 0 END), 0) as unpaid_bills
  FROM apartments a
  LEFT JOIN bill_shares bs ON bs.apartment_id = a.id
  WHERE a.building_id = ?
  GROUP BY a.id
  ORDER BY a.floor DESC, a.door_number ASC
");
$stmt->execute([$building_id]);
$apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kat bazlı grupla
$katlar = [];
foreach ($apartments as $apt) {
    $katlar[$apt['floor']][] = $apt;
}
?>


<div class="pagetitle"><h1>Anasayfa</h1></div>
<section class="section dashboard">
<div class="row">

  <!-- Kullanıcı Kartı -->
  <div class="col-lg-4 col-md-6">
    <div class="card info-card sales-card">
      <div class="card-body">
        <h5 class="card-title">Toplam Kullanıcı</h5>
        <div class="d-flex align-items-center">
          <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
            <i class="bi bi-people"></i>
          </div>
          <div class="ps-3">
            <h6><?= $userCount ?></h6>
            <span class="text-primary small pt-1 fw-bold">Apartmanınızdaki Toplam Kullanıcı Sayısı</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ödenen Fatura -->
  <div class="col-lg-4 col-md-6">
    <div class="card info-card revenue-card">
      <div class="card-body">
        <h5 class="card-title">Ödenen Fatura <span>| Ay</span></h5>
        <div class="d-flex align-items-center">
          <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
            <i class="bi bi-check2-circle"></i>
          </div>
          <div class="ps-3">
            <h6><?= $paid ?></h6>
            <span class="text-success small pt-1 fw-bold">Ödenen Fatura Adedi</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bekleyen Fatura -->
  <div class="col-lg-4 col-md-6">
    <div class="card info-card customers-card">
      <div class="card-body">
        <h5 class="card-title">Bekleyen Fatura <span>| Ay</span></h5>
        <div class="d-flex align-items-center">
          <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
            <i class="bi bi-receipt-cutoff"></i>
          </div>
          <div class="ps-3">
            <h6><?= $pending ?></h6>
            <span class="text-warning small pt-1 fw-bold">Bekleyen Fatura Adedi</span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Pasta Grafik: Ödenek Dağılımı -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Toplam Ödenekler</h5>
        <canvas id="doughnutChart" style="max-height: 400px;"></canvas>
        <script>
          document.addEventListener("DOMContentLoaded", () => {
            new Chart(document.querySelector('#doughnutChart'), {
              type: 'doughnut',
              data: {
                labels: [
                  'Aidat Ödendi (₺)',
                  'Aidat Beklemede (₺)',
                  'Fatura Ödendi (₺)',
                  'Fatura Beklemede (₺)'
                ],
                datasets: [{
                  label: 'Durum Dağılımı',
                  data: [<?= $aidatPaidAmount ?>, <?= $aidatPendingAmount ?>, <?= $billPaidAmount ?>, <?= $billPendingAmount ?>],
                  backgroundColor: [
                    'rgb(75, 192, 192)',
                    'rgb(255, 205, 86)',
                    'rgb(54, 162, 235)',
                    'rgb(255, 99, 132)'
                  ],
                  hoverOffset: 4
                }]
              }
            });
          });
        </script>
      </div>
    </div>
  </div>

  <!-- Duyurular -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Duyurular <span>| Son Eklenenler</span></h5>
        <div class="activity">
          <?php foreach ($announcements as $announcement): ?>
            <?php $badgeClass = getAnnouncementBadgeClass($announcement['created_at']); ?>
            <div class="activity-item d-flex">
              <div class="activite-label <?= $badgeClass ?>"><?= date('d M Y', strtotime($announcement['created_at'])) ?></div>
              <i class='bi bi-circle-fill activity-badge <?= $badgeClass ?> align-self-start'></i>
              <div class="activity-content">
                <a href="index.php?pages=announcement.php&id=<?= $announcement['id'] ?>" class="text-decoration-none">
                  <strong><?= htmlspecialchars($announcement['title']) ?></strong>: <?= htmlspecialchars($announcement['content']) ?>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (empty($announcements)): ?>
            <div class="text-center text-muted">Henüz duyuru yok.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Son 5 Aidat -->
  <div class="col-lg-12">
    <div class="card recent-sales overflow-auto">
      <div class="card-body">
        <h5 class="card-title">Son 5 Aidat</h5>
        <table class="table table-hover">
          <thead>
            <tr><th>Daire</th><th>Tutar (₺)</th><th>Durum</th><th>Son Ödeme</th></tr>
          </thead>
          <tbody>
            <?php foreach ($lastAidats as $row): ?>
            <tr>
              <td><?= $row['block'] ?>/<?= $row['floor'] ?>/<?= $row['door_number'] ?></td>
              <td><?= number_format($row['amount'], 2) ?></td>
              <td><?= strtolower($row['status']) === 'odendi' ? '<span class="badge bg-success">Ödendi</span>' : '<span class="badge bg-warning text-dark">Beklemede</span>' ?></td>
              <td><?= $row['due_date'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lastAidats)): ?>
              <tr><td colspan="4" class="text-center text-muted">Kayıt yok.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>





  <!-- Apartman Önizleme Grid'i -->
<div class="container mt-4">
<div class="apartman-container">
  <div class="roof"></div>

  <?php foreach ($katlar as $floor => $daireler): ?>
    <div class="kat">
      <?php foreach ($daireler as $d): ?>
        <?php $color = $d['unpaid_bills'] > 0 ? 'red' : 'green'; ?>
        <div class="daire <?= $color ?>"
     data-daire="<?= htmlspecialchars($d['door_number']) ?>"
     data-kat="<?= htmlspecialchars($d['floor']) ?>"
     data-durum="<?= $color === 'red' ? 'Borç Var' : 'Temiz' ?>"
     data-sahip="<?= htmlspecialchars($d['full_name'] ?? 'Boş') ?>"
     title="Daire <?= $d['door_number'] ?>">
</div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="kapi"></div>
</div>

</div>




</section>

<script>
document.querySelectorAll('.daire').forEach(kutu => {
  kutu.addEventListener('click', function () {
    const detay = document.getElementById('detayKutu');

    // Seçili daireleri temizle
    document.querySelectorAll('.daire').forEach(k => k.classList.remove('selected'));
    this.classList.add('selected');

    // Detay kutusu içeriğini doldur
    document.getElementById('detayDaire').textContent = this.dataset.daire;
    document.getElementById('detayKat').textContent = this.dataset.kat;
    document.getElementById('detayDurum').textContent = this.dataset.durum;
    document.getElementById('detaySahip').textContent = this.dataset.sahip;

    // Renk sınıfı
    detay.classList.remove('green', 'red');
    detay.classList.add(this.classList.contains('green') ? 'green' : 'red');

    // Konum hesapla
    const rect = this.getBoundingClientRect();
    const binaRect = document.querySelector('.apartman-container').getBoundingClientRect();
    const binaOrtasi = binaRect.left + binaRect.width / 2;

    const scrollTop = window.scrollY;
    const scrollLeft = window.scrollX;
    const detayWidth = 260;

    // Y ekseni - ortalanmış
    const y = rect.top + scrollTop + (rect.height / 2) - 50;
    detay.style.top = `${y}px`;

    // daire apartmanın sol tarafında mı sağında mı
    const daireOrtasi = rect.left + rect.width / 2;

    if (daireOrtasi < binaOrtasi) {
     // Sağ yarıda → kutuyu sola koy
detay.style.left = `${rect.left + scrollLeft - detayWidth - 20}px`;
      detay.style.right = 'auto';
      detay.classList.remove('arrow-right');
      detay.classList.add('arrow-left');
    } else {
       // Sol yarıda → kutuyu sağa koy
       detay.style.left = `${rect.right + scrollLeft + 20}px`;
      detay.style.right = 'auto';
      detay.classList.remove('arrow-left');
      detay.classList.add('arrow-right');
    }





    detay.style.display = 'block';
  });
});

// Dışarı tıklama ile kutuyu kapat ve seçimi temizle
document.addEventListener('click', function (e) {
  const detay = document.getElementById('detayKutu');
  if (!e.target.closest('.daire') && !e.target.closest('#detayKutu')) {
    detay.style.display = 'none';
    document.querySelectorAll('.daire').forEach(k => k.classList.remove('selected'));
  }
});
</script>
