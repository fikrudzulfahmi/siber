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
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Daftar Kegiatan Guru & Struktural</h6>
                        <a href="?controller=kegiatan&method=tambah" class="btn btn-sm btn-outline-white me-3 mb-0">
                            <i class="fas fa-plus me-2"></i>Tambah Kegiatan
                        </a>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0" id="tableKegiatan">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kegiatan</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Waktu</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($daftar_kegiatan)): ?>
                                    <?php foreach ($daftar_kegiatan as $kg): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-3 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($kg['nama_kegiatan']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0">ID: #<?= $kg['id_kegiatan'] ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    <i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y', strtotime($kg['tanggal'])) ?>
                                                </p>
                                                <p class="text-xs text-secondary mb-0">
                                                    <i class="far fa-clock me-1"></i> <?= substr($kg['jam_mulai'], 0, 5) ?> - <?= substr($kg['jam_selesai'], 0, 5) ?>
                                                </p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="text-xs text-secondary font-weight-bold">
                                                    <?= !empty($kg['keterangan']) ? htmlspecialchars(mb_strimwidth($kg['keterangan'], 0, 30, "...")) : '-' ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a href="?controller=kegiatan&method=detail&id_kegiatan=<?= $kg['id_kegiatan'] ?>"
                                                    class="btn btn-link text-success font-weight-bold text-xs mb-0 px-2">
                                                    <i class="fas fa-eye me-1"></i> Detail
                                                </a>

                                                <a href="?controller=kegiatan&method=edit&id_kegiatan=<?= $kg['id_kegiatan'] ?>"
                                                    class="btn btn-link text-info font-weight-bold text-xs mb-0 px-2">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>

                                                <a href="?controller=kegiatan&method=hapus&id_kegiatan=<?= $kg['id_kegiatan'] ?>"
                                                    class="btn btn-link text-danger font-weight-bold text-xs mb-0 px-2"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus kegiatan ini beserta seluruh datanya?');">
                                                    <i class="fas fa-trash me-1"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <p class="text-xs text-secondary mb-0">Belum ada data kegiatan.</p>
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