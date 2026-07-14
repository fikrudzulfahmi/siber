<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

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
                    <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Input Detail Rapor: <?= htmlspecialchars($dataSiswa['nama_siswa']) ?></h6>
                        <p class="text-white text-xs ps-3 mb-0">NISN: <?= $dataSiswa['nisn'] ?></p>
                    </div>
                </div>

                <div class="card-body px-4 pb-2">
                    <form action="?controller=rapor&method=update_terpadu" method="POST">
                        <input type="hidden" name="id_rapor_siswa" value="<?= $id_rapor_siswa ?>">
                        <input type="hidden" name="id_siswa" value="<?= $id_siswa ?>">

                        <div class="row">
                            <div class="col-md-4">
                                <h6 class="mb-3 mt-4">1. Presensi & Catatan</h6>
                                <div class="input-group input-group-static mb-3">
                                    <label>Sakit (Hari)</label>
                                    <input type="number" name="sakit" class="form-control" value="<?= $rapor['sakit'] ?? 0 ?>">
                                </div>
                                <div class="input-group input-group-static mb-3">
                                    <label>Izin (Hari)</label>
                                    <input type="number" name="izin" class="form-control" value="<?= $rapor['izin'] ?? 0 ?>">
                                </div>
                                <div class="input-group input-group-static mb-3">
                                    <label>Alfa (Hari)</label>
                                    <input type="number" name="alfa" class="form-control" value="<?= $rapor['alfa'] ?? 0 ?>">
                                </div>
                                <div class="input-group input-group-static mb-3">
                                    <label>Catatan Wali Kelas</label>
                                    <textarea name="catatan_walikelas" class="form-control" rows="4"><?= $rapor['catatan_walikelas'] ?? '' ?></textarea>
                                </div>
                                <div class="alert alert-light border p-2 mt-2">
                                    <small class="text-info">
                                        <i class="material-icons text-xs">info</i>
                                        Data kehadiran ditarik otomatis dari rekap Jurnal Harian. Anda tetap bisa mengubahnya secara manual jika diperlukan.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <h6 class="mb-3 mt-4">2. Ekstrakurikuler (Maks. 3)</h6>
                                <div class="table-responsive">
                                    <table class="table align-items-center mb-4">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama Kegiatan</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" width="15%">Nilai</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for ($i = 0; $i < 3; $i++):
                                                // Mengambil data dari variabel $ekstra
                                                $e_nama = $ekstra[$i]['nama_kegiatan'] ?? '';
                                                $e_nilai = $ekstra[$i]['nilai'] ?? '';
                                                $e_ket = $ekstra[$i]['keterangan'] ?? '';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="input-group input-group-outline">
                                                            <input type="text" name="ekstra[<?= $i ?>][nama]" class="form-control" value="<?= htmlspecialchars($e_nama) ?>" readonly>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-outline">
                                                            <input type="text" name="ekstra[<?= $i ?>][nilai]" class="form-control text-center" placeholder="A/B/C" value="<?= htmlspecialchars($e_nilai) ?>" readonly>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-outline">
                                                            <input type="text" name="ekstra[<?= $i ?>][ket]" class="form-control" value="<?= htmlspecialchars($e_ket) ?>" readonly>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <h6 class="mb-3 mt-4">3. Prestasi (Otomatis & Manual)</h6>
                                <div class="table-responsive">
                                    <table class="table align-items-center">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2" width="40%">Jenis Prestasi</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php for ($i = 0; $i < 3; $i++):
                                                // Mengambil data dari variabel $prestasi yang sudah diproses di Controller
                                                $val_jenis = $prestasi[$i]['jenis_prestasi'] ?? '';
                                                $val_ket = $prestasi[$i]['keterangan'] ?? '';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="input-group input-group-outline">
                                                            <input type="text" name="prestasi[<?= $i ?>][jenis]"
                                                                class="form-control"
                                                                placeholder="Contoh: Akademik / Olahraga"
                                                                value="<?= htmlspecialchars($val_jenis) ?>">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-outline">
                                                            <input type="text" name="prestasi[<?= $i ?>][ket]"
                                                                class="form-control"
                                                                placeholder="Contoh: Juara 1 Lomba MTQ"
                                                                value="<?= htmlspecialchars($val_ket) ?>">
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                    <?php if (empty($prestasi_manual) && !empty($prestasi_otomatis)): ?>
                                        <div class="alert alert-light border p-2 mt-2">
                                            <small class="text-info">
                                                <i class="material-icons text-xs">info</i>
                                                Data prestasi di atas ditarik otomatis dari inputan Kesiswaan/Admin.
                                                Klik <strong>Simpan</strong> untuk mengonfirmasi data ini ke rapor.
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 d-flex justify-content-end mb-4">
                                <a href="?controller=rapor&method=index" class="btn btn-outline-secondary me-2">Batal</a>
                                <button type="submit" class="btn bg-gradient-success">
                                    <i class="material-icons text-sm me-2">save</i> Simpan Semua Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>