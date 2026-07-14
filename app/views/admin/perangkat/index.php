<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<?php if ($msg = getFlash('success')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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

<?php if ($msg = getFlash('error')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#f44336',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapelSelect = document.getElementById('id_mapel_guru');
    const formContainer = document.getElementById('form-perangkat');

    mapelSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const id = selectedOption.value;
        const tingkat = selectedOption.getAttribute('data-tingkat');

        // Isi hidden input agar ikut dikirim saat submit
        document.getElementById('tingkat_input').value = tingkat;

        if (id) {
            formContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            fetch(`index.php?controller=perangkat&method=ajaxForm&id_mapel_guru=${id}&tingkat=${tingkat}`)
                .then(res => res.text())
                .then(html => {
                    formContainer.innerHTML = html;
                })
                .catch(err => {
                    formContainer.innerHTML = '<div class="alert alert-danger">Gagal memuat form.</div>';
                });
        } else {
            formContainer.innerHTML = '';
        }
    });
});

</script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Upload Perangkat Mengajar</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-2">
                    <?php if ($tahun_aktif): ?>
                        <div class="mb-3">
                            <label class="form-label">Tahun Pelajaran Aktif</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($tahun_aktif['tahun_pelajaran'] . ' - ' . $tahun_aktif['semester']); ?>" readonly>
                        </div>

                        <?php if (!empty($jenis_perangkat)): ?>
                            <form action="index.php?controller=perangkat&method=upload" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id_tahun_pelajaran" value="<?= htmlspecialchars($tahun_aktif['id_tahun_pelajaran']); ?>">

                                <div class="mb-3">
    <label class="form-label">Pilih Mapel & Tingkat</label>
    <select name="id_mapel_guru" id="id_mapel_guru" class="form-select" required>
        <option value="">-- Pilih Mapel & Tingkat --</option>
        <?php foreach ($mapel_guru as $mg): ?>
            <option value="<?= $mg['id_mapel_guru'] ?>" data-tingkat="<?= $mg['tingkat'] ?>">
                <?= htmlspecialchars($mg['nama_mapel']) ?> - <?= htmlspecialchars($mg['tingkat']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<input type="hidden" name="tingkat" id="tingkat_input">
<div id="form-perangkat"></div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">Data jenis perangkat tidak tersedia.</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger mt-3">Tidak ada tahun pelajaran aktif yang terdeteksi.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>