<?php
// File: test_keterlambatan.php
// Skrip KHUSUS untuk testing logika keterlambatan tanpa mengirim WA.

// ------------------- KONFIGURASI -------------------
$dbHost = 'localhost';
$dbName = 'ingintau_attendance';
$dbUser = 'ingintau_attendance';
$dbPass = 'TUsmekisa1968';
$logFile = __DIR__ . '/test_terlambat_log.txt'; // Log khusus untuk tes
$toleransiKeterlambatan = 0; // Atur toleransi sesuai kebutuhan tes
// ---------------------------------------------------

// Pengaturan dasar
set_time_limit(0);
date_default_timezone_set('Asia/Jakarta');
if (file_exists($logFile)) { unlink($logFile); }
header('Content-Type: text/plain'); // Tampilkan output sebagai teks biasa

function write_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage; // Tampilkan juga di layar untuk tes via browser
}

write_log("===== SESI TESTING KETERLAMBATAN DIMULAI =====");

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    write_log("Koneksi database BERHASIL.");
    
    $namaHariIni = strtolower(date('l'));
    write_log("Mencari jadwal untuk hari: {$namaHariIni}");

    $sql = "SELECT j.id_employee, e.nama, e.no_wa, j.waktu_datang AS jam_masuk_seharusnya
            FROM jadwal j JOIN employe e ON j.id_employee = e.id_employe
            WHERE LOWER(j.day) = :nama_hari_ini";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nama_hari_ini' => $namaHariIni]);
    $jadwalHariIni = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    write_log("Ditemukan " . count($jadwalHariIni) . " guru dengan jadwal hari ini.");

    if (empty($jadwalHariIni)) {
        write_log("Tidak ada jadwal. Selesai.");
        exit;
    }

    $rekapTerlambat = [];
    foreach ($jadwalHariIni as $id_guru => $dataJadwal) {
        $guru = $dataJadwal[0];
        $jamMasukSeharusnya = $guru['jam_masuk_seharusnya'];
        
        write_log("\n--- Memproses: {$guru['nama']} ---");
        write_log("   Jadwal Masuk Seharusnya: {$jamMasukSeharusnya}");

        $sqlCekAbsen = "SELECT MIN(scan_date) AS jam_absen_pertama FROM attendance
                        WHERE pin = (SELECT pin FROM employe WHERE id_employe = :id_guru)
                        AND DATE(scan_date) = CURDATE()";
        $stmtCekAbsen = $pdo->prepare($sqlCekAbsen);
        $stmtCekAbsen->execute([':id_guru' => $id_guru]);
        $hasilAbsen = $stmtCekAbsen->fetch(PDO::FETCH_ASSOC);
        
        $jamAbsenAktual = $hasilAbsen['jam_absen_pertama'];

        if (!$jamAbsenAktual) {
            write_log("   Status: BELUM ABSEN MASUK");
            continue;
        }
        
        write_log("   Jam Absen Aktual: " . date('H:i:s', strtotime($jamAbsenAktual)));

        $waktuSeharusnya = strtotime($jamMasukSeharusnya);
        $waktuAktual = strtotime($jamAbsenAktual);
        $selisihMenit = round(($waktuAktual - $waktuSeharusnya) / 60);

        write_log("   Perhitungan Selisih: {$selisihMenit} menit.");

        if ($selisihMenit > $toleransiKeterlambatan) {
            write_log("   KEPUTUSAN: TERLAMBAT");
            write_log("   ---> [DRY RUN] Seharusnya mengirim notifikasi ke {$guru['no_wa']}");
            $rekapTerlambat[] = $guru['nama'] . " (Terlambat {$selisihMenit} menit)";
        } else {
            write_log("   KEPUTUSAN: TEPAT WAKTU");
        }
    }
    
    write_log("\n--- REKAP HASIL TES ---");
    if (empty($rekapTerlambat)) {
        write_log("Tidak ada guru yang terdeteksi terlambat.");
    } else {
        write_log("Total guru terlambat: " . count($rekapTerlambat));
        foreach ($rekapTerlambat as $nama) {
            write_log("- " . $nama);
        }
    }

} catch (Exception $e) {
    write_log("FATAL ERROR: " . $e->getMessage());
}

write_log("===== SESI TESTING SELESAI =====");
?>