<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Rekap Kehadiran Harian Kelas</h6>
            </div>
        </div>
        <div class="card-body px-4 py-4">
            <form method="POST">
                <div style="display: flex; align-items: flex-end; gap: 1rem;" class="flex-wrap">

                    <div>
                        <label for="tanggal" class="form-label">Pilih Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal_terpilih) ?>" class="form-control border px-2">
                    </div>

                    <div>
                        <label for="id_kelas" class="form-label">Pilih Kelas</label>
                        <select name="id_kelas" id="id_kelas" class="form-control border px-2">
                            <option value="semua" <?= ($id_kelas_terpilih == 'semua') ? 'selected' : '' ?>>Semua Kelas</option>
                            <?php foreach ($daftar_kelas as $kelas): ?>
                                <option value="<?= $kelas['id_kelas'] ?>" <?= ($id_kelas_terpilih == $kelas['id_kelas']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['kelas']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-success mb-0">Tampilkan</button>
                        <a href="?controller=kehadiran&method=cetakPdf&tanggal=<?= urlencode($tanggal_terpilih) ?>&id_kelas=<?= urlencode($id_kelas_terpilih) ?>" class="btn btn-dark mb-0" target="_blank">
                            <i class="material-icons opacity-10">print</i> Cetak PDF
                        </a>
                    </div>

                </div>
            </form>
            <hr class="my-4">

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-items-center mb-0">
                    <thead>
                        <tr class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">
                            <th>Kelas</th>
                            <th>Jam Ke-</th>
                            <th>Mata Pelajaran</th>
                            <th>Guru Pengajar</th>
                            <th>Status Jurnal</th>
                            <th>Siswa Tidak Hadir (S, I, A)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jadwal_harian)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Tidak ada jadwal pelajaran untuk hari atau kelas yang dipilih.</td>
                            </tr>
                            <?php else:
                            $kelas_sekarang = '';
                            foreach ($jadwal_harian as $jadwal):
                            ?>
                                <tr>
                                    <td>
                                        <?php
                                        if ($kelas_sekarang != $jadwal['nama_kelas']) {
                                            echo '<b>' . htmlspecialchars($jadwal['nama_kelas']) . '</b>';
                                            $kelas_sekarang = $jadwal['nama_kelas'];
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $jadwal['jam_mulai'] ? htmlspecialchars($jadwal['jam_mulai'] . ' - ' . $jadwal['jam_selesai']) : '<b class="text-info">LIBUR</b>' ?>
                                    </td>
                                    <td><?= htmlspecialchars($jadwal['nama_mapel'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($jadwal['nama_guru'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if ($jadwal['jam_mulai']): ?>
                                            <span class="badge bg-gradient-<?= $jadwal['sudah_isi_jurnal'] ? 'success' : 'danger' ?>">
                                                <?= $jadwal['sudah_isi_jurnal'] ? 'Terisi' : 'Kosong' ?>
                                            </span>
                                        <?php else: echo '-';
                                        endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $siswa_tidak_hadir = [];
                                        if ($jadwal['jam_mulai']) { // Pastikan ada jadwal
                                            // ... (logika 'for' loop untuk mengambil data siswa tidak hadir tetap sama) ...
                                            for ($jam = (int)$jadwal['jam_mulai']; $jam <= (int)$jadwal['jam_selesai']; $jam++) {
                                                if (isset($kehadiran[$jadwal['id_kelas']][$jam])) {
                                                    foreach ($kehadiran[$jadwal['id_kelas']][$jam] as $info_siswa) {
                                                        $nama_siswa = explode(" (", $info_siswa)[0];
                                                        $siswa_tidak_hadir[$nama_siswa] = ltrim($info_siswa, '- ');
                                                    }
                                                }
                                            }
                                        }

                                        // ✅ LOGIKA BARU DI SINI
                                        if ($jadwal['sudah_isi_jurnal']) {
                                            // Jika jurnal sudah terisi
                                            echo empty($siswa_tidak_hadir) ? '<span class="text-success">- Hadir Semua -</span>' : implode('<br>', $siswa_tidak_hadir);
                                        } else {
                                            // Jika jurnal masih kosong
                                            echo '<span class="text-danger">Belum ada data jurnal</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>