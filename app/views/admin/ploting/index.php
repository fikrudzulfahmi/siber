<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <div class="row mb-4">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header  p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Ploting / Kenaikan Kelas Siswa</h6>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-sm mb-0">Pilih kelas asal dan kelas tujuan, lalu centang siswa yang akan dipindahkan.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <strong><i class="fas fa-sign-out-alt"></i> Dari Kelas (Asal)</strong>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-12">
                            <label class="d-block">Mode Sumber Siswa</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="mode_sumber" id="mode_pindah" value="pindah" checked onchange="toggleModeSumber()">
                                <label class="form-check-label" for="mode_pindah">Pindah Kelas (sudah ada kelas)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="mode_sumber" id="mode_baru" value="baru" onchange="toggleModeSumber()">
                                <label class="form-check-label" for="mode_baru">Siswa Baru (belum ada kelas)</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Tahun Ajaran Lama</label>
                            <select id="th_asal" class="form-control border px-2">
                                <option value="">Pilih Tahun</option>
                                <?php foreach ($tahun_ajaran as $t): ?>
                                    <option value="<?= $t['id_tahun_pelajaran'] ?>"><?= $t['tahun_pelajaran'] ?> (<?= $t['semester'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2" id="wrap_kls_asal">
                            <label>Kelas Asal</label>
                            <select id="kls_asal" class="form-control border px-2">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-secondary btn-sm w-100 mt-2" onclick="loadSiswaAsal()">Tampilkan Siswa</button>
                        </div>
                    </div>

                    <div class="table-responsive mt-3" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-sm text-center align-middle">
                            <thead class="table-dark text-white">
                                <tr>
                                    <th width="10%"><input type="checkbox" id="checkAll"></th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                </tr>
                            </thead>
                            <tbody id="list_siswa_asal">
                                <tr>
                                    <td colspan="3" class="text-muted">Silahkan pilih filter...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 d-flex align-items-center justify-content-center py-3">
            <div class="text-center">
                <button id="btnProses" class="btn btn-success btn-lg shadow rounded-circle p-4">
                    <i class="fas fa-arrow-right fa-2x"></i>
                </button>
                <div class="mt-2 font-weight-bold text-success">PROSES</div>

                <button id="btnLuluskan" class="btn btn-warning btn-sm shadow rounded-circle p-3 mt-4">
                    <i class="fas fa-user-graduate"></i>
                </button>
                <div class="mt-2 font-weight-bold text-warning" style="font-size: 0.8rem;">LULUSKAN</div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <strong><i class="fas fa-sign-in-alt"></i> Ke Kelas (Tujuan)</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Tahun Ajaran Baru</label>
                            <select id="th_tujuan" class="form-control border px-2">
                                <option value="">Pilih Tahun</option>
                                <?php foreach ($tahun_ajaran as $t): ?>
                                    <option value="<?= $t['id_tahun_pelajaran'] ?>"><?= $t['tahun_pelajaran'] ?> (<?= $t['semester'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Kelas Tujuan</label>
                            <select id="kls_tujuan" class="form-control border px-2" onchange="loadSiswaTujuan()">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= $k['id_kelas'] ?>"><?= $k['kelas'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive mt-3" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-striped table-sm text-center">
                            <thead class="bg-success text-white">
                                <tr>
                                    <th>No</th>
                                    <th>Siswa Terdaftar</th>
                                </tr>
                            </thead>
                            <tbody id="list_siswa_tujuan">
                                <tr>
                                    <td colspan="2" class="text-muted">Belum ada siswa di kelas tujuan ini.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // --- 0. Toggle Mode Sumber (Pindah Kelas vs Siswa Baru) ---
    function toggleModeSumber() {
        let mode = $('input[name="mode_sumber"]:checked').val();
        if (mode === 'baru') {
            $('#wrap_kls_asal').hide();
            $('#kls_asal').val(''); // kelas asal tidak relevan untuk siswa baru
        } else {
            $('#wrap_kls_asal').show();
        }
        // Reset tabel setiap ganti mode
        $('#list_siswa_asal').html('<tr><td colspan="3" class="text-muted">Silahkan pilih filter...</td></tr>');
    }

    // --- 1. Load Data Siswa ASAL (Kiri) ---
    // Menangani 2 mode: "pindah" (siswa yang sudah ada di kelas+tahun tertentu)
    // dan "baru" (siswa yang belum punya kelas sama sekali di tahun tersebut)
    function loadSiswaAsal() {
        let mode = $('input[name="mode_sumber"]:checked').val();
        let id_tahun = $('#th_asal').val();

        if (id_tahun == '') {
            Swal.fire('Perhatian', 'Pilih Tahun Ajaran terlebih dahulu', 'warning');
            return;
        }

        let url, ajaxData;

        if (mode === 'baru') {
            // Mode Siswa Baru: tidak butuh kelas asal
            url = '?controller=ploting&method=get_siswa_baru';
            ajaxData = {
                id_tahun: id_tahun
            };
        } else {
            // Mode Pindah Kelas: butuh kelas asal juga
            let id_kelas = $('#kls_asal').val();
            if (id_kelas == '') {
                Swal.fire('Perhatian', 'Pilih Tahun dan Kelas Asal terlebih dahulu', 'warning');
                return;
            }
            url = '?controller=ploting&method=get_data_siswa';
            ajaxData = {
                id_kelas: id_kelas,
                id_tahun: id_tahun
            };
        }

        $('#list_siswa_asal').html('<tr><td colspan="3">Loading...</td></tr>');

        $.ajax({
            url: url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                let html = '';
                if (response.length === 0) {
                    let pesan = (mode === 'baru') ?
                        'Semua siswa sudah punya kelas di tahun ini' :
                        'Tidak ada data siswa ditemukan';
                    html = `<tr><td colspan="3" class="text-danger">${pesan}</td></tr>`;
                } else {
                    $.each(response, function(i, item) {
                        html += `<tr>
                            <td><input type="checkbox" class="check-item" value="${item.id_siswa}"></td>
                            <td>${item.nama_siswa}</td>
                            <td>${item.nisn}</td>
                        </tr>`;
                    });
                }
                $('#list_siswa_asal').html(html);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('Terjadi kesalahan server');
            }
        });
    }

    // --- 2. Load Data Siswa TUJUAN (Kanan) ---
    function loadSiswaTujuan() {
        let id_kelas = $('#kls_tujuan').val();
        let id_tahun = $('#th_tujuan').val();

        if (id_kelas == '' || id_tahun == '') return;

        $('#list_siswa_tujuan').html('<tr><td colspan="2">Loading...</td></tr>');

        $.ajax({
            url: '?controller=ploting&method=get_data_siswa',
            type: 'POST',
            data: {
                id_kelas: id_kelas,
                id_tahun: id_tahun
            },
            dataType: 'json',
            success: function(response) {
                let html = '';
                if (response.length === 0) {
                    html = '<tr><td colspan="2">Kelas masih kosong</td></tr>';
                } else {
                    $.each(response, function(i, item) {
                        html += `<tr>
                            <td>${i+1}</td>
                            <td class="text-start ps-3">${item.nama_siswa} <i class="fas fa-check-circle text-success small"></i></td>
                        </tr>`;
                    });
                }
                $('#list_siswa_tujuan').html(html);
            }
        });
    }

    // --- 3b. EKSEKUSI LULUSKAN SISWA ---
    $('#btnLuluskan').click(function() {
        let ids = [];
        $('.check-item:checked').each(function() {
            ids.push($(this).val());
        });

        let id_tahun = $('#th_asal').val(); // tahun ajaran saat siswa lulus

        if (ids.length === 0) {
            Swal.fire('Gagal', 'Belum ada siswa yang dicentang di tabel kiri!', 'error');
            return;
        }
        if (id_tahun == '') {
            Swal.fire('Gagal', 'Pilih Tahun Ajaran (kiri) dahulu sebagai tahun kelulusan!', 'error');
            return;
        }

        Swal.fire({
            title: 'Konfirmasi Kelulusan',
            text: `Yakin ingin meluluskan ${ids.length} siswa? Siswa TIDAK akan dimasukkan ke kelas manapun.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Luluskan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '?controller=ploting&method=luluskan',
                    type: 'POST',
                    data: {
                        ids_siswa: ids,
                        id_tahun: id_tahun
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire('Selesai!', response.msg, 'success');

                        loadSiswaAsal(); // Refresh kiri (siswa yg lulus akan hilang dari daftar)

                        $('.check-item').prop('checked', false);
                        $('#checkAll').prop('checked', false);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal memproses kelulusan.', 'error');
                    }
                });
            }
        });
    });

    // --- 3. Check All Checkbox ---
    $('#checkAll').click(function() {
        $('.check-item').prop('checked', this.checked);
    });

    // --- 4. EKSEKUSI PROSES PINDAH ---
    $('#btnProses').click(function() {
        // Ambil data check
        let ids = [];
        $('.check-item:checked').each(function() {
            ids.push($(this).val());
        });

        let kls_tujuan = $('#kls_tujuan').val();
        let th_tujuan = $('#th_tujuan').val();

        // Validasi
        if (ids.length === 0) {
            Swal.fire('Gagal', 'Belum ada siswa yang dicentang di tabel kiri!', 'error');
            return;
        }
        if (kls_tujuan == '' || th_tujuan == '') {
            Swal.fire('Gagal', 'Pastikan Tahun dan Kelas TUJUAN sudah dipilih!', 'error');
            return;
        }

        // Konfirmasi SweetAlert
        Swal.fire({
            title: 'Konfirmasi Ploting',
            text: `Yakin ingin memindahkan ${ids.length} siswa ke kelas baru?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {

                // Kirim AJAX
                $.ajax({
                    url: '?controller=ploting&method=store',
                    type: 'POST',
                    data: {
                        ids_siswa: ids,
                        id_kelas_tujuan: kls_tujuan,
                        id_tahun_tujuan: th_tujuan
                    },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire('Selesai!', response.msg, 'success');

                        loadSiswaTujuan(); // Refresh Kanan

                        // Uncheck semua yang di kiri (opsional)
                        $('.check-item').prop('checked', false);
                        $('#checkAll').prop('checked', false);
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Gagal memproses data.', 'error');
                    }
                });

            }
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>