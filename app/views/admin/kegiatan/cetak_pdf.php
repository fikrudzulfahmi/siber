<!DOCTYPE html>
<html>

<head>
    <title>Laporan Kegiatan</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            line-height: 1.6;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .info-table {
            border: none;
        }

        td {
            border: none;
            padding: 2px;
        }

        .dokumentasi-section {
            margin-top: 30px;
            width: 100%;
        }

        .foto-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            /* Hilangkan border untuk galeri foto */
        }

        .foto-table td {
            border: none;
            padding: 10px;
            vertical-align: top;
            text-align: center;
            width: 50%;
            /* Mengatur 2 kolom */
        }

        .img-wrapper {
            border: 1px solid #ddd;
            padding: 5px;
            background: #fff;
            page-break-inside: avoid;
            /* Mencegah foto terpotong antar halaman */
        }

        .foto-item {
            width: 100%;
            height: auto;
            display: block;
        }

        h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .status-hadir {
            color: green;
            font-weight: bold;
        }

        .status-tidak {
            color: red;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">LAPORAN KEGIATAN GURU & STRUKTURAL</div>
        <div>Pondok Pesantren Roudhotul Muta'alimin Minggirsari</div>
    </div>

    <table class="info-table">
        <tr>
            <td width="120">Nama Kegiatan</td>
            <td>: <?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: <?= date('d F Y', strtotime($kegiatan['tanggal'])) ?></td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>: <?= substr($kegiatan['jam_mulai'], 0, 5) ?> - <?= substr($kegiatan['jam_selesai'], 0, 5) ?></td>
        </tr>
        <tr>
            <td>Keterangan</td>
            <td>: <?= htmlspecialchars($kegiatan['keterangan'] ?: '-') ?></td>
        </tr>
    </table>

    <h3>Daftar Hadir (Presensi Fingerprint)</h3>
    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Nama Peserta</th>
                <th>PIN</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($rekap_absen as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= $row['pin'] ?></td>
                    <td><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                    <td><?= ($row['jam_pulang'] && $row['jam_pulang'] != $row['jam_masuk']) ? date('H:i', strtotime($row['jam_pulang'])) : '-' ?></td>
                    <td>
                        <?php if ($row['jam_masuk']): ?>
                            <span class="status-hadir">HADIR</span>
                            <?= (strtotime($row['jam_masuk']) > strtotime($kegiatan['jam_mulai'])) ? ' (Terlambat)' : '' ?>
                        <?php else: ?>
                            <span class="status-tidak">TIDAK HADIR</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="dokumentasi-section">
        <h3>Dokumentasi Kegiatan</h3>
        <table class="foto-table">
            <?php
            $count = 0;
            foreach ($fotos as $f):
                $path = $_SERVER['DOCUMENT_ROOT'] . '/public/uploads/kegiatan/' . $f['nama_file'];
                if (file_exists($path)):
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

                    // Mulai baris baru setiap 2 foto
                    if ($count % 2 == 0) echo '<tr>';
            ?>
                    <td>
                        <div class="img-wrapper">
                            <img src="<?= $base64 ?>" class="foto-item">
                        </div>
                    </td>
            <?php
                    $count++;
                    // Tutup baris setiap 2 foto
                    if ($count % 2 == 0) echo '</tr>';
                endif;
            endforeach;

            // Menutup tag TR jika jumlah foto ganjil
            if ($count % 2 != 0) echo '<td></td></tr>';
            ?>
        </table>
    </div>
</body>

</html>