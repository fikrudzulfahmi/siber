<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Leger Nilai Siswa per Kelas</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="?controller=leger&method=index" method="POST">
                        <div class="row">
                            <!-- Tahun Pelajaran -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tahun_pelajaran" class="form-label">Tahun Pelajaran</label>
                                    <select id="tahun_pelajaran" name="id_tahun_pelajaran"
                                        class="form-control border focus-ring focus-ring-success rounded-3" required>
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($tahun_pelajaran_list as $thp): ?>
                                            <option value="<?= $thp['id_tahun_pelajaran'] ?>"
                                                <?= (isset($filter_terpilih['id_tahun_pelajaran']) && $filter_terpilih['id_tahun_pelajaran'] == $thp['id_tahun_pelajaran']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($thp['tahun_pelajaran'] . ' - ' . $thp['semester']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Kelas -->
                            <div class="col-md-6">
                                <!-- ✅ LOGIKA HAK AKSES BARU MENGGUNAKAN HELPER -->
                                <?php if (isAnyLevel($id_level, [1, 5])): // Admin & Kurikulum 
                                ?>
                                    <div class="mb-3">
                                        <label for="kelas" class="form-label">Kelas</label>
                                        <select id="kelas" name="id_kelas"
                                            class="form-control border focus-ring focus-ring-success rounded-3" required>
                                            <option value="">-- Pilih Kelas --</option>
                                            <?php foreach ($kelas_list as $k): ?>
                                                <option value="<?= $k['id_kelas'] ?>"
                                                    <?= (isset($filter_terpilih['id_kelas']) && $filter_terpilih['id_kelas'] == $k['id_kelas']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($k['kelas']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php elseif (isLevel($id_level, 3)): // Wali Kelas 
                                ?>
                                    <div class="mb-3">
                                        <label class="form-label">Kelas</label>
                                        <input type="text" class="form-control"
                                            value="<?= htmlspecialchars($kelas_walas['kelas'] ?? 'Anda bukan wali kelas') ?>" readonly>
                                        <!-- Wali kelas tidak perlu mengirim id_kelas karena sudah diambil otomatis di controller -->
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-table"></i> Tampilkan Leger
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <?php if (isset($leger_data) && !empty($leger_data['leger'])): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Leger Nilai Kelas: <?= htmlspecialchars($info_kelas['kelas']) ?></h5>
                            <?php $export_url = http_build_query($filter_terpilih); ?>
                            <a href="?controller=leger&method=exportExcel&<?= $export_url ?>" class="btn btn-dark" target="_blank">
                                <i class="fas fa-file-excel"></i>&nbsp; Export Excel
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center mb-0">
                                <thead class="table-success">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Siswa</th>
                                        <?php foreach ($leger_data['mapel_header'] as $mapel): ?>
                                            <th class="text-center" style="writing-mode: vertical-rl; text-orientation: mixed;"><?= htmlspecialchars($mapel) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1;
                                    foreach ($leger_data['leger'] as $data_siswa): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($data_siswa['nama_siswa']) ?></td>
                                            <?php foreach ($leger_data['mapel_header'] as $mapel): ?>
                                                <td class="text-center"><?= htmlspecialchars($data_siswa['nilai'][$mapel] ?? '-') ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <p class="text-center text-muted">Tidak ada data leger yang ditemukan untuk filter yang dipilih.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>