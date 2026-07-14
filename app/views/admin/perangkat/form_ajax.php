<form action="index.php?controller=perangkat&method=upload" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id_tahun_pelajaran" value="<?= $id_tahun ?>">
    <input type="hidden" name="id_mapel_guru" value="<?= $id_mapel_guru ?>">
    <input type="hidden" name="tingkat" id="tingkat_input">


    <div class="table-responsive">
        <table class="table table-bordered table-striped align-items-center mb-0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis</th>
                    <th>Upload File</th>
                    <th>Lihat Perangkat</th>
                    <th>Status</th>
                    <th>Terlambat</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $uploadedFiles = [];
foreach ($perangkat as $item) {
    $uploadedFiles[$item['jenis_perangkat']] = [
        'file_name' => $item['file'],
        'status' => $item['status_approval'],
        'tanggal_upload' => $item['tanggal_upload'],
        'tanggal_deadline' => $item['tanggal_deadline'],
        'catatan' => $item['catatan'] ?? null,  // Pastikan catatan ada, atau null jika tidak ada
    ];
}


                foreach ($jenis_perangkat as $index => $jenis):
                ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <?= htmlspecialchars($jenis) ?>
                            <?php if (isset($uploadedFiles[$jenis]) && strtolower($uploadedFiles[$jenis]['status']) === 'ditolak'): ?>
                                <small class="text-danger ms-2">Silahkan upload ulang.</small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <input type="file" class="form-control form-control-sm" name="perangkat[<?= htmlspecialchars($jenis) ?>]" accept=".pdf,.doc,.docx">
                        </td>
                        <td>
                            <?php if (!isset($uploadedFiles[$jenis])): ?>
                                <span class="badge bg-secondary">Tidak ada</span>
                            <?php else: ?>
                                <a href="uploads/perangkat/<?= $uploadedFiles[$jenis]['file_name'] ?>" class="badge bg-success" target="_blank">
                                    Lihat
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if (isset($uploadedFiles[$jenis])) {
                                $status = $uploadedFiles[$jenis]['status'];
                                $badgeClass = 'secondary'; // default

                                if ($status == 'Disetujui') {
                                    $badgeClass = 'success';
                                } elseif ($status == 'Pending') {
                                    $badgeClass = 'warning';
                                } else {
                                    $badgeClass = 'danger'; // misalnya Ditolak atau lainnya
                                }
                            } else {
                                $status = 'Belum dikirim';
                                $badgeClass = 'secondary';
                            }
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </td>

                        <style>
                            .badge.bg-success:hover {
                                background-color: #0ad174ff !important;
                                color: #fff;
                                cursor: pointer;
                            }
                        </style>

                        <td>
                            <?php
                            if (!isset($uploadedFiles[$jenis])) {
                                echo '<span class="badge bg-secondary">-</span>';
                            } else {
                                $uploadDate = $uploadedFiles[$jenis]['tanggal_upload'] ?? null;
                                $deadline = $uploadedFiles[$jenis]['tanggal_deadline'] ?? null;

                                if ($uploadDate && $deadline) {
                                    $uploadTime = strtotime($uploadDate);
                                    $deadlineTime = strtotime($deadline);

                                    if ($uploadTime <= $deadlineTime) {
                                        echo '<span class="badge bg-success">Tepat waktu</span>';
                                    } else {
                                        $diffDays = ceil(($uploadTime - $deadlineTime) / 86400);
                                        echo '<span class="badge bg-danger">Terlambat ' . $diffDays . ' hari</span>';
                                    }
                                } else {
                                    echo '<span class="badge bg-secondary">-</span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
    <?php if (isset($uploadedFiles[$jenis]['catatan'])): ?>
        <?= htmlspecialchars($uploadedFiles[$jenis]['catatan']) ?>
    <?php else: ?>
        <span class="text-muted">-</span>
    <?php endif; ?>
</td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="submit" class="btn btn-dark mt-3">Upload Semua</button>
</form>