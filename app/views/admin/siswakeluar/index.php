<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>


<div class="container-fluid py-4">

    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-danger shadow-danger border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">PD Keluar &mdash; Peserta Didik Berhenti</h6>
                    </div>
                </div>

                <div class="card-body px-4 pb-2 pt-4">
                    <p class="text-sm text-secondary mb-3">
                        Catat siswa yang keluar karena <b>mengundurkan diri</b>, <b>mutasi/pindah</b>, <b>meninggal</b>,
                        atau alasan lain. Data nilai dan rapor tetap tersimpan, tetapi siswa tidak lagi muncul di
                        daftar nilai, rapor, leger, dan ploting. Bila salah catat, gunakan tombol
                        <b>Kembalikan</b> di tabel riwayat.
                    </p>

                    <div class="row mb-4">
                        <?php foreach ($alasan as $kunci => $label): ?>
                            <div class="col-6 col-md-2 mb-2">
                                <div class="border-radius-md bg-gray-100 p-3 text-center">
                                    <h5 class="mb-0"><?= (int) ($ringkasan[$kunci] ?? 0) ?></h5>
                                    <small class="text-secondary"><?= sec_e($label) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="post" action="index.php?controller=siswaKeluar&method=keluarkan" id="formKeluar" class="row g-3 align-items-end mb-4">
                        <?= sec_csrf_field() ?>

                        <?php if ($pra_siswa): ?>
                            <div class="col-12">
                                <div class="alert alert-info py-2 mb-0">
                                    Siswa terpilih: <b><?= sec_e($pra_siswa['nama_siswa']) ?></b>
                                    (NISN <?= sec_e($pra_siswa['nisn']) ?><?= $pra_siswa['kelas_terakhir'] ? ', kelas ' . sec_e($pra_siswa['kelas_terakhir']) : '' ?>)
                                    &mdash; <a href="index.php?controller=siswaKeluar&method=index" class="text-decoration-underline">batalkan pilihan</a>
                                </div>
                            </div>
                            <input type="hidden" name="id_siswa" value="<?= (int) $pra_siswa['id_siswa'] ?>">
                        <?php endif; ?>

                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs font-weight-bold">Tahun Ajaran</label>
                            <select name="id_tahun" id="pilihTahun" class="form-select form-select-sm" onchange="window.location='index.php?controller=siswaKeluar&method=index&tahun=' + this.value">
                                <option value="">- pilih -</option>
                                <?php foreach ($tahun_list as $t): ?>
                                    <option value="<?= (int) $t['id_tahun_pelajaran'] ?>" <?= ((int) $t['id_tahun_pelajaran'] === (int) $id_tahun_form) ? 'selected' : '' ?>>
                                        <?= sec_e($t['tahun_pelajaran']) ?><?= ((int) $t['status'] === 1) ? ' (aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (!$pra_siswa): ?>
                            <div class="col-6 col-md-3">
                                <label class="form-label text-xs font-weight-bold">Kelas</label>
                                <select id="pilihKelas" class="form-select form-select-sm">
                                    <option value="">- pilih kelas -</option>
                                    <?php foreach ($kelas_list as $k): ?>
                                        <option value="<?= (int) $k['id_kelas'] ?>"><?= sec_e($k['kelas']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label text-xs font-weight-bold">Nama Siswa</label>
                                <select name="id_siswa" id="pilihSiswa" class="form-select form-select-sm" required>
                                    <option value="">- pilih kelas dulu -</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs font-weight-bold">Alasan Keluar</label>
                            <select name="status" class="form-select form-select-sm" required>
                                <?php foreach ($alasan as $kunci => $label): ?>
                                    <?php if ($kunci === 'lulus') continue; // lulus lewat menu Ploting ?>
                                    <option value="<?= sec_e($kunci) ?>"><?= sec_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs font-weight-bold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label text-xs font-weight-bold">Keterangan (opsional)</label>
                            <input type="text" name="keterangan" maxlength="255" class="form-control form-control-sm"
                                   placeholder="Sekolah tujuan / nomor surat / alasan">
                        </div>

                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-danger btn-sm w-100 mb-0"
                                    data-confirm="Keluarkan siswa ini? Data nilai tetap tersimpan dan bisa dikembalikan.">
                                <i class="material-icons-round align-middle me-1" style="font-size:18px">logout</i> Keluarkan
                            </button>
                        </div>
                    </form>

                    <hr>

                    <form method="get" action="index.php" class="row g-2 align-items-end mb-3">
                        <input type="hidden" name="controller" value="siswaKeluar">
                        <input type="hidden" name="method" value="index">
                        <div class="col-6 col-md-3">
                            <label class="form-label text-xs font-weight-bold">Cari nama / NISN</label>
                            <input type="text" name="q" class="form-control form-control-sm" value="<?= sec_e($filter['q']) ?>">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs font-weight-bold">Alasan</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <?php foreach ($alasan as $kunci => $label): ?>
                                    <option value="<?= sec_e($kunci) ?>" <?= ($filter['status'] === $kunci) ? 'selected' : '' ?>><?= sec_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label text-xs font-weight-bold">Tahun</label>
                            <select name="tahun" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <?php foreach ($tahun_list as $t): ?>
                                    <option value="<?= (int) $t['id_tahun_pelajaran'] ?>" <?= ((int) $t['id_tahun_pelajaran'] === (int) $filter['tahun']) ? 'selected' : '' ?>><?= sec_e($t['tahun_pelajaran']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" name="riwayat" id="riwayat" value="1" <?= $filter['riwayat'] ? 'checked' : '' ?>>
                                <label class="form-check-label text-xs" for="riwayat">Tampilkan yang sudah dikembalikan</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-success btn-sm w-100 mb-0"><i class="material-icons-round align-middle me-1" style="font-size:18px">search</i> Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-items-center mb-0" id="tabelKeluar">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center text-xs" width="4%">No</th>
                                    <th class="text-xs">Nama Siswa</th>
                                    <th class="text-xs">NISN</th>
                                    <th class="text-xs">Kelas</th>
                                    <th class="text-xs">Tahun</th>
                                    <th class="text-xs">Alasan</th>
                                    <th class="text-xs text-center">Tanggal</th>
                                    <th class="text-xs">Keterangan</th>
                                    <th class="text-xs">Dicatat</th>
                                    <th class="text-xs text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$riwayat): ?>
                                    <tr><td colspan="10" class="text-center text-secondary text-sm py-3">Belum ada data.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($riwayat as $r): ?>
                                        <tr class="<?= !empty($r['dibatalkan_pada']) ? 'text-secondary text-decoration-line-through' : '' ?>">
                                            <td class="text-center text-xs"><?= $no++ ?></td>
                                            <td class="text-xs fw-bold"><?= sec_e($r['nama_siswa']) ?></td>
                                            <td class="text-xs"><?= sec_e($r['nisn']) ?></td>
                                            <td class="text-xs"><?= sec_e($r['kelas_saat_keluar'] ?? '-') ?></td>
                                            <td class="text-xs"><?= sec_e($r['tahun_pelajaran'] ?? '-') ?></td>
                                            <td class="text-xs"><?= sec_e($alasan[$r['status']] ?? $r['status']) ?></td>
                                            <td class="text-xs text-center"><?= $r['tanggal'] ? date('d-m-Y', strtotime($r['tanggal'])) : '-' ?></td>
                                            <td class="text-xs"><?= sec_e($r['keterangan'] ?? '') ?></td>
                                            <td class="text-xs">
                                                <?= sec_e($r['dicatat_oleh'] ?? '-') ?>
                                                <?php if (!empty($r['dibatalkan_pada'])): ?>
                                                    <br><small>dikembalikan <?= date('d-m-Y', strtotime($r['dibatalkan_pada'])) ?> oleh <?= sec_e($r['dibatalkan_oleh'] ?? '-') ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (empty($r['dibatalkan_pada'])): ?>
                                                    <form method="post" action="index.php?controller=siswaKeluar&method=kembalikan" class="d-inline">
                                                        <?= sec_csrf_field() ?>
                                                        <input type="hidden" name="id_keluar" value="<?= (int) $r['id_keluar'] ?>">
                                                        <?php if ($filter['riwayat']): ?><input type="hidden" name="riwayat" value="1"><?php endif; ?>
                                                        <button type="submit" class="btn btn-outline-success btn-sm mb-0"
                                                                data-confirm="Kembalikan siswa ini ke status aktif? Nilainya akan tampil lagi.">
                                                            <i class="material-icons-round align-middle" style="font-size:18px">undo</i> Kembalikan
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">dibatalkan</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    var pilihKelas = document.getElementById('pilihKelas');
    var pilihSiswa = document.getElementById('pilihSiswa');
    var pilihTahun = document.getElementById('pilihTahun');

    if (pilihKelas && pilihSiswa) {
        pilihKelas.addEventListener('change', function() {
            var idKelas = this.value;
            var idTahun = pilihTahun ? pilihTahun.value : '';
            pilihSiswa.innerHTML = '<option value="">- memuat... -</option>';
            if (!idKelas || !idTahun) {
                pilihSiswa.innerHTML = '<option value="">- pilih kelas dulu -</option>';
                return;
            }
            fetch('index.php?controller=siswaKeluar&method=siswa&id_kelas=' + encodeURIComponent(idKelas) + '&id_tahun=' + encodeURIComponent(idTahun), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var opsi = '<option value="">- pilih siswa -</option>';
                (res.data || []).forEach(function(s) {
                    opsi += '<option value="' + s.id_siswa + '">' + s.nama_siswa + (s.nisn ? ' (' + s.nisn + ')' : '') + '</option>';
                });
                pilihSiswa.innerHTML = opsi;
                if (!res.data || !res.data.length) {
                    pilihSiswa.innerHTML = '<option value="">- tidak ada siswa aktif di kelas ini -</option>';
                }
            })
            .catch(function() {
                pilihSiswa.innerHTML = '<option value="">- gagal memuat siswa -</option>';
            });
        });
    }
});
</script>

<?php include '../app/views/layouts/footer.php'; ?>
