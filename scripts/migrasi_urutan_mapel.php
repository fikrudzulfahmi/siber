<?php
/**
 * Migrasi kolom `urutan` pada tabel `mapel_guru` untuk fitur "Urutan Mapel Rapor".
 *
 *   php scripts/migrasi_urutan_mapel.php            # tambahkan kolom bila belum ada
 *   php scripts/migrasi_urutan_mapel.php --cek      # hanya melaporkan status
 *
 * Kolom disimpan pada tabel `mapel_guru` per kelas & per tahun pelajaran,
 * sehingga urutan mapel kelas MTs, MA fase E/F, dan jurusan (IPA/IPS)
 * sepenuhnya independen.
 *
 * Padanan SQL bila ingin dieksekusi lewat phpMyAdmin:
 *
 *   ALTER TABLE mapel_guru
 *     ADD COLUMN urutan INT NULL DEFAULT 0 AFTER id_tahun_pelajaran,
 *     ADD INDEX idx_mg_urutan (id_kelas, id_tahun_pelajaran, urutan);
 */

require_once __DIR__ . '/../config/database.php';

$cekSaja = in_array('--cek', $argv ?? [], true);

$pdo = (new Database())->getConnection();
if (!$pdo) {
    fwrite(STDERR, "Gagal terhubung ke database.\n");
    exit(1);
}

$dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

$kolomAda = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mapel_guru' AND COLUMN_NAME = 'urutan'"
)->fetchColumn();

$indexAda = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mapel_guru' AND INDEX_NAME = 'idx_mg_urutan'"
)->fetchColumn();

echo "Database      : {$dbName}\n";
echo "Kolom urutan  : " . ($kolomAda ? 'ada' : 'BELUM ADA') . "\n";
echo "Index         : " . ($indexAda ? 'ada' : 'BELUM ADA') . "\n";

if ($cekSaja) {
    echo "\nMode --cek: tidak ada perubahan.\n";
    exit(0);
}

if ($kolomAda && $indexAda) {
    echo "\nTidak ada yang perlu diubah. Kolom mapel_guru.urutan sudah tersedia.\n";
    exit(0);
}

try {
    if (!$kolomAda) {
        $pdo->exec("ALTER TABLE mapel_guru ADD COLUMN urutan INT NULL DEFAULT 0 AFTER id_tahun_pelajaran");
        echo "\n+ Kolom mapel_guru.urutan ditambahkan.\n";
    }
    if (!$indexAda) {
        $pdo->exec("ALTER TABLE mapel_guru ADD INDEX idx_mg_urutan (id_kelas, id_tahun_pelajaran, urutan)");
        echo "+ Index idx_mg_urutan dibuat.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\nGAGAL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nSelesai. Urutan mapel sekarang dapat diatur di menu Layanan Rapor > Urutan Mapel Rapor.\n";
