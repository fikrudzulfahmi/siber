<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-10">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Kategori Nilai</h6>
                    </div>
                </div>
                <div class="card-body">
                    <form action="?controller=kategori&method=store" method="POST">
                        <input type="hidden" name="id_mapel_guru" value="<?= htmlspecialchars($info['id_mapel_guru']) ?>">
                        <input type="hidden" name="id_tahun_pelajaran" value="<?= htmlspecialchars($_SESSION['user']['id_tahun']) ?>">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Nama Kategori</label>
                                    <input type="text" name="kategori" class="form-control" placeholder="Contoh: Nilai Harian Semester Ganjil" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Banyak NS (1-10)</label>
                                    <input type="number" name="banyak_ns" id="banyak_ns" class="form-control" required min="1" max="10">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Bobot Rata NS</label>
                                    <input type="number" name="bobot_ns" class="form-control" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Bobot STS</label>
                                    <input type="number" name="bobot_sts" class="form-control" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Bobot SAS</label>
                                    <input type="number" name="bobot_sas" class="form-control" value="1" required>
                                </div>
                            </div>
                        </div>

                        <div id="section-nama-ns" class="mt-4" style="display:none;">
                            <h6>Kustom Nama Nilai (N)</h6>
                            <p class="text-xs text-muted">Beri nama untuk tiap kolom nilai, contoh: Tugas 1, UH 1, Praktikum, dll.</p>
                            <div id="container-nama-ns" class="row">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="?controller=kategori&method=index&id_mapel_guru=<?= htmlspecialchars($info['id_mapel_guru']) ?>" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('banyak_ns').addEventListener('input', function() {
        const jumlah = parseInt(this.value);
        const container = document.getElementById('container-nama-ns');
        const section = document.getElementById('section-nama-ns');

        container.innerHTML = '';

        if (jumlah > 0 && jumlah <= 10) {
            section.style.display = 'block';
            for (let i = 1; i <= jumlah; i++) {
                container.innerHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="input-group input-group-outline is-filled">
                            <label class="form-label">Nama N${i}</label>
                            <input type="text" name="nama_ns[n${i}]" class="form-control" value="N${i}" required>
                        </div>
                    </div>
                `;
            }
        } else {
            section.style.display = 'none';
        }
    });
</script>
<?php include '../app/views/layouts/footer.php'; ?>