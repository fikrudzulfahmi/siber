<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<style>
    .form-control {
        padding-left: 1rem !important;
    }

    .form-select {
        padding-left: 1rem !important;
    }

    #datatable {
        table-layout: fixed !important;
        width: 100% !important;
    }

    /* 2. Atur sel yang butuh wrap (diperbarui) */
    #datatable td.wrap-text,
    #datatable th.wrap-text {
        white-space: normal !important;
        /* Izinkan teks untuk wrap secara normal */
        word-wrap: break-word !important;
        /* Ini akan mematahkan teks di antara kata (di spasi) */
        /* Pastikan word-break: break-all; sudah dihapus atau tidak ada */
    }
</style>
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
<?php elseif ($msg = getFlash('error')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#f44336',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>


<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 mb-4">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Data Izin Pegawai</h6>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#tambahIzinModal">
                            + Tambah Izin
                        </button>
                    </div>

                    <div class="table-responsive p-0">


                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 3%;">No</th>
                                    <th style="width: 20%;">Nama</th>
                                    <th style="width: 7%;">Jenis</th>
                                    <th style="width: 20%;">Tanggal</th>
                                    <th style="width: 30%;">Keterangan</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 10%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $user = $_SESSION['user'];
                                $id_level = $_SESSION['user']['level'] ?? null;

                                // =================================================================
                                // BAGIAN KRITIS: Membuat pemetaan PIN ke Nama Pegawai.
                                // Pastikan blok ini ada persis sebelum loop utama.
                                // =================================================================
                                $pegawaiMap = [];
                                foreach ($pegawai_list as $p) {
                                    $pegawaiMap[$p['pin']] = trim($p['nama']);
                                }

                                // Loop utama untuk menampilkan setiap baris data izin
                                foreach ($izin_list as $izin):

                                    // Filter: Hanya tampilkan izin milik sendiri, kecuali untuk admin/pimpinan
                                    if (!isAnyLevel($id_level, [1, 5, 7]) && $user['pin'] != $izin['pin']) {
                                        continue; // Lanjut ke data berikutnya jika tidak sesuai
                                    }
                                ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $no++; ?></td>

                                        <td class="wrap-text align-middle">
                                            <?= htmlspecialchars($pegawaiMap[$izin['pin']] ?? 'Nama Tidak Ditemukan') ?>
                                        </td>

                                        <td class="text-center align-middle">
                                            <span class="badge bg-dark"><?= htmlspecialchars($izin['jenis']) ?></span>
                                        </td>

                                        <td class="wrap-text align-middle">
                                            <?= date('d M Y', strtotime($izin['tanggal_mulai'])) ?> s/d <?= date('d M Y', strtotime($izin['tanggal_selesai'])) ?>
                                        </td>

                                        <td class="wrap-text align-middle">
                                            <?= htmlspecialchars($izin['keterangan'] ?? '-') ?>

                                            <?php
                                            // JIKA statusnya ditolak DAN alasan penolakannya tidak kosong
                                            if ($izin['status_approval'] == 'ditolak' && !empty($izin['alasan_ditolak'])):
                                            ?>
                                                <hr class="my-1 mx-0 p-0">
                                                <small class="text-danger">
                                                    <strong>Keterangan:</strong> <?= htmlspecialchars($izin['alasan_ditolak']) ?>
                                                </small>
                                            <?php endif; ?>

                                            <?php
                                            // Tampilkan catatan persetujuan jika status disetujui dan catatan tidak kosong
                                            if ($izin['status_approval'] == 'disetujui' && !empty($izin['catatan_approval'])):
                                            ?>
                                                <hr class="my-1 mx-0 p-0">
                                                <small class="text-success">
                                                    <strong>Keterangan:</strong> <?= htmlspecialchars($izin['catatan_approval']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center align-middle">
                                            <?php if ($izin['status_approval'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php elseif ($izin['status_approval'] == 'disetujui'): ?>
                                                <span class="badge bg-success">Disetujui</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Ditolak</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center align-middle">
                                            <?php
                                            $canEdit = isAnyLevel($id_level, [1, 5, 7]) || ($user['pin'] == $izin['pin']);
                                            if ($izin['status_approval'] === 'pending' && $canEdit):
                                            ?>
                                                <button class="btn btn-success btn-sm my-1" data-bs-toggle="modal" data-bs-target="#editIzinModal<?= $izin['id'] ?>">Edit</button>

                                                <a href="index.php?controller=izinGuru&method=delete&id=<?= $izin['id'] ?>" class="btn btn-dark btn-sm my-1 delete-btn">Hapus</a>
                                            <?php else: ?>
                                                <small class="text-muted">Tidak ada aksi</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahIzinModal" tabindex="-1" aria-labelledby="tambahIzinModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?controller=izinGuru&method=store">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahIzinModalLabel">Formulir Pengajuan Izin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="pegawai" class="form-label">Pegawai</label>
                        <?php if (isAnyLevel($id_level, [1, 5, 7])): ?>
                            <select name="pin" id="pegawai" class="form-select" required>
                                <option value="" disabled selected>-- Pilih Pegawai --</option>
                                <?php foreach ($pegawai_list as $p): ?>
                                    <option value="<?= $p['pin'] ?>"><?= htmlspecialchars(trim($p['nama'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" readonly>
                            <input type="hidden" name="pin" value="<?= $user['pin'] ?>">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</Izin></label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="jenis" class="form-label">Jenis Izin</label>
                        <select name="jenis" id="jenis" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Jenis --</option>
                            <option value="Sakit">Sakit</option>
                            <option value="Izin">Izin</option>
                            <option value="Dinas Luar">Dinas Luar</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Tuliskan alasan atau keterangan tambahan..."></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Ajukan Izin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($izin_list as $izin): ?>
    <div class="modal fade" id="editIzinModal<?= $izin['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="index.php?controller=izinGuru&method=update" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Izin: <?= htmlspecialchars($pegawaiMap[$izin['pin']] ?? '-') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $izin['id'] ?>">
                    <input type="hidden" name="pin" value="<?= $izin['pin'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control" value="<?= $izin['tanggal_mulai'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control" value="<?= $izin['tanggal_selesai'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis Izin</label>
                        <select name="jenis" class="form-select" required>
                            <option value="Sakit" <?= $izin['jenis'] == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                            <option value="Izin" <?= $izin['jenis'] == 'Izin' ? 'selected' : '' ?>>Izin</option>
                            <option value="Dinas Luar" <?= $izin['jenis'] == 'Dinas Luar' ? 'selected' : '' ?>>Dinas Luar</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars($izin['keterangan']) ?></textarea>
                    </div>

                    <?php if (isAnyLevel($id_level, [1, 5, 7])): ?>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Approval</label>
                            <select name="status_approval" class="form-select" onchange="toggleAlasanDitolak(this)" data-modal-id="<?= $izin['id'] ?>" required>
                                <option value="pending" <?= $izin['status_approval'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="disetujui" <?= $izin['status_approval'] == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                                <option value="ditolak" <?= $izin['status_approval'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                            </select>
                        </div>
                        <div class="mb-3" id="alasanDitolakContainer<?= $izin['id'] ?>" style="display: <?= $izin['status_approval'] == 'ditolak' ? 'block' : 'none' ?>;">
                            <label class="form-label">Keterangan</label>
                            <textarea name="alasan_ditolak" class="form-control" rows="3" placeholder="Tuliskan keterangan mengapa izin ini ditolak..."><?= htmlspecialchars($izin['alasan_ditolak'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3" id="catatanApprovalContainer<?= $izin['id'] ?>" style="display: <?= $izin['status_approval'] == 'disetujui' ? 'block' : 'none' ?>;">
                            <label class="form-label">Catatan Persetujuan (opsional)</label>
                            <textarea name="catatan_approval" class="form-control" rows="2" placeholder="Catatan saat menyetujui..."><?= htmlspecialchars($izin['catatan_approval'] ?? '') ?></textarea>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="status_approval" value="<?= $izin['status_approval'] ?>">
                        <input type="hidden" name="catatan_approval" value="<?= htmlspecialchars($izin['catatan_approval'] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php include '../app/views/layouts/footer.php'; ?>
<script>
    function toggleAlasanDitolak(selectElement) {
        // Ambil ID unik dari atribut data-modal-id
        const modalId = selectElement.getAttribute('data-modal-id');
        const container = document.getElementById('alasanDitolakContainer' + modalId);

        const catatanContainer = document.getElementById('catatanApprovalContainer' + modalId);

        if (selectElement.value === 'ditolak') {
            container.style.display = 'block';
            if (catatanContainer) catatanContainer.style.display = 'none';
        } else if (selectElement.value === 'disetujui') {
            if (container) container.style.display = 'none';
            if (catatanContainer) catatanContainer.style.display = 'block';
        } else {
            if (container) container.style.display = 'none';
            if (catatanContainer) catatanContainer.style.display = 'none';
        }
    }
</script>
<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link langsung berjalan
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Izin yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href; // Lanjutkan ke link hapus jika dikonfirmasi
                }
            });
        });
    });
</script>