<?php
// File: test_keterlambatan.php
// VERSI FINAL: Skrip tes untuk Guru & Tendik berdasarkan tabel jadwal, mengabaikan Satpam.

// --- KONFIGURASI ---
$dbHost = 'localhost';
$dbName = 'ingintau_attendance';
$dbUser = 'ingintau_attendance';
$dbPass = 'TUsmekisa1968';
$logFile = __DIR__ . '/test_terlambat_log.txt';
$toleransiKeterlambatan = 0; // Menit
$idJabatanSatpam = 3; // Jabatan yang akan diabaikan
// --------------------

// Pengaturan dasar
set_time_limit(0);
date_default_timezone_set('Asia/Jakarta');
if (file_exists($logFile)) { unlink($logFile); }
header('Content-Type: text/plain; charset=utf-8');

function write_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

write_log("===== SESI TESTING KETERLAMBATAN DIMULAI (Guru & Tendik) =====");
$today = date('Y-m-d');
$namaHariIni = strtolower(date('l'));

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        GROUP BY e.pin, e.nama, e.no_wa
    ";
    $stmtJadwal = $pdo->prepare($sqlJadwal);
    $stmtJadwal->execute([':id_satpam' => $idJabatanSatpam, ':nama_hari_ini' => $namaHariIni]);
    $daftarPegawai = $stmtJadwal->fetchAll(PDO::FETCH_ASSOC);

    if (empty($daftarPegawai)) {
        write_log("Tidak ada Guru atau Tendik dengan jadwal hari ini.");
        exit;
    }
    write_log("Ditemukan " . count($daftarPegawai) . " pegawai (Guru/Tendik) dengan jadwal hari ini.");

    $rekapTerlambat = [];
    foreach ($daftarPegawai as $pegawai) {
        write_log("\n--- Memproses: {$pegawai['nama']} (Jadwal: {$pegawai['waktu_datang']}) ---");

        // Cek absensi
        $stmtAbsen = $pdo->prepare("SELECT MIN(scan_date) as jam_masuk FROM attendance WHERE pin = ? AND DATE(scan_date) = ? AND status = 'datang' AND LOWER(keterangan) = 'hadir'");
        $stmtAbsen->execute([$pegawai['pin'], $today]);
        $absen = $stmtAbsen->fetch(PDO::FETCH_ASSOC);

        if ($absen && $absen['jam_masuk']) {
            $waktuSeharusnya = strtotime($pegawai['waktu_datang']);
            $waktuAktual = strtotime($absen['jam_masuk']);
            $selisihMenit = round(($waktuAktual - $waktuSeharusnya) / 60);

            write_log("   Aktual: " . date('H:i:s', $waktuAktual) . " | Selisih: {$selisihMenit} menit.");

            if ($selisihMenit > $toleransiKeterlambatan) {
                write_log("   KEPUTUSAN: TERLAMBAT");
                $rekapTerlambat[] = $pegawai['nama'] . " (Terlambat {$selisihMenit} menit)";
            } else {
                write_log("   KEPUTUSAN: Tepat Waktu");
            }
        } else {
            write_log("   Status: Belum Absen / Keterangan bukan 'Hadir'. Dilewati.");
        }
    }
    
    write_log("\n--- REKAP HASIL TES ---");
    if (empty($rekapTerlambat)) {
        write_log("Tidak ada pegawai yang terdeteksi terlambat.");
    } else {
        write_log("Total pegawai terdeteksi terlambat: " . count($rekapTerlambat));
        foreach ($rekapTerlambat as $nama) {
            write_log("- " . $nama);
        }
    }

} catch (Exception $e) { 
    write_log("FATAL ERROR: " . $e->getMessage()); 
}

write_log("\n===== SESI TESTING SELESAI =====");
?>