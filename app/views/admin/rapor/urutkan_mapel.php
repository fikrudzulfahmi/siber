<?php include '../app/views/layouts/header.php'; ?>
<?php include '../app/views/layouts/sidebar.php'; ?>

<style>
    .um-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .um-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .9rem;
        margin-bottom: .5rem;
        border: 1px solid #e9ecef;
        border-radius: .6rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .um-item:hover {
        border-color: #a7d8b0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    }

    .um-item.um-ghost {
        opacity: .45;
        background: #f8f9fa;
    }

    .um-handle {
        flex: 0 0 auto;
        cursor: grab;
        color: #9aa0a6;
        display: inline-flex;
        align-items: center;
        padding: .25rem .1rem;
        touch-action: none;
    }

    .um-handle:active {
        cursor: grabbing;
    }

    .um-no {
        flex: 0 0 auto;
        min-width: 2.1rem;
        height: 2.1rem;
        border-radius: 50%;
        background: #e8f5e9;
        color: #2e7d32;
        font-weight: 700;
        font-size: .82rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .um-detail {
        flex: 1 1 auto;
        min-width: 0;
    }

    .um-nama {
        font-size: .92rem;
        font-weight: 600;
        color: #344767;
        margin-bottom: .15rem;
        line-height: 1.25;
    }

    .um-meta {
        font-size: .75rem;
        color: #8392ab;
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
    }

    .um-meta .badge-kode {
        background: #f1f3f5;
        color: #495057;
        padding: .15rem .45rem;
        border-radius: .35rem;
        font-weight: 600;
        font-size: .7rem;
    }

    .um-aksi {
        flex: 0 0 auto;
        display: flex;
        gap: .25rem;
    }

    .um-aksi .btn {
        --bs-btn-padding-y: .25rem;
        --bs-btn-padding-x: .45rem;
        --bs-btn-font-size: .8rem;
        line-height: 1.2;
        flex: 0 0 auto;
        min-width: 2.1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .um-kosong {
        padding: 2.5rem 1rem;
        text-align: center;
        color: #8392ab;
        font-size: .92rem;
    }

    @media (max-width: 575.98px) {
        .um-item {
            flex-wrap: wrap;
            gap: .35rem .5rem;
            padding: .6rem .75rem;
        }

        .um-handle { order: 1; }
        .um-no { order: 2; min-width: 1.85rem; height: 1.85rem; font-size: .75rem; }
        .um-aksi { order: 3; margin-left: auto; }
        .um-detail { order: 4; flex: 1 0 100%; margin-top: .2rem; }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-success shadow-success border-radius-lg pt-4 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="ps-3">
                            <h6 class="text-white text-capitalize mb-0">Urutan Mapel Rapor</h6>
                            <p class="text-white text-xs mb-0 opacity-8">Atur urutan tampil mata pelajaran di lembar rapor per kelas & tahun pelajaran.</p>
                        </div>
                        <span id="statusSimpan" class="badge bg-white text-dark me-3 mb-0" hidden>Belum disimpan</span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filter Kelas & Info Periode -->
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-12 col-md-5 col-lg-4">
                            <form method="GET" action="index.php" id="formPilihKelas">
                                <input type="hidden" name="controller" value="rapor">
                                <input type="hidden" name="method" value="urutkan_mapel">
                                <label class="form-label text-xs text-secondary mb-1">Pilih Kelas</label>
                                <select name="id_kelas" class="form-control form-control-sm border ps-2" onchange="this.form.submit()">
                                    <?php foreach ($daftar_kelas as $dk) : ?>
                                        <option value="<?= (int) $dk['id_kelas']; ?>" <?= ((int) $dk['id_kelas'] === (int) $id_kelas) ? 'selected' : ''; ?>>
                                            <?= sec_e($dk['kelas']); ?> (Tingkat <?= sec_e($dk['tingkat']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>

                        <div class="col-12 col-md-7 col-lg-8">
                            <div class="p-2 border rounded-3 bg-light d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="text-xs text-secondary">
                                    <span class="fw-bold text-dark">Periode Rapor Aktif:</span>
                                    <span class="badge bg-gradient-info ms-1"><?= sec_e(strtoupper($setting['jenis_rapor'])); ?></span>
                                    <span class="ms-1">TA <?= sec_e($setting['tahun_pelajaran']); ?> (Semester <?= sec_e($setting['semester']); ?>)</span>
                                </div>
                                <?php if (!empty($mapelList)) : ?>
                                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#modalSalin">
                                        <i class="material-icons-round text-xs me-1">content_copy</i> Salin ke Kelas Lain
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($mapelList)) : ?>
                        <div class="um-kosong border rounded-3 bg-light">
                            <i class="material-icons-round text-secondary text-3xl mb-2">menu_book</i>
                            <p class="mb-0">Belum ada mata pelajaran yang diploting untuk kelas <strong><?= sec_e($kelas['kelas'] ?? '-'); ?></strong> pada tahun pelajaran ini.</p>
                        </div>
                    <?php else : ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="text-xs text-secondary mb-0">
                                <strong><?= count($mapelList); ?> mata pelajaran</strong> terdaftar &middot; geser kartu atau pakai panah untuk menentukan urutan di rapor.
                            </p>
                        </div>

                        <ul class="um-list" id="daftarUrutMapel">
                            <?php $no = 0; foreach ($mapelList as $m) : $no++; ?>
                                <li class="um-item" data-id="<?= (int) $m['id_mapel_guru']; ?>">
                                    <span class="um-handle" title="Geser untuk memindah posisi">
                                        <i class="material-icons-round">drag_indicator</i>
                                    </span>
                                    <span class="um-no"><?= $no; ?></span>
                                    <div class="um-detail">
                                        <div class="um-nama"><?= sec_e($m['nama_mapel']); ?></div>
                                        <div class="um-meta">
                                            <span class="badge-kode"><?= sec_e($m['kode_mapel']); ?></span>
                                            <span><i class="material-icons-round text-xxs me-1">person</i><?= sec_e($m['nama_guru'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                    <span class="um-aksi">
                                        <button type="button" class="btn btn-outline-secondary um-up" title="Naikkan" aria-label="Naikkan">
                                            <i class="material-icons-round">arrow_upward</i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary um-down" title="Turunkan" aria-label="Turunkan">
                                            <i class="material-icons-round">arrow_downward</i>
                                        </button>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="button" class="btn btn-success mb-0" id="btnSimpan" disabled>
                                <i class="material-icons-round me-1">save</i> Simpan Urutan
                            </button>
                            <button type="button" class="btn btn-outline-secondary mb-0" id="btnReset">
                                <i class="material-icons-round me-1">restart_alt</i> Reset ke Abjad
                            </button>
                            <a href="?controller=rapor&method=index" class="btn btn-link text-secondary mb-0 ms-auto">
                                <i class="material-icons-round me-1">arrow_back</i> Kembali ke Layanan Rapor
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Salin Urutan ke Kelas Lain -->
<div class="modal fade" id="modalSalin" tabindex="-1" aria-labelledby="modalSalinLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalSalinLabel">Salin Urutan ke Kelas Lain</h6>
                <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-sm text-secondary">
                    Urutan mapel dari kelas <strong><?= sec_e($kelas['kelas'] ?? ''); ?></strong> akan diterapkan pada mapel yang cocok di kelas tujuan.
                </p>
                <div class="mb-3">
                    <label class="form-label text-xs fw-bold text-dark">Kelas Tujuan</label>
                    <select id="pilihKelasTujuan" class="form-control border ps-2">
                        <option value="">-- Pilih Kelas Tujuan --</option>
                        <?php foreach ($daftar_kelas as $dk) : ?>
                            <?php if ((int) $dk['id_kelas'] !== (int) $id_kelas) : ?>
                                <option value="<?= (int) $dk['id_kelas']; ?>">
                                    <?= sec_e($dk['kelas']); ?> (Tingkat <?= sec_e($dk['tingkat']); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm mb-0" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success btn-sm mb-0" id="btnKonfirmasiSalin">
                    <i class="material-icons-round text-xs me-1">content_copy</i> Salin Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($mapelList)) : ?>
    <script src="assets/js/sortable.min.js"></script>
    <script>
        (function () {
            'use strict';

            var list = document.getElementById('daftarUrutMapel');
            var btnSimpan = document.getElementById('btnSimpan');
            var btnReset = document.getElementById('btnReset');
            var status = document.getElementById('statusSimpan');
            var idKelas = <?= (int) $id_kelas; ?>;
            var idTahun = <?= (int) $id_tahun; ?>;
            var meta = document.querySelector('meta[name="csrf-token"]');
            var token = meta ? meta.getAttribute('content') : '';
            var kotor = false;

            function nomori() {
                var items = list.querySelectorAll('.um-item');
                for (var i = 0; i < items.length; i++) {
                    items[i].querySelector('.um-no').textContent = i + 1;
                }
            }

            function tandaiKotor() {
                if (kotor) return;
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

            function kirim(aksi, dataTambahan) {
                var ids = [];
                list.querySelectorAll('.um-item').forEach(function (el) {
                    ids.push(el.getAttribute('data-id'));
                });

                var body = new URLSearchParams();
                if (token) body.append('csrf_token', token);
                body.append('id_kelas', idKelas);
                body.append('id_tahun', idTahun);

                ids.forEach(function (id) {
                    body.append('urutan[]', id);
                });

                if (dataTambahan) {
                    for (var k in dataTambahan) {
                        body.append(k, dataTambahan[k]);
                    }
                }

                return fetch('index.php?controller=rapor&method=' + aksi, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': token || ''
                    },
                    body: body.toString()
                }).then(function (r) {
                    return r.json().catch(function () {
                        return { ok: false, error: 'Respon server tidak valid (HTTP ' + r.status + ').' };
                    });
                });
            }

            // Inisialisasi SortableJS
            if (typeof Sortable !== 'undefined' && list) {
                Sortable.create(list, {
                    handle: '.um-handle',
                    animation: 150,
                    ghostClass: 'um-ghost',
                    onEnd: function () {
                        nomori();
                        tandaiKotor();
                    }
                });
            }

            // Handler tombol naik/turun
            list.addEventListener('click', function (e) {
                var btnUp = e.target.closest('.um-up');
                var btnDown = e.target.closest('.um-down');
                if (!btnUp && !btnDown) return;

                var item = (btnUp || btnDown).closest('.um-item');
                if (!item) return;

                if (btnUp) {
                    var prev = item.previousElementSibling;
                    if (prev) {
                        list.insertBefore(item, prev);
                        nomori();
                        tandaiKotor();
                    }
                } else if (btnDown) {
                    var next = item.nextElementSibling;
                    if (next) {
                        list.insertBefore(next, item);
                        nomori();
                        tandaiKotor();
                    }
                }
            });

            // Tombol Simpan
            btnSimpan.addEventListener('click', function () {
                btnSimpan.disabled = true;
                btnSimpan.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...';

                kirim('simpan_urutan_mapel')
                    .then(function (res) {
                        if (res.ok) {
                            kotor = false;
                            status.hidden = true;
                            pesan('success', res.pesan || 'Urutan mapel berhasil disimpan.');
                        } else {
                            pesan('error', res.error || 'Gagal menyimpan urutan mapel.');
                        }
                    })
                    .catch(function (err) {
                        pesan('error', 'Koneksi gagal: ' + err.message);
                    })
                    .finally(function () {
                        btnSimpan.disabled = !kotor;
                        btnSimpan.innerHTML = '<i class="material-icons-round me-1">save</i> Simpan Urutan';
                    });
            });

            // Tombol Reset ke Abjad
            btnReset.addEventListener('click', function () {
                var konfirmasi = confirm('Urutan mapel kelas ini akan direset kembali ke urutan default abjad. Lanjutkan?');
                if (!konfirmasi) return;

                btnReset.disabled = true;
                kirim('reset_urutan_mapel')
                    .then(function (res) {
                        if (res.ok) {
                            pesan('success', res.pesan || 'Urutan mapel berhasil direset.');
                            window.location.reload();
                        } else {
                            pesan('error', res.error || 'Gagal mereset urutan mapel.');
                            btnReset.disabled = false;
                        }
                    })
                    .catch(function (err) {
                        pesan('error', 'Koneksi gagal: ' + err.message);
                        btnReset.disabled = false;
                    });
            });

            // Tombol Salin ke Kelas Lain
            var btnKonfirmasiSalin = document.getElementById('btnKonfirmasiSalin');
            var pilihKelasTujuan = document.getElementById('pilihKelasTujuan');
            if (btnKonfirmasiSalin && pilihKelasTujuan) {
                btnKonfirmasiSalin.addEventListener('click', function () {
                    var idTujuan = pilihKelasTujuan.value;
                    if (!idTujuan) {
                        alert('Silakan pilih kelas tujuan terlebih dahulu.');
                        return;
                    }

                    btnKonfirmasiSalin.disabled = true;
                    btnKonfirmasiSalin.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyalin...';

                    var body = new URLSearchParams();
                    if (token) body.append('csrf_token', token);
                    body.append('id_kelas_asal', idKelas);
                    body.append('id_kelas_tujuan', idTujuan);
                    body.append('id_tahun', idTahun);

                    fetch('index.php?controller=rapor&method=salin_urutan_mapel', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': token || ''
                        },
                        body: body.toString()
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.ok) {
                            pesan('success', res.pesan);
                            var modal = bootstrap.Modal.getInstance(document.getElementById('modalSalin'));
                            if (modal) modal.hide();
                        } else {
                            pesan('error', res.error || 'Gagal menyalin urutan mapel.');
                        }
                    })
                    .catch(function (err) {
                        pesan('error', 'Koneksi gagal: ' + err.message);
                    })
                    .finally(function () {
                        btnKonfirmasiSalin.disabled = false;
                        btnKonfirmasiSalin.innerHTML = '<i class="material-icons-round text-xs me-1">content_copy</i> Salin Sekarang';
                    });
                });
            }

            // Peringatan jika meninggalkan halaman sebelum menyimpan
            window.addEventListener('beforeunload', function (e) {
                if (kotor) {
                    e.preventDefault();
                    e.returnValue = 'Perubahan urutan mapel belum disimpan. Tinggalkan halaman?';
                }
            });
        })();
    </script>
<?php endif; ?>

<?php include '../app/views/layouts/footer.php'; ?>
