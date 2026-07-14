<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    body,
    .form-control,
    .btn,
    .card,
    .table {
        font-family: 'Roboto', sans-serif !important;
    }

    .badge-status {
        font-size: 0.75rem;
        padding: 0.5em 0.8em;
    }
</style>

<div class="container-fluid py-4">
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
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Manajemen Ekstrakurikuler</h6>
                        <?php if (isAnyLevel($id_level, [1])): ?>
                            <a href="?controller=ekstra&method=tambah" class="btn btn-sm btn-outline-white me-3" title="Tambah Ekstrakurikuler">
                                Tambah Ekstra
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" id="tableEkstra">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Ekstrakurikuler</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pembina/Pengampu</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($daftar_ekstra)): ?>
                                    <?php foreach ($daftar_ekstra as $ex): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-3 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($ex['nama_ekstra']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0">TP: <?= $activeTahun['tahun_pelajaran'] ?? '-' ?> | Semester <?= $activeTahun['semester'] ?? '-' ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    <i class="fas fa-user-tie me-1"></i> <?= htmlspecialchars($ex['nama_pengampu']) ?>
                                                </p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-xs text-secondary font-weight-bold">
                                                    <?= !empty($ex['keterangan']) ? htmlspecialchars(mb_strimwidth($ex['keterangan'], 0, 40, "...")) : '-' ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="btn-group shadow-none">
                                                    <?php if (isAnyLevel($id_level, [1])): ?>
                                                        <a href="?controller=ekstra&method=edit&id_ekstra=<?= $ex['id_ekstra'] ?>"
                                                            class="btn btn-link text-warning font-weight-bold text-xs mb-0">
                                                            <i class="fas fa-edit me-1"></i> Edit
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="?controller=ekstra&method=anggota&id_ekstra=<?= $ex['id_ekstra'] ?>"
                                                        class="btn btn-link text-info font-weight-bold text-xs mb-0" title="Kelola Anggota">
                                                        <i class="fas fa-users me-1"></i> Anggota
                                                    </a>
                                                    <?php if ($is_locked): ?>
                                                        <button class="btn btn-link text-secondary font-weight-bold text-xs mb-0"
                                                            style="cursor: not-allowed;"
                                                            onclick="Swal.fire('Terkunci', 'Periode input nilai rapor belum dibuka atau sudah dikunci oleh Admin.', 'warning')"
                                                            title="Input Nilai Terkunci">
                                                            <i class="fas fa-lock me-1"></i> Nilai Rapor
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="?controller=ekstra&method=inputNilaiRapor&id_ekstra=<?= $ex['id_ekstra'] ?>"
                                                            class="btn btn-link text-primary font-weight-bold text-xs mb-0" title="Input Nilai Rapor">
                                                            <i class="fas fa-star me-1"></i> Nilai Rapor
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($ex['sudah_isi_jurnal']): ?>
                                                        <button class="btn btn-link text-secondary font-weight-bold text-xs mb-0"
                                                            onclick="Swal.fire('Sudah Diisi', 'Jurnal untuk hari ini sudah terdata. Silakan cek di Riwayat jika ingin mengubah.', 'info')">
                                                            <i class="fas fa-check-circle text-success me-1"></i> Terisi
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="?controller=ekstra&method=inputKegiatan&id_ekstra=<?= $ex['id_ekstra'] ?>"
                                                            class="btn btn-link text-success font-weight-bold text-xs mb-0" title="Input Jurnal">
                                                            <i class="fas fa-edit me-1"></i> Jurnal
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="?controller=ekstra&method=riwayat&id_ekstra=<?= $ex['id_ekstra'] ?>"
                                                        class="btn btn-link text-secondary font-weight-bold text-xs mb-0" title="Lihat Riwayat">
                                                        <i class="fas fa-history me-1"></i> Riwayat
                                                    </a>

                                                    <a href="?controller=ekstra&method=laporan&id_ekstra=<?= $ex['id_ekstra'] ?>"
                                                        class="btn btn-link text-secondary font-weight-bold text-xs mb-0" title="Lihat Laporan">
                                                        <i class="fas fa-file-alt me-1"></i> Laporan
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-xs text-secondary mb-0">Belum ada data ekstrakurikuler untuk tahun pelajaran ini.</p>
                                        </td>
                                    </tr>
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