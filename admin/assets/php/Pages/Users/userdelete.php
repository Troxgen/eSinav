<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5, 4]); // Sadece admin ve yönetici kullanıcı silebilir
require_once __DIR__ . '/../../Settings/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?pages=userlist&status=invalid");
    exit;
}

$id = (int) $_GET['id'];
$myRoleLevel = $_SESSION['user_role_level'] ?? 99;
$myApartment = $_SESSION['active_apartment_id'];

try {
    // Kullanıcı bilgilerini çek
    $stmt = $db->prepare("SELECT u.id, u.role_id, u.apartment_id, r.level 
                          FROM users u
                          LEFT JOIN roles r ON u.role_id = r.id
                          WHERE u.id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: index.php?pages=userlist&status=notfound");
        exit;
    }

    // Yetki kontrolü: kendi seviyenden yüksek/kendi seviyendeki kullanıcıyı silemezsin
    if ((int)$user['level'] >= $myRoleLevel) {
        header("Location: index.php?pages=userlist&status=unauthorized");
        exit;
    }

    // Başka apartmana ait kullanıcıyı silemezsin
    if ($user['apartment_id'] != $myApartment) {
        header("Location: index.php?pages=userlist&status=denied");
        exit;
    }

    // Sil
    $del = $db->prepare("DELETE FROM users WHERE id = ?");
    $del->execute([$id]);

    header("Location: index.php?pages=userlist&status=deleted");
    exit;

} catch (PDOException $e) {
    header("Location: index.php?pages=userlist&status=dberror");
    exit;
}
