<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Konseling</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=konseling&method=store" method="POST" enctype="multipart/form-data">
                        <!-- Pilih Kelas -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Pilih Kelas</label>
                            <select id="kelas" name="id_kelas" class="form-control select2 border focus-ring focus-ring-success rounded-3" required>
                                <option value="" disabled selected>-- Pilih Kelas --</option>
                                <?php foreach ($kelas as $k): ?>
                                    <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Pilih Siswa -->
                        <div class="mb-4">
                            <label for="id_siswa" class="form-label">Nama Siswa</label>
                            <select class="form-control" name="id_siswa" id="siswa" required>
                                <option value="" disabled selected>-- Pilih Siswa --</option>

                                <?php
                                // Jika variabel $siswa sudah ada isinya (untuk Wali Kelas), tampilkan langsung
                                if (!empty($siswa)):
                                    foreach ($siswa as $s): ?>
                                        <option value="<?= $s['id_siswa'] ?>"><?= htmlspecialchars($s['nama_siswa']) ?></option>
                                <?php endforeach;
                                endif;
                                ?>
                            </select>
                        </div>
                        <!-- Pilih Kategori -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Kategori Permasalahan</label>
                            <select name="id_kategori" class="form-control select2 border focus-ring focus-ring-success rounded-3" required>
                                <option value="" disabled selected>-- Pilih Kategori --</option>
                                <?php foreach ($kategori as $kat): ?>
                                    <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Permasalahan -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Permasalahan</label>
                            <textarea name="permasalahan" class="form-control border focus-ring focus-ring-success rounded-3" rows="4" required></textarea>
                        </div>

                        <!-- Tanggal Masalah -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tanggal Masalah</label>
                            <input type="date" name="tanggal_masalah" class="form-control border focus-ring focus-ring-success rounded-3" required>
                        </div>

                        <!-- Bukti Fisik -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Bukti Fisik</label>
                            <input type="file" name="bukti_fisik" accept="image/*" class="form-control border focus-ring focus-ring-success rounded-3">
                        </div>

                        <!-- Dokumen Pendukung -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Dokumen Pendukung</label>
                            <input type="file" name="dokumen" class="form-control border focus-ring focus-ring-success rounded-3">
                        </div>

                        <!-- Pilih Petugas (Multiple Select) -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Petugas Konseling</label>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control border focus-ring focus-ring-success rounded-3" id="search-petugas" placeholder="Cari petugas...">
                            </div>


                            <div id="petugas-list" class="border p-2 rounded" style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($employee as $e): ?>
                                    <div class="form-check form-check-custom position-relative mb-2">
                                        <input class="form-check-input d-none" type="checkbox" name="id_employee[]" value="<?= $e['id_employe'] ?>" id="emp<?= $e['id_employe'] ?>">
                                        <label class="form-check-label d-flex align-items-center gap-2 ps-4" for="emp<?= $e['id_employe'] ?>">
                                            <span class="mdi mdi-checkbox-blank-outline unchecked-icon position-absolute start-0 text-muted"></span>
                                            <span class="mdi mdi-checkbox-marked checked-icon position-absolute start-0 text-success d-none"></span>
                                            <?= $e['nama'] ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=konseling&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white px-4">Simpan</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Script -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const kelasSelect = document.getElementById('kelas');
        const siswaSelect = document.getElementById('siswa');

        if (kelasSelect && siswaSelect) {
            kelasSelect.addEventListener('change', function() {
                const idKelas = this.value;
                if (!idKelas) return;

                siswaSelect.innerHTML = '<option disabled selected>Memuat...</option>';

                fetch('ajax.php?action=get_siswa&id_kelas=' + idKelas)
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal ambil data');
                        return response.json();
                    })
                    .then(data => {
                        let options = '<option disabled selected>-- Pilih Siswa --</option>';
                        data.forEach(siswa => {
                            options += `<option value="${siswa.id_siswa}">${siswa.nama_siswa}</option>`;
                        });
                        siswaSelect.innerHTML = options;

                        // trigger jika pakai select2
                        if ($(siswaSelect).hasClass('select2')) {
                            $(siswaSelect).trigger('change');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        siswaSelect.innerHTML = '<option disabled selected>Gagal memuat data</option>';
                    });
            });
        }
    });
</script>

<script>
    $('#search-petugas').on('keyup', function() {
        var keyword = $(this).val().toLowerCase();
        $('#petugas-list .form-check').each(function() {
            var label = $(this).text().toLowerCase();
            $(this).toggle(label.includes(keyword));
        });
    });
</script>



<?php include '../app/views/layouts/footer.php'; ?>