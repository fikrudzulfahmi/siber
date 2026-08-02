<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .form-control,
    .select2-container {
        width: 100% !important;
    }

    /* Pewarnaan Status Kehadiran agar visual lebih jelas */
    .status-H {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .status-S {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-I {
        background-color: #cff4fc;
        color: #055160;
    }

    .status-A {
        background-color: #f8d7da;
        color: #842029;
    }

    .status-B {
        background-color: #f1aeb5;
        color: #842029;
    }
    
    /* Custom Styling Tombol Presensi */
    .btn-presensi {
        min-width: 45px !important;
        padding: 6px 10px !important;
        font-size: 0.95rem !important;
        font-weight: bold !important;
        background-color: transparent;
        border: 1px solid #ced4da !important; /* Pastikan garis batas muncul */
        color: #6c757d;
        box-shadow: none !important;
    }
    
    /* Hilangkan efek hover sama sekali */
    .btn-presensi:hover {
        background-color: transparent !important;
        color: #6c757d !important;
        transform: none !important;
        box-shadow: none !important;
        border-color: #ced4da !important;
    }

    /* Warna saat terpilih (checked) */
    .btn-check:checked + .btn-presensi.btn-p-h { background-color: #4caf50 !important; border-color: #4caf50 !important; color: white !important; }
    .btn-check:checked + .btn-presensi.btn-p-s { background-color: #ff9800 !important; border-color: #ff9800 !important; color: white !important; }
    .btn-check:checked + .btn-presensi.btn-p-i { background-color: #03a9f4 !important; border-color: #03a9f4 !important; color: white !important; }
    .btn-check:checked + .btn-presensi.btn-p-a { background-color: #f44336 !important; border-color: #f44336 !important; color: white !important; }
    .btn-check:checked + .btn-presensi.btn-p-b { background-color: #344767 !important; border-color: #344767 !important; color: white !important; }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-warning shadow-warning border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Edit Jurnal Pembelajaran</h6>
                    </div>
                </div>

                <div class="card-body px-4 py-4">
                    <form action="?controller=jurnal&method=update" method="POST">
                        <input type="hidden" name="id" value="<?= $jurnal['id_jurnal'] ?>">

                        <div class="row mb-4">
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label text-dark fw-bold">Kelas</label>
                                <select id="kelas" name="id_kelas" class="form-control border focus-ring focus-ring-warning rounded-3 select2" required>
                                    <option disabled>-- Pilih Kelas --</option>
                                    <?php foreach ($kelas as $k): ?>
                                        <option value="<?= $k['id_kelas'] ?>" <?= $k['id_kelas'] == $jurnal['id_kelas'] ? 'selected' : '' ?>>
                                            <?= $k['kelas'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label text-dark fw-bold">Mata Pelajaran</label>
                                <select id="mapel" name="id_mapel_guru" class="form-control border focus-ring focus-ring-warning rounded-3 select2" required>
                                    <option value="">-- Pilih Kelas Dahulu --</option>
                                    <?php foreach ($mapelList as $m): ?>
                                        <option value="<?= $m['id_mapel_guru'] ?>" <?= $m['id_mapel_guru'] == $jurnal['id_mapel_guru'] ? 'selected' : '' ?>>
                                            <?= $m['nama_mapel'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold">Tujuan Pembelajaran</label>
                            <div id="tujuan-container">
                                <div class="tp-row d-flex align-items-center mb-2">
                                    <select name="id_tp" id="tp-select" class="form-control border focus-ring focus-ring-warning rounded-3 select2" required>
                                        <option value="">-- Pilih Tujuan --</option>
                                        <?php foreach ($tpList as $tp): ?>
                                            <option value="<?= $tp['id_tp'] ?>" <?= $tp['id_tp'] == $jurnal['id_tp'] ? 'selected' : '' ?>>
                                                <?= $tp['tujuan_pembelajaran'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted">*Anda sedang mengedit 1 entri jurnal. Untuk menambah TP lain, silakan buat jurnal baru.</small>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Jam Mulai</label>
                                <select name="jam_mulai" class="form-control border focus-ring focus-ring-warning rounded-3" required>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == $jurnal['jam_mulai'] ? 'selected' : '' ?>>Jam ke-<?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Jam Akhir</label>
                                <select name="jam_akhir" class="form-control border focus-ring focus-ring-warning rounded-3" required>
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == $jurnal['jam_akhir'] ? 'selected' : '' ?>>Jam ke-<?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold">Materi Pembelajaran</label>
                            <textarea name="materi" class="form-control border focus-ring focus-ring-warning rounded-3 p-2" rows="2" required><?= $jurnal['materi'] ?></textarea>
                        </div>

                        <div class="card mb-4 border">
                            <div class="card-header bg-light">
                                <h6 class="text-dark mb-0">Kehadiran Siswa</h6>
                            </div>
                            <div class="card-body" id="daftar-siswa" style="overflow-y: auto;">
                                <?php if (empty($siswaList)): ?>
                                    <p class="text-center text-muted mt-3">Siswa tidak ditemukan.</p>
                                <?php else: ?>
                                    <?php foreach ($siswaList as $idx => $s): ?>
                                        <?php
                                        // Cek Status tersimpan di database
                                        $status = 'H'; // Default Hadir
                                        if (isset($jurnal['kehadiran'])) {
                                            foreach ($jurnal['kehadiran'] as $k) {
                                                if ($k['id_siswa'] == $s['id_siswa']) {
                                                    $status = $k['status'];
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 flex-wrap">
                                            <span class="text-sm fw-bold mb-2 mb-md-0"><?= $idx + 1 ?>. <?= $s['nama_siswa'] ?></span>
                                            <div class="btn-group" role="group" aria-label="Kehadiran Siswa">
                                                <input type="radio" class="btn-check" name="kehadiran[<?= $s['id_siswa'] ?>]" id="H_<?= $s['id_siswa'] ?>" value="H" autocomplete="off" <?= $status == 'H' ? 'checked' : '' ?>>
                                                <label class="btn btn-presensi btn-p-h" for="H_<?= $s['id_siswa'] ?>">H</label>
                                                
                                                <input type="radio" class="btn-check" name="kehadiran[<?= $s['id_siswa'] ?>]" id="S_<?= $s['id_siswa'] ?>" value="S" autocomplete="off" <?= $status == 'S' ? 'checked' : '' ?>>
                                                <label class="btn btn-presensi btn-p-s" for="S_<?= $s['id_siswa'] ?>">S</label>
                                                
                                                <input type="radio" class="btn-check" name="kehadiran[<?= $s['id_siswa'] ?>]" id="I_<?= $s['id_siswa'] ?>" value="I" autocomplete="off" <?= $status == 'I' ? 'checked' : '' ?>>
                                                <label class="btn btn-presensi btn-p-i" for="I_<?= $s['id_siswa'] ?>">I</label>
                                                
                                                <input type="radio" class="btn-check" name="kehadiran[<?= $s['id_siswa'] ?>]" id="A_<?= $s['id_siswa'] ?>" value="A" autocomplete="off" <?= $status == 'A' ? 'checked' : '' ?>>
                                                <label class="btn btn-presensi btn-p-a" for="A_<?= $s['id_siswa'] ?>">A</label>
                                                
                                                <input type="radio" class="btn-check" name="kehadiran[<?= $s['id_siswa'] ?>]" id="B_<?= $s['id_siswa'] ?>" value="B" autocomplete="off" <?= $status == 'B' ? 'checked' : '' ?>>
                                                <label class="btn btn-presensi btn-p-b" for="B_<?= $s['id_siswa'] ?>">B</label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="fw-bold">Catatan Kehadiran</label>
                                <textarea name="catatan_kehadiran" class="form-control border focus-ring focus-ring-warning rounded-3 p-2"><?= $jurnal['catatan_kehadiran'] ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold">Catatan Pembelajaran</label>
                                <textarea name="catatan_pembelajaran" class="form-control border focus-ring focus-ring-warning rounded-3 p-2"><?= $jurnal['catatan_pembelajaran'] ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="?controller=jurnal&method=history" class="btn btn-outline-secondary me-2">Kembali</a>
                            <button type="submit" class="btn bg-gradient-warning text-white px-5">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Fungsi ganti warna select kehadiran
    function updateColor(selectInfo) {
        // Hapus semua class status
        selectInfo.classList.remove('status-H', 'status-S', 'status-I', 'status-A', 'status-B');
        // Tambah class sesuai value baru
        selectInfo.classList.add('status-' + selectInfo.value);
    }

    $(document).ready(function() {
        $('.select2').select2();

        // ----------------------------------------------------
        // LOGIKA AJAX SAMA SEPERTI INPUT
        // (Hanya berjalan jika user MENGUBAH Kelas/Mapel)
        // ----------------------------------------------------

        // 1. EVENT KETIKA KELAS BERUBAH (RESET SEMUA DATA)
        $('#kelas').on('change', function() {
            // Kita cek apakah ini perubahan manual user atau load awal
            // Tapi karena di Edit kita sudah pre-fill via PHP, event ini biasanya trigger saat user iseng ganti kelas.
            // Maka kita reset mapel & siswa.

            const id_kelas = $(this).val();

            // Reset UI
            $('#mapel').html('<option>Memuat...</option>');
            $('#daftar-siswa').html('<p class="text-center py-3">Memuat siswa...</p>');
            $('#tp-select').html('<option>-- Pilih Mapel Dahulu --</option>');

            // A. AJAX Mapel
            fetch(`?controller=jurnal&method=ajaxGetMapel&id_kelas=${id_kelas}`)
                .then(res => res.json())
                .then(data => {
                    let opsi = '<option value="">-- Pilih Mata Pelajaran --</option>';
                    if (data.length > 0) {
                        data.forEach(d => opsi += `<option value="${d.id_mapel_guru}">${d.nama_mapel}</option>`);
                    } else {
                        opsi = '<option disabled>Tidak ada mapel terploting</option>';
                    }
                    $('#mapel').html(opsi).trigger('change');
                });

            // B. AJAX Siswa (Default Status Hadir semua karena kelas berubah)
            fetch(`?controller=jurnal&method=ajaxGetSiswa&id_kelas=${id_kelas}`)
                .then(response => response.text())
                .then(rawText => {
                    try {
                        const data = JSON.parse(rawText);
                        if (data.error) {
                            $('#daftar-siswa').html(`<div class="alert alert-danger">${data.message}</div>`);
                            return;
                        }
                        let html = '';
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach((s, idx) => {
                                html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2 flex-wrap">
                                <span class="text-sm fw-bold mb-2 mb-md-0">${idx+1}. ${s.nama_siswa}</span>
                                <div class="btn-group" role="group" aria-label="Kehadiran Siswa">
                                    <input type="radio" class="btn-check" name="kehadiran[${s.id_siswa}]" id="H_${s.id_siswa}" value="H" autocomplete="off" checked>
                                    <label class="btn btn-presensi btn-p-h" for="H_${s.id_siswa}">H</label>
                                    
                                    <input type="radio" class="btn-check" name="kehadiran[${s.id_siswa}]" id="S_${s.id_siswa}" value="S" autocomplete="off">
                                    <label class="btn btn-presensi btn-p-s" for="S_${s.id_siswa}">S</label>
                                    
                                    <input type="radio" class="btn-check" name="kehadiran[${s.id_siswa}]" id="I_${s.id_siswa}" value="I" autocomplete="off">
                                    <label class="btn btn-presensi btn-p-i" for="I_${s.id_siswa}">I</label>
                                    
                                    <input type="radio" class="btn-check" name="kehadiran[${s.id_siswa}]" id="A_${s.id_siswa}" value="A" autocomplete="off">
                                    <label class="btn btn-presensi btn-p-a" for="A_${s.id_siswa}">A</label>
                                    
                                    <input type="radio" class="btn-check" name="kehadiran[${s.id_siswa}]" id="B_${s.id_siswa}" value="B" autocomplete="off">
                                    <label class="btn btn-presensi btn-p-b" for="B_${s.id_siswa}">B</label>
                                </div>
                            </div>`;
                            });
                        } else {
                            html = '<p class="text-center text-danger mt-3">Tidak ada siswa.</p>';
                        }
                        $('#daftar-siswa').html(html);
                    } catch (e) {
                        console.error(e);
                    }
                });
        });

        // 2. EVENT KETIKA MAPEL BERUBAH
        $('#mapel').on('change', function() {
            const id_mapel_guru = $(this).val();
            if (!id_mapel_guru) return;

            // Jangan reset jika value sama dengan database (saat load awal - meski PHP sudah handle, safety check)
            // Tapi karena AJAX ini async, kita biarkan PHP yang menangani load awal.
            // Script ini hanya aktif kalau user klik dropdown Mapel.

            // fetch TP
            fetch(`?controller=jurnal&method=ajaxGetTp&id_mapel_guru=${id_mapel_guru}`)
                .then(res => res.json())
                .then(data => {
                    let html = '<option value="">-- Pilih Tujuan Pembelajaran --</option>';
                    if (data.length > 0) {
                        data.forEach(d => {
                            html += `<option value="${d.id_tp}">${d.tujuan_pembelajaran}</option>`;
                        });
                    } else {
                        html = '<option value="">Tidak ada TP</option>';
                    }
                    $('#tp-select').html(html);
                });
        });

    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>