<?php
require_once __DIR__ . '/../Settings/db.php';
require_once __DIR__ . '/../Core/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inManagerMode = isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id']);



$userId = $_SESSION['user_id'] ?? null;

// Kullanıcının okumadığı son 4 duyuruyu al
$stmt = $db->prepare("
    SELECT a.id, a.title 
    FROM announcements a 
    LEFT JOIN announcement_reads r 
           ON r.announcement_id = a.id 
          AND r.user_id = ? 
    WHERE r.id IS NULL 
    ORDER BY a.created_at DESC 
    LIMIT 4
");
$stmt->execute([$userId]);
$unreadAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);
$newAnnouncementCount = count($unreadAnnouncements);

// Kullanıcı bilgisi ve rolü alınıyor
$userQuery = $db->prepare("
    SELECT u.full_name, r.name AS role_name, r.id AS role_id
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");
$userQuery->execute([$userId]);
$userInfo = $userQuery->fetch(PDO::FETCH_ASSOC);

$_SESSION['user_role'] = $userInfo['role_name'] ?? '';
$_SESSION['role_id'] = $userInfo['role_id'] ?? 0;

// hasRole fonksiyonu yoksa tanımla
if (!function_exists('hasRole')) {
    function hasRole($roles) {
        return isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], $roles);
    }
}

// Geçerli sayfa parametresi
$currentPage = $_GET['pages'] ?? null;
?>

<header id="header" class="header fixed-top d-flex align-items-center">
  <!-- Logo ve Sidebar Tetik Butonu -->
  <div class="d-flex align-items-center justify-content-between">
    <a href="index.php" class="logo d-flex align-items-center">
      <img src="assets/img/logo.png" alt="Logo" style="height:40px;">
      <span class="d-none d-lg-block ms-2">ApartHub</span>
    </a>
  </div>

  <!-- Üst Navbar (sağ taraf) -->
  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <!-- Arama ikonu (mobil) -->
      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle" href="#">
          <i class="bi bi-search"></i>
        </a>
      </li>

      <!-- Duyurular (Bildirim) Menüsü -->
      <li class="nav-item dropdown">
        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown">
          <i class="bi bi-bell"></i>
          <!-- Okunmamış duyuru sayısı -->
          <span class="badge bg-primary badge-number"><?= $newAnnouncementCount ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
          <li class="dropdown-header">
            <?= $newAnnouncementCount ?> yeni duyuru var
          </li>
          <li><hr class="dropdown-divider"></li>

          <?php if ($newAnnouncementCount > 0): ?>
            <?php foreach ($unreadAnnouncements as $announcement): ?>
              <li class="notification-item">
                <i class="bi bi-megaphone-fill text-info"></i>
                <div>
                  <h4><?= htmlspecialchars($announcement['title']) ?></h4>
                  <a href="index.php?pages=announcementread&id=<?= $announcement['id'] ?>" class="small text-muted">Okundu olarak işaretle</a>
                </div>
              </li>
              <li><hr class="dropdown-divider"></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="notification-item text-muted p-2">
              <i class="bi bi-megaphone-fill text-info"></i> Yeni duyuru bulunmamaktadır.
            </li>
            <li><hr class="dropdown-divider"></li>
          <?php endif; ?>

          <!-- Tüm duyuruları göster linki -->
          <li class="dropdown-footer">
            <a href="index.php?pages=announcementlist" class="text-primary fw-bold">Eski Duyuruları Göster</a>
          </li>
        </ul>
      </li>
      <!-- Duyurular Menüsü Son -->

      <!-- Profil Menüsü -->
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <!-- Opsiyonel profil resmi -->
          <img src="assets/img/logo.png" alt="Profile" class="rounded-circle" style="height:36px;">
          <span class="d-none d-md-block dropdown-toggle ps-2">
            <?= htmlspecialchars($userInfo['full_name'] ?? 'Kullanıcı') ?>
          </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header text-start">
            <h6><?= htmlspecialchars($userInfo['full_name'] ?? 'Kullanıcı') ?></h6>
            <span><?= htmlspecialchars($userInfo['role_name'] ?? '') ?></span>
          </li>
          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Çıkış Yap</span>
              </a>
              <?php if ($inManagerMode): ?>
                <br>
              <a class="dropdown-item d-flex align-items-center" href="index.php?pages=admin_restore&action=restore_admin">
              <span>Admine Geri Dön</span>
              </a>
              <?php endif; ?>

            
          </li>
        </ul>
      </li>
      <!-- Profil Menüsü Son -->

    </ul>
  </nav>
