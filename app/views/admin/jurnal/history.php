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
                    confirmButtonColor: '#4caf50'
                });
            });
        </script>
    <?php endif; ?>

    <style>
        .table-responsive .table {
            table-layout: fixed;
            width: 100%;
        }

        .table-responsive .table th,
        .table-responsive .table td {
            white-space: normal !important;
            word-wrap: break-word;
            vertical-align: top;
            font-size: 0.85rem;
        }

        .table th:nth-child(1) {
            width: 12%;
        }

        /* Tanggal */
        .table th:nth-child(2) {
            width: 10%;
        }

        /* Kelas */
        .table th:nth-child(3) {
            width: 15%;
        }

        /* Mapel */
        .table th:nth-child(4) {
            width: 10%;
        }

        /* Jam */
        .table th:nth-child(5) {
            width: 25%;
        }

        /* TP */
        .table th:nth-child(6) {
            width: 15%;
        }

        /* Catatan */
        .table th:nth-child(7) {
            width: 13%;
        }

        /* Aksi */
    </style>

    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Riwayat Jurnal Pembelajaran</h6>
                        <span class="badge bg-white text-success me-3">
                            Tahun: <?= $nama_tahun ?>
                        </span>
                    </div>
                </div>

                <div class="my-4 mx-4">
                    <button type="button" class="btn btn-dark mb-3" data-bs-toggle="modal" data-bs-target="#modalCetak">
                        <i class="fas fa-print me-1"></i> Cetak Laporan
                    </button>
                </div>

                <div class="modal fade" id="modalCetak" tabindex="-1" aria-labelledby="modalCetakLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="?controller=jurnal&method=cetakLaporan" method="POST" target="_blank">
                                <div class="modal-header">
                                    <h5 class="modal-title">Filter Laporan Jurnal</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Awal</label>
                                        <input type="date" name="tanggal_awal" class="form-control border px-2" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal Akhir</label>
                                        <input type="date" name="tanggal_akhir" class="form-control border px-2" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Cetak PDF</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-items-center mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder">Tanggal</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Kelas</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Mata Pelajaran</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Jam Ke-</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Tujuan Pembelajaran</th>
                                    <th class="text-uppercase text-xs font-weight-bolder">Catatan</th>
                                    <th class="text-uppercase text-xs font-weight-bolder text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jurnalList)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle mb-2" style="font-size: 2rem;"></i><br>
                                            Belum ada data jurnal pada Tahun Ajaran ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jurnalList as $j): ?>
                                        <tr>
                                            <td class="align-middle"><?= date('d-m-Y', strtotime($j['tanggal'])) ?></td>
                                            <td class="align-middle fw-bold"><?= $j['kelas'] ?></td>
                                            <td class="align-middle"><?= $j['nama_mapel'] ?></td>
                                            <td class="align-middle text-center"><?= $j['jam_mulai'] ?> - <?= $j['jam_akhir'] ?></td>
                                            <td class="align-middle text-sm">
                                                <?php
                                                if (!empty($j['tujuan_pembelajaran_list'])) {
                                                    echo '&bull; ' . $j['tujuan_pembelajaran_list'];
                                                } else {
                                                    echo '<span class="text-muted text-xs">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="align-middle text-xs">
                                                <?= !empty($j['catatan_pembelajaran']) ? $j['catatan_pembelajaran'] : '-' ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex flex-column gap-1">
                                                    <a href="?controller=jurnal&method=edit&id=<?= $j['id_jurnal'] ?>" class="btn btn-xs btn-warning mb-0">Edit</a>
                                                    <a href="?controller=jurnal&method=hapus&id=<?= $j['id_jurnal'] ?>" class="btn btn-xs btn-dark mb-0 delete-btn">Hapus</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <a href="?controller=jurnal&method=index" class="btn btn-outline-secondary">Kembali ke Input</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Hapus Jurnal?',
                text: "Data kehadiran siswa pada jurnal ini juga akan terhapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });
</script>
<?php include '../app/views/layouts/footer.php'; ?>