<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Tambah Kegiatan Baru</h6>
                    </div>
                </div>

                <div class="card-body px-4">
                    <form action="?controller=kegiatan&method=simpan" method="POST">

                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Nama Kegiatan</label>
                                <input type="text" class="form-control border px-3" name="nama_kegiatan" placeholder="Contoh: Rapat Kerja Kurikulum" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Tanggal</label>
                                <input type="date" class="form-control border px-3" name="tanggal" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Jam Mulai</label>
                                <input type="time" class="form-control border px-3" name="jam_mulai" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Jam Selesai</label>
                                <input type="time" class="form-control border px-3" name="jam_selesai" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label font-weight-bold">Keterangan (Opsional)</label>
                            <textarea class="form-control border px-3" name="keterangan" rows="3" placeholder="Tambahkan rincian kegiatan jika ada..."></textarea>
                        </div>

                        <hr class="horizontal dark">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label class="form-label font-weight-bold mb-0">Pilih Peserta (Guru & Struktural)</label>
                            <div class="d-flex align-items-center">
                                <input type="text" id="searchGuru" class="form-control form-control-sm border px-3 me-3" placeholder="Cari nama..." style="width: 200px;">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                    <label class="form-check-label mb-0 ms-2" for="checkAll">Pilih Semua</label>
                                </div>
                            </div>
                        </div>

                        <div class="participant-container border p-3 rounded bg-light">
                            <div class="row" id="listPeserta" style="max-height: 350px; overflow-y: auto;">
                                <?php foreach ($list_guru as $guru): ?>
                                    <div class="col-md-4 mb-2 guru-item">
                                        <div class="card card-body border-0 shadow-none p-2 mb-0 bg-transparent">
                                            <div class="form-check">
                                                <input class="form-check-input check-item" type="checkbox" name="peserta[]" value="<?= $guru['pin'] ?>" id="guru_<?= $guru['pin'] ?>">
                                                <label class="form-check-label" for="guru_<?= $guru['pin'] ?>">
                                                    <span class="d-block font-weight-bold text-dark text-sm"><?= htmlspecialchars($guru['nama']) ?></span>
                                                    <small class="text-xs text-secondary"><?= htmlspecialchars($guru['jabatan']) ?> (PIN: <?= $guru['pin'] ?>)</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 mb-2">
                            <a href="?controller=kegiatan&method=index" class="btn btn-outline-secondary me-2">Batal</a>
                            <button type="submit" class="btn bg-gradient-success px-5">
                                <i class="fas fa-save me-2"></i>Simpan Kegiatan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Fungsi Pilih Semua
        $('#checkAll').on('click', function() {
            $('.check-item').prop('checked', this.checked);
        });

        // Fungsi Pencarian Nama Guru
        $("#searchGuru").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".guru-item").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });

            // Jika sedang mencari, matikan "Pilih Semua" agar tidak membingungkan
            if (value !== "") {
                $('#checkAll').prop('checked', false);
            }
        });
    });
</script>

<style>
    .participant-container {
        border: 1px solid #dee2e6 !important;
    }

    .form-control.border {
        border: 1px solid #dee2e6 !important;
    }

    .guru-item:hover {
        background-color: #f8f9fa;
        border-radius: 10px;
    }

    /* Scrollbar Styling */
    #listPeserta::-webkit-scrollbar {
        width: 5px;
    }

    #listPeserta::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #listPeserta::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 10px;
    }
</style>