<?php
session_start();
include "assets/php/db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $etiket = $_POST['etiket'];
    $stmt = $db->prepare("INSERT INTO etiketler (etiket_adi) VALUES (?)");
    $stmt->execute([$etiket]);
    echo "Etiket eklendi!";
}
?>

<form method="POST">
    Etiket Adı: <input type="text" name="etiket"><br>
    <button type="submit">Ekle</button>
</form>