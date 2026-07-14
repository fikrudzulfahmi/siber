<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>


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

<?php if ($msg = getFlash('danger')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#e53935'
            });
        });
    </script>
<?php endif; ?>

<style>
    /* Perbaiki lebar form control agar tidak melebihi parent */
    .form-control,
    .select2-container {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }

    /* Hapus overflow horizontal di container utama */
    body,
    html {
        overflow-x: hidden;
    }

    /* Pastikan form container padding responsif */
    .card-body {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* Responsif untuk padding dan layout */
    @media (max-width: 768px) {
        .row {
            flex-direction: column;
        }

        .col-md-4 {
            width: 100%;
        }

        .select2-container {
            width: 100% !important;
        }
    }

    /* Custom select2 tampilan tetap */
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

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">

            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3">Input Jurnal Struktural</h6>
                    </div>
                </div>
                <div class="my-4 mx-4">
                    <a href="?controller=jurnalStruktural&method=history"
                        class="btn btn-info mb-3">
                        <i class="material-icons">history</i> History Jurnal
                    </a>
                </div>
                <div class="card-body px-4 py-4">
                    <form action="?controller=jurnalStruktural&method=store" method="POST">

                        <!-- Tanggal -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date"
                                name="tanggal"
                                class="form-control border rounded-3"
                                value="<?= date('Y-m-d') ?>"
                                required>
                        </div>

                        <!-- Program Kerja -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-dark">Program Kerja</h6>
                                <button type="button"
                                    id="tambah-program"
                                    class="btn btn-success btn-sm">
                                    Tambah Program
                                </button>
                            </div>

                            <div class="card-body" id="program-container">
                                <div class="program-row border rounded-3 p-3 mb-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Program Kerja</label>
                                        <select name="program[0][id_program]"
                                            class="form-control border focus-ring focus-ring-success rounded-3 select2"
                                            required>
                                            <option value="">-- Pilih Program Kerja --</option>
                                            <?php foreach ($programKerja as $pk): ?>
                                                <option value="<?= $pk['id_program'] ?>">
                                                    <?= $pk['nama_program'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label fw-bold">Deskripsi Realisasi</label>
                                        <textarea name="program[0][deskripsi]"
                                            class="form-control border focus-ring focus-ring-success rounded-3"
                                            rows="2"
                                            required></textarea>
                                    </div>

                                    <button type="button"
                                        class="btn btn-danger btn-sm btn-hapus-program"
                                        style="display:none;">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Akhir -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Catatan dan Tindak Lanjut</label>
                            <textarea name="catatan_akhir"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                rows="3"></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit"
                                class="btn bg-gradient-success text-white px-4">
                                Simpan Jurnal
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $('.select2').select2();
    width: '100%';
    let index = 1;

    // Tambah program kerja
    $('#tambah-program').on('click', function() {
        let html = `
    <div class="program-row border rounded-3 p-3 mb-3">
        <div class="mb-3">
            <label class="form-label fw-bold">Program Kerja</label>
            <select name="program[${index}][id_program]"
                    class="form-control border focus-ring focus-ring-success rounded-3 select2"
                    required>
                <option value="">-- Pilih Program Kerja --</option>
                <?php foreach ($programKerja as $pk): ?>
                    <option value="<?= $pk['id_program'] ?>">
                        <?= $pk['nama_program'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-2">
            <label class="form-label fw-bold">Deskripsi Realisasi</label>
            <textarea name="program[${index}][deskripsi]"
                      class="form-control border focus-ring focus-ring-success rounded-3"
                      rows="2"
                      required></textarea>
        </div>

        <button type="button"
                class="btn btn-danger btn-sm btn-hapus-program">
            Hapus
        </button>
    </div>
    `;

        $('#program-container').append(html);
        $('.select2').select2();
        index++;
        toggleHapus();
    });

    // Hapus program
    $('#program-container').on('click', '.btn-hapus-program', function() {
        $(this).closest('.program-row').remove();
        toggleHapus();
    });

    function toggleHapus() {
        const rows = $('.program-row');
        if (rows.length > 1) {
            $('.btn-hapus-program').show();
        } else {
            $('.btn-hapus-program').hide();
        }
    }

    toggleHapus();
</script>

<?php include '../app/views/layouts/footer.php'; ?>