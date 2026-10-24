<?php
/**
 * Filter status siswa untuk semua daftar siswa per kelas (nilai, leger, rapor).
 *
 * Siswa yang dikeluarkan dari kelas lewat menu Ploting (tombol "Luluskan")
 * TIDAK dihapus dari tabel `siswa`; hanya `status_siswa` yang diubah menjadi
 * 'lulus' dan baris `ploting_siswa` dibiarkan. Akibatnya, query yang hanya
 * memakai ploting_siswa masih menampilkan siswa tersebut di daftar nilai,
 * leger, dan input rapor, serta saat membuat kategori nilai baru.
 *
 * Aturan: setiap query yang menampilkan daftar siswa dalam satu kelas WAJIB
 * menambahkan siswa_aktif_sql() supaya isinya sama dengan halaman Ploting.
 */

if (!defined('SISWA_STATUS_NONAKTIF')) {
    // Status (lowercase) yang berarti siswa sudah tidak aktif di kelas.
    define('SISWA_STATUS_NONAKTIF', ['lulus', 'keluar', 'pindah', 'mutasi', 'berhenti', 'drop out']);
}

if (!function_exists('siswa_aktif_sql')) {
    /**
     * Potongan kondisi SQL: hanya siswa yang masih aktif di kelas.
     *
     * @param string $alias Alias tabel siswa pada query (default "s").
     */
    function siswa_aktif_sql(string $alias = 's'): string
    {
        $daftar = array_map(static function ($status) {
            return "'" . str_replace("'", "''", strtolower(trim((string) $status))) . "'";
        }, SISWA_STATUS_NONAKTIF);

        return "(($alias.status_siswa IS NULL) OR (LOWER(TRIM($alias.status_siswa)) NOT IN (" . implode(', ', $daftar) . ")))";
    }
}
