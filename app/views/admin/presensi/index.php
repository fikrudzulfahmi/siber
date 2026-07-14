<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-capitalize mb-0">Rekap Presensi</h4>
        <span class="badge bg-info">Tanggal: <?= $tanggalHariIni; ?></span>
    </div>

    <?php if (!empty($pesanLibur)): ?>
        <div class="alert alert-info"><?= $pesanLibur; ?></div>
    <?php else: ?>
        <div class="row mb-4">
            <!-- Hadir -->
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <p class="text-sm mb-0 fw-bold">Hadir</p>
                                <h5 class="fw-bolder text-success"><?= $jumlah_hadir ?></h5>
                            </div>
                            <div class="col-4 d-flex justify-content-center">
                                <div class="icon icon-shape bg-gradient-success shadow border-radius-md d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                                    <i class="material-icons fs-2 opacity-10 mb-3">group</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Belum Absen -->
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <p class="text-sm mb-0 fw-bold">Belum Absen</p>
                                <h5 class="fw-bolder text-danger"><?= $jumlah_tidak_hadir ?></h5>
                            </div>
                            <div class="col-4 d-flex justify-content-center">
                                <div class="icon icon-shape bg-gradient-dark shadow border-radius-md d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                                    <i class="material-icons fs-2 opacity-10 mb-3">person</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terlambat -->
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <p class="text-sm mb-0 fw-bold">Terlambat</p>
                                <h5 class="fw-bolder text-warning"><?= $jumlah_terlambat ?></h5>
                            </div>
                            <div class="col-4 d-flex justify-content-center">
                                <div class="icon icon-shape bg-gradient-warning shadow border-radius-md d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                                    <i class="material-icons fs-2 opacity-10 mb-3">schedule</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pulang Cepat -->
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <p class="text-sm mb-0 fw-bold">Pulang Cepat</p>
                                <h5 class="fw-bolder text-info"><?= $jumlah_pulang_cepat ?></h5>
                            </div>
                            <div class="col-4 d-flex justify-content-center">
                                <div class="icon icon-shape bg-gradient-info shadow border-radius-md d-flex align-items-center justify-content-center" style="width:60px; height:60px;">
                                    <i class="material-icons fs-2 opacity-10 mb-3">exit_to_app</i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Tabel Kehadiran -->
        <div class="row mb-4">
            <div class="card mb-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Guru</h6>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive p-0">
                        <table id="datatable4" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th>Jam Datang</th>
                                    <th>Jam Pulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($guru_kehadiran as $i => $g): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($g['nama']) ?></td>
                                        <td>
                                            <?php
                                            $badge = [
                                                'Hadir' => 'success',
                                                'Belum Absen' => 'light text-dark',
                                                'Tidak Ada Jadwal' => 'secondary',
                                                'Izin' => 'warning',
                                                'Sakit' => 'dark',
                                                'Dinas-Luar' => 'primary'
                                            ];
                                            ?>
                                            <span class="badge bg-<?= $badge[$g['keterangan']] ?? 'secondary' ?>"><?= $g['keterangan'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($g['waktu_datang']) ?></td>
                                        <td><?= htmlspecialchars($g['waktu_pulang']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tendik -->
        <div class="row mb-4">
            <div class="card">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tenaga Kependidikan</h6>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive p-0">
                        <table id="datatable3" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th>Jam Datang</th>
                                    <th>Jam Pulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tendik_kehadiran as $i => $t): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($t['nama']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $badge[$t['keterangan']] ?? 'secondary' ?>"><?= $t['keterangan'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($t['waktu_datang']) ?></td>
                                        <td><?= htmlspecialchars($t['waktu_pulang']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include '../app/views/layouts/footer.php'; ?>