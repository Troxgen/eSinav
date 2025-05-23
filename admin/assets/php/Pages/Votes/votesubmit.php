<?php
require_once __DIR__ . '/../../Core/auth.php';
requireLogin();
require_once __DIR__ . '/../../Settings/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("❌ Geçersiz istek yöntemi.");
}

$user_id = $_SESSION['user_id'];
$building_id = $_SESSION['building_id']; // apartment_id değil artık!

$vote_id = $_POST['vote_id'] ?? null;
$option_id = $_POST['option_id'] ?? null;

if (!$vote_id || !$option_id) {
    die("❌ Eksik veri gönderildi.");
}

// 1. Oylama gerçekten kullanıcının binasına mı ait?
$stmt = $db->prepare("SELECT id FROM votes WHERE id = ? AND building_id = ?");
$stmt->execute([$vote_id, $building_id]);
if (!$stmt->fetch()) {
    die("❌ Bu oylamaya erişim yetkiniz yok.");
}

// 2. Seçenek gerçekten bu oylamaya mı ait?
$opt = $db->prepare("SELECT id FROM vote_options WHERE id = ? AND vote_id = ?");
$opt->execute([$option_id, $vote_id]);
if (!$opt->fetch()) {
    die("❌ Geçersiz seçenek.");
}

// 3. Daha önce oy kullanmış mı?
$dupe = $db->prepare("SELECT id FROM vote_responses WHERE vote_id = ? AND user_id = ?");
$dupe->execute([$vote_id, $user_id]);
if ($dupe->rowCount() > 0) {
    die("❗ Bu oylamaya zaten oy kullandınız.");
}

// 4. Oy kaydet
try {
    $stmt = $db->prepare("INSERT INTO vote_responses (vote_id, user_id, option_id) VALUES (?, ?, ?)");
    $stmt->execute([$vote_id, $user_id, $option_id]);

    header("Location: index.php?pages=voteresult&id=$vote_id&success=1");
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    die("❌ Veritabanı hatası: " . $e->getMessage());
}
