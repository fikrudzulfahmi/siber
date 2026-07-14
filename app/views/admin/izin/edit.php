<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Form Edit Izin</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="?controller=izin&method=update" method="POST">

                        <?php
                        // ambil nama kelas berdasarkan id_kelas user
                        $stmt = $this->db->prepare("SELECT kelas FROM kelas WHERE id_kelas = ?");
                        $stmt->execute([$_SESSION['user']['id_kelas']]);
                        $kelas = $stmt->fetchColumn(); // hanya ambil nilai kolom "kelas"

                        $keteranganSaatIni = $izin['keterangan'] ?? '';
                        ?>

                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <input type="text" class="form-control" value="<?= $kelas ?>" disabled>
                            <input type="hidden" name="id_perizinan" value="<?= $izin['id_perizinan'] ?>">
                            <input type="hidden" name="id_kelas" value="<?= $_SESSION['user']['id_kelas'] ?? '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="id_siswa" class="form-label">Nama Siswa</label>
                            <select class="form-control border focus-ring focus-ring-success rounded-3" name="id_siswa" id="siswa" required>
                                <option disabled>-- Pilih Siswa --</option>
                                <!-- Akan diisi via JS -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="keperluan" class="form-label">Keperluan</label>
                            <textarea name="keperluan" class="form-control border focus-ring focus-ring-success rounded-3" rows="3" required><?= $izin['keperluan'] ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="waktu_meninggalkan" class="form-label">Waktu Meninggalkan</label>
                            <input type="datetime-local" class="form-control border focus-ring focus-ring-success rounded-3"
                                name="waktu_meninggalkan" value="<?= date('Y-m-d\TH:i', strtotime($izin['waktu_meninggalkan'])) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="waktu_kembali" class="form-label">Waktu Kembali</label>
                            <input type="datetime-local" class="form-control border focus-ring focus-ring-success rounded-3"
                                name="waktu_kembali" value="<?= date('Y-m-d\TH:i', strtotime($izin['waktu_kembali'])) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rekomendasi Oleh</label>
                            <input type="text" class="form-control" value="<?= $izin['nama_rekom'] ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <select name="keterangan" class="form-control border focus-ring focus-ring-success rounded-3">
                                <option value="-" <?= ($keteranganSaatIni == '-') ? 'selected' : '' ?>>
                                    Pilih Keterangan ...
                                </option>
                                <option value="tepat" <?= ($keteranganSaatIni == 'tepat') ? 'selected' : '' ?>>
                                    Tepat Waktu
                                </option>
                                <option value="terlambat" <?= ($keteranganSaatIni == 'terlambat') ? 'selected' : '' ?>>
                                    Terlambat
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tindakan</label>
                            <input type="text" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $izin['tindakan'] ?>" name="tindakan">
                        </div>

                        <button type="submit" class="btn btn-success">Perbarui</button>
                        <a href="?controller=izin&method=index" class="btn btn-secondary">Kembali</a>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const siswaSelect = document.getElementById('siswa');
        const idKelas = "<?= $_SESSION['user']['id_kelas'] ?>";
        const selectedSiswa = "<?= $izin['id_siswa'] ?>";

        if (idKelas && siswaSelect) {
            siswaSelect.innerHTML = '<option disabled selected>Memuat...</option>';

            fetch('ajax.php?action=get_siswa&id_kelas=' + idKelas)
                .then(response => {
                    if (!response.ok) throw new Error('Gagal ambil data');
                    return response.json();
                })
                .then(data => {
                    let options = '<option disabled>-- Pilih Siswa --</option>';
                    data.forEach(siswa => {
                        const selected = siswa.id_siswa == selectedSiswa ? 'selected' : '';
                        options += `<option value="${siswa.id_siswa}" ${selected}>${siswa.nama_siswa}</option>`;
                    });
                    siswaSelect.innerHTML = options;
                    if ($(siswaSelect).hasClass('select2')) $(siswaSelect).trigger('change');
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    siswaSelect.innerHTML = '<option disabled selected>Gagal memuat data</option>';
                });
        }
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>