-- ============================================================
-- Tabel Activity Log (SIBER)
-- Import file ini ke database produksi sebelum memakai menu
-- Activity Log.  Contoh via phpMyAdmin: Import > pilih file ini.
-- Atau via CLI:  mysql -u USER -p NAMADB < activity_log.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id_log` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `id_employe` INT(11) DEFAULT NULL COMMENT 'ID pegawai (id_employe) pelaku',
  `nama_user` VARCHAR(100) DEFAULT NULL COMMENT 'Nama pelaku saat aktivitas terjadi',
  `level_user` VARCHAR(100) DEFAULT NULL COMMENT 'Level/role pelaku',
  `aktivitas` VARCHAR(255) NOT NULL COMMENT 'Deskripsi aktivitas, mis. Tambah Data Siswa',
  `modul` VARCHAR(100) DEFAULT NULL COMMENT 'Nama modul/halaman, mis. Data Siswa',
  `aksi` VARCHAR(30) DEFAULT NULL COMMENT 'Kategori: Buka/Tambah/Ubah/Hapus/Login/Logout/Cetak/Export/Lainnya',
  `controller` VARCHAR(100) DEFAULT NULL,
  `method` VARCHAR(100) DEFAULT NULL,
  `http_method` VARCHAR(10) DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `browser` VARCHAR(50) DEFAULT NULL,
  `os` VARCHAR(50) DEFAULT NULL,
  `device` VARCHAR(20) DEFAULT NULL COMMENT 'Desktop/Mobile/Tablet',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_id_employe` (`id_employe`),
  KEY `idx_aksi` (`aksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
