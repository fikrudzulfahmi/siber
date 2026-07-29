<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <div class="row mb-4">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Anggota Kelas</h6>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-sm mb-0">Lihat daftar anggota kelas berdasarkan hasil plotting.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-filter"></i> Filter Pencarian</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label>Tahun Ajaran</label>
                            <select id="filter_tahun" class="form-control border px-2">
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <?php foreach ($tahun_ajaran as $t): ?>
                                    <option value="<?= $t['id_tahun_pelajaran'] ?>"><?= htmlspecialchars($t['tahun_pelajaran']) ?> (<?= htmlspecialchars($t['semester']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label>Kelas</label>
                            <select id="filter_kelas" class="form-control border px-2">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($daftar_kelas as $k): ?>
                                    <option value="<?= $k['id_kelas'] ?>"><?= htmlspecialchars($k['kelas']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button id="btnCari" class="btn btn-success w-100">
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Daftar Siswa</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0 text-center">
                            <thead class="table-dark text-white">
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="5%">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Siswa</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">NISN</th>
                                </tr>
                            </thead>
                            <tbody id="list_anggota_kelas">
                                <tr>
                                    <td colspan="3" class="text-muted py-4">Silahkan pilih Tahun Ajaran dan Kelas, lalu klik Cari.</td>
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
    $(document).ready(function() {
        $('#btnCari').click(function() {
            let id_tahun = $('#filter_tahun').val();
            let id_kelas = $('#filter_kelas').val();

            if (id_tahun === '' || id_kelas === '') {
                Swal.fire('Perhatian', 'Pilih Tahun Ajaran dan Kelas terlebih dahulu', 'warning');
                return;
            }

            $('#list_anggota_kelas').html('<tr><td colspan="3" class="py-4">Memuat data...</td></tr>');

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
                        html = '<tr><td colspan="3" class="text-danger py-4">Tidak ada anggota di kelas ini.</td></tr>';
                    } else {
                        $.each(response, function(i, item) {
                            html += `<tr>
                                <td><p class="text-xs font-weight-bold mb-0">${i + 1}</p></td>
                                <td class="text-start ps-4">
                                    <h6 class="mb-0 text-sm">${item.nama_siswa}</h6>
                                </td>
                                <td><p class="text-xs text-secondary mb-0">${item.nisn}</p></td>
                            </tr>`;
                        });
                    }
                    $('#list_anggota_kelas').html(html);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    $('#list_anggota_kelas').html('<tr><td colspan="3" class="text-danger py-4">Terjadi kesalahan server</td></tr>');
                    Swal.fire('Error', 'Terjadi kesalahan saat memuat data', 'error');
                }
            });
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>
