<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    .form-control, .form-select { padding-left: 1rem !important; }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Pegawai</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=user&method=store" method="POST" autocomplete="off">
                        <?= sec_csrf_field() ?>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control border focus-ring focus-ring-success rounded-3"
                                       value="<?= sec_e($_POST['nama'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">
                                    PIN <small class="text-muted">(nomor di mesin fingerprint)</small>
                                </label>
                                <input type="text" name="pin" inputmode="numeric" pattern="[0-9]{1,20}"
                                       class="form-control border focus-ring focus-ring-success rounded-3"
                                       value="<?= sec_e($_POST['pin'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">Kategori Jabatan</label>
                                <select name="id_jabatan" class="form-control border focus-ring focus-ring-success rounded-3" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach (($jabatans ?? []) as $jabatan): ?>
                                        <option value="<?= (int) $jabatan['id_jabatan'] ?>"
                                            <?= ((string) ($_POST['id_jabatan'] ?? '') === (string) $jabatan['id_jabatan']) ? 'selected' : '' ?>>
                                            <?= sec_e($jabatan['jabatan']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">
                                    Jabatan <small class="text-muted">(kosongkan = ikut kategori)</small>
                                </label>
                                <input type="text" name="jabatan" class="form-control border focus-ring focus-ring-success rounded-3"
                                       value="<?= sec_e($_POST['jabatan'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">No. WhatsApp</label>
                                <input type="text" name="no_wa" inputmode="tel"
                                       class="form-control border focus-ring focus-ring-success rounded-3"
                                       value="<?= sec_e($_POST['no_wa'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">Username</label>
                                <input type="text" name="username" class="form-control border focus-ring focus-ring-success rounded-3"
                                       value="<?= sec_e($_POST['username'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold">Password</label>
                                <input type="password" name="password" class="form-control border focus-ring focus-ring-success rounded-3"
                                       minlength="6" required autocomplete="new-password">
                                <small class="text-xs text-secondary">Minimal 6 karakter.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold d-block">Level Akses</label>
                            <div class="row">
                                <?php foreach (($allLevels ?? []) as $lv): ?>
                                    <?php $dipilih = in_array((string) $lv['id_level'], array_map('strval', (array) ($_POST['levels'] ?? [])), true); ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="levels[]"
                                                   id="level<?= (int) $lv['id_level'] ?>" value="<?= (int) $lv['id_level'] ?>"
                                                   <?= $dipilih ? 'checked' : '' ?>>
                                            <label class="form-check-label text-sm" for="level<?= (int) $lv['id_level'] ?>">
                                                <?= sec_e($lv['nama_level']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-xs text-secondary">Tanpa level, pegawai tidak bisa membuka menu apa pun.</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success mb-0">Simpan</button>
                            <a href="?controller=user&method=index" class="btn btn-outline-secondary mb-0">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../app/views/layouts/footer.php'; ?>
