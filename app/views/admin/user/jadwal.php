<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

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
<?php if ($msg = getFlash('danger')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#f44336',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>

<div class="container-fluid py-4">
    <div class="mb-3">
        <a href="?controller=user&method=index" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- ===== TABEL JADWAL USER ===== -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Jadwal <?= htmlspecialchars($user['nama']) ?></h6>
                    </div>
                </div>

                <div class="card-body px-4 pb-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addJadwalModal">
                            <i class="fas fa-plus"></i> Tambah Jadwal
                        </button>
                    </div>
                </div>

                <div class="card-body px-0 py-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Hari</th>
                                    <th>Waktu Datang</th>
                                    <th>Waktu Pulang</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $days = [
                                    'Monday' => 'Senin',
                                    'Tuesday' => 'Selasa',
                                    'Wednesday' => 'Rabu',
                                    'Thursday' => 'Kamis',
                                    'Friday' => 'Jumat',
                                    'Saturday' => 'Sabtu',
                                    'Sunday' => 'Minggu'
                                ];

                                $i = 1;
                                foreach ($jadwal as $j):
                                ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?= $days[$j['day']] ?? $j['day'] ?></td>
                                        <td><?= htmlspecialchars($j['waktu_datang']) ?></td>
                                        <td><?= htmlspecialchars($j['waktu_pulang']) ?></td>
                                        <td>
                                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#editJadwal<?= $j['id_jadwal'] ?>">Edit</button>
                                            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#deleteJadwal<?= $j['id_jadwal'] ?>">Hapus</button>
                                        </td>
                                    </tr>

                                    <!-- MODAL EDIT JADWAL -->
                                    <div class="modal fade" id="editJadwal<?= $j['id_jadwal'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="?controller=user&method=updateJadwal" method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Jadwal <?= $days[$j['day']] ?? $j['day'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?= $j['id_jadwal'] ?>">
                                                        <input type="hidden" name="id_employe" value="<?= $user['id_employe'] ?>">
                                                        <div class="mb-3">
                                                            <label>Hari</label>
                                                            <select class="form-select" name="day" required>
                                                                <?php foreach ($days as $key => $val): ?>
                                                                    <option value="<?= $key ?>" <?= $j['day'] == $key ? 'selected' : '' ?>><?= $val ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Waktu Datang</label>
                                                            <input type="time" class="form-control" name="in" value="<?= htmlspecialchars($j['waktu_datang']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Waktu Pulang</label>
                                                            <input type="time" class="form-control" name="out" value="<?= htmlspecialchars($j['waktu_pulang']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-success">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MODAL HAPUS JADWAL -->
                                    <div class="modal fade" id="deleteJadwal<?= $j['id_jadwal'] ?>" tabindex="-1" aria-labelledby="deleteJadwalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="?controller=user&method=deleteJadwal" method="POST">
                                                    <input type="hidden" name="id" value="<?= $j['id_jadwal'] ?>">
                                                    <input type="hidden" name="id_user" value="<?= $user['id_employe'] ?>">
                                                    <!-- Modal Body -->
                                                    <div class="modal-body">
                                                        <div class="text-center">
                                                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                                            <p class="fw-bold mb-1">Apakah Anda yakin ingin menghapus jadwal pada hari <strong><?= $j['day'] ?></strong>?</p>
                                                            <p class="text-muted mb-0">Tindakan ini tidak bisa dibatalkan!</p>
                                                        </div>

                                                    </div>

                                                    <!-- Modal Footer -->
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                <?php $i++;
                                endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH JADWAL -->
    <div class="modal fade" id="addJadwalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="?controller=user&method=addJadwal" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Jadwal <?= htmlspecialchars($user['nama']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_user" value="<?= htmlspecialchars($user['pin']) ?>">
                        <input type="hidden" name="id_employe" value="<?= $user['id_employe'] ?>">
                        <div class="mb-3">
                            <label>Hari</label>
                            <select class="form-select" name="day" required>
                                <?php foreach ($days as $key => $val): ?>
                                    <option value="<?= $key ?>"><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Waktu Datang</label>
                            <input type="time" class="form-control" name="in" required>
                        </div>
                        <div class="mb-3">
                            <label>Waktu Pulang</label>
                            <input type="time" class="form-control" name="out" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>
