<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    .form-control,
    .form-select {
        padding-left: 1rem !important;
    }
</style>

<style>
    /* Kelas ini akan kita tambahkan ke <table> Anda.
      Ini adalah kunci utamanya.
    */
    .tabel-presensi {
        table-layout: fixed; /* Memaksa tabel mematuhi lebar kolom */
        width: 100%;         /* Pastikan tabel memenuhi lebar kontainer */
    }

    /* Opsional: Kelas ini bisa Anda tambahkan ke <td> 
      jika ada teks yang perlu dipaksa turun baris (wrap).
    */
    .wrap-text {
        white-space: normal !important; /* Memaksa teks untuk turun baris (wrap) */
        overflow-wrap: break-word;     /* Modern, akan memutus kata HANYA JIKA kata itu terlalu panjang untuk satu baris */
        word-break: normal; 
    }
</style>


<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <!-- Header Card -->
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 mb-4">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Rekap Presensi</h6>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="card-body p-3">
                    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="hidden" name="controller" value="repres">
                        <input type="hidden" name="method" value="index">

                        <input type="date" name="start" value="<?= htmlspecialchars($startDate) ?>" class="form-control border focus-ring focus-ring-success rounded-3">
                        <input type="date" name="end" value="<?= htmlspecialchars($endDate) ?>" class="form-control border focus-ring focus-ring-success rounded-3">

                        <select name="jabatan" class="form-select border focus-ring focus-ring-success rounded-3">
                            <option value="">Semua Jabatan</option>
                            <?php foreach ($listJabatan as $jab): ?>
                                <option value="<?= $jab['id_jabatan'] ?>" <?= ($idJabatan == $jab['id_jabatan']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($jab['jabatan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-success">Filter</button>

                        <a href="?controller=repres&method=cetak&start=<?= urlencode($startDate) ?>&end=<?= urlencode($endDate) ?>&jabatan=<?= urlencode($idJabatan) ?>" class="btn btn-dark" target="_blank">Cetak PDF</a>
                        <a href="?controller=repres&method=exportExcel&start=<?= urlencode($startDate) ?>&end=<?= urlencode($endDate) ?>&jabatan=<?= urlencode($idJabatan) ?>"
                            class="btn btn-outline-dark">
                            Export Excel
                        </a>
                        <a href="?controller=rekapIndividu&method=index" class="btn btn-info">Rekap Individu</a>
                    </form>
                </div>

                <!-- Tabel Rekap -->
                <div class="card-body p-3">
                    <div class="table-responsive">
                    <table id="datatable7" class="table tabel-presensi table-bordered table-striped align-items-center mb-0">
                        <thead>
                            <tr>
                                <th style="width: 45%;">Nama</th>
                                <th style="width: 25%;">Jabatan</th>
                                <th class="wrap-text" style="width: 12%;">Hari efektif</th>
                                <th class="wrap-text" style="width: 10%;">Kehadiran</th>
                                <th class="wrap-text" style="width: 9%;">Alpa</th>
                                <th class="wrap-text" style="width: 10%;">Terlambat</th>
                                <th class="wrap-text" style="width: 13%;">Pulang Cepat</th>
                                <th class="wrap-text" style="width: 10%;">Sakit</th>
                                <th class="wrap-text" style="width: 8%;">Izin</th>
                                <th class="wrap-text" style="width: 12%;">Dinas Luar</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php if (!empty($rekap)): ?>
                                <?php foreach ($rekap as $row): ?>
                                    <tr>
                                        <td style="text-align: left;"><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= htmlspecialchars($row['jabatan']) ?></td>
                                        <td><span class="badge bg-info"><?= $row['Hari_Efektif'] ?></span></td>
                                        <td><span class="badge bg-success"><?= $row['Kehadiran'] ?></span></td>
                                        <td><span class="badge bg-dark"><?= $row['Alpa'] ?></span></td>
                                        <td><span class="badge bg-secondary"><?= $row['Terlambat'] ?></span></td>
                                        <td><span class="badge bg-warning"><?= $row['Pulang_Cepat'] ?></span></td>
                                        <td><span class="badge bg-dark"><?= $row['Sakit'] ?></span></td>
                                        <td><span class="badge bg-warning"><?= $row['Izin'] ?></span></td>
                                        <td><span class="badge bg-secondary"><?= $row['Dinas_Luar'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data</td>
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
    $(document).ready(function() {
        $('#datatable7').DataTable({
            language: {
                searchPlaceholder: "Cari disini...",
                paginate: {
                    previous: '<i class="material-icons-round">chevron_left</i>',
                    next: '<i class="material-icons-round">chevron_right</i>'
                }
            }
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>