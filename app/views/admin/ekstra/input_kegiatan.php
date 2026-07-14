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
    <form action="?controller=ekstra&method=simpanKegiatan" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_ekstra" value="<?= $id_ekstra ?>">

        <div class="row">
            <div class="col-lg-5">
                <div class="card my-4">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                            <h6 class="text-white ps-3 mb-0">Jurnal: <?= $ekstra['nama_ekstra'] ?></h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="input-group input-group-static mb-4">
                            <label>Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label>Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Latihan Dasar" required>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label>Isi Kegiatan (Materi)</label>
                            <textarea name="isi_kegiatan" class="form-control" rows="4" placeholder="Apa saja yang dilakukan?"></textarea>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label>Upload Foto (Dokumentasi)</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card my-4">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                            <h6 class="text-white ps-3 mb-0">Presensi Guru / Pembimbing</h6>
                        </div>
                    </div>
                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Guru</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="ps-4">
                                            <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($ekstra['nama_pengampu']) ?></p>
                                            <p class="text-xxs text-secondary mb-0">Koordinator</p>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="btn-group btn-group-sm">
                                                <input type="radio" class="btn-check" name="presensi_guru[<?= $ekstra['id_guru_pengampu'] ?>]" id="GH_<?= $ekstra['id_guru_pengampu'] ?>" value="Hadir" checked>
                                                <label class="btn btn-outline-success text-xxs px-2 py-1 mb-0" for="GH_<?= $ekstra['id_guru_pengampu'] ?>">H</label>

                                                <input type="radio" class="btn-check" name="presensi_guru[<?= $ekstra['id_guru_pengampu'] ?>]" id="GI_<?= $ekstra['id_guru_pengampu'] ?>" value="Izin">
                                                <label class="btn btn-outline-info text-xxs px-2 py-1 mb-0" for="GI_<?= $ekstra['id_guru_pengampu'] ?>">I</label>

                                                <input type="radio" class="btn-check" name="presensi_guru[<?= $ekstra['id_guru_pengampu'] ?>]" id="GS_<?= $ekstra['id_guru_pengampu'] ?>" value="Sakit">
                                                <label class="btn btn-outline-warning text-xxs px-2 py-1 mb-0" for="GS_<?= $ekstra['id_guru_pengampu'] ?>">S</label>

                                                <input type="radio" class="btn-check" name="presensi_guru[<?= $ekstra['id_guru_pengampu'] ?>]" id="GA_<?= $ekstra['id_guru_pengampu'] ?>" value="Alfa">
                                                <label class="btn btn-outline-danger text-xxs px-2 py-1 mb-0" for="GA_<?= $ekstra['id_guru_pengampu'] ?>">A</label>
                                            </div>
                                        </td>
                                    </tr>

                                    <?php if (!empty($pendamping_aktif)): ?>
                                        <?php foreach ($pendamping_aktif as $p): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($p['nama']) ?></p>
                                                    <p class="text-xxs text-secondary mb-0">Pendamping</p>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <input type="radio" class="btn-check" name="presensi_guru[<?= $p['id_employe'] ?>]" id="GH_<?= $p['id_employe'] ?>" value="Hadir" checked>
                                                        <label class="btn btn-outline-success text-xxs px-2 py-1 mb-0" for="GH_<?= $p['id_employe'] ?>">H</label>

                                                        <input type="radio" class="btn-check" name="presensi_guru[<?= $p['id_employe'] ?>]" id="GI_<?= $p['id_employe'] ?>" value="Izin">
                                                        <label class="btn btn-outline-info text-xxs px-2 py-1 mb-0" for="GI_<?= $p['id_employe'] ?>">I</label>

                                                        <input type="radio" class="btn-check" name="presensi_guru[<?= $p['id_employe'] ?>]" id="GS_<?= $p['id_employe'] ?>" value="Sakit">
                                                        <label class="btn btn-outline-warning text-xxs px-2 py-1 mb-0" for="GS_<?= $p['id_employe'] ?>">S</label>

                                                        <input type="radio" class="btn-check" name="presensi_guru[<?= $p['id_employe'] ?>]" id="GA_<?= $p['id_employe'] ?>" value="Alfa">
                                                        <label class="btn btn-outline-danger text-xxs px-2 py-1 mb-0" for="GA_<?= $p['id_employe'] ?>">A</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <hr class="horizontal dark">
                <div class="card my-4">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3">
                            <h6 class="text-white ps-3 mb-0">Presensi Siswa</h6>
                        </div>
                    </div>
                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive p-0" style="max-height: 400px;">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Siswa</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($anggota as $ang): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <p class="text-xs font-weight-bold mb-0"><?= $ang['nama_siswa'] ?></p>
                                                <p class="text-xxs text-secondary mb-0"><?= $ang['kelas'] ?></p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <input type="radio" class="btn-check" name="presensi[<?= $ang['id_ploting_siswa'] ?>]" id="H_<?= $ang['id_ekstra_anggota'] ?>" value="Hadir" checked>
                                                    <label class="btn btn-outline-success text-xxs px-2 py-1 mb-0" for="H_<?= $ang['id_ekstra_anggota'] ?>">H</label>

                                                    <input type="radio" class="btn-check" name="presensi[<?= $ang['id_ploting_siswa'] ?>]" id="I_<?= $ang['id_ekstra_anggota'] ?>" value="Izin">
                                                    <label class="btn btn-outline-info text-xxs px-2 py-1 mb-0" for="I_<?= $ang['id_ekstra_anggota'] ?>">I</label>

                                                    <input type="radio" class="btn-check" name="presensi[<?= $ang['id_ploting_siswa'] ?>]" id="S_<?= $ang['id_ekstra_anggota'] ?>" value="Sakit">
                                                    <label class="btn btn-outline-warning text-xxs px-2 py-1 mb-0" for="S_<?= $ang['id_ekstra_anggota'] ?>">S</label>

                                                    <input type="radio" class="btn-check" name="presensi[<?= $ang['id_ploting_siswa'] ?>]" id="A_<?= $ang['id_ekstra_anggota'] ?>" value="Alfa">
                                                    <label class="btn btn-outline-danger text-xxs px-2 py-1 mb-0" for="A_<?= $ang['id_ekstra_anggota'] ?>">A</label>
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
                    <a href="?controller=ekstra&method=index" class="btn btn-light me-2">Batal</a>
                    <button type="submit" class="btn bg-gradient-success">Simpan Jurnal & Presensi</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include '../app/views/layouts/footer.php'; ?>