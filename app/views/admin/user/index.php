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
                        <h6 class="text-white text-capitalize ps-3">Daftar User</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Jabatan Spesifik</th>
                                    <th>Kategori Jabatan</th>
                                    <th>PIN</th>
                                    <th>No WA</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($user['nama']) ?></td>
                                        <td><?= htmlspecialchars($user['jabatan']) ?></td>
                                        <td>
                                            <span class="badge bg-gradient-secondary"><?= htmlspecialchars($user['kategori_jabatan'] ?? 'N/A') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($user['pin']) ?></td>
                                        <td><?= htmlspecialchars($user['no_wa']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td>
                                            <?php
                                            // Ubah string "1,5" menjadi array [1, 5] lalu tampilkan namanya
                                            $levels = !empty($user['user_levels']) ? explode(',', $user['user_levels']) : [];
                                            echo levelDisplay($levels);
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?controller=user&method=edit&id=<?= $user['id_employe'] ?>" class="btn btn-dark btn-sm">Edit</a>
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