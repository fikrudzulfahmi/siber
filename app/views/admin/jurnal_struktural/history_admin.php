<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">


    <style>
        .table-responsive .table {
            table-layout: fixed;
            width: 100%;
        }

        .table th,
        .table td {
            white-space: normal !important;
            overflow-wrap: break-word;
            vertical-align: middle;
        }

        /* Atur lebar kolom */
        .table th:nth-child(1) {
            width: 15%;
        }

        /* Tanggal */
        .table th:nth-child(2) {
            width: 45%;
        }

        /* Program Kerja */
        .table th:nth-child(3) {
            width: 25%;
        }

        /* Catatan Akhir */
        .table th:nth-child(4) {
            width: 15%;
        }

        /* Aksi */
    </style>

    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            Rekap Jurnal Struktural
                        </h6>
                    </div>
                </div>

                <form method="GET" class="row mb-3 p-3 mt-3 align-items-end">
                    <input type="hidden" name="controller" value="jurnalStruktural">
                    <input type="hidden" name="method" value="historyAdmin">

                    <div class="col-md-3">
                        <label class="form-label fw-bold text-xs">Dari Tanggal</label>
                        <input type="date" name="tanggal_awal"
                            value="<?= sec_e($tanggalAwal) ?>"
                            class="form-control border focus-ring focus-ring-success rounded-3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-xs">Sampai Tanggal</label>
                        <input type="date" name="tanggal_akhir"
                            value="<?= sec_e($tanggalAkhir) ?>"
                            class="form-control border focus-ring focus-ring-success rounded-3">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-success">Tampilkan</button>
                        <a href="?controller=jurnalStruktural&method=cetakLaporan&tanggal_awal=<?= sec_e($tanggalAwal) ?>&tanggal_akhir=<?= sec_e($tanggalAkhir) ?>"
                            class="btn btn-outline-dark" target="_blank">
                            <i class="material-icons-round me-1">print</i> Cetak Laporan
                        </a>
                    </div>
                </form>

                <div class="card-body px-4 py-4">
                    <div class="table-responsive p-3">

                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama</th>
                                    <th>Program kerja</th>
                                    <th>Catatan dan Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jurnalList)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Tidak ada jurnal pada periode
                                            <?= date('d-m-Y', strtotime($tanggalAwal)) ?> s.d
                                            <?= date('d-m-Y', strtotime($tanggalAkhir)) ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jurnalList as $row): ?>
                                        <tr>
                                            <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                                            <td><?= $row['ringkasan_program'] ?: '-' ?></td>
                                            <td><?= htmlspecialchars($row['catatan_akhir'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>


                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>