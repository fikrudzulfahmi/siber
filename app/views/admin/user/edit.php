<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    .form-control {
        padding-left: 1rem !important;
    }

    .form-select {
        padding-left: 1rem !important;
    }
</style>

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

<div class="container-fluid py-4">

    <!-- ===== FORM EDIT USER ===== -->
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit <?= $user['nama'] ?></h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=user&method=update" method="POST">
                        <input type="hidden" name="id" value="<?= $user['id_employe'] ?>">

                        <!-- Nama -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Nama</label>
                            <input type="text" name="nama" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $user['nama'] ?>" required>
                        </div>

                        <!-- PIN -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">PIN</label>
                            <input type="text" name="pin" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $user['pin'] ?>" required>
                        </div>

                        <!-- Jabatan -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $user['jabatan'] ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Kategori Jabatan</label>
                            <select name="id_jabatan" class="form-control border focus-ring focus-ring-success rounded-3" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($jabatans as $jabatan): ?>
                                    <option value="<?= $jabatan['id_jabatan'] ?>" <?= ($user['id_jabatan'] == $jabatan['id_jabatan']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($jabatan['jabatan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- No WA -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">No WA</label>
                            <input type="text" name="no_wa" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $user['no_wa'] ?>" required>
                        </div>

                        <!-- Username -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Username</label>
                            <input type="text" name="username" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $user['username'] ?>" required>
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Password <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                            <input type="password" name="password" class="form-control border focus-ring focus-ring-success rounded-3">
                        </div>

                        <!-- Level -->
                        <div class="mb-4">
                            <label for="levels" class="form-label text-dark fw-bold">Level Akses</label>

                            <select multiple class="form-select border focus-ring focus-ring-success rounded-3" id="levels" name="levels[]" size="6">

                                <?php foreach ($allLevels as $level): ?>
                                    <?php
                                    // Cek apakah level ini dimiliki oleh user
                                    $isSelected = in_array($level['id_level'], $userOwnedLevels);
                                    ?>
                                    <option value="<?= $level['id_level'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($level['nama_level']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                            <div class="form-text">
                                Tahan tombol Ctrl (atau Cmd di Mac) untuk memilih lebih dari satu level.
                            </div>
                        </div>

                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=user&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white px-4">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TABEL JADWAL USER ===== -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Jadwal <?= $user['nama'] ?></h6>
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
                                        <td><?= $days[$j['day']] ?? $j['day'] ?></td> <!-- tampil hari dalam bahasa Indonesia -->
                                        <td><?= $j['waktu_datang'] ?></td>
                                        <td><?= $j['waktu_pulang'] ?></td>
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
                                                            <input type="time" class="form-control" name="in" value="<?= $j['waktu_datang'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Waktu Pulang</label>
                                                            <input type="time" class="form-control" name="out" value="<?= $j['waktu_pulang'] ?>" required>
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
                        <h5 class="modal-title">Tambah Jadwal <?= $user['nama'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_user" value="<?= $user['pin'] ?>">
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