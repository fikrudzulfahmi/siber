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
                        <?= sec_csrf_field() ?>
                        <input type="hidden" name="id_ekstra" value="<?= $ekstra['id_ekstra'] ?>">
                        <input type="hidden" name="id_rapor" value="<?= $raporAktif['id_rapor'] ?>">

                        <?php
                        // Ambil daftar kelas unik dan hitung jumlah siswa per kelas
                        $daftar_kelas = [];
                        $jumlah_per_kelas = [];
                        if (!empty($anggota)) {
                            foreach ($anggota as $s) {
                                $kls = !empty($s['kelas']) ? $s['kelas'] : '-';
                                if (!isset($jumlah_per_kelas[$kls])) {
                                    $jumlah_per_kelas[$kls] = 0;
                                    $daftar_kelas[] = $kls;
                                }
                                $jumlah_per_kelas[$kls]++;
                            }
                        }
                        ?>

                        <!-- Toolbar Filter & Pencarian Per Kelas -->
                        <div class="row align-items-center mb-3 g-2 bg-light p-2 border-radius-md border">
                            <div class="col-md-4 col-sm-6">
                                <label for="filterKelas" class="form-label text-xs font-weight-bold text-uppercase text-secondary mb-1">
                                    <i class="material-icons-round text-xs align-middle">filter_alt</i> Filter Kelas:
                                </label>
                                <select id="filterKelas" class="form-select form-select-sm border px-2 bg-white" onchange="filterDanHitung()">
                                    <option value="">-- Semua Kelas (<?= count($anggota) ?> Siswa) --</option>
                                    <?php foreach ($daftar_kelas as $kls): ?>
                                        <option value="<?= htmlspecialchars($kls) ?>">
                                            Kelas <?= htmlspecialchars($kls) ?> (<?= $jumlah_per_kelas[$kls] ?> siswa)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <label for="cariSiswa" class="form-label text-xs font-weight-bold text-uppercase text-secondary mb-1">
                                    <i class="material-icons-round text-xs align-middle">search</i> Cari Siswa:
                                </label>
                                <input type="text" id="cariSiswa" class="form-control form-control-sm border px-2 bg-white" placeholder="Ketik nama siswa atau kelas..." onkeyup="filterDanHitung()">
                            </div>
                            <div class="col-md-4 col-12 text-md-end text-start mt-md-3">
                                <span class="badge bg-gradient-info py-2 px-3 text-xxs">
                                    Menampilkan <span id="countTampil"><?= count($anggota) ?></span> dari <?= count($anggota) ?> siswa
                                </span>
                            </div>
                        </div>

                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0" id="tabelNilaiEkstra">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center user-select-none" style="cursor: pointer; width: 65px;" onclick="sortTable(0)" title="Klik untuk mengurutkan No">
                                            No <i class="material-icons-round text-xxs align-middle" id="sortIcon0">unfold_more</i>
                                        </th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 user-select-none" style="cursor: pointer;" onclick="sortTable(1)" title="Klik untuk mengurutkan Nama Siswa">
                                            Nama Siswa <i class="material-icons-round text-xxs align-middle" id="sortIcon1">unfold_more</i>
                                        </th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 user-select-none" style="cursor: pointer; width: 140px;" onclick="sortTable(2)" title="Klik untuk mengurutkan Kelas">
                                            Kelas <i class="material-icons-round text-xxs align-middle" id="sortIcon2">unfold_more</i>
                                        </th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7" style="width: 170px;">Nilai (Predikat)</th>
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
                                        <tr class="baris-siswa" data-kelas="<?= htmlspecialchars($s['kelas'] ?? '') ?>" data-nama="<?= htmlspecialchars(strtolower($s['nama_siswa'] ?? '')) ?>" data-default-idx="<?= $no ?>" <?= $row_bg ?>>
                                            <td class="text-center text-sm cell-no"><?= $no++ ?></td>
                                            <td>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">
                                                        <?= htmlspecialchars($s['nama_siswa']) ?>
                                                        <?php if ($is_filled): ?>
                                                            <span class="badge badge-sm bg-gradient-success ms-2" style="font-size: 0.6rem;">
                                                                <i class="material-icons-round" style="font-size: 0.75rem;">check</i> Terisi
                                                            </span>
                                                        <?php endif; ?>
                                                    </h6>
                                                </div>
                                            </td>
                                            <td class="text-sm">
                                                <span class="badge badge-sm bg-gradient-light text-dark border">
                                                    <?= sec_e($s['kelas']) ?>
                                                </span>
                                            </td>
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
                                    <tr id="rowTidakDitemukan" style="display: none;">
                                        <td colspan="5" class="text-center py-4 text-secondary">
                                            <i class="material-icons-round d-block mb-1 opacity-5" style="font-size: 2rem;">search_off</i>
                                            Tidak ada data siswa yang cocok dengan filter.
                                        </td>
                                    </tr>
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

