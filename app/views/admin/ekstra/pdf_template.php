<html>

<head>
    <title><?= $title ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
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
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .text-left {
            text-align: left;
        }

        .bg-grey {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="header">
        <h3>LAPORAN KEGIATAN EKSTRAKURIKULER <?= strtoupper($ekstra['nama_ekstra']) ?></h3>
        <p>Periode: <?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    </div>

    <h4>I. Rekapitulasi Presensi Pembina & Pendamping</h4>
    <table>
        <thead>
            <tr class="bg-grey">
                <th>No</th>
                <th>Nama Guru</th>
                <th>Jabatan</th>
                <th>H</th>
                <th>I</th>
                <th>S</th>
                <th>A</th>
            </tr>
        </thead>
        <tbody>
            <?php $noG = 1;
            foreach ($rekap_guru as $rg): ?>
                <tr>
                    <td><?= $noG++ ?></td>
                    <td class="text-left"><?= $rg['nama'] ?></td>
                    <td><?= $rg['jabatan'] ?></td>
                    <td><?= $rg['hadir'] ?></td>
                    <td><?= $rg['izin'] ?></td>
                    <td><?= $rg['sakit'] ?></td>
                    <td><?= $rg['alfa'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4>II. Rekapitulasi Presensi Siswa</h4>
    <table>
        <thead>
            <tr class="bg-grey">
                <th>No</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>H</th>
                <th>I</th>
                <th>S</th>
                <th>A</th>
            </tr>
        </thead>
        <tbody>
            <?php $noS = 1;
            foreach ($rekap_siswa as $rs): ?>
                <tr>
                    <td><?= $noS++ ?></td>
                    <td class="text-left"><?= $rs['nama_siswa'] ?></td>
                    <td><?= $rs['kelas'] ?></td>
                    <td><?= $rs['hadir'] ?></td>
                    <td><?= $rs['izin'] ?></td>
                    <td><?= $rs['sakit'] ?></td>
                    <td><?= $rs['alfa'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4>III. Jurnal Kegiatan & Dokumentasi</h4>
    <table>
        <thead>
            <tr class="bg-grey">
                <th width="15%">Tanggal</th>
                <th>Kegiatan & Materi</th>
                <th width="25%">Dokumentasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jurnal as $j): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($j['tanggal'])) ?></td>
                    <td class="text-left">
                        <strong><?= $j['nama_kegiatan'] ?></strong><br>
                        <?= $j['isi_kegiatan'] ?>
                    </td>
                    <td>
                        <?php
                        // Menggunakan realpath agar PHP menemukan lokasi fisik file di server
                        $path = realpath(__DIR__ . '/../../../../public/uploads/ekstra/' . $j['nama_file']);

                        if ($j['nama_file'] && $path && file_exists($path)) :
                            try {
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $dataImg = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($dataImg);
                        ?>
                                <img src="<?= $base64 ?>" style="width: 120px; height: auto;">
                            <?php
                            } catch (Exception $e) {
                                echo '<small style="color: red;">Error load gambar</small>';
                            }
                        else : ?>
                            <small style="color: grey;">Tidak ada foto</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>