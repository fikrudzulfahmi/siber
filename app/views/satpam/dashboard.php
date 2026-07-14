<style>
    /* Ganti #datatable5 dengan ID tabel Anda */
    #datatable5 {
        table-layout: fixed;
        width: 100% !important;
    }

    /* Kelas untuk membungkus teks */
    .wrap-text {
        white-space: normal !important;
        /* Wajib ada untuk mengizinkan wrap */
        word-wrap: break-word;
        /* Memecah kata yang panjang */
    }
</style>
<div class="container-fluid py-4">
    <!-- Selamat Datang Card -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card bg-gradient-success shadow-success border-radius-lg">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="text-white mb-0">
                            Selamat Datang, <strong><?= $_SESSION['user']['nama'] ?? 'Pengguna' ?></strong>
                        </h5>
                        <p class="text-white mb-0">
                            Anda login sebagai <strong><?= levelDisplay($_SESSION['user']['level']) ?></strong>.
                        </p>
                    </div>
                    <div>
                        <i class="material-icons text-white opacity-10" style="font-size: 48px;">waving_hand</i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <!-- HEADER -->
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3 mb-0">
                            Daftar Izin Siswa
                        </h6>
                    </div>
                </div>
                <!-- TABLE -->
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable6"
                            class="table table-bordered table-striped align-items-center mb-0">
                            <thead class="text-center">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Keperluan</th>
                                    <th>Waktu Meninggalkan</th>
                                    <th>Waktu Kembali</th>
                                    <th>Rekomendasi</th>
                                    <th>Tindakan / Catatan</th>
                                    <?php if (isAnyLevel($id_level, [8])): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($izin as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $row['nama_siswa'] ?></td>
                                        <td><?= $row['nama_kelas'] ?></td>
                                        <td><?= $row['keperluan'] ?></td>
                                        <td><?= formatTanggalIndo($row['waktu_meninggalkan'], true, true) ?></td>
                                        <td><?= formatTanggalIndo($row['waktu_kembali'], true, true) ?></td>
                                        <td><?= $row['nama_rekom'] ?></td>
                                        <td>
                                            <?php
                                            $info = [];

                                            // Cek dan ubah teks keterangan
                                            if (!empty($row['keterangan'])) {
                                                $keterangan_text = '';
                                                if ($row['keterangan'] == 'tepat') {
                                                    $keterangan_text = 'Tepat Waktu';
                                                } elseif ($row['keterangan'] == 'terlambat') {
                                                    $keterangan_text = 'Terlambat';
                                                }

                                                if ($keterangan_text) {
                                                    $info[] = '<strong>' . $keterangan_text . '</strong>';
                                                }
                                            }

                                            // Tambahkan tindakan jika ada isinya
                                            if (!empty($row['tindakan'])) {
                                                $info[] = htmlspecialchars($row['tindakan']);
                                            }

                                            // Gabungkan info atau tampilkan strip jika kosong
                                            echo !empty($info) ? implode(' - ', $info) : '-';
                                            ?>
                                        </td>
                                        <?php if (isAnyLevel($id_level, [8])): ?>
                                            <td class="text-center">

                                                <!-- Tombol untuk menandai sudah kembali -->
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal" data-bs-target="#kembaliModal<?= $row['id_perizinan'] ?>">
                                                    Tandai Kembali
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals untuk menandai kembali -->
    <?php foreach ($izin as $rowModal): ?>
        <div class="modal fade" id="kembaliModal<?= $rowModal['id_perizinan'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="index.php?controller=dashboard&method=markKembali" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tandai Kembali: <?= htmlspecialchars($rowModal['nama_siswa']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_perizinan" value="<?= $rowModal['id_perizinan'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Waktu Kembali</label>
                            <input type="datetime-local" name="waktu_kembali" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($rowModal['waktu_kembali'] ?? date('Y-m-d H:i'))) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <select name="keterangan_kembali" class="form-select">
                                <option value="tepat" <?= (isset($rowModal['keterangan']) && $rowModal['keterangan'] == 'tepat') ? 'selected' : '' ?>>Tepat Waktu</option>
                                <option value="terlambat" <?= (isset($rowModal['keterangan']) && $rowModal['keterangan'] == 'terlambat') ? 'selected' : '' ?>>Terlambat</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tindakan / Catatan</label>
                            <textarea name="tindakan" class="form-control" rows="3"><?= htmlspecialchars($rowModal['tindakan'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>