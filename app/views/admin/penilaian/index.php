<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>


<div class="container-fluid py-4">

    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= sec_js($msg) ?>, // agar aman dari karakter khusus
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
                        <h6 class="text-white text-capitalize ps-3">Daftar Mata Pelajaran</h6>
                    </div>
                </div>


                <div class="card-body px-0 pb-2">
                    <?php if (function_exists('sec_has_level') && sec_has_level([1])): ?>
                    <div class="px-4 pt-3">
                        <form method="post" action="index.php?controller=kategori&method=syncAll"
                              onsubmit="return confirm('Sinkronkan baris nilai untuk SEMUA kelas? Hanya menambah baris yang belum ada, nilai lama tidak berubah.');">
                            <button type="submit" class="btn btn-outline-success btn-sm mb-0">
                                <i class="fas fa-sync"></i> Sinkronkan Semua Nilai
                            </button>
                            <small class="text-muted ms-2">Untuk siswa baru yang masuk setelah kategori dibuat.</small>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kelas</th>
                                    <th>Kode Mapel</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Tingkat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kelasList as $row): ?>
                                    <tr>
                                        <td></td>
                                        <td><?= sec_e($row['kelas']) ?></td>
                                        <td><?= sec_e($row['kode_mapel']) ?></td>
                                        <td><?= sec_e($row['nama_mapel']) ?></td>
                                        <td><?= sec_e($row['tingkat_mapel']) ?></td>
                                        <td>
                                            <a href="?controller=kategori&method=index&id_mapel_guru=<?= sec_e($row['id_mapel_guru']) ?>" class="btn btn-success btn-sm">Detail</a>
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

<?php include '../app/views/layouts/footer.php'; ?>