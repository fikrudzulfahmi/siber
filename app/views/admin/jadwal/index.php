<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

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
<?php if ($msg = getFlash('warning')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Gagal!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#4caf50',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>
<?php if ($msg = getFlash('danger')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'danger',
                title: 'Gagal',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#4caf50',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>

<style>
    /* Perbaiki lebar form control agar tidak melebihi parent */
    .form-control,
    .select2-container {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }

    /* Hapus overflow horizontal di container utama */
    body,
    html {
        overflow-x: hidden;
    }

    /* Pastikan form container padding responsif */
    .card-body {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* Responsif untuk padding dan layout */
    @media (max-width: 768px) {
        .row {
            flex-direction: column;
        }

        .col-md-4 {
            width: 100%;
        }

        .select2-container {
            width: 100% !important;
        }
    }

    /* Custom select2 tampilan tetap */
    .form-control {
        padding-left: 1rem !important;
    }

    .select2-results__option {
        padding-left: 1.5rem !important;
        position: relative;
    }

    .select2-results__option::before {
        content: "☐";
        position: absolute;
        left: 0.5rem;
        color: #aaa;
    }

    .select2-results__option--selected::before {
        content: "☑";
        color: #28a745;
    }
</style>





<style>
    /* Mengatur wadah nav-pills */
    #jadwalTab {
        background-color: #f8f9fa;
        border-radius: 0.75rem;
        padding: 0.5rem;
        display: inline-flex;
        /* Agar background pas dengan isi */
    }

    /* Gaya untuk tombol/link hari */
    .nav-pills .nav-link {
        color: #6c757d;
        /* Warna teks untuk hari tidak aktif */
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease-in-out;
    }

    /* Gaya untuk tombol HARI YANG AKTIF */
    .nav-pills .nav-link.active {
        color: #fff;
        /* Gunakan gradient yang sama dengan header card Anda */
        background-color: #28a745;
    }

    /* Efek hover untuk tombol yang tidak aktif */
    .nav-pills .nav-link:not(.active):hover {
        background-color: #e9ecef;
        color: #344767;
    }
</style>



<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Jadwal Pelajaran</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="" method="GET" class="d-flex">
            <input type="hidden" name="controller" value="jadwal">
            <input type="hidden" name="method" value="index">
            
            <select name="id_kelas" class="form-control border focus-ring focus-ring-success rounded-3 me-2 mb-3" onchange="this.form.submit()">
                <?php foreach ($kelasList as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>" <?= ($id_kelas == $k['id_kelas']) ? 'selected' : '' ?>>
                        <?= $k['kelas'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>




                    <!-- Tab hari -->
                    <?php $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    $hariAktif = $_GET['hari'] ?? $hariList[0];  ?>
                    <ul class="nav nav-pills mb-3" id="jadwalTab" role="tablist">
                        <?php foreach ($hariList as $hari): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= (strtolower($hari) == strtolower($hariAktif)) ? 'active' : '' ?>"
                                    id="<?= strtolower($hari) ?>-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#<?= strtolower($hari) ?>"
                                    type="button" role="tab">
                                    <?= $hari ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Isi tab -->
                    <div class="tab-content mt-3" id="jadwalTabContent">
                        <?php foreach ($hariList as $hari): ?>
                            <div class="tab-pane fade <?= (strtolower($hari) == strtolower($hariAktif)) ? 'show active' : '' ?>"
                                id="<?= strtolower($hari) ?>" role="tabpanel">
                                <table
                                    class="table table-bordered table-striped align-items-center mb-0">
                                    <?php if (!isLevel($id_level, 7)): ?>
                                        <div class="my-4 mx-4">
                                            <a href="?controller=jadwal&method=create&id_kelas=<?= $id_kelas; ?>&hari=<?= $hari; ?>" class="btn btn-dark mb-3">Tambah Jadwal</a>
                                        </div>
                                    <?php endif; ?>
                                    <thead>
                                        <tr>
                                            <th>Jam</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Guru</th>
                                            <?php if (!isLevel($id_level, 7)): ?>
                                                <th>Aksi</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $found = false;
                                        foreach ($jadwal as $j):
                                            if ($j['hari'] == $hari): $found = true; ?>
                                                <tr>
                                                    <td><?= $j['jam_mulai'] ?> - <?= $j['jam_selesai'] ?></td>
                                                    <td><?= $j['nama_mapel'] ?></td>
                                                    <td><?= $j['nama_guru'] ?></td>
                                                    <?php if (!isLevel($id_level, 7)): ?>
                                                        <td>
                                                            <a href="?controller=jadwal&method=delete&id=<?= $j['id_jadwal'] ?>&id_kelas=<?= $id_kelas ?>&hari=<?= $hari; ?>"
                                                                class="btn btn-dark btn-sm delete-btn">
                                                                Hapus
                                                            </a>

                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                        <?php endif;
                                        endforeach; ?>
                                        <?php if (!$found): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Tidak ada jadwal</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Mencegah link langsung berjalan
                const href = this.getAttribute('href');
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Jadwal yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href; // Lanjutkan ke link hapus jika dikonfirmasi
                    }
                });
            });
        });
    </script>

    <?php include '../app/views/layouts/footer.php'; ?>