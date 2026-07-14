
<div class="container-fluid py-4">
  <!-- Selamat Datang Card -->
  <div class="row">
    <div class="col-12 mb-4">
      <div class="card bg-gradient-success shadow-success border-radius-lg">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h5 class="text-white mb-0">
              Selamat Datang, <strong><?= $_SESSION['user']['nama'] ?? 'Pengguna' ?></strong>
            </h5>
            <p class="text-white mb-0">
    Anda login sebagai <strong><?= levelDisplay($_SESSION['user']['level']) ?></strong>.
</p>
          </div>
          <div>
            <i class="material-icons text-white opacity-10" style="font-size: 48px;">waving_hand</i>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                    <h6 class="text-white text-capitalize ps-3">Pengingat Deadline Perangkat</h6>
                </div>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($deadlineList)): ?>
                    <p class="text-sm text-muted mb-0">Tidak ada data deadline.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($deadlineList as $dl): ?>
                            <?php 
                                $itemClass = match($dl['status']) {
                                    'lewat'   => 'list-group-item-danger',
                                    'dekat'   => 'list-group-item-warning',
                                    'selesai' => 'list-group-item-success',
                                    default   => ''
                                };
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center <?= $itemClass ?>">
<div>
    <strong><?= htmlspecialchars($dl['jenis_perangkat']) ?></strong>
    <?php if ($dl['status'] == 'ditolak'): ?>
        <small class="text-danger ms-2">Silahkan upload ulang perangkat.</small>
    <?php endif; ?>
    <br>
    <small class="text-muted">
        Deadline: <?= date('d/m/Y', strtotime($dl['tanggal_deadline'])) ?>
    </small>
</div>

                                <div>
                                   <?php if ($dl['status'] == 'selesai'): ?>
    <span class="badge bg-success">Selesai</span>
<?php elseif ($dl['status'] == 'lewat'): ?>
    <span class="badge bg-danger">Lewat</span>
<?php elseif ($dl['status'] == 'dekat'): ?>
    <span class="badge bg-warning"><?= $dl['sisa_hari'] ?> hari lagi</span>
<?php elseif ($dl['status'] == 'ditolak'): ?>
    <span class="badge bg-danger">Ditolak</span>
<?php else: ?>
    <span class="badge bg-secondary">Aman</span>
<?php endif; ?>

                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($deadlineProgram) && $showProgramDeadline): ?>
<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                    <h6 class="text-white text-capitalize ps-3">Pengingat Deadline Program Struktural</h6>
                </div>
            </div>
            <div class="card-body px-4 py-3">
                <ul class="list-group list-group-flush">
                    <?php foreach($deadlineProgram as $dp): ?>
                        <?php
                        $itemClass = match($dp['status']) {
                            'selesai' => 'list-group-item-success',
                            'lewat'   => 'list-group-item-danger',
                            'ditolak' => 'list-group-item-danger',
                            default   => 'list-group-item-warning'
                        };
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center <?= $itemClass ?>">
                            <div>
                                <strong><?= htmlspecialchars($dp['jenis_program']) ?></strong>
                                <?php if($dp['status'] == 'lewat'): ?>
                                    <small class="text-danger ms-2">Segera upload!</small>
                                <?php elseif($dp['status'] == 'ditolak'): ?>
                                    <small class="text-danger ms-2">Silahkan upload ulang program.</small>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted">
Deadline: <?= date('d/m/Y', strtotime($dp['deadline'])) ?>
                                </small>
                            </div>
                            <div>
                                <?php if($dp['status'] == 'selesai'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                <?php elseif($dp['status'] == 'lewat'): ?>
                                    <span class="badge bg-danger">Lewat</span>
                                <?php elseif($dp['status'] == 'ditolak'): ?>
                                    <span class="badge bg-danger">Ditolak</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?= $dp['sisa_hari'] ?> hari lagi</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
