<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

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
<?php if ($msg = getFlash('warning')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Gagal!',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#4caf50',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>
<?php if ($msg = getFlash('danger')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'danger',
                title: 'Gagal',
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#4caf50',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>

<style>
    /* Perbaiki lebar form control agar tidak melebihi parent */
    .form-control,
    .select2-container {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }

    /* Hapus overflow horizontal di container utama */
    body,
    html {
        overflow-x: hidden;
    }

    /* Pastikan form container padding responsif */
    .card-body {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* Responsif untuk padding dan layout */
    @media (max-width: 768px) {
        .row {
            flex-direction: column;
        }

        .col-md-4 {
            width: 100%;
        }

        .select2-container {
            width: 100% !important;
        }
    }

    /* Custom select2 tampilan tetap */
    .form-control {
        padding-left: 1rem !important;
    }

    .select2-results__option {
        padding-left: 1.5rem !important;
        position: relative;
    }

    .select2-results__option::before {
        content: "☐";
        position: absolute;
        left: 0.5rem;
        color: #aaa;
    }

    .select2-results__option--selected::before {
        content: "☑";
        color: #28a745;
    }
</style>

<style>
    .form-check-custom input[type="checkbox"]:checked~.form-check-label .checked-icon {
        display: inline-block !important;
    }

    .form-check-custom input[type="checkbox"]:checked~.form-check-label .unchecked-icon {
        display: none !important;
    }

    .form-check-custom .mdi {
        font-size: 1.2rem;
        top: 0.1rem;
    }
</style>

<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Rekapitulasi Tujuan Pembelajaran (TP)</h6>
                        <p class="text-white text-sm ps-3 mb-0">
                            Tahun Pelajaran: <strong><?= $tahun_info['tahun_pelajaran'] ?></strong> |
                            Semester: <strong><?= $tahun_info['semester'] ?></strong>
                        </p>
                    </div>
                </div>
                <div class="card-body px-4 py-4">
                    <form method="POST" class="mb-4">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="id_guru" class="form-label">Filter Berdasarkan Guru</label>
                                <select name="id_guru" id="id_guru" class="form-control">
                                    <option value="semua">-- Tampilkan Semua Guru --</option>
                                    <?php foreach ($daftar_guru as $guru): ?>
                                        <option value="<?= $guru['id_employe'] ?>" <?= ($guru['id_employe'] == $id_guru_terpilih) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($guru['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn bg-gradient-success text-white mb-0">Tampilkan</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th style="white-space: normal; word-wrap: break-word; max-width: 200px;">Nama Guru</th>
                                    <th style="white-space: normal; word-wrap: break-word; max-width: 200px;">Mata Pelajaran & Kelas</th>
                                    <th class="text-center">Jumlah TP</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rekap_tp)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $no = 1;
                                    $currentGuru = "";
                                    foreach ($rekap_tp as $row):
                                        // Logika untuk menggabungkan baris berdasarkan nama guru
                                        if ($currentGuru != $row['nama_guru']) {
                                            $guruRows = array_filter($rekap_tp, function ($item) use ($row) {
                                                return $item['nama_guru'] == $row['nama_guru'];
                                            });
                                            $rowspan = count($guruRows);
                                            echo "<tr>";
                                            echo "<td class='text-center align-middle' rowspan='{$rowspan}'>{$no}</td>";
                                            echo "<td class='align-middle' rowspan='{$rowspan}' style='white-space: normal; word-wrap: break-word; max-width: 200px;'> " . htmlspecialchars($row['nama_guru']) . "</td>";
                                            $currentGuru = $row['nama_guru'];
                                            $no++;
                                        } else {
                                            echo "<tr>"; // Baris baru untuk mapel berikutnya dari guru yang sama
                                        }
                                    ?>
                                        <td style="white-space: normal; word-wrap: break-word; max-width: 200px;"><?= htmlspecialchars($row['nama_mapel']) ?>
                                            <span class="text-muted"> - Kelas <?= htmlspecialchars($row['nama_kelas']) ?></span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge bg-dark"><?= htmlspecialchars($row['jumlah_tp']) ?> TP</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button class="btn btn-sm btn-success mb-0 btn-lihat-tp" data-id="<?= $row['id_mapel_guru'] ?>" data-mapel="<?= htmlspecialchars($row['nama_mapel']) ?>">
                                                <i class="fas fa-eye"></i>&nbsp; Lihat TP
                                            </button>
                                        </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Inisialisasi Select2 pada dropdown guru
        $('#id_guru').select2({
            theme: 'bootstrap-5'
        });
    });


    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-lihat-tp').forEach(button => {
            button.addEventListener('click', function() {
                const idMapelGuru = this.dataset.id;
                const namaMapel = this.dataset.mapel;

                // Tampilkan loading spinner
                Swal.fire({
                    title: 'Memuat Data...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Ambil data TP dari server via Fetch API (AJAX)
                fetch(`?controller=tp&method=getDetail&id=${idMapelGuru}`)
                    .then(response => response.json())
                    .then(data => {
                        let contentHtml = '';
                        if (data.length > 0) {
                            contentHtml = '<ol style="text-align: left; padding-left: 20px;">';
                            data.forEach(tp => {
                                contentHtml += `<li>${escapeHtml(tp.tujuan_pembelajaran)}</li>`;
                            });
                            contentHtml += '</ol>';
                        } else {
                            contentHtml = '<p class="text-muted"><i>Belum ada Tujuan Pembelajaran yang ditambahkan untuk mapel ini.</i></p>';
                        }

                        // Tampilkan hasil di SweetAlert
                        Swal.fire({
                            title: `TP untuk ${namaMapel}`,
                            html: contentHtml,
                            icon: 'info',
                            confirmButtonText: 'Tutup'
                        });
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Gagal mengambil data dari server.', 'error');
                    });
            });
        });

        // Fungsi untuk mencegah XSS
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    });
</script>



<?php include '../app/views/layouts/footer.php'; ?>