<script>
var sortDirections = {};

function filterDanHitung() {
    var filterEl = document.getElementById('filterKelas');
    var cariEl = document.getElementById('cariSiswa');
    var filterKls = filterEl ? (filterEl.value || '').toUpperCase().trim() : '';
    var cari = cariEl ? (cariEl.value || '').toUpperCase().trim() : '';
    var rows = document.querySelectorAll('#tabelNilaiEkstra tbody tr.baris-siswa');
    var emptyRow = document.getElementById('rowTidakDitemukan');
    
    var visibleCount = 0;
    
    rows.forEach(function(row) {
        var kls = (row.getAttribute('data-kelas') || '').toUpperCase();
        var nama = (row.getAttribute('data-nama') || '').toUpperCase();
        
        var matchKls = (filterKls === '' || kls === filterKls);
        var matchCari = (cari === '' || nama.indexOf(cari) > -1 || kls.indexOf(cari) > -1);
        
        if (matchKls && matchCari) {
            row.style.display = '';
            visibleCount++;
            var cellNo = row.querySelector('.cell-no');
            if (cellNo) cellNo.textContent = visibleCount;
        } else {
            row.style.display = 'none';
        }
    });
    
    var countBadge = document.getElementById('countTampil');
    if (countBadge) countBadge.textContent = visibleCount;
    
    if (emptyRow) {
        emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
    }
}

function sortTable(colIndex) {
    var table = document.getElementById('tabelNilaiEkstra');
    if (!table) return;
    var tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr.baris-siswa'));
    var currentDir = sortDirections[colIndex] || 'asc';
    var newDir = (currentDir === 'asc') ? 'desc' : 'asc';
    sortDirections[colIndex] = newDir;
    
    [0, 1, 2].forEach(function(i) {
        var icon = document.getElementById('sortIcon' + i);
        if (icon) icon.textContent = 'unfold_more';
    });
    var activeIcon = document.getElementById('sortIcon' + colIndex);
    if (activeIcon) {
        activeIcon.textContent = (newDir === 'asc') ? 'arrow_upward' : 'arrow_downward';
    }
    
    rows.sort(function(a, b) {
        if (colIndex === 0) {
            var valA = parseInt(a.getAttribute('data-default-idx') || '0', 10);
            var valB = parseInt(b.getAttribute('data-default-idx') || '0', 10);
            return (newDir === 'asc') ? (valA - valB) : (valB - valA);
        } else if (colIndex === 1) {
            var valA = (a.getAttribute('data-nama') || '').trim();
            var valB = (b.getAttribute('data-nama') || '').trim();
            var cmp = valA.localeCompare(valB, 'id', { numeric: true, sensitivity: 'base' });
            return (newDir === 'asc') ? cmp : -cmp;
        } else if (colIndex === 2) {
            var valA = (a.getAttribute('data-kelas') || '').trim();
            var valB = (b.getAttribute('data-kelas') || '').trim();
            var cmp = valA.localeCompare(valB, 'id', { numeric: true, sensitivity: 'base' });
            return (newDir === 'asc') ? cmp : -cmp;
        }
        return 0;
    });
    
    rows.forEach(function(r) {
        tbody.appendChild(r);
    });
    
    filterDanHitung();
}
</script>

<?php include '../app/views/layouts/footer.php'; ?>
