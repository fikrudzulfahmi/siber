<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<style>
    body,
    .form-control,
    .accordion-button,
    .btn,
    .modal,
    .card,
    .list-group-item {
        font-family: 'Roboto', sans-serif !important;
    }

    /* Efek hover untuk list kategori */
    .list-group-item:hover {
        background-color: #cecece60;
        /* hijau muda */
        cursor: pointer;
        transition: 0.2s;
    }

    /* Efek hover untuk accordion header */
    .accordion-button:hover {
        background-color: #cecece60;
        transition: 0.2s;
    }
</style>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Laporan & Monitoring Nilai per Guru</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">

                    <form action="" method="GET" class="row mb-3">
                        <input type="hidden" name="controller" value="laporan">
                        <input type="hidden" name="method" value="index">

                        <div class="col-md-4">
                            <div class="input-group input-group-static">
                                <label for="id_tahun" class="ms-0">Pilih Tahun Ajaran (Arsip)</label>
                                <select name="id_tahun" id="id_tahun" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- Tampilkan Semua --</option>
                                    <?php foreach ($daftar_tahun as $th): ?>
                                        <option value="<?= $th['id_tahun_pelajaran'] ?>" <?= ($id_tahun == $th['id_tahun_pelajaran']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($th['tahun_pelajaran'] . ' - ' . $th['semester']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div class="input-group input-group-outline mb-4">
                        <input type="text" id="search-guru-input" class="form-control" placeholder="Ketik untuk mencari nama guru...">
                    </div>

                    <div class="accordion" id="accordionLaporan">
                        <?php foreach ($data_laporan_lengkap as $id_guru => $guru): ?>
                            <div class="accordion-item mb-3">
                                <h2 class="accordion-header">
                                    <button class="accordion-button border-bottom font-weight-bold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-guru-<?= $id_guru ?>" aria-expanded="false">
                                        <?= htmlspecialchars($guru['nama_guru']) ?>
                                        <i class="collapse-close fa fa-plus text-xs pt-1 position-absolute end-0 me-3"></i>
                                        <i class="collapse-open fa fa-minus text-xs pt-1 position-absolute end-0 me-3"></i>
                                    </button>
                                </h2>
                                <div id="collapse-guru-<?= $id_guru ?>" class="accordion-collapse collapse" data-bs-parent="#accordionLaporan">
                                    <div class="accordion-body">
                                        <div class="accordion" id="accordion-mapel-<?= $id_guru ?>">

                                            <?php if (!empty($guru['mapel_list'])): ?>
                                                <?php foreach ($guru['mapel_list'] as $key => $mapel): ?>
                                                    <?php
                                                    // 1. Ambil ID Guru dan ID Mapel untuk memastikan ID benar-benar unik
                                                    // 2. Buat ID HTML yang bersih (hanya Huruf dan Angka)
                                                    $clean_key = preg_replace("/[^a-zA-Z0-9]/", "", $key);
                                                    $accordion_id = "collapse-" . $clean_key . "-" . $id_guru;
                                                    ?>
                                                    <div class="accordion-item mb-2">
                                                        <h2 class="accordion-header">
                                                            <button class="accordion-button border-bottom collapsed"
                                                                type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#<?= $accordion_id ?>"
                                                                aria-expanded="false">
                                                                <?= htmlspecialchars($mapel['nama_mapel'] . ' - ' . $mapel['kelas']) ?>
                                                            </button>
                                                        </h2>

                                                        <div id="<?= $accordion_id ?>"
                                                            class="accordion-collapse collapse"
                                                            data-bs-parent="#accordion-mapel-<?= $id_guru ?>">

                                                            <div class="accordion-body">
                                                                <ul class="list-group">
                                                                    <?php if (!empty($mapel['kategori_list'])): ?>
                                                                        <?php foreach ($mapel['kategori_list'] as $kategori): ?>
                                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                <?= htmlspecialchars($kategori['kategori']) ?>
                                                                                <button class="btn btn-success btn-sm mb-0 btn-lihat-nilai"
                                                                                    data-id-kategori="<?= $kategori['id_kategori'] ?>">
                                                                                    Lihat Nilai
                                                                                </button>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <li class="list-group-item text-muted text-center py-2">
                                                                            <small>Belum ada kategori di semester ini.</small>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNilai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lembar Nilai Siswa (Read-Only)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalNilaiContent">
                <p class="text-center">Memuat data nilai...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btn-export-excel" class="btn btn-dark" target="_blank">
                    <i class="fas fa-file-excel"></i>&nbsp; Export Excel
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>

<script>
    $(document).ready(function() {
        $('#search-guru-input').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase().trim();
            $('#accordionLaporan > .accordion-item').each(function() {
                var guruAccordionItem = $(this);
                var guruName = guruAccordionItem.find('.accordion-button').first().text().toLowerCase().trim();
                if (guruName.includes(searchTerm)) {
                    guruAccordionItem.show();
                } else {
                    guruAccordionItem.hide();
                }
            });
        });

        $('#accordionLaporan').on('click', '.btn-lihat-nilai', function() {
            var idKategori = $(this).data('id-kategori');
            var modalContent = $('#modalNilaiContent');
            var btnExport = $('#btn-export-excel');
            var myModal = new bootstrap.Modal(document.getElementById('modalNilai'));
            myModal.show();
            modalContent.html('<p class="text-center">Memuat data nilai...</p>');
            btnExport.attr('href', '?controller=laporan&method=exportExcel&id_kategori=' + idKategori);
            $.ajax({
                url: '?controller=laporan&method=getNilaiAjax&id_kategori=' + idKategori,
                type: 'GET',
                success: function(response) {
                    modalContent.html(response);
                },
                error: function() {
                    modalContent.html('<p class="text-center text-danger">Gagal memuat data nilai.</p>');
                }
            });
        });
    });
</script>