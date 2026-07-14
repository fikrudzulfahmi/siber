<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
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
        }

        h2,
        h4 {
            text-align: center;
            margin: 0;
        }

        .mb {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <h2>LAPORAN JURNAL PEMBELAJARAN</h2>
    <h4 class="mb">Periode: <?= date('d-m-Y', strtotime($tanggal_awal)) ?> s.d <?= date('d-m-Y', strtotime($tanggal_akhir)) ?></h4>
    <p style="text-align:left; margin: 5px 0 10px 0;"><strong>Nama Guru:</strong> <?= $nama_guru ?></p>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 60px;">Kelas / Jam</th>
                <th style="width: 100px;">Mata Pelajaran</th>
                <th>Materi & Tujuan</th>
                <th style="width: 120px;">Siswa Tidak Hadir</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($jurnalList as $j): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d-m-Y', strtotime($j['tanggal'])) ?></td>
                    <td><?= $j['kelas'] ?><br><small>(Jam: <?= $j['jam_mulai'] ?>-<?= $j['jam_akhir'] ?>)</small></td>
                    <td><?= $j['nama_mapel'] ?></td>
                    <td>
                        <strong>Materi:</strong> <?= $j['materi'] ?><br>
                        <strong>Tujuan:</strong> <?= $j['tujuan_pembelajaran'] ?>
                    </td>
                    <td>
                        <?php if (!empty($j['rekap_siswa'])): ?>
                            <ul style="margin:0; padding-left:12px;">
                                <?php foreach ($j['rekap_siswa'] as $s): ?>
                                    <li><?= $s['nama_siswa'] ?> (<?= $s['status'] ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            Nihil (Hadir Semua)
                        <?php endif; ?>
                    </td>
                    <td><?= $j['catatan_pembelajaran'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>