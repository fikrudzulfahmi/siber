<!DOCTYPE html>
<html>

<head>
    <title>Daftar Peringkat - <?= $dataKelas['nama_kelas'] ?></title>
    <style>
        @page {
            margin: 0.5cm 0.5cm 0.5cm 2cM;
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
            border-bottom: 2.5pt solid #000;
            margin-bottom: 15px;
            padding-bottom: 5px;
        }

        .kop-table td {
            border: none;
            vertical-align: middle;
        }

        .logo-box {
            width: 70px;
            padding-bottom: 5px;
        }

        .text-kop {
            text-align: center;
            padding-bottom: 5px;
        }

        .info-siswa {
            width: 100%;
            margin-bottom: 15px;
            border: none;
        }

        .info-siswa td {
            border: none;
            padding: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px 5px;
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

        /* Styling Badge Peringkat */
        .badge-juara {
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            color: #fff;
            display: inline-block;
        }

        .footer-table {
            width: 100%;
            margin-top: 30px;
            border: none;
            page-break-inside: avoid;
            /* Mencegah ttd terpotong halaman */
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

    <h3 style="text-align: center; text-transform: uppercase; margin-bottom: 15px;">DAFTAR PERINGKAT KELAS<br><?= strtoupper($judul) ?></h3>

    <table class="info-siswa">
        <tr>
            <td width="15%">Madrasah</td>
            <td width="45%">: <strong><?= $sekolah['nama'] ?></strong></td>
            <td width="15%">Kelas/Fase</td>
            <td>: <?= $dataKelas['kelas'] ?> / <?= $fase ?></td>
        </tr>
        <tr>
            <td>Tahun Ajaran</td>
            <td>: <?= $setting['tahun_pelajaran'] ?></td>
            <td>Semester</td>
            <td>: <?= $setting['semester'] ?></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="6%">No</th>
                <th width="18%">Peringkat</th>
                <th width="18%">NISN</th>
                <th>Nama Lengkap Siswa</th>
                <th width="18%">Total Nilai</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($peringkatData)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="color: red; padding: 15px;">Data nilai siswa belum diinput.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($peringkatData as $row): ?>
                    <?php
                    // Atur warna background badge berdasarkan peringkat
                    if ($row['peringkat'] == 1) {
                        $badgeStyle = 'background-color: #D4AF37; color: #fff;'; // Emas
                    } elseif ($row['peringkat'] == 2) {
                        $badgeStyle = 'background-color: #B0B0B0; color: #fff;'; // Perak
                    } elseif ($row['peringkat'] == 3) {
                        $badgeStyle = 'background-color: #CD7F32; color: #fff;'; // Perunggu
                    } else {
                        $badgeStyle = 'background-color: #e9ecef; color: #333; border: 1px solid #ccc; font-weight: normal;'; // Netral untuk peringkat 4++
                    }
                    ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center">
                            <span class="badge-juara" style="<?= $badgeStyle ?>">
                                Peringkat <?= $row['peringkat'] ?>
                            </span>
                        </td>
                        <td class="text-center"><?= !empty($row['nisn']) ? $row['nisn'] : '-' ?></td>
                        <td class="bold"><?= strtoupper($row['nama_siswa']) ?></td>
                        <td class="text-center bold" style="font-size: 10pt; color: #006400;">
                            <?= number_format($row['total_nilai'], 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    $bulanIndo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $tglFormat = date('d') . ' ' . $bulanIndo[(int)date('m')] . ' ' . date('Y');
    ?>
    <table class="footer-table">
        <tr>
            <td width="50%">
                <br>
                Kepala Madrasah,
                <br><br><br><br>
                <strong><?= $sekolah['kamad'] ?></strong>
            </td>
            <td width="50%">
                Blitar, <?= $tglFormat ?><br>
                Wali Kelas,
                <br><br><br><br>
                <strong><?= $_SESSION['user']['nama'] ?></strong>
            </td>
        </tr>
    </table>

</body>

</html>