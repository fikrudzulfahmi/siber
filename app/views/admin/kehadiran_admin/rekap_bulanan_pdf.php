<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran Bulanan</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
        }

        h1,
        h3 {
            text-align: center;
            margin: 5px 0;
        }

        h1 {
            font-size: 14px;
        }

        h3 {
            font-size: 12px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-start {
            text-align: left;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-info {
            color: #0dcaf0;
        }

        .fw-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php
    // ✅ Pastikan array nama bulan ini LENGKAP
    $nama_bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
    ?>
    <h1>Rekap Kehadiran Bulanan</h1>
    <h3>Kelas: <?= htmlspecialchars($nama_kelas) ?> | Bulan: <?= $nama_bulan[(int)$bulan_terpilih] ?> <?= $tahun_terpilih ?></h3>
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 2%;">No</th>
                <th rowspan="2" class="text-start">Nama Siswa</th>
                <th colspan="<?= $rekap_bulanan['total_hari'] ?>">Tanggal</th>
                <th colspan="3">Jumlah</th>
            </tr>
            <tr>
                <?php for ($tgl = 1; $tgl <= $rekap_bulanan['total_hari']; $tgl++): ?>
                    <th style="width: 1.5%;"><?= $tgl ?></th>
                <?php endfor; ?>
                <th style="width: 1.5%;">S</th>
                <th style="width: 1.5%;">I</th>
                <th style="width: 1.5%;">A</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rekap_bulanan['siswa'])): ?>
                <tr>
                    <td colspan="<?= $rekap_bulanan['total_hari'] + 5 ?>">Tidak ada data.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($rekap_bulanan['siswa'] as $siswa): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-start"><?= htmlspecialchars($siswa['nama_siswa']) ?></td>
                        <?php foreach ($siswa['kehadiran'] as $status): ?>
                            <td>
                                <?php
                                if ($status == 'S') echo "<span class='text-warning fw-bold'>S</span>";
                                elseif ($status == 'I') echo "<span class='text-info fw-bold'>I</span>";
                                elseif ($status == 'A') echo "<span class='text-danger fw-bold'>A</span>";
                                elseif ($status == '0') echo "-";
                                else echo "H";
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td><?= $siswa['total_S'] ?></td>
                        <td><?= $siswa['total_I'] ?></td>
                        <td><?= $siswa['total_A'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>