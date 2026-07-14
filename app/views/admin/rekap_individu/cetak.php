<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rekap Presensi Individu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .text-center {
            text-align: center;
        }

        h3,
        h5 {
            margin: 2px 0;
        }

        p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px;
        }

        th {
            background-color: #f2f2f2;
        }

        .summary-table {
            width: 30%;
        }

        /* ✅ CSS BARU DITAMBAHKAN DI SINI */
        .content-container {
            /* Kontainer utama untuk mengatur posisi footer */
            position: relative;
            min-height: 95%;
        }

        .footer-container {
            position: absolute;
            bottom: 90px;
            right: 0;
            width: 150px;
            /* Sesuaikan lebar blok tanda tangan */
            text-align: left;
            /* Teks di dalamnya rata kiri */
        }

        .signature {
            margin-top: 60px;
        }
    </style>
</head>

<body>

    <div class="content-container">

        <div class="text-center">
            <h3>Rekap Presensi Guru & Pegawai</h3>
            <h3>Pondok Pesantren Roudlatul Mutaalimin</h3>
            <p>
                <b>Nama: <?= htmlspecialchars($infoPegawai['nama']) ?></b><br>
                Tanggal: <?= $periodeFormatted['awal'] ?> - <?= $periodeFormatted['akhir'] ?>
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Waktu Datang</th>
                    <th>Waktu Pulang</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rekapData['rincian'] as $i => $d) : ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= $d['tanggal_formatted'] ?></td>
                        <td class="text-center"><?= $d['datang'] ?></td>
                        <td class="text-center"><?= $d['pulang'] ?></td>
                        <td class="text-center"><?= $d['keterangan'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="summary-table">
            <?php $summary = $rekapData['summary']; ?>
            <tr>
                <td colspan="2">
                    <h5>Ringkasan</h5>
                </td>
            </tr>
            <tr>
                <td>Terlambat</td>
                <td class="text-center"><?= $summary['Terlambat'] ?></td>
            </tr>
            <tr>
                <td>Tidak Terlambat</td>
                <td class="text-center"><?= $summary['Hadir'] - $summary['Terlambat'] ?></td>
            </tr>
            <tr>
                <td>Pulang Cepat</td>
                <td class="text-center"><?= $summary['Pulang_Cepat'] ?></td>
            </tr>
            <tr>
                <td>Sakit</td>
                <td class="text-center"><?= $summary['Sakit'] ?></td>
            </tr>
            <tr>
                <td>Izin</td>
                <td class="text-center"><?= $summary['Izin'] ?></td>
            </tr>
            <tr>
                <td>Dinas Luar</td>
                <td class="text-center"><?= $summary['Dinas_Luar'] ?></td>
            </tr>
            <tr>
                <td>Alpa</td>
                <td class="text-center"><?= $summary['Alpa'] ?></td>
            </tr>
            <tr>
                <td>Kehadiran</td>
                <td class="text-center"><?= $summary['Hadir'] ?></td>
            </tr>
            <tr>
                <td>Hari Efektif</td>
                <td class="text-center"><?= $summary['Hari_Efektif'] ?></td>
            </tr>
        </table>

        <div class="footer-container">
            Blitar, <?= $tanggalCetak ?><br>
            Ketua Yayasan,<br>
            <div class="signature">(............................................)</div>
        </div>

    </div>

</body>

</html>