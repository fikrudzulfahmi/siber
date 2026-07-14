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

<div class="container-fluid py-4">

    <?php if ($msg = getFlash('success')): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Daftar Siswa</h6>
                    </div>
                </div>

                <div class="card-body px-4 pb-2">
                    <div class="d-flex justify-content-between flex-wrap align-items-center">

                        <?php if (!isLevel($id_level, 7)): ?>
                            <div class="d-flex gap-2">
                                <a href="?controller=siswa&method=create" class="btn btn-success">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Siswa
                                </a>
                                <a href="TemplateUpload.xlsx" class="btn btn-dark">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Download Template
                                </a>
                            </div>

                            <form action="?controller=siswa&method=uploadExcel"
                                method="POST" enctype="multipart/form-data"
                                class="d-flex flex-wrap align-items-center gap-2">

                                <label for="file_excel" class="form-label mb-0 fw-bold">
                                    Pilih File Excel:
                                </label>
                                <input type="file" class="form-control form-control-sm"
                                    name="file_excel" id="file_excel"
                                    accept=".xls,.xlsx" required
                                    style="max-width: 250px;">

                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-upload me-1"></i> Upload
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">

                    <div class="mb-3 p-4">
                        <label for="filter-kelas" class="form-label fw-bold">Filter Kelas:</label>
                        <select id="filter-kelas" class="form-select border px-2" style="max-width:250px;">
                            <option value="">-- Semua Kelas --</option>
                            <?php
                            // Gunakan 'nama_kelas' sesuai query Model terbaru
                            $list_kelas = array_column($siswa, 'nama_kelas');

                            // Hapus duplikat
                            $kelasUnik = array_unique($list_kelas);

                            // Hapus nilai kosong (jika ada siswa belum dapat kelas)
                            $kelasUnik = array_filter($kelasUnik);

                            sort($kelasUnik);
                            foreach ($kelasUnik as $k) {
                                echo "<option value='{$k}'>{$k}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="table-responsive p-4">
                        <table id="datatable2" class="table table-striped table-bordered align-items-center mb-0 text-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th>Nama Siswa</th>
                                    <th>NISN</th>
                                    <th>TTL</th>
                                    <th>Kelas (Tahun Aktif)</th>
                                    <th>Wali</th>
                                    <th>No. HP</th>
                                    <?php if (!isLevel($id_level, 7)): ?>
                                        <th class="text-center">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($siswa as $row): ?>
                                    <tr>
                                        <td class="text-center"></td>
                                        <td class="fw-bold"><?= $row['nama_siswa'] ?></td>
                                        <td><?= $row['nisn'] ?></td>
                                        <td><?= $row['tempat_lhr'] ?>, <?= date('d-m-Y', strtotime($row['tgl_lhr'])) ?></td>

                                        <td class="text-center">
                                            <?php if (!empty($row['nama_kelas'])): ?>
                                                <span class="badge bg-gradient-info"><?= $row['nama_kelas'] ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-secondary">Belum Bagi Kelas</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= $row['nama_wali'] ?></td>
                                        <td><a href="https://wa.me/<?= preg_replace('/^0/', '62', $row['hp_wali']) ?>" target="_blank" class="text-success fw-bold"><i class="fab fa-whatsapp"></i> <?= $row['hp_wali'] ?></a></td>

                                        <?php if (!isLevel($id_level, 7)): ?>
                                            <td class="text-center">
                                                <a href="?controller=siswa&method=edit&id=<?= $row['id_siswa'] ?>" class="btn btn-link text-dark px-2 mb-0">
                                                    <i class="fas fa-pencil-alt text-dark"></i>
                                                </a>
                                                <a href="?controller=siswa&method=delete&id=<?= $row['id_siswa'] ?>" class="btn btn-link text-danger px-2 mb-0 delete-btn">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </a>
                                            </td>
                                        <?php endif; ?>
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

<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Siswa yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        const table = $('#datatable2').DataTable({
            language: {
                search: "",
                searchPlaceholder: "Cari disini...",
                paginate: {
                    previous: '<i class="material-icons-round">chevron_left</i>',
                    next: '<i class="material-icons-round">chevron_right</i>'
                }
            },
            columnDefs: [{
                targets: 0,
                orderable: false,
                searchable: false
            }],
            order: []
        });

        // Auto nomor urut dinamis
        table.on('order.dt search.dt', function() {
            table.column(0, {
                    search: 'applied',
                    order: 'applied'
                })
                .nodes()
                .each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
        }).draw();

        // PERBAIKAN 2: Filter Kelas dengan REGEX (Exact Match)
        $('#filter-kelas').on('change', function() {
            var selectedValue = $(this).val();

            if (selectedValue) {
                // Menggunakan Regex agar pencarian presisi
                // Contoh: Agar pilih "X-1" TIDAK menampilkan "X-10"
                var regex = '^' + $.fn.dataTable.util.escapeRegex(selectedValue) + '$';

                // true = enable regex, false = disable smart search
                table.column(4).search(regex, true, false).draw();
            } else {
                // Reset filter jika pilih "Semua Kelas"
                table.column(4).search('').draw();
            }
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>