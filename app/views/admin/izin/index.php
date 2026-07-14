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

<style>
    /* Ganti #datatable5 dengan ID tabel Anda */
    #datatable5 {
        table-layout: fixed;
        width: 100% !important;
    }

    /* Kelas untuk membungkus teks */
    .wrap-text {
        white-space: normal !important;
        /* Wajib ada untuk mengizinkan wrap */
        word-wrap: break-word;
        /* Memecah kata yang panjang */
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">

                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Daftar Izin Siswa</h6>
                    </div>
                </div>

                <div class="card-body px-4 pb-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <form action="?controller=izin&method=rekap" method="get" target="_blank" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                            <input type="hidden" name="controller" value="izin">
                            <input type="hidden" name="method" value="rekap">
                            <input type="date" name="tanggal_awal" class="form-control" required style="max-width: 180px;">
                            <input type="date" name="tanggal_akhir" class="form-control" required style="max-width: 180px;">
                            <button type="submit" class="btn btn-dark d-flex align-items-center gap-1">
                                <span class="material-icons">print</span> Cetak Rekap
                            </button>
                        </form>

                        <?php if (isAnyLevel($id_level, [3, 4])): // ✅ Variabel $id_level sekarang sudah ada 
                        ?>
                            <div class="align-items-center flex-wrap gap-3">
                                <a href="?controller=izin&method=create" class="btn btn-dark d-flex align-items-center gap-1">
                                    <span class="material-icons">add</span> Tambah Izin
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body px-0 pb-2">
                    <div class="table-responsive p-4">
                        <table id="datatable5" class="table table-bordered table-striped align-items-center mb-0">
                            <thead class="text-center">
                                <tr>
                                    <th width="3%">No</th>
                                    <th width="20%">Nama Siswa</th>
                                    <th width="10%">Kelas</th>
                                    <th width="20%">Keperluan</th>
                                    <th width="15%">Waktu Meninggalkan</th>
                                    <th width="15%">Waktu Kembali</th>
                                    <th width="15%">Rekomendasi</th>
                                    <th width="25%">Keterangan</th>
                                    <?php if (isAnyLevel($id_level, [3, 1, 5, 6, 7])): // ✅ Ditambahkan Pimpinan bisa edit/hapus juga 
                                    ?>
                                        <th>Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($izin as $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= $row['nama_siswa'] ?></td>
                                        <td><?= $row['nama_kelas'] ?></td>
                                        <td><?= $row['keperluan'] ?></td>
                                        <td><?= formatTanggalIndo($row['waktu_meninggalkan'], true, true) ?></td>
                                        <td><?= formatTanggalIndo($row['waktu_kembali'], true, true) ?></td>
                                        <td><?= $row['nama_rekom'] ?></td>
                                        <td>
                                            <?php
                                            $info = [];

                                            // Cek dan ubah teks keterangan
                                            if (!empty($row['keterangan'])) {
                                                $keterangan_text = '';
                                                if ($row['keterangan'] == 'tepat') {
                                                    $keterangan_text = 'Tepat Waktu';
                                                } elseif ($row['keterangan'] == 'terlambat') {
                                                    $keterangan_text = 'Terlambat';
                                                }

                                                if ($keterangan_text) {
                                                    $info[] = '<strong>' . $keterangan_text . '</strong>';
                                                }
                                            }

                                            // Tambahkan tindakan jika ada isinya
                                            if (!empty($row['tindakan'])) {
                                                $info[] = htmlspecialchars($row['tindakan']);
                                            }

                                            // Gabungkan info atau tampilkan strip jika kosong
                                            echo !empty($info) ? implode(' - ', $info) : '-';
                                            ?>
                                        </td>
                                        <?php if (isAnyLevel($id_level, [3, 1, 5, 6, 7])): // ✅ Ditambahkan Pimpinan bisa edit/hapus juga 
                                        ?>
                                            <td class="text-center">
                                                <a href="?controller=izin&method=edit&id=<?= $row['id_perizinan'] ?>" class="btn btn-sm btn-success">Edit</a>
                                                <a href="?controller=izin&method=delete&id=<?= $row['id_perizinan'] ?>" class="btn btn-sm btn-dark delete-btn">Hapus</a>
                                                <a href="?controller=izin&method=cetak&id=<?= $row['id_perizinan'] ?>" class="btn btn-sm btn-info" target="_blank">Cetak</a>
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
            e.preventDefault(); // Mencegah link langsung berjalan
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Perizinan yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href; // Lanjutkan ke link hapus jika dikonfirmasi
                }
            });
        });
    });
</script>