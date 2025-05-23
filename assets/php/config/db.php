<?php
$host = 'localhost';
$dbname = 'aparthub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo = $db; // 👈 Bu satır kritik! Hem $db hem $pdo kullanılabilsin
} catch (PDOException $e) {
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}
?>