<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #111;
        }

        h2 {
            text-align: center;
            font-size: 14px;
            margin: 0 0 2px 0;
        }

        h4 {
            text-align: center;
            font-size: 11px;
            font-weight: normal;
            margin: 0 0 10px 0;
        }

        p.guru {
            margin: 0 0 8px 0;
            font-size: 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            line-height: 1.35;
        }

        th {
            background-color: #e9ecef;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }

        tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        td.no,
        td.tgl,
        td.jam {
            text-align: center;
        }

        .kosong {
            color: #888;
        }
    </style>
</head>

<body>
    <h2>LAPORAN JURNAL PEMBELAJARAN</h2>
    <h4>Periode: <?= date('d-m-Y', strtotime($tanggal_awal)) ?> s.d <?= date('d-m-Y', strtotime($tanggal_akhir)) ?></h4>
    <p class="guru"><strong>Nama Guru:</strong> <?= sec_e($nama_guru) ?></p>

    <table>
        <colgroup>
            <col style="width:4%">
            <col style="width:9%">
            <col style="width:11%">
            <col style="width:14%">
            <col style="width:30%">
            <col style="width:17%">
            <col style="width:15%">
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kelas / Jam</th>
                <th>Mata Pelajaran</th>
                <th>Materi &amp; Tujuan</th>
                <th>Siswa Tidak Hadir</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jurnalList)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#666;">
                        Tidak ada jurnal pada periode ini.
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($jurnalList as $j): ?>
                    <?php
                    // Ringkas daftar siswa tidak hadir: "Nama (S), Nama (A)".
                    // Status di tabel disimpan sebagai kode satu huruf.
                    $ringkas = [];
                    foreach (($j['rekap_siswa'] ?? []) as $s) {
                        $kode = strtoupper(trim((string) ($s['status'] ?? '')));
                        $ringkas[] = sec_e($s['nama_siswa']) . ($kode !== '' ? ' (' . sec_e($kode) . ')' : '');
                    }
                    ?>
                    <tr>
                        <td class="no"><?= $no++ ?></td>
                        <td class="tgl"><?= date('d-m-Y', strtotime($j['tanggal'])) ?></td>
                        <td class="jam">
                            <?= sec_e($j['kelas']) ?><br>
                            <span style="color:#555;">Jam <?= sec_e($j['jam_mulai']) ?>&ndash;<?= sec_e($j['jam_akhir']) ?></span>
                        </td>
                        <td><?= sec_e($j['nama_mapel']) ?></td>
                        <td>
                            <strong>Materi:</strong> <?= sec_e($j['materi']) ?><br>
                            <strong>Tujuan:</strong> <?= $j['tujuan_pembelajaran'] ?: '-' ?>
                        </td>
                        <td><?= $ringkas ? implode(', ', $ringkas) : '<span class="kosong">-</span>' ?></td>
                        <td><?= !empty($j['catatan_pembelajaran']) ? sec_e($j['catatan_pembelajaran']) : '<span class="kosong">-</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>
