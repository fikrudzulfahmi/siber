<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-12 col-md-12 mx-auto">
            <div class="card mt-4">
                <div class="card-header p-3 pt-2">
                    <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                        <i class="material-icons opacity-10">notification_important</i>
                    </div>
                    <div class="text-end pt-1">
                        <p class="text-sm mb-0 text-capitalize">Konfigurasi Pesan</p>
                        <h4 class="mb-0">WhatsApp Notifikasi</h4>
                    </div>
                </div>
                <hr class="dark horizontal my-0">
                <div class="card-body p-3">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <h6 class="mb-0 text-dark">Status Notifikasi Jurnal</h6>
                                <p class="text-xs text-secondary mb-0">
                                    Aktifkan untuk mengizinkan Cronjob mengirim pesan otomatis.
                                </p>
                                <div class="mt-2">
                                    <span class="badge badge-sm" id="badgeStatus">
                                        <?= ($wa_status === 'true') ? 'SISTEM AKTIF' : 'SISTEM NON-AKTIF' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="form-check form-switch ps-0">
                                <input class="form-check-input ms-auto" type="checkbox" id="flexSwitchCheckDefault" <?= ($wa_status === 'true') ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-0">
                        <p class="text-xs mb-0 text-dark">
                            <strong>Info:</strong> Pengaturan ini akan mengubah nilai di database menjadi string <code>true</code> atau <code>false</code> secara literal, yang akan dibaca oleh skrip cronjob Anda untuk menentukan mode <code>$debugMode</code>.
                        </p>
                    </div>
                </div>
                <div class="card-footer p-3">
                    <div id="statusMessage" class="text-center text-xs font-weight-bold" style="display:none;">
                        Tersimpan!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toggle = document.getElementById('flexSwitchCheckDefault');
        const badge = document.getElementById('badgeStatus');
        const message = document.getElementById('statusMessage');

        // Inisialisasi warna badge saat pertama load
        updateBadgeStyle(toggle.checked);

        toggle.addEventListener('change', function() {
            const isChecked = this.checked;

            // Kirim ke Controller
            fetch('?controller=jurnal&method=updateWASetting', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        status: isChecked
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update tampilan UI
                        updateBadgeStyle(isChecked);

                        // Animasi pesan tersimpan
                        message.style.display = 'block';
                        message.classList.add('text-success');
                        setTimeout(() => {
                            message.style.display = 'none';
                        }, 2000);

                        console.log("Database status: " + data.db_value);
                    } else {
                        alert("Gagal memperbarui pengaturan.");
                        this.checked = !isChecked; // Balikkan posisi switch jika gagal
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    this.checked = !isChecked;
                });
        });

        function updateBadgeStyle(active) {
            if (active) {
                badge.innerText = 'SISTEM AKTIF';
                badge.className = 'badge badge-sm bg-gradient-success';
            } else {
                badge.innerText = 'SISTEM NON-AKTIF';
                badge.className = 'badge badge-sm bg-gradient-danger';
            }
        }
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>