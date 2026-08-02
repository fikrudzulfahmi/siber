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
                            <label class="form-label text-dark fw-bold">Level Akses</label>

                            <div class="row px-2 mt-2">
                                <?php foreach ($allLevels as $level): ?>
                                    <?php
                                    // Cek apakah level ini dimiliki oleh user
                                    $isSelected = in_array($level['id_level'], $userOwnedLevels);
                                    ?>
                                    <div class="col-md-4 col-sm-6 mb-2">
                                        <div class="form-check custom-control custom-checkbox">
                                            <input class="form-check-input border-secondary focus-ring focus-ring-success" 
                                                   type="checkbox" 
                                                   name="levels[]" 
                                                   value="<?= $level['id_level'] ?>" 
                                                   id="level_<?= $level['id_level'] ?>"
                                                   <?= $isSelected ? 'checked' : '' ?>
                                                   style="cursor: pointer;">
                                            <label class="form-check-label text-dark" for="level_<?= $level['id_level'] ?>" style="cursor: pointer;">
                                                <?= htmlspecialchars($level['nama_level']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="form-text mt-2">
                                Anda dapat mencentang lebih dari satu level akses.
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

</div>

<?php include '../app/views/layouts/footer.php'; ?>