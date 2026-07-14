<div class="container-fluid py-4">

    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($msg) ?>, // agar aman dari karakter khusus
                    confirmButtonColor: '#4caf50',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php endif; ?>


    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Daftar Konseling</h6>
                    </div>
                </div>
                <?php if (!isLevel($id_level, [1, 6, 7])): ?>
                    <div class="my-4 mx-4">
                        <a href="?controller=konseling&method=create" class="btn btn-dark mb-3">Tambah Konseling</a>
                    </div>
                <?php endif; ?>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th style="white-space: normal; word-wrap: break-word; max-width: 200px;">Nama Siswa</th>
                                    <th style="white-space: normal; word-wrap: break-word; max-width: 200px;">Kategori</th>
                                    <th style="white-space: normal; word-wrap: break-word; max-width: 500px;">
                                        Permasalahan
                                    </th>

                                    <th>Tanggal</th>
                                    <th style="white-space: normal; word-wrap: break-word; max-width: 200px;">Petugas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($konseling as $k): ?>
                                    <tr>
                                        <td><?= $k['id_konseling'] ?></td>
                                        <td style="white-space: normal; word-wrap: break-word; max-width: 200px;"><?= $k['nama_siswa'] ?></td>
                                        <td style="white-space: normal; word-wrap: break-word; max-width: 200px;"><?= htmlspecialchars($k['nama_kategori']) ?></td>
                                        <td style="white-space: normal; word-wrap: break-word; max-width: 500px;">
                                            <?= $k['permasalahan'] ?>
                                        </td>

                                        <td><?= formatTanggalIndo($k['tanggal_masalah'], false, false) ?></td>


                                        <td style="white-space: normal; word-wrap: break-word; max-width: 200px;">
                                            <?php foreach ($k['nama_petugas'] as $petugas): ?>
                                                <span class="badge bg-dark me-1 mb-1"><?= htmlspecialchars($petugas) ?></span>
                                            <?php endforeach; ?>
                                        </td>



                                        <td>
                                            <?php if ($k['status'] === 'Selesai'): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">Berlangsung</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?controller=konseling&method=tindakLanjut&id=<?= $k['id_konseling'] ?>"
                                                class="btn btn-dark btn-sm">Tindak Lanjut</a>

                                            <?php if ($k['status'] === 'Berlangsung'): ?>
                                                <?php if (!isLevel($id_level, 7)): ?>
                                                    <a href="?controller=konseling&method=selesai&id=<?= $k['id_konseling'] ?>"
                                                        class="btn btn-success btn-sm"
                                                        onclick="return confirm('Tandai sebagai selesai?')">Selesai
                                                    <?php endif; ?>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($k['status'] === 'Selesai'): ?>
                                                    <a href="?controller=konseling&method=cetak&id=<?= $k['id_konseling'] ?>"
                                                        class="btn btn-sm btn-outline-info" target="_blank">
                                                        <i class="fas fa-print"></i> Cetak
                                                    </a>
                                                <?php endif; ?>

                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>


                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>