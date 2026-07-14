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
    .form-control {
        padding-left: 1rem !important;
    }
</style>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tindak Lanjut Konseling</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">

                    <div class="row">
                        <div class="col-md-8">
                            <!-- Riwayat Tindak Lanjut -->
                             <a href="?controller=konseling&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
                            <h6 class="mb-3 text-dark fw-bold">Riwayat Tindak Lanjut</h6>
                            
                            <?php if (!empty($tindaklanjut)): ?>
                                <ul class="list-group mb-4">
                                    <?php foreach ($tindaklanjut as $i => $tl): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="me-auto">
                                                <div class="fw-bold"><?= date('d M Y', strtotime($tl['tanggal'])) ?></div>
                                                <?= nl2br(htmlspecialchars($tl['catatan'])) ?>
                                            </div>
                                            <?php if (!empty($tl['bukti'])): ?>
                                                <button class="btn btn-sm mt-2 btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buktiModal<?= $i ?>">Lihat Bukti</button>

                                                <!-- Modal Bukti -->
                                                <div class="modal fade" id="buktiModal<?= $i ?>" tabindex="-1" aria-labelledby="buktiModalLabel<?= $i ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="buktiModalLabel<?= $i ?>">Bukti Tindak Lanjut</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <img src="uploads/tindaklanjut/<?= $tl['bukti'] ?>" class="img-fluid rounded shadow">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-light">Belum ada tindak lanjut.</div>
                            <?php endif; ?>
                        </div>
                        <?php if (!isLevel($id_level, [1, 7])): ?>
                            <!-- Form Tindak Lanjut -->
                            <div class="col-md-4">
                                <h6 class="mb-3 text-dark fw-bold">Tambah Tindak Lanjut</h6>

                                <?php if ($status === 'Selesai'): ?>
                                    <div class="alert alert-light">
                                        Konseling ini telah <strong>SELESAI</strong>. Tambahan tindak lanjut tidak diperbolehkan.
                                    </div>
                                <?php else: ?>
                                    <form action="?controller=konseling&method=simpanTindakLanjut" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="id_konseling" value="<?= $id ?>">

                                        <div class="mb-3">
                                            <label class="form-label text-dark fw-bold">Catatan Tindak Lanjut</label>
                                            <textarea name="catatan" class="form-control border focus-ring focus-ring-success rounded-3" rows="4" required></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label text-dark fw-bold">Tanggal</label>
                                            <input type="date" name="tanggal" class="form-control border focus-ring focus-ring-success rounded-3" required>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label text-dark fw-bold">Upload Bukti (Opsional)</label>
                                            <input type="file" name="bukti" accept="image/*,application/pdf"
                                                class="form-control border focus-ring focus-ring-success rounded-3">
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn bg-gradient-success text-white">Simpan</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>