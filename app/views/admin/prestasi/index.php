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

    .badge-juara {
        background-image: linear-gradient(195deg, #66BB6A 0%, #43A047 100%);
        color: white;
        padding: 0.5em 0.8em;
        border-radius: 0.5rem;
    }
</style>

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

    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Daftar Prestasi Siswa Kolektif</h6>
                        <a href="?controller=prestasi&method=tambah" class="btn btn-sm btn-outline-white me-3 mb-0">
                            <i class="fas fa-plus me-1"></i> Tambah Prestasi
                        </a>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="px-4 pb-3">
                        <form method="GET" action="index.php" class="d-flex align-items-center">
                            <input type="hidden" name="controller" value="prestasi">
                            <input type="hidden" name="method" value="index">
                            <span class="text-sm me-2">Tahun Pelajaran:</span>
                            <select name="id_tahun" class="form-control form-control-sm border ps-2 w-20" onchange="this.form.submit()">
                                <?php foreach ($list_tahun as $tp) : ?>
                                    <option value="<?= $tp['id_tahun_pelajaran']; ?>" <?= ($id_tahun_aktif == $tp['id_tahun_pelajaran']) ? 'selected' : ''; ?>>
                                        <?= $tp['tahun_pelajaran']; ?> - <?= $tp['semester']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kegiatan & Tanggal</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Juara / Tingkat</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Siswa (Tim)</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Sertifikat</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($daftar_prestasi)) : ?>
                                    <?php foreach ($daftar_prestasi as $p) : ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-3 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($p['nama_kegiatan']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0">
                                                            <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($p['tgl_kegiatan'])) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">
                                                    <span class="badge badge-sm badge-juara"><?= htmlspecialchars($p['juara']) ?></span>
                                                </p>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($p['tingkat']) ?></p>
                                            </td>
                                            <td class="align-middle text-sm">
                                                <span class="text-xs font-weight-bold" title="<?= htmlspecialchars($p['nama_peserta']) ?>">
                                                    <i class="fas fa-users me-1 text-info"></i>
                                                    <?= htmlspecialchars(mb_strimwidth($p['nama_peserta'], 0, 50, "...")) ?>
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php if ($p['file_sertifikat']) : ?>
                                                    <a href="../public/uploads/sertifikat/<?= $p['file_sertifikat'] ?>" target="_blank" class="text-secondary font-weight-bold text-xs">
                                                        <i class="fas fa-file-pdf text-danger me-1"></i> Lihat
                                                    </a>
                                                <?php else : ?>
                                                    <span class="text-xs text-secondary italic">Tidak ada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="btn-group shadow-none">
                                                    <a href="?controller=prestasi&method=edit&id=<?= $p['id_prestasi_kegiatan'] ?>"
                                                        class="btn btn-link text-warning font-weight-bold text-xs mb-0">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </a>
                                                    <a href="?controller=prestasi&method=hapus&id=<?= $p['id_prestasi_kegiatan'] ?>"
                                                        class="btn btn-link text-danger font-weight-bold text-xs mb-0"
                                                        onclick="return confirm('Hapus data prestasi ini? Daftar peserta terkait akan otomatis terhapus secara logis.')">
                                                        <i class="fas fa-trash me-1"></i> Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <p class="text-xs text-secondary mb-0">Belum ada data prestasi untuk tahun pelajaran ini.</p>
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