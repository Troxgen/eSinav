<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5]); // Sadece admin

require_once __DIR__ . '/../../Settings/db.php';

$roleId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
$currentLevel = $_SESSION['user_role_level'] ?? 99;

if (!$roleId) {
  header("Location: index.php?pages=rolelist&status=invalid");
  exit;
}

try {
  // Rol bilgisi getir
  $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
  $stmt->execute([$roleId]);
  $role = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$role) {
    header("Location: index.php?pages=rolelist&status=notfound");
    exit;
  }

  // Yetki seviyesi kontrolü
  if ((int) $role['level'] >= $currentLevel) {
    header("Location: index.php?pages=rolelist&status=unauthorized");
    exit;
  }

  // Kullanıcı varsa rol silinemez
  $checkUsers = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
  $checkUsers->execute([$roleId]);
  $userCount = $checkUsers->fetchColumn();

  if ($userCount > 0) {
    header("Location: index.php?pages=rolelist&status=assigned");
    exit;
  }

  // Silme işlemi
  $delete = $db->prepare("DELETE FROM roles WHERE id = ?");
  $delete->execute([$roleId]);

  header("Location: index.php?pages=rolelist&status=deleted");
  exit;

} catch (PDOException $e) {
  header("Location: index.php?pages=rolelist&status=dberror");
  exit;
}
