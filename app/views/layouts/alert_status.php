<?php
/**
 * Alert status dari redirect controller (?status=...).
 * Dipakai view yang sebelumnya mengabaikan parameter status sehingga
 * pengguna tidak tahu kenapa aksinya gagal.
 */
$siber_status = $_GET['status'] ?? '';
$siber_pesan  = [
    'no_student' => ['warning', 'Belum ada siswa aktif yang bisa dipilih. Periksa plotting siswa pada kelas dan tahun pelajaran aktif.'],
    'error'      => ['danger', 'Data gagal diproses. Periksa kembali isian Anda, atau hubungi administrator bila masalah berlanjut.'],
    'sukses'     => ['success', 'Data berhasil disimpan.'],
];
if (isset($siber_pesan[$siber_status])):
    [$siber_jenis, $siber_teks] = $siber_pesan[$siber_status];
    ?>
    <div class="alert alert-<?= sec_e($siber_jenis) ?> text-white alert-dismissible fade show" role="alert">
        <?= sec_e($siber_teks) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>
