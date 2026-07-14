<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#4caf50',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php endif; ?>

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
                            Riwayat Jurnal Struktural
                        </h6>
                    </div>
                </div>

                <div class="my-4 mx-4">
                    <a href="?controller=jurnalStruktural&method=index"
                        class="btn btn-dark mb-3">
                        Kembali Input Jurnal
                    </a>
                </div>

                <div class="card-body px-4 py-4">
                    <div class="table-responsive">

                        <table class="table table-bordered align-items-center mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder">Tanggal</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Program Kerja</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Catatan dan Tindak Lanjut</th>
                                    <th class="text-uppercase text-xs font-weight-bolder text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($jurnals)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Belum ada jurnal struktural pada tahun pelajaran aktif
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jurnals as $j): ?>
                                        <tr>
                                            <td>
                                                <?= date('d-m-Y', strtotime($j['tanggal'])) ?>
                                            </td>

                                            <td>
                                                <?= strip_tags($j['ringkasan_program'], '<br><strong><hr><i>') ?>

                                            </td>

                                            <td>
                                                <?= htmlspecialchars($j['catatan_akhir'] ?? '-') ?>
                                            </td>

                                            <td class="text-center">
                                                <a href="?controller=jurnalStruktural&method=edit&id=<?= $j['id_jurnal'] ?>"
                                                    class="btn btn-sm btn-warning">
                                                    Edit
                                                </a>
                                            </td>
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