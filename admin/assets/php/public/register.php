<?php
require_once __DIR__ . '/../Settings/db.php'; // Veritabanı bağlantısını ekleyelim

$errorMessage = "";
$successMessage = "";

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apartmentAction = $_POST['apartment_action'] ?? '';
    if ($apartmentAction === 'new') {
        // Yeni apartman oluşturulacaksa
        $apartmentName = $_POST['new_apartment_name'] ?? '';
        $apartmentAddress = $_POST['new_apartment_address'] ?? '';

        if (empty($apartmentName) || empty($apartmentAddress)) {
            $errorMessage = "Yeni apartman adı ve adresi gereklidir!";
        } else {
            // Yeni apartmanı veritabanına ekle
            try {
                $stmt = $pdo->prepare("INSERT INTO apartments (name, address) VALUES (?, ?)");
                $stmt->execute([$apartmentName, $apartmentAddress]);
                $successMessage = "Yeni apartman başarıyla oluşturuldu.";
            } catch (PDOException $e) {
                $errorMessage = "Veritabanına kaydedilirken bir hata oluştu: " . $e->getMessage();
            }
        }

    } elseif ($apartmentAction === 'existing') {
        // Mevcut apartman seçilecekse
        $existingApartmentId = $_POST['existing_apartment_id'] ?? '';

        if (empty($existingApartmentId)) {
            $errorMessage = 'Lütfen bir apartman seçin!';
        } else {
            // Mevcut apartman ile ilgili işlemler
            $successMessage = 'Mevcut apartman başarıyla seçildi.';
        }
    } else {
        $errorMessage = 'Lütfen bir seçenek seçin.';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Formu</title>
    <link rel="stylesheet" href="styles.css"> <!-- CSS dosyasını ekleyelim -->
</head>
<body>

<div class="container">
    <h2>Yeni Kullanıcı Kayıt Formu</h2>

    <!-- Hata ve başarı mesajlarını gösterelim -->
    <?php if ($errorMessage): ?>
        <div class="error"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    <?php if ($successMessage): ?>
        <div class="success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <!-- Form Başlangıcı -->
    <form action="register.php" method="POST">
        <label for="apartment_select">Bir apartman seçin ya da yeni oluşturun:</label>
        <select id="apartment_select" name="apartment_action" required>
            <option value="new">Yeni Apartman Oluştur</option>
            <option value="existing">Mevcut Apartman Seç</option>
        </select>

        <!-- Yeni apartman oluşturma alanları -->
        <div id="new_apartment_fields" style="display:none;">
            <label for="new_apartment_name">Yeni Apartman Adı:</label>
            <input type="text" id="new_apartment_name" name="new_apartment_name" required>

            <label for="new_apartment_address">Yeni Apartman Adresi:</label>
            <input type="text" id="new_apartment_address" name="new_apartment_address" required>
        </div>

        <!-- Mevcut apartman seçme alanları -->
        <div id="existing_apartment_fields" style="display:none;">
            <label for="existing_apartment_id">Mevcut Apartmanı Seçin:</label>
            <select name="existing_apartment_id" id="existing_apartment_id" required>
                <!-- Burada mevcut apartmanlar listelenecek -->
                <option value="1">Apartman 1</option>
                <option value="2">Apartman 2</option>
                <option value="3">Apartman 3</option>
            </select>
        </div>

        <button type="submit">Kaydet</button>
    </form>
</div>

<script>
    document.getElementById('apartment_select').addEventListener('change', function() {
        var selectedOption = this.value;
        if (selectedOption === 'new') {
            document.getElementById('new_apartment_fields').style.display = 'block';
            document.getElementById('existing_apartment_fields').style.display = 'none';
        } else if (selectedOption === 'existing') {
            document.getElementById('new_apartment_fields').style.display = 'none';
            document.getElementById('existing_apartment_fields').style.display = 'block';
        }
    });
</script>

</body>
</html>
