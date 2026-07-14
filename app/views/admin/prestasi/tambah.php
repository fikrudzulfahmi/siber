<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Input Prestasi Siswa (Kolektif)</h6>
                        <a href="?controller=prestasi&method=index" class="btn btn-sm btn-outline-white me-3 mb-0">Kembali</a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="?controller=prestasi&method=simpan" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label font-weight-bold">Nama Kegiatan / Lomba</label>
                                        <input type="text" name="nama_kegiatan" class="form-control border ps-2" placeholder="Contoh: Lomba Robotik Nasional 2024" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label font-weight-bold">Jenis Prestasi</label>
                                        <select name="jenis_prestasi" class="form-control border ps-2" required>
                                            <option value="Akademik">Akademik</option>
                                            <option value="Non-Akademik">Non-Akademik</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label font-weight-bold">Tingkat</label>
                                        <select name="tingkat" class="form-control border ps-2" required>
                                            <option value="Sekolah">Sekolah</option>
                                            <option value="Kecamatan">Kecamatan</option>
                                            <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                                            <option value="Karesidenan">Karesidenan</option>
                                            <option value="Provinsi">Provinsi</option>
                                            <option value="Nasional">Nasional</option>
                                            <option value="Internasional">Internasional</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label font-weight-bold">Juara</label>
                                        <select name="juara_select" id="juara_select" class="form-control border ps-2" onchange="toggleJuaraCustom()" required>
                                            <option value="Juara 1">Juara 1</option>
                                            <option value="Juara 2">Juara 2</option>
                                            <option value="Juara 3">Juara 3</option>
                                            <option value="Harapan 1">Harapan 1</option>
                                            <option value="Harapan 2">Harapan 2</option>
                                            <option value="Harapan 3">Harapan 3</option>
                                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                                        </select>
                                        <input type="text" name="juara_custom" id="juara_custom" class="form-control border ps-2 mt-2 d-none" placeholder="Sebutkan juara/penghargaan...">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label font-weight-bold">Tanggal Kegiatan</label>
                                        <input type="date" name="tgl_kegiatan" class="form-control border ps-2" required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label font-weight-bold">Penyelenggara</label>
                                        <input type="text" name="penyelenggara" class="form-control border ps-2" placeholder="Contoh: Dinas Pendidikan / Universitas..." required>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label font-weight-bold">Sertifikat (Gambar/PDF)</label>
                                        <input type="file" name="sertifikat" class="form-control border ps-2">
                                        <small class="text-muted">Opsional. Maksimal 2MB.</small>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label font-weight-bold">Keterangan Tambahan</label>
                                        <textarea name="keterangan_tambahan" class="form-control border ps-2" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label font-weight-bold text-success"><i class="fas fa-users"></i> Pilih Peserta (Bisa Lebih dari Satu)</label>
                                <div class="input-group input-group-outline mb-2">
                                    <input type="text" id="searchSiswa" class="form-control" placeholder="Cari nama atau kelas...">
                                </div>
                                <div class="table-responsive border border-radius-lg" style="max-height: 450px; overflow-y: auto;">
                                    <table class="table align-items-center mb-0" id="tableSiswa">
                                        <thead class="sticky-top bg-white z-index-1">
                                            <tr>
                                                <th class="text-center p-2" width="10%">Pilih</th>
                                                <th class="text-xs font-weight-bolder opacity-7 p-2">Nama Siswa</th>
                                                <th class="text-xs font-weight-bolder opacity-7 p-2">Kelas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($list_siswa as $s) : ?>
                                                <tr class="siswa-row">
                                                    <td class="text-center">
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input" type="checkbox" name="pilih_siswa[]" value="<?= $s['id_ploting']; ?>">
                                                        </div>
                                                    </td>
                                                    <td><span class="text-xs font-weight-bold"><?= htmlspecialchars($s['nama_siswa']); ?></span></td>
                                                    <td class="text-center"><span class="text-xs"><?= htmlspecialchars($s['kelas']); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-danger mt-2 d-block">* Pastikan minimal satu siswa dipilih.</small>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn bg-gradient-success">Simpan Data Prestasi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi menampilkan input manual jika pilih "Lainnya"
    function toggleJuaraCustom() {
        const select = document.getElementById('juara_select');
        const customInput = document.getElementById('juara_custom');
        if (select.value === 'Lainnya') {
            customInput.classList.remove('d-none');
            customInput.required = true;
        } else {
            customInput.classList.add('d-none');
            customInput.required = false;
        }
    }

    // Live Search Siswa
    document.getElementById('searchSiswa').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll('.siswa-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>