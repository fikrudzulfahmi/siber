<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek akses via key
if (!isset($_GET['key']) || $_GET['key'] !== 'SiberRM_svbndks987839432') {
    die('Unauthorized');
}

// File: reminder_terlambat.php
// VERSI FINAL: Ditambahkan Fitur Debug Mode dari Database

// --- KONFIGURASI ---
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd';
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV';
$logFile = __DIR__ . '/terlambat_log.txt';
$toleransiKeterlambatan = 1; // Menit
$idJabatanSatpam = 3; // Jabatan yang akan diabaikan
$debugMode = true; // Default awal
// --------------------

// Pengaturan dasar
set_time_limit(0);
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: text/plain');

if (file_exists($logFile)) {
    unlink($logFile);
}

function write_log($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logContent = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logContent, FILE_APPEND);
    echo $logContent; // Juga tampilkan di browser/terminal
}

write_log("===== CRON JOB NOTIFIKASI KETERLAMBATAN DIMULAI =====");
$today = date('Y-m-d');
$namaHariIni = strtolower(date('l'));

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- AMBIL SETTING DEBUG MODE DARI DATABASE ---
    // Menggunakan key yang sama dengan skrip jurnal atau sesuaikan dengan key baru (misal: 'wa_notif_terlambat')
    $stmtSetting = $pdo->prepare("SELECT status FROM settings WHERE key_setting = 'wa_notif_terlambat' LIMIT 1");
    $stmtSetting->execute();
    $rowSetting = $stmtSetting->fetch(PDO::FETCH_ASSOC);

    // Jika status di DB 'true' (aktif mengirim), maka $debugMode = false
    // Jika status di DB 'false' (matikan pengiriman), maka $debugMode = true
    $dbStatus = $rowSetting ? $rowSetting['status'] : 'false';
    $debugMode = ($dbStatus === 'false');

    write_log("MODE DEBUG: " . ($debugMode ? "AKTIF (SIMULASI)" : "NON-AKTIF (KIRIM ASLI)"));
    // ----------------------------------------------

    // Cek hari libur
    $stmtLibur = $pdo->prepare("SELECT keterangan FROM hari_libur WHERE :today BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
    $stmtLibur->execute([':today' => $today]);
    if ($stmtLibur->fetch()) {
        write_log("Hari ini libur. Proses dihentikan.");
        exit;
    }

    // Ambil semua pegawai (kecuali Satpam) yang punya jadwal hari ini
    $sqlJadwal = "
        SELECT e.pin, e.nama, e.no_wa, MIN(j.waktu_datang) AS waktu_datang
        FROM employe e 
        JOIN jadwal j ON e.pin = j.id_employee 
        WHERE 
            e.id_jabatan != :id_satpam 
            AND LOWER(j.day) = :nama_hari_ini 
            AND e.no_wa IS NOT NULL AND e.no_wa != ''
        GROUP BY e.pin, e.nama, e.no_wa
    ";
    $stmtJadwal = $pdo->prepare($sqlJadwal);
    $stmtJadwal->execute([':id_satpam' => $idJabatanSatpam, ':nama_hari_ini' => $namaHariIni]);
    $daftarPegawai = $stmtJadwal->fetchAll(PDO::FETCH_ASSOC);

    foreach ($daftarPegawai as $pegawai) {
        write_log("\n--- Memproses: {$pegawai['nama']} (Jadwal: {$pegawai['waktu_datang']}) ---");

        // Cek absensi
        $stmtAbsen = $pdo->prepare("SELECT MIN(scan_date) as jam_masuk FROM attendance WHERE pin = ? AND DATE(scan_date) = ? AND status = 'datang' AND LOWER(keterangan) = 'hadir'");
        $stmtAbsen->execute([$pegawai['pin'], $today]);
        $absen = $stmtAbsen->fetch(PDO::FETCH_ASSOC);

        if ($absen && $absen['jam_masuk']) {
            $selisihMenit = round((strtotime($absen['jam_masuk']) - strtotime($pegawai['waktu_datang'])) / 60);

            if ($selisihMenit > $toleransiKeterlambatan) {
                write_log("KEPUTUSAN: TERLAMBAT ({$selisihMenit} Menit).");
                $namaClean = trim($pegawai['nama']);
                $pesan = "*Pemberitahuan Keterlambatan* ⏰\n\n" .
                    "Yth. Bapak/Ibu *{$namaClean}*,\n\n" .
                    "Sistem mencatat Anda melakukan presensi masuk pada jam " . date('H:i:s', strtotime($absen['jam_masuk'])) . ".\n" .
                    "Jadwal masuk Anda seharusnya adalah jam " . date('H:i:s', strtotime($pegawai['waktu_datang'])) . ".\n\n" .
                    "Mohon untuk dapat hadir lebih tepat waktu di kemudian hari.\n\n" .
                    "Terima kasih,\n" .
                    "*SIBER PPRM*";

                if (!$debugMode) {
                    // Mengirim pesan asli dengan cURL
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://api.fonnte.com/send',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => ['target' => $pegawai['no_wa'], 'message' => $pesan],
                        CURLOPT_HTTPHEADER => ["Authorization: {$fonnteToken}"],
                    ));
                    $response = curl_exec($curl);
                    curl_close($curl);

                    write_log("STATUS: Pesan dikirim ke {$pegawai['no_wa']}. Respons: {$response}");
                    sleep(2); // Delay sedikit untuk menghindari rate limit API
                } else {
                    write_log("STATUS: Simulasi (Pesan tidak dikirim karena Mode Debug Aktif)");
                    write_log("ISI PESAN:\n{$pesan}");
                }
            } else {
                write_log("KEPUTUSAN: TEPAT WAKTU.");
            }
        } else {
            write_log("KEPUTUSAN: BELUM ABSEN / TIDAK HADIR.");
        }
    }
} catch (Exception $e) {
    write_log("FATAL ERROR: " . $e->getMessage());
}

write_log("\n===== SEMUA PROSES SELESAI =====");
