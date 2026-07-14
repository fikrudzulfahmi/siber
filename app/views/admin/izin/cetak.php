<style>
    .page {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .divider {
        position: absolute;
        top: 50%;
        /* tepat di tengah halaman */
        left: 0;
        width: 100%;
        border-top: 1px dashed #000;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 12pt;
        margin: 0;
        padding: 0;
    }

    .a5 {
        width: 100%;
        padding: 10px 15px;
    }

    .header {
        text-align: center;
        font-weight: bold;
        margin-bottom: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    table td {
        border: 1px solid #000;
        padding: 6px;
        vertical-align: top;
    }

    .label {
        width: 35%;
        font-weight: bold;
    }

    .ttd {
        text-align: right;
        margin-top: 40px;
    }
</style>

<head>
    <meta charset="UTF-8">
    <title>Surat Izin Santri | PPRM Minggirsari</title>
</head>

<div class="page">
    <div class="a5">
        <div class="header">
            Surat Izin Santri<br>
            Ponpes Roudlotul Mutaallimin Minggirsari
        </div>

        <table>
            <tr>
                <td class="label">Nama Siswa</td>
                <td><?= $izin['nama_siswa'] ?></td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td><?= $izin['kelas'] ?></td>
            </tr>
            <tr>
                <td class="label">Keperluan</td>
                <td><?= $izin['keperluan'] ?></td>
            </tr>
            <tr>
                <td class="label">Waktu Meninggalkan</td>
                <td><?= date('d/m/Y H:i', strtotime($izin['waktu_meninggalkan'])) ?></td>
            </tr>
            <tr>
                <td class="label">Waktu Kembali</td>
                <td><?= date('d/m/Y H:i', strtotime($izin['waktu_kembali'])) ?></td>
            </tr>
            <tr>
                <td class="label">Keterangan</td>
                <td><?php
                    $info = [];

                    // Cek dan ubah teks keterangan
                    if (!empty($izin['keterangan'])) {
                        $keterangan_text = '';
                        if ($izin['keterangan'] == 'tepat') {
                            $keterangan_text = 'Tepat Waktu';
                        } elseif ($izin['keterangan'] == 'terlambat') {
                            $keterangan_text = 'Terlambat';
                        }

                        if ($keterangan_text) {
                            $info[] = '<strong>' . $keterangan_text . '</strong>';
                        }
                    }

                    // Tambahkan tindakan jika ada isinya
                    if (!empty($izin['tindakan'])) {
                        $info[] = htmlspecialchars($izin['tindakan']);
                    }

                    // Gabungkan info atau tampilkan strip jika kosong
                    echo !empty($info) ? implode(' - ', $info) : '-';
                    ?></td>
            </tr>
        </table>

        <div class="ttd">
            Blitar, <?= formatTanggalIndo($tanggalCetak) ?><br><br><br><br>
            <u><?= $izin['nama_rekom'] ?></u><br>
            Pemberi Rekomendasi
        </div>
    </div>

    <!-- Garis tengah halaman -->
    <div class="divider"></div>

    <div class="a5" style="margin-top: 18%;">
        <div class="header">
            Surat Izin Santri<br>
            Ponpes Roudlotul Mutaallimin Minggirsari
        </div>

        <table>
            <tr>
                <td class="label">Nama Siswa</td>
                <td><?= $izin['nama_siswa'] ?></td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td><?= $izin['kelas'] ?></td>
            </tr>
            <tr>
                <td class="label">Keperluan</td>
                <td><?= $izin['keperluan'] ?></td>
            </tr>
            <tr>
                <td class="label">Waktu Meninggalkan</td>
                <td><?= date('d/m/Y H:i', strtotime($izin['waktu_meninggalkan'])) ?></td>
            </tr>
            <tr>
                <td class="label">Waktu Kembali</td>
                <td><?= date('d/m/Y H:i', strtotime($izin['waktu_kembali'])) ?></td>
            </tr>
            <tr>
                <td class="label">Keterangan</td>
                <td><?php
                    $info = [];

                    // Cek dan ubah teks keterangan
                    if (!empty($izin['keterangan'])) {
                        $keterangan_text = '';
                        if ($izin['keterangan'] == 'tepat') {
                            $keterangan_text = 'Tepat Waktu';
                        } elseif ($izin['keterangan'] == 'terlambat') {
                            $keterangan_text = 'Terlambat';
                        }

                        if ($keterangan_text) {
                            $info[] = '<strong>' . $keterangan_text . '</strong>';
                        }
                    }

                    // Tambahkan tindakan jika ada isinya
                    if (!empty($izin['tindakan'])) {
                        $info[] = htmlspecialchars($izin['tindakan']);
                    }

                    // Gabungkan info atau tampilkan strip jika kosong
                    echo !empty($info) ? implode(' - ', $info) : '-';
                    ?></td>
            </tr>
        </table>

        <div class="ttd">
            Blitar, <?= formatTanggalIndo($tanggalCetak) ?><br><br><br><br>
            <u><?= $izin['nama_rekom'] ?></u><br>
            Pemberi Rekomendasi
        </div>
    </div>
</div>