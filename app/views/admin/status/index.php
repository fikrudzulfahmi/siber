<?php
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

/** @var array $data */
$f   = $data['finger'] ?? ['ok' => false, 'error' => 'data tidak tersedia'];
$w   = $data['wa'] ?? ['ok' => false, 'error' => 'data tidak tersedia'];
$sesi = $w['sesi'] ?? null;

$badge = static function ($ok, $teksOk = 'ONLINE', $teksGagal = 'OFFLINE') {
    return $ok
        ? '<span class="badge bg-success">● ' . sec_e($teksOk) . '</span>'
        : '<span class="badge bg-danger">● ' . sec_e($teksGagal) . '</span>';
};
$statusVerifikasi = [
    'sent'      => ['bg-secondary', 'terkirim'],
    'delivered' => ['bg-info', 'sampai'],
    'read'      => ['bg-success', 'dibaca'],
    'played'    => ['bg-success', 'diputar'],
    'failed'    => ['bg-danger', 'gagal'],
    'queued'    => ['bg-warning', 'antre'],
    'sending'   => ['bg-warning', 'mengirim'],
];
?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Status Layanan</h5>
                <p class="text-sm text-secondary mb-0">
                    Presensi &amp; WhatsApp Gateway — diperbarui server pada <?= sec_e($data['diambil'] ?? '-') ?>
                    (otomatis 30 detik)
                </p>
            </div>
            <a href="?controller=status&method=index" class="btn btn-sm btn-outline-primary mb-0">Muat ulang</a>
        </div>
    </div>

    <div class="row">
        <!-- ============================ PRESENSI ============================ -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="material-icons text-sm me-1">fingerprint</i> Sinkronisasi Presensi</h6>
                        <?= $badge(!empty($f['ok'])) ?>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <?php if (empty($f['ok'])): ?>
                        <div class="alert alert-danger text-sm mb-0">
                            <?= sec_e($f['error'] ?? 'Tidak dapat membaca status presensi.') ?>
                        </div>
                    <?php else: ?>
                        <?php
                        $node     = $f['node'] ?? [];
                        $presensi = $f['presensi'] ?? [];
                        $hidup    = !empty($node['hidup']);
                        $umur     = $node['umur_detik'] ?? null;
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Service Node (server lokal)</span>
                            <?= $badge($hidup, 'HIDUP', 'MATI') ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Tarikan terakhir</span>
                            <span class="text-sm font-weight-bold">
                                <?= $umur === null ? '-' : sec_e($umur) . ' detik lalu' ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Presensi hari ini</span>
                            <span class="text-sm font-weight-bold"><?= (int) ($presensi['hari_ini'] ?? 0) ?> baris</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Presensi terakhir masuk</span>
                            <span class="text-sm font-weight-bold"><?= sec_e($presensi['terakhir'] ?? '-') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-sm text-secondary">Belum terkirim ke hosting</span>
                            <?php $pending = (int) ($presensi['belum_terkirim'] ?? 0); ?>
                            <span class="badge <?= $pending > 0 ? 'bg-warning' : 'bg-success' ?>">
                                <?= $pending ?> baris
                            </span>
                        </div>

                        <hr class="horizontal dark my-2">
                        <p class="text-xs text-uppercase text-secondary mb-2">Mesin Fingerprint</p>
                        <?php foreach ((array) ($f['mesin'] ?? []) as $m): ?>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-sm">
                                    <?= sec_e($m['nama'] ?? 'Mesin') ?>
                                    <span class="text-xs text-secondary"><?= sec_e(($m['ip'] ?? '') . ':' . ($m['port'] ?? '')) ?></span>
                                </span>
                                <?= $badge(!empty($m['online'])) ?>
                            </div>
                        <?php endforeach; ?>
                        <p class="text-xs text-secondary mb-0 mt-2">Host: <?= sec_e($f['host'] ?? '-') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ============================ WHATSAPP ============================ -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="material-icons text-sm me-1">chat</i> WhatsApp Gateway</h6>
                        <?php
                        $statusSesi = $sesi['live_status'] ?? ($sesi['status'] ?? null);
                        echo $badge($statusSesi === 'connected', 'TERHUBUNG', strtoupper((string) ($statusSesi ?? 'TIDAK DIKETAHUI')));
                        ?>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <?php if (empty($w['ok'])): ?>
                        <div class="alert alert-danger text-sm mb-0">
                            <?= sec_e($w['error'] ?? 'Tidak dapat menghubungi WA Gateway.') ?>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Sesi</span>
                            <span class="text-sm font-weight-bold"><?= sec_e($sesi['label'] ?? ($sesi['name'] ?? '-')) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Nomor</span>
                            <span class="text-sm font-weight-bold"><?= sec_e($sesi['phone'] ?? '-') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Terakhir terlihat</span>
                            <span class="text-sm"><?= sec_e($sesi['last_seen_at'] ?? '-') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-sm text-secondary">Kuota hari ini</span>
                            <span class="text-sm">
                                <?= (int) ($sesi['sent_today'] ?? 0) ?> / <?= (int) ($sesi['daily_quota'] ?? 0) ?>
                            </span>
                        </div>
                        <?php if (!empty($sesi['last_error'])): ?>
                            <div class="alert alert-warning text-xs mb-2 py-2">
                                <?= sec_e($sesi['last_error']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($w['qr'])): ?>
                            <hr class="horizontal dark my-2">
                            <p class="text-xs text-uppercase text-secondary mb-2">Pindai QR untuk menyambungkan</p>
                            <div class="text-center">
                                <img src="<?= sec_e($w['qr']) ?>" alt="QR WhatsApp"
                                     style="width:100%;max-width:260px;background:#fff;padding:8px;border-radius:10px;">
                            </div>
                            <p class="text-xs text-secondary text-center mt-2 mb-0">
                                Buka WhatsApp di HP → Perangkat tertaut → Tautkan perangkat.
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== PESAN TERAKHIR ====================== -->
    <?php if (!empty($w['pesan'])): ?>
    <div class="card mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">15 Pesan Terakhir</h6></div>
        <div class="card-body px-0 pt-3 pb-2">
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Waktu</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tujuan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Isi</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ((array) $w['pesan'] as $m): ?>
                            <?php $st = $statusVerifikasi[$m['status'] ?? ''] ?? ['bg-secondary', $m['status'] ?? '-']; ?>
                            <tr>
                                <td class="ps-3"><span class="text-xs"><?= sec_e($m['created_at'] ?? '-') ?></span></td>
                                <td><span class="text-xs font-weight-bold"><?= sec_e($m['to_number'] ?? '-') ?></span></td>
                                <td>
                                    <span class="text-xs text-secondary">
                                        <?= sec_e(mb_substr((string) ($m['body'] ?? ''), 0, 60)) ?>
                                    </span>
                                </td>
                                <td><span class="badge <?= sec_e($st[0]) ?>"><?= sec_e($st[1]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====================== LOG GATEWAY ====================== -->
    <?php if (!empty($w['log'])): ?>
    <div class="card mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">15 Log Gateway</h6></div>
        <div class="card-body px-0 pt-3 pb-2">
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-3">Waktu</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Level</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kejadian</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ((array) $w['log'] as $l): ?>
                            <?php $lv = strtolower((string) ($l['level'] ?? 'info')); ?>
                            <tr>
                                <td class="ps-3"><span class="text-xs"><?= sec_e($l['created_at'] ?? '-') ?></span></td>
                                <td>
                                    <span class="badge <?= $lv === 'error' ? 'bg-danger' : ($lv === 'warning' ? 'bg-warning' : 'bg-secondary') ?>">
                                        <?= sec_e($lv) ?>
                                    </span>
                                </td>
                                <td><span class="text-xs font-weight-bold"><?= sec_e($l['event'] ?? '-') ?></span></td>
                                <td><span class="text-xs text-secondary"><?= sec_e(mb_substr((string) ($l['message'] ?? ''), 0, 90)) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
  setTimeout(function () { window.location.reload(); }, 30000);
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
