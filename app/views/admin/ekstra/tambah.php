<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12 mx-auto">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Ekstrakurikuler Baru</h6>
                    </div>
                </div>
                <div class="card-body">
                    <form action="?controller=ekstra&method=simpan" method="POST">

                        <div class="input-group input-group-static mb-4">
                            <label>Nama Ekstrakurikuler</label>
                            <input type="text" name="nama_ekstra" class="form-control" placeholder="Contoh: Pramuka, Futsal, Drumband" required>
                        </div>

                        <div class="input-group input-group-static mb-4">
                            <label for="id_guru_pengampu" class="ms-0">Penanggung Jawab (Guru/Pembina)</label>
                            <select name="id_guru_pengampu" class="form-control" id="id_guru_pengampu" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach ($list_guru as $g): ?>
                                    <option value="<?= $g['id_employe'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group input-group-static mb-4">
                            <label>Keterangan / Deskripsi Singkat</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Jadwal rutin atau info singkat lainnya..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="?controller=ekstra&method=index" class="btn btn-light">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white">Simpan Data</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>