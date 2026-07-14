<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-gradient-dark p-3">
            <h6 class="text-white mb-0">Cetak Laporan Ekstrakurikuler</h6>
        </div>
        <div class="card-body p-4">
            <form action="?controller=ekstra&method=cetakLaporan" method="POST" target="_blank">
                <input type="hidden" name="id_ekstra" value="<?= $_GET['id_ekstra'] ?>">

                <div class="row">
                    <div class="col-md-12 mb-4">
                        <label class="form-label font-weight-bold">Pilih Periode Laporan:</label>
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <label class="form-label text-xs font-weight-bold">DARI TANGGAL</label>
                                <div class="input-group">
                                    <input type="date" name="tgl_awal" class="form-control border ps-2" required>
                                </div>
                            </div>

                            <div class="col-md-2 text-center mt-4">
                                <span class="badge bg-light text-dark shadow-none border">Sampai Dengan</span>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label text-xs font-weight-bold">SAMPAI TANGGAL</label>
                                <div class="input-group">
                                    <input type="date" name="tgl_akhir" class="form-control border ps-2" required>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">Laporan akan mencakup Rekap Siswa, Rekap Guru, dan Jurnal pada tanggal tersebut.</small>
                    </div>
                </div>

                <div class="text-end border-top pt-3">
                    <a href="?controller=ekstra&method=index" class="btn btn-link text-secondary mb-0">Kembali</a>
                    <button type="submit" class="btn bg-gradient-danger mb-0">
                        <i class="fas fa-file-pdf me-2 text-lg"></i> Generate PDF (Dompdf)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info mt-4 text-white border-0 shadow-sm" role="alert">
        <strong>Tips:</strong> Untuk laporan per-semester, pilih tanggal awal semester (misal: 1 Januari) dan tanggal akhir semester (misal: 30 Juni).
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>