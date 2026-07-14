<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                <h6 class="text-white text-capitalize ps-3">Rekap Presensi Individu</h6>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            <form method="POST" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <select name="pin" class="form-control" required>
                        <option value="">-- Pilih Pegawai --</option>
                        <?php foreach ($listPegawai as $p) : ?>
                            <option value="<?= $p['pin'] ?>" <?= (isset($pin) && $pin == $p['pin']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="month" name="periode" class="form-control" value="<?= htmlspecialchars($periode) ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success">Tampilkan</button>
                    <a href="?controller=repres&method=index" class="btn btn-secondary">Kembali</a>
                </div>
            </form>

            <?php if (isset($rekapDataFormatted)) : ?>
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>Hasil Rekap</h4>
                    <a href="?controller=rekapIndividu&method=cetak&pin=<?= urlencode($pin) ?>&periode=<?= urlencode($periode) ?>" class="btn btn-dark" target="_blank"><i class="material-icons opacity-10">print</i> Cetak PDF</a>
                </div>

                <div class="text-center my-3">
                    <h5>Rekap Presensi Guru & Pegawai</h5>
                    <h6>Pondok Pesantren Raudlatul Mutaalimin</h6>
                    <p>
                        <b>Nama: <?= htmlspecialchars($infoPegawai['nama']) ?></b><br>
                        Tanggal: <?= date('01 F Y', strtotime($periode)) ?> - <?= date('t F Y', strtotime($periode)) ?>
                    </p>
                </div>

                <h5 class="mt-4">Rincian Kehadiran</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr class="text-center">
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Waktu Datang</th>
                            <th>Waktu Pulang</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekapDataFormatted['rincian'] as $i => $d) : ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td><?= $d['tanggal_formatted'] ?></td>
                                <td class="text-center"><?= $d['datang'] ?></td>
                                <td class="text-center"><?= $d['pulang'] ?></td>
                                <td class="text-center"><?= $d['keterangan'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h5 class="mt-4">Ringkasan</h5>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <?php $summary = $rekapDataFormatted['summary']; ?>
                            <tr>
                                <td>Terlambat</td>
                                <td class="text-center"><?= $summary['Terlambat'] ?></td>
                            </tr>
                            <tr>
                                <td>Tidak Terlambat</td>
                                <td class="text-center"><?= $summary['Hadir'] - $summary['Terlambat'] ?></td>
                            </tr>
                            <tr>
                                <td>Pulang Cepat</td>
                                <td class="text-center"><?= $summary['Pulang_Cepat'] ?></td>
                            </tr>
                            <tr>
                                <td>Sakit</td>
                                <td class="text-center"><?= $summary['Sakit'] ?></td>
                            </tr>
                            <tr>
                                <td>Izin</td>
                                <td class="text-center"><?= $summary['Izin'] ?></td>
                            </tr>
                            <tr>
                                <td>Dinas Luar</td>
                                <td class="text-center"><?= $summary['Dinas_Luar'] ?></td>
                            </tr>
                            <tr>
                                <td>Alpa</td>
                                <td class="text-center"><?= $summary['Alpa'] ?></td>
                            </tr>
                            <tr>
                                <td>Kehadiran</td>
                                <td class="text-center"><?= $summary['Hadir'] ?></td>
                            </tr>
                            <tr>
                                <td>Hari Efektif</td>
                                <td class="text-center"><?= $summary['Hari_Efektif'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../app/views/layouts/footer.php'; ?>