<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
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

<!-- Select2 CSS -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<!-- Select2 Theme Bootstrap 4 -->
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />


<!-- Custom Style for Form Control -->
<style>
    .select2-results__option[aria-selected=true]::before {
        content: "●";
        display: inline-block;
        margin-right: 6px;
        color: #28a745;
        /* warna hijau */
        font-size: 14px;
        vertical-align: middle;
    }

    /* 2. Non-selected tetap punya lingkaran kosong */
    .select2-results__option::before {
        content: "○";
        display: inline-block;
        margin-right: 6px;
        color: #aaa;
        font-size: 14px;
        vertical-align: middle;
    }

    /* 3. Warna latar belakang ketika dihover */
    .select2-results__option--highlighted {
        background-color: #d4edda !important;
        /* hijau muda */
        color: #155724 !important;
        /* teks hijau gelap */
    }

    /* 4. Aktif (selected) hijau juga */
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #c3e6cb !important;
        color: #155724 !important;
    }
</style>
<!-- <style>
    .form-check-custom input[type="checkbox"]:checked~.form-check-label .checked-icon {
        display: inline-block !important;
    }

    .form-check-custom input[type="checkbox"]:checked~.form-check-label .unchecked-icon {
        display: none !important;
    }

    .form-check-custom .mdi {
        font-size: 1.2rem;
        top: 0.1rem;
    }
</style>  -->

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Kelas & Guru</h6>
                    </div>
                    <p class="text-white text-sm ps-3 mb-0">
                        Tahun Pelajaran: <strong><?= $nama_tahun ?></strong> |
                        Semester: <strong><?= $semester ?></strong>
                    </p>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=mapel&method=update_guru" method="POST">
                        <input type="hidden" name="id_mapel" value="<?= $mapel['id_mapel'] ?>">
                        <input type="hidden" name="id_mapel_guru" value="<?= $data['id_mapel_guru'] ?>">
                        <input type="hidden" name="id_tahun_pelajaran" value="<?= $id_tahun_aktif ?>">
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Kelas</label>
                            <select name="id_kelas"
                                class="form-control select2 border focus-ring focus-ring-success rounded-3"
                                required>
                                <?php foreach ($kelas as $k): ?>
                                    <option value="<?= $k['id_kelas'] ?>" <?= $k['id_kelas'] == $data['id_kelas'] ? 'selected' : '' ?>>
                                        <?= $k['kelas'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Guru Pengajar</label>
                            <select name="id_guru"
                                class="form-control select2 border focus-ring focus-ring-success rounded-3"
                                required>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id_employe'] ?>" <?= $u['id_employe'] == $data['id_guru'] ? 'selected' : '' ?>>
                                        <?= $u['nama'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=mapel&method=guru&id=<?= $mapel['id_mapel'] ?>" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-success text-white px-4">Simpan</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JS: jQuery & Select2 -->

<script src="assets/js/select2.min.js"></script>
<script>
    $('.select2').select2({
        theme: 'bootstrap4', // ini kunci tampilannya
        placeholder: "-- Pilih Guru --",
        allowClear: true,
        width: '100%'
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>