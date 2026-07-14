<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <form action="?controller=ekstra&method=updateKegiatan" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_ekstra_kegiatan" value="<?= $jurnal['id_ekstra_kegiatan'] ?>">
        <input type="hidden" name="id_ekstra" value="<?= $ekstra['id_ekstra'] ?>">

        <div class="row">
            <div class="col-lg-5">
                <div class="card my-4">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-warning shadow-warning border-radius-lg pt-4 pb-3">
                            <h6 class="text-white ps-3 mb-0">Edit Jurnal: <?= $ekstra['nama_ekstra'] ?></h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="input-group input-group-static mb-4">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= $jurnal['tanggal'] ?>" required>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label>Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" value="<?= htmlspecialchars($jurnal['nama_kegiatan']) ?>" required>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label>Isi Kegiatan</label>
                            <textarea name="isi_kegiatan" class="form-control" rows="4"><?= htmlspecialchars($jurnal['isi_kegiatan']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-xs">Foto Saat Ini:</label><br>
                            <?php if ($jurnal['nama_file']): ?>
                                <img src="../public/uploads/ekstra/<?= $jurnal['nama_file'] ?>" class="img-thumbnail mb-2" style="height: 100px;">
                            <?php endif; ?>
                            <input type="file" name="foto" class="form-control border p-2" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card my-4">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3">
                            <h6 class="text-white ps-3 mb-0">Koreksi Presensi</h6>
                        </div>
                    </div>
                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive p-0" style="max-height: 400px;">
                            <table class="table align-items-center mb-0">
                                <tbody>
                                    <?php foreach ($anggota as $ang):
                                        $status_skrg = $presensi_lama[$ang['id_ploting_siswa']] ?? 'Alfa';
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <p class="text-xs font-weight-bold mb-0"><?= $ang['nama_siswa'] ?></p>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <?php foreach (['Hadir' => 'H', 'Izin' => 'I', 'Sakit' => 'S', 'Alfa' => 'A'] as $full => $short): ?>
                                                        <input type="radio" class="btn-check" name="presensi[<?= $ang['id_ploting_siswa'] ?>]"
                                                            id="st_<?= $short ?>_<?= $ang['id_ploting_siswa'] ?>"
                                                            value="<?= $full ?>" <?= ($status_skrg == $full) ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-secondary text-xxs px-2 py-1 mb-0" for="st_<?= $short ?>_<?= $ang['id_ploting_siswa'] ?>"><?= $short ?></label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <a href="?controller=ekstra&method=riwayat&id_ekstra=<?= $ekstra['id_ekstra'] ?>" class="btn btn-light">Batal</a>
                    <button type="submit" class="btn bg-gradient-warning">Update Jurnal</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../app/views/layouts/footer.php'; ?>