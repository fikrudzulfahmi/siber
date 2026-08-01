<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">

        <div class="col-lg-12 col-md-12 mx-auto mb-4">
            <div class="card mt-4">
                <div class="card-header p-3 pt-2">
                    <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">router</i>
                    </div>

                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize">Monitoring Jaringan</p>
                        <h4 class="mb-0">Status Mesin Fingerprint</h4>

                        <div class="mt-3">
                            <?php
                            date_default_timezone_set('Asia/Jakarta');

                            if (!empty($data['last_ping'])) {
                                $last_ping_time = strtotime($data['last_ping'] . ' UTC');
                                $waktu_sekarang = time();
                                $selisih = $waktu_sekarang - $last_ping_time;
                                $is_online = ($selisih >= 0 && $selisih < 90);

                                if ($is_online) {
                                    $badge_m1 = ($data['mesin1_status'] == 'Online') ? 'bg-success' : 'bg-danger';
                                    $badge_m2 = ($data['mesin2_status'] == 'Online') ? 'bg-success' : 'bg-danger';

                                    echo '
                                    <div class="d-flex justify-content-end align-items-center mb-1">
                                        <span class="text-sm font-weight-bold text-dark me-2">Server Lokal (Node.js) :</span>
                                        <span class="badge bg-success" style="width: 80px;">● Online</span>
                                    </div>
                                    <div class="d-flex justify-content-end align-items-center mb-1">
                                        <span class="text-sm text-secondary me-2">Mesin 1 (IP 192.168.2.89) :</span>
                                        <span class="badge ' . $badge_m1 . '" style="width: 80px;">● ' . $data['mesin1_status'] . '</span>
                                    </div>
                                    <div class="d-flex justify-content-end align-items-center">
                                        <span class="text-sm text-secondary me-2">Mesin 2 (IP 192.168.2.91) :</span>
                                        <span class="badge ' . $badge_m2 . '" style="width: 80px;">● ' . $data['mesin2_status'] . '</span>
                                    </div>';
                                } else {
                                    echo '
                                    <div class="d-flex justify-content-end align-items-center mb-1">
                                        <span class="text-sm font-weight-bold text-dark me-2">Server Lokal (Node.js) :</span>
                                        <span class="badge bg-danger" style="width: 80px;">● Offline</span>
                                    </div>
                                    <p class="text-xs text-danger mb-0 mt-1">*Status Mesin tidak dapat dilacak karena Server Lokal terputus.</p>';
                                }
                            } else {
                                echo '<span class="badge bg-secondary mt-2">Data Status Belum Ada</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-md-12 mx-auto">
            <div class="card mt-4">
                <div class="card-header p-3 pt-2">
                    <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">mark_email_read</i>
                    </div>

                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize">Konfigurasi Sistem</p>
                        <h4 class="mb-0">Pengaturan WhatsApp Gateway</h4>
                    </div>
                </div>

                <hr class="dark horizontal my-0">

                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fitur Notifikasi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Key Database</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_settings as $set): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= $set['keterangan'] ?></h6>
                                                    <p class="text-xs text-secondary mb-0">Otomatisasi pengiriman pesan via Fonnte</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0"><code><?= $set['key_setting'] ?></code></p>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm badge-indicator" data-key="<?= $set['key_setting'] ?>">
                                                <?= ($set['status'] === 'true') ? 'AKTIF' : 'NON-AKTIF' ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input btn-toggle-wa"
                                                    type="checkbox"
                                                    data-key="<?= $set['key_setting'] ?>"
                                                    <?= ($set['status'] === 'true') ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-3">
                    <div id="statusMessage" class="text-center text-xs font-weight-bold" style="display:none;"></div>
                </div>
            </div>
        </div>

        <!-- 2 Kolom Backup & Restore -->
        <div class="row mt-4">
            <!-- Card Backup -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 text-center p-4">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="icon icon-shape icon-lg bg-light text-center border-radius-xl">
                            <i class="material-icons text-success text-gradient opacity-10">cloud_upload</i>
                        </div>
                    </div>
                    <h5 class="text-dark font-weight-bolder">Backup Database</h5>
                    <p class="text-sm text-secondary mb-4">Buat cadangan data aplikasi Anda saat ini. Proses ini akan menghasilkan file .sql berisi database, menyimpannya secara lokal, dan mengunggahnya ke Google Drive.</p>
                    <div>
                        <a href="?controller=backup&method=createBackup" class="btn btn-success" onclick="return confirm('Apakah Anda yakin ingin memulai proses backup sekarang? Proses ini akan memakan waktu beberapa saat.')">
                            Buat Backup Sekarang
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Card Restore -->
            <div class="col-md-6 mb-4">
                <div class="card h-100 text-center p-4">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="icon icon-shape icon-lg bg-light text-center border-radius-xl">
                            <i class="material-icons text-danger text-gradient opacity-10">autorenew</i>
                        </div>
                    </div>
                    <h5 class="text-dark font-weight-bolder">Restore Database</h5>
                    <p class="text-sm text-secondary mb-4">Pulihkan database dari file `.sql` mentah hasil backup sebelumnya.</p>
                    
                    <form action="?controller=backup&method=restore" method="POST" enctype="multipart/form-data" onsubmit="return confirm('PERINGATAN Keras! Semua data Anda saat ini akan dihapus permanen dan diganti dengan data dari file backup. Lanjutkan?');">
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <input type="file" name="backup_file" class="form-control form-control-sm border px-2" accept=".sql" style="max-width: 250px;" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100" style="max-width: 250px;">
                            Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Card Konfigurasi Folder Google Drive -->
        <div class="row mt-4">
            <div class="col-lg-12 col-md-12 mx-auto mb-4">
                <div class="card">
                    <div class="card-header pb-0 p-3">
                        <h6 class="mb-0">Konfigurasi Folder Google Drive</h6>
                        <p class="text-sm text-secondary mb-0">Atur ID Folder Google Drive yang akan digunakan untuk menampung file backup Anda secara otomatis.</p>
                        <p class="text-sm text-secondary mb-0">Contoh : https://drive.google.com/drive/u/0/folders/<strong>1Addxxxxxxxxxxxxxxxxxxxxxxxxxxxx</strong></p>
                        <p class="text-sm text-secondary mb-0">Ambil kombinasi huruf dan angka di belakang /folders/.</p>
                    </div>
                    <div class="card-body p-3">
                        <?php
                            $realFolderId = '';
                            $configPath = __DIR__ . '/../../../config.php';
                            if (file_exists($configPath)) {
                                $cfg = file_get_contents($configPath);
                                if (preg_match("/define\('GOOGLE_DRIVE_FOLDER_ID',\s*'(.*?)'\);/", $cfg, $matches)) {
                                    $realFolderId = $matches[1];
                                }
                            }
                        ?>
                        <form action="?controller=setting&method=updateFolderIdConfig" method="POST">
                            <div class="row">
                                <div class="col-md-9">
                                    <div class="input-group input-group-outline mb-1 is-filled">
                                        <label class="form-label">Google Drive Folder ID</label>
                                        <input type="text" name="folder_id" class="form-control" value="<?= htmlspecialchars($realFolderId) ?>" placeholder="Contoh: 1A2b3C4d5E6f7G8h9I0j" required>
                                    </div>
                                    <small class="text-success text-xs fw-bold">ID Tersimpan saat ini: <?= htmlspecialchars($realFolderId ?: 'Belum ada') ?></small>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">Simpan ID Folder</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Card Riwayat Backup -->
        <div class="row">
            <div class="col-lg-12 col-md-12 mx-auto mb-4">
            <div class="card">
                <div class="card-header pb-0 p-3">
                    <h6 class="mb-0">Riwayat Backup</h6>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama File</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Ukuran</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tanggal</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($riwayat_backup)): ?>
                                    <?php foreach ($riwayat_backup as $rb): ?>
                                        <tr>
                                            <td>
                                                <p class="text-sm font-weight-bold mb-0 px-3"><?= htmlspecialchars($rb['nama_file']) ?></p>
                                            </td>
                                            <td>
                                                <p class="text-sm text-secondary mb-0"><?= htmlspecialchars($rb['ukuran']) ?></p>
                                            </td>
                                            <td>
                                                <p class="text-sm text-secondary mb-0"><?= htmlspecialchars($rb['tanggal']) ?></p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a href="?controller=backup&method=downloadBackup&file=<?= urlencode($rb['nama_file']) ?>" class="text-info font-weight-bold text-xs mx-2">
                                                    Unduh
                                                </a>
                                                <a href="?controller=backup&method=deleteBackup&file=<?= urlencode($rb['nama_file']) ?>" class="text-danger font-weight-bold text-xs mx-2" onclick="return confirm('Yakin ingin menghapus file backup ini secara permanen?')">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-sm text-secondary py-3">Belum ada riwayat backup lokal.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggles = document.querySelectorAll('.btn-toggle-wa');
        const message = document.getElementById('statusMessage');

        // Fungsi untuk memperbarui tampilan Badge secara real-time
        const updateUI = (key, isChecked) => {
            const badge = document.querySelector(`.badge-indicator[data-key="${key}"]`);
            if (badge) {
                if (isChecked) {
                    badge.innerText = 'AKTIF';
                    badge.className = 'badge badge-sm badge-indicator bg-gradient-success';
                } else {
                    badge.innerText = 'NON-AKTIF';
                    badge.className = 'badge badge-sm badge-indicator bg-gradient-danger';
                }
            }
        };

        // Inisialisasi warna saat halaman dimuat
        toggles.forEach(t => updateUI(t.dataset.key, t.checked));

        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const isChecked = this.checked;
                const key = this.dataset.key;

                // Fetch ke SettingController method updateAJAX
                fetch('?controller=setting&method=updateAJAX', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            key: key,
                            status: isChecked
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateUI(key, isChecked);
                            message.innerText = "Berhasil: " + key + " diubah menjadi " + data.db_value;
                            message.style.display = 'block';
                            message.className = 'text-center text-xs font-weight-bold text-success';
                            setTimeout(() => {
                                message.style.display = 'none';
                            }, 3000);
                        } else {
                            alert("Gagal memperbarui database.");
                            this.checked = !isChecked;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.checked = !isChecked;
                    });
            });
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>