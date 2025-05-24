
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="utf-8">
    <title></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Free HTML Templates" name="keywords">
    <meta content="Free HTML Templates" name="description">
    <!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jost:wght@500;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Easing, Waypoints, CounterUp (Opsiyonel efektler için) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"></script>

<!-- Owl Carousel JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

<!-- Custom JS -->
<script src="assets/js/main.js"></script>

</body>
</html>