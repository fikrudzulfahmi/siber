<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Muat konfigurasi database & rahasia
require_once __DIR__ . '/../../config/database.php';
if (is_file(__DIR__ . '/../../config/secrets.php')) {
    require_once __DIR__ . '/../../config/secrets.php';
}
require_once __DIR__ . '/../../app/config.php';

// Cek akses via key (CLI dibebaskan atau mencocokkan key bila diberikan)
$isCli = (PHP_SAPI === 'cli' || php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? null;
if ($isCli && $providedKey === null && isset($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $arg) {
        if (str_starts_with($arg, '--key=')) {
            $providedKey = substr($arg, 6);
        } elseif (str_starts_with($arg, 'key=')) {
            $providedKey = substr($arg, 4);
        }
    }
}

if (!$isCli || $providedKey !== null) {
    if (!isset($providedKey) || !defined('CRON_KEY') || CRON_KEY === '' || !hash_equals(CRON_KEY, (string) $providedKey)) {
        if (PHP_SAPI !== 'cli') { http_response_code(403); }
        die('Unauthorized');
    }
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

// Selaraskan zona waktu dengan cronjob lain (server hosting bisa ber-UTC,
// akibatnya tanggal 'hari ini' meleset 7 jam dan pesan pagi tidak ikut dibuat).
date_default_timezone_set('Asia/Jakarta');

// Helper pengiriman WA: kirim langsung ke endpoint WA Gateway
require_once __DIR__ . '/_wa_outbox.php';

// ------------------- KONFIGURASI -------------------
$debugMode = true; // SET KE FALSE UNTUK MULAI MENGIRIM PESAN ASLI
// ---------------------------------------------------

header('Content-Type: text/plain');

try {
    $database = new Database();
    $pdo = $database->getConnection();

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
        // Kirim langsung ke WA Gateway endpoint (mode PUSH)
        $hasilKirim = wa_kirim($guru['no_wa'], $pesan, $pdo);
        echo "[STATUS KIRIM]: " . wa_kirim_hasil_log($hasilKirim) . PHP_EOL;
    } else {
        echo "\n[STATUS]: Berhasil disimulasikan (Pesan tidak benar-benar dikirim)\n";
    }
    echo "--------------------------------------------------\n\n";
}
