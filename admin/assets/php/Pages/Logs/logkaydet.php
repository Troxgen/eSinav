<?php
require_once __DIR__ . '/../../Settings/db.php';

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

function logLogin($user_id) {
    global $pdo; // ✅ Güncel PDO nesnesi

    $ip_address = getClientIP();
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Bilinmiyor', 0, 255);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, ip_address, user_agent)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $ip_address, $user_agent]);
    } catch (PDOException $e) {
        error_log("🔴 Giriş log kaydı hatası: " . $e->getMessage());
    }
}
