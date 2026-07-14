<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            Program Struktural
                        </h6>
                    </div>
                </div>

                <div class="card-body px-4 pb-2">

<?php if (!empty($levelText)): ?>
<div class="alert alert-light">
    Halo, <strong><?= htmlspecialchars($namaUser) ?></strong> segera lengkapi program struktural anda sebagai <strong><?= htmlspecialchars($levelText) ?></strong>.
</div>
<?php endif; ?>

                    <?php if ($tahun): ?>
                        <div class="mb-3">
                            <label class="form-label">Tahun Pelajaran Aktif</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($tahun['tahun_pelajaran'] . ' - ' . $tahun['semester']) ?>"
                                   readonly>
                        </div>

                        <form action="index.php?controller=programStruktural&method=upload"
                              method="POST" enctype="multipart/form-data">

                            <input type="hidden" name="id_tahun_pelajaran"
                                   value="<?= $tahun['id_tahun_pelajaran'] ?>">

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Jenis Program</th>
                                            <th>Upload File</th>
                                            <th>File</th>
                                            <th>Status</th>
                                            <th>Terlambat</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    <?php
                                    // mapping file yang sudah diupload
                                    $uploaded = [];
                                    foreach ($program as $p) {
                                        $uploaded[$p['jenis_program']] = $p;
                                    }
                                    ?>

                                    <?php foreach ($jenis_program as $i => $jenis): ?>
<tr>
    <td><?= $i + 1 ?></td>
    <td><?= htmlspecialchars($jenis) ?> <?php if (
    isset($uploaded[$jenis]) &&
    strtolower($uploaded[$jenis]['status_approval']) === 'ditolak'
): ?>
    <small class="text-danger ms-2">Silahkan upload ulang.</small>
<?php endif; ?></td>

    <td>
        <input type="file"
               name="program[<?= htmlspecialchars($jenis) ?>]"
               class="form-control form-control-sm"
               accept=".pdf,.doc,.docx">

    </td>


    <td>
        <?php if (isset($uploaded[$jenis])): ?>
            <a href="uploads/program_struktural/<?= $uploaded[$jenis]['file'] ?>" target="_blank" class="badge bg-success">Lihat File</a>
        <?php else: ?>
            <span class="badge bg-secondary">Belum ada</span>
        <?php endif; ?>
    </td>

    <td>
        <?php
        $status = $uploaded[$jenis]['status_approval'] ?? 'Belum dikirim';
        $badge = match ($status) {
            'Disetujui' => 'success',
            'Ditolak' => 'danger',
            'Pending' => 'warning',
            default => 'secondary'
        };
        ?>
        <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
    </td>

    <td>
        <?php
        $uploadDate = $uploaded[$jenis]['tanggal_upload'] ?? null;
        $deadline = $deadlines[$jenis] ?? null;

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
        ?>
    </td>
<style>
                            .badge.bg-success:hover {
                                background-color: #0ad174ff !important;
                                color: #fff;
                                cursor: pointer;
                            }
                        </style>
    <td>
        <?= htmlspecialchars($uploaded[$jenis]['catatan'] ?? '-') ?>
    </td>
</tr>
<?php endforeach; ?>

                                    

                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-dark mt-3">
                                Upload Semua
                            </button>
                        </form>

                    <?php else: ?>
                        <div class="alert alert-danger">
                            Tidak ada tahun pelajaran aktif.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>
