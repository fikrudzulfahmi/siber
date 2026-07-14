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
                        <h6 class="text-white text-capitalize ps-3">Rekap Jurnal Pembelajaran</h6>
                    </div>
                </div>
                <div class="card-body px-4 py-4">
                    <h5>Rekap Jurnal Guru - <?= htmlspecialchars($tanggal) ?></h5>
                    <form method="GET">
                        <input type="hidden" name="controller" value="jurnal">
                        <input type="hidden" name="method" value="rekap">
                        <label>Pilih Tanggal:</label>
                        <div class="row">
                            <div class="col-md-4 col-6 mb-3">

                                <input class="form-control border focus-ring focus-ring-success"
                                    type="date"
                                    name="tanggal"
                                    value="<?= $tanggal ?>"
                                    style="width: 180px;">
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <button type="submit" class="btn bg-gradient-success text-white px-4">
                                    Lihat
                                </button>
                            </div>
                        </div>

                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Guru</th>
                                    <th>Mapel | Kelas | Jam Ke-</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rekap)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Data tidak ditemukan</td>
                                    </tr>
                                    <?php else:
                                    $no = 1;
                                    $currentGuru = "";
                                    foreach ($rekap as $row):
                                        if ($currentGuru != $row['nama']): ?>
                                            <?php if ($currentGuru != "") echo "</td></tr>"; // Tutup baris guru sebelumnya 
                                            ?>
                                            <tr>
                                                <td class='text-center'><?= $no++ ?></td>
                                                <td class="font-weight-bold"><?= htmlspecialchars($row['nama']) ?></td>
                                                <td>
                                                <?php $currentGuru = $row['nama'];
                                            endif; ?>

                                                <?php if ($row['status_jurnal'] == 'Sudah'): ?>
                                                    <button type="button" class="btn btn-sm btn-success detail-jurnal-btn m-1" data-id-jurnal="<?= $row['id_jurnal'] ?>">
                                                        <i class="mdi mdi-book-open-page-variant"></i> <?= htmlspecialchars($row['nama_mapel']) ?> (<?= $row['kelas'] ?>)
                                                    </button>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-light text-dark border m-1">
                                                        <?= htmlspecialchars($row['nama_mapel']) ?> (<?= $row['kelas'] ?>) - Belum Isi
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                                </td>
                                            </tr> <?php endif; ?>
                            </tbody>
                        </table>


                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                document.querySelectorAll('.detail-jurnal-btn').forEach(button => {
                                    button.addEventListener('click', function() {
                                        const idJurnal = this.dataset.idJurnal;

                                        Swal.fire({
                                            title: 'Memuat Detail Jurnal...',
                                            allowOutsideClick: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });

                                        fetch(`?controller=jurnal&method=getDetail&id=${idJurnal}`)
                                            .then(response => {
                                                if (!response.ok) throw new Error('Network response was not ok');
                                                return response.text();
                                            })
                                            .then(text => {
                                                try {
                                                    const data = JSON.parse(text);

                                                    if (data.error) {
                                                        Swal.fire('Gagal!', data.error, 'error');
                                                        return;
                                                    }

                                                    const jurnal = data.jurnal;
                                                    const kehadiran = data.kehadiran || [];

                                                    // 1. Render Tabel Informasi Utama
                                                    let html = `
                        <div style="text-align: left; font-size: 14px;">
                            <table class="table table-bordered" style="table-layout: fixed; width: 100%;">
                                <tbody>
                                    <tr>
                                        <td style="width: 35%; font-weight: bold; background-color: #f8f9fa;">Mapel / Kelas</td>
                                        <td>${jurnal?.nama_mapel || '-'} / ${jurnal?.nama_kelas || '-'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold; background-color: #f8f9fa;">Guru Pengajar</td>
                                        <td>${jurnal?.nama_guru || '-'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold; background-color: #f8f9fa;">Jam Ke-</td>
                                        <td>${jurnal?.jam_mulai || '?'} - ${jurnal?.jam_akhir || jurnal?.jam_selesai || '?'}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: bold; background-color: #f8f9fa;">Tujuan Pembelajaran</td>
                                        <td style="white-space: pre-wrap; word-wrap: break-word;">${jurnal?.tujuan_pembelajaran || '-'}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <h6 class="mt-3" style="font-weight: bold;">Materi Pembelajaran:</h6>
                            <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-radius: 5px; margin-bottom: 15px;">
                                ${jurnal?.materi || '-'}
                            </div>

                            <h6 class="mt-3" style="font-weight: bold;">Daftar Ketidakhadiran Siswa:</h6>
                            <table class="table table-sm table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nama Siswa</th>
                                        <th style="width: 20%; text-align: center;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                                                    // 2. Render Daftar Kehadiran (Looping tabel jurnal_kehadiran)
                                                    if (kehadiran.length > 0) {
                                                        kehadiran.forEach(s => {
                                                            let badgeColor = s.status === 'A' ? 'bg-danger' : (s.status === 'S' ? 'bg-warning text-dark' : 'bg-info');
                                                            html += `
                                    <tr>
                                        <td>${s.nama_siswa}</td>
                                        <td style="text-align: center;"><span class="badge ${badgeColor}">${s.status}</span></td>
                                    </tr>`;
                                                        });
                                                    } else {
                                                        html += `<tr><td colspan="2" class="text-center text-muted">Semua siswa hadir (Nihil).</td></tr>`;
                                                    }

                                                    html += `</tbody></table></div>`;

                                                    // 3. Tampilkan Modal
                                                    Swal.fire({
                                                        title: '<strong>Detail Jurnal Pembelajaran</strong>',
                                                        html: html,
                                                        icon: 'info',
                                                        width: '800px',
                                                        confirmButtonText: 'Tutup',
                                                        confirmButtonColor: '#3085d6'
                                                    });

                                                } catch (err) {
                                                    console.error("JSON Parse Error:", text);
                                                    Swal.fire('Error!', 'Terjadi kesalahan saat memproses data server.', 'error');
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Fetch Error:', error);
                                                Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                                            });
                                    });
                                });
                            });
                        </script>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <?php include '../app/views/layouts/footer.php'; ?>