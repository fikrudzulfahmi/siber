-- ============================================================
--  PERBAIKAN & PEMULIHAN ANTREAN WA SIBER
--  Database : u607305378_siber
--  Jalankan di phpMyAdmin hosting bila antrean mandek di 'in_queue'.
-- ============================================================

-- 0. Intip kondisi saat ini (aman, tidak mengubah data)
SELECT status, COUNT(*) AS jumlah, MIN(created_at) AS tertua, MAX(created_at) AS terbaru
  FROM outbox_wa
 GROUP BY status;

-- 1. Tutup klaim mandek lawas sebagai 'failed' agar tidak terkirim serentak
--    saat engine dihidupkan lagi (hindari spam ratusan notifikasi basi).
--    Sesuaikan tanggal bila perlu (contoh: sebelum hari ini).
UPDATE outbox_wa
   SET status = 'failed'
 WHERE status = 'in_queue'
   AND created_at < CURDATE();

-- 2. Pastikan semua saklar notifikasi cron aktif ('true' = kirim asli)
--    Skrip cron lama menganggap baris setting yang hilang sebagai MODE SIMULASI.
INSERT INTO settings (key_setting, status) VALUES
  ('wa_notif_jurnal',            'true'),
  ('wa_notif_terlambat',         'true'),
  ('wa_notif_rekap_terlambat',   'true')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- 3. Verifikasi status saklar
SELECT key_setting, status
  FROM settings
 WHERE key_setting LIKE 'wa_notif%';

-- 4. Verifikasi antrean (jalankan 5-10 menit setelah engine baru aktif)
SELECT status, COUNT(*) AS jumlah, MAX(created_at) AS terbaru
  FROM outbox_wa
 GROUP BY status;
