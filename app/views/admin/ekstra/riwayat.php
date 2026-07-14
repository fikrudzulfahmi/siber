<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <?php if ($msg = getFlash('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: <?= json_encode($msg) ?>, // agar aman dari karakter khusus
                    confirmButtonColor: '#4caf50',
                    confirmButtonText: 'OK'
                });
            });
        </script>
    <?php endif; ?>
    <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
            <div class="bg-gradient-info shadow-info border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="text-white ps-3 mb-0">Riwayat Kegiatan: <?= htmlspecialchars($ekstra['nama_ekstra']) ?></h6>
                <a href="?controller=ekstra&method=index" class="btn btn-sm btn-outline-white me-3 mb-0">Kembali</a>
            </div>
        </div>
        <div class="card-body px-0 pb-2">
            <div class="table-responsive p-0">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tanggal</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Kegiatan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Isi Kegiatan</th>
                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Foto</th>
                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>

                            <td class="text-center">
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($riwayat)): foreach ($riwayat as $r): ?>
                                <tr>
                                    <td class="ps-4 text-xs font-weight-bold"><?= formatTanggalIndo($r['tanggal']) ?></td>
                                    <td class="text-sm font-weight-bold"><?= htmlspecialchars($r['nama_kegiatan']) ?></td>
                                    <td class="text-xs" style="white-space: pre-line;"><?= htmlspecialchars($r['isi_kegiatan']) ?></td>
                                    <td>
                                        <?php if ($r['nama_file']): ?>
                                            <a href="../public/uploads/ekstra/<?= $r['nama_file'] ?>" target="_blank">
                                                <img src="../public/uploads/ekstra/<?= $r['nama_file'] ?>" class="avatar avatar-sm me-3 border-radius-lg">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-xxs text-secondary">Tidak ada foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">

                                        <a href="?controller=ekstra&method=editJurnal&id_kegiatan=<?= $r['id_ekstra_kegiatan'] ?>&id_ekstra=<?= $id_ekstra ?>"
                                            class="btn btn-link text-warning mb-0 px-2">
                                            <i class="material-icons text-sm">edit</i>
                                        </a>
                                        <a href="?controller=ekstra&method=hapusJurnal&id_kegiatan=<?= $r['id_ekstra_kegiatan'] ?>&id_ekstra=<?= $id_ekstra ?>"
                                            onclick="return confirm('Hapus jurnal ini? Data presensi juga akan terhapus.')"
                                            class="btn btn-link text-danger mb-0 px-2">
                                            <i class="material-icons text-sm">delete</i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-xs text-secondary">Belum ada catatan kegiatan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>