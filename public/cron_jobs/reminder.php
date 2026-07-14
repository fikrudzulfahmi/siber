<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Cek akses via key
if (!isset($_GET['key']) || $_GET['key'] !== 'SiberRM_svbndks987839432') {
    die('Unauthorized');
}

// ------------------- KONFIGURASI -------------------
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd'; // Gunakan password asli Anda di sini
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV'; // Gunakan token asli Anda di sini
$debugMode = true; // SET KE FALSE UNTUK MULAI MENGIRIM PESAN ASLI
// ---------------------------------------------------

header('Content-Type: text/plain');

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- AMBIL SETTING DEBUG MODE DARI DATABASE DISINI ---
    $stmtSetting = $pdo->prepare("SELECT status FROM settings WHERE key_setting = 'wa_notif_jurnal' LIMIT 1");
    $stmtSetting->execute();
    $rowSetting = $stmtSetting->fetch(PDO::FETCH_ASSOC);

    // Konversi status 'true'/'false' dari DB ke boolean untuk $debugMode
    // Jika status di DB 'true', maka $debugMode harus false (agar kirim beneran)
    $dbStatus = $rowSetting ? $rowSetting['status'] : 'false';
    $debugMode = ($dbStatus === 'false');
    // -----------------------------------------------------

} catch (PDOException $e) {
    die("ERROR: Koneksi database gagal: " . $e->getMessage());
}



// Logika hari dan tanggal
$today = date('Y-m-d');
$dayNameMapping = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$todayName = $dayNameMapping[date('l')];

$monthMapping = [
    'January' => 'Januari',
    'February' => 'Februari',
    'March' => 'Maret',
    'April' => 'April',
    'May' => 'Mei',
    'June' => 'Juni',
    'July' => 'Juli',
    'August' => 'Agustus',
    'September' => 'September',
    'October' => 'Oktober',
    'November' => 'November',
    'December' => 'Desember'
];
$tanggalIndo = date('d ') . $monthMapping[date('F')] . date(' Y');

echo "=== MODE DEBUG: " . ($debugMode ? "AKTIF (TIDAK MENGIRIM WA)" : "NON-AKTIF (AKAN MENGIRIM WA)") . " ===\n";
echo "Hari ini: $todayName, $tanggalIndo\n\n";

// Query dengan DISTINCT untuk eliminasi duplikat di level database
$sql = "
  SELECT DISTINCT 
    e.id_employe, 
    e.nama, 
    e.no_wa, 
    m.nama_mapel, 
    jp.jam_mulai, 
    jp.jam_selesai,
    k.kelas
FROM jadwal_pelajaran jp
LEFT JOIN mapel_guru mg ON jp.id_mapel_guru = mg.id_mapel_guru
LEFT JOIN employe e ON mg.id_guru = e.id_employe
LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
LEFT JOIN kelas k ON mg.id_kelas = k.id_kelas
-- JOIN ke tabel tahun_pelajaran untuk filter status aktif
INNER JOIN tahun_pelajaran tp ON mg.id_tahun_pelajaran = tp.id_tahun_pelajaran 
    AND tp.status = 'Aktif'
-- Cek apakah sudah mengisi jurnal di hari dan jam yang sama
LEFT JOIN jurnal j ON j.id_mapel_guru = mg.id_mapel_guru 
    AND j.tanggal = :today
    AND j.jam_mulai = jp.jam_mulai 
WHERE
    LOWER(jp.hari) = LOWER(:todayName)
    AND j.id_jurnal IS NULL -- Menampilkan yang BELUM mengisi jurnal
    AND e.no_wa IS NOT NULL 
    AND e.no_wa != ''
ORDER BY e.nama, jp.jam_mulai
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':today' => $today, ':todayName' => $todayName]);
$jadwalBelumDiisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($jadwalBelumDiisi)) {
    echo "Semua guru sudah mengisi jurnal.\n";
    exit;
}

// Pengelompokan dan pencegahan duplikasi di level PHP
$guruUntukDiingatkan = [];
foreach ($jadwalBelumDiisi as $jadwal) {
    $id = $jadwal['id_employe'];
    $itemJadwal = "- {$jadwal['nama_mapel']} (Jam ke-{$jadwal['jam_mulai']}-{$jadwal['jam_selesai']}) Kelas {$jadwal['kelas']}";

    if (!isset($guruUntukDiingatkan[$id])) {
        $guruUntukDiingatkan[$id] = [
            'nama'   => $jadwal['nama'],
            'no_wa'  => $jadwal['no_wa'],
            'jadwal' => []
        ];
    }

    if (!in_array($itemJadwal, $guruUntukDiingatkan[$id]['jadwal'])) {
        $guruUntukDiingatkan[$id]['jadwal'][] = $itemJadwal;
    }
}

// Simulasi/Proses Pengiriman
foreach ($guruUntukDiingatkan as $id => $guru) {
    $daftarJadwal = implode("\n", $guru['jadwal']);
    $namaClean = trim($guru['nama']);
    $pesan = "*Pengingat Otomatis* ⏰\n\n" .
        "Yth. Bapak/Ibu *{$namaClean}*,\n\n" .
        "Anda terpantau belum mengisi jurnal pembelajaran untuk jadwal berikut pada hari ini, {$tanggalIndo}:\n" .
        "{$daftarJadwal}\n\n" .
        "Mohon segera melengkapinya di:\nhttps://siber.pondokminggirsari.com\n\n" .
        "Terima kasih,\n*SIBER PPRM*";

    echo "--------------------------------------------------\n";
    echo "KE: {$guru['nama']} ({$guru['no_wa']})\n";
    echo "ISI PESAN:\n\n$pesan\n";

    if (!$debugMode) {
        // Blok pengiriman asli
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['target' => $guru['no_wa'], 'message' => $pesan, 'delay' => '5'],
            CURLOPT_HTTPHEADER => ["Authorization: $fonnteToken"],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        echo "\n[STATUS KIRIM]: $response\n";
    } else {
        echo "\n[STATUS]: Berhasil disimulasikan (Pesan tidak benar-benar dikirim)\n";
    }
    echo "--------------------------------------------------\n\n";
}
