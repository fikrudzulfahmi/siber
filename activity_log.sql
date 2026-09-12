-- ============================================================================
-- SIBER — SQL UNTUK SERVER: Tabel Activity Log
-- Dibuat untuk fitur "Activity Log" (aktivitas, user, waktu, perangkat).
--
-- CARA PAKAI (pilih salah satu):
--   A. phpMyAdmin : pilih database -> menu "Import" -> pilih file ini -> Go
--   B. Tab "SQL"  : buka file ini, copy seluruh isinya, tempel, lalu Go
--   C. CLI/SSH    : mysql -u USER -p NAMA_DATABASE < activity_log.sql
--
-- Aman dijalankan berulang kali (IF NOT EXISTS) — tidak menghapus data.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. TABEL UTAMA
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id_log`     BIGINT(20)   NOT NULL AUTO_INCREMENT,
  `id_employe` INT(11)      DEFAULT NULL  COMMENT 'ID pegawai (id_employe) pelaku aktivitas',
  `nama_user`  VARCHAR(100) DEFAULT NULL  COMMENT 'Nama pelaku saat aktivitas terjadi',
  `level_user` VARCHAR(100) DEFAULT NULL  COMMENT 'Level/role pelaku, mis. Administrator',
  `aktivitas`  VARCHAR(255) NOT NULL      COMMENT 'Deskripsi aktivitas, mis. Tambah Data Siswa',
  `modul`      VARCHAR(100) DEFAULT NULL  COMMENT 'Nama modul/halaman, mis. Data Siswa',
  `aksi`       VARCHAR(30)  DEFAULT NULL  COMMENT 'Kategori: Buka/Akses Data/Tambah/Ubah/Hapus/Login/Logout/Cetak/Export/Lainnya',
  `controller` VARCHAR(100) DEFAULT NULL  COMMENT 'Controller yang diakses',
  `method`     VARCHAR(100) DEFAULT NULL  COMMENT 'Method/aksi pada controller',
  `http_method` VARCHAR(10) DEFAULT NULL  COMMENT 'GET / POST',
  `url`        VARCHAR(500) DEFAULT NULL  COMMENT 'URL lengkap yang diakses',
  `ip_address` VARCHAR(45)  DEFAULT NULL  COMMENT 'IP klien (mendukung IPv6)',
  `user_agent` VARCHAR(500) DEFAULT NULL  COMMENT 'User-Agent mentah dari browser',
  `browser`    VARCHAR(50)  DEFAULT NULL  COMMENT 'Chrome / Firefox / Safari / Edge / Opera / dll',
  `os`         VARCHAR(50)  DEFAULT NULL  COMMENT 'Windows 10/11 / Android / iOS / macOS / Linux / dll',
  `device`     VARCHAR(20)  DEFAULT NULL  COMMENT 'Desktop / Mobile / Tablet',
  `created_at` DATETIME     NOT NULL      COMMENT 'Waktu aktivitas (waktu server)',
  PRIMARY KEY (`id_log`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_id_employe` (`id_employe`),
  KEY `idx_aksi` (`aksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 2. VERIFIKASI — pastikan tabel sudah terbentuk
-- ---------------------------------------------------------------------------
-- Jalankan query ini; harus muncul 1 baris dengan nama 'activity_log'.
SELECT TABLE_NAME, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'activity_log';

-- Cek struktur kolom (harus ada 17 kolom termasuk browser, os, device).
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'activity_log'
ORDER BY ORDINAL_POSITION;

-- ---------------------------------------------------------------------------
-- 3. CEK PRASYARAT FITUR TERKAIT (harus sudah ada, hanya untuk memastikan)
-- ---------------------------------------------------------------------------
-- Fitur "siswa aktif / lulus" dan "ploting" memakai kolom & tabel berikut.
-- Jalankan untuk memastikan semuanya sudah ada di server.
SELECT 'kolom siswa.status_siswa' AS objek, COUNT(*) AS ada
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'status_siswa'
UNION ALL
SELECT 'kolom siswa.tahun_lulus', COUNT(*)
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'tahun_lulus'
UNION ALL
SELECT 'tabel ploting_siswa', COUNT(*)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ploting_siswa';

-- Jika salah satu hasilnya 0, jalankan blok berikut (hapus tanda komentar dulu):
-- ALTER TABLE `siswa`
--   ADD COLUMN `status_siswa` VARCHAR(20) DEFAULT NULL,
--   ADD COLUMN `tahun_lulus` VARCHAR(20) DEFAULT NULL;

-- ---------------------------------------------------------------------------
-- 4. PEMBERSIHAN (OPSIONAL — jalankan hanya bila perlu, ini menghapus data)
-- ---------------------------------------------------------------------------
-- Hapus log lebih tua dari 90 hari:
-- DELETE FROM `activity_log` WHERE `created_at` < (NOW() - INTERVAL 90 DAY);

-- Hapus SELURUH isi log (tabel tetap ada):
-- TRUNCATE TABLE `activity_log`;

-- Hapus tabel (rollback total fitur):
-- DROP TABLE IF EXISTS `activity_log`;
