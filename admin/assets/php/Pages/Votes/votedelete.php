<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
requireRole([5,4]);
require_once __DIR__ . '/../../Settings/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: index.php?pages=votelist&status=invalid");
    exit;
}

$vote_id = (int)$id;
$building_id = $_SESSION['building_id'];
$user_level = $_SESSION['user_role_level'];

try {
    // Oylamanın gerçekten bu binaya ait olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT * FROM votes WHERE id = ? AND building_id = ?");
    $stmt->execute([$vote_id, $building_id]);
    $vote = $stmt->fetch();

    if (!$vote) {
        header("Location: index.php?pages=votelist&status=unauthorized");
        exit;
    }

    // Tüm ilişkili verileri sil
    $db->beginTransaction();
    $db->prepare("DELETE FROM vote_responses WHERE vote_id = ?")->execute([$vote_id]);
    $db->prepare("DELETE FROM vote_options WHERE vote_id = ?")->execute([$vote_id]);
    $db->prepare("DELETE FROM votes WHERE id = ?")->execute([$vote_id]);
    $db->commit();

    header("Location: index.php?pages=votelist&status=deleted");
    exit;

} catch (PDOException $e) {
    $db->rollBack();
    header("Location: index.php?pages=votelist&status=dberror");
    exit;
}
