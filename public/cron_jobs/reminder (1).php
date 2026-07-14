<?php
// File: reminder.php
// Versi Diperbaiki dengan Timezone Eksplisit dan Logging

// ------------------- KONFIGURASI -------------------
$dbHost = 'localhost';
$dbName = 'ingintau_attendance';
$dbUser = 'ingintau_attendance';
$dbPass = 'TUsmekisa1968';
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV';
$logFile = __DIR__ . '/reminder_log.txt'; // File untuk mencatat aktivitas
// ---------------------------------------------------

// ✅ --- LANGKAH 1: ATUR ZONA WAKTU SECARA EKSPLISIT ---
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan WIB (GMT+7)

// Fungsi untuk logging
function write_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

// 1. KONEKSI KE DATABASE (Tetap sama)
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    write_log("ERROR: Koneksi database gagal: " . $e->getMessage());
    die("ERROR: Koneksi database gagal: " . $e->getMessage());
}

// ... (Fungsi isTanggalLibur tetap sama) ...

// 2. LOGIKA PENGECEKAN HARI LIBUR
$today = date('Y-m-d');
$dayNameMapping = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$todayName = $dayNameMapping[date('l')];

write_log("Cron job dimulai. Hari ini: {$todayName}, {$today}.");

// ... (Pengecekan hari libur tetap sama) ...

// 3. JIKA BUKAN HARI LIBUR, LANJUTKAN PROSES...
$sql = "
    SELECT e.id_employe, e.nama, e.no_wa, m.nama_mapel, jp.jam_mulai, jp.jam_selesai
    FROM jadwal_pelajaran jp
    LEFT JOIN mapel_guru mg ON jp.id_mapel_guru = mg.id_mapel_guru
    LEFT JOIN employe e ON mg.id_guru = e.id_employe
    LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
    LEFT JOIN jurnal j ON j.id_mapel_guru = mg.id_mapel_guru AND DATE(j.created_at) = :today
    WHERE
        LOWER(jp.hari) = LOWER(:todayName)
        AND j.id_jurnal IS NULL
        AND e.no_wa IS NOT NULL AND e.no_wa != ''
    ORDER BY e.nama, jp.jam_mulai
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':today' => $today, ':todayName' => $todayName]);
$jadwalBelumDiisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ --- LANGKAH 2: LOGGING UNTUK DIAGNOSIS ---
if (empty($jadwalBelumDiisi)) {
    write_log("Tidak ada jadwal yang perlu diingatkan. Kueri tidak menemukan guru yang belum mengisi jurnal.");
    echo "Tidak ada jadwal yang perlu diingatkan.\n";
    exit;
}

write_log("Ditemukan " . count($jadwalBelumDiisi) . " jadwal yang belum diisi. Memulai proses pengiriman...");

// 4. KELOMPOKKAN JADWAL (Tetap sama)
$guruUntukDiingatkan = [];
// ... (logika foreach untuk mengelompokkan) ...

// ✅ --- LANGKAH 3: PEMFORMATAN NOMOR WA ---
function format_wa_number($number) {
    $cleaned = preg_replace('/[^0-9]/', '', $number);
    if (substr($cleaned, 0, 1) == '0') { return '62' . substr($cleaned, 1); }
    if (substr($cleaned, 0, 2) == '62') { return $cleaned; }
    return $number;
}

// 5. KIRIM PESAN
foreach ($guruUntukDiingatkan as $id => $guru) {
    $nomorTujuan = format_wa_number($guru['no_wa']); // Gunakan nomor yang sudah diformat
    $namaGuru = $guru['nama'];
    $daftarJadwal = implode("\n", $guru['jadwal_terlewat']);

    // ... (Logika menyusun $pesan tetap sama) ...
    $pesan = "Pengingat Otomatis ⏰\n\n" .
             "Yth. Bapak/Ibu *{$namaGuru}*,\n\n" .
             // ...
             "*SIBER PPRM*";
             
    // ... (Logika cURL untuk mengirim ke Fonnte tetap sama) ...
    $response = curl_exec($curl);
    curl_close($curl);
    
    write_log("Mengirim ke: {$namaGuru} ({$nomorTujuan}). Response Fonnte: {$response}");
    sleep(1);
}

write_log("Proses pengiriman selesai.");
?>