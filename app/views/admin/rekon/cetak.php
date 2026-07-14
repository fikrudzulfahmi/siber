<!-- views/rekon/cetak.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Konseling</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .no-border td { border: none; }
        .header { text-align: center; margin-bottom: 20px; }
        h3, h4, p { margin: 0; text-align: center; }
        .tanggal { font-size: 11px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h3>Rekap Konseling</h3>
        <h4>Ponpes Roudlotul Mutaallimin Minggirsari</h4>
<?php if (!empty($infoFilter)): ?>
    <h4><?= $infoFilter ?></h4>
<?php endif; ?>

        <div class="tanggal">Dicetak pada: <?= $tanggalCetak ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 150px;">Nama Siswa</th>
                <th style="width: 80px;">Kelas</th>
                <th style="width: 120px;">Kategori</th>
                <th>Permasalahan</th>
                <th style="width: 80px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach ($data as $i => $row): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_masalah'])) ?></td>
                        <td><?= $row['nama_siswa'] ?></td>
                        <td><?= $row['kelas'] ?? '-' ?></td>
                        <td><?= $row['nama_kategori'] ?? '-' ?></td>
                        <td><?= $row['permasalahan'] ?></td>
                        <td><?= $row['status'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
