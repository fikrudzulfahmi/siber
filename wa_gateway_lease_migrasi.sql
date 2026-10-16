-- ============================================================================
--  SIBER x WA GATEWAY LOKAL — MIGRASI KLAIM ATOMIK & SEWA ANTREAN
--  Jalankan SEKALI lewat phpMyAdmin hosting (pilih database SIBER) atau:
--    mysql -u <user> -p <nama_db> < wa_gateway_lease_migrasi.sql
--
--  Aman dijalankan berulang (idempoten) — setiap langkah memeriksa dulu apakah
--  kolom/index-nya sudah ada.
--
--  Yang ditambahkan pada tabel `outbox_wa` (struktur lama tidak diubah/dihapus):
--    claimed_at DATETIME NULL  -> kapan baris diklaim gateway (dasar LEASE)
--    attempt    TINYINT        -> berapa kali baris sudah diklaim
--    INDEX (status, id)        -> mempercepat klaim & penghitungan antrean
--    INDEX (nomor)             -> mempercepat pengecekan anti-duplikat enqueue
--
--  Kenapa perlu: jobs.php sekarang MENGKLAIM baris (pending -> in_queue) saat
--  diserahkan ke gateway. Tanpa claimed_at, baris yang mandek tidak bisa
--  dibedakan dari baris yang baru diklaim, sehingga requeue.php tidak bisa
--  menyelamatkannya. Tanpa `attempt`, satu baris rusak bisa berputar selamanya.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Kolom claimed_at
-- ---------------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbox_wa'
                AND COLUMN_NAME = 'claimed_at');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `outbox_wa` ADD COLUMN `claimed_at` DATETIME NULL DEFAULT NULL AFTER `created_at`',
  'SELECT ''claimed_at sudah ada (dilewati)'' AS langkah1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------------
-- 2. Kolom attempt
-- ---------------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbox_wa'
                AND COLUMN_NAME = 'attempt');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `outbox_wa` ADD COLUMN `attempt` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `claimed_at`',
  'SELECT ''attempt sudah ada (dilewati)'' AS langkah2');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------------
-- 3. Index (status, id) — klaim & rekap jadi cepat
-- ---------------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbox_wa'
                AND INDEX_NAME = 'idx_status_id');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `outbox_wa` ADD INDEX `idx_status_id` (`status`, `id`)',
  'SELECT ''idx_status_id sudah ada (dilewati)'' AS langkah3');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------------
-- 4. Index (nomor) — pengecekan anti-duplikat saat enqueue
-- ---------------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbox_wa'
                AND INDEX_NAME = 'idx_nomor');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `outbox_wa` ADD INDEX `idx_nomor` (`nomor`)',
  'SELECT ''idx_nomor sudah ada (dilewati)'' AS langkah4');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------------
-- 5. PEMERIKSAAN setelah migrasi: harus tampil 6 kolom + index di atas
-- ---------------------------------------------------------------------------
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbox_wa'
 ORDER BY ORDINAL_POSITION;

SHOW INDEX FROM `outbox_wa`;

-- ---------------------------------------------------------------------------
-- 6. OPSIONAL (pilih salah satu, jalankan hanya bila memang diinginkan)
-- ---------------------------------------------------------------------------

-- 6a. Kalau pesan LAMA yang belum terkirim (April–Juli 2026) MEMANG mau dikirim,
--     "majukan" tanggalnya supaya melewati batas WA_MULAI_DARI dan ikut tertarik:
-- UPDATE outbox_wa
--    SET created_at = NOW()
--  WHERE status = 'pending' AND created_at < '2026-09-28 00:00:00';

-- 6b. Kalau pesan lama itu TIDAK perlu dikirim, tutup saja statusnya:
-- UPDATE outbox_wa
--    SET status = 'failed', claimed_at = NULL
--  WHERE status IN ('pending','in_queue') AND created_at < '2026-09-28 00:00:00';

-- 6c. Bersihkan sisa klaim mandek sebelum menyalakan gateway (opsional):
-- UPDATE outbox_wa SET status = 'pending', claimed_at = NULL, attempt = 0
--  WHERE status = 'in_queue';
