<?php
// File: reminder.php
// Versi TAHAN BANTING dengan try...catch dan logging detail

// ------------------- KONFIGURASI -------------------
$dbHost = 'localhost';
$dbName = 'ingintau_attendance';
$dbUser = 'ingintau_attendance';
$dbPass = 'TUsmekisa1968';
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV';
$logFile = __DIR__ . '/reminder_log.txt';
// ---------------------------------------------------

// ✅ JARING PENGAMAN: Hilangkan batas waktu dan memori
set_time_limit(0);
ini_set('memory_limit', '-1');

// Atur zona waktu dan hapus log lama untuk pengujian baru
date_default_timezone_set('Asia/Jakarta');
if (file_exists($logFile)) { unlink($logFile); }

function write_log($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

write_log("===== CRON JOB DIMULAI (VERSI TAHAN BANTING) =====");

try {
    // 1. KONEKSI DATABASE
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    write_log("Koneksi database BERHASIL.");

    // ... (Fungsi isTanggalLibur dan pengecekan hari libur) ...
    $today = date('Y-m-d');
    $dayNameMapping = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $todayName = $dayNameMapping[date('l')];
    write_log("Hari ini: {$todayName}, {$today}.");

    // 3. AMBIL DATA GURU
    write_log("Menjalankan kueri untuk mencari guru...");
    $sql = "
        SELECT e.id_employe, e.nama, e.no_wa, m.nama_mapel, jp.jam_mulai, jp.jam_selesai
        FROM jadwal_pelajaran jp
        LEFT JOIN mapel_guru mg ON jp.id_mapel_guru = mg.id_mapel_guru
        LEFT JOIN employe e ON mg.id_guru = e.id_employe
        LEFT JOIN mapel m ON mg.id_mapel = mg.id_mapel
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

    if (empty($jadwalBelumDiisi)) {
        write_log("Tidak ada jadwal yang perlu diingatkan. Proses selesai.");
        exit;
    }
    write_log("Ditemukan " . count($jadwalBelumDiisi) . " total jadwal. Akan dikelompokkan menjadi guru unik.");

    // 4. KELOMPOKKAN JADWAL
    $guruUntukDiingatkan = [];
    foreach ($jadwalBelumDiisi as $jadwal) {
        $guruUntukDiingatkan[$jadwal['id_employe']]['nama'] = $jadwal['nama'];
        $guruUntukDiingatkan[$jadwal['id_employe']]['no_wa'] = $jadwal['no_wa'];
        $guruUntukDiingatkan[$jadwal['id_employe']]['jadwal_terlewat'][] = "- {$jadwal['nama_mapel']} (Jam {$jadwal['jam_mulai']}-{$jadwal['jam_selesai']})";
    }
    write_log("Ditemukan " . count($guruUntukDiingatkan) . " guru unik yang akan dikirimi pesan.");

    // 5. KIRIM PESAN
    function format_wa_number($number) {
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        if (substr($cleaned, 0, 1) == '0') { return '62' . substr($cleaned, 1); }
        return $cleaned;
    }

    $counter = 1;
    foreach ($guruUntukDiingatkan as $id => $guru) {
        // ✅ --- JARING PENGAMAN PER GURU DIMULAI ---
        try {
            write_log("--- Memproses Pesan ke-{$counter} untuk: {$guru['nama']} ---");
            
            $nomorTujuan = format_wa_number($guru['no_wa']);
            $daftarJadwal = implode("\n", $guru['jadwal_terlewat']);
            $pesan = "Pengingat Otomatis ⏰\n\nYth. Bapak/Ibu *{$guru['nama']}*,\n\nAnda terpantau belum mengisi jurnal untuk jadwal berikut hari ini:\n{$daftarJadwal}\n\nMohon segera melengkapinya di https://siber.ingintau.my.id .\n\nTerima kasih,\n*SIBER PPRM*";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, // Beri timeout 30 detik per request
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => ['target' => $nomorTujuan, 'message' => $pesan],
                CURLOPT_HTTPHEADER => ["Authorization: {$fonnteToken}"],
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            
            write_log("Pesan SUKSES dikirim ke {$guru['nama']}. Respons Fonnte: {$response}");

        } catch (Exception $e) {
            // Jika terjadi error, catat di log dan LANJUTKAN ke guru berikutnya
            write_log("!!! PROSES GAGAL UNTUK GURU: {$guru['nama']} (ID: {$id}) !!!");
            write_log("!!! ERROR: " . $e->getMessage());
        }
        // ✅ --- JARING PENGAMAN PER GURU SELESAI ---
        
        sleep(1);
        $counter++;
    }

} catch (Exception $e) {
    // Menangkap error fatal di luar loop (misal: koneksi DB gagal total)
    write_log("FATAL ERROR KESELURUHAN: " . $e->getMessage());
}

write_log("===== SEMUA PROSES SELESAI (TIDAK ADA LAGI GURU UNTUK DIPROSES) =====");
?>