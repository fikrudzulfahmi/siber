<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek akses via key
if (!isset($_GET['key']) || $_GET['key'] !== 'SiberRM_svbndks987839432') {
    die('Unauthorized');
}

// File: rekap_terlambat.php
// VERSI ADAPTASI WA GATEWAY SENDIRI (Tanpa Fonnte)

// --- KONFIGURASI ---
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd';
$logFile = __DIR__ . '/rekap_terlambat_log.txt';
$toleransiKeterlambatan = 1; // Menit
$idJabatanSatpam = 3;
$debugMode = true; // Default awal

$nomorTujuanRekap = [
    // '6285790900076',
    // '6285645810609',
    // '6285815543137',
    // '6285735119674',
    // '6282139315007',
    '6281216898874', //nomor fikru untuk tes 
];
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
    echo $logContent;
}

write_log("===== CRON JOB REKAP KETERLAMBATAN DIMULAI =====");
$today = date('Y-m-d');
$namaHariIni = strtolower(date('l'));

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- AMBIL SETTING DEBUG MODE DARI DATABASE ---
    $stmtSetting = $pdo->prepare("SELECT status FROM settings WHERE key_setting = 'wa_notif_rekap_terlambat' LIMIT 1");
    $stmtSetting->execute();
    $rowSetting = $stmtSetting->fetch(PDO::FETCH_ASSOC);

    $dbStatus = $rowSetting ? $rowSetting['status'] : 'false';
    $debugMode = ($dbStatus === 'false');

    write_log("MODE DEBUG: " . ($debugMode ? "AKTIF (SIMULASI)" : "NON-AKTIF (MASUK ANTRIAN WA)"));
    // ----------------------------------------------

    // Cek hari libur
    $stmtLibur = $pdo->prepare("SELECT keterangan FROM hari_libur WHERE :today BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
    $stmtLibur->execute([':today' => $today]);
    if ($stmtLibur->fetch()) {
        write_log("Hari ini libur. Proses dihentikan.");
        exit;
    }

    $toleransiSql = "00:" . str_pad($toleransiKeterlambatan, 2, '0', STR_PAD_LEFT) . ":00";
    $sql = "
        SELECT 
            e.nama,
            MIN(j.waktu_datang) AS jam_masuk_seharusnya,
            (SELECT MIN(scan_date) FROM attendance WHERE pin = e.pin AND DATE(scan_date) = :today AND status = 'datang' AND LOWER(keterangan) = 'hadir') AS jam_absen_aktual
        FROM jadwal j 
        JOIN employe e ON e.pin = j.id_employee
        WHERE 
            e.id_jabatan != :id_satpam
            AND LOWER(j.day) = :nama_hari_ini
        GROUP BY e.pin, e.nama
        HAVING 
            jam_absen_aktual IS NOT NULL 
            AND TIME(jam_absen_aktual) > ADDTIME(jam_masuk_seharusnya, :toleransi)
        ORDER BY e.nama ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_satpam' => $idJabatanSatpam,
        ':nama_hari_ini' => $namaHariIni,
        ':today' => $today,
        ':toleransi' => $toleransiSql
    ]);
    $pegawaiTerlambat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Siapkan pesan
    if (empty($pegawaiTerlambat)) {
        $pesan = "*Laporan Keterlambatan Harian* ✅\n\n" .
            "Yth. Bapak/Ibu Pimpinan,\n\n" .
            "Untuk hari ini, " . date('d F Y') . ", tidak ada pegawai yang tercatat datang terlambat.\n\n" .
            "Terima kasih.\n*SIBER PPRM*";
    } else {
        $daftarTerlambat = [];
        $no = 1;
        foreach ($pegawaiTerlambat as $pegawai) {
            $selisihMenit = round((strtotime($pegawai['jam_absen_aktual']) - strtotime($pegawai['jam_masuk_seharusnya'])) / 60);
            $jamAktualFormatted = date('H:i', strtotime($pegawai['jam_absen_aktual']));
            $namaClean = trim($pegawai['nama']);
            $daftarTerlambat[] = "{$no}. *{$namaClean}* (Masuk: " . date('H:i', strtotime($pegawai['jam_masuk_seharusnya'])) . ", Absen: {$jamAktualFormatted}, Telat: {$selisihMenit} mnt)";
            $no++;
        }
        $pesan = "*Laporan Keterlambatan Harian* 📋\n\n" .
            "Yth. Bapak/Ibu Pimpinan,\n\n" .
            "Berikut rekapitulasi pegawai terlambat hari ini, " . date('d F Y') . ":\n\n" .
            implode("\n", $daftarTerlambat) . "\n\n" .
            "Mohon untuk ditindaklanjuti.\n\n*SIBER PPRM*";
    }

    // Eksekusi Pengiriman (Insert ke Database Hosting)
    write_log("Memproses rekap untuk " . count($nomorTujuanRekap) . " nomor pimpinan...");

    if ($debugMode) {
        write_log("STATUS: Simulasi Aktif. Isi pesan rekap:");
        write_log("\n----------------------------\n" . $pesan . "\n----------------------------");
    }

    // Siapkan query insert
    $stmtInsert = $pdo->prepare("INSERT INTO outbox_wa (nomor, pesan, status) VALUES (?, ?, 'pending')");

    foreach ($nomorTujuanRekap as $nomor) {
        if (!$debugMode) {
            // Masukkan ke tabel antrian hosting, bukan cURL ke fonnte
            $stmtInsert->execute([$nomor, $pesan]);
            write_log("-> Pesan disimpan ke tabel outbox_wa (pending) untuk: {$nomor}");
        } else {
            write_log("-> [SIMULASI] Ke nomor pimpinan: {$nomor}");
        }
    }
} catch (Exception $e) {
    write_log("FATAL ERROR: " . $e->getMessage());
}

write_log("\n===== SEMUA PROSES SELESAI =====");
