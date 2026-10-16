<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek akses via key
if (!isset($_GET['key']) || $_GET['key'] !== 'SiberRM_svbndks987839432') {
    die('Unauthorized');
}

// --- Kunci anti-tumpang-tindih ---------------------------------------------
// Sebelumnya dua eksekusi berbarengan (cron dobel / klik manual saat cron jalan)
// membuat pesan yang sama dimasukkan dua kali ke antrean -> terkirim dobel.
$__lockBerkas = __DIR__ . '/.lock_' . basename(__FILE__); // dipatok ke folder skrip: path pasti sama untuk cron (CLI) maupun akses web
$__lockFp = @fopen($__lockBerkas, 'c');
if ($__lockFp !== false && !flock($__lockFp, LOCK_EX | LOCK_NB)) {
    echo 'SKIP: ' . basename(__FILE__) . " masih berjalan (lock aktif).\n";
    exit;
}
register_shutdown_function(function () use ($__lockFp) {
    if (is_resource($__lockFp)) {
        flock($__lockFp, LOCK_UN);
        fclose($__lockFp);
    }
});

// Helper antrean WA: pesan disimpan ke tabel outbox_wa, dikirim oleh WA Gateway lokal
require_once __DIR__ . '/_wa_outbox.php';

// --- KONFIGURASI ---
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd';
$idJabatanStruktural = 2; // Khusus Struktural
$debugMode = true;
// --------------------

header('Content-Type: text/plain');
date_default_timezone_set('Asia/Jakarta');

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- AMBIL SETTING DEBUG MODE ---
    $stmtSetting = $pdo->prepare("SELECT status FROM settings WHERE key_setting = 'wa_notif_jurnal_struktural' LIMIT 1");
    $stmtSetting->execute();
    $rowSetting = $stmtSetting->fetch(PDO::FETCH_ASSOC);
    $dbStatus = $rowSetting ? $rowSetting['status'] : 'false';
    $debugMode = ($dbStatus === 'false');

    $today = date('Y-m-d');
    $namaHariIni = strtolower(date('l'));

    echo "=== MODE DEBUG: " . ($debugMode ? "AKTIF (SIMULASI)" : "NON-AKTIF (KIRIM ASLI)") . " ===\n";
    echo "Hari ini: " . date('d-m-Y') . " ($namaHariIni)\n\n";

    // 1. Ambil daftar pegawai struktural yang memiliki jadwal hari ini sesuai tabel 'jadwal'
    $sqlStruktural = "
        SELECT e.id_employe, e.nama, e.no_wa, j.waktu_datang, j.waktu_pulang
        FROM employe e
        JOIN jadwal j ON e.pin = j.id_employee
        WHERE e.id_jabatan = :id_jabatan
          AND LOWER(j.day) = :hari
          AND e.no_wa IS NOT NULL AND e.no_wa != ''
        GROUP BY e.id_employe
    ";

    $stmt = $pdo->prepare($sqlStruktural);
    $stmt->execute([':id_jabatan' => $idJabatanStruktural, ':hari' => $namaHariIni]);
    $pegawaiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pegawaiList)) {
        echo "Tidak ada jadwal struktural untuk hari ini.\n";
        exit;
    }

    foreach ($pegawaiList as $pegawai) {
        // 2. Cek apakah sudah ada entri di tabel 'jurnal_struktural' untuk pegawai ini pada tanggal hari ini
        $stmtCekJurnal = $pdo->prepare("SELECT id_jurnal FROM jurnal_struktural WHERE id_employe = ? AND tanggal = ?");
        $stmtCekJurnal->execute([$pegawai['id_employe'], $today]);
        $sudahIsi = $stmtCekJurnal->fetch();

        echo "--- Memproses: {$pegawai['nama']} (Jam Kerja: {$pegawai['waktu_datang']} - {$pegawai['waktu_pulang']}) ---\n";

        if (!$sudahIsi) {
            // Jika BELUM mengisi jurnal
            $namaClean = trim($pegawai['nama']);
            $pesan = "*Pengingat Jurnal Struktural* ✍️\n\n" .
                "Yth. Bapak/Ibu *{$namaClean}*,\n\n" .
                "Sistem mencatat Anda belum mengisi *Jurnal Struktural* untuk hari ini, " . date('d-m-Y') . ".\n\n" .
                "Sesuai jadwal, jam kerja Anda adalah *{$pegawai['waktu_datang']} s/d {$pegawai['waktu_pulang']}*.\n" .
                "Mohon segera melengkapinya sebelum meninggalkan tugas.\n\n" .
                "Terima kasih,\n*SIBER PPRM*";

            if (!$debugMode) {
                // Antrekan ke tabel outbox_wa; WA Gateway lokal yang mengirim (mode PULL)
                $hasilAntre = wa_enqueue($pdo, $pegawai['no_wa'], $pesan);
                echo "STATUS: " . wa_enqueue_hasil_log($hasilAntre) . PHP_EOL;
            } else {
                echo "STATUS: [SIMULASI] Pesan tidak dikirim ke {$pegawai['no_wa']}\n";
                echo "ISI PESAN:\n$pesan\n";
            }
        } else {
            echo "STATUS: Sudah mengisi jurnal. Selesai.\n";
        }
        echo "------------------------------------------\n";
    }
} catch (PDOException $e) {
    die("FATAL ERROR: " . $e->getMessage());
}

echo "\n===== PROSES SELESAI =====";
