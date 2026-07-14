<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#4caf50'
                });
            });
        </script>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3">Program Kerja</h6>
                    </div>
                </div>

                <div class="my-4 mx-4">
                    <a href="?controller=programKerja&method=create" class="btn btn-dark">
                        Tambah Program Kerja
                    </a>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Program Kerja</th>
                                    <th>Pembuat</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($programKerja)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Belum ada program kerja
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1;
                                    foreach ($programKerja as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_program']) ?></strong><br>
                                                <small class="text-muted">
                                                    <?= nl2br(htmlspecialchars($row['deskripsi_default'])) ?>
                                                </small>
                                            </td>
                                            <td><?= $row['nama'] ?? '-' ?></td>
                                            <td>
                                                <a href="?controller=programKerja&method=edit&id=<?= $row['id_program'] ?>"
                                                    class="btn btn-success btn-sm">
                                                    Edit
                                                </a>
                                                <a href="?controller=programKerja&method=delete&id=<?= $row['id_program'] ?>"
                                                    class="btn btn-dark btn-sm delete-btn">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                text: "Siswa yang dihapus tidak dapat dikembalikan!",
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