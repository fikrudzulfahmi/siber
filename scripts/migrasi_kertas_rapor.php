<?php
/**
 * Migrasi kolom `kertas_rapor` dan `kertas_peringkat` pada tabel `rapor_setting`.
 *
 *   php scripts/migrasi_kertas_rapor.php            # tambahkan kolom bila belum ada
 *   php scripts/migrasi_kertas_rapor.php --cek      # hanya melaporkan status
 *
 * Memungkinkan pemilihan ukuran kertas (A4 vs F4) per sesi rapor untuk lembar
 * rapor siswa dan daftar peringkat secara independen. Default: A4.
 *
 * Padanan SQL bila ingin dieksekusi lewat phpMyAdmin:
 *
 *   ALTER TABLE rapor_setting
 *     ADD COLUMN kertas_rapor VARCHAR(10) NOT NULL DEFAULT 'A4' AFTER is_locked,
 *     ADD COLUMN kertas_peringkat VARCHAR(10) NOT NULL DEFAULT 'A4' AFTER kertas_rapor;
 */

require_once __DIR__ . '/../config/database.php';

$cekSaja = in_array('--cek', $argv ?? [], true);

$pdo = (new Database())->getConnection();
if (!$pdo) {
    fwrite(STDERR, "Gagal terhubung ke database.\n");
    exit(1);
}

$dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

$kolomRaporAda = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rapor_setting' AND COLUMN_NAME = 'kertas_rapor'"
)->fetchColumn();

$kolomPeringkatAda = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rapor_setting' AND COLUMN_NAME = 'kertas_peringkat'"
)->fetchColumn();

echo "Database                 : {$dbName}\n";
echo "Kolom kertas_rapor       : " . ($kolomRaporAda ? 'ada' : 'BELUM ADA') . "\n";
echo "Kolom kertas_peringkat   : " . ($kolomPeringkatAda ? 'ada' : 'BELUM ADA') . "\n";

if ($cekSaja) {
    echo "\nMode --cek: tidak ada perubahan.\n";
    exit(0);
}

if ($kolomRaporAda && $kolomPeringkatAda) {
    echo "\nTidak ada yang perlu diubah. Kolom pengaturan kertas sudah tersedia.\n";
    exit(0);
}

try {
    if (!$kolomRaporAda) {
        $pdo->exec("ALTER TABLE rapor_setting ADD COLUMN kertas_rapor VARCHAR(10) NOT NULL DEFAULT 'A4' AFTER is_locked");
        echo "\n+ Kolom rapor_setting.kertas_rapor ditambahkan (default: A4).\n";
    }
    if (!$kolomPeringkatAda) {
        $pdo->exec("ALTER TABLE rapor_setting ADD COLUMN kertas_peringkat VARCHAR(10) NOT NULL DEFAULT 'A4' AFTER kertas_rapor");
        echo "+ Kolom rapor_setting.kertas_peringkat ditambahkan (default: A4).\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\nGAGAL: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nSelesai. Pengaturan ukuran kertas (A4/F4) sekarang dapat diatur di menu Setting Rapor.\n";
