<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4 col-md-5">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Detail Kegiatan</h6>
                    </div>
                </div>

                <div class="card-body p-3">
                    <ul class="list-group">
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Nama:</strong> &nbsp; <?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Tanggal:</strong> &nbsp; <?= date('d/m/Y', strtotime($kegiatan['tanggal'])) ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Waktu:</strong> &nbsp; <?= substr($kegiatan['jam_mulai'], 0, 5) ?> - <?= substr($kegiatan['jam_selesai'], 0, 5) ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Keterangan:</strong> <br> <?= nl2br(htmlspecialchars($kegiatan['keterangan'])) ?></li>
                    </ul>

                    <hr class="horizontal dark mt-4 mb-3">

                    <h6>Dokumentasi (Foto)</h6>
                    <div class="row">
                        <?php
                        $fotos = $kegiatanModel->getFotosByKegiatan($kegiatan['id_kegiatan']);
                        if (!empty($fotos)):
                            foreach ($fotos as $f):
                        ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card shadow-none border">
                                        <img src="public/uploads/kegiatan/<?= $f['nama_file'] ?>" class="img-fluid border-radius-lg">
                                    </div>
                                </div>
                            <?php
                            endforeach;
                        else:
                            ?>
                            <div class="col-12">
                                <div class="border border-2 border-dashed border-radius-lg p-4 text-center">
                                    <p class="text-xs text-secondary mb-0">Belum ada foto kegiatan.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="?controller=kegiatan&method=uploadFoto" method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="id_kegiatan" value="<?= $kegiatan['id_kegiatan'] ?>">
                        <div class="input-group input-group-outline mb-2">
                            <input type="file" name="foto[]" class="form-control" accept="image/*" multiple required>
                        </div>
                        <p class="text-xxs text-muted">* Anda bisa memilih lebih dari 1 foto sekaligus</p>
                        <button type="submit" class="btn btn-sm bg-gradient-dark w-100">
                            <i class="fas fa-upload me-2"></i> Unggah & Kompres Semua
                        </button>
                    </form>
                </div>
            </div>
            <a href="?controller=kegiatan&method=index" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
            </a>
        </div>

        <div class="col-lg-8 col-md-7">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-3 pb-3 px-3 d-flex align-items-center justify-content-between">
                        <h6 class="text-white text-capitalize mb-0">Rekap Kehadiran (Range Waktu)</h6>
                        <a href="?controller=kegiatan&method=cetak&id_kegiatan=<?= $kegiatan['id_kegiatan'] ?>"
                            class="btn btn-light btn-sm mb-0 text-success" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i> Download Laporan PDF
                        </a>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="table-responsive p-0" style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-sm align-items-center mb-0" id="tablePresensi">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Guru</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jam Masuk</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jam Pulang</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rekap_absen as $row): ?>
                                    <?php
                                    $jam_masuk = $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : null;
                                    $jam_pulang = $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : null;

                                    // Logika Status
                                    $status_label = '<span class="badge badge-sm bg-gradient-danger">TIDAK HADIR</span>';
                                    $detail_keterangan = "";

                                    if ($jam_masuk) {
                                        $status_label = '<span class="badge badge-sm bg-gradient-success">HADIR</span>';

                                        // Cek Terlambat (Jika jam masuk > jam mulai kegiatan)
                                        if (strtotime($jam_masuk) > strtotime($kegiatan['jam_mulai'])) {
                                            $detail_keterangan .= '<div class="text-danger mt-1" style="font-size: 10px;">TERLAMBAT</div>';
                                        }

                                        // Cek Pulang Cepat (Jika jam pulang < jam selesai kegiatan)
                                        if ($jam_pulang && strtotime($jam_pulang) < strtotime($kegiatan['jam_selesai'])) {
                                            $detail_keterangan .= '<div class="text-warning mt-1" style="font-size: 10px;">PULANG CEPAT</div>';
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($row['nama']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0">PIN: <?= $row['pin'] ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold"><?= $jam_masuk ?? '-' ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold"><?= ($jam_pulang != $jam_masuk) ? $jam_pulang : '-' ?></span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <?= $status_label ?>
                                            <?= $detail_keterangan ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3" id="paginationContainer">
                        <ul class="pagination pagination-sm m-0" id="pagination"></ul>
                    </div>

                    <p class="text-xxs text-secondary mt-3 mb-0 fst-italic">
                        * Status <strong>Hadir</strong> diberikan jika guru melakukan scan fingerprint pada rentang jam kegiatan (termasuk toleransi 15 menit sebelum mulai).
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../app/views/layouts/footer.php'; ?>

<style>
    /* CSS ini yang bikin ringan, menyembunyikan baris sejak awal tanpa nunggu JS jalan */
    #tablePresensi tbody tr {
        display: none;
    }

    /* Hanya tampilkan yang punya class ini */
    #tablePresensi tbody tr.baris-aktif {
        display: table-row;
    }

    /* Style Tombol Aktif */
    .page-item.active .page-link {
        background-color: #212529 !important;
        border-color: #212529 !important;
        color: #fff !important;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        var limit = 6; // Ubah angka ini untuk mengatur jumlah baris per halaman
        var rows = $('#tablePresensi tbody tr');
        var totalRows = rows.length;
        var totalPages = Math.ceil(totalRows / limit);
        var pagination = $('#pagination');

        if (totalPages > 1) {
            var htmlPagination = '';
            for (var i = 1; i <= totalPages; i++) {
                var activeClass = (i === 1) ? 'active' : '';
                htmlPagination += '<li class="page-item ' + activeClass + '" data-page="' + i + '"><a class="page-link text-dark" href="javascript:void(0)">' + i + '</a></li>';
            }
            pagination.html(htmlPagination);

            showPage(1);

            pagination.on('click', 'li.page-item', function() {
                var pageNum = $(this).data('page');
                pagination.find('li').removeClass('active');
                $(this).addClass('active');
                showPage(pageNum);
            });
        } else {
            rows.addClass('baris-aktif');
        }

        function showPage(page) {
            var start = (page - 1) * limit;
            var end = start + limit;
            rows.removeClass('baris-aktif');
            rows.slice(start, end).addClass('baris-aktif');
        }
    });
</script>