-- =====================================================================
-- Tabel riwayat PD Keluar (peserta didik berhenti / mutasi / meninggal)
-- =====================================================================
-- Tabel ini biasanya dibuat OTOMATIS saat menu "PD Keluar" pertama kali
-- dibuka. Jalankan file ini hanya bila user database tidak punya izin
-- CREATE TABLE.
--
-- Status utama tetap disimpan di `siswa.status_siswa` supaya semua daftar
-- nilai, rapor, dan leger otomatis menyembunyikan siswa tersebut.
-- Tabel ini menyimpan alasan + tanggal + siapa yang mencatat, sehingga
-- status bisa dikembalikan (dibatalkan) tanpa kehilangan riwayat.
-- =====================================================================

CREATE TABLE IF NOT EXISTS siswa_keluar (
    id_keluar INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    id_tahun INT NULL,
    status VARCHAR(30) NOT NULL,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(255) NULL,
    dicatat_oleh VARCHAR(100) NULL,
    dibuat_pada DATETIME NOT NULL,
    dibatalkan_oleh VARCHAR(100) NULL,
    dibatalkan_pada DATETIME NULL,
    KEY idx_siswa (id_siswa),
    KEY idx_status (status),
    KEY idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
