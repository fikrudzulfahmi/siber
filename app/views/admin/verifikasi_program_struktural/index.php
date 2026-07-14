<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
.badge.bg-success:hover {
    background-color: #0ad174ff !important;
    color: #fff;
    cursor: pointer;
}
.catatan-input {
    border: 1px solid #ced4da !important;
    background-color: #fff !important;
    border-radius: 2px;
    min-width: 180px;
    width: 100%;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4 shadow-sm">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Verifikasi Program Struktural</h6>
                    </div>
                </div>

                <div class="card-body">
                    <form id="filter-form" class="row g-3 mb-4" autocomplete="off" novalidate>
                        <div class="col-md-3">
                            <label for="tahun" class="form-label fw-semibold">Tahun Pelajaran</label>
                            <select id="tahun" class="form-select" required>
                                <?php foreach ($tahun_pelajaran as $tp): ?>
                                    <option value="<?= htmlspecialchars($tp['id_tahun_pelajaran']) ?>">
                                        <?= htmlspecialchars($tp['tahun_pelajaran'] . ' - ' . $tp['semester']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="pegawai" class="form-label fw-semibold">Pegawai</label>
                            <select id="pegawai" class="form-select" required>
    <option value="">-- Pilih Pegawai --</option>
    <?php foreach ($pegawai as $p): ?>
        <option value="<?= htmlspecialchars($p['id_employe']) ?>">
            <?= htmlspecialchars($p['nama'] . ' - ' . getLevelDisplayName($p['id_level'])) ?>
        </option>
    <?php endforeach; ?>
</select>

                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-items-center mb-0" id="table-program">
                            <thead>
                                <tr>
                                    <th>Jenis Program</th>
                                    <th>File</th>
                                    <th>Status Approval</th>
                                    <th>Aksi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Pilih filter untuk melihat data program</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div> <!-- card-body -->
            </div> <!-- card -->
        </div> <!-- col-12 -->
    </div> <!-- row -->
</div> <!-- container-fluid -->

<script>
function statusBadgeClass(status) {
    if (!status) return 'bg-secondary';
    switch (status.trim().toLowerCase()) {
        case 'disetujui': return 'bg-success';   
        case 'pending':   return 'bg-warning'; 
        case 'ditolak':   return 'bg-danger';    
        case 'belum upload': return 'bg-secondary'; 
        default: return 'bg-secondary';
    }
}

function clearTable() {
    const tbody = document.querySelector('#table-program tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Pilih filter untuk melihat data program</td></tr>';
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// -------------------------
// Change Tahun Pelajaran
// -------------------------
document.getElementById('tahun').addEventListener('change', function() {
    document.getElementById('pegawai').value = '';
    clearTable();
});

// -------------------------
// Change Pegawai → ambil program
// -------------------------
document.getElementById('pegawai').addEventListener('change', function() {
    const idUser = this.value;
    const idTahun = document.getElementById('tahun').value;
    const tbody = document.querySelector('#table-program tbody');

    if (!idUser) {
        clearTable();
        return;
    }

    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>';

fetch(`index.php?controller=verifikasiProgramStruktural&method=getProgram&id_user=${idUser}&id_tahun=${idTahun}`)
    .then(res => res.json())
    .then(resp => {
        const tbody = document.querySelector('#table-program tbody');
        tbody.innerHTML = '';

        if (!resp.success) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${resp.message}</td></tr>`;
            return;
        }

        const data = resp.data;
        if (!data || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Data program tidak ditemukan.</td></tr>';
            return;
        }

        data.forEach(item => {
            const statusApproval = (item.status_approval || '').trim().toLowerCase();
            const fileCell = item.file
                ? `<a href="uploads/program_struktural/${item.file}" target="_blank" rel="noopener noreferrer" class="badge bg-success text-decoration-none">Lihat File</a>`
                : '<span class="badge bg-secondary">Belum Upload</span>';

            const statusText = statusApproval === 'belum upload' ? 'Belum Upload' : capitalize(statusApproval);
            const selectDisabled = statusApproval === 'belum upload' ? 'disabled' : '';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.jenis_program}</td>
                <td>${fileCell}</td>
                <td><span class="badge ${statusBadgeClass(statusApproval)}">${statusText}</span></td>
                <td>
                    <select data-id="${item.id || ''}" class="form-select form-select-sm status-select" ${selectDisabled}>
                        <option value="pending" ${statusApproval === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="disetujui" ${statusApproval === 'disetujui' ? 'selected' : ''}>Disetujui</option>
                        <option value="ditolak" ${statusApproval === 'ditolak' ? 'selected' : ''}>Ditolak</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm catatan-input" 
                        data-id="${item.id || ''}" value="${item.catatan || ''}">
                </td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(() => {
        document.querySelector('#table-program tbody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data program.</td></tr>';
    });

});

// -------------------------
// Event Delegation utk update
// -------------------------
document.querySelector('#table-program tbody').addEventListener('change', function(e) {
    if (e.target.classList.contains('status-select')) {
        const id = e.target.dataset.id;
        const status = e.target.value;

        fetch('index.php?controller=verifikasiProgramStruktural&method=updateStatus', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
        })
        .then(res => res.json())
        .then(resp => console.log(resp))
        .catch(() => console.error('Gagal update status'));
    }
});

document.querySelector('#table-program tbody').addEventListener('blur', function(e) {
    if (e.target.classList.contains('catatan-input')) {
        const id = e.target.dataset.id;
        const catatan = e.target.value;

        fetch('index.php?controller=verifikasiProgramStruktural&method=updateCatatan', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${encodeURIComponent(id)}&catatan=${encodeURIComponent(catatan)}`
        })
        .then(res => res.json())
        .then(resp => console.log(resp))
        .catch(() => console.error('Gagal update catatan'));
    }
}, true);
</script>

<?php include '../app/views/layouts/footer.php'; ?>
