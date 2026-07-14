<?php
// File: _hasil_nilai.php
// Template untuk menampilkan tabel nilai di dalam modal (read-only)
// VERSI DIPERBARUI: Menyesuaikan tampilan agar sinkron dengan tabel input nilai

if (empty($hasil_laporan)) {
    echo '<p class="text-center text-muted">Tidak ada data nilai yang ditemukan untuk kategori ini.</p>';
    return;
}

// Ambil jumlah NS yang aktif
$banyak_ns = $hasil_laporan[0]['banyak_ns'] ?? 10;

// Decode Nama Kustom (diambil dari baris pertama data atau variabel kategori jika tersedia)
$customNama = json_decode($hasil_laporan[0]['nama_ns'] ?? '{}', true);
?>

<div class="table-responsive p-0">
    <table class="table align-items-center mb-0">
        <thead>
            <tr>
                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No</th>
                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Siswa</th>

                <?php
                // HANYA LOOP SEBANYAK N YANG AKTIF
                for ($i = 1; $i <= $banyak_ns; $i++):
                    $label = !empty($customNama['n' . $i]) ? $customNama['n' . $i] : 'N' . $i;
                ?>
                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-wrap" style="white-space: normal; word-wrap: break-word; max-width: 120px;">
                        <?= htmlspecialchars($label) ?>
                    </th>
                <?php endfor; ?>

                <th class="text-center bg-light text-xxs font-weight-bolder">Rata-rata</th>
                <th class="text-center bg-warning text-white text-xxs font-weight-bolder">STS</th>
                <th class="text-center bg-info text-white text-xxs font-weight-bolder">SAS</th>
                <th class="text-center bg-success text-white text-xxs font-weight-bolder">Nilai Raport</th>
                <th class="text-center text-xxs font-weight-bolder">Tuntas (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($hasil_laporan as $nilai): ?>
                <tr>
                    <td class="text-center text-sm"><?= $no++ ?></td>
                    <td>
                        <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($nilai['nama_siswa']) ?></p>
                    </td>

                    <?php
                    // HANYA TAMPILKAN DATA SEBANYAK N YANG AKTIF
                    for ($i = 1; $i <= $banyak_ns; $i++):
                    ?>
                        <td class="text-center text-xs">
                            <?= ($nilai['n' . $i] !== null && $nilai['n' . $i] !== '') ? htmlspecialchars($nilai['n' . $i]) : '-' ?>
                        </td>
                    <?php endfor; ?>

                    <td class="text-center bg-light">
                        <span class="text-xs font-weight-bold"><?= number_format($nilai['rata'] ?? 0, 1) ?></span>
                    </td>
                    <td class="text-center bg-warning text-white">
                        <span class="text-xs font-weight-bold"><?= htmlspecialchars($nilai['sts'] ?? '-') ?></span>
                    </td>
                    <td class="text-center bg-info text-white">
                        <span class="text-xs font-weight-bold"><?= htmlspecialchars($nilai['sas'] ?? '-') ?></span>
                    </td>
                    <td class="text-center bg-success">
                        <span class="text-white text-xs font-weight-bold"><?= number_format($nilai['nilai_raport'] ?? 0, 1) ?></span>
                    </td>

                    <td class="text-center">
                        <div class="progress-wrapper w-75 mx-auto">
                            <div class="progress-info">
                                <div class="progress-percentage">
                                    <span class="text-xs font-weight-bold"><?= round($nilai['persentase_tuntas'] ?? 0) ?>%</span>
                                </div>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-gradient-success" style="width: <?= round($nilai['persentase_tuntas'] ?? 0) ?>%;" role="progressbar"></div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 10px;">
                            <?= htmlspecialchars($nilai['jumlah_nilai_kosong'] ?? 0) ?> nilai kosong
                        </small>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>