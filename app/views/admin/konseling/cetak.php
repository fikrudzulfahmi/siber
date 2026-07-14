<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Konseling | PPRM Minggirsari</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .no-border td {
            border: none;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        h3,
        h4,
        p {
            margin: 0;
            text-align: center;
        }

        .tanggal {
            font-size: 11px;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h3>Laporan Konseling</h3>
        <h4>Ponpes Roudlotul Mutaallimin Minggirsari</h4>
        <div class="tanggal">Dicetak pada: <?= formatTanggalIndo($tanggalCetak) ?></div>
    </div>

    <table>
        <tr>
            <th>Nama Siswa</th>
            <td><?= htmlspecialchars($konseling['nama_siswa']) ?></td>
        </tr>
        <tr>
            <th>Kelas</th>
            <td><?= htmlspecialchars($konseling['kelas'] ?? '-') ?></td>
        </tr>
        <tr>
            <th>Kategori</th>
            <td><?= htmlspecialchars($konseling['nama_kategori']) ?></td>
        </tr>
        <tr>
            <th>Permasalahan</th>
            <td><?= nl2br(htmlspecialchars($konseling['permasalahan'])) ?></td>
        </tr>
        <tr>
            <th>Tanggal Masalah</th>
            <td><?= htmlspecialchars(formatTanggalIndo($konseling['tanggal_masalah'])) ?></td>
        </tr>
        <tr>
            <th>Petugas</th>
            <td><?= !empty($konseling['nama_petugas']) ? implode(', ', $konseling['nama_petugas']) : '-' ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><?= htmlspecialchars($konseling['status']) ?></td>
        </tr>
    </table>

    <h3>Tindak Lanjut</h3>
    <?php if (!empty($tindakLanjut)): ?>
        <table>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Catatan</th>
                <th>Bukti</th>
            </tr>
            <?php foreach ($tindakLanjut as $i => $tl): ?>
                <?php
                $filePath = __DIR__ . '/../../../../public/uploads/tindaklanjut/' . $tl['bukti'];
                if (!empty($tl['bukti']) && file_exists($filePath)) {
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                    $imgData = base64_encode(file_get_contents($filePath));
                    $src = 'data:image/' . $ext . ';base64,' . $imgData;
                } else {
                    $src = '';
                }
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($tl['tanggal']) ?></td>
                    <td><?= nl2br(htmlspecialchars($tl['catatan'])) ?></td>
                    <td>
                        <?php if ($src): ?>
                            <img src="<?= $src ?>" style="max-width:200px; max-height:200px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p><em>Belum ada tindak lanjut.</em></p>
    <?php endif; ?>
</body>

</html>