<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    .us-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .us-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .6rem .9rem;
        margin-bottom: .5rem;
        border: 1px solid #e9ecef;
        border-radius: .6rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .us-item:hover {
        border-color: #a7d8b0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    }

    .us-item.us-ghost {
        opacity: .45;
        background: #f8f9fa;
    }

    .us-handle {
        cursor: grab;
        color: #9aa0a6;
        display: inline-flex;
        align-items: center;
        padding: .25rem .1rem;
        touch-action: none;
    }

    .us-handle:active {
        cursor: grabbing;
    }

    .us-no {
        min-width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: #e8f5e9;
        color: #2e7d32;
        font-weight: 600;
        font-size: .8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .us-nama {
        flex: 1 1 auto;
        min-width: 0;
        font-size: .9rem;
        font-weight: 500;
        color: #344767;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .us-nisn {
        font-size: .72rem;
        color: #8392ab;
        white-space: nowrap;
    }

    .us-aksi {
        display: flex;
        gap: .25rem;
    }

    .us-aksi .btn {
        --bs-btn-padding-y: .15rem;
        --bs-btn-padding-x: .35rem;
        --bs-btn-font-size: .8rem;
        line-height: 1.2;
    }

    .us-kosong {
        padding: 2rem 1rem;
        text-align: center;
        color: #8392ab;
        font-size: .9rem;
    }

    @media (max-width: 575.98px) {
        .us-nisn {
            display: none;
        }
    }
</style>

<div class="container-fluid py-4">

    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="ps-3">
                            <h6 class="text-white text-capitalize mb-0">Urutkan Siswa</h6>
                            <p class="text-white text-xs mb-0 opacity-8">Geser kartu ke atas/bawah, lalu simpan.</p>
                        </div>
                        <span id="statusSimpan" class="badge bg-white text-dark me-3 mb-0" hidden>Belum disimpan</span>
                    </div>
                </div>

                <div class="card-body">
                    <form method="GET" action="index.php" class="row g-2 align-items-end mb-4">
                        <input type="hidden" name="controller" value="urutkanSiswa">
                        <input type="hidden" name="method" value="index">
                        <div class="col-12 col-sm-6 col-lg-4">
                            <label class="form-label text-xs text-secondary mb-1">Kelas</label>
                            <select name="id_kelas" class="form-control form-control-sm border ps-2" onchange="this.form.submit()">
                                <?php foreach ($daftar_kelas as $dk) : ?>
                                    <option value="<?= (int) $dk['id_kelas']; ?>" <?= ((int) $dk['id_kelas'] === (int) $id_kelas) ? 'selected' : ''; ?>>
                                        <?= sec_e($dk['kelas']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <label class="form-label text-xs text-secondary mb-1">Tahun Pelajaran</label>
                            <select name="id_tahun" class="form-control form-control-sm border ps-2" onchange="this.form.submit()">
                                <?php foreach ($daftar_tahun as $tp) : ?>
                                    <option value="<?= (int) $tp['id_tahun_pelajaran']; ?>" <?= ((int) $tp['id_tahun_pelajaran'] === (int) $id_tahun) ? 'selected' : ''; ?>>
                                        <?= sec_e($tp['tahun_pelajaran']); ?> - <?= sec_e($tp['semester']); ?><?= (strtolower((string) $tp['status']) === 'aktif') ? ' (aktif)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <?php if (empty($siswa)) : ?>
                        <div class="us-kosong">
                            Belum ada siswa aktif di kelas ini pada tahun pelajaran yang dipilih.
                        </div>
                    <?php else : ?>
                        <p class="text-xs text-secondary mb-2">
                            <?= count($siswa); ?> siswa &middot; urutan ini dipakai di absensi, nilai, leger, rapor, dan cetak.
                        </p>

                        <ul class="us-list" id="daftarUrut">
                            <?php $no = 0; foreach ($siswa as $s) : $no++; ?>
                                <li class="us-item" data-id="<?= (int) $s['id_ploting']; ?>">
                                    <span class="us-handle" title="Geser untuk memindah">
                                        <i class="material-icons-round">drag_indicator</i>
                                    </span>
                                    <span class="us-no"><?= $no; ?></span>
                                    <span class="us-nama"><?= sec_e($s['nama_siswa']); ?></span>
                                    <span class="us-nisn"><?= sec_e((string) $s['nisn']); ?></span>
                                    <span class="us-aksi">
                                        <button type="button" class="btn btn-outline-secondary us-up" title="Naikkan" aria-label="Naikkan">
                                            <i class="material-icons-round">arrow_upward</i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary us-down" title="Turunkan" aria-label="Turunkan">
                                            <i class="material-icons-round">arrow_downward</i>
                                        </button>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="button" class="btn btn-success mb-0" id="btnSimpan" disabled>
                                <i class="material-icons-round me-1">save</i> Simpan Urutan
                            </button>
                            <button type="button" class="btn btn-outline-secondary mb-0" id="btnReset">
                                <i class="material-icons-round me-1">sort_by_alpha</i> Kembali ke Abjad
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($siswa)) : ?>
    <script src="assets/js/sortable.min.js"></script>
    <script>
        (function () {
            'use strict';

            var list = document.getElementById('daftarUrut');
            var btnSimpan = document.getElementById('btnSimpan');
            var btnReset = document.getElementById('btnReset');
            var status = document.getElementById('statusSimpan');
            var idKelas = <?= (int) $id_kelas; ?>;
            var idTahun = <?= (int) $id_tahun; ?>;
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : '';
            var kotor = false;

            function nomori() {
                var item = list.querySelectorAll('.us-item');
                for (var i = 0; i < item.length; i++) {
                    item[i].querySelector('.us-no').textContent = i + 1;
                }
            }

            function tandaiKotor() {
                if (kotor) {
                    return;
                }
                kotor = true;
                btnSimpan.disabled = false;
                status.hidden = false;
            }

            function pesan(tipe, teks) {
                if (window.SiberToast) {
                    SiberToast.show(tipe, teks);
                } else if (tipe === 'error' && window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: teks });
                } else {
                    alert(teks);
                }
            }

            function kirim(aksi) {
                var ids = [];
                list.querySelectorAll('.us-item').forEach(function (el) {
                    ids.push(el.getAttribute('data-id'));
                });

                var body = new URLSearchParams();
                body.append('csrf_token', token);
                body.append('id_kelas', idKelas);
                body.append('id_tahun', idTahun);
                ids.forEach(function (id) {
                    body.append('urutan[]', id);
                });

                return fetch('index.php?controller=urutkanSiswa&method=' + aksi, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': token,
                        'Accept': 'application/json'
                    },
                    body: body.toString()
                }).then(function (r) {
                    return r.json().catch(function () {
                        return { ok: false, error: 'Jawaban server tidak dikenali.' };
                    });
                });
            }

            // Drag & drop (SortableJS). Kalau gagal dimuat, tombol panah tetap bekerja.
            if (window.Sortable) {
                new Sortable(list, {
                    handle: '.us-handle',
                    animation: 150,
                    ghostClass: 'us-ghost',
                    onEnd: function () {
                        nomori();
                        tandaiKotor();
                    }
                });
            }

            list.addEventListener('click', function (e) {
                var naik = e.target.closest('.us-up');
                var turun = e.target.closest('.us-down');
                if (!naik && !turun) {
                    return;
                }
                var li = e.target.closest('.us-item');
                if (!li) {
                    return;
                }
                if (naik && li.previousElementSibling) {
                    list.insertBefore(li, li.previousElementSibling);
                } else if (turun && li.nextElementSibling) {
                    list.insertBefore(li.nextElementSibling, li);
                } else {
                    return;
                }
                nomori();
                tandaiKotor();
            });

            btnSimpan.addEventListener('click', function () {
                btnSimpan.disabled = true;
                kirim('simpan').then(function (res) {
                    if (res.ok) {
                        var baru = list.querySelectorAll('.us-item').length;
                        if (baru) {
                            btnSimpan.disabled = true;
                        }
                        kotor = false;
                        status.hidden = true;
                        pesan('success', res.pesan || 'Urutan berhasil disimpan.');
                    } else {
                        btnSimpan.disabled = false;
                        pesan('error', res.error || res.message || 'Urutan gagal disimpan.');
                    }
                }).catch(function () {
                    btnSimpan.disabled = false;
                    pesan('error', 'Koneksi gagal. Periksa jaringan lalu coba lagi.');
                });
            });

            btnReset.addEventListener('click', function () {
                var jalan = function () {
                    kirim('reset').then(function (res) {
                        if (res.ok) {
                            window.location.reload();
                        } else {
                            pesan('error', res.error || 'Urutan gagal direset.');
                        }
                    }).catch(function () {
                        pesan('error', 'Koneksi gagal. Periksa jaringan lalu coba lagi.');
                    });
                };

                if (window.SiberConfirm) {
                    SiberConfirm({
                        judul: 'Kembalikan ke abjad?',
                        teks: 'Urutan kelas ini akan dihapus dan siswa kembali diurutkan sesuai nama.',
                        tombolYa: 'Ya, kembalikan'
                    }).then(function (ya) {
                        if (ya) {
                            jalan();
                        }
                    });
                } else if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Kembalikan ke abjad?',
                        text: 'Urutan kelas ini akan dihapus dan siswa kembali diurutkan sesuai nama.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, kembalikan',
                        cancelButtonText: 'Batal'
                    }).then(function (r) {
                        if (r.isConfirmed) {
                            jalan();
                        }
                    });
                } else if (confirm('Kembalikan urutan kelas ini ke abjad?')) {
                    jalan();
                }
            });
        })();
    </script>
<?php endif; ?>

<?php include '../app/views/layouts/footer.php'; ?>
