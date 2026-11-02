<?php
/**
 * Migrasi kolom `urutan` pada tabel `mapel` untuk fitur urutan mapel di rapor.
 *
 *   php scripts/migrasi_urutan_mapel.php            # tambahkan kolom bila belum ada
 *   php scripts/migrasi_urutan_mapel.php --cek      # hanya melaporkan status
 *
 * Aman dijalankan berkali-kali (idempotent): hanya menambah kolom dan index
 * kalau belum ada, tidak menyentuh data mapel/nilai yang sudah ada.
 *
 * Padanan SQL bila ingin dieksekusi lewat phpMyAdmin:
 *
 *   ALTER TABLE mapel
 *     ADD COLUMN urutan INT NULL DEFAULT 0 AFTER tingkat_mapel,
 *     ADD INDEX idx_mapel_urutan (tingkat_mapel, urutan);
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
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mapel' AND COLUMN_NAME = 'urutan'"
)->fetchColumn();

$indexAda = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mapel' AND INDEX_NAME = 'idx_mapel_urutan'"
)->fetchColumn();

echo "Database      : {$dbName}\n";
echo "Kolom urutan  : " . ($kolomAda ? 'ada' : 'BELUM ADA') . "\n";
echo "Index         : " . ($indexAda ? 'ada' : 'BELUM ADA') . "\n";

if ($cekSaja) {
    echo "\nMode --cek: tidak ada perubahan.\n";
    exit(0);
}

if ($kolomAda && $indexAda) {
    echo "\nTidak ada yang perlu diubah. Kolom dan index sudah tersedia.\n";
    exit(0);
}

try {
    if (!$kolomAda) {
        $pdo->exec("ALTER TABLE mapel ADD COLUMN urutan INT NULL DEFAULT 0 AFTER tingkat_mapel");
        echo "\n+ Kolom mapel.urutan ditambahkan.\n";
    }
    if (!$indexAda) {
        $pdo->exec("ALTER TABLE mapel ADD INDEX idx_mapel_urutan (tingkat_mapel, urutan)");
        echo "+ Index idx_mapel_urutan dibuat.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\nGAGAL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nSelesai. Nomor urut mapel sekarang dapat diatur via menu Mata Pelajaran.\n";
