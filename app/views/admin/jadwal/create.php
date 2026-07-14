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
</style>

<style>
    /* ...existing code... */

    /* Rapi tombol hapus dan tambah tujuan */
    .tp-row .btn-hapus-tp {
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        line-height: 1.2;
        border-radius: 0.3rem;
        margin-left: 0.5rem;
        font-weight: 500;
        letter-spacing: 0.5px;
        min-width: 38px;
        min-height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #tambah-tujuan {
        padding: 0.3rem 1.1rem;
        font-size: 0.75rem;
        border-radius: 0.5rem;
        font-weight: 600;
        box-shadow: 0 2px 6px #d4f5e2;
        letter-spacing: 0.5px;
    }
</style>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Input Jadwal Pelajaran</h6>
                        
                         <p class="text-white text-sm ps-3 mb-0">
                            Tahun Pelajaran: <strong><?= $tahun_aktif['tahun_pelajaran'] ?></strong> |
                            Semester: <strong><?= $tahun_aktif['semester'] ?></strong>
                        </p>
                    </div>
                </div>
                <div class="card-body px-4 py-4">
                    <form action="index.php?controller=jadwal&method=store" method="POST">
            
            <div class="mb-3">
                <label for="id_kelas" class="form-label">Kelas</label>
                <select name="id_kelas" id="id_kelas" class="form-control border focus-ring focus-ring-success rounded-3" required onchange="loadMapelAndJam()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>" 
                            <?= ($id_kelas_terpilih == $k['id_kelas']) ? 'selected' : '' ?>>
                            <?= $k['kelas'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_mapel_guru" class="form-label">Mata Pelajaran (Sesuai Tahun Aktif)</label>
                <select name="id_mapel_guru" id="id_mapel_guru" class="form-control border focus-ring focus-ring-success rounded-3" required>
                    <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                </select>
                <small class="text-muted">Hanya mapel yang diajarkan di kelas terpilih pada tahun ini yang muncul.</small>
            </div>

            <div class="mb-3">
                <label for="hari" class="form-label">Hari</label>
                <select name="hari" id="hari" class="form-control border focus-ring focus-ring-success rounded-3" required onchange="cekJamTerpakai()">
                    <?php 
                    $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    foreach ($days as $d): ?>
                        <option value="<?= $d ?>" <?= ($hari_terpilih == $d) ? 'selected' : '' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="jam_mulai" class="form-label">Jam Mulai (Ke-)</label>
                    <input type="number" name="jam_mulai" id="jam_mulai" class="form-control border focus-ring focus-ring-success rounded-3" min="1" max="15" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="jam_selesai" class="form-label">Jam Selesai (Ke-)</label>
                    <input type="number" name="jam_selesai" id="jam_selesai" class="form-control border focus-ring focus-ring-success rounded-3" min="1" max="15" required>
                </div>
            </div>

            <div id="info-jam" class="alert alert-info d-none">
                <strong>Info:</strong> Jam yang sudah terpakai di hari ini: <span id="list-jam-terpakai">-</span>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
            <a href="index.php?controller=jadwal&method=index&id_kelas=<?= $id_kelas_terpilih ?>" class="btn btn-secondary">Batal</a>
        </form>

                    <script>
    // Fungsi Utama: Load Mapel saat kelas berubah
    function loadMapelAndJam() {
        const idKelas = document.getElementById('id_kelas').value;
        const selectMapel = document.getElementById('id_mapel_guru');

        // Reset Dropdown
        selectMapel.innerHTML = '<option value="">Memuat data...</option>';

        if (!idKelas) {
            selectMapel.innerHTML = '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>';
            return;
        }

        // Panggil Controller via AJAX
        fetch(`index.php?controller=jadwal&method=getMapelGuruByKelas&id_kelas=${idKelas}`)
            .then(response => response.json())
            .then(data => {
                selectMapel.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                
                if (data.length === 0) {
                    selectMapel.innerHTML += '<option value="" disabled>Tidak ada mapel untuk kelas ini di tahun aktif</option>';
                }

                data.forEach(item => {
                    // Tampilkan Nama Mapel dan Guru
                    const option = document.createElement('option');
                    option.value = item.id_mapel_guru;
                    option.textContent = `${item.nama_mapel} (${item.nama_guru})`;
                    selectMapel.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                selectMapel.innerHTML = '<option value="">Gagal memuat data</option>';
            });

        // Cek juga jam terpakai jika hari sudah dipilih
        cekJamTerpakai();
    }

    // Fungsi Tambahan: Cek Jam yang sudah dipakai
    function cekJamTerpakai() {
        const idKelas = document.getElementById('id_kelas').value;
        const hari = document.getElementById('hari').value;
        const infoBox = document.getElementById('info-jam');
        const spanList = document.getElementById('list-jam-terpakai');

        if (!idKelas || !hari) {
            infoBox.classList.add('d-none');
            return;
        }

        fetch(`index.php?controller=jadwal&method=getJamTerpakaiByKelasDanHari&id_kelas=${idKelas}&hari=${hari}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    infoBox.classList.remove('d-none');
                    // Urutkan dan tampilkan jam
                    data.sort((a, b) => a - b);
                    spanList.textContent = data.join(', ');
                } else {
                    infoBox.classList.add('d-none');
                }
            });
    }

    // Jalankan otomatis saat halaman dimuat (jika mode edit atau ada pre-select dari URL)
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('id_kelas').value) {
            loadMapelAndJam();
        }
    });
</script>
                </div>
            </div>
        </div>
    </div>
</div>



<?php include '../app/views/layouts/footer.php'; ?>