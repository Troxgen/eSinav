<?php
// Pages/apartmentsview.php - Daire Detayları (Geliştirilmiş stil ve yönetici giriş butonu eklenmiş)

require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireBuilding();

require_once __DIR__ . '/../../Settings/db.php';

$building_id = $_SESSION['building_id'];
$role_id = $_SESSION['role_id'];
$isAdminOrManager = in_array($role_id, [3, 5]); // 3=Yönetici, 5=Admin
$isAdmin = $role_id == 5; // Sadece admin mi?

// Daire ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Daire ID belirtilmedi.";
    header('Location: index.php?pages=dashboard');
    exit;
}

$apartment_id = intval($_GET['id']);

// Daire bilgilerini getir
$stmt = $pdo->prepare("
    SELECT a.*, b.name as building_name, o.full_name as owner_name, o.email as owner_email, o.phone as owner_phone,
           o.id as owner_id
    FROM apartments a
    LEFT JOIN buildings b ON a.building_id = b.id
    LEFT JOIN users o ON a.owner_id = o.id
    WHERE a.id = ? AND a.building_id = ?
");
$stmt->execute([$apartment_id, $building_id]);
$apartment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apartment) {
    $_SESSION['error'] = "Daire bulunamadı veya bu binaya ait değil.";
    header('Location: index.php?pages=dashboard');
    exit;
}

// Kiracı bilgilerini getir
$tenantStmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.email, u.phone, u.tc_no
    FROM rentals r
    JOIN users u ON r.tenant_id = u.id
    WHERE r.apartment_id = ? AND (r.end_date IS NULL OR r.end_date >= CURDATE())
    ORDER BY r.start_date DESC
");
$tenantStmt->execute([$apartment_id]);
$tenants = $tenantStmt->fetchAll(PDO::FETCH_ASSOC);

