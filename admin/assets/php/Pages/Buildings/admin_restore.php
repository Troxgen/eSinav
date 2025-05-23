<?php
// Pages/admin_restore.php - DÜZELTİLMİŞ SÜRÜM

// Hataları göster
ini_set('display_errors', 1); 
error_reporting(E_ALL);

require_once __DIR__ . '/../../Core/auth.php';
requireLogin();

require_once __DIR__ . '/../../Settings/db.php';

// Admin geri yükleme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'restore_admin') {
    if (isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id'])) {
        // Önceki kullanıcı bilgilerini geçici olarak sakla
        $prevUserId = $_SESSION['previous_user_id'];
        $prevRoleId = $_SESSION['previous_role_id'];
        $prevBuildingId = $_SESSION['previous_building_id'] ?? null;
        
        // Veritabanından kullanıcı bilgilerini al
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$prevUserId]);
        $previousUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($previousUser) {
            // Oturumu yeniden başlat (güvenlik için)
            session_regenerate_id(true);
            
            // Admin bilgilerini oturuma geri yükle
            $_SESSION['user_id'] = $previousUser['id'];
            $_SESSION['full_name'] = $previousUser['full_name'];
            $_SESSION['email'] = $previousUser['email'];
            $_SESSION['role_id'] = $previousUser['role_id'];
            
            // Rol seviyesini de geri yükle
            $roleStmt = $pdo->prepare("SELECT level FROM roles WHERE id = ?");
            $roleStmt->execute([$previousUser['role_id']]);
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
            if ($role) {
                $_SESSION['role_level'] = $role['level'];
            }
            
            // ÖNEMLİ: Daha önce seçilmiş olan bina ID'sini geri yükle
            if ($prevBuildingId) {
                $_SESSION['building_id'] = $prevBuildingId;
            } else if ($previousUser['building_id']) {
                $_SESSION['building_id'] = $previousUser['building_id'];
            }
            
            // Önceki kullanıcı değişkenlerini temizle
            unset($_SESSION['previous_user_id']);
            unset($_SESSION['previous_role_id']);
            unset($_SESSION['previous_building_id']);
            
            // Bu girişi logla
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $loginLogStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
            $loginLogStmt->execute([$previousUser['id'], $ip, $user_agent]);
            
            $_SESSION['success'] = "Admin hesabınıza başarıyla geri döndünüz.";
            
            // Buildings sayfasına yönlendir
            header('Location: index.php?pages=dashboard');
            exit;
        } else {
            $_SESSION['error'] = "Önceki kullanıcı bulunamadı.";
        }
    } else {
        $_SESSION['error'] = "Önceki oturum bilgisi bulunamadı.";
    }
    
    // Bir sorun olduysa buildings sayfasına yönlendir
    header('Location: index.php?pages=buildings');
    exit;
} else {
    // Normal durumda buildings sayfasına yönlendir
    header('Location: index.php?pages=buildings');
    exit;
}