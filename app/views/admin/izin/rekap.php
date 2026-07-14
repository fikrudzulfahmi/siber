<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Izin Siswa | PPRM Minggirsari</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
        h3, h4, p { margin: 0; text-align: center; }
    </style>
</head>
<body>
    <h3>Rekap Izin Siswa</h3>
    <h4>Ponpes Roudlotul Mutaallimin Minggirsari</h4>
    <p>Periode: <?= date('d/m/Y', strtotime($tanggalAwal)) ?> s.d. <?= date('d/m/Y', strtotime($tanggalAkhir)) ?></p>
    <br>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Keperluan</th>
                <th>Waktu Meninggalkan</th>
                <th>Waktu Kembali</th>
                <th>Rekomendasi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach ($izin as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['nama_siswa'] ?></td>
                <td><?= $row['nama_kelas'] ?></td>
                <td><?= $row['keperluan'] ?></td>
                <td><?= formatTanggalIndo($row['waktu_meninggalkan'], true) ?></td>
                <td><?= formatTanggalIndo($row['waktu_kembali'], true) ?></td>
                <td><?= $row['nama_rekom'] ?></td>
                <td><?= $row['keterangan'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br><br>
    <p style="text-align:right;">Dicetak: <?= $tanggalCetak ?></p>
</body>
</html>
