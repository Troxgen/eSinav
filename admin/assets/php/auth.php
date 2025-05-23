<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa login sayfasına yönlendir
function requireLogin() {
    if (!isset($_SESSION['kullanici_id'])) {
        header("Location: login.php?error=unauthorized");
        exit;
    }
}

// Belirli rollerin erişimine izin ver (örnek: ['admin', 'ogretmen'])
function requireRole(array $allowedRoles = []) {
    if (!empty($allowedRoles)) {
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowedRoles)) {
            $_SESSION['error'] = "Bu sayfaya erişim yetkiniz yok.";
            header("Location: index.php");
            exit;
        }
    }
}

// Giriş yapan kişinin rolü bu mu?
function isRole($roleName) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $roleName;
}

// Admin mi?
function isAdmin() {
    return isRole('admin');
}

// Oturumu göster (debug için)
function debugSession() {
    echo "<div style='background: #f1f1f1; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h3>Oturum Bilgileri (Debug)</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    echo "</div>";
}
