<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek akses via key
if (!isset($_GET['key']) || $_GET['key'] !== 'SiberRM_svbndks987839432') {
    die('Unauthorized');
}

// --- KONFIGURASI ---
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd';
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV';
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
                // Pengiriman asli via Fonnte
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.fonnte.com/send',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => ['target' => $pegawai['no_wa'], 'message' => $pesan],
                    CURLOPT_HTTPHEADER => ["Authorization: $fonnteToken"],
                ]);
                $response = curl_exec($curl);
                curl_close($curl);
                echo "STATUS: Pesan dikirim ke {$pegawai['no_wa']}. Respon: $response\n";
                sleep(2);
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
