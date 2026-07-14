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