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
                        <h6 class="text-white text-capitalize ps-3">Daftar Tahun Pelajaran</h6>
                    </div>
                </div>


                <div class="my-4 mx-4">
                    <a href="?controller=tahunPelajaran&method=create" class="btn btn-dark mb-3">Tambah Tahun Pelajaran</a>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tahun Pelajaran</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tahun as $row): ?>
                                    <tr>
                                        <td></td>
                                        <td><?= $row['tahun_pelajaran'] ?></td>
                                        <td><?= $row['semester'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center" style="gap: 5px;">
                                                <?php if ($row['status'] == 'Aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center" style="gap: 5px;">

                                                <?php if ($row['status'] != 'Aktif'): ?>
                                                    <a href="?controller=tahunPelajaran&method=activate&id=<?= $row['id_tahun_pelajaran'] ?>" class="btn btn-info btn-sm activate-btn">Aktifkan</a>
                                                <?php endif; ?>

                                                <a href="?controller=tahunPelajaran&method=edit&id=<?= $row['id_tahun_pelajaran'] ?>" class="btn btn-success btn-sm">Edit</a>

                                                <a href="?controller=tahunPelajaran&method=delete&id=<?= $row['id_tahun_pelajaran'] ?>" class="btn btn-dark btn-sm delete-btn">Hapus</a>

                                            </div>
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

<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link langsung berjalan
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Tahun Pelajaran yang dihapus tidak dapat dikembalikan!",
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

    document.querySelectorAll('.activate-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Aktifkan Tahun Pelajaran?',
                text: "Tahun pelajaran lain yang aktif akan otomatis dinonaktifkan.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4caf50', // Warna hijau
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Ya, aktifkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href; // Lanjutkan ke link jika dikonfirmasi
                }
            });
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>