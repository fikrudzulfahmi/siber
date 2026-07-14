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
                        <h6 class="text-white text-capitalize ps-3 mb-0">Upload Perangkat Mengajar</h6>
                    </div>
                </div>

                <div class="card-body">
                    <form id="filter-form" class="row g-3 mb-4" autocomplete="off" novalidate>
                        <div class="col-md-3">
                            <label for="tahun_pelajaran" class="form-label fw-semibold">Tahun Pelajaran</label>
                            <select id="tahun_pelajaran" class="form-select" required>
                                <?php foreach ($tahun_pelajaran as $tp): ?>
                                    <option value="<?= htmlspecialchars($tp['id_tahun_pelajaran']) ?>">
                                        <?= htmlspecialchars($tp['tahun_pelajaran'] . ' - ' . $tp['semester']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="guru" class="form-label fw-semibold">Guru</label>
                            <select id="guru" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach ($guru_list as $g): ?>
                                    <option value="<?= htmlspecialchars($g['id_employe']) ?>"><?= htmlspecialchars($g['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="mapel_guru" class="form-label fw-semibold">Mapel & Tingkat</label>
                            <select id="mapel_guru" class="form-select" disabled required>
                                <option value="">-- Pilih Mapel & Tingkat --</option>
                            </select>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-items-center mb-0"" id="table-perangkat">
                            <thead>
                                <tr>
                                    <th>Jenis Perangkat</th>
                                    <th>File</th>
                                    <th>Status Approval</th>
                                    <th>Aksi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        Pilih filter untuk melihat data perangkat
                                    </td>
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
    const tbody = document.querySelector('#table-perangkat tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Pilih filter untuk melihat data perangkat</td></tr>';
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// -------------------------
// Change Tahun Pelajaran
// -------------------------
document.getElementById('tahun_pelajaran').addEventListener('change', function() {
    document.getElementById('guru').value = '';
    const mapelSelect = document.getElementById('mapel_guru');
    mapelSelect.innerHTML = '<option value="">-- Pilih Mapel & Tingkat --</option>';
    mapelSelect.disabled = true;
    clearTable();
});

// -------------------------
// Change Guru → ambil mapel
// -------------------------
document.getElementById('guru').addEventListener('change', function() {
    const idGuru = this.value;
    const idTahun = document.getElementById('tahun_pelajaran').value;
    const mapelSelect = document.getElementById('mapel_guru');

    mapelSelect.innerHTML = '<option>Loading...</option>';
    mapelSelect.disabled = true;

    if (!idGuru) {
        mapelSelect.innerHTML = '<option value="">-- Pilih Mapel & Tingkat --</option>';
        mapelSelect.disabled = true;
        clearTable();
        return;
    }

    fetch(`index.php?controller=verifikasi&method=getMapelByGuru&id_guru=${idGuru}&id_tahun=${idTahun}`)
        .then(res => res.json())
        .then(data => {
            mapelSelect.innerHTML = '<option value="">-- Pilih Mapel & Tingkat --</option>';
            data.forEach(mg => {
                const option = document.createElement('option');
                option.value = mg.id_mapel_guru;
                option.textContent = `${mg.nama_mapel} - ${mg.tingkat}`;
                mapelSelect.appendChild(option);
            });
            mapelSelect.disabled = false;
            clearTable();
        })
        .catch(() => {
            mapelSelect.innerHTML = '<option value="">-- Pilih Mapel & Tingkat --</option>';
            mapelSelect.disabled = true;
            clearTable();
        });
});

// -------------------------
// Change Mapel → ambil perangkat
// -------------------------
document.getElementById('mapel_guru').addEventListener('change', function() {
    const idMapelGuru = this.value;
    const idGuru = document.getElementById('guru').value;
    const idTahun = document.getElementById('tahun_pelajaran').value;
    const tbody = document.querySelector('#table-perangkat tbody');

    if (!idMapelGuru) {
        clearTable();
        return;
    }

    fetch(`index.php?controller=verifikasi&method=getPerangkat&id_guru=${idGuru}&id_tahun=${idTahun}&id_mapel_guru=${idMapelGuru}`)
        .then(res => res.json())
        .then(data => {
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Data perangkat tidak ditemukan.</td></tr>';
                return;
            }

            data.forEach(item => {
                const statusApproval = (item.status_approval || '').trim().toLowerCase();
                const fileCell = item.file
                    ? `<a href="uploads/perangkat/${item.file}" target="_blank" rel="noopener noreferrer" class="badge bg-success text-decoration-none">Lihat File</a>`
                    : '<span class="badge bg-secondary">Belum Upload</span>';

                const statusText = statusApproval === 'belum upload'
                    ? 'Belum Upload'
                    : capitalize(statusApproval);

                const selectDisabled = statusApproval === 'belum upload' ? 'disabled' : '';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.jenis_perangkat}</td>
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
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data perangkat.</td></tr>';
        });
});

// -------------------------
// Event Delegation utk update
// -------------------------
document.querySelector('#table-perangkat tbody').addEventListener('change', function(e) {
    if (e.target.classList.contains('status-select')) {
        const id = e.target.dataset.id;
        const status = e.target.value;

        fetch('index.php?controller=verifikasi&method=updateStatus', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`
        })
        .then(res => res.json())
        .then(resp => console.log(resp))
        .catch(() => console.error('Gagal update status'));
    }
});

document.querySelector('#table-perangkat tbody').addEventListener('blur', function(e) {
    if (e.target.classList.contains('catatan-input')) {
        const id = e.target.dataset.id;
        const catatan = e.target.value;

        fetch('index.php?controller=verifikasi&method=updateCatatan', {
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
