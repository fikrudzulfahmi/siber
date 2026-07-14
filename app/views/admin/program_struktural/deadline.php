<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Pengaturan Deadline Program Struktural</h6>
                    </div>
                </div>
                <div class="card-body px-4 pb-2">
                    <label for="tahunSelect">Pilih Tahun Pelajaran</label>
                    <select id="tahunSelect" class="form-select mb-3">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($tahun_all as $tp) : ?>
                            <option value="<?= $tp['id_tahun_pelajaran'] ?>"><?= htmlspecialchars($tp['tahun_pelajaran']) ?> - <?= htmlspecialchars($tp['semester']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-items-center mb-0" id="deadlineTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Jenis Program</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="3" class="text-center">Silakan pilih tahun pelajaran</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('tahunSelect').addEventListener('change', function() {
    let idTahun = this.value;
    if (!idTahun) return;

    fetch(`?controller=DeadlineProgramStruktural&method=getByTahun&id_tahun_pelajaran=${idTahun}`)
        .then(res => res.json())
        .then(res => {
            let tbody = document.querySelector("#deadlineTable tbody");
            tbody.innerHTML = "";
            if (res.success && res.data.length > 0) {
                res.data.forEach((item, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${i+1}</td>
                            <td>${item.jenis_program}</td>
                            <td>
                                <input type="date" class="form-select w-auto" style="min-width: 150px;" 
                                value="${item.tanggal_deadline || ''}" 
                                onchange="saveDeadline(${item.id_deadline !== null ? item.id_deadline : 'null'}, '${item.jenis_program}', this.value)" />
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center">Tidak ada data</td></tr>`;
            }
        });
});

function saveDeadline(id, jenis, tanggal) {
    let idTahun = document.getElementById('tahunSelect').value;
    let isUpdate = (id !== null && id !== "null" && id !== undefined);
    let url = isUpdate ? 
        `?controller=DeadlineProgramStruktural&method=update` : 
        `?controller=DeadlineProgramStruktural&method=storeSingle`;

    let bodyData = isUpdate ? 
        `id_deadline=${id}&tanggal_deadline=${tanggal}` : 
        `id_tahun_pelajaran=${idTahun}&jenis_program=${encodeURIComponent(jenis)}&tanggal_deadline=${tanggal}`;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: bodyData
    })
    .then(res => res.json())
    .then(res => {
        Swal.fire({
            icon: res.success ? 'success' : 'error',
            title: res.success ? (isUpdate ? 'Berhasil diperbarui' : 'Berhasil ditambahkan') : 'Gagal',
            text: res.message || 'Deadline berhasil disimpan',
            timer: 1500,
            showConfirmButton: false
        });
        if (!isUpdate && res.success) {
            document.getElementById('tahunSelect').dispatchEvent(new Event('change'));
        }
    });
}
</script>

<?php include '../app/views/layouts/footer.php'; ?>
