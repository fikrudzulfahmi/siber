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
                        <h6 class="text-white text-capitalize ps-3">Daftar Kelas & Guru Mapel <?= $mapel['nama_mapel'] ?> Tingkat <?= $mapel['tingkat_mapel'] ?></h6>
                        <p class="text-white text-sm ps-3 mb-0">
                            Tahun Pelajaran: <strong><?= $tahun_aktif['tahun_pelajaran'] ?></strong> |
                            Semester: <strong><?= $tahun_aktif['semester'] ?></strong>
                        </p>
                    </div>
                </div>

                <?php if (!isLevel($id_level, 7)): ?>
                    <div class="my-4 mx-4">
                        <a href="?controller=mapel&method=create_guru&id=<?= $mapel['id_mapel'] ?>" class="btn btn-dark mb-3">Tambah Kelas & Guru</a>
                    </div>
                <?php endif; ?>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kelas</th>
                                    <th>Nama Guru</th>
                                    <?php if (!isLevel($id_level, 7)): ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guruList as $row): ?>
                                    <tr>
                                        <td></td>
                                        <td><?= $row['kelas'] ?></td>
                                        <td><?= $row['nama'] ?></td>
                                        <?php if (!isLevel($id_level, 7)): ?>
                                            <td>
                                                <a href="?controller=mapel&method=edit_guru&id=<?= $row['id_mapel_guru'] ?>" class="btn btn-success btn-sm">Edit</a>
                                                <a href="?controller=mapel&method=delete_guru&id=<?= $row['id_mapel_guru'] ?>&id_mapel=<?= $row['id_mapel'] ?>"
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
                <div class="d-flex justify-content-end">
                    <a href="?controller=mapel&method=index&id=<?= $mapel['id_mapel'] ?>" class="btn btn-outline-secondary me-2">Kembali</a>
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
                text: "Guru Pembelajaran yang dihapus tidak dapat dikembalikan!",
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