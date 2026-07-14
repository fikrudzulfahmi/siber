<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>


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
                        <h6 class="text-white text-capitalize ps-3">Daftar Tujuan Pembelajaran Mapel <?= $info['nama_mapel'] ?> Kelas <?= $info['kelas'] ?></h6>
                    </div>
                </div>


                <div class="my-4 mx-4">
                    <a href="?controller=tp&method=create&id_mapel_guru=<?= $info['id_mapel_guru'] ?>"
                        class="btn btn-dark mb-1">Tambah Tujuan Pembelajaran</a>
                    <a href="?controller=tp&method=index" class="btn btn-outline-secondary me-2 float-end">Kembali</a>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tujuan Pembelajaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tujuanList as $row): ?>
                                    <tr>
                                        <td></td>
                                        <td><?= $row['tujuan_pembelajaran'] ?></td>
                                        <td>
                                            <a href="?controller=tp&method=edit&id=<?= $row['id_tp'] ?>" class="btn btn-success btn-sm">Edit</a>
                                            <a href="?controller=tp&method=delete&id=<?= $row['id_tp'] ?>&id_mapel_guru=<?= $row['id_mapel_guru'] ?>" class="btn btn-dark btn-sm" onclick="return confirm('Yakin ingin menghapus?')">Hapus</a>
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