<?php
$stmt = $db->prepare("
    SELECT * FROM kullanicilar
    WHERE rol = 'ogretmen'
    ORDER BY ad_soyad ASC
");
$stmt->execute();
$odemeler = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HTML Listeleme -->
<table class="table table-lg">
    <thead>
        <tr>
            <th>#</th>
            <th>Öğretmen</th>
            <th>Tutar</th>
            <th>Tarih</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($odemeler as $i => $odeme): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($odeme['ad_soyad']) ?></td>
            <td><?= number_format($odeme['odenen_tutar'], 2, ',', '.') ?>₺</td>
            <td><?= date('d.m.Y', strtotime($odeme['odeme_tarihi'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
