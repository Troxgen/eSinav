<?php
session_start();
include "assets/php/db.php";

if (!isset($_SESSION['kullanici_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $soru = $_POST['soru'];
    $a = $_POST['a'];
    $b = $_POST['b'];
    $c = $_POST['c'];
    $d = $_POST['d'];
    $dogru = $_POST['dogru'];

    $stmt = $db->prepare("INSERT INTO sorular (ogretmen_id, soru_metni, secenek_a, secenek_b, secenek_c, secenek_d, dogru_secenek) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['kullanici_id'], $soru, $a, $b, $c, $d, $dogru]);

    echo "Soru eklendi!";
}
?>

<form method="POST">
    Soru: <input type="text" name="soru"><br>
    A: <input type="text" name="a"><br>
    B: <input type="text" name="b"><br>
    C: <input type="text" name="c"><br>
    D: <input type="text" name="d"><br>
    Doğru Şık: <select name="dogru">
        <option value="a">A</option>
        <option value="b">B</option>
        <option value="c">C</option>
        <option value="d">D</option>
    </select><br>
    <button type="submit">Kaydet</button>
</form>