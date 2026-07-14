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
                        <h6 class="text-white ps-3">
                            Edit Program Kerja
                        </h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">

                    <form method="post" action="?controller=programKerja&method=update">

                        <!-- ID PROGRAM -->
                        <input type="hidden" name="id_program"
                            value="<?= htmlspecialchars($program['id_program']) ?>">

                        <div class="mb-3">
                            <label class="form-label">Nama Program Kerja</label>
                            <input type="text"
                                name="nama_program"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                required
                                value="<?= htmlspecialchars($program['nama_program']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SOP Program Kerja</label>
                            <textarea name="deskripsi_default"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                rows="4"><?= htmlspecialchars($program['deskripsi_default']) ?></textarea>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                Simpan Perubahan
                            </button>

                            <a href="?controller=programKerja&method=index"
                                class="btn btn-secondary">
                                Kembali
                            </a>
                        </div>

                    </form>

                </div>

            </div>
        </div>
    </div>

</div>

<?php include '../app/views/layouts/footer.php'; ?>