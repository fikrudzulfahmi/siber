<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#4caf50',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php endif; ?>

    <?php if ($msg = getFlash('error')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#d33',
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
                        <h6 class="text-white text-capitalize ps-3">Daftar Jenis Perangkat Mengajar</h6>
                    </div>
                </div>

                <div class="my-4 mx-4">
                    <button type="button" class="btn btn-dark mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                        Tambah Jenis Perangkat
                    </button>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Jenis Perangkat</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($jenis_perangkat as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td>
                                            <?php if ($row['status'] == 1): ?>
                                                <span class="badge bg-gradient-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-danger">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success btn-sm edit-btn" 
                                                data-id="<?= $row['id'] ?>" 
                                                data-nama="<?= htmlspecialchars($row['nama']) ?>" 
                                                data-status="<?= $row['status'] ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal">
                                                Edit
                                            </button>
                                            <?php if ($row['status'] == 1): ?>
                                                <a href="?controller=jenisPerangkat&method=delete&id=<?= $row['id'] ?>"
                                                    class="btn btn-dark btn-sm delete-btn">
                                                    Nonaktifkan
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

<!-- Modal Tambah -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="?controller=jenisPerangkat&method=store" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Jenis Perangkat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group input-group-outline mb-3">
                        <label class="form-label">Nama Jenis Perangkat</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="?controller=jenisPerangkat&method=update" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Jenis Perangkat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="input-group input-group-outline mb-3 is-filled">
                        <label class="form-label">Nama Jenis Perangkat</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    
                    <div class="form-check form-switch ps-0 mt-3">
                        <input class="form-check-input ms-auto" type="checkbox" name="status" id="edit_status" value="1">
                        <label class="form-check-label text-body ms-3 text-truncate w-80 mb-0" for="edit_status">Aktifkan Perangkat</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Jenis perangkat ini akan dinonaktifkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, nonaktifkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const status = this.getAttribute('data-status');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            
            if (status == 1) {
                document.getElementById('edit_status').checked = true;
            } else {
                document.getElementById('edit_status').checked = false;
            }
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>
