<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
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
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 px-3 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white text-capitalize m-0">
                                Manajemen Rapor: <?= htmlspecialchars($setting['jenis_rapor']) ?> (<?= htmlspecialchars($setting['tahun_pelajaran']) ?>)
                            </h6>
                            <small class="text-white opacity-8">Kelas: <?= htmlspecialchars($kelas['kelas']) ?></small>
                        </div>
                        <div>
                            <a href="index.php?controller=rapor&method=cetak_peringkat&id_kelas=<?= $kelas['id_kelas'] ?>" class="btn bg-profile-button btn-sm text-white mb-0 shadow-sm" target="_blank" style="background-color: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4);">
                                <i class="fa fa-print me-1"></i> Cetak Peringkat
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="5%">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="25%">Nama Siswa</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Progres Nilai Mapel</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status Rapor</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($siswaList as $s):
                                    $is_lengkap = ($s['has_absensi'] && $s['has_ekstra'] && $s['has_catatan']);
                                    $persen = ($s['progres']['total'] > 0) ? ($s['progres']['terisi'] / $s['progres']['total'] * 100) : 0;
                                ?>
                                    <tr>
                                        <td class="text-center text-sm"><?= $no++ ?></td>
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($s['nama_siswa']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?= $s['nisn'] ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <a href="javascript:void(0)" class="btn-detail-nilai" data-id="<?= $s['id_siswa'] ?>" data-nama="<?= htmlspecialchars($s['nama_siswa']) ?>">
                                                <div class="progress-wrapper w-75 mx-auto">
                                                    <div class="progress-info">
                                                        <div class="progress-percentage">
                                                            <span class="text-xs font-weight-bold"><?= $s['progres']['terisi'] ?> / <?= $s['progres']['total'] ?> Mapel</span>
                                                        </div>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar <?= ($persen < 100) ? 'bg-gradient-warning' : 'bg-gradient-success' ?>" role="progressbar" style="width: <?= $persen ?>%"></div>
                                                    </div>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm <?= $s['has_absensi'] ? 'bg-gradient-success' : 'bg-gradient-secondary' ?>">ABS</span>
                                            <span class="badge badge-sm <?= $s['has_ekstra'] ? 'bg-gradient-success' : 'bg-gradient-secondary' ?>">EKS</span>
                                            <span class="badge badge-sm <?= $s['has_catatan'] ? 'bg-gradient-success' : 'bg-gradient-secondary' ?>">CAT</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <a href="?controller=rapor&method=edit_rapor&id_siswa=<?= $s['id_siswa'] ?>" class="btn btn-link text-primary btn-sm mb-0">Input</a>
                                            <a href="?controller=rapor&method=cetak&id_siswa=<?= $s['id_siswa'] ?>" target="_blank" class="btn btn-link text-dark btn-sm mb-0">Cetak</a>
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

<div class="modal fade" id="modalDetailNilai" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Nilai: <span id="modalNamaSiswa"></span></h5>
                <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-xs">Mapel/Guru</th>
                                <th class="text-xs text-center">Nilai</th>
                                <th class="text-xs text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="isiDetailNilai">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-detail-nilai').forEach(btn => {
        btn.addEventListener('click', function() {
            const idSiswa = this.getAttribute('data-id');
            const namaSiswa = this.getAttribute('data-nama');
            document.getElementById('modalNamaSiswa').innerText = namaSiswa;

            const tbody = document.getElementById('isiDetailNilai');
            // Colspan diubah menjadi 3
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4">Memuat data...</td></tr>';

            // Inisialisasi modal bootstrap
            const modalEl = document.getElementById('modalDetailNilai');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            fetch(`index.php?controller=rapor&method=detail_progres&id_siswa=${idSiswa}`)
                .then(res => {
                    if (!res.ok) throw new Error('Koneksi ke server bermasalah');
                    return res.json();
                })
                .then(data => {
                    tbody.innerHTML = '';

                    if (data.error) {
                        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${data.error}</td></tr>`;
                        return;
                    }

                    if (!Array.isArray(data) || data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center">Tidak ada data mapel.</td></tr>';
                        return;
                    }

                    data.forEach(item => {
                        const hasNilai = (item.nilai !== null && item.nilai > 0);
                        const nilaiDisplay = hasNilai ? `<strong>${item.nilai}</strong>` : '<span class="text-danger">-</span>';
                        const statusBadge = hasNilai ?
                            '<span class="badge badge-sm bg-gradient-success">Masuk</span>' :
                            '<span class="badge badge-sm bg-gradient-danger">Kosong</span>';

                        // Kolom Nama Guru digabung ke bawah Nama Mapel
                        tbody.innerHTML += `
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <h6 class="mb-0 text-sm">${item.nama_mapel}</h6>
                                <span class="text-xs text-secondary">${item.nama_guru}</span>
                            </div>
                        </td>
                        <td class="text-center text-sm">${nilaiDisplay}</td>
                        <td class="text-center">${statusBadge}</td>
                    </tr>
                `;
                    });
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data atau format data salah.</td></tr>';
                });
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>