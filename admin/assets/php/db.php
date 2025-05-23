<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=e_sinav;charset=utf8", "root", "");
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}