<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            Input Nilai Rapor Ekstrakurikuler: <?= htmlspecialchars($ekstra['nama_ekstra']) ?><br>
                            <small class="text-white opacity-8">
                                Periode: <?= htmlspecialchars($raporAktif['jenis_rapor']) ?> Semester <?= htmlspecialchars($raporAktif['semester']) ?>,
                                Tahun Pelajaran: <?= htmlspecialchars($raporAktif['tahun_pelajaran']) ?>
                            </small>
                        </h6>
                    </div>
                </div>

                <div class="card-body px-4 pb-2">
                    <form action="?controller=ekstra&method=simpanNilaiRapor" method="POST">
                        <input type="hidden" name="id_ekstra" value="<?= $ekstra['id_ekstra'] ?>">
                        <input type="hidden" name="id_rapor" value="<?= $raporAktif['id_rapor'] ?>">

                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="5%">No</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="25%">Nama Siswa</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="10%">Kelas</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" width="15%">Nilai (Predikat)</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan / Capaian Kompetensi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($anggota as $s):
                                        // Ambil nilai lama
                                        $old = (isset($nilai_lama[$s['id_siswa']][0])) ? $nilai_lama[$s['id_siswa']][0] : ($nilai_lama[$s['id_siswa']] ?? null);

                                        // Logika pengecekan: Apakah sudah ada nilai yang tersimpan?
                                        $is_filled = !empty($old['nilai']);
                                        // Warna background baris: hijau sangat muda jika sudah terisi
                                        $row_bg = $is_filled ? 'style="background-color: #f0fff4;"' : '';
                                    ?>
                                        <tr <?= $row_bg ?>>
                                            <td class="text-center text-sm"><?= $no++ ?></td>
                                            <td>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">
                                                        <?= htmlspecialchars($s['nama_siswa']) ?>
                                                        <?php if ($is_filled): ?>
                                                            <span class="badge badge-sm bg-gradient-success ms-2" style="font-size: 0.6rem;">
                                                                <i class="fas fa-check"></i> Terisi
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                </div>
                                            </td>
                                            <td class="text-sm"><?= $s['kelas'] ?></td>
                                            <td class="align-middle text-center">
                                                <select name="nilai_siswa[<?= $s['id_siswa'] ?>][nilai]"
                                                    class="form-select border px-2 text-center text-sm <?= $is_filled ? 'border-success' : '' ?>"
                                                    style="height: 40px;">
                                                    <option value="">- Pilih -</option>
                                                    <option value="A" <?= ($old['nilai'] ?? '') == 'A' ? 'selected' : '' ?>>A (Sangat Baik)</option>
                                                    <option value="B" <?= ($old['nilai'] ?? '') == 'B' ? 'selected' : '' ?>>B (Baik)</option>
                                                    <option value="C" <?= ($old['nilai'] ?? '') == 'C' ? 'selected' : '' ?>>C (Cukup)</option>
                                                    <option value="D" <?= ($old['nilai'] ?? '') == 'D' ? 'selected' : '' ?>>D (Kurang)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <textarea
                                                    name="nilai_siswa[<?= $s['id_siswa'] ?>][keterangan]"
                                                    class="form-control border px-2 py-1 text-sm <?= $is_filled ? 'border-success' : '' ?>"
                                                    rows="2"
                                                    placeholder="Contoh: Sangat aktif dalam kegiatan..."><?= htmlspecialchars($old['keterangan'] ?? '') ?></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 mb-3 d-flex justify-content-between">
                            <a href="?controller=ekstra&method=index" class="btn btn-outline-secondary">
                                <i class="material-icons text-sm">arrow_back</i> Kembali
                            </a>
                            <button type="submit" class="btn bg-gradient-success">
                                <i class="material-icons text-sm">save</i> Simpan Nilai Rapor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>