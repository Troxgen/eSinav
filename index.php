
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

</head>

<body class="index-page">

<?php

include "assets/php/Template/navbar.php";
if(isset($_GET["pages"])){
  $page = $_GET["pages"];
  if($page == "anasayfa"){
    include "assets/php/Pages/anasayfa.php";
  }else if($page == "hakkimizda"){
    include "assets/php/Pages/hakkimizda.php";
  }else if($page == "kurumsal"){
    include "assets/php/Pages/kurumsal.php";
  }else if($page == "kvkk"){
    include "assets/php/Pages/kvkk.php";
  }else if($page == "iletisim"){
    include "assets/php/Pages/iletisim.php";
  }else{
    include "home.php";
  }
}else{
  include "assets/php/Pages/anasayfa.php";
}
include "assets/php/Template/footer.php";
?>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script src="assets/js/main.js"></script>

    <script src="assets/js/main.js"></script>

</body>

</html>