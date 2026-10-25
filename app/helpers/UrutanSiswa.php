<?php
/**
 * Urutan tampil siswa.
 *
 * Urutan manual disimpan di `ploting_siswa.urutan` — per kelas per tahun
 * pelajaran, karena satu siswa bisa punya urutan berbeda di tiap kelas/tahun.
 * Semua query daftar siswa memakai `urutan_siswa_order()` supaya urutannya
 * seragam; siswa yang belum punya urutan (NULL) tetap di belakang dan
 * diurutkan abjad seperti sebelumnya.
 */

require_once __DIR__ . '/SiswaAktif.php';

/**
 * Ekspresi ORDER BY (tanpa kata "ORDER BY") untuk daftar siswa.
 *
 * @param string $ps alias tabel ploting_siswa pada query pemanggil
 * @param string $s  alias tabel siswa pada query pemanggil
 */
function urutan_siswa_order(string $ps = 'ps', string $s = 's'): string
{
    return "($ps.urutan IS NULL) ASC, $ps.urutan ASC, $s.nama_siswa ASC";
}
