<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran - <?= htmlspecialchars($tanggal_terpilih) ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
        }

        h1,
        h3 {
            text-align: center;
            margin: 5px 0;
        }

        h1 {
            font-size: 16px;
        }

        h3 {
            font-size: 12px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
        }

        tr {
            page-break-inside: avoid !important;
        }

        .text-center {
            text-align: center;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-success {
            color: #28a745;
        }

        .fw-bold {
            font-weight: bold;
        }

        .align-top {
            vertical-align: top;
        }

        /* ✅ --- TRIK CSS UNTUK SIMULASI ROWSPAN --- ✅ */
        .kelas-cell {
            border-bottom: 1px solid #fff;
            /* Border bawah jadi putih (tak terlihat) */
            border-right: 1px solid #333;
            border-left: 1px solid #333;
        }

        .kelas-cell.first-row {
            border-top: 2px solid #000;
            /* Border atas tebal untuk baris pertama */
        }

        .kelas-cell.last-row {
            border-bottom: 2px solid #000;
            /* Border bawah tebal untuk baris terakhir */
        }

        .data-cell.first-row {
            border-top: 2px solid #000;
            /* Border atas tebal untuk sel data */
        }

        .data-cell.last-row {
            border-bottom: 2px solid #000;
            /* Border bawah tebal untuk sel data */
        }
    </style>
</head>

<body>
    <h1>Rekap Kehadiran Jurnal Pembelajaran</h1>
    <h3>Tanggal: <?= htmlspecialchars(date('d F Y', strtotime($tanggal_terpilih))) ?></h3>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Kelas</th>
                <th class="text-center" style="width: 20%;">Jadwal Pelajaran</th>
                <th style="width: 25%;">Guru Pengajar</th>
                <th style="width: 40%;">Keterangan Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $jadwalPerKelas = [];
            foreach ($jadwal_harian as $jadwal) {
                $jadwalPerKelas[$jadwal['nama_kelas']][] = $jadwal;
            }
            ?>

            <?php if (empty($jadwalPerKelas)): ?>
                <tr>
                    <td colspan="4" class="text-center">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($jadwalPerKelas as $namaKelas => $jadwals): ?>
                    <?php
                    $totalJadwal = count($jadwals);
                    $rowCounter = 0;
                    foreach ($jadwals as $jadwal):
                        $rowCounter++;
                        $isFirstRow = ($rowCounter == 1);
                        $isLastRow = ($rowCounter == $totalJadwal);

                        // Tentukan class untuk border tebal
                        $rowClass = '';
                        if ($isFirstRow) $rowClass .= ' first-row';
                        if ($isLastRow) $rowClass .= ' last-row';
                    ?>
                        <tr>
                            <td class="fw-bold kelas-cell <?= $rowClass ?>">
                                <?= $isFirstRow ? htmlspecialchars($namaKelas) : '' ?>
                            </td>

                            <?php if ($jadwal['jam_mulai'] === null): ?>
                                <td colspan="3" class="text-center text-muted data-cell <?= $rowClass ?>"><i>Tidak ada jadwal pelajaran hari ini.</i></td>
                            <?php else: ?>
                                <td class="text-center data-cell <?= $rowClass ?>">
                                    Jam <?= $jadwal['jam_mulai'] . '-' . $jadwal['jam_selesai'] ?><br>
                                    <small><?= htmlspecialchars($jadwal['nama_mapel']) ?></small>
                                </td>
                                <td class="data-cell <?= $rowClass ?>"><?= htmlspecialchars($jadwal['nama_guru'] ?? '-') ?></td>
                                <td class="align-top data-cell <?= $rowClass ?>">
                                    <?php if ($jadwal['sudah_isi_jurnal']): ?>
                                        <?php
                                        $absensiSiswa = [];
                                        for ($jam = (int)$jadwal['jam_mulai']; $jam <= (int)$jadwal['jam_selesai']; $jam++) {
                                            if (!empty($kehadiran[$jadwal['id_kelas']][$jam])) {
                                                foreach ($kehadiran[$jadwal['id_kelas']][$jam] as $absen) {
                                                    $absensiSiswa[] = $absen;
                                                }
                                            }
                                        }
                                        if (empty($absensiSiswa)) {
                                            echo "<span class='text-success'><i>Nihil</i></span>";
                                        } else {
                                            echo "<span>" . implode("<br>", array_unique($absensiSiswa)) . "</span>";
                                        }
                                        ?>
                                    <?php else: ?>
                                        <strong class="text-danger"><i>Belum mengisi jurnal.</i></strong>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>