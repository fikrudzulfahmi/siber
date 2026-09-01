<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
        }

        h2,
        h4 {
            text-align: center;
            margin: 0;
        }

        .mb {
            margin-bottom: 20px;
        }

        .ttd {
            margin-top: 40px;
            width: 100%;
        }

        .ttd table {
            border: none;
            width: 100%;
        }

        .ttd td {
            border: none;
            text-align: center;
            width: 50%;
        }
    </style>
</head>

<body>
    <h2>LAPORAN JURNAL STRUKTURAL</h2>
    <h4 class="mb">Periode: <?= date('d-m-Y', strtotime($tanggalAwal)) ?> s.d <?= date('d-m-Y', strtotime($tanggalAkhir)) ?></h4>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th style="width: 130px;">Nama</th>
                <th>Program Kerja</th>
                <th style="width: 150px;">Catatan dan Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jurnalList)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#666;">
                        Tidak ada jurnal pada periode <?= date('d-m-Y', strtotime($tanggalAwal)) ?> s.d <?= date('d-m-Y', strtotime($tanggalAkhir)) ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($jurnalList as $row): ?>
                    <tr>
                        <td style="text-align:center;"><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                        <td><?= $row['ringkasan_program'] ?: '-' ?></td>
                        <td><?= htmlspecialchars($row['catatan_akhir'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd">
        <table>
            <tr>
                <td>
                    Mengetahui,<br>
                    Kepala Madrasah
                    <br><br><br><br>
                    <strong><u>................................................</u></strong>
                </td>
                <td>
                    <?= date('d F Y') ?><br>
                    Petugas
                    <br><br><br><br>
                    <strong><u><?= htmlspecialchars($_SESSION['user']['nama'] ?? '') ?></u></strong>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