</header>

<?php
$currentPage = $_GET['pages'] ?? null; // Mevcut sayfa, varsayılan olarak null
?>

<aside id="sidebar" class="sidebar">

<ul class="sidebar-nav" id="sidebar-nav">
<li class="nav-item ">
    <a class="nav-link active "href="index.php">
      <i class="bi bi-house-fill"></i>
      <span>Başlangıç</span>
    </a>
    <?php if (in_array($_SESSION['role_id'], [5])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['userlist', 'usercreate', 'manage_users']) ? 'active' : 'collapsed' ?>" data-bs-target="#Users-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-people-fill"></i><span>Kullanıcılar</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Users-nav" class="nav-content collapse <?= in_array($currentPage, ['userlist', 'usercreate', 'manage_users']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=userlist" class="<?= $currentPage === 'userlist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <li><a href="index.php?pages=usercreate" class="<?= $currentPage === 'usercreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Ekle</span></a></li>
      <li><a href="index.php?pages=manage_users" class="<?= $currentPage === 'manage_users' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Kullanıcı Onayla</span></a></li>
    </ul>
  </li>
<?php endif; ?>

<?php if (in_array($_SESSION['role_id'], [5])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['buildinglist', 'buildingcreate']) ? 'active' : 'collapsed' ?>" data-bs-target="#Apartman-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-buildings-fill"></i><span>Apartmanlar</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Apartman-nav" class="nav-content collapse <?= in_array($currentPage, ['buildinglist', 'buildingcreate']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=buildinglist" class="<?= $currentPage === 'buildinglist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <li><a href="index.php?pages=buildingcreate" class="<?= $currentPage === 'buildingcreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Ekle</span></a></li>
    </ul>
  </li>
<?php endif; ?>



  <?php if (in_array($_SESSION['role_id'], [5])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['rolelist', 'rolecreate']) ? 'active' : 'collapsed' ?>" data-bs-target="#Roles-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-award-fill"></i><span>Roller</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Roles-nav" class="nav-content collapse <?= in_array($currentPage, ['rolelist', 'rolecreate']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=rolelist" class="<?= $currentPage === 'rolelist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <li><a href="index.php?pages=rolecreate" class="<?= $currentPage === 'rolecreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Ekle</span></a></li>
    </ul>
  </li>
  <?php endif; ?>

  <?php if (in_array($_SESSION['role_id'], [4,3])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['apartmentlist', 'apartmentcreate']) ? 'active' : 'collapsed' ?>" data-bs-target="#Apartments-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-building"></i><span>Daireler</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Apartments-nav" class="nav-content collapse <?= in_array($currentPage, ['apartmentlist', 'apartmentcreate']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=apartmentlist" class="<?= $currentPage === 'apartmentlist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <li><a href="index.php?pages=apartmentcreate" class="<?= $currentPage === 'apartmentcreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Ekle</span></a></li>
    </ul>
  </li>
  <?php endif; ?>

  <?php if (in_array($_SESSION['role_id'], [4,3,2,1])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['aidatlist', 'aidatcreate']) ? 'active' : 'collapsed' ?>" data-bs-target="#Aidat-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-cash-stack"></i><span>Aidatlar</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Aidat-nav" class="nav-content collapse <?= in_array($currentPage, ['aidatlist', 'aidatcreate']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=aidatlist" class="<?= $currentPage === 'aidatlist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <?php if (in_array($_SESSION['role_id'], [4])): ?>
      <li><a href="index.php?pages=aidatcreate" class="<?= $currentPage === 'aidatcreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Aidat</span></a></li>
      <?php endif; ?>

    </ul>
  </li>
  <?php endif; ?>

  <!-- Faturalar (admin, yönetici) -->
  <?php if (in_array($_SESSION['role_id'], [4])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['billlist', 'billcreate', 'billsharelist']) ? 'active' : 'collapsed' ?>" data-bs-target="#Bills-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-receipt"></i><span>Faturalar</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Bills-nav" class="nav-content collapse <?= in_array($currentPage, ['billlist', 'billcreate', 'billsharelist']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=billlist" class="<?= $currentPage === 'billlist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <li><a href="index.php?pages=billcreate" class="<?= $currentPage === 'billcreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Fatura</span></a></li>
      <li><a href="index.php?pages=billsharelist" class="<?= $currentPage === 'billsharelist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Paylaşımlar</span></a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- Oylamalar (admin, yönetici, ev sahibi, kiracı) -->
  <?php if (in_array($_SESSION['role_id'], [4,3,2])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['votelist', 'votecreate', 'vote_response_list']) ? 'active' : 'collapsed' ?>" data-bs-target="#Votes-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-bar-chart-fill"></i><span>Oylamalar</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Votes-nav" class="nav-content collapse <?= in_array($currentPage, [
  'votelist',
  'votecreate',
  'voteoptioncreate',
  'voterespond',
  'vote_myvotes',
  'voteresult',
  'vote_response_list'
]) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">

  <li><a href="index.php?pages=votelist" class="<?= $currentPage === 'votelist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Oylama Listesi</span></a></li>

  <?php if (in_array($_SESSION['role_id'], [4])): ?>
    <li><a href="index.php?pages=votecreate" class="<?= $currentPage === 'votecreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Oylama Başlat</span></a></li>
    <li><a href="index.php?pages=voteoptioncreate" class="<?= $currentPage === 'voteoptioncreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Seçenek Ekle</span></a></li>
    <li><a href="index.php?pages=vote_response_list" class="<?= $currentPage === 'vote_response_list' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Oy Cevapları</span></a></li>
  <?php endif; ?>

  <?php if (in_array($_SESSION['role_id'], [4,3,2])): ?>
    <li><a href="index.php?pages=voterespond" class="<?= $currentPage === 'voterespond' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Oy Kullan</span></a></li>
    <li><a href="index.php?pages=vote_myvotes" class="<?= $currentPage === 'vote_myvotes' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Oylarım</span></a></li>
    <li><a href="index.php?pages=voteresult" class="<?= $currentPage === 'voteresult' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Sonuçlar</span></a></li>
  <?php endif; ?>

