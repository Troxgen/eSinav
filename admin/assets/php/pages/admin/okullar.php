<?php 
$stmt = $db->prepare("
    SELECT *, (ogrenci_adeti * ogrenci_tutar) AS toplam_gelir
    FROM okullar
    ORDER BY toplam_gelir DESC
");
$stmt->execute();
$okullar = $stmt->fetchAll(PDO::FETCH_ASSOC);

$genel_toplam = 0;
$en_zengin_okul = null;
foreach ($okullar as $okul) {
    $genel_toplam += $okul['toplam_gelir'];
    if (!$en_zengin_okul || $okul['toplam_gelir'] > $en_zengin_okul['toplam_gelir']) {
        $en_zengin_okul = $okul;
    }
}
?>
<table class="table table-lg">
    <thead>
        <tr>
            <th>Okul Adı</th>
            <th>Şehir</th>
            <th>İlçe</th>
            <th>Öğrenci Sayısı</th>
            <th>Ücret</th>
            <th>Toplam Gelir</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($okullar as $okul): ?>
            <tr>
                <td><?= $okul['ad'] ?></td>
                <td><?= $okul['sehir'] ?></td>
                <td><?= $okul['ilce'] ?></td>
                <td><?= $okul['ogrenci_adeti'] ?></td>
                <td><?= $okul['ogrenci_tutar'] ?> ₺</td>
                <td><?= number_format($okul['toplam_gelir'], 2) ?> ₺</td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><strong>Genel Toplam:</strong> <?= number_format($genel_toplam, 2) ?> ₺</p>
<p><strong>En Zengin Okul:</strong> <?= $en_zengin_okul['ad'] ?> (<?= number_format($en_zengin_okul['toplam_gelir'], 2) ?> ₺)</p>
