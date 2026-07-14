<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3">Program Kerja</h6>
                    </div>
                </div>

                <div class="card-body">

                    <!-- FILTER USER -->
                    <form method="get" class="row mb-3">
                        <input type="hidden" name="controller" value="programKerja">
                        <input type="hidden" name="method" value="indexAdmin">

                        <div class="col-md-4">
                            <select name="user" class="form-control border focus-ring focus-ring-success rounded-3">
                                <option value="">-- Semua User --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id_employe']; ?>"
                                        <?= ($_GET['user'] ?? '') == $u['id_employe'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($u['nama']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary">Filter</button>
                        </div>
                    </form>

                    <!-- TABEL -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama User</th>
                                    <th>Program Kerja</th>
                                    <th>SOP Program Kerja</th>
                                    <th>Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($programKerja)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Data tidak tersedia</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($programKerja as $i => $pk): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= htmlspecialchars($pk['nama']) ?></td>
                                            <td><?= htmlspecialchars($pk['nama_program']) ?></td>
                                            <td><?= nl2br(htmlspecialchars($pk['deskripsi_default'])) ?></td>
                                            <td><?= date('d-m-Y', strtotime($pk['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


<?php include '../app/views/layouts/footer.php'; ?>