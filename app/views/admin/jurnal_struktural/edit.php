<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
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
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">

            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-warning shadow-warning border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3">Edit Jurnal Struktural</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=jurnalStruktural&method=update" method="POST">

                        <input type="hidden" name="id_jurnal" value="<?= $jurnal['id_jurnal'] ?>">

                        <!-- Tanggal -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date"
                                class="form-control"
                                value="<?= $jurnal['tanggal'] ?>"
                                readonly>
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
                                <?php foreach ($programList as $i => $p): ?>
                                    <div class="program-row border rounded-3 p-3 mb-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Program Kerja</label>
                                            <select name="program[<?= $i ?>][id_program]"
                                                class="form-control select2" required>

                                                <option value="">-- Pilih Program Kerja --</option>

                                                <?php foreach ($programKerja as $pk): ?>
                                                    <option value="<?= $pk['id_program'] ?>"
                                                        <?= $pk['id_program'] == $p['id_program'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($pk['nama_program']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-bold">Deskripsi Realisasi</label>
                                            <textarea name="program[<?= $i ?>][deskripsi_realisasi]"
                                                class="form-control border focus-ring focus-ring-success rounded-3"
                                                rows="2"
                                                required><?= htmlspecialchars($p['deskripsi_realisasi'] ?? '-') ?></textarea>
                                        </div>

                                        <button type="button"
                                            class="btn btn-danger btn-sm btn-hapus-program">
                                            Hapus
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Catatan Akhir -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Catatan Akhir</label>
                            <textarea name="catatan_akhir"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                rows="3"><?= htmlspecialchars($jurnal['catatan_akhir']) ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="?controller=jurnalStruktural&method=history"
                                class="btn btn-secondary me-2">
                                Batal
                            </a>
                            <button type="submit"
                                class="btn bg-gradient-success text-white px-4">
                                Simpan Perubahan
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
    $('.select2').select2({
        width: '100%'
    });
    let index = <?= count($programList) ?>;

    $('#tambah-program').on('click', function() {
        let html = `
        <div class="program-row border rounded-3 p-3 mb-3">
            <div class="mb-3">
                <label class="form-label fw-bold">Program Kerja</label>
                <select name="program[${index}][id_program]"
                        class="form-control border focus-ring focus-ring-success rounded-3 select2" required>
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
                <textarea name="program[${index}][deskripsi_realisasi]"
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
        $('.select2').select2({
            width: '100%'
        });
        index++;
    });

    $('#program-container').on('click', '.btn-hapus-program', function() {
        $(this).closest('.program-row').remove();
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>