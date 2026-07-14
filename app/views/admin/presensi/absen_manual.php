<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<!-- Flash Message dengan SweetAlert -->
<?php if ($msg = getFlash('success')): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= $msg ?>',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
<?php elseif ($msg = getFlash('error')): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?= $msg ?>',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
<?php endif; ?>

<!-- Custom Style -->
<style>
    .form-control { padding-left: 1rem !important; }
    .form-select { padding-left: 1rem !important; }
</style>

<!-- Main Container -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">

            <!-- Card Absen Manual -->
            <div class="card my-4">
                
                <!-- Card Header -->
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 mb-4">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Absen Manual Pegawai</h6>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body p-3">
                    <form method="POST" action="index.php?controller=absenManual&method=store">

                        <!-- Pegawai -->
                        <div class="mb-3">
                            <label class="form-label">Pegawai</label>
                            <select name="pin" class="form-select border focus-ring focus-ring-success rounded-3" required>
                                <option value="">-- Pilih Pegawai --</option>
                                <?php foreach ($pegawai_list as $p): ?>
                                    <option value="<?= $p['pin'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tanggal -->
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control border focus-ring focus-ring-success rounded-3" required>
                        </div>

                        <!-- Jam -->
                        <div class="mb-3">
                            <label class="form-label">Jam</label>
                            <input type="time" name="jam" class="form-control border focus-ring focus-ring-success rounded-3" required>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select border focus-ring focus-ring-success rounded-3" required>
                                <option value="datang">Datang</option>
                                <option value="pulang">Pulang</option>
                            </select>
                        </div>

                        <!-- Keterangan -->
<div class="mb-3">
    <label class="form-label">Keterangan</label>
    <input type="text" 
           name="keterangan" 
           value="Hadir" 
           class="form-control border focus-ring focus-ring-success rounded-3" 
           readonly 
           required>
</div>


                        <!-- Submit -->
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">Simpan Absen</button>
                        </div>

                    </form>
                </div>
                <!-- End Card Body -->

            </div>
            <!-- End Card -->

        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>
