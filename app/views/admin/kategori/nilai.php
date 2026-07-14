<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<?php
// Decode Nama Kustom untuk Header Tabel (N1, N2, dst)
$customNama = json_decode($kategoriInfo['nama_ns'] ?? '{}', true);
?>

<?php if ($msg = getFlash('success')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#4caf50'
            });
        });
    </script>
<?php endif; ?>

<?php if ($msg = getFlash('danger')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#d33'
            });
        });
    </script>
<?php endif; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            Input Nilai: <?= htmlspecialchars($kategoriInfo['kategori']) ?><br>
                            <small class="text-white opacity-8"><?= htmlspecialchars($info['nama_mapel']) ?> - Kelas <?= htmlspecialchars($info['kelas']) ?></small>
                        </h6>
                    </div>
                </div>

                <form action="?controller=kategori&method=updateNilai" method="POST">
                    <input type="hidden" name="id_kategori" value="<?= htmlspecialchars($kategoriInfo['id_kategori']) ?>">
                    <input type="hidden" name="id_mapel_guru" value="<?= htmlspecialchars($info['id_mapel_guru']) ?>">
                    <input type="hidden" name="banyak_ns" value="<?= htmlspecialchars($kategoriInfo['banyak_ns']) ?>">

                    <div class="card-body px-0 pb-2">
                        <div class="alert alert-info text-white mx-4">
                            <strong>Info:</strong> Kategori ini menggunakan <strong><?= htmlspecialchars($kategoriInfo['banyak_ns']) ?> Nilai Harian</strong> yang ditampilkan di bawah ini.
                        </div>

                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Siswa</th>

                                        <?php
                                        // HANYA LOOP SEBANYAK 'banyak_ns', KOLOM SELEBIHNYA TERSEMBUNYI
                                        for ($i = 1; $i <= $kategoriInfo['banyak_ns']; $i++):
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
                                    <?php if (empty($nilaiList)): ?>
                                        <tr>
                                            <td colspan="<?= 7 + $kategoriInfo['banyak_ns'] ?>" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center justify-content-center">
                                                    <i class="material-icons text-secondary text-4xl mb-2">person_off</i>
                                                    <h6 class="text-secondary font-weight-normal">
                                                        Tidak ada siswa ditemukan di kelas ini.
                                                    </h6>
                                                    <p class="text-xs text-muted">
                                                        Kemungkinan Admin belum melakukan <b>Ploting Siswa</b> untuk
                                                        Tahun Ajaran/Kelas ini.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1;
                                        foreach ($nilaiList as $nilai): ?>
                                            <tr>
                                                <td class="text-center text-sm"><?= $no++ ?></td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($nilai['nama_siswa']) ?></p>
                                                </td>

                                                <?php for ($i = 1; $i <= $kategoriInfo['banyak_ns']; $i++): ?>
                                                    <td>
                                                        <input type="number" step="any" name="nilai[<?= $nilai['id_nilai'] ?>][n<?= $i ?>]"
                                                            value="<?= htmlspecialchars($nilai['n' . $i]) ?>"
                                                            class="form-control form-control-sm text-center p-1"
                                                            style="min-width: 50px;">
                                                    </td>
                                                <?php endfor; ?>

                                                <td class="text-center bg-light">
                                                    <span class="text-xs font-weight-bold"><?= round($nilai['rata'], 1) ?></span>
                                                </td>
                                                <td class="bg-warning">
                                                    <input type="number" step="any" name="nilai[<?= $nilai['id_nilai'] ?>][sts]" value="<?= htmlspecialchars($nilai['sts']) ?>" class="form-control form-control-sm text-center p-1 text-white" style="min-width: 60px; background: rgba(0,0,0,0.1)">
                                                </td>
                                                <td class="bg-info">
                                                    <input type="number" step="any" name="nilai[<?= $nilai['id_nilai'] ?>][sas]" value="<?= htmlspecialchars($nilai['sas']) ?>" class="form-control form-control-sm text-center p-1 text-white" style="min-width: 60px; background: rgba(0,0,0,0.1)">
                                                </td>
                                                <td class="text-center bg-success">
                                                    <span class="text-white text-xs font-weight-bold"><?= round($nilai['nilai_raport'], 1) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="progress-wrapper w-75 mx-auto">
                                                        <div class="progress-info">
                                                            <div class="progress-percentage"><span class="text-xs font-weight-bold"><?= round($nilai['persentase_tuntas']) ?>%</span></div>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-gradient-success" style="width: <?= round($nilai['persentase_tuntas']) ?>%;" role="progressbar"></div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted d-block mt-1" style="font-size: 10px;"><?= $nilai['jumlah_nilai_kosong'] ?> nilai kosong</small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end flex-wrap">
                        <a href="?controller=kategori&method=index&id_mapel_guru=<?= $info['id_mapel_guru'] ?>" class="btn btn-secondary me-2">Kembali</a>

                        <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#modalImport">
                            <i class="fas fa-upload"></i>&nbsp; Import Excel
                        </button>

                        <a href="?controller=kategori&method=exportExcel&id_kategori=<?= $kategoriInfo['id_kategori'] ?>&id_mapel_guru=<?= $info['id_mapel_guru'] ?>" class="btn btn-dark me-2">
                            <i class="fas fa-file-excel text-success"></i>&nbsp; Export Excel
                        </a>

                        <a href="?controller=kategori&method=exportPdf&id_kategori=<?= $kategoriInfo['id_kategori'] ?>&id_mapel_guru=<?= $info['id_mapel_guru'] ?>" target="_blank" class="btn btn-dark me-2">
                            <i class="fas fa-file-pdf text-danger"></i>&nbsp; Export PDF
                        </a>

                        <button type="submit" class="btn btn-success">Simpan Semua Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="?controller=kategori&method=importExcel" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Import Nilai dari Excel</h5>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_kategori" value="<?= $kategoriInfo['id_kategori'] ?>">
                    <input type="hidden" name="id_mapel_guru" value="<?= $info['id_mapel_guru'] ?>">
                    <p class="text-sm text-muted">Pastikan format file sesuai dengan template hasil export.</p>
                    <div class="input-group input-group-outline my-3">
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Upload & Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>