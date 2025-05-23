<?php
// Core/auth.php için güncellenmiş fonksiyonlar - SORUN GİDERME

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa login sayfasına yönlendir
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=unauthorized");
        exit;
    }
}

// Belirli rol ID'lerine izin verir (örnek: [1, 2, 3])
function requireRole(array $allowedRoleIds = []) {
    // Rol kontrolü yap, eğer izin verilen roller boşsa tüm rollere izin ver
    if (!empty($allowedRoleIds)) {
        if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], $allowedRoleIds)) {
            // Eğer admin olarak yönetici hesabındaysak, admin hesabına dönme seçeneği sunan bir hata göster
            if (isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id'])) {
                $_SESSION['error'] = "Bu sayfaya erişim yetkiniz yok. Admin hesabınıza geri dönebilirsiniz.";
                header("Location: index.php?pages=dashboard");
                exit;
            }
            
            // Normal yetkisiz erişim hatası
            header("Location: index.php?error=unauthorized_role");
            exit;
        }
    }
}

// Belirli bir rol seviyesinden **daha düşük** olanlara izin verir
function requireRoleLevelBelow($maxLevel) {
    if (!isset($_SESSION['role_level']) || $_SESSION['role_level'] > $maxLevel) {
        // Eğer admin olarak yönetici hesabındaysak, admin hesabına dönme seçeneği sunan bir hata göster
        if (isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id'])) {
            $_SESSION['error'] = "Bu sayfaya erişim yetkiniz yok. Admin hesabınıza geri dönebilirsiniz.";
            header("Location: index.php?pages=dashboard");
            exit;
        }
        
        header("Location: index.php?error=unauthorized_level");
        exit;
    }
}

// Belirli bir rol seviyesinden **daha yüksek** olanlara izin verir
function requireRoleLevelAbove($minLevel) {
    if (!isset($_SESSION['role_level']) || $_SESSION['role_level'] < $minLevel) {
        // Eğer admin olarak yönetici hesabındaysak, admin hesabına dönme seçeneği sunan bir hata göster
        if (isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id'])) {
            $_SESSION['error'] = "Bu sayfaya erişim yetkiniz yok. Admin hesabınıza geri dönebilirsiniz.";
            header("Location: index.php?pages=dashboard");
            exit;
        }
        
        header("Location: index.php?error=unauthorized_level");
        exit;
    }
}

// Kullanıcıya daire atanmış mı kontrolü
function requireApartment() {
    if (!isset($_SESSION['apartment_id'])) {
        echo "<div style='padding: 20px; background: #ffdddd; color: red; text-align: center;'>
        ❌ Bu sayfaya erişmek için lütfen bir daire (apartment) seçiniz.
        </div>";
        exit;
    }
}

// Kullanıcıya bina atanmış mı kontrolü
function requireBuilding() {
    if (!isset($_SESSION['building_id'])) {
        header("Location: index.php?error=missing_building");
        exit;
    }
}

// Yönetici modunda mı kontrol et (admin hesabı olarak yönetici hesabında)
function isInManagerMode() {
    return isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id']);
}

// Admin hesabına dönme işlemi
function restoreAdminSession($pdo) {
    // Önceki admin oturumu var mı kontrol et
    if (isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id'])) {
        try {
            // Veritabanından kullanıcı bilgilerini al
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['previous_user_id']]);
            $previousUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($previousUser) {
                // Önceki kullanıcı bilgilerini geçici olarak sakla
                $prevUserId = $_SESSION['previous_user_id'];
                $prevRoleId = $_SESSION['previous_role_id'];
                $prevBuildingId = $_SESSION['previous_building_id'] ?? null;
                
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
                } else if (isset($previousUser['building_id']) && $previousUser['building_id']) {
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
                
                return true;
            }
        } catch (PDOException $e) {
            // Hata mesajını logla
            error_log("PDO hatası: " . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

// Debug bilgisi göster (sadece geliştirme ortamında kullanın)
function debugSession() {
    echo "<div style='background: #f1f1f1; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Oturum Bilgileri (Debug)</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
}