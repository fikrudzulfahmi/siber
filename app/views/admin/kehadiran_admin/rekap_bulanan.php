<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    /* ... (CSS Anda yang sudah ada) ... */

    /* ✅ Tambahkan ini untuk mengecilkan kolom tanggal */
    .th-tanggal {
        width: 1%;
        /* Membuat kolom sekecil mungkin */
        white-space: nowrap;
        /* Mencegah teks turun baris */
    }
</style>

<div class="container-fluid py-4">
    <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Rekap Kehadiran Bulanan</h6>
            </div>
        </div>
        <div class="card-body px-4 py-4">
            <?php
            // PASTIKAN BLOK PHP INI ADA DAN LENGKAP
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

            <form method="POST">
                <div style="display: flex; align-items: flex-end; gap: 1rem;">

                    <div>
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-control border">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= ($bulan_terpilih == $i) ? 'selected' : '' ?>>
                                    <?= $nama_bulan[$i] ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Tahun</label>
                        <input type="number" name="tahun" value="<?= $tahun_terpilih ?>" class="form-control border">
                    </div>

                    <?php if ($_SESSION['user']['level'] != 3): ?>
                        <div>
                            <label class="form-label">Kelas</label>
                            <select name="id_kelas" class="form-control border">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($daftar_kelas as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>" <?= ($id_kelas_terpilih == $kelas['id_kelas']) ? 'selected' : '' ?>><?= $kelas['kelas'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div>
                        <button type="submit" class="btn bg-gradient-success text-white mb-0">Tampilkan</button>

                        <?php if (!empty($rekap_bulanan['siswa'])): ?>
                            <a href="?controller=kehadiran&method=cetakRekapBulanan&bulan=<?= $bulan_terpilih ?>&tahun=<?= $tahun_terpilih ?>&id_kelas=<?= $id_kelas_terpilih ?>"
                                target="_blank" class="btn bg-dark text-white mb-0">
                                <i class="fas fa-file-pdf"></i>&nbsp; Cetak PDF
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </form>
            <hr>

            <?php if (!empty($rekap_bulanan['siswa'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th rowspan="2" class="align-middle">No</th>
                                <th rowspan="2" class="align-middle">Nama Siswa</th>
                                <th colspan="<?= $rekap_bulanan['total_hari'] ?>">Tanggal</th>
                                <th colspan="3" class="th-tanggal">Jumlah</th>
                            </tr>
                            <tr>
                                <?php for ($tgl = 1; $tgl <= $rekap_bulanan['total_hari']; $tgl++): ?>
                                    <th class="th-tanggal"><?= $tgl ?></th>
                                <?php endfor; ?>
                                <th class="th-tanggal">S</th>
                                <th class="th-tanggal">I</th>
                                <th class="th-tanggal">A</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($rekap_bulanan['siswa'] as $siswa): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="text-start"><?= $siswa['nama_siswa'] ?></td>
                                    <?php foreach ($siswa['kehadiran'] as $status): ?>
                                        <td>
                                            <?php
                                            if ($status == 'S') echo "<span class='text-warning fw-bold'>S</span>";
                                            elseif ($status == 'I') echo "<span class='text-info fw-bold'>I</span>";
                                            elseif ($status == 'A') echo "<span class='text-danger fw-bold'>A</span>";
                                            elseif ($status == '0') echo "<span class='text-muted'>0</span>";
                                            else echo "H";
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td><?= $siswa['total_S'] ?></td>
                                    <td><?= $siswa['total_I'] ?></td>
                                    <td><?= $siswa['total_A'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($id_kelas_terpilih): ?>
                <p class="text-center text-muted">Tidak ada data siswa untuk kelas yang dipilih.</p>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>