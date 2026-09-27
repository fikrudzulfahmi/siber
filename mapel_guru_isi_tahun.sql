-- ============================================================================
-- SIBER — SQL: Rapikan baris mapel_guru TANPA TAHUN (id_tahun_pelajaran = 0)
--
-- MASALAH: 83 baris penugasan mengajar tidak punya tahun pelajaran. Akibatnya
-- dropdown "Pilih Mapel & Tingkat" di menu Perangkat Saya (dan Data Mapel)
-- menampilkan pilihan dari tahun ajaran lama.
--
-- SOLUSI (TANPA MENGHAPUS APA PUN):
--   Hanya baris tanpa tahun yang TIDAK punya padanan di tahun aktif yang diisi
--   tahun aktif. Baris tanpa tahun yang sudah punya padanan di tahun aktif
--   DIBIARKAN apa adanya — kode aplikasi sudah mengabaikannya, dan mengisinya
--   justru akan membuat baris kembar di tahun aktif.
--
-- Aman dijalankan berulang (idempoten): setelah dijalankan, baris sasaran sudah
-- punya tahun sehingga tidak lagi memenuhi syarat UPDATE.
--
-- CARA PAKAI: phpMyAdmin -> tab SQL -> tempel seluruh isi -> Go
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. LIHAT KONDISI AWAL (jalankan dulu, catat angkanya)
-- ---------------------------------------------------------------------------
-- Rekap baris per tahun
SELECT id_tahun_pelajaran, COUNT(*) AS jumlah_baris
FROM mapel_guru
GROUP BY id_tahun_pelajaran
ORDER BY id_tahun_pelajaran;

-- Baris tanpa tahun yang TIDAK punya padanan di tahun aktif (calon sasaran)
SET @thn_aktif := (SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1);
SELECT @thn_aktif AS tahun_aktif;

SELECT mg.id_mapel_guru, mg.id_guru, e.nama, m.nama_mapel, k.kelas, k.tingkat
FROM mapel_guru mg
JOIN mapel m ON m.id_mapel = mg.id_mapel            -- hanya yang mapelnya valid
JOIN kelas k ON k.id_kelas = mg.id_kelas
LEFT JOIN employe e ON e.id_employe = mg.id_guru
WHERE (mg.id_tahun_pelajaran = 0 OR mg.id_tahun_pelajaran IS NULL)
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT * FROM mapel_guru) x
      WHERE x.id_guru  = mg.id_guru
        AND x.id_mapel = mg.id_mapel
        AND x.id_kelas = mg.id_kelas
        AND x.id_tahun_pelajaran = @thn_aktif
  );

-- ---------------------------------------------------------------------------
-- 2. ISI TAHUN untuk baris sasaran (bukan hapus — datanya tetap utuh)
-- ---------------------------------------------------------------------------
UPDATE mapel_guru mg
JOIN mapel m ON m.id_mapel = mg.id_mapel
SET mg.id_tahun_pelajaran = (SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1)
WHERE (mg.id_tahun_pelajaran = 0 OR mg.id_tahun_pelajaran IS NULL)
  AND NOT EXISTS (
      SELECT 1 FROM (SELECT * FROM mapel_guru) x
      WHERE x.id_guru  = mg.id_guru
        AND x.id_mapel = mg.id_mapel
        AND x.id_kelas = mg.id_kelas
        AND x.id_tahun_pelajaran = (SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1)
  );

-- ---------------------------------------------------------------------------
-- 3. PERIKSA HASIL
-- ---------------------------------------------------------------------------
SELECT id_tahun_pelajaran, COUNT(*) AS jumlah_baris
FROM mapel_guru
GROUP BY id_tahun_pelajaran
ORDER BY id_tahun_pelajaran;

-- Sisa baris tanpa tahun (seharusnya 0 untuk kategori sasaran di atas)
SELECT COUNT(*) AS sisa_tanpa_tahun
FROM mapel_guru
WHERE id_tahun_pelajaran = 0 OR id_tahun_pelajaran IS NULL;

-- ---------------------------------------------------------------------------
-- 4. TEMUAN LAIN (perlu perbaikan manual di menu Data Mapel, BUKAN dihapus)
-- ---------------------------------------------------------------------------
-- 23 baris mapel_guru merujuk mapel yang tidak ada di tabel `mapel`
-- (8 di antaranya ada di TAHUN AKTIF: id_mapel 144 & 145).
-- Baris seperti ini otomatis TIDAK muncul di dropdown, sehingga guru kehilangan
-- pilihan mapel-nya tanpa pesan apa pun.
SELECT mg.id_mapel, COUNT(*) AS jumlah_baris,
       MIN(mg.id_tahun_pelajaran) AS tahun_min, MAX(mg.id_tahun_pelajaran) AS tahun_max
FROM mapel_guru mg
LEFT JOIN mapel m ON m.id_mapel = mg.id_mapel
WHERE m.id_mapel IS NULL
GROUP BY mg.id_mapel
ORDER BY jumlah_baris DESC;

-- Detail barisnya (untuk diperbaiki mapelnya lewat menu Data Mapel)
SELECT mg.id_mapel_guru, mg.id_mapel, mg.id_guru, e.nama, k.kelas, mg.id_tahun_pelajaran
FROM mapel_guru mg
LEFT JOIN mapel m ON m.id_mapel = mg.id_mapel
LEFT JOIN employe e ON e.id_employe = mg.id_guru
LEFT JOIN kelas k ON k.id_kelas = mg.id_kelas
WHERE m.id_mapel IS NULL
ORDER BY mg.id_tahun_pelajaran DESC, mg.id_mapel;

-- ---------------------------------------------------------------------------
-- 5. PEMBATALAN (OPSIONAL, dikomentari)
-- ---------------------------------------------------------------------------
-- Hanya perlu bila Anda ingin mengembalikan baris sasaran ke kondisi tanpa tahun.
-- Ganti <ID> dengan nilai id_mapel_guru dari hasil query bagian 1.
-- UPDATE mapel_guru SET id_tahun_pelajaran = 0 WHERE id_mapel_guru IN (<ID>);
