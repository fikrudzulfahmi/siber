<?php
extract($data);
include '../app/views/layouts/header.php';
include '../app/views/layouts/sidebar.php';
?>

<div class="container-fluid py-4">
    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#4caf50'
                });
            });
        </script>
    <?php endif; ?>
    <?php if ($msg = getFlash('error')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: <?= json_encode($msg) ?>,
                    confirmButtonColor: '#d33'
                });
            });
        </script>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            Kategori Nilai: <?= htmlspecialchars($info['nama_mapel']) ?> - Kelas <?= htmlspecialchars($info['kelas']) ?>
                        </h6>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="d-flex justify-content-between align-items-center mx-4 mt-3 mb-3">
                        <div>
                            <?php if ($cekKategori): ?>
                                <button type="button" class="btn btn-primary" onclick="alertSudahAda()">
                                    <i class="fas fa-plus"></i> Tambah Kategori
                                </button>
                            <?php else: ?>
                                <a href="?controller=kategori&method=create&id_mapel_guru=<?= $id_mapel_guru ?>" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Kategori
                                </a>
                            <?php endif; ?>

                            <script>
                                function alertSudahAda() {
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'Akses Dibatasi',
                                        text: 'Anda sudah membuat kategori nilai untuk mata pelajaran ini pada semester/tahun pelajaran tersebut.',
                                        footer: '<span style="color: #d33">Catatan: Hanya diperbolehkan 1 kategori per semester.</span>',
                                        confirmButtonColor: '#3085d6',
                                        confirmButtonText: 'Siap, Mengerti'
                                    });
                                }
                            </script>
                        </div>

                        <div class="text-end">
                            <span class="badge bg-gradient-info mb-2 d-block">
                                Tahun Pelajaran Aktif: <?= htmlspecialchars($active_tahun['tahun_pelajaran']) ?> (<?= htmlspecialchars($active_tahun['semester']) ?>)
                            </span>
                            <a href="?controller=penilaian&method=index" class="btn btn-outline-secondary btn-sm mb-0">Kembali</a>
                        </div>
                    </div>

                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0 table-hover">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kategori</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tahun Pelajaran</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Semester</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Banyak Nilai</th>
                                    <th class="text-secondary opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($kategoriList)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-secondary">
                                            <i class="material-icons text-lg mb-2">inbox</i>
                                            <p class="mb-0 text-sm">Belum ada kategori nilai untuk Tahun Pelajaran Aktif ini.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1;
                                    foreach ($kategoriList as $row): ?>
                                        <tr>
                                            <td class="ps-4 text-sm"><?= $no++ ?></td>
                                            <td class="text-sm font-weight-bold"><?= htmlspecialchars($row['kategori']) ?></td>
                                            <td class="text-sm"><?= htmlspecialchars($row['tahun_pelajaran']) ?></td>
                                            <td class="text-sm"><?= htmlspecialchars($row['semester']) ?></td>
                                            <td class="text-center text-sm"><?= htmlspecialchars($row['banyak_ns']) ?></td>
                                            <td class="align-middle">
                                                <a href="?controller=kategori&method=nilai&id=<?= $row['id_kategori'] ?>&id_mapel_guru=<?= $row['id_mapel_guru'] ?>"
                                                    class="btn btn-success btn-sm px-3 mb-0">Input Nilai</a>

                                                <a href="?controller=kategori&method=edit&id=<?= $row['id_kategori'] ?>"
                                                    class="btn btn-info btn-sm px-3 mb-0">Edit</a>

                                                <a href="?controller=kategori&method=delete&id=<?= $row['id_kategori'] ?>&id_mapel_guru=<?= $row['id_mapel_guru'] ?>"
                                                    class="btn btn-danger btn-sm px-3 mb-0 btn-hapus">Hapus</a>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Event delegation agar tombol hapus berfungsi
        document.body.addEventListener('click', function(event) {
            if (event.target.closest('.btn-hapus')) {
                event.preventDefault();
                const button = event.target.closest('.btn-hapus');
                const url = button.href;

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Kategori dan semua nilai siswa di dalamnya akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            }
        });
    });
</script>

<?php include '../app/views/layouts/footer.php'; ?>