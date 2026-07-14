<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
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

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Custom Style for Form Control -->
<style>
    .form-control {
        padding-left: 1rem !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Kelas</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=kelas&method=update" method="POST">
                        <input type="hidden" name="id_kelas" value="<?= $kelas['id_kelas'] ?>">
                        <!-- Nama Kelas -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Nama Kelas</label>
                            <input type="text" name="kelas"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                value="<?= $kelas['kelas'] ?>" required>
                        </div>

                        <!-- Tingkat -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tingkat</label>
                            <input type="number" name="tingkat"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                min="7" max="12" value="<?= $kelas['tingkat'] ?>" required>
                        </div>

                        <!-- Wali Kelas -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Wali Kelas</label>
                            <select name="walikelas"
                                class="form-control select2 border focus-ring focus-ring-success rounded-3"
                                required>
                                <option value="" disabled>-- Pilih Walikelas --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id_employe'] ?>" <?= $u['id_employe'] == $kelas['wali_kelas'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=kelas&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white px-4">Simpan</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JS: jQuery & Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Cari wali kelas...",
            width: '100%'
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>