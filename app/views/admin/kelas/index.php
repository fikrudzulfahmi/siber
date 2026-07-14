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
                        <h6 class="text-white text-capitalize ps-3">Daftar Kelas</h6>
                    </div>
                </div>


                <?php if (!isLevel($id_level, 7)): ?>
                    <div class="my-4 mx-4">
                        <a href="?controller=kelas&method=create" class="btn btn-dark mb-3">Tambah Kelas</a>
                    </div>
                <?php endif; ?>


                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Kelas</th>
                                    <th>Kelas</th>
                                    <th>Tingkat</th>
                                    <th>Walikelas</th>
                                    <?php if (!isLevel($id_level, 7)): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kelas as $row): ?>
                                    <tr>
                                        <td></td>
                                        <td><?= $row['id_kelas'] ?></td>
                                        <td><?= $row['kelas'] ?></td>
                                        <td><?= $row['tingkat'] ?></td>
                                        <td><?= $row['nama'] ?></td>
                                        <?php if (!isLevel($id_level, 7)): ?>
                                            <td>
                                                <a href="?controller=kelas&method=edit&id=<?= $row['id_kelas'] ?>" class="btn btn-success btn-sm">Edit</a>
                                                <a href="?controller=kelas&method=delete&id=<?= $row['id_kelas'] ?>"
                                                    class="btn btn-dark btn-sm delete-btn">
                                                    Hapus
                                                </a>
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
</div>

<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link langsung berjalan
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Kelas yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href; // Lanjutkan ke link hapus jika dikonfirmasi
                }
            });
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>