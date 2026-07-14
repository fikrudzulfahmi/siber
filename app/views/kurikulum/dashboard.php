<div class="container-fluid py-4">
    <!-- Selamat Datang Card -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card bg-gradient-success shadow-success border-radius-lg">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="text-white mb-0">
                            Selamat Datang, <strong><?= $_SESSION['user']['nama'] ?? 'Pengguna' ?></strong>
                        </h5>
                        <p class="text-white mb-0">
                            Anda login sebagai <strong><?= levelDisplay($_SESSION['user']['level']) ?></strong>.
                        </p>
                    </div>
                    <div>
                        <i class="material-icons text-white opacity-10" style="font-size: 48px;">waving_hand</i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Presensi -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card bg-gradient-info shadow-info border-radius-lg">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="text-white mb-0">
                                Presensi Hari Ini
                            </h5>
                            <p class="text-white text-sm mb-0 opacity-8">
                                <?= FormatTanggalIndoHari(date('d M Y')) ?>
                            </p>
                        </div>
                        <div>
                            <i class="material-icons text-white opacity-10" style="font-size: 48px;">alarm</i>
                        </div>
                    </div>

                    <?php if (!isset($presensiHariIni) || empty($presensiHariIni['ada_jadwal'])): ?>
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-white me-2">event_busy</i>
                            <p class="text-white mb-0"><strong>Tidak ada jadwal</strong> untuk Anda hari ini.</p>
                        </div>
                    <?php else: ?>

                        <div class="mb-3">
                            <?php if (isset($presensiHariIni['is_kegiatan']) && $presensiHariIni['is_kegiatan']): ?>
                                <span class="badge bg-warning text-dark border-0 py-2 px-3 shadow-sm" style="font-size: 0.8rem;">
                                    <i class="fas fa-star me-1"></i> Presensi Kegiatan: <?= htmlspecialchars($presensiHariIni['nama_kegiatan']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info text-white border-0 py-1 px-2 opacity-8">
                                    <i class="fas fa-calendar-day me-1"></i> Presensi Harian
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="p-2 border border-white border-radius-md">
                                    <p class="text-white text-sm mb-1 opacity-8">Waktu Datang</p>
                                    <div class="d-flex align-items-center">
                                        <h6 class="text-white mb-0 me-2">
                                            <?= $presensiHariIni['waktu_datang'] ?>
                                        </h6>

                                        <?php if ($presensiHariIni['status_datang'] == 'Terlambat'): ?>
                                            <span class="badge bg-warning border-0 text-dark">Terlambat</span>
                                        <?php elseif ($presensiHariIni['status_datang'] == 'Alpa'): ?>
                                            <span class="badge bg-danger border-0">Alpa</span>
                                        <?php elseif ($presensiHariIni['status_datang'] == 'Belum Absen'): ?>
                                            <span class="badge bg-secondary border-0">Belum Absen</span>
                                        <?php elseif ($presensiHariIni['status_datang'] == 'Tepat Waktu'): ?>
                                            <span class="badge bg-success border-0">Tepat Waktu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-2 border border-white border-radius-md">
                                    <p class="text-white text-sm mb-1 opacity-8">Waktu Pulang</p>
                                    <div class="d-flex align-items-center">
                                        <?php if (isset($presensiHariIni['is_blinking']) && $presensiHariIni['is_blinking']): ?>
                                            <h6 class="text-danger mb-0 me-2 text-blink font-weight-bold" style="text-shadow: 1px 1px 2px rgba(255,255,255,0.8);">
                                                <?= $presensiHariIni['waktu_pulang'] ?>
                                            </h6>
                                        <?php else: ?>
                                            <h6 class="text-white mb-0 me-2">
                                                <?= $presensiHariIni['waktu_pulang'] ?>
                                            </h6>
                                        <?php endif; ?>

                                        <?php if ($presensiHariIni['status_pulang'] == 'Pulang Cepat'): ?>
                                            <span class="badge bg-warning border-0 text-dark">Pulang Cepat</span>
                                        <?php elseif ($presensiHariIni['status_pulang'] == 'Alpa'): ?>
                                            <span class="badge bg-danger border-0">Alpa</span>
                                        <?php elseif ($presensiHariIni['status_pulang'] == 'Sesuai'): ?>
                                            <span class="badge bg-success border-0">Sesuai</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Akhir Card Presensi -->

    <!-- Statistik Cards -->
    <div class="row">
        <!-- Total Siswa -->
        <div class="col-xl-4 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 font-weight-bold">Total Siswa</p>
                                <h5 class="font-weight-bolder"><?= $jumlahSiswa ?? 0 ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                <i class="material-icons opacity-10">group</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Guru -->
        <div class="col-xl-4 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 font-weight-bold">Total Guru</p>
                                <h5 class="font-weight-bolder"><?= $jumlahGuru ?? 0 ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-dark shadow text-center border-radius-md">
                                <i class="material-icons opacity-10">person</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Kelas -->
        <div class="col-xl-4 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 font-weight-bold">Total Kelas</p>
                                <h5 class="font-weight-bolder"><?= $jumlahKelas ?? 0 ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                <i class="material-icons opacity-10">class</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                    <h6 class="text-white text-capitalize ps-3">Pengingat Deadline Perangkat</h6>
                </div>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($deadlineList)): ?>
                    <p class="text-sm text-muted mb-0">Tidak ada data deadline.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($deadlineList as $dl): ?>
                            <?php
                            $itemClass = match ($dl['status']) {
                                'lewat'   => 'list-group-item-danger',
                                'dekat'   => 'list-group-item-warning',
                                'selesai' => 'list-group-item-success',
                                default   => ''
                            };
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center <?= $itemClass ?>">
                                <div>
                                    <strong><?= htmlspecialchars($dl['jenis_perangkat']) ?></strong>
                                    <?php if ($dl['status'] == 'ditolak'): ?>
                                        <small class="text-danger ms-2">Silahkan upload ulang perangkat.</small>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        Deadline: <?= date('d/m/Y', strtotime($dl['tanggal_deadline'])) ?>
                                    </small>
                                </div>

                                <div>
                                    <?php if ($dl['status'] == 'selesai'): ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php elseif ($dl['status'] == 'lewat'): ?>
                                        <span class="badge bg-danger">Lewat</span>
                                    <?php elseif ($dl['status'] == 'dekat'): ?>
                                        <span class="badge bg-warning"><?= $dl['sisa_hari'] ?> hari lagi</span>
                                    <?php elseif ($dl['status'] == 'ditolak'): ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Aman</span>
                                    <?php endif; ?>

                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($deadlineProgram) && $showProgramDeadline): ?>
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Pengingat Deadline Program Struktural</h6>
                    </div>
                </div>
                <div class="card-body px-4 py-3">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($deadlineProgram as $dp): ?>
                            <?php
                            $itemClass = match ($dp['status']) {
                                'selesai' => 'list-group-item-success',
                                'lewat'   => 'list-group-item-danger',
                                'ditolak' => 'list-group-item-danger',
                                default   => 'list-group-item-warning'
                            };
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center <?= $itemClass ?>">
                                <div>
                                    <strong><?= htmlspecialchars($dp['jenis_program']) ?></strong>
                                    <?php if ($dp['status'] == 'lewat'): ?>
                                        <small class="text-danger ms-2">Segera upload!</small>
                                    <?php elseif ($dp['status'] == 'ditolak'): ?>
                                        <small class="text-danger ms-2">Silahkan upload ulang program.</small>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        Deadline: <?= date('d/m/Y', strtotime($dp['deadline'])) ?>
                                    </small>
                                </div>
                                <div>
                                    <?php if ($dp['status'] == 'selesai'): ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php elseif ($dp['status'] == 'lewat'): ?>
                                        <span class="badge bg-danger">Lewat</span>
                                    <?php elseif ($dp['status'] == 'ditolak'): ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><?= $dp['sisa_hari'] ?> hari lagi</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>