<?php
function restoreAdminSession($pdo) {
    if (isset($_SESSION['previous_user_id']) && isset($_SESSION['previous_role_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['previous_user_id']]);
        $previousUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($previousUser) {
            $_SESSION['user_id'] = $previousUser['id'];
            $_SESSION['full_name'] = $previousUser['full_name'];
            $_SESSION['email'] = $previousUser['email'];
            $_SESSION['role_id'] = $previousUser['role_id'];
            
            unset($_SESSION['previous_user_id']);
            unset($_SESSION['previous_role_id']);
            
            $_SESSION['success'] = "Yönetici oturumundan çıkış yapıldı. Admin hesabınıza döndünüz.";
            return true;
        }
    }
    
    return false;
}
if (isset($_GET['action']) && $_GET['action'] == 'restore_admin') {
    if (restoreAdminSession($pdo)) {
        header('Location: index.php?pages=buildings');
        exit;
    } else {
        $_SESSION['error'] = "Admin hesabına dönüş yapılamadı.";
        header('Location: index.php?pages=dashboard');
        exit;
    }
}