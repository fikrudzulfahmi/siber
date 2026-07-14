<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Presensi</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2,
        h4,
        h5 {
            margin: 0;
            text-align: center;
        }

        p {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #eee;
        }

        .signature {
            margin-top: 30px;
            float: right;
            text-align: center;
        }
    </style>
</head>

<body>

    <h2>Rekap Presensi Guru & Pegawai</h2>
    <h4>Pondok Pesantren Raudlatul Mutaalimin</h4>
    <p style="text-align: center;"><?= $infoFilter ?></p>
    <p>Tanggal cetak: <?= $tanggalCetak ?></p>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Hari Efektif</th>
                <th>Kehadiran</th>
                <th>Alpa</th>
                <th>Terlambat</th>
                <th>Pulang Cepat</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Dinas Luar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rekap)): ?>
                <?php foreach ($rekap as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['jabatan']) ?></td>
                        <td><?= $row['Hari_Efektif'] ?? 0 ?></td>
                        <td><?= $row['Kehadiran'] ?? 0 ?></td>
                        <td><?= $row['Alpa'] ?? 0 ?></td>
                        <td><?= $row['Terlambat'] ?? 0 ?></td>
                        <td><?= $row['Pulang_Cepat'] ?? 0 ?></td>
                        <td><?= $row['Sakit'] ?? 0 ?></td>
                        <td><?= $row['Izin'] ?? 0 ?></td>
                        <td><?= $row['Dinas_Luar'] ?? 0 ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature">
        <div>Blitar, <?= $tanggalCetak ?></div>
        <div style="margin-bottom: 40px;">Ketua Yayasan,</div>
        <div>(........................................)</div>
    </div>

</body>

</html>