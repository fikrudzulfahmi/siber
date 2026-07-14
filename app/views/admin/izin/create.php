<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Form Tambah Izin</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="?controller=izin&method=store" method="POST">

                        <?php
                        $id_kelas = $_SESSION['user']['id_kelas'] ?? null;
                        $id_level = $_SESSION['user']['level'] ?? null;

                        $kelas = null;

                        // Jika wali kelas
                        if ($id_kelas && $id_level != 4) {
                            $stmt = $this->db->prepare("SELECT kelas FROM kelas WHERE id_kelas = ?");
                            $stmt->execute([$id_kelas]);
                            $kelas = $stmt->fetchColumn();
                        }
                        ?>

                        <div class="mb-3">
                            <label class="form-label">Kelas</label>

                            <?php if ($id_level == 4): ?>
                                <!-- User BK: tampilkan semua kelas -->
                                <select name="id_kelas" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $stmt = $this->db->query("SELECT id_kelas, kelas FROM kelas ORDER BY kelas ASC");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?= $row['id_kelas'] ?>"><?= htmlspecialchars($row['kelas']) ?></option>
                                    <?php endwhile; ?>
                                </select>

                            <?php elseif (!empty($id_kelas) && !empty($kelas)): ?>
                                <!-- Wali kelas -->
                                <input type="text" class="form-control" value="<?= $kelas ?>" disabled>
                                <input type="hidden" name="id_kelas" value="<?= $id_kelas ?>">

                            <?php else: ?>
                                <!-- Bukan wali kelas dan bukan BK -->
                                <input type="text" class="form-control is-invalid" value="Kelas tidak ditemukan" disabled>
                                <div class="invalid-feedback">
                                    Pastikan Anda login sebagai wali kelas atau BK.
                                </div>
                            <?php endif; ?>
                        </div>




                        <div class="mb-3">
                            <label for="id_siswa" class="form-label">Nama Siswa</label>
                            <select class="form-control border focus-ring focus-ring-success rounded-3" name="id_siswa" id="siswa" required>
                                <option disabled selected>-- Pilih Siswa --</option>
                                <!-- Akan terisi via JS berdasarkan kelas -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="keperluan" class="form-label">Keperluan</label>
                            <textarea name="keperluan" class="form-control border focus-ring focus-ring-success rounded-3" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="waktu_meninggalkan" class="form-label">Waktu Meninggalkan</label>
                            <input type="datetime-local" class="form-control border focus-ring focus-ring-success rounded-3" name="waktu_meninggalkan" required>
                        </div>

                        <div class="mb-3">
                            <label for="waktu_kembali" class="form-label">Waktu Kembali</label>
                            <input type="datetime-local" class="form-control border focus-ring focus-ring-success rounded-3" name="waktu_kembali" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rekomendasi Oleh</label>
                            <input type="text" class="form-control border focus-ring focus-ring-success rounded-3" value="<?= $_SESSION['user']['nama'] ?? 'Pengguna' ?>" disabled>
                        </div>



                        <button type="submit" class="btn btn-success">Simpan</button>
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
        const kelasSelect = document.querySelector('select[name="id_kelas"]');

        // Ambil data dari PHP Session
        const idKelasSession = "<?= $_SESSION['user']['id_kelas'] ?? '' ?>";
        const idTahunSession = "<?= $_SESSION['user']['id_tahun'] ?? '' ?>"; // Pastikan session ini ada

        /**
         * Fungsi untuk load siswa berdasarkan plotting
         */
        function loadSiswa(idKelas, idTahun) {
            if (!idKelas) return;

            // Tampilkan loading state
            siswaSelect.innerHTML = '<option disabled selected>Memuat data siswa...</option>';

            // Tambahkan parameter id_tahun ke URL fetch agar data sesuai periode aktif
            const url = `ajax.php?action=get_siswa&id_kelas=${idKelas}&id_tahun=${idTahun}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    let options = '<option value="" disabled selected>-- Pilih Siswa --</option>';

                    if (data.length > 0) {
                        data.forEach(siswa => {
                            options += `<option value="${siswa.id_siswa}">${siswa.nama_siswa}</option>`;
                        });
                    } else {
                        options = '<option value="" disabled selected>Tidak ada siswa di kelas ini</option>';
                    }

                    siswaSelect.innerHTML = options;

                    // Trigger update jika menggunakan library Select2
                    if (typeof $ !== 'undefined' && $(siswaSelect).data('select2')) {
                        $(siswaSelect).trigger('change');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    siswaSelect.innerHTML = '<option disabled selected>Gagal memuat data</option>';
                });
        }

        // --- LOGIKA EKSEKUSI ---

        // 1. Jika User adalah Wali Kelas (id_kelas otomatis terdeteksi dari session)
        if (idKelasSession) {
            loadSiswa(idKelasSession, idTahunSession);
        }

        // 2. Jika User adalah Guru BK/Admin (memilih kelas dari dropdown)
        if (kelasSelect) {
            // Jika saat page load select kelas sudah ada isinya (misal filter sebelumnya)
            if (kelasSelect.value) {
                loadSiswa(kelasSelect.value, idTahunSession);
            }

            // Event listener saat ganti kelas
            kelasSelect.addEventListener('change', function() {
                loadSiswa(this.value, idTahunSession);
            });
        }
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>