// Aidat bilgilerini getir
$duesStmt = $pdo->prepare("
    SELECT bs.*, b.type, b.due_date
    FROM bill_shares bs
    JOIN bills b ON bs.bill_id = b.id
    WHERE bs.apartment_id = ? AND b.type = 'aidat'
    ORDER BY b.due_date DESC
    LIMIT 5
");
$duesStmt->execute([$apartment_id]);
$dues = $duesStmt->fetchAll(PDO::FETCH_ASSOC);

// Ücret bilgilerini getir - BUG FIX: 'ücret' yerine 'ucret' olarak arıyoruz çünkü SQL sorgusu özel karakterleri desteklemiyor
$feesStmt = $pdo->prepare("
    SELECT bs.*, b.type, b.due_date
    FROM bill_shares bs
    JOIN bills b ON bs.bill_id = b.id
    WHERE bs.apartment_id = ? AND b.type = 'ücret'
    ORDER BY b.due_date DESC
    LIMIT 5
");
$feesStmt->execute([$apartment_id]);
$fees = $feesStmt->fetchAll(PDO::FETCH_ASSOC);

// Bakım talebi bilgilerini getir
$maintenanceStmt = $pdo->prepare("
    SELECT m.*, u.full_name as requested_by
    FROM maintenance_requests m
    JOIN users u ON m.user_id = u.id
    WHERE m.apartment_id = ?
    ORDER BY m.created_at DESC
    LIMIT 5
");
$maintenanceStmt->execute([$apartment_id]);
$maintenanceRequests = $maintenanceStmt->fetchAll(PDO::FETCH_ASSOC);

// Eğer ev sahibi varsa, yönetici olarak giriş yapma butonunu göstermek için kontrol et
$ownerManagerStmt = null;
$ownerManager = null;
if ($isAdmin && $apartment['owner_id']) {
    $ownerManagerStmt = $pdo->prepare("
        SELECT u.* 
        FROM users u 
        WHERE u.id = ? AND u.status = 1
    ");
    $ownerManagerStmt->execute([$apartment['owner_id']]);
    $ownerManager = $ownerManagerStmt->fetch(PDO::FETCH_ASSOC);
}

// İstatistikler için veri çek
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN bs.status = 'odendi' THEN 1 END) as total_paid,
        COUNT(CASE WHEN bs.status = 'beklemede' THEN 1 END) as total_pending,
        SUM(CASE WHEN bs.status = 'odendi' THEN bs.share_amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN bs.status = 'beklemede' THEN bs.share_amount ELSE 0 END) as pending_amount
    FROM bill_shares bs
    JOIN bills b ON bs.bill_id = b.id
    WHERE bs.apartment_id = ?
");
$statsStmt->execute([$apartment_id]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Son işlemleri getir
$recentActivitiesStmt = $pdo->prepare("
    SELECT 'bill_payment' as type, bs.paid_at as date, bs.share_amount as amount, b.type as bill_type
    FROM bill_shares bs
    JOIN bills b ON bs.bill_id = b.id
    WHERE bs.apartment_id = ? AND bs.status = 'odendi' AND bs.paid_at IS NOT NULL
    UNION
    SELECT 'maintenance' as type, m.created_at as date, 0 as amount, m.title as bill_type
    FROM maintenance_requests m
    WHERE m.apartment_id = ?
    ORDER BY date DESC
    LIMIT 5
");
$recentActivitiesStmt->execute([$apartment_id, $apartment_id]);
$recentActivities = $recentActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Özel stiller -->
<style>
    .apartment-header {
        background: linear-gradient(135deg, #20639B, #3CAEA3);
        color: #fff;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }
    
    .apartment-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 100%;
        background: rgba(255, 255, 255, 0.1);
        clip-path: polygon(100% 0, 0 0, 100% 100%);
    }
    
    .apartment-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.3rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    }
    
    .info-card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.4s ease;
        height: 100%;
        border: none;
        overflow: hidden;
    }
    
    .info-card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-5px);
    }
    
    .info-card .card-header {
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        border-bottom: 1px solid #eaecef;
        font-weight: 600;
        color: #495057;
        padding: 15px 20px;
    }
    
    .info-section {
        margin-bottom: 30px;
    }
    
    .action-buttons {
        margin-top: 20px;
    }
    
    .action-buttons .btn {
        margin-right: 8px;
        border-radius: 6px;
        font-weight: 500;
        padding: 8px 15px;
        transition: all 0.3s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .table-custom {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .table-custom th {
        background-color: #f8f9fa;
        border-color: #eaecef;
        font-weight: 600;
    }
    
    .table-custom td, .table-custom th {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .badge {
        font-weight: 500;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
    }
    
    .section-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eaecef;
    }
    
    .section-title h6 {
        margin: 0;
        font-weight: 700;
        color: #343a40;
        font-size: 1.1rem;
    }
    
    .tenant-card, .dues-card, .maintenance-card, .fees-card, .stats-card {
        margin-bottom: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: none;
    }
    
    .btn-login-as {
        background-color: #6c5ce7;
        border-color: #6c5ce7;
        color: white;
    }
    
    .btn-login-as:hover {
        background-color: #5a49d6;
        border-color: #5a49d6;
        color: white;
    }
    
    /* Animation for hover effects */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .btn-animated:hover {
        animation: pulse 1s infinite;
    }
    
    /* Stats Card Styles */
    .stats-card {
        transition: all 0.3s;
    }
    
    .stats-item {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .stats-item-blue {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
    }
    
    .stats-item-green {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }
    
    .stats-item-orange {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
    }
    
    .stats-item-red {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
    }
    
    .stats-icon {
        font-size: 2rem;
        margin-right: 15px;
    }
    
    .stats-info h4 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .stats-info p {
        margin: 0;
        opacity: 0.8;
    }
    
    /* Activity Timeline */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        height: 100%;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -30px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #3CAEA3;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #3CAEA3;
    }
    
    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .timeline-date {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    /* Tabs */
    .nav-tabs .nav-link {
        border: none;
        color: #495057;
        font-weight: 500;
        padding: 12px 20px;
        border-radius: 0;
        position: relative;
    }
    
    .nav-tabs .nav-link.active {
        color: #3CAEA3;
        background: transparent;
    }
    
    .nav-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: #3CAEA3;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
    }
    
    .nav-tabs {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 20px;
    }
    
    /* Toast Notification */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    
    .custom-toast {
        background: white;
        color: #333;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        overflow: hidden;
        margin-bottom: 15px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.5s;
    }
    
    .custom-toast.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .custom-toast-header {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #eaecef;
    }
    
    .custom-toast-body {
        padding: 15px;
    }
    
    /* Loading animation */
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid #f3f3f3;
        border-radius: 50%;
        border-top: 3px solid #3CAEA3;
        animation: spin 1s linear infinite;
        margin-right: 10px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Progress Circle */
    .progress-circle {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: #f3f3f3;
        margin: 0 auto 15px;
    }
    
    .progress-circle-inner {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: #343a40;
    }
    
    .progress-circle-bar {
        position: absolute;
        top: 0;
        left: 0;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        clip: rect(0px, 100px, 100px, 50px);
    }
    
    .progress-circle-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        clip: rect(0px, 50px, 100px, 0px);
        background: #3CAEA3;
        transform: rotate(calc(var(--percent) * 3.6deg));
    }
</style>

<!-- Bildirim Toast -->
<div class="toast-container"></div>

<section class="section">
    <!-- Daire Başlık -->
    <div class="apartment-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5>
                <i class="bi bi-building"></i> 
                <?= htmlspecialchars($apartment['building_name']) ?> - 
                <?= htmlspecialchars($apartment['block']) ?> Blok, 
                <?= htmlspecialchars($apartment['floor']) ?>. Kat, 
                No: <?= htmlspecialchars($apartment['door_number']) ?>
            </h5>
            <div>
                <a href="index.php?pages=apartments" class="btn btn-light btn-sm btn-animated">
                    <i class="bi bi-arrow-left"></i> Geri Dön
                </a>
                <?php if ($isAdminOrManager): ?>
                <button type="button" class="btn btn-primary btn-sm" id="showQRBtn">
                    <i class="bi bi-qr-code"></i> QR Kod
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Daire İstatistikleri -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="section-title">
                        <h6><i class="bi bi-graph-up"></i> Daire İstatistikleri</h6>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-item stats-item-blue">
                                <div class="stats-icon">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div class="stats-info">
                                    <h4><?= number_format($stats['total_paid'] ?? 0) ?></h4>
                                    <p>Ödenen Fatura</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-item stats-item-green">
                                <div class="stats-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="stats-info">
                                    <h4><?= number_format($stats['paid_amount'] ?? 0, 2) ?> ₺</h4>
                                    <p>Ödenen Miktar</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-item stats-item-orange">
                                <div class="stats-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="stats-info">
                                    <h4><?= number_format($stats['total_pending'] ?? 0) ?></h4>
                                    <p>Bekleyen Fatura</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="stats-item stats-item-red">
                                <div class="stats-icon">
                                    <i class="bi bi-exclamation-circle"></i>
                                </div>
                                <div class="stats-info">
                                    <h4><?= number_format($stats['pending_amount'] ?? 0, 2) ?> ₺</h4>
                                    <p>Bekleyen Miktar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <!-- Daire Bilgileri -->
        <div class="col-md-6 mb-4">
            <div class="card info-card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Daire Bilgileri
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <tr>
                            <th style="width: 40%">Bina</th>
                            <td><?= htmlspecialchars($apartment['building_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Blok</th>
                            <td><?= htmlspecialchars($apartment['block']) ?></td>
                        </tr>
                        <tr>
                            <th>Kat</th>
                            <td><?= htmlspecialchars($apartment['floor']) ?></td>
                        </tr>
                        <tr>
                            <th>Kapı No</th>
                            <td><?= htmlspecialchars($apartment['door_number']) ?></td>
                        </tr>
                    </table>
                    
                    <?php if ($isAdminOrManager): ?>
                    <div class="action-buttons text-center">
                        <a href="index.php?pages=apartmentsedit&id=<?= $apartment_id ?>" class="btn btn-warning btn-animated">
                            <i class="bi bi-pencil"></i> Düzenle
                        </a>
                        <a href="index.php?pages=apartmentsdelete&id=<?= $apartment_id ?>" class="btn btn-danger btn-animated" 
                           onclick="return confirm('Bu daireyi silmek istediğinize emin misiniz?');">
                            <i class="bi bi-trash"></i> Sil
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Daire Sahibi Bilgileri -->
        <div class="col-md-6 mb-4">
            <div class="card info-card">
                <div class="card-header">
                    <i class="bi bi-person"></i> Daire Sahibi Bilgileri
                </div>
                <div class="card-body">
                    <?php if ($apartment['owner_id']): ?>
                    <table class="table table-bordered table-hover">
                        <tr>
                            <th style="width: 40%">Ad Soyad</th>
                            <td><?= htmlspecialchars($apartment['owner_name']) ?></td>
                        </tr>
                        <tr>
                            <th>E-posta</th>
                            <td><?= htmlspecialchars($apartment['owner_email']) ?></td>
                        </tr>
                        <tr>
                            <th>Telefon</th>
                            <td><?= htmlspecialchars($apartment['owner_phone']) ?></td>
                        </tr>
                    </table>
                    
                    <div class="action-buttons text-center">
                        <?php if ($isAdminOrManager): ?>
                        <a href="index.php?pages=apartmentsowner&id=<?= $apartment_id ?>" class="btn btn-primary btn-animated">
                            <i class="bi bi-pencil"></i> Daire Sahibini Düzenle
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($isAdmin && $ownerManager): ?>
                        <a href="index.php?pages=buildingsview&id=<?= $building_id ?>&action=login_as_owner&owner_id=<?= $apartment['owner_id'] ?>" 
                           class="btn btn-login-as btn-animated" 
                           onclick="return confirm('<?= htmlspecialchars($apartment['owner_name']) ?> kullanıcısı olarak giriş yapmak istediğinize emin misiniz?');">
                            <i class="bi bi-box-arrow-in-right"></i> Ev Sahibi Olarak Giriş Yap
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Bu daire için henüz bir daire sahibi tanımlanmamış.
                    </div>
                    
                    <?php if ($isAdminOrManager): ?>
                    <div class="text-center mt-3">
                        <a href="index.php?pages=apartmentsowner&id=<?= $apartment_id ?>" class="btn btn-primary btn-animated">
                            <i class="bi bi-plus-circle"></i> Daire Sahibi Ekle
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Son Aktiviteler -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">
                        <h6><i class="bi bi-clock-history"></i> Son Aktiviteler</h6>
                    </div>
                    
                    <div class="timeline">
                        <?php if (count($recentActivities) > 0): ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <div class="timeline-date">
                                            <?= date('d.m.Y H:i', strtotime($activity['date'])) ?>
                                        </div>
                                        <div class="timeline-text">
                                            <?php if ($activity['type'] == 'bill_payment'): ?>
                                                <strong><?= ucfirst($activity['bill_type']) ?></strong> ödemesi yapıldı - 
                                                <span class="text-success"><?= number_format($activity['amount'], 2) ?> ₺</span>
                                            <?php else: ?>
                                                Bakım talebi oluşturuldu - 
                                                <strong><?= htmlspecialchars($activity['bill_type']) ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Henüz aktivite bulunmuyor.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="apartmentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tenants-tab" data-bs-toggle="tab" data-bs-target="#tenants" type="button" role="tab" aria-controls="tenants" aria-selected="true">
                        <i class="bi bi-people"></i> Kiracılar
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="dues-tab" data-bs-toggle="tab" data-bs-target="#dues" type="button" role="tab" aria-controls="dues" aria-selected="false">
                        <i class="bi bi-cash-coin"></i> Aidatlar
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button" role="tab" aria-controls="fees" aria-selected="false">
                        <i class="bi bi-receipt"></i> Ücretler
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab" aria-controls="maintenance" aria-selected="false">
                        <i class="bi bi-tools"></i> Bakım Talepleri
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="apartmentTabsContent">
                <!-- Kiracılar Tab -->
                <div class="tab-pane fade show active" id="tenants" role="tabpanel" aria-labelledby="tenants-tab">
                    <div class="card tenant-card">
                        <div class="card-body">
                            <div class="section-title">
                                <h6><i class="bi bi-people"></i> Kiracı Bilgileri</h6>
                                <?php if ($isAdminOrManager): ?>
                                <a href="index.php?pages=apartmentstenantsadd&id=<?= $apartment_id ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Kiracı Ekle
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (count($tenants) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-custom">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ad Soyad</th>
                                            <th>E-posta</th>
                                            <th>Telefon</th>
                                            <th>Başlangıç</th>
                                            <th>Bitiş</th>
                                            <?php if ($isAdminOrManager): ?>
                                            <th style="width: 160px;">İşlemler</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tenants as $tenant): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-person-circle text-primary me-1"></i>
                                                <?= htmlspecialchars($tenant['full_name']) ?>
                                            </td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($tenant['email']) ?>">
                                                    <?= htmlspecialchars($tenant['email']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="tel:<?= htmlspecialchars($tenant['phone']) ?>">
                                                    <?= htmlspecialchars($tenant['phone']) ?>
                                                </a>
                                            </td>
                                            <td><?= date('d.m.Y', strtotime($tenant['start_date'])) ?></td>
                                            <td>
                                                <?php if ($tenant['end_date']): ?>
                                                    <?= date('d.m.Y', strtotime($tenant['end_date'])) ?>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Süresiz</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($isAdminOrManager): ?>
                                            <td class="text-center">
                                                <a href="index.php?pages=apartmentstenantsedit&id=<?= $tenant['id'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="index.php?pages=apartmentstenantsdelete&id=<?= $tenant['id'] ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Bu kiracıyı kaldırmak istediğinize emin misiniz?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                
                                                <?php if ($isAdmin): ?>
                                                <a href="index.php?pages=buildingsview&id=<?= $building_id ?>&action=login_as_tenant&tenant_id=<?= $tenant['tenant_id'] ?>" 
                                                   class="btn btn-sm btn-login-as" title="Kiracı Olarak Giriş Yap"
                                                   onclick="return confirm('<?= htmlspecialchars($tenant['full_name']) ?> kullanıcısı olarak giriş yapmak istediğinize emin misiniz?');">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Bu dairede şu anda aktif kiracı bulunmuyor.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Aidatlar Tab -->
                <div class="tab-pane fade" id="dues" role="tabpanel" aria-labelledby="dues-tab">
                    <div class="card dues-card">
                        <div class="card-body">
                            <div class="section-title">
                                <h6><i class="bi bi-cash-coin"></i> Aidat Ödemeleri</h6>
                                <?php if ($isAdminOrManager): ?>
                                <a href="index.php?pages=billsadd&apartment_id=<?= $apartment_id ?>&type=aidat" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Aidat Ekle
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (count($dues) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-custom">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Dönem</th>
                                            <th>Miktar</th>
                                            <th>Son Ödeme</th>
                                            <th>Durum</th>
                                            <th>Ödeme Tarihi</th>
                                            <?php if ($isAdminOrManager): ?>
                                            <th style="width: 120px;">İşlemler</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dues as $due): ?>
                                        <tr>
                                            <td><?= date('F Y', strtotime($due['due_date'])) ?></td>
                                            <td class="text-end"><?= number_format($due['share_amount'], 2) ?> ₺</td>
                                            <td><?= date('d.m.Y', strtotime($due['due_date'])) ?></td>
                                            <td>
                                                <?php if ($due['status'] == 'odendi'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Ödendi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-clock"></i> Beklemede
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $due['paid_at'] ? date('d.m.Y', strtotime($due['paid_at'])) : '-' ?></td>
                                            <?php if ($isAdminOrManager): ?>
                                            <td class="text-center">
                                                <?php if ($due['status'] != 'odendi'): ?>
                                                <a href="index.php?pages=billsmark&id=<?= $due['id'] ?>&status=odendi" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Öde
                                                </a>
                                                <?php else: ?>
                                                <a href="index.php?pages=billsmark&id=<?= $due['id'] ?>&status=beklemede" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-x-circle"></i> İptal
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Bu daire için henüz aidat kaydı bulunmuyor.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Ücretler Tab - FIXED -->
                <div class="tab-pane fade" id="fees" role="tabpanel" aria-labelledby="fees-tab">
                    <div class="card fees-card">
                        <div class="card-body">
                            <div class="section-title">
                                <h6><i class="bi bi-receipt"></i> Ücret Ödemeleri</h6>
                                <?php if ($isAdminOrManager): ?>
                                <a href="index.php?pages=billsadd&apartment_id=<?= $apartment_id ?>&type=ücret" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Ücret Ekle
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (count($fees) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-custom">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Dönem</th>
                                            <th>Miktar</th>
                                            <th>Son Ödeme</th>
                                            <th>Durum</th>
                                            <th>Ödeme Tarihi</th>
                                            <?php if ($isAdminOrManager): ?>
                                            <th style="width: 120px;">İşlemler</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fees as $fee): ?>
                                        <tr>
                                            <td><?= date('F Y', strtotime($fee['due_date'])) ?></td>
                                            <td class="text-end"><?= number_format($fee['share_amount'], 2) ?> ₺</td>
                                            <td><?= date('d.m.Y', strtotime($fee['due_date'])) ?></td>
                                            <td>
                                                <?php if ($fee['status'] == 'odendi'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Ödendi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-clock"></i> Beklemede
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $fee['paid_at'] ? date('d.m.Y', strtotime($fee['paid_at'])) : '-' ?></td>
                                            <?php if ($isAdminOrManager): ?>
                                            <td class="text-center">
                                                <?php if ($fee['status'] != 'odendi'): ?>
                                                <a href="index.php?pages=billsmark&id=<?= $fee['id'] ?>&status=odendi" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Öde
                                                </a>
                                                <?php else: ?>
                                                <a href="index.php?pages=billsmark&id=<?= $fee['id'] ?>&status=beklemede" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-x-circle"></i> İptal
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Bu daire için henüz ücret kaydı bulunmuyor.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Bakım Talepleri Tab -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel" aria-labelledby="maintenance-tab">
                    <div class="card maintenance-card">
                        <div class="card-body">
                            <div class="section-title">
                                <h6><i class="bi bi-tools"></i> Bakım/Onarım Talepleri</h6>
                                <a href="index.php?pages=maintenanceadd&apartment_id=<?= $apartment_id ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Yeni Talep
                                </a>
                            </div>
                            
                            <?php if (count($maintenanceRequests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-custom">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Konu</th>
                                            <th>Talep Eden</th>
                                            <th>Durum</th>
                                            <?php if ($isAdminOrManager): ?>
                                            <th style="width: 200px;">İşlemler</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenanceRequests as $request): ?>
                                        <tr>
                                            <td><?= date('d.m.Y', strtotime($request['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($request['title']) ?></td>
                                            <td><?= htmlspecialchars($request['requested_by']) ?></td>
                                            <td>
                                                <?php if ($request['status'] == 'beklemede'): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-clock"></i> Beklemede
                                                    </span>
                                                <?php elseif ($request['status'] == 'yapildi'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle"></i> Tamamlandı
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle"></i> İptal Edildi
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($isAdminOrManager): ?>
                                            <td class="text-center">
                                                <a href="index.php?pages=maintenanceview&id=<?= $request['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i> Görüntüle
                                                </a>
                                                <?php if ($request['status'] == 'beklemede'): ?>
                                                <a href="index.php?pages=maintenancestatus&id=<?= $request['id'] ?>&status=yapildi" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Tamamla
                                                </a>
                                                <a href="index.php?pages=maintenancestatus&id=<?= $request['id'] ?>&status=iptal" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-x-circle"></i> İptal
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Bu daire için henüz bakım/onarım talebi bulunmuyor.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- QR Kod Modal -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrCodeModalLabel">Daire QR Kodu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrcode"></div>
                <p class="mt-3">
                    QR kod ile daireye hızlı erişim sağlayabilirsiniz.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="downloadQR">
                    <i class="bi bi-download"></i> İndir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Bildirim Ekle (Toast) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toast bildirim oluşturma fonksiyonu
    function showToast(title, message, type = 'primary') {
        const toastContainer = document.querySelector('.toast-container');
        
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        toast.innerHTML = `
            <div class="custom-toast-header">
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
            <div class="custom-toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Toast'u göster
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // 5 saniye sonra kapat
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 500);
        }, 5000);
    }
    
    // Sayfa yüklendiğinde test bildirimi
    showToast('Hoş Geldiniz', 'Daire detay sayfası başarıyla yüklendi.');
    
    // QR Kod Oluşturma
    const showQRBtn = document.getElementById('showQRBtn');
    if (showQRBtn) {
        showQRBtn.addEventListener('click', function() {
            const qrModal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
            qrModal.show();
            
            // QR kod oluşturma buraya gelecek (normalde bir kütüphane kullanılır)
            const qrcode = document.getElementById('qrcode');
            qrcode.innerHTML = `
                <div style="width: 200px; height: 200px; margin: 0 auto; padding: 20px; background-color: white;">
                    <div style="width: 100%; height: 100%; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px dashed #dee2e6;">
                        <div style="text-align: center;">
                            <i class="bi bi-qr-code" style="font-size: 3rem;"></i>
                            <p style="margin-top: 10px;">QR Kod Görüntüsü</p>
                            <p style="font-size: 0.8rem;">Daire ID: <?= $apartment_id ?></p>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    // İşlem durumları için animasyonlu bildirim
    <?php if (isset($_SESSION['success'])): ?>
    showToast('Başarılı', '<?= $_SESSION['success'] ?>', 'success');
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    showToast('Hata', '<?= $_SESSION['error'] ?>', 'danger');
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
});
</script>