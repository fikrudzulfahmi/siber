<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#4caf50',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php endif; ?>
    <div class="row mb-3">
        <div class="col-12">
            <a href="?controller=ekstra&method=index" class="btn btn-sm btn-outline-secondary shadow-none">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Ekstra
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white ps-3 mb-0"><i class="material-icons text-sm">groups</i> Tim Pembina & Pendamping</h6>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Pembina</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Peran</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex px-3 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($ekstra['nama_pengampu']) ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-sm bg-gradient-primary">Koordinator</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <small class="text-secondary">Utama</small>
                                    </td>
                                </tr>

                                <?php if (!empty($pendamping_aktif)): foreach ($pendamping_aktif as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-3 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($p['nama']) ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-sm border border-info text-info">Pendamping</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a href="?controller=ekstra&method=hapusPendampingSatu&id_p=<?= $p['id_pendamping'] ?>&id_ekstra=<?= $id_ekstra ?>"
                                                    class="text-danger font-weight-bold text-xs"
                                                    onclick="return confirm('Hapus pendamping ini?')">
                                                    Hapus
                                                </a>
                                            </td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr class="horizontal dark">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white ps-3 mb-0">Anggota <?= htmlspecialchars($ekstra['nama_ekstra']) ?></h6>
                        <span class="badge bg-light text-dark me-3"><?= count($anggota) ?> Siswa</span>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-0" style="max-height: 450px;">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Siswa</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kelas</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php if (!empty($anggota)): foreach ($anggota as $ang): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td class="ps-4">
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($ang['nama_siswa']) ?></h6>
                                                </div>
                                            </td>
                                            <td><span class="text-xs font-weight-bold"><?= htmlspecialchars($ang['kelas']) ?></span></td>
                                            <td class="text-center">
                                                <a href="?controller=ekstra&method=hapusAnggota&id_anggota=<?= $ang['id_ekstra_anggota'] ?>&id_ekstra=<?= $id_ekstra ?>"
                                                    onclick="return confirm('Keluarkan siswa ini?')" class="btn btn-link text-danger mb-0 px-2">
                                                    <i class="material-icons text-sm">person_remove</i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-xs text-secondary">Belum ada anggota terdaftar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3 mb-0">Setting Tim & Anggota</h6>
                    </div>
                </div>
                <div class="card-body">
                    <form action="?controller=ekstra&method=simpanAnggotaTerpadu" method="POST">
                        <input type="hidden" name="id_ekstra" value="<?= $id_ekstra ?>">

                        <div class="mb-4">
                            <h6 class="text-sm font-weight-bold"><i class="fas fa-chalkboard-teacher me-2"></i>Guru Pendamping</h6>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px;">
                                <table class="table table-sm align-items-center mb-0">
                                    <tbody id="tableGuru">
                                        <?php if (!empty($guru_tersedia)): foreach ($guru_tersedia as $gt): ?>
                                                <?php
                                                if ($gt['id_employe'] == $ekstra['id_guru_pengampu']) continue;
                                                $list_pendamping = $id_pendamping_sekarang ?? [];
                                                $is_pendamping = in_array($gt['id_employe'], $list_pendamping) ? 'checked' : '';
                                                ?>
                                                <tr>
                                                    <td class="ps-3" style="width: 15%">
                                                        <input type="checkbox" name="pilih_guru[]" value="<?= $gt['id_employe'] ?>" <?= $is_pendamping ?>>
                                                    </td>
                                                    <td>
                                                        <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($gt['nama']) ?></p>
                                                    </td>
                                                </tr>
                                        <?php endforeach;
                                        endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="horizontal dark">

                        <div class="mb-3">
                            <h6 class="text-sm font-weight-bold"><i class="fas fa-user-graduate me-2"></i>Siswa Anggota</h6>

                            <div class="row px-2 mb-2">
                                <div class="col-6 px-1">
                                    <select id="filterKelas" class="form-select form-select-sm border px-2 w-100" style="background-color: #f8f9fa;" onchange="filterSiswa()">
                                        <option value="">-- Semua Kelas --</option>
                                        <?php
                                        $kelas_unik = [];
                                        if (!empty($siswa_tersedia)) {
                                            $kelas_unik = array_unique(array_column($siswa_tersedia, 'kelas'));
                                            sort($kelas_unik);
                                            foreach ($kelas_unik as $k) {
                                                echo '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($k) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-6 px-1">
                                    <div class="input-group input-group-outline w-100">
                                        <input type="text" id="searchInput" placeholder="Cari nama..." class="form-control form-control-sm" onkeyup="filterSiswa()">
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px;">
                                <table class="table table-sm align-items-center mb-0" id="tableSiswaTersedia">
                                    <thead class="bg-light position-sticky top-0" style="z-index: 1;">
                                        <tr>
                                            <th class="ps-3" style="width: 15%"><input type="checkbox" id="checkAll"></th>
                                            <th class="text-xxs font-weight-bolder opacity-7">Pilih Semua <span id="labelCheckAll"></span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($siswa_tersedia as $st): ?>
                                            <tr class="siswa-row" data-kelas="<?= htmlspecialchars($st['kelas']) ?>">
                                                <td class="ps-3">
                                                    <input type="checkbox" name="pilih_siswa[]" value="<?= $st['id_ploting'] ?>" class="checkItem">
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0 nama-siswa"><?= htmlspecialchars($st['nama_siswa']) ?></p>
                                                    <p class="text-xxs text-secondary mb-0"><?= htmlspecialchars($st['kelas']) ?></p>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-info w-100 mb-0">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pencarian Instan Terpadu (Nama + Kelas)
    function filterSiswa() {
        let inputName = document.getElementById("searchInput").value.toUpperCase();
        let inputKelas = document.getElementById("filterKelas").value.toUpperCase();
        let rows = document.getElementsByClassName("siswa-row");

        for (let i = 0; i < rows.length; i++) {
            let namaElement = rows[i].getElementsByClassName("nama-siswa")[0];
            let kelasData = rows[i].getAttribute("data-kelas").toUpperCase();

            if (namaElement) {
                let txtName = namaElement.textContent || namaElement.innerText;

                // Cek apakah baris ini cocok dengan input Nama dan input Kelas
                let matchName = txtName.toUpperCase().indexOf(inputName) > -1;
                let matchKelas = (inputKelas === "" || kelasData === inputKelas);

                if (matchName && matchKelas) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    }

    // Check All yang HANYA memilih hasil filter yang sedang terlihat di layar
    document.getElementById('checkAll').onclick = function() {
        let checkboxes = document.getElementsByClassName('checkItem');
        for (let checkbox of checkboxes) {
            // Hanya centang checkbox jika barisnya tidak disembunyikan (display !== 'none')
            if (checkbox.closest('tr').style.display !== 'none') {
                checkbox.checked = this.checked;
            }
        }
    }
</script>

<?php include '../app/views/layouts/footer.php'; ?>