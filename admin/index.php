<?php
ob_start(); // EN ÜSTTE!!!

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'assets/php/Settings/db.php';

$conn = new PDO("mysql:host=localhost;dbname=aparthub", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Apart Hub Admin Pages</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">


  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
  
<?php 
if (!empty($successMessage)) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo $successMessage;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

if (!empty($errorMessage)) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo $errorMessage;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}


include "assets/php/Template/navbar.php";


echo '<main id="main" class="main">';


$page = isset($_GET["pages"]) ? $_GET["pages"] : "anasayfa"; // Default sayfa anasayfa

// Sayfaların düzenlenmesi ve dahil edilmesi
switch ($page) {



  case "settings":
    include "assets/php/Pages/User Settings/settings.php";
    break;


  // USERS
  case "userlist":
    include "assets/php/Pages/Users/userlist.php";
    break;  case "userlistpdf":
      include "assets/php/Pages/Users/userlistpdf.php";
      break;
  case "usercreate":
    include "assets/php/Pages/Users/usercreate.php";
    break;
  case "useredit":
    include "assets/php/Pages/Users/useredit.php";
    break;
  case "userdelete":
    include "assets/php/Pages/Users/userdelete.php";
    break;


    case "manage_users":
      include "assets/php/Pages/Manage Users/manage_users.php";
      break;

  // ROLES
  case "rolelist":
    include "assets/php/Pages/Roles/rolelist.php";
    break;
  case "rolecreate":
    include "assets/php/Pages/Roles/rolecreate.php";
    break;
  case "roleedit":
    include "assets/php/Pages/Roles/roleedit.php";
    break;
  case "roledelete":
    include "assets/php/Pages/Roles/roledelete.php";
    break;

  // APARTMENTS
  case "apartmentlist":
    include "assets/php/Pages/Apartments/apartmentlist.php";
    break;
  case "apartmentcreate":
    include "assets/php/Pages/Apartments/apartmentcreate.php";
    break;
  case "apartmentedit":
    include "assets/php/Pages/Apartments/apartmentedit.php";
    break;
  case "apartmentdelete":
    include "assets/php/Pages/Apartments/apartmentdelete.php";
    break;
  // APARTMENTS
  case "buildinglist":
    include "assets/php/Pages/Buildings/buildingslist.php";
    break;
  case "apartmentcreate":
    include "assets/php/Pages/Buildings/buildingscreate.php";
    break;
  case "apartmentedit":
    include "assets/php/Pages/Buildings/buildingsedit.php";
    break;
  case "apartmentdelete":
    include "assets/php/Pages/Buildings/buildingsdelete.php";
    break;
      case "buildingsview":
    include "assets/php/Pages/Buildings/buildingsview.php";
    break;

  // RENTALS
  case "rentallist":
    include "assets/php/Pages/Rentals/rentallist.php";
    break;
  case "rentalcreate":
    include "assets/php/Pages/Rentals/rentalcreate.php";
    break;
  case "rentaledit":
    include "assets/php/Pages/Rentals/rentaledit.php";
    break;
  case "rentaldelete":
    include "assets/php/Pages/Rentals/rentaldelete.php";
    break;

  // AIDAT
  case "aidatlist":
    include "assets/php/Pages/Aidats/aidatlist.php";
    break;
  case "aidatcreate":
    include "assets/php/Pages/Aidats/aidatcreate.php";
    break;
  case "aidatedit":
    include "assets/php/Pages/Aidats/aidatedit.php";
    break;
  case "aidatdelete":
    include "assets/php/Pages/Aidats/aidatdelete.php";
    break;

  // BILLS
  case "billlist":
    include "assets/php/Pages/Bills/billlist.php";
    break;
  case "billcreate":
    include "assets/php/Pages/Bills/billcreate.php";
    break;
  case "billedit":
    include "assets/php/Pages/Bills/billedit.php";
    break;
  case "billdelete":
    include "assets/php/Pages/Bills/billdelete.php";
    break;
  case "bill_share":
    include "assets/php/Pages/Bills/bill_share.php"; // ⚡ Paylaştırma sayfası
    break;
  case "billsharelist":
    include "assets/php/Pages/Bills/bill_share_list.php"; // ⚡ Paylaştırılmış faturalar
    break;
  case "billshareupdate":
    include "assets/php/Pages/Bills/bill_share_update.php"; // ⚡ Paylaştırma güncelleme sayfası
    break;

 // VOTES
case "votelist":
  include "assets/php/Pages/Votes/votelist.php";
  break;

case "votecreate":
  include "assets/php/Pages/Votes/votecreate.php";
  break;

case "voteoptioncreate":
  include "assets/php/Pages/Votes/voteoptioncreate.php";
  break;

case "voterespond": // Kullanıcıların oy kullanacağı sayfa
  include "assets/php/Pages/Votes/voterespond.php";
  break;

case "vote_myvotes": // Kullanıcının verdiği oylar
  include "assets/php/Pages/Votes/vote_myvotes.php";
  break;

case "voteresult": // Oylama sonucu görüntüleme
  include "assets/php/Pages/Votes/voteresult.php";
  break;

case "vote_response_list": // Admin/yönetici oy yanıtlarını görür
  include "assets/php/Pages/Votes/vote_response_list.php";
  break;

case "votedelete":
  include "assets/php/Pages/Votes/votedelete.php";
  break;

case "voteedit":
  include "assets/php/Pages/Votes/voteedit.php";
  break;

case "votesubmit": // form action target olarak kullanılabilir
  include "assets/php/Pages/Votes/votesubmit.php";
  break;


  // ANNOUNCEMENTS
  case "announcementlist":
    include "assets/php/Pages/Announcements/announcementlist.php";
    break;
  case "announcementcreate":
    include "assets/php/Pages/Announcements/announcementcreate.php";
    break;
  case "announcementedit":
    include "assets/php/Pages/Announcements/announcementedit.php";
    break;
  case "announcementdelete":
    include "assets/php/Pages/Announcements/announcementdelete.php";
    break;
  case "announcementread":
    include "assets/php/Pages/Announcements/announcementread.php";
    break;
    

  // MAINTENANCE REQUESTS
  case "maintenancelist":
    include "assets/php/Pages/Maintenances/maintenance_requests_list.php";
    break;
  case "maintenancecreate":
    include "assets/php/Pages/Maintenances/maintenance_requests_create.php";
    break;
  case "maintenanceedit":
    include "assets/php/Pages/Maintenances/maintenance_requests_edit.php";
    break;
  case "maintenancedelete":
    include "assets/php/Pages/Maintenances/maintenance_requests_delete.php";
    break;

  // DASHBOARD & STATIC
  case "anasayfa":
    include "assets/php/Pages/Dashboard/anasayfa.php";
    break;
  case "kvkk":
    include "assets/php/Pages/kvkk.php";
    break;
  case "iletisim":
    include "assets/php/Pages/iletisim.php";
    break;
  case "logout":
    include "assets/php/Pages/logout.php";
    break;

case "admin_restore":
    include "assets/php/Pages/Buildings/admin_restore.php";
    break;

    

  // EĞER SAYFA BULUNAMAZSA
  default:
    include "assets/php/Pages/Dashboard/anasayfa.php";
    break;
}


echo '</main>'; // doğru kapanış

include "assets/php/Template/footer.php";
?>


  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
</body>

</html>
<?php ob_end_flush(); ?>
