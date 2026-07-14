<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white ps-3">Rekap Perangkat Mengajar</h6>
                    </div>
                </div>
                <div class="card-body px-4 py-4">

                    <!-- Filter -->
<form method="GET">
    <input type="hidden" name="controller" value="rekat">
    <input type="hidden" name="method" value="index">

    <div class="row align-items-end">
        <div class="col-md-4 col-6 mb-3">
            <label>Tahun Pelajaran</label>
            <select name="tahun_pelajaran" class="form-control border" required>
                <option value="">-- Pilih Tahun Pelajaran --</option>
                <?php foreach ($tahun_pelajaran as $tp): ?>
                    <option value="<?= htmlspecialchars($tp['id_tahun_pelajaran']) ?>"
                        <?= (isset($id_tahun) && $id_tahun == $tp['id_tahun_pelajaran']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tp['tahun_pelajaran'] . ' - Semester ' . $tp['semester']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-6 mb-1">
            <button type="submit" class="btn bg-dark text-white w-100">
                Lihat
            </button>
        </div>
    </div>
</form>


                    <!-- Table -->
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>Nama Guru</th>
                                    <th>Mapel - Tingkat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($rekap as $guru): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($guru['nama']) ?></td>
                                        <td>
                                            <?php foreach ($guru['mapel_badges'] as $badge): ?>
<span class="badge rounded-pill <?= ($badge['terupload'] >= 1) ? 'bg-dark' : 'bg-secondary' ?> mb-1"
    style="cursor:pointer;"
    data-bs-toggle="modal"
    data-bs-target="#modalDetail"
    data-id-tahun="<?= htmlspecialchars($id_tahun ?? '') ?>"
    data-id-guru="<?= htmlspecialchars($guru['id_employe'] ?? '') ?>"
    data-id-mapel="<?= htmlspecialchars($badge['id_mapel'] ?? '') ?>"
    data-tingkat="<?= htmlspecialchars($badge['tingkat'] ?? '') ?>">
    <?= htmlspecialchars($badge['nama_mapel']) ?> - <?= htmlspecialchars($badge['tingkat']) ?> 
    <?= $badge['terupload'] ?>/<?= $badge['total_perangkat'] ?>
</span>

                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-gradient-success text-white">
        <h5 class="modal-title" id="modalDetailLabel" style="color: white;">Detail Perangkat</h5>
        <button type="button" class="btn-close" style="color: white;" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalContent">
          <table class="table table-bordered table-striped align-items-center mb-0">
            <thead>
              <tr>
                <th>Jenis Perangkat</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <!-- Data akan diisi lewat JS -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$('#modalDetail').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var idTahun = button.data('id-tahun');
    var idGuru = button.data('id-guru');
    var idMapel = button.data('id-mapel');
    var tingkat = button.data('tingkat');

    var modal = $(this);
    var tbody = modal.find('tbody');
    tbody.html('<tr><td colspan="2" class="text-center text-muted">Memuat data...</td></tr>');

$.getJSON('index.php?controller=rekat&method=getDetail', {
    id_tahun: idTahun,
    id_guru: idGuru,
    id_mapel: idMapel,
    tingkat: tingkat
}, function(data) {
    tbody.empty();
    if (data.length > 0) {
        data.forEach(function(item) {
            var badgeClass = item.status === 'Sudah Upload' ? 'badge bg-success' : 'badge bg-secondary';
            tbody.append('<tr><td>'+item.nama_perangkat+'</td><td><span class="'+badgeClass+'">'+item.status+'</span></td></tr>');
        });
    } else {
        tbody.append('<tr><td colspan="2" class="text-center text-muted">Tidak ada data</td></tr>');
    }
});

});
</script>

<?php include '../app/views/layouts/footer.php'; ?>
