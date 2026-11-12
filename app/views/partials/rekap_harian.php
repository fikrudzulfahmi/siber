<?php
/**
 * Partial rekap harian dashboard:
 * 1. Presensi hari ini (jadwal kerja / kegiatan lembaga / tanpa jadwal / libur)
 * 2. Jurnal mengajar hari ini (semua jam mengajar + status pengisian)
 * 3. Jurnal struktural hari ini (khusus Tenaga Kependidikan, id_jabatan = 2)
 *
 * Data disiapkan oleh DashboardController::rekapPresensiHariIni(),
 * rekapJurnalMengajarHariIni(), dan rekapJurnalStrukturalHariIni().
 */
$presensi          = $presensiHariIni ?? [];
$modePresensi      = $presensi['mode'] ?? 'tanpa_jadwal';
$jurnalMengajar    = $rekapJurnalMengajar ?? [];
$jurnalStruktural  = $rekapJurnalStruktural ?? null;

$statusKhusus = ['Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Dinas'];
$isStatusKhusus = in_array($presensi['status_datang'] ?? '', $statusKhusus, true);

$jurnalBelum = 0;
foreach ($jurnalMengajar as $jm) {
    if (($jm['status'] ?? '') !== 'Sudah') {
        $jurnalBelum++;
    }
}
?>
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

                    <?php if ($modePresensi === 'libur'): ?>
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-white me-2">beach_access</i>
                            <p class="text-white mb-0">
                                <strong><?= sec_e($presensi['keterangan'] ?: 'Libur') ?></strong> &mdash; tidak ada kewajiban presensi hari ini.
                            </p>
                        </div>
                    <?php elseif ($modePresensi === 'tanpa_jadwal'): ?>
                        <div class="d-flex align-items-center mb-2">
                            <i class="material-icons text-white me-2">event_busy</i>
                            <p class="text-white mb-0"><strong>Tidak ada jadwal</strong> untuk Anda hari ini.</p>
                        </div>
                        <?php if (!empty($presensi['scan_masuk'])): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-white text-info border-0">
                                    <i class="material-icons-round me-1">login</i> Scan masuk <?= sec_e($presensi['scan_masuk']) ?>
                                </span>
                                <?php if (!empty($presensi['scan_pulang'])): ?>
                                    <span class="badge bg-white text-info border-0">
                                        <i class="material-icons-round me-1">logout</i> Scan pulang <?= sec_e($presensi['scan_pulang']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-white text-xs mt-2 mb-0 opacity-8">
                                Scan tercatat, tetapi tidak terekap karena tidak ada jadwal.
                            </p>
                        <?php endif; ?>
                    <?php elseif ($isStatusKhusus): ?>
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-white me-2">assignment_ind</i>
                            <p class="text-white mb-0">
                                Status hari ini: <strong><?= sec_e($presensi['status_datang']) ?></strong>.
                            </p>
                        </div>
                    <?php else: ?>

                        <div class="mb-3 d-flex flex-wrap gap-2">
                            <?php if (!empty($presensi['is_kegiatan'])): ?>
                                <span class="badge bg-warning text-dark border-0 py-2 px-3 shadow-sm" style="font-size: 0.8rem;">
                                    <i class="material-icons-round me-1">star</i> Presensi Kegiatan: <?= sec_e($presensi['nama_kegiatan']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-info text-white border-0 py-1 px-2 opacity-8">
                                    <i class="material-icons-round me-1">today</i> Presensi Harian
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($presensi['jam_kerja'])): ?>
                                <span class="badge bg-white text-info border-0 py-1 px-2">
                                    <i class="material-icons-round me-1">schedule</i> <?= sec_e($presensi['jam_kerja']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-2 mb-md-0">
                                <div class="p-2 border border-white border-radius-md">
                                    <p class="text-white text-sm mb-1 opacity-8">Waktu Datang</p>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <h6 class="text-white mb-0 me-2">
                                            <?= sec_e($presensi['waktu_datang'] ?? '--:--') ?>
                                        </h6>

                                        <?php if (($presensi['status_datang'] ?? '') == 'Terlambat'): ?>
                                            <span class="badge bg-warning border-0 text-dark">Terlambat</span>
                                        <?php elseif (($presensi['status_datang'] ?? '') == 'Alpa'): ?>
                                            <span class="badge bg-danger border-0">Alpa</span>
                                        <?php elseif (($presensi['status_datang'] ?? '') == 'Belum Absen'): ?>
                                            <span class="badge bg-secondary border-0">Belum Absen</span>
                                        <?php elseif (($presensi['status_datang'] ?? '') == 'Tepat Waktu'): ?>
                                            <span class="badge bg-success border-0">Tepat Waktu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-2 border border-white border-radius-md">
                                    <p class="text-white text-sm mb-1 opacity-8">Waktu Pulang</p>
                                    <div class="d-flex align-items-center flex-wrap">
                                        <?php if (!empty($presensi['is_blinking'])): ?>
                                            <h6 class="text-danger mb-0 me-2 text-blink font-weight-bold" style="text-shadow: 1px 1px 2px rgba(255,255,255,0.8);">
                                                <?= sec_e($presensi['waktu_pulang']) ?>
                                            </h6>
                                        <?php else: ?>
                                            <h6 class="text-white mb-0 me-2">
                                                <?= sec_e($presensi['waktu_pulang'] ?? '--:--') ?>
                                            </h6>
                                        <?php endif; ?>

                                        <?php if (($presensi['status_pulang'] ?? '') == 'Pulang Cepat'): ?>
                                            <span class="badge bg-warning border-0 text-dark">Pulang Cepat</span>
                                        <?php elseif (($presensi['status_pulang'] ?? '') == 'Alpa'): ?>
                                            <span class="badge bg-danger border-0">Alpa</span>
                                        <?php elseif (($presensi['status_pulang'] ?? '') == 'Sesuai'): ?>
                                            <span class="badge bg-success border-0">Sesuai</span>
                                        <?php elseif (($presensi['status_pulang'] ?? '') == 'Belum Absen'): ?>
                                            <span class="badge bg-secondary border-0">Belum Absen</span>
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

    <?php if (!empty($jurnalMengajar) || $jurnalStruktural !== null): ?>
    <div class="row">
        <?php if (!empty($jurnalMengajar)): ?>
        <div class="<?= $jurnalStruktural !== null ? 'col-lg-6' : 'col-12' ?> mb-4">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h6 class="mb-0">Jurnal Mengajar Hari Ini</h6>
                        <p class="text-xs text-muted mb-0"><?= count($jurnalMengajar) ?> jam mengajar</p>
                    </div>
                    <?php if ($jurnalBelum > 0): ?>
                        <span class="badge bg-gradient-warning"><?= $jurnalBelum ?> belum diisi</span>
                    <?php else: ?>
                        <span class="badge bg-gradient-success">Semua terisi</span>
                    <?php endif; ?>
                </div>
                <div class="card-body px-3 pt-3 pb-1">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($jurnalMengajar as $jm): ?>
                            <li class="d-flex justify-content-between align-items-start gap-2 pb-2 mb-2 border-bottom">
                                <div class="pe-1" style="min-width: 0;">
                                    <div class="text-sm font-weight-bold text-truncate">
                                        <?= sec_e($jm['nama_mapel']) ?> &middot; <?= sec_e($jm['kelas']) ?>
                                    </div>
                                    <div class="text-xs text-muted">
                                        <?= sec_e($jm['jam']) ?>
                                        <?php if (($jm['status'] ?? '') === 'Sudah' && !empty($jm['jam_isi'])): ?>
                                            &middot; diisi <?= sec_e(substr($jm['jam_isi'], 0, 5)) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <?php if (($jm['status'] ?? '') === 'Sudah'): ?>
                                        <?php // Sudah diisi -> hijau, dan bisa diklik untuk mengedit. ?>
                                        <a href="?controller=jurnal&method=edit&id=<?= (int) ($jm['id_jurnal'] ?? 0) ?>"
                                           class="badge bg-gradient-success text-decoration-none"
                                           title="Klik untuk mengedit jurnal ini">
                                            <i class="material-icons-round" style="font-size:13px; vertical-align:-2px;">edit</i>
                                            Sudah
                                        </a>
                                    <?php elseif (($jm['status'] ?? '') === 'Belum'): ?>
                                        <?php // Belum diisi -> merah, diklik membuka form pengisian. ?>
                                        <a href="?controller=jurnal&method=index"
                                           class="badge bg-gradient-danger text-decoration-none"
                                           title="Klik untuk mengisi jurnal">
                                            <i class="material-icons-round" style="font-size:13px; vertical-align:-2px;">edit_note</i>
                                            Belum
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum waktunya</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($jurnalStruktural !== null): ?>
        <div class="<?= !empty($jurnalMengajar) ? 'col-lg-6' : 'col-12' ?> mb-4">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h6 class="mb-0">Jurnal Struktural Hari Ini</h6>
                        <p class="text-xs text-muted mb-0">
                            <?php if (!empty($jurnalStruktural['libur'])): ?>
                                Libur: <?= sec_e($jurnalStruktural['libur']) ?>
                            <?php elseif (!empty($jurnalStruktural['jadwal'])): ?>
                                Jam kerja <?= sec_e($jurnalStruktural['jadwal']['jam_masuk']) ?> - <?= sec_e($jurnalStruktural['jadwal']['jam_pulang']) ?>
                            <?php else: ?>
                                Tidak ada jadwal kerja hari ini
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($jurnalStruktural['sudah'])): ?>
                        <span class="badge bg-gradient-success">Sudah diisi</span>
                    <?php elseif (!empty($jurnalStruktural['wajib_isi'])): ?>
                        <span class="badge bg-gradient-danger">Belum diisi</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Tidak wajib diisi</span>
                    <?php endif; ?>
                </div>
                <div class="card-body pt-3">
                    <?php if (!empty($jurnalStruktural['sudah'])): ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-light text-dark border">
                                <i class="material-icons-round me-1">check_circle</i>
                                Diisi pukul <?= sec_e($jurnalStruktural['jam_isi'] ?: '-') ?>
                            </span>
                            <span class="badge bg-light text-dark border">
                                <i class="material-icons-round me-1">task_alt</i>
                                <?= (int) $jurnalStruktural['jumlah_program'] ?> program kerja
                            </span>
                        </div>
                        <p class="text-sm text-muted mb-2" style="white-space: pre-line;">
                            <?= sec_e($jurnalStruktural['catatan'] !== '' ? mb_substr($jurnalStruktural['catatan'], 0, 220) : 'Tidak ada catatan akhir.') ?>
                        </p>
                        <a href="?controller=jurnalStruktural&method=index" class="text-sm">Lihat / perbarui</a>
                    <?php elseif (!empty($jurnalStruktural['wajib_isi'])): ?>
                        <p class="text-sm text-muted mb-2">
                            Jurnal struktural hari ini belum diisi.
                        </p>
                        <a href="?controller=jurnalStruktural&method=index" class="btn btn-sm bg-gradient-info mb-0">
                            <i class="material-icons-round me-1">edit_note</i> Isi sekarang
                        </a>
                    <?php else: ?>
                        <p class="text-sm text-muted mb-2">
                            <?php if (!empty($jurnalStruktural['libur'])): ?>
                                Hari libur, jurnal struktural tidak wajib diisi.
                            <?php else: ?>
                                Tidak ada jadwal kerja hari ini, jurnal struktural tidak wajib diisi.
                            <?php endif; ?>
                        </p>
                        <a href="?controller=jurnalStruktural&method=index" class="text-sm text-muted">
                            Tetap isi jurnal (opsional)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
