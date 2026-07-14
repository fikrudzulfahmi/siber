<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<?php if ($msg = getFlash('success')): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: <?= json_encode($msg) ?>,
            confirmButtonColor: '#4caf50'
        });
    </script>
<?php endif; ?>
<?php if ($msg = getFlash('warning')): ?>
    <script>
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: <?= json_encode($msg) ?>,
            confirmButtonColor: '#ff9800'
        });
    </script>
<?php endif; ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .form-control,
    .select2-container {
        width: 100% !important;
    }

    .tp-row .btn-hapus-tp {
        margin-left: 0.5rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Input Jurnal Pembelajaran</h6>
                    </div>
                </div>

                <div class="my-4 mx-4">
                    <a href="?controller=jurnal&method=history" class="btn btn-dark">Riwayat Jurnal</a>
                </div>

                <div class="card-body px-4 pb-4">
                    <form action="?controller=jurnal&method=simpan" method="POST">
                        <div class="row mb-4">
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label text-dark fw-bold">Kelas</label>
                                <select id="kelas" name="id_kelas" class="form-control border focus-ring focus-ring-success rounded-3 select2" required>
                                    <option disabled selected>-- Pilih Kelas --</option>
                                    <?php foreach ($kelas as $k): ?>
                                        <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label text-dark fw-bold">Mata Pelajaran</label>
                                <select id="mapel" name="id_mapel_guru" class="form-control border focus-ring focus-ring-success rounded-3 select2" required>
                                    <option value="">-- Pilih Kelas Dahulu --</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold">Tujuan Pembelajaran</label>
                            <div id="tujuan-container">
                                <div class="tp-row d-flex align-items-center mb-2">
                                    <select name="tujuan_pembelajaran[]" id="tp-0" class="form-control border focus-ring focus-ring-success rounded-3 tujuan-select me-2" required>
                                        <option value="">-- Pilih Mapel Dahulu --</option>
                                    </select>
                                    <button type="button" class="btn btn-danger btn-sm btn-hapus-tp" style="display:none; white-space: nowrap;">Hapus</button>
                                </div>
                            </div>
                            <button type="button" id="tambah-tujuan" class="btn btn-success btn-sm mt-2">Tambah TP</button>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Jam Mulai</label>
                                <select name="jam_mulai" class="form-control border focus-ring focus-ring-success rounded-3" required>
                                    <?php for ($i = 1; $i <= 10; $i++): echo "<option value='$i'>Jam ke-$i</option>";
                                    endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="fw-bold">Jam Akhir</label>
                                <select name="jam_akhir" class="form-control border focus-ring focus-ring-success rounded-3" required>
                                    <?php for ($i = 1; $i <= 10; $i++): echo "<option value='$i'>Jam ke-$i</option>";
                                    endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold">Materi Pembelajaran</label>
                            <textarea name="materi" class="form-control border focus-ring focus-ring-success rounded-3 p-2" rows="2" required></textarea>
                        </div>

                        <div class="card mb-4 border">
                            <div class="card-header bg-light">
                                <h6 class="text-dark mb-0">Kehadiran Siswa</h6>
                            </div>
                            <div class="card-body" id="daftar-siswa" style=" overflow-y: auto;">
                                <p class="text-center text-muted mt-3">Pilih kelas untuk memuat daftar siswa.</p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="fw-bold">Catatan Kehadiran</label>
                                <textarea name="catatan_kehadiran" class="form-control border focus-ring focus-ring-success rounded-3 p-2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold">Catatan Pembelajaran</label>
                                <textarea name="catatan_pembelajaran" class="form-control border focus-ring focus-ring-success rounded-3 p-2"></textarea>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn bg-gradient-success text-white px-5">Simpan</button>
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
    $(document).ready(function() {
        $('.select2').select2();

        // Variabel Global untuk menampung TP agar bisa dibaca oleh fungsi lain
        let tpGlobal = [];

        // ============================================================
        // 1. EVENT KETIKA KELAS BERUBAH
        // ============================================================
        $('#kelas').on('change', function() {
            const id_kelas = $(this).val();

            // Reset Dropdowns & UI
            $('#mapel').html('<option>Memuat...</option>');
            $('#daftar-siswa').html('<p class="text-center py-3">Memuat siswa...</p>');
            $('#tp-0').html('<option>-- Pilih Mapel Dahulu --</option>');

            // Bersihkan tpGlobal saat ganti kelas
            tpGlobal = [];

            // A. AJAX: Ambil Mapel
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

            // B. AJAX: Ambil Siswa (Kode Anda yang sudah Benar & Aman)
            fetch(`?controller=jurnal&method=ajaxGetSiswa&id_kelas=${id_kelas}`)
                .then(response => response.text())
                .then(rawText => {
                    try {
                        const data = JSON.parse(rawText);

                        // Cek error dari Controller
                        if (data.error) {
                            $('#daftar-siswa').html(`<div class="alert alert-danger text-white text-center">${data.message}</div>`);
                            return;
                        }

                        let html = '';
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach((s, idx) => {
                                html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <span class="text-sm fw-bold">${idx+1}. ${s.nama_siswa}</span>
                                <select name="kehadiran[${s.id_siswa}]" class="form-select form-select-sm border focus-ring focus-ring-success rounded-3 text-center w-auto" style="min-width:80px">
                                    <option value="H" selected>Hadir</option>
                                    <option value="S">Sakit</option>
                                    <option value="I">Izin</option>
                                    <option value="A">Alpa</option>
                                    <option value="B">Bolos</option>
                                </select>
                            </div>`;
                            });
                        } else {
                            html = '<p class="text-center text-danger mt-3">Tidak ada siswa ditemukan di ploting kelas ini untuk Tahun Ajaran aktif.</p>';
                        }
                        $('#daftar-siswa').html(html);

                    } catch (e) {
                        console.error("Error Parsing JSON:", e);
                        console.log("Server Response:", rawText);
                        $('#daftar-siswa').html(`<div class="alert alert-danger text-white">Terjadi Error Sistem.<br>Response: ${rawText.substring(0, 100)}...</div>`);
                    }
                })
                .catch(err => {
                    $('#daftar-siswa').html('<p class="text-center text-danger">Gagal menghubungi server.</p>');
                });
        });

        // ============================================================
        // 2. EVENT KETIKA MAPEL BERUBAH (Dikeluarkan dari event Kelas)
        // ============================================================
        $('#mapel').on('change', function() {
            const id_mapel_guru = $(this).val();

            // Reset select TP
            $('.tujuan-select').html('<option>Memuat...</option>');

            if (!id_mapel_guru) {
                $('.tujuan-select').html('<option value="">-- Pilih Mapel Dahulu --</option>');
                return;
            }

            // AJAX: Ambil TP
            fetch(`?controller=jurnal&method=ajaxGetTp&id_mapel_guru=${id_mapel_guru}`)
                .then(res => res.json())
                .then(data => {
                    tpGlobal = data; // Simpan ke variabel global
                    updateAllTpSelects(); // Update semua dropdown TP yang ada
                })
                .catch(err => {
                    $('.tujuan-select').html('<option value="">Gagal memuat TP</option>');
                });
        });

        // ============================================================
        // 3. FUNGSI HELPER & DINAMIS (Dikeluarkan dari event Kelas)
        // ============================================================

        function updateAllTpSelects() {
            $('.tujuan-select').each(function() {
                const val = $(this).val(); // Simpan nilai yang sedang dipilih (jika ada)
                let html = '<option value="">-- Pilih Tujuan Pembelajaran --</option>';

                if (tpGlobal.length > 0) {
                    tpGlobal.forEach(d => {
                        // Cek agar nilai yang sudah dipilih tidak hilang saat refresh
                        const sel = (val == d.id_tp) ? 'selected' : '';
                        html += `<option value="${d.id_tp}" ${sel}>${d.tujuan_pembelajaran}</option>`;
                    });
                } else {
                    html = '<option value="">Tidak ada TP untuk mapel & tahun ini</option>';
                }
                $(this).html(html);
            });
        }

        // Tombol Tambah TP
        $('#tambah-tujuan').click(function() {
            const div = `
        <div class="tp-row d-flex align-items-center mb-2">
            <select name="tujuan_pembelajaran[]" class="form-control tujuan-select me-2" required>
                <option value="">-- Pilih Tujuan Pembelajaran --</option>
            </select>
            <button type="button" class="btn btn-danger btn-sm btn-hapus-tp " style="white-space: nowrap;">Hapus</button>
        </div>`;
            $('#tujuan-container').append(div);

            // Isi dropdown baru dengan data TP yang sudah ada di memori
            updateAllTpSelects();
            cekTombolHapus();
        });

        // Tombol Hapus TP (Event Delegation)
        $(document).on('click', '.btn-hapus-tp', function() {
            $(this).closest('.tp-row').remove();
            cekTombolHapus();
        });

        function cekTombolHapus() {
            const rows = $('.tp-row');
            if (rows.length > 1) rows.find('.btn-hapus-tp').show();
            else rows.find('.btn-hapus-tp').hide();
        }

    }); // End Document Ready
</script>

<?php include '../app/views/layouts/footer.php'; ?>