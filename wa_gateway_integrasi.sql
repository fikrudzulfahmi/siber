-- ============================================================================
--  SIBER  x  WA GATEWAY LOKAL  —  integrasi antrean WhatsApp
--  Berkas ini AMAN dijalankan berulang (idempoten) di database produksi.
--  Jalankan lewat phpMyAdmin hosting, atau:
--    mysql -u <user> -p <nama_db> < wa_gateway_integrasi.sql
-- ============================================================================
--
--  Ringkas: cronjob SIBER (reminder.php, rekap_walas.php, reminder_terlambat.php,
--  rekap_terlambat.php, reminder_jurnal_struktural.php) berhenti mengirim lewat
--  Fonnte dan beralih MENYIMPAN pesan ke tabel `outbox_wa` (status 'pending').
--  Gateway WA lokal menariknya lewat /wa-gateway/jobs.php dan melaporkan status
--  lewat /wa-gateway/ack.php.
--
--  TIDAK ADA perubahan skema: tabel outbox_wa sudah ada sejak sebelumnya.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- 1. Prasyarat: tabel outbox_wa harus ada dengan bentuk seperti ini.
--    (CREATE TABLE IF NOT EXISTS = tidak melakukan apa pun bila sudah ada)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `outbox_wa` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `nomor`      VARCHAR(20) NOT NULL,
  `pesan`      TEXT NOT NULL,
  `status`     ENUM('pending','in_queue','sent','failed') DEFAULT 'pending',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------------
-- 2. PEMERIKSAAN — jalankan SELECT di bawah untuk melihat kondisi antrean
-- ---------------------------------------------------------------------------
-- 2a. Struktur kolom yang benar (harus terlihat 5 baris)
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'outbox_wa';

-- 2b. Jumlah pesan per status sebelum integrasi diaktifkan
SELECT status, COUNT(*) AS jumlah, MIN(created_at) AS tertua, MAX(created_at) AS terbaru
  FROM outbox_wa GROUP BY status;

-- 2c. Pesan lama yang belum pernah terkirim (perkiraan: 68 baris dari April–Juli 2026).
--     Pesan ini TIDAK akan ditarik gateway karena endpoint jobs.php hanya menarik
--     created_at >= '2026-09-28 00:00:00' (lihat public/wa-gateway/_config.php).
SELECT COUNT(*) AS pesan_lama_terbengkalai
  FROM outbox_wa
 WHERE status = 'pending' AND created_at < '2026-09-28 00:00:00';


-- ---------------------------------------------------------------------------
-- 3. OPSIONAL — tutup status 68 pesan lama itu supaya tabelnya bersih.
--    Ini hanya merapikan; pengiriman sudah dicegah oleh filter tanggal di atas.
--    Jalankan HANYA bila kamu setuju pesan lama itu tidak perlu dikirim.
--    (Tidak ada data yang dihapus — hanya statusnya diubah.)
-- ---------------------------------------------------------------------------
UPDATE outbox_wa
   SET status = 'failed'
 WHERE status = 'pending'
   AND created_at < '2026-09-28 00:00:00';

-- Verifikasi hasil langkah 3 (harus: failed bertambah, pending lama menjadi 0)
SELECT status, COUNT(*) AS jumlah FROM outbox_wa GROUP BY status;

-- ROLLBACK langkah 3 (buka komentar bila ingin mengembalikan ke 'pending'):
-- UPDATE outbox_wa SET status = 'pending'
--  WHERE status = 'failed' AND created_at < '2026-09-28 00:00:00' AND pesan IS NOT NULL;


-- ---------------------------------------------------------------------------
-- 4. OPSIONAL — status lebih rinci (centang 2 & dibaca) di dalam SIBER.
--    Saat ini gateway MEMADATKAN 'delivered' dan 'read' menjadi 'sent' karena
--    enum di atas hanya punya 4 nilai. Bila ingin SIBER ikut mencatat
--    "sampai" (centang 2) dan "dibaca" (centang biru), jalankan ALTER ini,
--    lalu ubah pemetaan di public/wa-gateway/ack.php:
--        'delivered' => 'sampai',
--        'read'      => 'dibaca',
--    (tidak wajib — rekap harian di dashboard gateway tetap mencatat detailnya)
-- ---------------------------------------------------------------------------
-- ALTER TABLE `outbox_wa`
--   MODIFY COLUMN `status` ENUM('pending','in_queue','sent','sampai','dibaca','failed')
--   DEFAULT 'pending';


-- ---------------------------------------------------------------------------
-- 5. PEMERIKSAAN AKHIR — bukti gateway benar-benar sudah menarik pesan:
--    baris 'in_queue' = sudah ditarik gateway (menunggu dikirim)
--    baris 'sent'     = sudah terkirim ke WhatsApp
--    baris 'failed'   = gagal setelah 3 kali percobaan (lihat dashboard gateway)
-- ---------------------------------------------------------------------------
SELECT id, nomor, LEFT(pesan, 45) AS cuplikan, status, created_at
  FROM outbox_wa
 WHERE created_at >= '2026-09-28 00:00:00'
 ORDER BY id DESC
 LIMIT 20;
