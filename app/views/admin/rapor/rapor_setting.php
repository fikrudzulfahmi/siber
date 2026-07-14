<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<?php $is_edit = isset($edit_setting) && !empty($edit_setting); ?>

<div class="container-fluid py-4">
    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
    <div class="row">
        <div class="col-md-4">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-<?= $is_edit ? 'warning' : 'info' ?> shadow-info border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3"><?= $is_edit ? 'Edit Setting Rapor' : 'Tambah Setting Rapor' ?></h6>
                    </div>
                </div>
                <div class="card-body">
                    <form action="?controller=adminRapor&method=<?= $is_edit ? 'update' : 'store' ?>" method="POST">

                        <?php if ($is_edit): ?>
                            <input type="hidden" name="id_rapor" value="<?= $edit_setting['id_rapor'] ?>">
                        <?php endif; ?>

                        <div class="input-group input-group-static mb-4">
                            <label for="id_tahun" class="ms-0">Pilih Tahun Pelajaran & Semester</label>
                            <select class="form-control" id="id_tahun" name="id_tahun" required>
                                <?php foreach ($tahun_pelajaran as $tp): ?>
                                    <?php $selected = ($is_edit && $edit_setting['id_tahun_pelajaran'] == $tp['id_tahun_pelajaran']) ? 'selected' : ''; ?>
                                    <option value="<?= $tp['id_tahun_pelajaran'] ?>" <?= $selected ?>>
                                        <?= $tp['tahun_pelajaran'] ?> - <?= $tp['semester'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label for="jenis_rapor" class="ms-0">Jenis Rapor</label>
                            <select class="form-control" id="jenis_rapor" name="jenis_rapor" required>
                                <option value="tengah" <?= ($is_edit && $edit_setting['jenis_rapor'] == 'tengah') ? 'selected' : '' ?>>Rapor Tengah Semester</option>
                                <option value="semester" <?= ($is_edit && $edit_setting['jenis_rapor'] == 'semester') ? 'selected' : '' ?>>Rapor Akhir Semester</option>
                            </select>
                        </div>
                        <div class="input-group input-group-static mb-4">
                            <label for="tanggal_pembagian" class="ms-0">Tanggal Pembagian Rapor</label>
                            <input type="date" class="form-control" id="tgl_pembagian" name="tgl_pembagian" value="<?= $is_edit ? $edit_setting['tgl_pembagian'] : '' ?>" required>
                        </div>

                        <div class="form-check form-switch d-flex align-items-center mb-3">
                            <input class="form-check-input" type="checkbox" id="is_kenaikan" name="is_kenaikan" <?= ($is_edit && $edit_setting['is_kenaikan'] == 1) ? 'checked' : '' ?>>
                            <label class="form-check-label mb-0 ms-3" for="is_kenaikan">Aktifkan Fitur Kenaikan Kelas</label>
                        </div>

                        <button type="submit" class="btn bg-gradient-<?= $is_edit ? 'warning' : 'info' ?> w-100">
                            <?= $is_edit ? 'Update Setting' : 'Simpan Setting' ?>
                        </button>

                        <?php if ($is_edit): ?>
                            <a href="?controller=adminRapor&method=index" class="btn btn-outline-secondary w-100 mt-2">Batal Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3">Daftar Riwayat Periode Rapor</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Periode</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jenis</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal Pembagian</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settings as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= $s['tahun_pelajaran'] ?></h6>
                                                    <p class="text-xs text-secondary mb-0">Semester <?= $s['semester'] ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-secondary"><?= $s['jenis_rapor'] ?></span>
                                            <?= $s['is_kenaikan'] ? '<br><small class="text-info">Fitur Kenaikan ON</small>' : '' ?>
                                        </td>
                                        <td class="align-middle text-center">
                                            <p class="text-xs text-secondary mb-0"><?= $s['tgl_pembagian'] ?></p>
                                        </td>
                                        <td class="align-middle text-center">
                                            <?php if ($s['is_active']): ?>
                                                <span class="badge badge-sm bg-gradient-success">AKTIF</span>
                                            <?php else: ?>
                                                <span class="badge badge-sm bg-gradient-light text-dark">Non-Aktif</span>
                                            <?php endif; ?>

                                            <?php if ($s['is_locked']): ?>
                                                <i class="material-icons text-danger" title="Terkunci">lock</i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle text-center">
                                            <?php if (!$s['is_locked']): ?>
                                                <a href="?controller=adminRapor&method=edit&id=<?= $s['id_rapor'] ?>" class="btn btn-sm btn-link text-info">Edit</a>
                                            <?php endif; ?>

                                            <?php if (!$s['is_active']): ?>
                                                <a href="?controller=adminRapor&method=activate&id=<?= $s['id_rapor'] ?>" class="btn btn-sm btn-link text-success">Aktifkan</a>
                                            <?php endif; ?>

                                            <a href="?controller=adminRapor&method=lock&id=<?= $s['id_rapor'] ?>&status=<?= $s['is_locked'] ? '0' : '1' ?>" class="btn btn-sm btn-link text-<?= $s['is_locked'] ? 'warning' : 'danger' ?>">
                                                <?= $s['is_locked'] ? 'Buka Kunci' : 'Kunci Input' ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>