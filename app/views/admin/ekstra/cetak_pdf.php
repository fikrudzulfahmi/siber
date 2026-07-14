<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .content {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        .no-border {
            border: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>JURNAL KEGIATAN EKSTRAKURIKULER</h2>
        <h3><?= strtoupper($detail['nama_ekstra']) ?></h3>
    </div>

    <div class="content">
        <table class="no-border" style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 100px;">Tanggal</td>
                <td style="border: none;">: <?= date('d-m-Y', strtotime($detail['tanggal'])) ?></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">Kegiatan</td>
                <td style="border: none;">: <?= $detail['nama_kegiatan'] ?></td>
            </tr>
        </table>

        <p><strong>Isi/Materi Kegiatan:</strong></p>
        <div style="border: 1px solid #ccc; padding: 10px; min-height: 100px;">
            <?= nl2br($detail['isi_kegiatan']) ?>
        </div>

        <h4>Daftar Hadir Siswa:</h4>
        <table>
            <thead>
                <tr style="background-color: #eee;">
                    <th width="30">No</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th width="60">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($detail['presensi'] as $p): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $p['nama_siswa'] ?></td>
                        <td><?= $p['kelas'] ?></td>
                        <td><?= $p['status'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>