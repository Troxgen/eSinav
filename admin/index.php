<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mazer Admin Dashboard</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.5.5/css/perfect-scrollbar.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">
</head>

<body class="index-page">

<?php
$page = $_GET['page'] ?? 'anasayfa';
$rol = $_SESSION['rol'] ?? 'ogrenci';

// İzinli sayfalar, page + rol kombinasyonu
$izinli_sayfalar = [
    'anasayfa' => ['admin', 'ogretmen', 'ogrenci'],
    'userlist' => ['admin'],
    // diğerleri...
];

// navbar include
switch ($rol) {
    case 'admin':
        include("assets/php/template/admin-navbar.php");
        break;
    case 'ogretmen':
        include("assets/php/template/ogretmen-navbar.php");
        break;
    case 'ogrenci':
    default:
        include("assets/php/template/ogrenci-navbar.php");
        break;
}

// sayfa kontrol
if (!isset($izinli_sayfalar[$page])) {
    die("Sayfa bulunamadı 🥲");
}

if (!in_array($rol, $izinli_sayfalar[$page])) {
    die("İzinsiz giriş 🤬 Rol: $rol");
}

// Dinamik sayfa yükleme (anasayfa rol bazlı)
if ($page === 'anasayfa') {
    switch ($rol) {
        case 'admin':
            include "assets/php/pages/admin/admin-anasayfa.php";
            break;
        case 'ogretmen':
            include "assets/php/Pages/Ogretmen/ogretmen-anasayfa.php";
            break;
        case 'ogrenci':
        default:
            include "assets/php/Pages/Ogrenci/ogrenci-anasayfa.php";
            break;
    }
} else {
    // Diğer sayfalar normal switch
    switch ($page) {
        case "anasayfa":
            include "assets/php/pages/admin/admin-anasayfa.php";
            break;
        case "ogretmenler":
            include "assets/php/pages/admin/ogretmenler.php";
            break;
        case "ogrenciler":
            include "assets/php/pages/admin/ogrenciler.php";
            break;
        case "siniflar":
            include "assets/php/pages/admin/siniflar.php";
            break;
        default:
            die("Hadi oradan, böyle sayfa mı olur?! 🤡");
    }
}

?>


<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
</a>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.5.5/perfect-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="assets/js/pages/dashboard.js"></script>
<script src="assets/js/main.js"></script>

</body>
</html>
