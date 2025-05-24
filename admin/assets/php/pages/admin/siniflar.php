<? $stmt = $db->prepare("
    SELECT s.*, o.ad AS okul_adi
    FROM siniflar s
    JOIN okullar o ON s.okul_id = o.id
    ORDER BY o.ad, s.seviye, s.ad
");
$stmt->execute();
$siniflar = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table class="table table-lg">
    <thead>
        <tr>
            <th>Sınıf Adı</th>
            <th>Seviye</th>
            <th>Okul Adı</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($siniflar as $sinif): ?>
        <tr>
            <td><?= $sinif['ad'] ?></td>
            <td><?= $sinif['seviye'] ?></td>
            <td><?= $sinif['okul_adi'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
