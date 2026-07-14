<!DOCTYPE html>
<html>

<head>
    <title>Rapor Siswa - <?= $dataSiswa['nama_siswa'] ?></title>
    <style>
        @page {
            margin: 0.5cm 0.5cm 0.5cm 2cM;
            /* Atur margin atas, kanan, bawah, kiri */
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            margin: 0;
            padding: 10px;
        }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            /* Ini adalah garis bawah kop */
            border-bottom: 2.5pt solid #000;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }

        .kop-table td {
            border: none;
            /* Pastikan sel tabel tidak punya garis dalam */
            vertical-align: middle;
        }

        .logo-box {
            width: 70px;
            padding-bottom: 5px;
            /* Jarak logo ke garis */
        }

        .text-kop {
            text-align: center;
            padding-bottom: 5px;
            /* Jarak teks ke garis */
        }

        /* Layout Info Siswa */
        .info-siswa {
            width: 100%;
            margin-bottom: 10px;
            border: none;
        }

        .info-siswa td {
            border: none;
            padding: 1px;
        }

        /* Container Kolom */
        .row {
            width: 100%;
            clear: both;
        }

        .col-left {
            width: 45%;
            float: left;
        }

        .col-right {
            width: 52%;
            float: right;
        }

        /* Tabel Standar */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 3px 5px;
        }

        th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-size: 8pt;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        /* Box Catatan */
        .box-catatan {
            border: 1px solid black;
            width: 98%;
            min-height: 40px;
            padding: 5px;
            margin-bottom: 10px;
        }

        /* Footer Tanda Tangan */
        .footer-table {
            width: 100%;
            margin-top: 15px;
            border: none;
        }

        .footer-table td {
            border: none;
            text-align: center;
        }
    </style>
</head>

