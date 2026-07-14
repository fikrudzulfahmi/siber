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
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<!-- Custom Style for Form Control -->
<style>
    .form-control {
        padding-left: 1rem !important;
    }
</style>

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

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Siswa</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=siswa&method=store" method="POST">

                        <!-- Nama Siswa -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Nama Siswa</label>
                            <input type="text" name="siswa"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : Budi " required>
                        </div>

                        <!-- NISN -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">NISN</label>
                            <input type="number" name="nisn"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : 00xxxxxxxx" required>
                        </div>
                        <!-- Tempat Lahir -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tempat Lahir</label>
                            <input type="text" name="tempat_lhr"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : Blitar" required>
                        </div>
                        <!-- Tgl Lahir -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Tanggal Lahir</label>
                            <input type="date" name="tgl_lhr"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : 09/12/2008" required>
                        </div>

                        <!-- Wali Kelas -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Kelas</label>
                            <select name="id_kelas"
                                class="form-control select2 border focus-ring focus-ring-success rounded-3"
                                required>
                                <option value="" disabled selected>-- Pilih Kelas --</option>
                                <?php foreach ($kelas as $k): ?>
                                    <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Alamat -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Alamat</label>
                            <input type="text" name="alamat"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : Jl. Raya brantas No.5 RT.02 RW.02 Desa Minggirsari Kecamatan Kanigoro Kabupaten Blitar" required>
                        </div>

                        <!-- Nama Wali -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">Nama Wali</label>
                            <input type="text" name="nama_wali"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : Budiono" required>
                        </div>

                        <!-- No HP Wali -->
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold">No HP Wali</label>
                            <input type="number" name="hp_wali"
                                class="form-control border focus-ring focus-ring-success rounded-3"
                                placeholder="Contoh : 08xxxxxxx" required>
                        </div>


                        <!-- Tombol -->
                        <div class="d-flex justify-content-end">
                            <a href="?controller=siswa&method=index" class="btn btn-outline-secondary me-2">Kembali</a>
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
        placeholder: "-- Pilih --",
        allowClear: true,
        width: '100%'
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>