<?php
/**
 * View: Activity Log
 * Dimuat lewat helper view() → header/sidebar/footer sudah otomatis.
 */
$qs = $_GET; // untuk membangun link pagination dengan filter tetap terbawa

// Helper kecil: bangun query string pagination
if (!function_exists('activityLogPageUrl')) {
    function activityLogPageUrl($qs, $halaman)
    {
        $qs['controller'] = 'activityLog';
        $qs['method']     = 'index';
        $qs['hal']        = $halaman;
        return '?' . http_build_query($qs);
    }
}
?>

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

<style>
    #tabelLog td,
    #tabelLog th {
        vertical-align: middle;
    }

    .log-uri {
        max-width: 260px;
        display: inline-block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
    }

    .badge-aksi {
        font-size: 0.68rem;
        letter-spacing: .3px;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Activity Log</h6>
                        <span class="badge bg-white text-dark me-3"><?= number_format($total, 0, ',', '.') ?> aktivitas tercatat</span>
                    </div>
                </div>

                <!-- FILTER -->
                <form method="GET" class="row g-2 p-3 mt-2 align-items-end">
                    <input type="hidden" name="controller" value="activityLog">
                    <input type="hidden" name="method" value="index">

                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">Dari Tanggal</label>
                        <input type="date" name="tanggal_awal" value="<?= htmlspecialchars($filters['tanggal_awal']) ?>" class="form-control form-control-sm border px-2">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">Sampai Tanggal</label>
                        <input type="date" name="tanggal_akhir" value="<?= htmlspecialchars($filters['tanggal_akhir']) ?>" class="form-control form-control-sm border px-2">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">User</label>
                        <select name="id_employe" class="form-select form-select-sm border px-2">
                            <option value="">-- Semua User --</option>
                            <?php foreach ($daftarUser as $u): ?>
                                <option value="<?= $u['id_employe'] ?>" <?= ((string)$filters['id_employe'] === (string)$u['id_employe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nama_user']) ?> (<?= $u['jumlah'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">Jenis Aksi</label>
                        <select name="aksi" class="form-select form-select-sm border px-2">
                            <option value="">-- Semua Aksi --</option>
                            <?php foreach ($daftarAksi as $a): ?>
                                <option value="<?= $a ?>" <?= ($filters['aksi'] === $a) ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">Modul</label>
                        <select name="modul" class="form-select form-select-sm border px-2">
                            <option value="">-- Semua Modul --</option>
                            <?php foreach ($daftarModul as $m): ?>
                                <option value="<?= htmlspecialchars($m['modul']) ?>" <?= ($filters['modul'] === $m['modul']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['modul']) ?> (<?= $m['jumlah'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">Kata Kunci</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="aktivitas / IP" class="form-control form-control-sm border px-2">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold text-xs mb-1">Tampil</label>
                        <select name="per_page" class="form-select form-select-sm border px-2">
                            <?php foreach ([10, 25, 50, 100] as $p): ?>
                                <option value="<?= $p ?>" <?= ($perPage == $p) ? 'selected' : '' ?>><?= $p ?> baris</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-success btn-sm mb-0">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                        <a href="?controller=activityLog&method=index" class="btn btn-outline-secondary btn-sm mb-0">
                            Reset
                        </a>
                    </div>
                </form>

                <div class="card-body px-4 pb-2">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                        <form action="?controller=activityLog&method=hapus" method="POST" class="d-flex align-items-center gap-2 form-hapus-lama mb-0">
                            <input type="hidden" name="mode" value="lama">
                            <label class="text-xs mb-0">Hapus log lebih dari</label>
                            <input type="number" name="hari" value="90" min="1" class="form-control form-control-sm border px-2" style="width:80px;">
                            <label class="text-xs mb-0">hari</label>
                            <button type="submit" class="btn btn-sm btn-dark mb-0">
                                <i class="fas fa-broom me-1"></i> Bersihkan
                            </button>
                        </form>

                        <form action="?controller=activityLog&method=hapus" method="POST" class="form-hapus-semua mb-0">
                            <input type="hidden" name="mode" value="semua">
                            <button type="submit" class="btn btn-sm btn-danger mb-0">
                                <i class="fas fa-trash me-1"></i> Hapus Semua
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-3">
                        <table id="tabelLog" class="table table-striped table-bordered align-items-center mb-0 text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" width="4%">No</th>
                                    <th width="14%">Waktu</th>
                                    <th width="15%">User</th>
                                    <th>Aktivitas</th>
                                    <th width="18%">Perangkat</th>
                                    <th width="11%">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada aktivitas yang tercatat pada filter ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = (($halaman - 1) * $perPage) + 1; ?>
                                    <?php foreach ($logs as $row): ?>
                                        <?php
                                        $warnaAksi = [
                                            'Tambah'      => 'bg-gradient-success',
                                            'Ubah'        => 'bg-gradient-info',
                                            'Hapus'       => 'bg-gradient-danger',
                                            'Login'       => 'bg-gradient-primary',
                                            'Logout'      => 'bg-gradient-secondary',
                                            'Cetak'       => 'bg-gradient-dark',
                                            'Export'      => 'bg-gradient-dark',
                                            'Buka'        => 'bg-gradient-light',
                                            'Akses Data'  => 'bg-gradient-secondary',
                                        ][$row['aksi']] ?? 'bg-gradient-secondary';
                                        $teksAksi = in_array($row['aksi'], ['Buka'], true) ? 'text-dark' : 'text-white';
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td>
                                                <span class="text-xs fw-bold d-block"><?= formatTanggalIndo($row['created_at'], true, false) ?></span>
                                                <span class="text-xs text-secondary"><?= date('H:i:s', strtotime($row['created_at'])) ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold d-block"><?= htmlspecialchars($row['nama_user'] ?? 'Tamu') ?></span>
                                                <span class="text-xs text-secondary"><?= htmlspecialchars($row['level_user'] ?? '-') ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-aksi <?= $warnaAksi ?> <?= $teksAksi ?> mb-1"><?= htmlspecialchars($row['aksi'] ?? '-') ?></span>
                                                <span class="d-block"><?= htmlspecialchars($row['aktivitas'] ?? '-') ?></span>
                                                <span class="text-xs text-secondary" title="<?= htmlspecialchars($row['url'] ?? '') ?>">
                                                    <i class="fas fa-link me-1"></i><span class="log-uri"><?= htmlspecialchars($row['url'] ?? '-') ?></span>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="d-block"><i class="fas fa-globe me-1 text-secondary"></i><?= htmlspecialchars($row['browser'] ?? '-') ?></span>
                                                <span class="text-xs text-secondary">
                                                    <?= htmlspecialchars($row['os'] ?? '-') ?> &middot; <?= htmlspecialchars($row['device'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-xs"><?= htmlspecialchars($row['ip_address'] ?? '-') ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($totalHalaman > 1): ?>
                        <div class="d-flex justify-content-between align-items-center px-4 pb-3 flex-wrap gap-2">
                            <span class="text-xs text-secondary">
                                Halaman <?= $halaman ?> dari <?= $totalHalaman ?> &middot; total <?= number_format($total, 0, ',', '.') ?> data
                            </span>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($halaman <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= activityLogPageUrl($qs, $halaman - 1) ?>">&laquo;</a>
                                    </li>
                                    <?php
                                    $mulai = max(1, $halaman - 2);
                                    $akhir = min($totalHalaman, $halaman + 2);
                                    for ($i = $mulai; $i <= $akhir; $i++): ?>
                                        <li class="page-item <?= ($i == $halaman) ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= activityLogPageUrl($qs, $i) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($halaman >= $totalHalaman) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= activityLogPageUrl($qs, $halaman + 1) ?>">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.form-hapus-lama').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const hari = form.querySelector('input[name="hari"]').value;
            Swal.fire({
                title: 'Bersihkan Activity Log?',
                text: 'Semua log yang lebih lama dari ' + hari + ' hari akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, bersihkan!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });

    document.querySelectorAll('.form-hapus-semua').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Hapus SEMUA Activity Log?',
                text: 'Seluruh riwayat aktivitas akan dihapus permanen dan tidak dapat dikembalikan!',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus semua!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
