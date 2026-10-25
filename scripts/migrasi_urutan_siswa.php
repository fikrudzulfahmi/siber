<?php
/**
 * Migrasi 1 kolom untuk fitur "Urutkan Siswa".
 *
 *   php scripts/migrasi_urutan_siswa.php            # tambahkan kolom bila belum ada
 *   php scripts/migrasi_urutan_siswa.php --cek      # hanya melaporkan status
 *
 * Aman dijalankan berkali-kali (idempotent): hanya menambah kolom dan index
 * kalau belum ada, tidak menyentuh data siswa/nilai.
 *
 * Padanan SQL bila ingin lewat phpMyAdmin:
 *
 *   ALTER TABLE ploting_siswa
 *     ADD COLUMN urutan INT NULL DEFAULT NULL AFTER id_tahun,
 *     ADD INDEX idx_ploting_urut (id_kelas, id_tahun, urutan);
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
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ploting_siswa' AND COLUMN_NAME = 'urutan'"
)->fetchColumn();

$indexAda = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ploting_siswa' AND INDEX_NAME = 'idx_ploting_urut'"
)->fetchColumn();

echo "Database      : {$dbName}\n";
echo "Kolom urutan  : " . ($kolomAda ? 'ada' : 'BELUM ADA') . "\n";
echo "Index         : " . ($indexAda ? 'ada' : 'BELUM ADA') . "\n";

if ($cekSaja) {
    echo "\nMode --cek: tidak ada perubahan.\n";
    exit(0);
}

if ($kolomAda && $indexAda) {
    echo "\nTidak ada yang perlu diubah.\n";
    exit(0);
}

try {
    if (!$kolomAda) {
        $pdo->exec("ALTER TABLE ploting_siswa ADD COLUMN urutan INT NULL DEFAULT NULL AFTER id_tahun");
        echo "\n+ Kolom ploting_siswa.urutan ditambahkan.\n";
    }
    if (!$indexAda) {
        $pdo->exec("ALTER TABLE ploting_siswa ADD INDEX idx_ploting_urut (id_kelas, id_tahun, urutan)");
        echo "+ Index idx_ploting_urut dibuat.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\nGAGAL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nSelesai. Buka menu Master > Urutkan Siswa untuk mulai mengatur urutan.\n";