</ul>

  </li>
  <?php endif; ?>

  <!-- Duyurular (admin, yönetici) -->
  <?php if (in_array($_SESSION['role_id'], [4])): ?>

  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['announcementlist', 'announcementcreate']) ? 'active' : 'collapsed' ?>" data-bs-target="#Announcements-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-megaphone-fill"></i><span>Duyurular</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Announcements-nav" class="nav-content collapse <?= in_array($currentPage, ['announcementlist', 'announcementcreate']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=announcementlist" class="<?= $currentPage === 'announcementlist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <?php if (in_array($_SESSION['role_id'], [4])): ?>
        <li><a href="index.php?pages=announcementcreate" class="<?= $currentPage === 'announcementcreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Ekle</span></a></li>
      <?php endif; ?>
    </ul>
  </li>
  <?php endif; ?>

  <!-- Bakım Talepleri (admin, yönetici, ev sahibi, kiracı) -->
  <?php if (in_array($_SESSION['role_id'], [4,3,2])): ?>
  <li class="nav-item">
    <a class="nav-link <?= in_array($currentPage, ['maintenancelist', 'maintenancecreate']) ? 'active' : 'collapsed' ?>" data-bs-target="#Maintenance-nav" data-bs-toggle="collapse" href="#">
      <i class="bi bi-tools"></i><span>Bakım & Onarım</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="Maintenance-nav" class="nav-content collapse <?= in_array($currentPage, ['maintenancelist', 'maintenancecreate']) ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
      <li><a href="index.php?pages=maintenancelist" class="<?= $currentPage === 'maintenancelist' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Liste</span></a></li>
      <li><a href="index.php?pages=maintenancecreate" class="<?= $currentPage === 'maintenancecreate' ? 'active' : '' ?>"><i class="bi bi-circle"></i><span>Yeni Talep</span></a></li>
    </ul>
  </li>
  <?php endif; ?>

  <!-- Ayarlar (admin) -->
  <?php if (in_array($_SESSION['role_id'], [5])): ?>
  <li class="nav-item">
    <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="index.php?pages=settings">
      <i class="bi bi-gear-fill"></i>
      <span>Ayarlar</span>
    </a>
  </li>
  <?php endif; ?>

  <li class="nav-item">
    <a class="nav-link <?= $currentPage === 'logout' ? 'active' : '' ?>" href="logout.php">
      <i class="bi bi-box-arrow-right"></i>
      <span>Çıkış Yap</span>
    </a>
  </li>
</ul>

</aside>