<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<?php
// Decode nama_ns dari database
$nama_ns_existing = json_decode($kategori['nama_ns'] ?? '{}', true);
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-10">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Kategori Nilai</h6>
                    </div>
                </div>
                <div class="card-body">
                    <form action="?controller=kategori&method=updateKategori" method="POST">
                        <input type="hidden" name="id_kategori" value="<?= htmlspecialchars($kategori['id_kategori']) ?>">
                        <input type="hidden" name="id_mapel_guru" value="<?= htmlspecialchars($kategori['id_mapel_guru']) ?>">

                        <div class="input-group input-group-outline my-3 is-filled">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="kategori" class="form-control" value="<?= htmlspecialchars($kategori['kategori']) ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Banyak NS (1-10)</label>
                                    <input type="number" name="banyak_ns" id="banyak_ns" class="form-control" value="<?= htmlspecialchars($kategori['banyak_ns']) ?>" required min="1" max="10">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Bobot Rata NS</label>
                                    <input type="number" name="bobot_ns" class="form-control" value="<?= htmlspecialchars($kategori['bobot_ns']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Bobot STS</label>
                                    <input type="number" name="bobot_sts" class="form-control" value="<?= htmlspecialchars($kategori['bobot_sts']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline my-3 is-filled">
                                    <label class="form-label">Bobot SAS</label>
                                    <input type="number" name="bobot_sas" class="form-control" value="<?= htmlspecialchars($kategori['bobot_sas']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div id="section-nama-ns" class="mt-4">
                            <h6>Kustom Nama Nilai (N)</h6>
                            <div id="container-nama-ns" class="row">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="?controller=kategori&method=index&id_mapel_guru=<?= htmlspecialchars($kategori['id_mapel_guru']) ?>" class="btn btn-secondary me-2">Batal</a>
                            <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const existingNames = <?= json_encode($nama_ns_existing) ?>;

    function generateInputs() {
        const jumlah = parseInt(document.getElementById('banyak_ns').value);
        const container = document.getElementById('container-nama-ns');
        container.innerHTML = '';

        if (jumlah > 0 && jumlah <= 10) {
            for (let i = 1; i <= jumlah; i++) {
                const currentVal = existingNames['n' + i] ? existingNames['n' + i] : 'N' + i;
                container.innerHTML += `
                    <div class="col-md-3 mb-3">
                        <div class="input-group input-group-outline is-filled">
                            <label class="form-label">Nama N${i}</label>
                            <input type="text" name="nama_ns[n${i}]" class="form-control" value="${currentVal}" required>
                        </div>
                    </div>
                `;
            }
        }
    }

    document.getElementById('banyak_ns').addEventListener('input', generateInputs);
    // Jalankan pertama kali saat halaman dimuat
    window.onload = generateInputs;
</script>
<?php include '../app/views/layouts/footer.php'; ?>