<body>

    <table class="kop-table">
        <tr>
            <td class="logo-box">
                <?php if (!empty($base64Logo)): ?>
                    <img src="<?= $base64Logo ?>" width="70" height="70">
                <?php endif; ?>
            </td>
            <td class="text-kop">
                <h3 style="margin:0; font-size: 12pt; text-transform: uppercase;">YAYASAN PENDIDIKAN MAARIF NU MINGGIRSARI</h3>
                <h2 style="margin:0; font-size: 15pt; color: #006400;"><?= $sekolah['nama'] ?></h2>
                <p style="margin:0; font-size: 9pt;">Jl. Raya Brantas Desa Minggirsari RT. 2 RW. 3 Kanigoro - Blitar</p>
            </td>
            <td style="width: 70px;"></td>
        </tr>
    </table>

    <h3 style="text-align: center;"><?= strtoupper($judul) ?></h3>

    <table class="info-siswa">
        <tr>
            <td width="12%">Nama</td>
            <td width="38%">: <strong><?= strtoupper($dataSiswa['nama_siswa']) ?></strong></td>
            <td width="15%">Kelas/Fase</td>
            <td>: <?= $dataKelas['kelas'] ?> / <?= $fase ?></td>
        </tr>
        <tr>
            <td>NIS/NISN</td>
            <td>: <?= $dataSiswa['nisn'] ?></td>
            <td>Semester</td>
            <td>: <?= $setting['semester'] ?></td>
        </tr>
        <tr>
            <td>Madrasah</td>
            <td>: <?= $sekolah['nama'] ?></td>
            <td>Tahun Ajaran</td>
            <td>: <?= $setting['tahun_pelajaran'] ?></td>
        </tr>
    </table>

    <div class="row">
        <div class="col-left">
            <p class="bold" style="margin: 5px 0;">CAPAIAN HASIL BELAJAR</p>
            <table>
                <thead>
                    <tr>
                        <th width="10%">No</th>
                        <th>Mata Pelajaran</th>
                        <th width="20%">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    $total = 0;
                    foreach ($nilaiAkhir as $n): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= $n['nama_mapel'] ?></td>
                            <td class="text-center"><?= round($n['nilai_final']) ?></td>
                        </tr>
                    <?php $total += $n['nilai_final'];
                    endforeach; ?>
                    <tr class="bold">
                        <td colspan="2" class="text-center">Jumlah</td>
                        <td class="text-center"><?= round($total) ?></td>
                    </tr>
                </tbody>
            </table>
            <p style="font-size: 10pt;">
                Peringkat ke:
                <strong>
                    <?= in_array($rankSiswa, [1, 2, 3]) ? $rankSiswa : '-' ?>
                </strong>
            </p>
        </div>

        <div class="col-right">
            <p class="bold" style="margin: 5px 0;">EKSTRAKURIKULER</p>
            <table>
                <tr>
                    <th width="10%">No</th>
                    <th>Kegiatan</th>
                    <th width="15%">Nilai</th>
                    <th>Keterangan</th>
                </tr>
                <?php for ($i = 0; $i < 3; $i++):
                    // Logika pengecekan agar rapi, jika kosong cetak '-'
                    $e_nama = !empty($dataEkstra[$i]['nama_kegiatan']) ? $dataEkstra[$i]['nama_kegiatan'] : '-';
                    $e_nilai = !empty($dataEkstra[$i]['nilai']) ? $dataEkstra[$i]['nilai'] : '-';
                    $e_ket = !empty($dataEkstra[$i]['keterangan']) ? $dataEkstra[$i]['keterangan'] : '-';
                ?>
                    <tr>
                        <td class="text-center"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($e_nama) ?></td>
                        <td class="text-center"><?= htmlspecialchars($e_nilai) ?></td>
                        <td><?= htmlspecialchars($e_ket) ?></td>
                    </tr>
                <?php endfor; ?>
            </table>

            <p class="bold" style="margin: 5px 0;">PRESTASI</p>
            <table>
                <thead>
                    <tr>
                        <th width="10%">No</th>
                        <th width="30%">Jenis Prestasi</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < 3; $i++):
                        // Logika pengecekan agar rapi, jika kosong cetak '-'
                        $p_jenis = !empty($dataPrestasi[$i]['jenis_prestasi']) ? $dataPrestasi[$i]['jenis_prestasi'] : '-';
                        $p_ket = !empty($dataPrestasi[$i]['keterangan']) ? $dataPrestasi[$i]['keterangan'] : '-';
                    ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($p_jenis) ?></td>
                            <td><?= htmlspecialchars($p_ket) ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <p class="bold" style="margin: 5px 0;">KETIDAKHADIRAN</p>
            <table style="width: 70%;">
                <tr>
                    <td width="40%">Sakit</td>
                    <td><?= $dataRaporSiswa['sakit'] ?? 0 ?> hari</td>
                </tr>
                <tr>
                    <td>Izin</td>
                    <td><?= $dataRaporSiswa['izin'] ?? 0 ?> hari</td>
                </tr>
                <tr>
                    <td>Alpha</td>
                    <td><?= $dataRaporSiswa['alfa'] ?? 0 ?> hari</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="row" style="margin-top: 10px;">
        <p class="bold" style="margin: 2px 0;">CATATAN WALI KELAS</p>
        <div class="box-catatan"><?= !empty($dataRaporSiswa['catatan_walikelas']) ? htmlspecialchars($dataRaporSiswa['catatan_walikelas']) : '-' ?></div>

        <p class="bold" style="margin: 2px 0;">CATATAN ORANG TUA</p>
        <div class="box-catatan"></div>
    </div>

    <table class="footer-table">
        <tr>
            <td width="33%">
                Orang Tua/Wali<br><br><br><br>
                ( ............................. )
            </td>
            <td width="33%">
                Mengetahui,<br>Kepala Madrasah<br><br><br><br>
                <strong><u><?= $sekolah['kamad'] ?></u></strong>
            </td>
            <td width="33%">
                Blitar, <?= formatTanggalIndo($setting['tgl_pembagian']) ?><br>
                Wali Kelas<br><br><br><br>
                <strong><u><?= $_SESSION['user']['nama'] ?></u></strong>
            </td>
        </tr>
    </table>

</body>

</html>