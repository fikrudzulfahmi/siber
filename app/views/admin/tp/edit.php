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

    .select2-results__option {
        padding-left: 1.5rem !important;
        position: relative;
    }

    .select2-results__option::before {
        content: "☐";
        position: absolute;
        left: 0.5rem;
        color: #aaa;
    }

    .select2-results__option--selected::before {
        content: "☑";
        color: #28a745;
    }
</style>
<style>
    .form-check-custom input[type="checkbox"]:checked~.form-check-label .checked-icon {
        display: inline-block !important;
    }

    .form-check-custom input[type="checkbox"]:checked~.form-check-label .unchecked-icon {
        display: none !important;
    }

    .form-check-custom .mdi {
        font-size: 1.2rem;
        top: 0.1rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Tujuan Pembelajaran</h6>
                        <p class="text-white text-sm ps-3 mb-0">
                            Periode: <strong><?= $tahun_aktif['tahun_pelajaran'] ?></strong> (<?= $tahun_aktif['semester'] ?>)
                        </p>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=tp&method=update" method="POST">
                        <input type="hidden" name="id_mapel_guru" value="<?= $tp['id_mapel_guru'] ?>">
                        <input type="hidden" name="id_tp" value="<?= $tp['id_tp'] ?>">
                        <!-- Nama Kelas -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tujuan Pembelajaran</label>
                            <input type="text" name="tp"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                value="<?= $tp['tujuan_pembelajaran'] ?>" required>
                        </div>

                        <!-- Tingkat -->


                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=tp&method=tujuan&id_mapel_guru=<?= $tp['id_mapel_guru'] ?>" class="btn btn-outline-secondary me-2">Kembali</a>
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
<script>
    $('#search-guru').on('keyup', function() {
        var keyword = $(this).val().toLowerCase();
        $('#guru-list .form-check').each(function() {
            var label = $(this).text().toLowerCase();
            $(this).toggle(label.includes(keyword));
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>